<?php
// ================================================
// Hospital Geral do Bengo — API: Agenda
// Endpoints AJAX para marcações
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';

exigirPerfil(['recepcionista', 'admin', 'medico']);

header('Content-Type: application/json; charset=utf-8');

$acao = trim($_GET['acao'] ?? '');

// ------------------------------------------------
// Pesquisar pacientes (para autocomplete)
// ------------------------------------------------
if ($acao === 'pesquisar_paciente') {
    $termo = trim($_GET['q'] ?? '');

    $db = Database::ligar();
    
    if (empty($termo)) {
        $stmt = $db->prepare(
            "SELECT id, nome, bi_nif, idade, telefone, email, numero_processo
             FROM pacientes
             ORDER BY id DESC
             LIMIT 10"
        );
        $stmt->execute();
    } else {
        $stmt = $db->prepare(
            "SELECT id, nome, bi_nif, idade, telefone, email, numero_processo
             FROM pacientes
             WHERE nome LIKE :q OR bi_nif LIKE :q2 OR numero_processo LIKE :q3
             ORDER BY nome ASC
             LIMIT 10"
        );
        $stmt->execute([':q' => "%{$termo}%", ':q2' => "%{$termo}%", ':q3' => "%{$termo}%"]);
    }
    
    echo json_encode(['resultados' => $stmt->fetchAll()]);
    exit;
}

// ------------------------------------------------
// Capacidade/ocupação de um médico num dia/turno
// ------------------------------------------------
if ($acao === 'capacidade') {
    require_once __DIR__ . '/../../app/models/Marcacao.php';

    $medicoId = (int) ($_GET['medico_id'] ?? 0);
    $data = trim($_GET['data'] ?? '');
    $turno = trim($_GET['turno'] ?? '');

    if ($medicoId <= 0 || empty($data) || !in_array($turno, ['manha', 'tarde'])) {
        echo json_encode(['erro' => 'Parâmetros inválidos.']);
        exit;
    }

    $info = Marcacao::infoCapacidade($medicoId, $data, $turno);
    echo json_encode($info);
    exit;
}

// ------------------------------------------------
// Médicos disponíveis numa data/turno
// ------------------------------------------------
if ($acao === 'medicos_disponiveis') {
    require_once __DIR__ . '/../../app/models/Marcacao.php';

    $data = trim($_GET['data'] ?? '');
    $turno = trim($_GET['turno'] ?? '');
    $espId = !empty($_GET['especialidade_id']) ? (int) $_GET['especialidade_id'] : null;

    if (empty($data) || !in_array($turno, ['manha', 'tarde'])) {
        echo json_encode(['medicos' => []]);
        exit;
    }

    $medicos = Marcacao::medicosDisponiveis($data, $turno, $espId);

    // Adicionar info de capacidade a cada médico
    foreach ($medicos as &$m) {
        $info = Marcacao::infoCapacidade((int) $m['id'], $data, $turno);
        $m['ocupacao'] = $info['ocupacao'];
        $m['capacidade'] = $info['capacidade'];
        $m['livre'] = $info['livre'];
        $m['lotado'] = $info['lotado'];
    }

    echo json_encode(['medicos' => $medicos]);
    exit;
}

// ------------------------------------------------
// Contactos de um paciente
// ------------------------------------------------
if ($acao === 'contactos_paciente') {
    require_once __DIR__ . '/../../app/models/PacienteContacto.php';

    $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
    if ($pacienteId <= 0) {
        echo json_encode(['contactos' => []]);
        exit;
    }

    $contactos = PacienteContacto::listarPorPaciente($pacienteId);
    echo json_encode(['contactos' => $contactos]);
    exit;
}

// ------------------------------------------------
// Obter dados de um paciente específico por ID
// ------------------------------------------------
if ($acao === 'obter_paciente') {
    $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
    if ($pacienteId <= 0) {
        echo json_encode(['paciente' => null]);
        exit;
    }

    $db = Database::ligar();
    $stmt = $db->prepare(
        "SELECT id, nome, bi_nif, idade, telefone, email, numero_processo
         FROM pacientes
         WHERE id = :id
         LIMIT 1"
    );
    $stmt->execute([':id' => $pacienteId]);
    $pac = $stmt->fetch();
    
    echo json_encode(['paciente' => $pac ?: null]);
    exit;
}

