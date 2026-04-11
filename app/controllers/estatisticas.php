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

    if ($uid === (int) sessao('utilizador_id')) {
        $_SESSION['erro'] =
            'Não pode desactivar a sua própria conta.';
        header('Location: ' . BASE_URL .
            'app/views/admin/utilizadores.php');
        exit;
    }

    if ($uid > 0 && Estatistica::toggleEstado($uid, $estado)) {
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

// ------------------------------------------------
// ACÇÃO: Criar utilizador
// ------------------------------------------------
if ($acao === 'criar_utilizador') {
    $nome = trim($_POST['nome'] ?? '');
    $username = trim($_POST['nome_utilizador'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $perfil = trim($_POST['perfil'] ?? '');
    $espId = (int) ($_POST['especialidade_id'] ?? 0);
    $consId = (int) ($_POST['consultorio_id'] ?? 0);
    $telefone = trim($_POST['telefone'] ?? '');

    $erros = [];

    if (empty($nome) || mb_strlen($nome) < 3) {
        $erros[] = 'Nome deve ter pelo menos 3 caracteres.';
    }
    if (empty($username) || mb_strlen($username) < 3) {
        $erros[] = 'Username deve ter pelo menos 3 caracteres.';
    }
    if (empty($senha) || mb_strlen($senha) < 6) {
        $erros[] = 'Senha deve ter pelo menos 6 caracteres.';
    }
    if (!in_array($perfil, ['admin', 'medico', 'recepcionista'])) {
        $erros[] = 'Perfil inválido.';
    }
    if ($perfil === 'medico' && $espId === 0) {
        $erros[] = 'Médicos devem ter uma especialidade.';
    }
    if (Estatistica::usernameExiste($username)) {
        $erros[] = 'Este nome de utilizador já existe.';
    }

    if (!empty($erros)) {
        $_SESSION['erro'] = implode(' ', $erros);
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . BASE_URL .
            'app/views/admin/criar_utilizador.php');
        exit;
    }

    try {
        Estatistica::criarUtilizador([
            'nome' => $nome,
            'nome_utilizador' => $username,
            'senha' => $senha,
            'perfil' => $perfil,
            'especialidade_id' => $espId,
            'consultorio_id' => $consId,
            'telefone' => $telefone,
        ]);

        $_SESSION['mensagem'] =
            'Utilizador "' . $nome . '" criado com sucesso.';
        header('Location: ' . BASE_URL .
            'app/views/admin/utilizadores.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['erro'] = 'Erro ao criar utilizador.';
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . BASE_URL .
            'app/views/admin/criar_utilizador.php');
        exit;
    }
}

// ------------------------------------------------
// ACÇÃO: Editar utilizador
// ------------------------------------------------
if ($acao === 'editar_utilizador') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $username = trim($_POST['nome_utilizador'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $perfil = trim($_POST['perfil'] ?? '');
    $espId = (int) ($_POST['especialidade_id'] ?? 0);
    $consId = (int) ($_POST['consultorio_id'] ?? 0);
    $telefone = trim($_POST['telefone'] ?? '');

    $erros = [];

    if ($id <= 0) {
        $erros[] = 'Utilizador inválido.';
    }
    if (empty($nome) || mb_strlen($nome) < 3) {
        $erros[] = 'Nome deve ter pelo menos 3 caracteres.';
    }
    if (empty($username) || mb_strlen($username) < 3) {
        $erros[] = 'Username deve ter pelo menos 3 caracteres.';
    }
    if (!empty($senha) && mb_strlen($senha) < 6) {
        $erros[] = 'Senha deve ter pelo menos 6 caracteres.';
    }
    if (!in_array($perfil, ['admin', 'medico', 'recepcionista'])) {
        $erros[] = 'Perfil inválido.';
    }
    if ($perfil === 'medico' && $espId === 0) {
        $erros[] = 'Médicos devem ter uma especialidade.';
    }
    if (Estatistica::usernameExiste($username, $id)) {
        $erros[] = 'Este nome de utilizador já existe.';
    }

    if (!empty($erros)) {
        $_SESSION['erro'] = implode(' ', $erros);
        header('Location: ' . BASE_URL .
            'app/views/admin/editar_utilizador.php?id=' . $id);
        exit;
    }

    try {
        Estatistica::editarUtilizador($id, [
            'nome' => $nome,
            'nome_utilizador' => $username,
            'senha' => $senha,
            'perfil' => $perfil,
            'especialidade_id' => $espId,
            'consultorio_id' => $consId,
            'telefone' => $telefone,
        ]);

        $_SESSION['mensagem'] =
            'Utilizador actualizado com sucesso.';
        header('Location: ' . BASE_URL .
            'app/views/admin/utilizadores.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['erro'] = 'Erro ao actualizar utilizador.';
        header('Location: ' . BASE_URL .
            'app/views/admin/editar_utilizador.php?id=' . $id);
        exit;
    }
}

header('Location: ' . BASE_URL .
    'app/views/admin/dashboard.php');
exit;
