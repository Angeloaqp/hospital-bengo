<?php
// ================================================
// Hospital Geral do Bengo — Controller: Senhas
// Chamar, Concluir, Cancelar, Desfazer
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Senha.php';
require_once __DIR__ . '/../../app/models/Auditoria.php';

exigirPerfil(['medico', 'admin', 'recepcionista']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL .
        'app/views/medico/dashboard.php');
    exit;
}

$acao = trim($_POST['acao'] ?? '');
$senhaId = (int) ($_POST['senha_id'] ?? 0);
$medicoId = (int) sessao('utilizador_id');

$destino = BASE_URL . 'app/views/medico/dashboard.php';

// ------------------------------------------------
// CHAMAR próximo paciente
// ------------------------------------------------
if ($acao === 'chamar' && $senhaId > 0) {
    $consultorio = Senha::consultorioDoMedico($medicoId);
    $consId = $consultorio['id'] ?? 1;

    if (Senha::chamar($senhaId, $medicoId, $consId)) {
        $_SESSION['mensagem'] = 'Paciente chamado.';
        $_SESSION['ultima_chamada'] = $senhaId;
        $_SESSION['chamada_ts'] = time();
        Auditoria::registar(
            $medicoId,
            'chamar_paciente',
            'Senha ID: ' . $senhaId
        );
    } else {
        $_SESSION['erro'] =
            'Não foi possível chamar — senha já alterada.';
    }
    header('Location: ' . $destino);
    exit;
}

// ------------------------------------------------
// CONCLUIR atendimento
// ------------------------------------------------
if ($acao === 'concluir' && $senhaId > 0) {
    if (Senha::concluir($senhaId)) {
        $_SESSION['mensagem'] = 'Atendimento concluído.';
        Auditoria::registar(
            $medicoId,
            'concluir_atendimento',
            'Senha ID: ' . $senhaId
        );
    } else {
        $_SESSION['erro'] =
            'Não foi possível concluir o atendimento.';
    }
    header('Location: ' . $destino);
    exit;
}

// ------------------------------------------------
// CANCELAR por ausência
// ------------------------------------------------
if ($acao === 'cancelar' && $senhaId > 0) {
    if (Senha::cancelar($senhaId)) {
        $_SESSION['mensagem'] =
            'Senha cancelada — paciente ausente.';
        Auditoria::registar(
            $medicoId,
            'cancelar_paciente',
            'Senha ID: ' . $senhaId
        );
    } else {
        $_SESSION['erro'] =
            'Não foi possível cancelar a senha.';
    }
    header('Location: ' . $destino);
    exit;
}

// ------------------------------------------------
// DESFAZER chamada (janela de 15 segundos)
// ------------------------------------------------
if ($acao === 'desfazer' && $senhaId > 0) {
    if (Senha::desfazerChamada($senhaId)) {
        $_SESSION['mensagem'] =
            'Chamada desfeita — paciente voltou à fila.';
        Auditoria::registar(
            $medicoId,
            'desfazer_chamada',
            'Senha ID: ' . $senhaId
        );
        unset(
            $_SESSION['ultima_chamada'],
            $_SESSION['chamada_ts']
        );
    } else {
        $_SESSION['erro'] =
            'Tempo esgotado — não é possível desfazer.';
    }
    header('Location: ' . $destino);
    exit;
}

header('Location: ' . $destino);
exit;
