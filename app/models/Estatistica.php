<?php
// ================================================
// Hospital Geral do Bengo — Model: Estatistica
// Consultas de dados para o painel do administrador
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Estatistica
{

    /**
     * Resumo geral do dia actual
     */
    public static function resumoHoje(): array
    {
        $db = Database::ligar();

        $total = (int) $db->query(
            "SELECT COUNT(*) FROM senhas
             WHERE DATE(criado_em) = CURDATE()"
        )->fetchColumn();

        $concluidos = (int) $db->query(
            "SELECT COUNT(*) FROM senhas
             WHERE estado = 'concluida'
             AND DATE(criado_em) = CURDATE()"
        )->fetchColumn();

        $cancelados = (int) $db->query(
            "SELECT COUNT(*) FROM senhas
             WHERE estado = 'cancelada'
             AND DATE(criado_em) = CURDATE()"
        )->fetchColumn();

        $emEspera = (int) $db->query(
            "SELECT COUNT(*) FROM senhas
             WHERE estado = 'espera'"
        )->fetchColumn();

        $tempoMedio = $db->query(
            "SELECT AVG(
                TIMESTAMPDIFF(MINUTE, criado_em, hora_chamada)
             )
             FROM senhas
             WHERE hora_chamada IS NOT NULL
             AND DATE(criado_em) = CURDATE()"
        )->fetchColumn();

        return [
            'total' => $total,
            'concluidos' => $concluidos,
            'cancelados' => $cancelados,
            'em_espera' => $emEspera,
            'tempo_medio' => $tempoMedio
                ? (int) round($tempoMedio)
                : 0,
        ];
    }

    /**
     * Contagem por prioridade — hoje
     */
    public static function porPrioridade(): array
    {
        $db = Database::ligar();
        $stmt = $db->query(
            "SELECT prioridade, COUNT(*) AS total
             FROM senhas
             WHERE DATE(criado_em) = CURDATE()
             GROUP BY prioridade
             ORDER BY prioridade ASC"
        );
        $rows = $stmt->fetchAll();

        $mapa = [
            1 => [
                'label' => 'Urgente',
                'total' => 0,
                'cor' => '#DC2626'
            ],
            2 => [
                'label' => 'Idoso',
                'total' => 0,
                'cor' => '#D97706'
            ],
            3 => [
                'label' => 'Grávida',
                'total' => 0,
                'cor' => '#7C3AED'
            ],
            4 => [
                'label' => 'Normal',
                'total' => 0,
                'cor' => '#1E6FD9'
            ],
        ];

        foreach ($rows as $r) {
            $p = (int) $r['prioridade'];
            if (isset($mapa[$p])) {
                $mapa[$p]['total'] = (int) $r['total'];
            }
        }

        return array_values($mapa);
    }

    /**
     * Contagem por tipo de atendimento — hoje
     */
    public static function porTipoAtendimento(): array
    {
        $db = Database::ligar();
        $stmt = $db->query(
            "SELECT ta.nome AS tipo,
                    COUNT(s.id) AS total
             FROM senhas s
             JOIN tipos_atendimento ta
                  ON s.tipo_atendimento_id = ta.id
             WHERE DATE(s.criado_em) = CURDATE()
             GROUP BY ta.id, ta.nome
             ORDER BY total DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Últimos 10 atendimentos do dia
     */
    public static function ultimosAtendimentos(
        int $limite = 10
    ): array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT s.codigo, s.estado, s.prioridade,
                    s.criado_em, s.hora_chamada,
                    s.hora_conclusao,
                    p.nome  AS paciente,
                    p.idade,
                    ta.nome AS tipo,
                    u.nome  AS medico,
                    c.nome  AS consultorio
             FROM senhas s
             JOIN pacientes p
                  ON s.paciente_id = p.id
             JOIN tipos_atendimento ta
                  ON s.tipo_atendimento_id = ta.id
             LEFT JOIN utilizadores u
                  ON s.atendido_por = u.id
             LEFT JOIN consultorios c
                  ON s.consultorio_id = c.id
             WHERE DATE(s.criado_em) = CURDATE()
             ORDER BY s.criado_em DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lista todos os utilizadores do sistema
     */
    public static function todosUtilizadores(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT id, nome, nome_utilizador,
                    perfil, estado, criado_em
             FROM utilizadores
             ORDER BY perfil ASC, nome ASC"
        )->fetchAll();
    }

    /**
     * Activa ou desactiva um utilizador
     */
    public static function toggleEstado(
        int $id,
        int $estado
    ): bool {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE utilizadores
             SET estado = :estado
             WHERE id = :id"
        );
        $stmt->execute([
            ':estado' => $estado,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }
}
