<?php
// ================================================
// Hospital Geral do Bengo
// Model: Utilizador (Gerenciamento de Perfil e Histórico Pessoal)
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Utilizador
{
    /**
     * Obtém os dados completos de um utilizador,
     * incluindo cargo, especialidade (médico) e foto
     */
    public static function obter(int $id): ?array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT u.id, u.nome, u.perfil, u.nome_utilizador, u.telefone, u.foto_path,
                    e.nome AS especialidade, c.nome AS consultorio
             FROM utilizadores u
             LEFT JOIN especialidades e ON u.especialidade_id = e.id
             LEFT JOIN consultorios c ON u.consultorio_id = c.id
             WHERE u.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Actualiza o perfil do utilizador (nome e foto)
     */
    public static function actualizarPerfil(int $id, string $nome, ?string $telefone, ?string $fotoPath): bool
    {
        $db = Database::ligar();
        if ($fotoPath) {
            $stmt = $db->prepare(
                "UPDATE utilizadores SET nome = :nome, telefone = :tel, foto_path = :foto WHERE id = :id"
            );
            return $stmt->execute([
                ':nome' => trim($nome),
                ':tel' => trim($telefone),
                ':foto' => $fotoPath,
                ':id' => $id
            ]);
        } else {
            $stmt = $db->prepare(
                "UPDATE utilizadores SET nome = :nome, telefone = :tel WHERE id = :id"
            );
            return $stmt->execute([
                ':nome' => trim($nome),
                ':tel' => trim($telefone),
                ':id' => $id
            ]);
        }
    }

    /**
     * Altera a senha do utilizador
     */
    public static function alterarSenha(int $id, string $senhaAntiga, string $senhaNova): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare("SELECT senha_hash FROM utilizadores WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $hashAntigo = $stmt->fetchColumn();

        if (password_verify($senhaAntiga, $hashAntigo)) {
            $novoHash = password_hash($senhaNova, PASSWORD_DEFAULT);
            $stmtUpdate = $db->prepare("UPDATE utilizadores SET senha_hash = :senha WHERE id = :id");
            return $stmtUpdate->execute([':senha' => $novoHash, ':id' => $id]);
        }

        return false; // Senha antiga não confere
    }

    /**
     * Remove a foto de perfil do utilizador
     */
    public static function removerFoto(int $id): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare("UPDATE utilizadores SET foto_path = NULL WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Devolve estatísticas vitais do utilizador consoante o seu Perfil
     */
    public static function estatisticasPessoais(int $id, string $perfil): array
    {
        $db = Database::ligar();
        $stats = [
            'hoje' => 0,
            'semana' => 0,
            'mes' => 0,
            'total' => 0,
            'tempo_medio' => '--',
            'ausencias' => 0
        ];

        if ($perfil === 'medico') {
            // Conta pacientes atendidos hoje, na semana (últimos 7 dias), no mês, e total
            $queryContagens = "
                SELECT 
                    SUM(DATE(criado_em) = CURDATE()) AS hoje,
                    SUM(criado_em >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) AS semana,
                    SUM(MONTH(criado_em) = MONTH(CURDATE()) AND YEAR(criado_em) = YEAR(CURDATE())) AS mes,
                    COUNT(*) AS total
                FROM senhas 
                WHERE atendido_por = :id AND estado IN ('concluida', 'cancelada')
            ";
            $resContagem = $db->prepare($queryContagens);
            $resContagem->execute([':id' => $id]);
            $contagens = $resContagem->fetch(PDO::FETCH_ASSOC);

            // Conta cancelamentos (ausências)
            $ausencias = $db->prepare("SELECT COUNT(*) FROM senhas WHERE atendido_por = :id AND estado = 'cancelada'");
            $ausencias->execute([':id' => $id]);
            $stats['ausencias'] = (int) $ausencias->fetchColumn();

            // Calcula o tempo médio dos atendimentos concluídos
            $tempoMedio = $db->prepare("SELECT AVG(TIMESTAMPDIFF(MINUTE, criado_em, hora_chamada)) FROM senhas WHERE atendido_por = :id AND estado = 'concluida'");
            $tempoMedio->execute([':id' => $id]);
            $tm = $tempoMedio->fetchColumn();

            $stats['hoje'] = (int) $contagens['hoje'];
            $stats['semana'] = (int) $contagens['semana'];
            $stats['mes'] = (int) $contagens['mes'];
            $stats['total'] = (int) $contagens['total'];
            $stats['tempo_medio'] = $tm ? round($tm) . ' min' : '--';

        } elseif ($perfil === 'recepcionista') {
            // Conta pacientes registados pela recepcionista (emissor de senha)
            $queryContagens = "
                SELECT 
                    SUM(DATE(criado_em) = CURDATE()) AS hoje,
                    SUM(criado_em >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) AS semana,
                    SUM(MONTH(criado_em) = MONTH(CURDATE()) AND YEAR(criado_em) = YEAR(CURDATE())) AS mes,
                    COUNT(*) AS total
                FROM senhas 
                WHERE registado_por = :id
            ";
            $resContagem = $db->prepare($queryContagens);
            $resContagem->execute([':id' => $id]);
            $contagens = $resContagem->fetch(PDO::FETCH_ASSOC);

            // Registos por hora
            $stats['hoje'] = (int) $contagens['hoje'];
            $stats['semana'] = (int) $contagens['semana'];
            $stats['mes'] = (int) $contagens['mes'];
            $stats['total'] = (int) $contagens['total'];
            $stats['tempo_medio'] = ($stats['hoje'] > 0) ? round($stats['hoje'] / 8, 1) . ' / hr' : '--';

        } elseif ($perfil === 'admin') {
            // Usa as acções da auditoria registadas por este admin
            $queryContagens = "
                SELECT 
                    SUM(DATE(criado_em) = CURDATE()) AS hoje,
                    SUM(criado_em >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) AS semana,
                    SUM(MONTH(criado_em) = MONTH(CURDATE()) AND YEAR(criado_em) = YEAR(CURDATE())) AS mes,
                    COUNT(*) AS total
                FROM logs_auditoria 
                WHERE utilizador_id = :id
            ";
            $resContagem = $db->prepare($queryContagens);
            $resContagem->execute([':id' => $id]);
            $contagens = $resContagem->fetch(PDO::FETCH_ASSOC);

            $stats['hoje'] = (int) $contagens['hoje'];
            $stats['semana'] = (int) $contagens['semana'];
            $stats['mes'] = (int) $contagens['mes'];
            $stats['total'] = (int) $contagens['total'];
            $stats['tempo_medio'] = '--'; // Não aplica
        }

        return $stats;
    }

    /**
     * Devolve as ultimas 20 acções/trabalhos dependendo do Perfil
     */
    public static function ultimasAccoes(int $id, string $perfil): array
    {
        $db = Database::ligar();
        if ($perfil === 'medico') {
            $stmt = $db->prepare("
                SELECT s.codigo, s.estado, s.hora_chamada, s.hora_conclusao, 
                       p.nome AS paciente_nome, ta.nome AS atendimento_tipo,
                       TIMESTAMPDIFF(MINUTE, s.hora_chamada, s.hora_conclusao) AS duracao
                FROM senhas s
                JOIN pacientes p ON s.paciente_id = p.id
                JOIN tipos_atendimento ta ON s.tipo_atendimento_id = ta.id
                WHERE s.atendido_por = :id AND s.estado IN ('concluida', 'cancelada')
                ORDER BY s.hora_chamada DESC LIMIT 20
            ");
        } elseif ($perfil === 'recepcionista') {
            $stmt = $db->prepare("
                SELECT s.codigo, s.estado, s.criado_em,
                       p.nome AS paciente_nome, ta.nome AS atendimento_tipo
                FROM senhas s
                JOIN pacientes p ON s.paciente_id = p.id
                JOIN tipos_atendimento ta ON s.tipo_atendimento_id = ta.id
                WHERE s.registado_por = :id
                ORDER BY s.criado_em DESC LIMIT 20
            ");
        } else {
            // Admin (Tira da tabela logs_auditoria)
            $stmt = $db->prepare("
                SELECT l.accao, l.detalhes, l.criado_em, l.ip
                FROM logs_auditoria l
                WHERE l.utilizador_id = :id
                ORDER BY l.criado_em DESC LIMIT 20
            ");
        }
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    /**
     * Dados para Sparkline (mini-gráfico) - Conta as acções dos últimos 7 dias
     */
    public static function sparkline7Dias(int $id, string $perfil): array
    {
        $db = Database::ligar();
        if ($perfil === 'medico') {
            $sql = "SELECT DATE(criado_em) AS data_dia, COUNT(*) AS volume
                    FROM senhas WHERE atendido_por = :id AND criado_em >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY data_dia ORDER BY data_dia ASC";
        } elseif ($perfil === 'recepcionista') {
            $sql = "SELECT DATE(criado_em) AS data_dia, COUNT(*) AS volume
                    FROM senhas WHERE registado_por = :id AND criado_em >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY data_dia ORDER BY data_dia ASC";
        } else {
            $sql = "SELECT DATE(criado_em) AS data_dia, COUNT(*) AS volume
                    FROM logs_auditoria WHERE utilizador_id = :id AND criado_em >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY data_dia ORDER BY data_dia ASC";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Preenche os espaços com 0 para garantir que há sempre 7 dias contínuos
        $diasArray = [];
        $volArray = [];
        // Constrói mapa vazio dos últimos 7 dias
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $diasArray[$d] = 0;
        }

        // Sobrepõe os resultados da BD
        foreach ($resultados as $r) {
            if (isset($diasArray[$r['data_dia']])) {
                $diasArray[$r['data_dia']] = (int) $r['volume'];
            }
        }

        return [
            'labels' => array_keys($diasArray),
            'data' => array_values($diasArray)
        ];
    }
}
