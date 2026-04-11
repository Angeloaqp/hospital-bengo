<?php
// ================================================
// Hospital Geral do Bengo — Model: Historico
// Pesquisa de pacientes e histórico de senhas
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Historico
{

    /**
     * Pesquisa pacientes por nome (LIKE)
     */
    public static function pesquisarPaciente(
        string $termo
    ): array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT p.id, p.nome, p.idade, p.morada,
                    p.peso, p.registado_em,
                    u.nome AS registado_por_nome,
                    (SELECT COUNT(*)
                     FROM senhas s
                     WHERE s.paciente_id = p.id
                    ) AS total_senhas,
                    (SELECT s2.estado
                     FROM senhas s2
                     WHERE s2.paciente_id = p.id
                     ORDER BY s2.criado_em DESC
                     LIMIT 1
                    ) AS ultimo_estado
             FROM pacientes p
             LEFT JOIN utilizadores u
                  ON p.registado_por = u.id
             WHERE p.nome LIKE :termo
             ORDER BY p.registado_em DESC
             LIMIT 20"
        );
        $stmt->execute([
            ':termo' => '%' . $termo . '%'
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Histórico de senhas de um paciente
     */
    public static function historicoSenhas(
        int $pacienteId
    ): array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT s.id, s.codigo, s.prioridade,
                    s.estado, s.criado_em,
                    s.hora_chamada, s.hora_conclusao,
                    ta.nome AS tipo_atendimento,
                    u.nome  AS medico_nome,
                    c.nome  AS consultorio
             FROM senhas s
             JOIN tipos_atendimento ta
                  ON s.tipo_atendimento_id = ta.id
             LEFT JOIN utilizadores u
                  ON s.atendido_por = u.id
             LEFT JOIN consultorios c
                  ON s.consultorio_id = c.id
             WHERE s.paciente_id = :pid
             ORDER BY s.criado_em DESC
             LIMIT 20"
        );
        $stmt->execute([':pid' => $pacienteId]);
        return $stmt->fetchAll();
    }

    /**
     * Dados de um paciente pelo ID
     */
    public static function obterPaciente(
        int $id
    ): ?array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT * FROM pacientes
             WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /**
     * Rechamar: cria nova senha para paciente cancelado
     */
    public static function rechamar(
        int $pacienteId,
        int $tipoAtendimentoId,
        int $prioridade,
        int $registadoPor
    ): string {
        require_once __DIR__ . '/Senha.php';

        $db = Database::ligar();

        $codigo = Senha::gerarCodigo($prioridade);

        $stmt = $db->prepare(
            "INSERT INTO senhas
                (codigo, paciente_id, tipo_atendimento_id,
                 prioridade, estado, registado_por)
             VALUES
                (:cod, :pid, :tid, :pri, 'espera', :reg)"
        );
        $stmt->execute([
            ':cod' => $codigo,
            ':pid' => $pacienteId,
            ':tid' => $tipoAtendimentoId,
            ':pri' => $prioridade,
            ':reg' => $registadoPor,
        ]);

        return $codigo;
    }

    /**
     * Obtém a última senha de um paciente
     */
    public static function ultimaSenha(
        int $pacienteId
    ): ?array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT s.*, ta.nome AS tipo_atendimento
             FROM senhas s
             JOIN tipos_atendimento ta
                  ON s.tipo_atendimento_id = ta.id
             WHERE s.paciente_id = :pid
             ORDER BY s.criado_em DESC
             LIMIT 1"
        );
        $stmt->execute([':pid' => $pacienteId]);
        $r = $stmt->fetch();
        return $r ?: null;
    }
}
