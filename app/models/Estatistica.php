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
     * Lista todos os utilizadores com especialidade e consultório
     */
    public static function todosUtilizadores(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT u.id, u.nome, u.nome_utilizador,
                    u.perfil, u.estado, u.criado_em,
                    u.telefone,
                    e.nome AS especialidade,
                    c.nome AS consultorio
             FROM utilizadores u
             LEFT JOIN especialidades e
                  ON u.especialidade_id = e.id
             LEFT JOIN consultorios c
                  ON u.consultorio_id = c.id
             ORDER BY u.perfil ASC, u.nome ASC"
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

    // ================================================
    // CRUD de Utilizadores (Fase 1)
    // ================================================

    /**
     * Cria um novo utilizador
     */
    public static function criarUtilizador(array $d): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "INSERT INTO utilizadores
                (nome, nome_utilizador, senha_hash,
                 perfil, especialidade_id,
                 consultorio_id, telefone, estado)
             VALUES
                (:nome, :user, :hash,
                 :perfil, :esp, :cons, :tel, 1)"
        );
        $stmt->execute([
            ':nome' => $d['nome'],
            ':user' => $d['nome_utilizador'],
            ':hash' => password_hash(
                $d['senha'],
                PASSWORD_BCRYPT
            ),
            ':perfil' => $d['perfil'],
            ':esp' => $d['especialidade_id'] ?: null,
            ':cons' => $d['consultorio_id'] ?: null,
            ':tel' => $d['telefone'] ?: null,
        ]);
        return (int) $db->lastInsertId();
    }

    /**
     * Obtém um utilizador pelo ID
     */
    public static function obterUtilizador(int $id): ?array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT u.*, e.nome AS especialidade_nome,
                    c.nome AS consultorio_nome
             FROM utilizadores u
             LEFT JOIN especialidades e
                  ON u.especialidade_id = e.id
             LEFT JOIN consultorios c
                  ON u.consultorio_id = c.id
             WHERE u.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /**
     * Edita os dados de um utilizador
     * (senha só é alterada se preenchida)
     */
    public static function editarUtilizador(
        int $id,
        array $d
    ): bool {
        $db = Database::ligar();

        $sql = "UPDATE utilizadores SET
                    nome = :nome,
                    nome_utilizador = :user,
                    perfil = :perfil,
                    especialidade_id = :esp,
                    consultorio_id = :cons,
                    telefone = :tel";

        $params = [
            ':nome' => $d['nome'],
            ':user' => $d['nome_utilizador'],
            ':perfil' => $d['perfil'],
            ':esp' => $d['especialidade_id'] ?: null,
            ':cons' => $d['consultorio_id'] ?: null,
            ':tel' => $d['telefone'] ?: null,
            ':id' => $id,
        ];

        // Actualiza senha apenas se preenchida
        if (!empty($d['senha'])) {
            $sql .= ", senha_hash = :hash";
            $params[':hash'] = password_hash(
                $d['senha'],
                PASSWORD_BCRYPT
            );
        }

        $sql .= " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Verifica se nome_utilizador já existe
     */
    public static function usernameExiste(
        string $username,
        ?int $excluirId = null
    ): bool {
        $db = Database::ligar();
        $sql = "SELECT COUNT(*) FROM utilizadores
                WHERE nome_utilizador = :u";
        $params = [':u' => $username];

        if ($excluirId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excluirId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Lista especialidades activas
     */
    public static function listarEspecialidades(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT id, nome FROM especialidades
             WHERE activo = 1
             ORDER BY nome ASC"
        )->fetchAll();
    }

    /**
     * Lista consultórios activos
     */
    public static function listarConsultorios(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT id, nome FROM consultorios
             WHERE activo = 1
             ORDER BY nome ASC"
        )->fetchAll();
    }

    // ================================================
    // FASE 6 — RELATÓRIOS E GRÁFICOS
    // ================================================

    /**
     * Resumo de estados dentro de um período (Agrupado por Data)
     */
    public static function resumoPorPeriodo(
        string $dataInicio,
        string $dataFim
    ): array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT DATE(criado_em) AS data_dia,
                    COUNT(*) AS total,
                    SUM(estado = 'concluida') AS concluidos,
                    SUM(estado = 'cancelada') AS cancelados
             FROM senhas
             WHERE DATE(criado_em) BETWEEN :di AND :df
             GROUP BY DATE(criado_em)
             ORDER BY data_dia ASC"
        );
        $stmt->execute([':di' => $dataInicio, ':df' => $dataFim]);
        return $stmt->fetchAll();
    }

    /**
     * Produtividade por médico no período seleccionado
     */
    public static function porMedico(
        string $dataInicio,
        string $dataFim
    ): array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT u.id, u.nome AS medico,
                    COUNT(s.id) AS total_atendidos,
                    AVG(TIMESTAMPDIFF(MINUTE, s.criado_em, s.hora_chamada)) AS tempo_medio_espera,
                    COUNT(DISTINCT s.paciente_id) AS pacientes_unicos
             FROM senhas s
             JOIN utilizadores u ON s.atendido_por = u.id
             WHERE s.estado = 'concluida' 
               AND DATE(s.criado_em) BETWEEN :di AND :df
             GROUP BY u.id, u.nome
             ORDER BY total_atendidos DESC"
        );
        $stmt->execute([':di' => $dataInicio, ':df' => $dataFim]);
        return $stmt->fetchAll();
    }

    /**
     * Volume de senhas por hora (pico de fluxo)
     */
    public static function horasPico(
        string $dataInicio,
        string $dataFim
    ): array {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT HOUR(criado_em) AS hora,
                    COUNT(*) AS volume
             FROM senhas
             WHERE DATE(criado_em) BETWEEN :di AND :df
             GROUP BY HOUR(criado_em)
             ORDER BY hora ASC"
        );
        $stmt->execute([':di' => $dataInicio, ':df' => $dataFim]);
        return $stmt->fetchAll();
    }
}

