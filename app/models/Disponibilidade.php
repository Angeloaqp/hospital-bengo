<?php
// ================================================
// Hospital Geral do Bengo — Model: Disponibilidade
// Gestão de horários médicos e bloqueios de agenda
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Disponibilidade
{
    // ------------------------------------------------
    // Listar disponibilidades de um médico
    // ------------------------------------------------
    public static function listarPorMedico(int $medicoId): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT d.*, e.nome AS especialidade_nome,
                    c.nome AS consultorio_nome
             FROM disponibilidades_medicas d
             JOIN especialidades e ON d.especialidade_id = e.id
             LEFT JOIN consultorios c ON d.consultorio_id = c.id
             WHERE d.medico_id = :med
             AND d.activo = 1
             ORDER BY d.dia_semana ASC, d.turno ASC"
        );
        $stmt->execute([':med' => $medicoId]);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------
    // Listar todas as disponibilidades activas
    // ------------------------------------------------
    public static function listarTodas(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT d.*, u.nome AS medico_nome,
                    e.nome AS especialidade_nome,
                    c.nome AS consultorio_nome
             FROM disponibilidades_medicas d
             JOIN utilizadores u ON d.medico_id = u.id
             JOIN especialidades e ON d.especialidade_id = e.id
             LEFT JOIN consultorios c ON d.consultorio_id = c.id
             WHERE d.activo = 1
             ORDER BY u.nome ASC, d.dia_semana ASC, d.turno ASC"
        )->fetchAll();
    }

    // ------------------------------------------------
    // Guardar disponibilidade (insert or update)
    // ------------------------------------------------
    public static function guardar(array $dados): int
    {
        $db = Database::ligar();

        // Verificar se já existe para este médico/dia/turno
        $stmt = $db->prepare(
            "SELECT id FROM disponibilidades_medicas
             WHERE medico_id = :med AND dia_semana = :dia AND turno = :turno
             LIMIT 1"
        );
        $stmt->execute([
            ':med'  => (int) $dados['medico_id'],
            ':dia'  => (int) $dados['dia_semana'],
            ':turno'=> $dados['turno'],
        ]);
        $existente = $stmt->fetch();

        if ($existente) {
            $stmt2 = $db->prepare(
                "UPDATE disponibilidades_medicas SET
                    especialidade_id = :esp,
                    consultorio_id = :cons,
                    capacidade = :cap,
                    activo = 1
                 WHERE id = :id"
            );
            $stmt2->execute([
                ':esp'  => (int) $dados['especialidade_id'],
                ':cons' => !empty($dados['consultorio_id']) ? (int) $dados['consultorio_id'] : null,
                ':cap'  => (int) ($dados['capacidade'] ?? 10),
                ':id'   => (int) $existente['id'],
            ]);
            return (int) $existente['id'];
        }

        $stmt3 = $db->prepare(
            "INSERT INTO disponibilidades_medicas
                (medico_id, especialidade_id, consultorio_id,
                 dia_semana, turno, capacidade)
             VALUES
                (:med, :esp, :cons, :dia, :turno, :cap)"
        );
        $stmt3->execute([
            ':med'  => (int) $dados['medico_id'],
            ':esp'  => (int) $dados['especialidade_id'],
            ':cons' => !empty($dados['consultorio_id']) ? (int) $dados['consultorio_id'] : null,
            ':dia'  => (int) $dados['dia_semana'],
            ':turno'=> $dados['turno'],
            ':cap'  => (int) ($dados['capacidade'] ?? 10),
        ]);
        return (int) $db->lastInsertId();
    }

    // ------------------------------------------------
    // Remover disponibilidade (desactivar)
    // ------------------------------------------------
    public static function remover(int $id): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE disponibilidades_medicas SET activo = 0 WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------
    // Bloqueios de agenda
    // ------------------------------------------------
    public static function criarBloqueio(array $dados): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "INSERT INTO bloqueios_agenda
                (medico_id, consultorio_id, data_bloqueio,
                 turno, motivo, criado_por)
             VALUES
                (:med, :cons, :data, :turno, :motivo, :criado)"
        );
        $stmt->execute([
            ':med'    => !empty($dados['medico_id']) ? (int) $dados['medico_id'] : null,
            ':cons'   => !empty($dados['consultorio_id']) ? (int) $dados['consultorio_id'] : null,
            ':data'   => $dados['data_bloqueio'],
            ':turno'  => $dados['turno'],
            ':motivo' => $dados['motivo'],
            ':criado' => (int) $dados['criado_por'],
        ]);
        return (int) $db->lastInsertId();
    }

    public static function removerBloqueio(int $id): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE bloqueios_agenda SET activo = 0 WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function listarBloqueios(?int $medicoId = null): array
    {
        $db = Database::ligar();
        $where = ["b.activo = 1", "b.data_bloqueio >= CURDATE()"];
        $params = [];

        if ($medicoId) {
            $where[] = "(b.medico_id = :med OR b.medico_id IS NULL)";
            $params[':med'] = $medicoId;
        }

        $sql = "SELECT b.*, u.nome AS medico_nome,
                       c.nome AS consultorio_nome,
                       cr.nome AS criado_por_nome
                FROM bloqueios_agenda b
                LEFT JOIN utilizadores u ON b.medico_id = u.id
                LEFT JOIN consultorios c ON b.consultorio_id = c.id
                JOIN utilizadores cr ON b.criado_por = cr.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.data_bloqueio ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------
    // Listar consultórios activos
    // ------------------------------------------------
    public static function listarConsultorios(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT * FROM consultorios WHERE activo = 1 ORDER BY nome"
        )->fetchAll();
    }

    // ------------------------------------------------
    // Listar especialidades activas
    // ------------------------------------------------
    public static function listarEspecialidades(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT * FROM especialidades WHERE activo = 1 ORDER BY nome"
        )->fetchAll();
    }

    // ------------------------------------------------
    // Listar tipos de atendimento activos
    // ------------------------------------------------
    public static function listarTiposAtendimento(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT ta.*, e.nome AS especialidade_nome
             FROM tipos_atendimento ta
             LEFT JOIN especialidades e ON ta.especialidade_id = e.id
             WHERE ta.activo = 1
             ORDER BY ta.nome"
        )->fetchAll();
    }

    // ------------------------------------------------
    // Listar médicos activos
    // ------------------------------------------------
    public static function listarMedicos(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT u.id, u.nome, e.nome AS especialidade_nome,
                    c.nome AS consultorio_nome
             FROM utilizadores u
             LEFT JOIN especialidades e ON u.especialidade_id = e.id
             LEFT JOIN consultorios c ON u.consultorio_id = c.id
             WHERE u.perfil = 'medico' AND u.estado = 1
             ORDER BY u.nome"
        )->fetchAll();
    }

    // ------------------------------------------------
    // CRUD de consultórios
    // ------------------------------------------------
    public static function criarConsultorio(string $nome, ?string $responsavel = null): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "INSERT INTO consultorios (nome, responsavel) VALUES (:nome, :resp)"
        );
        $stmt->execute([':nome' => $nome, ':resp' => $responsavel]);
        return (int) $db->lastInsertId();
    }

    public static function editarConsultorio(int $id, string $nome, ?string $responsavel, int $activo): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE consultorios SET nome = :nome, responsavel = :resp, activo = :act WHERE id = :id"
        );
        $stmt->execute([':nome' => $nome, ':resp' => $responsavel, ':act' => $activo, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------
    // CRUD de especialidades
    // ------------------------------------------------
    public static function criarEspecialidade(string $nome, ?string $descricao = null): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "INSERT INTO especialidades (nome, descricao) VALUES (:nome, :desc)"
        );
        $stmt->execute([':nome' => $nome, ':desc' => $descricao]);
        return (int) $db->lastInsertId();
    }

    public static function editarEspecialidade(int $id, string $nome, ?string $descricao, int $activo): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE especialidades SET nome = :nome, descricao = :desc, activo = :act WHERE id = :id"
        );
        $stmt->execute([':nome' => $nome, ':desc' => $descricao, ':act' => $activo, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------
    // CRUD de tipos de atendimento
    // ------------------------------------------------
    public static function criarTipoAtendimento(string $nome, string $prefixo, ?int $especialidadeId): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "INSERT INTO tipos_atendimento (nome, prefixo, especialidade_id) VALUES (:nome, :pref, :esp)"
        );
        $stmt->execute([':nome' => $nome, ':pref' => $prefixo, ':esp' => $especialidadeId]);
        return (int) $db->lastInsertId();
    }

    public static function editarTipoAtendimento(int $id, string $nome, string $prefixo, ?int $especialidadeId, int $activo): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE tipos_atendimento SET nome = :nome, prefixo = :pref, especialidade_id = :esp, activo = :act WHERE id = :id"
        );
        $stmt->execute([':nome' => $nome, ':pref' => $prefixo, ':esp' => $especialidadeId, ':act' => $activo, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
