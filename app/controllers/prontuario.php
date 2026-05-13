<?php
// ================================================
// Hospital Geral do Bengo — Controller: Prontuário Clínico
// Guardar e atualizar notas de consulta
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Prontuario.php';
require_once __DIR__ . '/../../app/models/Auditoria.php';

exigirPerfil(['medico', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'app/views/medico/fila_actual.php');
    exit;
}

validarTokenCsrf();

$acao      = trim($_POST['acao'] ?? '');
$medicoId  = (int) sessao('utilizador_id');
$destino   = BASE_URL . 'app/views/medico/fila_actual.php';

// ------------------------------------------------
// GUARDAR prontuário (novo ou atualizar)
// ------------------------------------------------
if ($acao === 'guardar_prontuario') {
    $senhaId      = (int) ($_POST['senha_id'] ?? 0);
    $prontuarioId = (int) ($_POST['prontuario_id'] ?? 0);
    $notas        = $_POST['notas_clinicas'] ?? '';
    $prescricao   = $_POST['prescricao'] ?? '';
    $diagnostico  = $_POST['diagnostico'] ?? '';

    if ($senhaId <= 0) {
        $_SESSION['erro'] = 'Dados inválidos — senha não identificada.';
        header('Location: ' . $destino);
        exit;
    }

    // Obter paciente_id a partir da senha
    $pacienteId = Prontuario::pacienteDaSenha($senhaId);

    if (!$pacienteId) {
        $_SESSION['erro'] = 'Paciente não encontrado para esta consulta.';
        header('Location: ' . $destino);
        exit;
    }

    if ($prontuarioId > 0) {
        // Atualizar existente
        Prontuario::atualizar($prontuarioId, $notas, $prescricao, $diagnostico);
        $_SESSION['mensagem'] = 'Prontuário atualizado com sucesso.';
        Auditoria::registar($medicoId, 'atualizar_prontuario', "Prontuário #$prontuarioId, Senha #$senhaId");
    } else {
        // Criar novo
        $novoId = Prontuario::criar($senhaId, $pacienteId, $medicoId, $notas, $prescricao, $diagnostico);
        $_SESSION['mensagem'] = 'Prontuário registado com sucesso.';
        Auditoria::registar($medicoId, 'criar_prontuario', "Prontuário #$novoId, Senha #$senhaId, Paciente #$pacienteId");
    }

    header('Location: ' . $destino);
    exit;
}

// ------------------------------------------------
// CONCLUIR COM PRONTUÁRIO (guardar + concluir senha)
// ------------------------------------------------
if ($acao === 'concluir_com_prontuario') {
    require_once __DIR__ . '/../../app/models/Senha.php';

    $senhaId      = (int) ($_POST['senha_id'] ?? 0);
    $prontuarioId = (int) ($_POST['prontuario_id'] ?? 0);
    $notas        = $_POST['notas_clinicas'] ?? '';
    $prescricao   = $_POST['prescricao'] ?? '';
    $diagnostico  = $_POST['diagnostico'] ?? '';

    if ($senhaId <= 0) {
        $_SESSION['erro'] = 'Dados inválidos.';
        header('Location: ' . $destino);
        exit;
    }

    $pacienteId = Prontuario::pacienteDaSenha($senhaId);

    if (!$pacienteId) {
        $_SESSION['erro'] = 'Paciente não encontrado.';
        header('Location: ' . $destino);
        exit;
    }

    // Guardar/Atualizar prontuário
    if ($prontuarioId > 0) {
        Prontuario::atualizar($prontuarioId, $notas, $prescricao, $diagnostico);
    } else {
        $prontuarioId = Prontuario::criar($senhaId, $pacienteId, $medicoId, $notas, $prescricao, $diagnostico);
    }

    // Concluir a senha
    if (Senha::concluir($senhaId)) {
        $_SESSION['mensagem'] = 'Consulta concluída e prontuário guardado.';
        Auditoria::registar($medicoId, 'concluir_com_prontuario', "Prontuário #$prontuarioId, Senha #$senhaId");
    } else {
        $_SESSION['erro'] = 'Prontuário guardado mas não foi possível concluir a senha.';
    }

    header('Location: ' . $destino);
    exit;
}

// Fallback
header('Location: ' . $destino);
exit;
