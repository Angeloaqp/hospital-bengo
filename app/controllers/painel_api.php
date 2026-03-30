<?php
// ================================================
// Hospital Geral do Bengo
// API JSON — Dados em tempo real para o painel
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Senha.php';

// Sem cache
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

$coresSenha = [
    1 => '#EF4444',
    2 => '#F59E0B',
    3 => '#A78BFA',
    4 => '#60A5FA',
];

$emChamada = Senha::emChamadaAgora();

echo json_encode([
    'em_chamada' => $emChamada ? [
        'codigo' => $emChamada['codigo'],
        'consultorio' => $emChamada['consultorio'] ?? '',
        'cor' => $coresSenha[
            $emChamada['prioridade']
        ] ?? '#60A5FA',
    ] : null,
    'em_espera' => Senha::contarPorEstado('espera'),
    'timestamp' => time(),
], JSON_UNESCAPED_UNICODE);
