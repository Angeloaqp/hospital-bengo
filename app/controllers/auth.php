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

validarTokenCsrf();

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
                    senha_hash, perfil, estado,
                    tentativas_falhadas, bloqueado_ate
             FROM utilizadores
             WHERE nome_utilizador = :u
             LIMIT 1"
        );
        $stmt->execute([':u' => $nomeUtilizador]);
        $utilizador = $stmt->fetch();

        if (!$utilizador) {
            $_SESSION['erro_login'] = 'Utilizador ou senha incorrectos.';
            header('Location: ' . BASE_URL . 'public/index.php');
            exit;
        }

        // Verifica se a conta está bloqueada
        if ($utilizador['bloqueado_ate']) {
            $bloqueadoAte = strtotime($utilizador['bloqueado_ate']);
            if (time() < $bloqueadoAte) {
                $minutos = ceil(($bloqueadoAte - time()) / 60);
                $_SESSION['erro_login'] = "Conta bloqueada por segurança. Tente novamente em $minutos minuto(s).";
                header('Location: ' . BASE_URL . 'public/index.php');
                exit;
            } else {
                // Tempo de bloqueio expirou, limpar
                $db->prepare("UPDATE utilizadores SET tentativas_falhadas = 0, bloqueado_ate = NULL WHERE id = :id")->execute([':id' => $utilizador['id']]);
                $utilizador['tentativas_falhadas'] = 0;
            }
        }

        // Verifica estado activo e senha
        if ($utilizador['estado'] != 1 || !password_verify($senha, $utilizador['senha_hash'])) {
            // Conta falhas
            $falhas = $utilizador['tentativas_falhadas'] + 1;
            $query = "UPDATE utilizadores SET tentativas_falhadas = :f";
            $params = [':f' => $falhas, ':id' => $utilizador['id']];
            
            if ($falhas >= 5) {
                $query .= ", bloqueado_ate = DATE_ADD(NOW(), INTERVAL 15 MINUTE)";
                $_SESSION['erro_login'] = 'Muitas tentativas falhadas. Conta bloqueada por 15 minutos.';
            } else {
                $_SESSION['erro_login'] = 'Utilizador ou senha incorrectos.';
            }
            
            $query .= " WHERE id = :id";
            $db->prepare($query)->execute($params);

            header('Location: ' . BASE_URL . 'public/index.php');
            exit;
        }

        // Sucesso: Limpar falhas se existirem
        if ($utilizador['tentativas_falhadas'] > 0) {
            $db->prepare("UPDATE utilizadores SET tentativas_falhadas = 0, bloqueado_ate = NULL WHERE id = :id")->execute([':id' => $utilizador['id']]);
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