// ------------------------------------------------
// Obter médicos de uma especialidade (com dados do consultório base)
// ------------------------------------------------
if ($acao === 'medicos_da_especialidade') {
    $espId = (int) ($_GET['especialidade_id'] ?? 0);
    
    $db = Database::ligar();
    $sql = "SELECT u.id, u.nome, 
            (
                SELECT GROUP_CONCAT(DISTINCT esp.nome SEPARATOR ', ')
                FROM especialidades esp
                LEFT JOIN medico_especialidades me ON me.especialidade_id = esp.id
                WHERE me.medico_id = u.id OR esp.id = u.especialidade_id
            ) as especialidades_concat,
            c.nome as consultorio_nome, c.id as consultorio_id
            FROM utilizadores u
            LEFT JOIN consultorios c ON u.consultorio_id = c.id
            WHERE u.perfil = 'medico' AND u.estado = 1";
            
    if ($espId > 0) {
        $sql .= " AND (u.especialidade_id = " . $espId . " OR u.id IN (SELECT medico_id FROM medico_especialidades WHERE especialidade_id = " . $espId . "))";
    }
    
    $sql .= " ORDER BY u.nome ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $medicos = $stmt->fetchAll();
    
    echo json_encode(['medicos' => $medicos]);
    exit;
}

// ------------------------------------------------
// Obter horários de um médico (Disponibilidades)
// ------------------------------------------------
if ($acao === 'horario_medico') {
    $medicoId = (int) ($_GET['medico_id'] ?? 0);
    
    $db = Database::ligar();
    $stmt = $db->prepare(
        "SELECT dia_semana, turno, capacidade, data_disponibilidade 
         FROM disponibilidades_medicas 
         WHERE medico_id = :med AND activo = 1"
    );
    $stmt->execute([':med' => $medicoId]);
    $horarios = $stmt->fetchAll();
    
    echo json_encode(['horarios' => $horarios]);
    exit;
}

// ------------------------------------------------
// Calcular hora automática de atendimento
// ------------------------------------------------
if ($acao === 'calcular_hora') {
    $medicoId = (int) ($_GET['medico_id'] ?? 0);
    $dataConsulta = $_GET['data'] ?? '';
    
    // Normalizar o turno: remove acentos e minúsculas
    $turno = strtolower(trim($_GET['turno'] ?? ''));
    $turno = str_replace(['ã','á','à','â'], 'a', $turno);

    if (!$medicoId || !$dataConsulta || !in_array($turno, ['manha', 'tarde'])) {
        echo json_encode(['erro' => 'Parâmetros incompletos ou inválidos.', 'hora_marcacao' => null]);
        exit;
    }

    $db = Database::ligar();
    $stmt = $db->prepare(
        "SELECT COUNT(*) as total 
         FROM marcacoes 
         WHERE medico_id = :med 
         AND data_consulta = :data 
         AND turno = :turno 
         AND estado != 'cancelada'"
    );
    $stmt->execute([
        ':med' => $medicoId,
        ':data' => $dataConsulta,
        ':turno' => $turno
    ]);
    $result = $stmt->fetch();
    $total = (int) $result['total'];

    // Para Manhã: 08:00 às 11:50
    // Para Tarde: 13:00 às 15:50
    $startHour = ($turno === 'tarde') ? 13 : 8;
    $startMinute = 0;

    $additionalMinutes = $total * 30;
    
    // Calcula as horas e minutos totais
    $totalMinutes = ($startHour * 60) + $startMinute + $additionalMinutes;
    
    // Limite máximo: 11:50 para manhã e 15:50 para tarde
    $limitMinutes = ($turno === 'tarde') ? (15 * 60 + 50) : (11 * 60 + 50);

    if ($totalMinutes > $limitMinutes) {
        $totalMinutes = $limitMinutes;
    }

    $hora = floor($totalMinutes / 60);
    $minuto = $totalMinutes % 60;

    $horaFormatada = sprintf("%02d:%02d", $hora, $minuto);

    echo json_encode([
        'hora_marcacao' => $horaFormatada,
        'turno' => $turno,
        'total_agendado' => $total
    ]);
    exit;
}

echo json_encode(['erro' => 'Acção desconhecida.']);
