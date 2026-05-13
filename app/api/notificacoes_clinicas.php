<?php
// ================================================
// API: Notificações Clínicas (Polling)
// Verifica se há novas senhas urgentes na fila
// ================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Senha.php';

header('Content-Type: application/json');

// Apenas médicos devem fazer polling a isto
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'medico') {
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Em vez de ver todas urgentes do hospital, devia ser da fila do médico
$medicoId = (int) sessao('utilizador_id');
$esperaMedico = Senha::filaDoMedico($medicoId);

// Filtrar urgentes (1 e 2 são prioridades altas)
$urgentes = array_filter($esperaMedico, function($s) {
    return in_array((int)$s['prioridade'], [1, 2]); // Vermelho e Laranja
});

echo json_encode([
    'urgentes_count' => count($urgentes)
]);
