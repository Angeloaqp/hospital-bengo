<?php
// ================================================
// Hospital Geral do Bengo
// Página de Login — Ponto de entrada do sistema
// ================================================

require_once __DIR__ . '/../config/base_url.php';
require_once __DIR__ . '/../config/sessao.php';

// Se já está autenticado, redireciona para o dashboard
if (!empty($_SESSION['utilizador_id'])) {
    redirecionarPorPerfil();
}

// Recupera e limpa erro de login
$erroLogin = $_SESSION['erro_login'] ?? '';
unset($_SESSION['erro_login']);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NOME ?></title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>

    <div class="login-container">

        <div class="login-logo">
            <div class="logo-box">HGB</div>
            <h1><?= APP_NOME ?></h1>
            <p>Sistema de Gestão de Filas Hospitalares</p>
        </div>

        <div class="login-card">

            <?php if ($erroLogin): ?>
                <div class="alerta-erro">
                    <?= htmlspecialchars($erroLogin) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>app/controllers/auth.php" id="form-login">
                <input type="hidden" name="acao" value="login">

                <div class="form-group">
                    <label for="nome_utilizador">
                        Utilizador
                    </label>
                    <input type="text" id="nome_utilizador" name="nome_utilizador"
                        placeholder="O seu nome de utilizador" autocomplete="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="A sua senha"
                        autocomplete="current-password" required>
                </div>

                <div class="form-group">
                    <label for="perfil_info">
                        Perfil de acesso
                    </label>
                    <select id="perfil_info" disabled>
                        <option>
                            Detectado automaticamente pelo sistema
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn-login" id="btn-entrar">
                    Entrar no sistema
                </button>
            </form>

        </div>

        <div class="login-footer">
            <?= APP_NOME ?> v<?= APP_VERSAO ?> &mdash;
            Acesso restrito a pessoal autorizado
        </div>

    </div>

    <script src="assets/js/validacao.js"></script>
</body>

</html>