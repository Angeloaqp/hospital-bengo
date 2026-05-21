<?php
// ================================================
// Hospital Geral do Bengo — Controller: Pacientes API
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Paciente.php';

exigirPerfil(['recepcionista', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'erros' => ['Método não permitido.']]);
    exit;
}

validarTokenCsrf();

$acao = trim($_POST['acao'] ?? '');

if ($acao === 'registar_apenas') {
    $nome = trim($_POST['nome'] ?? '');
    $bi_nif = trim($_POST['bi_nif'] ?? '');
    $idade = trim($_POST['idade'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');
    $morada = trim($_POST['morada'] ?? '');
    $peso = trim($_POST['peso'] ?? '');

    $erros = [];

    if (empty($nome) || mb_strlen($nome) < 3) {
        $erros[] = 'Nome deve ter pelo menos 3 caracteres.';
    }

    if (!is_numeric($idade) || $idade < 0 || $idade > 120) {
        $erros[] = 'Idade inválida.';
    }

    if (!empty($sexo) && !in_array($sexo, ['M', 'F'])) {
        $erros[] = 'Género inválido.';
    }

    if (empty($morada)) {
        $erros[] = 'Morada é obrigatória.';
    }

    if ((int) $idade < 18 && empty($peso)) {
        $erros[] = 'Peso é obrigatório para menores de 18 anos.';
    }

    if (!empty($erros)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'erros' => $erros]);
        exit;
    }

    try {
        $pacienteId = (int) ($_POST['paciente_id'] ?? 0);
        $dados = [
            'nome' => $nome,
            'bi_nif' => $bi_nif,
            'idade' => $idade,
            'sexo' => $sexo,
            'morada' => $morada,
            'peso' => $peso ?: null,
        ];
        
        if ($pacienteId > 0) {
            Paciente::atualizarApenas($pacienteId, $dados);
        } else {
            $pacienteId = Paciente::registarApenas($dados, (int) sessao('utilizador_id'));
        }

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'paciente_id' => $pacienteId]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'erros' => ['Erro interno ao registar paciente.']]);
        exit;
    }
}

header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'erros' => ['Ação inválida.']]);
exit;
