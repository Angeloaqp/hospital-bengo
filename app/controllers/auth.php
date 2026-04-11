<?php
// ================================================
// Hospital Geral do Bengo
// Controller de Autenticação — Login e Logout
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Auditoria.php';

// Processa apenas pedidos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'public/index.php');
    exit;
}

$acao = trim($_POST['acao'] ?? '');

// ------------------------------------------------
// ACÇÃO: Login
// ------------------------------------------------
if ($acao === 'login') {
    $nomeUtilizador = trim($_POST['nome_utilizador'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Validação básica
    if (empty($nomeUtilizador) || empty($senha)) {
        $_SESSION['erro_login'] =
            'Preencha o utilizador e a senha.';
        header('Location: ' . BASE_URL . 'public/index.php');
        exit;
    }

    try {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT id, nome, nome_utilizador, 
                    senha_hash, perfil, estado
             FROM utilizadores
             WHERE nome_utilizador = :u
             LIMIT 1"
        );
        $stmt->execute([':u' => $nomeUtilizador]);
        $utilizador = $stmt->fetch();

        // Verifica utilizador + senha + estado activo
        if (
            !$utilizador ||
            !password_verify($senha, $utilizador['senha_hash']) ||
            $utilizador['estado'] != 1
        ) {
            $_SESSION['erro_login'] =
                'Utilizador ou senha incorrectos.';
            header('Location: ' . BASE_URL . 'public/index.php');
            exit;
        }

        // Regenera ID de sessão por segurança
        session_regenerate_id(true);

        // Guarda dados na sessão
        $_SESSION['utilizador_id'] = $utilizador['id'];
        $_SESSION['utilizador_nome'] = $utilizador['nome'];
        $_SESSION['nome_utilizador'] = $utilizador['nome_utilizador'];
        $_SESSION['perfil'] = $utilizador['perfil'];

        // Remove erro anterior se existir
        unset($_SESSION['erro_login']);

        // Regista login na auditoria
        Auditoria::registar(
            $utilizador['id'],
            'login',
            'Perfil: ' . $utilizador['perfil']
        );

        // Redireciona para o dashboard do perfil
        redirecionarPorPerfil();

    } catch (PDOException $e) {
        $_SESSION['erro_login'] =
            'Erro interno. Tente novamente.';
        header('Location: ' . BASE_URL . 'public/index.php');
        exit;
    }
}

// ------------------------------------------------
// ACÇÃO: Logout
// ------------------------------------------------
if ($acao === 'logout') {
    $uid = (int) sessao('utilizador_id');
    if ($uid > 0) {
        Auditoria::registar($uid, 'logout');
    }
    encerrarSessao();
    header('Location: ' . BASE_URL . 'public/index.php');
    exit;
}

// Fallback
header('Location: ' . BASE_URL . 'public/index.php');
exit;
