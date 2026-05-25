<?php
// ================================================
// API Endpoint para Vínculos do Médico
// Retorna JSON com as Especialidades e Consultórios
// ================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Disponibilidade.php';

if (!in_array($_SESSION['perfil'] ?? '', ['admin', 'recepcionista'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

$medicoId = (int)($_GET['medico_id'] ?? 0);

if ($medicoId <= 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID inválido']);
    exit;
}

$vinculos = Disponibilidade::obterVinculosMedico($medicoId);

$db = Database::ligar();
$todasEspecialidades = $db->query("SELECT id, nome FROM especialidades WHERE activo = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
$todosConsultorios = $db->query("SELECT id, nome FROM consultorios WHERE activo = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

$resposta = [
    'especialidades' => [],
    'consultorios' => []
];

foreach ($vinculos['especialidades'] as $eId) {
    if (isset($todasEspecialidades[$eId])) {
        $resposta['especialidades'][] = ['id' => $eId, 'nome' => $todasEspecialidades[$eId]];
    }
}

foreach ($vinculos['consultorios'] as $cId) {
    if (isset($todosConsultorios[$cId])) {
        $resposta['consultorios'][] = ['id' => $cId, 'nome' => $todosConsultorios[$cId]];
    }
}

header('Content-Type: application/json');
echo json_encode($resposta);
