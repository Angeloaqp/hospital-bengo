<?php
// ================================================
// Hospital Geral do Bengo
// API — Toggle Aceitar Encaixes (Walk-ins)
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!logado() || (sessao('perfil') !== 'medico' && sessao('perfil') !== 'admin')) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'mensagem' => 'Método inválido.']);
    exit;
}

$medicoId = (int) sessao('utilizador_id');
$estado = isset($_POST['estado']) && $_POST['estado'] === 'true' ? 1 : 0;

try {
    $db = Database::ligar();
    $stmt = $db->prepare("UPDATE utilizadores SET aceitar_walkins = :estado WHERE id = :id");
    $stmt->execute([':estado' => $estado, ':id' => $medicoId]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensagem' => $e->getMessage()]);
}
