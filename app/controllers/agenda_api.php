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
    $sql = "SELECT u.id, u.nome, e.nome as especialidade_nome, c.nome as consultorio_nome, c.id as consultorio_id
            FROM utilizadores u
            LEFT JOIN especialidades e ON u.especialidade_id = e.id
            LEFT JOIN consultorios c ON u.consultorio_id = c.id
            WHERE u.perfil = 'medico' AND u.estado = 1";
            
    $params = [];
    if ($espId > 0) {
        $sql .= " AND u.especialidade_id = :espId";
        $params[':espId'] = $espId;
    }
    
    $sql .= " ORDER BY u.nome ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $medicos = $stmt->fetchAll();
    
    echo json_encode(['medicos' => $medicos]);
    exit;
}

echo json_encode(['erro' => 'Acção desconhecida.']);
