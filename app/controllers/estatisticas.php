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

validarTokenCsrf();

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
    $sexo = trim($_POST['sexo'] ?? 'M');
    if (!in_array($sexo, ['M', 'F'])) $sexo = 'M';
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

    // Tratamento de upload de foto (opcional)
    $fotoPath = null;
    $maxSize = 2 * 1024 * 1024; // 2MB
    $formatosPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $img = $_FILES['foto'];

        if ($img['size'] > $maxSize) {
            $erros[] = 'A foto deve ter no máximo 2MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeReal = $finfo->file($img['tmp_name']);
            if (!in_array($mimeReal, $formatosPermitidos)) {
                $erros[] = 'Foto: apenas JPG, PNG ou WEBP.';
            }
        }
    }

    if (!empty($erros)) {
        $_SESSION['erro'] = implode(' ', $erros);
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . BASE_URL .
            'app/views/admin/criar_utilizador.php');
        exit;
    }

    // Mover foto para destino final (após validação completa)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $img = $_FILES['foto'];
        $pastaDestino = __DIR__ . '/../../public/uploads/fotos/';
        if (!is_dir($pastaDestino)) {
            mkdir($pastaDestino, 0755, true);
        }
        $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        $nomeArquivo = 'perfil_novo_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
        $caminhoFinal = $pastaDestino . $nomeArquivo;

        if (move_uploaded_file($img['tmp_name'], $caminhoFinal)) {
            $fotoPath = 'uploads/fotos/' . $nomeArquivo;
        } else {
            $_SESSION['erro'] = 'Erro ao guardar a foto de perfil.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL .
                'app/views/admin/criar_utilizador.php');
            exit;
        }
    }

    try {
        Estatistica::criarUtilizador([
            'nome' => $nome,
            'sexo' => $sexo,
            'nome_utilizador' => $username,
            'senha' => $senha,
            'perfil' => $perfil,
            'especialidade_id' => $espId,
            'consultorio_id' => $consId,
            'telefone' => $telefone,
            'foto_path' => $fotoPath,
        ]);

        // Buscar nomes da especialidade e consultório para o card de confirmação
        $espNome = '';
        $consNome = '';
        if ($espId > 0) {
            $espList = Estatistica::listarEspecialidades();
            foreach ($espList as $e) {
                if ((int)$e['id'] === $espId) { $espNome = $e['nome']; break; }
            }
        }
        if ($consId > 0) {
            $consList = Estatistica::listarConsultorios();
            foreach ($consList as $c) {
                if ((int)$c['id'] === $consId) { $consNome = $c['nome']; break; }
            }
        }

        $_SESSION['utilizador_criado'] = [
            'nome' => $nome,
            'sexo' => $sexo,
            'nome_utilizador' => $username,
            'perfil' => $perfil,
            'especialidade' => $espNome,
            'consultorio' => $consNome,
            'telefone' => $telefone,
            'foto_path' => $fotoPath,
            'data' => date('d/m/y'),
        ];

        header('Location: ' . BASE_URL .
            'app/views/admin/criar_utilizador.php');
        exit;

    } catch (PDOException $e) {
        // Se falhou o insert, apagar foto já movida
        if ($fotoPath) {
            $fotoReal = __DIR__ . '/../../public/' . $fotoPath;
            if (file_exists($fotoReal)) {
                @unlink($fotoReal);
            }
        }
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
    $sexo = trim($_POST['sexo'] ?? 'M');
    if (!in_array($sexo, ['M', 'F'])) $sexo = 'M';
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
            'sexo' => $sexo,
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
