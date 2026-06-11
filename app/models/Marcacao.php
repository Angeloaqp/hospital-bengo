<?php
// ================================================
// Hospital Geral do Bengo — Model: Marcacao
// Agenda central de marcações (consultas)
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Marcacao
{
    // ------------------------------------------------
    // Criar nova marcação
    // ------------------------------------------------
    public static function criar(array $dados): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "INSERT INTO marcacoes
                (paciente_id, especialidade_id, tipo_atendimento_id,
                 consultorio_id, medico_id, data_consulta, hora_marcacao, turno,
                 origem, prioridade, observacoes, criada_por)
             VALUES
                (:pac, :esp, :tipo, :cons, :med, :data, :hora, :turno,
                 :origem, :prio, :obs, :criada)"
        );
        $stmt->execute([
            ':pac'    => (int) $dados['paciente_id'],
            ':esp'    => (int) $dados['especialidade_id'],
            ':tipo'   => (int) $dados['tipo_atendimento_id'],
            ':cons'   => !empty($dados['consultorio_id']) ? (int) $dados['consultorio_id'] : null,
            ':med'    => (int) $dados['medico_id'],
            ':data'   => $dados['data_consulta'],
            ':hora'   => $dados['hora_marcacao'] ?? null,
            ':turno'  => $dados['turno'],
            ':origem' => $dados['origem'] ?? 'marcacao',
            ':prio'   => (int) ($dados['prioridade'] ?? 4),
            ':obs'    => $dados['observacoes'] ?? null,
            ':criada' => (int) $dados['criada_por'],
        ]);
        return (int) $db->lastInsertId();
    }

    // ------------------------------------------------
    // Obter marcação por ID (com dados expandidos)
    // ------------------------------------------------
    public static function obter(int $id): ?array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT m.*,
                    DATE_FORMAT(m.hora_marcacao, '%H:%i') as hora_formatada,
                    p.nome AS paciente_nome,
                    p.idade AS paciente_idade,
                    p.bi_nif AS paciente_bi,
                    e.nome AS especialidade_nome,
                    ta.nome AS tipo_atendimento_nome,
                    ta.prefixo AS tipo_prefixo,
                    c.nome AS consultorio_nome,
                    u.nome AS medico_nome,
                    cr.nome AS criada_por_nome
             FROM marcacoes m
             JOIN pacientes p       ON m.paciente_id = p.id
             JOIN especialidades e  ON m.especialidade_id = e.id
             JOIN tipos_atendimento ta ON m.tipo_atendimento_id = ta.id
             LEFT JOIN consultorios c ON m.consultorio_id = c.id
             JOIN utilizadores u    ON m.medico_id = u.id
             JOIN utilizadores cr   ON m.criada_por = cr.id
             WHERE m.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    // ------------------------------------------------
    // Listar agenda de um dia (com filtros opcionais)
    // ------------------------------------------------
    public static function listarAgendaDia(
        string $data,
        ?int $medicoId = null,
        ?int $especialidadeId = null,
        ?string $estado = null,
        ?string $turno = null
    ): array {
        $db = Database::ligar();
        $where = ["m.data_consulta = :data"];
        $params = [':data' => $data];

        if ($medicoId) {
            $where[] = "m.medico_id = :med";
            $params[':med'] = $medicoId;
        }
        if ($especialidadeId) {
            $where[] = "m.especialidade_id = :esp";
            $params[':esp'] = $especialidadeId;
        }
        if ($estado) {
            $where[] = "m.estado = :estado";
            $params[':estado'] = $estado;
        }
        if ($turno) {
            $where[] = "m.turno = :turno";
            $params[':turno'] = $turno;
        }

        $sql = "SELECT m.*,
                       DATE_FORMAT(m.hora_marcacao, '%H:%i') as hora_formatada,
                       p.nome AS paciente_nome,
                       p.idade AS paciente_idade,
                       e.nome AS especialidade_nome,
                       ta.nome AS tipo_atendimento_nome,
                       c.nome AS consultorio_nome,
                       u.nome AS medico_nome,
                       t.prioridade_clinica AS triagem_prioridade,
                       s.codigo AS senha_codigo
                FROM marcacoes m
                JOIN pacientes p       ON m.paciente_id = p.id
                JOIN especialidades e  ON m.especialidade_id = e.id
                JOIN tipos_atendimento ta ON m.tipo_atendimento_id = ta.id
                LEFT JOIN consultorios c ON m.consultorio_id = c.id
                JOIN utilizadores u    ON m.medico_id = u.id
                LEFT JOIN triagens t   ON t.marcacao_id = m.id
                LEFT JOIN senhas s     ON s.marcacao_id = m.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY m.turno ASC,
                         COALESCE(t.prioridade_clinica, m.prioridade) ASC,
                         m.criado_em ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------
    // Listar agenda num intervalo de dias (com filtros opcionais)
    // ------------------------------------------------
    public static function listarAgendaIntervalo(
        string $dataInicio,
        string $dataFim,
        ?int $medicoId = null,
        ?int $especialidadeId = null,
        ?string $estado = null,
        ?string $turno = null
    ): array {
        $db = Database::ligar();
        $where = ["m.data_consulta >= :data_inicio AND m.data_consulta <= :data_fim"];
        $params = [':data_inicio' => $dataInicio, ':data_fim' => $dataFim];

        if ($medicoId) {
            $where[] = "m.medico_id = :med";
            $params[':med'] = $medicoId;
        }
        if ($especialidadeId) {
            $where[] = "m.especialidade_id = :esp";
            $params[':esp'] = $especialidadeId;
        }
        if ($estado) {
            $where[] = "m.estado = :estado";
            $params[':estado'] = $estado;
        }
        if ($turno) {
            $where[] = "m.turno = :turno";
            $params[':turno'] = $turno;
        }

        $sql = "SELECT m.*,
                       DATE_FORMAT(m.hora_marcacao, '%H:%i') as hora_formatada,
                       p.nome AS paciente_nome,
                       p.idade AS paciente_idade,
                       e.nome AS especialidade_nome,
                       ta.nome AS tipo_atendimento_nome,
                       c.nome AS consultorio_nome,
                       u.nome AS medico_nome,
                       t.id AS triagem_id,
                       t.sintomas AS triagem_sintomas,
                       t.temperatura AS triagem_temperatura,
                       t.pressao_arterial AS triagem_pressao_arterial,
                       t.peso AS triagem_peso,
                       t.frequencia_cardiaca AS triagem_frequencia_cardiaca,
                       t.observacoes AS triagem_observacoes,
                       t.prioridade_clinica AS triagem_prioridade,
                       s.codigo AS senha_codigo
                FROM marcacoes m
                JOIN pacientes p       ON m.paciente_id = p.id
                JOIN especialidades e  ON m.especialidade_id = e.id
                JOIN tipos_atendimento ta ON m.tipo_atendimento_id = ta.id
                LEFT JOIN consultorios c ON m.consultorio_id = c.id
                JOIN utilizadores u    ON m.medico_id = u.id
                LEFT JOIN triagens t   ON t.marcacao_id = m.id
                LEFT JOIN senhas s     ON s.marcacao_id = m.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY m.data_consulta ASC,
                         m.turno ASC,
                         COALESCE(m.hora_marcacao, '23:59:59') ASC,
                         COALESCE(t.prioridade_clinica, m.prioridade) ASC,
                         m.criado_em ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------
    // Listar marcações por médico num dia
    // ------------------------------------------------
    public static function listarPorMedicoDia(
        int $medicoId,
        string $data
    ): array {
        return self::listarAgendaDia($data, $medicoId);
    }

    // ------------------------------------------------
    // Remarcar: muda estado para 'remarcada', cria nova
    // ------------------------------------------------
    public static function remarcar(
        int $id,
        string $novaData,
        string $novoTurno,
        int $utilizadorId
    ): int {
        $db = Database::ligar();
        $db->beginTransaction();

        try {
            // Obter marcação original
            $original = self::obter($id);
            if (!$original || !in_array($original['estado'], ['marcada', 'confirmada'])) {
                throw new RuntimeException('Marcação não pode ser remarcada.');
            }

            // Verificar disponibilidade para a nova data
            if (!self::verificarDisponibilidade(
                (int) $original['medico_id'],
                $original['consultorio_id'] ? (int) $original['consultorio_id'] : null,
                $novaData,
                $novoTurno
            )) {
                throw new RuntimeException('Sem disponibilidade na data/turno seleccionados.');
            }

            // Marcar original como remarcada
            $stmt = $db->prepare(
                "UPDATE marcacoes SET estado = 'remarcada', atualizado_em = NOW()
                 WHERE id = :id"
            );
            $stmt->execute([':id' => $id]);

            // Criar nova marcação
            $novoId = self::criar([
                'paciente_id'        => $original['paciente_id'],
                'especialidade_id'   => $original['especialidade_id'],
                'tipo_atendimento_id'=> $original['tipo_atendimento_id'],
                'consultorio_id'     => $original['consultorio_id'],
                'medico_id'          => $original['medico_id'],
                'data_consulta'      => $novaData,
                'turno'              => $novoTurno,
                'origem'             => $original['origem'],
                'prioridade'         => $original['prioridade'],
                'observacoes'        => 'Remarcada de #' . $id,
                'criada_por'         => $utilizadorId,
            ]);

            // Vincular à original
            $stmt2 = $db->prepare(
                "UPDATE marcacoes SET remarcada_de_id = :orig WHERE id = :novo"
            );
            $stmt2->execute([':orig' => $id, ':novo' => $novoId]);

            $db->commit();
            return $novoId;

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------
    // Cancelar marcação
    // ------------------------------------------------
    public static function cancelar(
        int $id,
        string $motivo,
        int $utilizadorId
    ): bool {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE marcacoes
             SET estado = 'cancelada',
                 observacoes = CONCAT(COALESCE(observacoes,''), '\nCancelada: ', :motivo),
                 atualizado_em = NOW()
             WHERE id = :id
             AND estado IN ('marcada','confirmada')"
        );
        $stmt->execute([':id' => $id, ':motivo' => $motivo]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------
    // Marcar como falta
    // ------------------------------------------------
    public static function marcarFalta(int $id, int $utilizadorId): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE marcacoes
             SET estado = 'falta', atualizado_em = NOW()
             WHERE id = :id
             AND estado IN ('marcada','confirmada')"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------
    // Marcar faltas / cancelar senhas passadas automaticamente
    // ------------------------------------------------
    public static function marcarAusenciasAutomaticas(): void
    {
        $db = Database::ligar();
        
        // 1. Marcações passadas que nunca foram atendidas
        $db->exec(
            "UPDATE marcacoes
             SET estado = 'falta', atualizado_em = NOW()
             WHERE data_consulta < CURDATE()
             AND estado IN ('marcada', 'confirmada', 'em_atendimento')"
        );
        
        // 2. Senhas do dia anterior (ou passadas) não concluídas
        $db->exec(
            "UPDATE senhas
             SET estado = 'cancelada'
             WHERE DATE(criado_em) < CURDATE()
             AND estado IN ('espera', 'chamada')"
        );
    }

    // ------------------------------------------------
    // Check-in: actualiza para 'confirmada'
    // ------------------------------------------------
    public static function confirmarCheckin(
        int $id,
        int $prioridade,
        int $utilizadorId
    ): bool {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE marcacoes
             SET estado = 'confirmada',
                 prioridade = :prio,
                 atualizado_em = NOW()
             WHERE id = :id
             AND estado = 'marcada'"
        );
        $stmt->execute([':id' => $id, ':prio' => $prioridade]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------
    // Iniciar atendimento
    // ------------------------------------------------
    public static function iniciarAtendimento(int $id): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE marcacoes
             SET estado = 'em_atendimento', atualizado_em = NOW()
             WHERE id = :id
             AND estado = 'confirmada'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------
    // Concluir marcação
    // ------------------------------------------------
    public static function concluir(int $id): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE marcacoes
             SET estado = 'concluida', atualizado_em = NOW()
             WHERE id = :id
             AND estado = 'em_atendimento'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------
    // Contar ocupação de um médico num turno/dia
    // ------------------------------------------------
    public static function contarOcupacao(
        int $medicoId,
        string $data,
        string $turno
    ): int {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM marcacoes
             WHERE medico_id = :med
             AND data_consulta = :data
             AND turno = :turno
             AND estado NOT IN ('cancelada','falta','remarcada')"
        );
        $stmt->execute([
            ':med'  => $medicoId,
            ':data' => $data,
            ':turno'=> $turno,
        ]);
        return (int) $stmt->fetchColumn();
    }

    // ------------------------------------------------
    // Verificar disponibilidade (capacidade + bloqueios)
    // ------------------------------------------------
    public static function verificarDisponibilidade(
        int $medicoId,
        ?int $consultorioId,
        string $data,
        string $turno
    ): bool {
        $db = Database::ligar();

        // 1. Verificar bloqueio
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM bloqueios_agenda
             WHERE data_bloqueio = :data
             AND turno = :turno
             AND activo = 1
             AND (medico_id = :med OR medico_id IS NULL)"
        );
        $stmt->execute([':data' => $data, ':turno' => $turno, ':med' => $medicoId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        // 2. Obter capacidade da disponibilidade (data concreta primeiro, depois padrão semanal)
        $diaSemana = (int) date('N', strtotime($data)); // 1=Seg, 7=Dom
        $stmt2 = $db->prepare(
            "SELECT capacidade FROM disponibilidades_medicas
             WHERE medico_id = :med
             AND (data_disponibilidade = :data2 OR (data_disponibilidade IS NULL AND dia_semana = :dia))
             AND turno = :turno
             AND activo = 1
             ORDER BY data_disponibilidade DESC
             LIMIT 1"
        );
        $stmt2->execute([
            ':med'  => $medicoId,
            ':data2' => $data,
            ':dia'  => $diaSemana,
            ':turno'=> $turno,
        ]);
        $disp = $stmt2->fetch();

        // Se não há disponibilidade configurada, usar capacidade padrão de 15
        $capacidade = $disp ? (int) $disp['capacidade'] : 15;

        // 3. Contar ocupação actual
        $ocupacao = self::contarOcupacao($medicoId, $data, $turno);

        return $ocupacao < $capacidade;
    }

    // ------------------------------------------------
    // Obter capacidade e ocupação (para exibir na UI)
    // ------------------------------------------------
    public static function infoCapacidade(
        int $medicoId,
        string $data,
        string $turno
    ): array {
        $db = Database::ligar();
        $diaSemana = (int) date('N', strtotime($data));

        $stmt = $db->prepare(
            "SELECT capacidade FROM disponibilidades_medicas
             WHERE medico_id = :med
             AND (data_disponibilidade = :data2 OR (data_disponibilidade IS NULL AND dia_semana = :dia))
             AND turno = :turno AND activo = 1
             ORDER BY data_disponibilidade DESC
             LIMIT 1"
        );
        $stmt->execute([':med' => $medicoId, ':data2' => $data, ':dia' => $diaSemana, ':turno' => $turno]);
        $disp = $stmt->fetch();
        $capacidade = $disp ? (int) $disp['capacidade'] : 15;
        $ocupacao = self::contarOcupacao($medicoId, $data, $turno);

        return [
            'capacidade' => $capacidade,
            'ocupacao'   => $ocupacao,
            'livre'      => $capacidade - $ocupacao,
            'lotado'     => $ocupacao >= $capacidade,
            'definida'   => $disp ? true : false,
        ];
    }

    // ------------------------------------------------
    // Fila confirmada para um médico (check-in feito)
    // Ordenada por prioridade clínica + hora de check-in
    // ------------------------------------------------
    public static function filaConfirmadaMedico(int $medicoId): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT m.*,
                    p.nome AS paciente_nome,
                    p.idade AS paciente_idade,
                    e.nome AS especialidade_nome,
                    ta.nome AS tipo_atendimento_nome,
                    c.nome AS consultorio_nome,
                    t.prioridade_clinica,
                    t.sintomas AS triagem_sintomas,
                    t.temperatura AS triagem_temperatura,
                    t.pressao_arterial AS triagem_pressao,
                    t.peso AS triagem_peso,
                    t.observacoes AS triagem_obs,
                    s.id AS senha_id,
                    s.codigo AS senha_codigo
             FROM marcacoes m
             JOIN pacientes p       ON m.paciente_id = p.id
             JOIN especialidades e  ON m.especialidade_id = e.id
             JOIN tipos_atendimento ta ON m.tipo_atendimento_id = ta.id
             LEFT JOIN consultorios c ON m.consultorio_id = c.id
             LEFT JOIN triagens t   ON t.marcacao_id = m.id
             LEFT JOIN senhas s     ON s.marcacao_id = m.id
             WHERE m.medico_id = :med
             AND m.data_consulta = CURDATE()
             AND m.estado = 'confirmada'
             ORDER BY COALESCE(t.prioridade_clinica, m.prioridade) ASC,
                      m.atualizado_em ASC"
        );
        $stmt->execute([':med' => $medicoId]);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------
    // Estatísticas rápidas
    // ------------------------------------------------
    public static function estatisticasDia(string $data): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(estado = 'marcada') AS marcadas,
                SUM(estado = 'confirmada') AS confirmadas,
                SUM(estado = 'em_atendimento') AS em_atendimento,
                SUM(estado = 'concluida') AS concluidas,
                SUM(estado = 'cancelada') AS canceladas,
                SUM(estado = 'falta') AS faltas,
                SUM(estado = 'remarcada') AS remarcadas
             FROM marcacoes
             WHERE data_consulta = :data"
        );
        $stmt->execute([':data' => $data]);
        return $stmt->fetch() ?: [];
    }

    // ------------------------------------------------
    // Listar médicos disponíveis numa data/turno
    // ------------------------------------------------
    public static function medicosDisponiveis(
        string $data,
        string $turno,
        ?int $especialidadeId = null
    ): array {
        $db = Database::ligar();
        $diaSemana = (int) date('N', strtotime($data));

        $where = [
            "(d.data_disponibilidade = :data_exact OR (d.data_disponibilidade IS NULL AND d.dia_semana = :dia))",
            "d.turno = :turno",
            "d.activo = 1",
            "u.perfil = 'medico'",
            "u.estado = 1",
        ];
        $params = [':data_exact' => $data, ':dia' => $diaSemana, ':turno' => $turno];

        if ($especialidadeId) {
            $where[] = "d.especialidade_id = :esp";
            $params[':esp'] = $especialidadeId;
        }

        $sql = "SELECT u.id, u.nome, e.nome AS especialidade,
                       c.nome AS consultorio, d.capacidade
                FROM disponibilidades_medicas d
                JOIN utilizadores u    ON d.medico_id = u.id
                JOIN especialidades e  ON d.especialidade_id = e.id
                LEFT JOIN consultorios c ON d.consultorio_id = c.id
                WHERE " . implode(' AND ', $where) . "
                AND u.id NOT IN (
                    SELECT COALESCE(b.medico_id, 0) FROM bloqueios_agenda b
                    WHERE b.data_bloqueio = :data_b AND b.turno = :turno_b AND b.activo = 1
                )
                ORDER BY u.nome";
        $params[':data_b'] = $data;
        $params[':turno_b'] = $turno;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
