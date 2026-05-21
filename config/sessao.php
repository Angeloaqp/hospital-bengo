<?php
// ================================================
// Hospital Geral do Bengo
// Configuração de sessões e funções auxiliares
// ================================================

// Inicia sessão segura
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 28800,    // 8 horas
        'path' => '/',
        'secure' => false,    // true em produção com HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
require_once __DIR__ . '/seguranca.php';

// Limpeza automática diária de ausências (executada apenas uma vez por dia por sessão)
if (!isset($_SESSION['ultima_limpeza_ausencias']) || $_SESSION['ultima_limpeza_ausencias'] !== date('Y-m-d')) {
    require_once __DIR__ . '/../app/models/Marcacao.php';
    if (class_exists('Marcacao') && method_exists('Marcacao', 'marcarAusenciasAutomaticas')) {
        try {
            Marcacao::marcarAusenciasAutomaticas();
            $_SESSION['ultima_limpeza_ausencias'] = date('Y-m-d');
        } catch (\Throwable $e) {
            // Ignorar erro silenciosamente para não quebrar a sessão
        }
    }
}

// ------------------------------------------------
// Funções de autenticação e controlo de acesso
// ------------------------------------------------

/**
 * Verifica se o utilizador está autenticado.
 * Redireciona para login se não estiver.
 */
function exigirLogin(): void
{
    if (empty($_SESSION['utilizador_id'])) {
        header('Location: ' . BASE_URL . 'public/index.php');
        exit;
    }
}

/**
 * Verifica se o utilizador tem o perfil exigido.
 * Redireciona para página de acesso negado se não tiver.
 *
 * @param string|array $perfis Perfil ou array de perfis permitidos
 */
function exigirPerfil(string|array $perfis): void
{
    exigirLogin();
    $perfisPermitidos = (array) $perfis;
    if (!in_array($_SESSION['perfil'], $perfisPermitidos, true)) {
        http_response_code(403);
        die('<h2 style="font-family:sans-serif;color:#DC2626;
             padding:2rem;">Acesso negado — 
             não tem permissão para esta página.</h2>');
    }
}

/**
 * Retorna o valor de sessão ou null se não existir.
 *
 * @param string $chave
 * @return mixed
 */
function sessao(string $chave): mixed
{
    return $_SESSION[$chave] ?? null;
}

/**
 * Encerra a sessão completamente.
 */
function encerrarSessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Redireciona para o dashboard correcto conforme o perfil.
 */
function redirecionarPorPerfil(): void
{
    $destinos = [
        'recepcionista' => BASE_URL .
            'app/views/recepcionista/dashboard.php',
        'medico' => BASE_URL .
            'app/views/medico/dashboard.php',
        'admin' => BASE_URL .
            'app/views/admin/dashboard.php',
    ];
    $perfil = sessao('perfil');
    if (isset($destinos[$perfil])) {
        header('Location: ' . $destinos[$perfil]);
        exit;
    }
}
