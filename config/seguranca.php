<?php
// ================================================
// Hospital Geral do Bengo
// Funcionalidades de Segurança (CSRF)
// ================================================

// Iniciar a sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Gera um token CSRF único e guarda na sessão.
 * Deve ser chamado antes ou no início do render do formulário.
 * 
 * @return string O token gerado.
 */
function gerarTokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Fallback em sistemas antigos sem random_bytes
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF submetido via POST.
 * Se for inválido, devolve HTTP 403 e pára a execução.
 */
function validarTokenCsrf(): void
{
    // Apenas testar se for POST (para GET não é necessário por design da App, mas recomendado em sistemas puristas)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tokenSubmetido = $_POST['csrf_token'] ?? '';
        $tokenSessao = $_SESSION['csrf_token'] ?? '';

        if (empty($tokenSubmetido) || empty($tokenSessao) || !hash_equals($tokenSessao, $tokenSubmetido)) {
            http_response_code(403);
            die('
                <div style="font-family: sans-serif; text-align: center; margin-top: 50px;">
                    <h1 style="color: #DC2626;">Acesso Negado (Erro CSRF)</h1>
                    <p>O token de segurança é inválido ou a sua sessão expirou.</p>
                    <a href="javascript:history.back()" style="padding: 10px 20px; background: #000; color: #fff; text-decoration: none; border-radius: 8px;">Voltar</a>
                </div>
            ');
        }
    }
}
