<?php
// ================================================
// Hospital Geral do Bengo — Model: Senha
// Operações sobre a fila de espera
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Senha
{

    /**
     * Devolve toda a fila de espera ordenada por prioridade
     * e hora de chegada (mais antigo primeiro dentro da mesma prioridade)
     */
    public static function filaEspera(): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT s.id, s.codigo, s.prioridade, s.estado,
                    s.criado_em,
                    p.nome AS paciente_nome,
                    p.idade,
                    ta.nome AS tipo_atendimento
             FROM senhas s
             JOIN pacientes p  ON s.paciente_id = p.id
             JOIN tipos_atendimento ta 
                  ON s.tipo_atendimento_id = ta.id
             WHERE s.estado = 'espera'
             ORDER BY s.prioridade ASC, s.criado_em ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Conta pacientes em espera por prioridade
     */
    public static function contarPorEstado(
        string $estado = 'espera'
    ): int {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM senhas WHERE estado = :e"
        );
        $stmt->execute([':e' => $estado]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta urgentes em espera
     */
    public static function contarUrgentes(): int
    {
        $db = Database::ligar();
        $stmt = $db->query(
            "SELECT COUNT(*) FROM senhas 
             WHERE estado = 'espera' AND prioridade = 1"
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Total de atendidos hoje (concluídos + cancelados)
     */
    public static function atendidosHoje(): int
    {
        $db = Database::ligar();
        $stmt = $db->query(
            "SELECT COUNT(*) FROM senhas 
             WHERE estado IN ('concluida','cancelada')
             AND DATE(criado_em) = CURDATE()"
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Tempo médio de espera hoje (em minutos)
     */
    public static function tempoMedioEspera(): int
    {
        $db = Database::ligar();
        $stmt = $db->query(
            "SELECT AVG(
                TIMESTAMPDIFF(MINUTE, criado_em, hora_chamada)
             ) 
             FROM senhas 
             WHERE hora_chamada IS NOT NULL
             AND DATE(criado_em) = CURDATE()"
        );
        $resultado = $stmt->fetchColumn();
        return $resultado ? (int) round($resultado) : 0;
    }

    /**
     * Gera o próximo código de senha para uma prioridade
     * Formato: U-001, I-001, G-001, N-001
     */
    public static function gerarCodigo(int $prioridade, string $dataReferencia = null): string
    {
        $prefixos = [
            1 => 'U',
            2 => 'I',
            3 => 'G',
            4 => 'N'
        ];
        $prefixo = $prefixos[$prioridade] ?? 'N';
        $data = $dataReferencia ?: date('Y-m-d');

        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM senhas s
             LEFT JOIN marcacoes m ON s.marcacao_id = m.id
             WHERE s.codigo LIKE :p
             AND (
                 m.data_consulta = :d1
                 OR (m.id IS NULL AND DATE(s.criado_em) = :d2)
             )"
        );
        $stmt->execute([':p' => $prefixo . '-%', ':d1' => $data, ':d2' => $data]);
        $total = (int) $stmt->fetchColumn();

        return $prefixo . '-' . str_pad($total + 1, 3, '0', STR_PAD_LEFT);
    }
    /**
     * Devolve a próxima senha em espera 
     * (maior prioridade + mais antiga)
     */
    public static function proxima(): ?array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT s.id, s.codigo, s.prioridade,
                    p.nome AS paciente_nome,
                    p.idade,
                    ta.nome AS tipo_atendimento,
                    s.criado_em
             FROM senhas s
             JOIN pacientes p 
                  ON s.paciente_id = p.id
             JOIN tipos_atendimento ta 
                  ON s.tipo_atendimento_id = ta.id
             WHERE s.estado = 'espera'
             ORDER BY s.prioridade ASC, s.criado_em ASC
             LIMIT 1"
        );
        $stmt->execute();
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Chama um paciente — muda estado para 'chamada'
     * Regista hora de chamada e consultório
     */
    public static function chamar(
        int $senhaId,
        int $medicoId,
        int $consultorioId
    ): bool {
        $db = Database::ligar();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "UPDATE senhas 
                 SET estado         = 'chamada',
                     atendido_por   = :medico,
                     consultorio_id = :cons,
                     hora_chamada   = NOW()
                 WHERE id = :id 
                 AND estado = 'espera'"
            );
            $stmt->execute([
                ':medico' => $medicoId,
                ':cons' => $consultorioId,
                ':id' => $senhaId,
            ]);
            
            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                return false;
            }
            
            // Sync com marcacoes se existir
            $stmt2 = $db->prepare(
                "UPDATE marcacoes m 
                 JOIN senhas s ON s.marcacao_id = m.id 
                 SET m.estado = 'em_atendimento', m.atualizado_em = NOW() 
                 WHERE s.id = :id AND m.estado = 'confirmada'"
            );
            $stmt2->execute([':id' => $senhaId]);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Conclui o atendimento — muda estado para 'concluida'
     */
    public static function concluir(int $senhaId): bool
    {
        $db = Database::ligar();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "UPDATE senhas 
                 SET estado          = 'concluida',
                     hora_conclusao  = NOW()
                 WHERE id    = :id 
                 AND estado  = 'chamada'"
            );
            $stmt->execute([':id' => $senhaId]);
            
            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                return false;
            }
            
            // Sync com marcacoes se existir
            $stmt2 = $db->prepare(
                "UPDATE marcacoes m 
                 JOIN senhas s ON s.marcacao_id = m.id 
                 SET m.estado = 'concluida', m.atualizado_em = NOW() 
                 WHERE s.id = :id AND m.estado = 'em_atendimento'"
            );
            $stmt2->execute([':id' => $senhaId]);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Cancela senha por ausência do paciente
     */
    public static function cancelar(int $senhaId): bool
    {
        $db = Database::ligar();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "UPDATE senhas 
                 SET estado         = 'cancelada',
                     hora_conclusao = NOW()
                 WHERE id = :id 
                 AND estado IN ('espera','chamada')"
            );
            $stmt->execute([':id' => $senhaId]);
            
            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                return false;
            }
            
            // Sync com marcacoes se existir
            $stmt2 = $db->prepare(
                "UPDATE marcacoes m 
                 JOIN senhas s ON s.marcacao_id = m.id 
                 SET m.estado = 'falta', m.atualizado_em = NOW() 
                 WHERE s.id = :id AND m.estado IN ('confirmada', 'em_atendimento')"
            );
            $stmt2->execute([':id' => $senhaId]);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Desfaz uma chamada — volta para 'espera'
     * Só funciona se a chamada foi feita há menos de 15s
     */
    public static function desfazerChamada(
        int $senhaId
    ): bool {
        $db = Database::ligar();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "UPDATE senhas 
                 SET estado         = 'espera',
                     atendido_por   = NULL,
                     consultorio_id = NULL,
                     hora_chamada   = NULL
                 WHERE id    = :id 
                 AND estado  = 'chamada'
                 AND TIMESTAMPDIFF(SECOND, hora_chamada, NOW()) <= 15"
            );
            $stmt->execute([':id' => $senhaId]);
            
            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                return false;
            }
            
            // Sync com marcacoes se existir
            $stmt2 = $db->prepare(
                "UPDATE marcacoes m 
                 JOIN senhas s ON s.marcacao_id = m.id 
                 SET m.estado = 'confirmada', m.atualizado_em = NOW() 
                 WHERE s.id = :id AND m.estado = 'em_atendimento'"
            );
            $stmt2->execute([':id' => $senhaId]);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Devolve a senha actualmente em atendimento 
     * (estado = 'chamada') para um médico
     */
    public static function emAtendimento(
        int $medicoId
    ): ?array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT s.id, s.codigo, s.prioridade,
                    s.hora_chamada,
                    p.nome AS paciente_nome,
                    ta.nome AS tipo_atendimento,
                    c.nome AS consultorio
             FROM senhas s
             JOIN pacientes p  
                  ON s.paciente_id = p.id
             JOIN tipos_atendimento ta 
                  ON s.tipo_atendimento_id = ta.id
             LEFT JOIN consultorios c 
                  ON s.consultorio_id = c.id
             WHERE s.estado       = 'chamada'
             AND   s.atendido_por = :medico
             ORDER BY s.hora_chamada DESC
             LIMIT 1"
        );
        $stmt->execute([':medico' => $medicoId]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Devolve o consultório do médico logado
     */
    public static function consultorioDoMedico(
        int $medicoId
    ): ?array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT id, nome FROM consultorios
             WHERE responsavel LIKE CONCAT(
                 '%',
                 (SELECT nome FROM utilizadores 
                  WHERE id = :id),
                 '%'
             )
             AND activo = 1
             LIMIT 1"
        );
        $stmt->execute([':id' => $medicoId]);
        $resultado = $stmt->fetch();
        // Fallback: usa o primeiro consultório activo
        if (!$resultado) {
            $resultado = $db->query(
                "SELECT id, nome FROM consultorios 
                 WHERE activo = 1 
                 ORDER BY id LIMIT 1"
            )->fetch();
        }
        return $resultado ?: null;
    }
    /**
     * Devolve a senha actualmente em chamada 
     * (para o painel público)
     */
    public static function emChamadaAgora(): ?array
    {
        $db = Database::ligar();
        $stmt = $db->query(
            "SELECT s.codigo, s.prioridade,
                    c.nome AS consultorio,
                    p.nome AS paciente_nome,
                    p.idade AS paciente_idade
             FROM senhas s
             LEFT JOIN consultorios c 
                  ON s.consultorio_id = c.id
             LEFT JOIN pacientes p
                  ON s.paciente_id = p.id
             WHERE s.estado = 'chamada'
             ORDER BY s.hora_chamada DESC
             LIMIT 1"
        );
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Devolve as próximas senhas em espera 
     * (para o painel público — máx. 3)
     */
    public static function proximasParaPainel(
        int $limite = 3
    ): array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT s.codigo, s.prioridade
             FROM senhas s
             WHERE s.estado = 'espera'
             ORDER BY s.prioridade ASC, s.criado_em ASC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Devolve as últimas senhas já atendidas 
     * (concluídas — máx. 3)
     */
    public static function ultimasConcluidas(
        int $limite = 3
    ): array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT codigo, prioridade
             FROM senhas
             WHERE estado = 'concluida'
             AND DATE(criado_em) = CURDATE()
             ORDER BY hora_conclusao DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Devolve as últimas senhas canceladas 
     * por ausência (máx. 2)
     */
    public static function ultimasCanceladas(
        int $limite = 2
    ): array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT codigo, prioridade
             FROM senhas
             WHERE estado = 'cancelada'
             AND DATE(criado_em) = CURDATE()
             ORDER BY hora_conclusao DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Devolve o tempo médio de espera hoje (em minutos)
     * para exibir no painel público
     */
    public static function tempoMedioPublico(): int
    {
        $db = Database::ligar();
        $stmt = $db->query(
            "SELECT AVG(
                TIMESTAMPDIFF(MINUTE, criado_em, hora_chamada)
             )
             FROM senhas
             WHERE hora_chamada IS NOT NULL
             AND DATE(criado_em) = CURDATE()"
        );
        $r = $stmt->fetchColumn();
        return $r ? (int) round($r) : 0;
    }

    // ================================================
    // Fase 2 — Filas por Especialidade
    // ================================================

    /**
     * Devolve a especialidade do médico logado
     */
    public static function especialidadeDoMedico(
        int $medicoId
    ): ?array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT e.id, e.nome, u.aceitar_walkins
             FROM utilizadores u
             LEFT JOIN especialidades e
                  ON u.especialidade_id = e.id
             WHERE u.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $medicoId]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /**
     * Devolve o consultório atribuído ao médico (v2)
     * Usa a coluna consultorio_id em utilizadores
     */
    public static function consultorioDoMedicoV2(
        int $medicoId
    ): ?array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT c.id, c.nome
             FROM utilizadores u
             JOIN consultorios c
                  ON u.consultorio_id = c.id
             WHERE u.id = :id
             AND c.activo = 1
             LIMIT 1"
        );
        $stmt->execute([':id' => $medicoId]);
        $r = $stmt->fetch();
        if ($r)
            return $r;

        // Fallback: método original
        return self::consultorioDoMedico($medicoId);
    }

    /**
     * Fila filtrada pela especialidade do médico
     * Se o médico não tem especialidade, mostra tudo
     */
    public static function filaDoMedico(
        int $medicoId
    ): array {
        $db = Database::ligar();

        // Obter especialidade do médico (e aceitar_walkins)
        $esp = self::especialidadeDoMedico($medicoId);
        $aceitaWalkins = $esp ? (int) $esp['aceitar_walkins'] : 1;

        if ($esp && $esp['id']) {
            // Filtrar: tipos de atendimento que correspondam
            // à especialidade (via id)
            $stmt = $db->prepare(
                "SELECT s.id, s.codigo, s.prioridade,
                        s.estado, s.criado_em, s.origem,
                        p.nome AS paciente_nome,
                        p.idade,
                        ta.nome AS tipo_atendimento
                 FROM senhas s
                 JOIN pacientes p  ON s.paciente_id = p.id
                 JOIN tipos_atendimento ta
                      ON s.tipo_atendimento_id = ta.id
                 LEFT JOIN marcacoes m ON s.marcacao_id = m.id
                 WHERE s.estado = 'espera'
                 AND (
                     m.data_consulta = CURDATE() 
                     OR 
                     (m.id IS NULL AND DATE(s.criado_em) = CURDATE())
                 )
                 AND (
                    ta.especialidade_id = :esp_id
                    OR s.prioridade = 1
                 )
                 AND (
                    :aceita_walkins = 1
                    OR s.origem != 'mesmo_dia'
                    OR s.prioridade = 1
                 )
                 ORDER BY s.prioridade ASC,
                          s.criado_em ASC"
            );
            $stmt->execute([
                ':esp_id' => $esp['id'],
                ':aceita_walkins' => $aceitaWalkins
            ]);
        } else {
            // Sem especialidade? Mostra fila global
            $stmt = $db->prepare(
                "SELECT s.id, s.codigo, s.prioridade,
                        s.estado, s.criado_em, s.origem,
                        p.nome AS paciente_nome,
                        p.idade,
                        ta.nome AS tipo_atendimento
                 FROM senhas s
                 JOIN pacientes p  ON s.paciente_id = p.id
                 JOIN tipos_atendimento ta
                      ON s.tipo_atendimento_id = ta.id
                 LEFT JOIN marcacoes m ON s.marcacao_id = m.id
                 WHERE s.estado = 'espera'
                 AND (
                     m.data_consulta = CURDATE() 
                     OR 
                     (m.id IS NULL AND DATE(s.criado_em) = CURDATE())
                 )
                 AND (
                    :aceita_walkins = 1
                    OR s.origem != 'mesmo_dia'
                    OR s.prioridade = 1
                 )
                 ORDER BY s.prioridade ASC,
                          s.criado_em ASC"
            );
            $stmt->execute([':aceita_walkins' => $aceitaWalkins]);
        }

        return $stmt->fetchAll();
    }

    /**
     * Próxima senha filtrada para o médico
     */
    public static function proximaDoMedico(
        int $medicoId
    ): ?array {
        $fila = self::filaDoMedico($medicoId);
        return $fila[0] ?? null;
    }

    /**
     * Conta pacientes em espera para o médico
     */
    public static function contarEsperaDoMedico(
        int $medicoId
    ): int {
        return count(self::filaDoMedico($medicoId));
    }

    /**
     * Devolve a distribuição da fila por prioridade para o médico
     * Retorna array com contagem por cada tipo de prioridade
     */
    public static function distribuicaoPrioridade(int $medicoId): array
    {
        $fila = self::filaDoMedico($medicoId);
        $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        foreach ($fila as $s) {
            $p = (int) $s['prioridade'];
            if (isset($dist[$p])) $dist[$p]++;
        }
        return $dist;
    }

    /**
     * FASE 10: Devolve a contagem de senhas emitidas por hora no dia atual (para a recepção)
     */
    public static function fluxoHorario(): array
    {
        $db = Database::ligar();
        $stmt = $db->query(
            "SELECT HOUR(criado_em) AS hora, COUNT(*) AS volume
             FROM senhas
             WHERE DATE(criado_em) = CURDATE()
             GROUP BY HOUR(criado_em)
             ORDER BY hora ASC"
        );
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Preenche as horas de serviço do hospital
        $horas = [];
        for ($i = 6; $i <= 20; $i++) {
            $hFormat = sprintf("%02d:00", $i);
            $horas[$hFormat] = 0;
        }

        foreach ($resultado as $row) {
            $hFormat = sprintf("%02d:00", $row['hora']);
            if (isset($horas[$hFormat])) {
                $horas[$hFormat] = (int) $row['volume'];
            }
        }

        return [
            'labels' => array_keys($horas),
            'data' => array_values($horas)
        ];
    }
}
