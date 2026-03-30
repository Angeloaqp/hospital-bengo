<?php
// ================================================
// Hospital Geral do Bengo — Controller: Pacientes
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Paciente.php';

exigirPerfil(['recepcionista', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL .
        'app/views/recepcionista/dashboard.php');
    exit;
}

$acao = trim($_POST['acao'] ?? '');

// ------------------------------------------------
// ACÇÃO: Registar Paciente
// ------------------------------------------------
if ($acao === 'registar') {

    // --- Recolha e sanitização ---
    $nome = trim($_POST['nome'] ?? '');
    $idade = trim($_POST['idade'] ?? '');
    $morada = trim($_POST['morada'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    $tipo_atendimento = trim($_POST['tipo_atendimento_id'] ?? '');
    $prioridade = trim($_POST['prioridade'] ?? '');

    // --- Validação server-side ---
    $erros = [];

    if (empty($nome) || mb_strlen($nome) < 3) {
        $erros[] = 'Nome deve ter pelo menos 3 caracteres.';
    }

    if (!is_numeric($idade) || $idade < 0 || $idade > 120) {
        $erros[] = 'Idade inválida.';
    }

    if (empty($morada)) {
        $erros[] = 'Morada é obrigatória.';
    }

    // Peso obrigatório para menores de 18
    if ((int) $idade < 18 && empty($peso)) {
        $erros[] = 'Peso é obrigatório para menores de 18 anos.';
    }

    if (
        empty($tipo_atendimento) ||
        !is_numeric($tipo_atendimento)
    ) {
        $erros[] = 'Tipo de atendimento inválido.';
    }

    if (!in_array($prioridade, ['1', '2', '3', '4'], true)) {
        $erros[] = 'Nível de prioridade inválido.';
    }

    // Se houver erros, volta ao formulário
    if (!empty($erros)) {
        $_SESSION['erros_form'] = $erros;
        $_SESSION['dados_form'] = $_POST;
        header('Location: ' . BASE_URL .
            'app/views/recepcionista/registar.php');
        exit;
    }

    // --- Registo ---
    try {
        $codigo = Paciente::registarComSenha([
            'nome' => $nome,
            'idade' => $idade,
            'morada' => $morada,
            'peso' => $peso ?: null,
            'tipo_atendimento_id' => $tipo_atendimento,
            'prioridade' => $prioridade,
        ], (int) sessao('utilizador_id'));

        $_SESSION['mensagem'] =
            "Paciente registado com sucesso — Senha: {$codigo}";
        $_SESSION['ultima_senha'] = $codigo;

        header('Location: ' . BASE_URL .
            'app/views/recepcionista/dashboard.php');
        exit;

    } catch (RuntimeException $e) {
        $_SESSION['erros_form'] = [$e->getMessage()];
        $_SESSION['dados_form'] = $_POST;
        header('Location: ' . BASE_URL .
            'app/views/recepcionista/registar.php');
        exit;
    }
}

// Fallback
header('Location: ' . BASE_URL .
    'app/views/recepcionista/dashboard.php');
exit;
