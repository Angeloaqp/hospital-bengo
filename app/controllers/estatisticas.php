<?php
// ================================================
// Hospital Geral do Bengo
// Controller: Estatísticas e Gestão de Utilizadores
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Estatistica.php';

exigirPerfil(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL .
        'app/views/admin/dashboard.php');
    exit;
}

$acao = trim($_POST['acao'] ?? '');

// ------------------------------------------------
// ACÇÃO: Toggle estado do utilizador
// ------------------------------------------------
if ($acao === 'toggle_utilizador') {
    $uid = (int) ($_POST['utilizador_id'] ?? 0);
    $estado = (int) ($_POST['estado'] ?? 0);

    // Não permite desactivar a si próprio
    if ($uid === (int) sessao('utilizador_id')) {
        $_SESSION['erro'] =
            'Não pode desactivar a sua própria conta.';
        header('Location: ' . BASE_URL .
            'app/views/admin/utilizadores.php');
        exit;
    }

    if (
        $uid > 0 && Estatistica::toggleEstado(
            $uid,
            $estado
        )
    ) {
        $_SESSION['mensagem'] = $estado
            ? 'Utilizador activado com sucesso.'
            : 'Utilizador desactivado com sucesso.';
    } else {
        $_SESSION['erro'] =
            'Não foi possível actualizar o utilizador.';
    }

    header('Location: ' . BASE_URL .
        'app/views/admin/utilizadores.php');
    exit;
}

header('Location: ' . BASE_URL .
    'app/views/admin/dashboard.php');
exit;
