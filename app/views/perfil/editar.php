<?php
// ================================================
// Hospital Geral do Bengo
// Vista: Meu Perfil (Editar Foto, Nome e Senha)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

// Segurança
exigirPerfil(['admin', 'medico', 'recepcionista']);

$meuId = (int) sessao('utilizador_id');
$meuPerfil = sessao('perfil');

// Vai buscar o URL base de regresso (dashboard) consoante o cargo logado
$urlVoltar = BASE_URL . "app/views/{$meuPerfil}/dashboard.php";

$dados = Utilizador::obter($meuId);

$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

$mensagem_senha = $_SESSION['mensagem_senha'] ?? '';
$erro_senha = $_SESSION['erro_senha'] ?? '';
unset($_SESSION['mensagem_senha'], $_SESSION['erro_senha']);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil —
        <?= APP_NOME ?>
    </title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .perfil-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
            align-items: start;
        }

        .perfil-card {
            background: #fff;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
            text-align: center;
        }

        .avatar-wrap {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto 16px;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .avatar-iniciais {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--azul), #3B82F6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            border: 4px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--azul);
            color: #fff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            border: 2px solid #fff;
        }

        .btn-upload input[type="file"] {
            display: none;
            /* Oculta input, clicamos na div */
        }

        .perfil-titulo {
            font-size: 20px;
            font-weight: 700;
            color: var(--texto);
            margin-bottom: 4px;
        }

        .perfil-sub {
            font-size: 14px;
            color: var(--texto-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .form-card {
            background: #fff;
            padding: 32px;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
            margin-bottom: 24px;
        }

        .card-header {
            font-size: 16px;
            font-weight: 600;
            color: var(--texto);
            border-bottom: 1px solid var(--borda);
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: var(--texto);
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--borda);
            border-radius: var(--radius-sm);
        }

        @media (max-width: 768px) {
            .perfil-grid {
                grid-template-columns: 1fr;
            }

            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="text-on-surface">
<?php $paginaActual = 'perfil'; ?>
        <?php include __DIR__ . '/../comum/sidebar.php'; ?>

        <?php $tituloPagina = 'Perfil'; $subtituloPagina = ''; ?>
        <?php include __DIR__ . '/../comum/header.php'; ?>
<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1500px]">
<div class="flex items-center justify-between mb-6">
                <div>
                    <h2>Definições de Perfil</h2>
                    <div class="sub">Faça a gestão dos seus dados e segurança</div>
                </div>
            </div>

            <div class="perfil-grid">

                <!-- ESQUERDA: FOTO E IDENTIDADE -->
                <div class="perfil-card">
                    <form id="form-foto" action="<?= BASE_URL ?>app/controllers/perfil.php" method="POST"
                        enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="actualizar">
                        <input type="hidden" name="nome" value="<?= htmlspecialchars($dados['nome']) ?>">
                        <input type="hidden" name="telefone" value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>">

                        <div class="avatar-wrap">
                            <?php if (!empty($dados['foto_path'])): ?>
                                <img src="<?= BASE_URL . 'public/' . $dados['foto_path'] ?>" alt="Avatar"
                                    class="avatar-img">
                            <?php else: ?>
                                <div class="avatar-iniciais">
                                    <?= strtoupper(substr($dados['nome'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>

                            <label class="btn-upload" title="Alterar Fotografia">
                                📷
                                <input type="file" name="foto" id="foto-input"
                                    accept="image/jpeg, image/png, image/webp"
                                    onchange="document.getElementById('form-foto').submit();">
                            </label>
                        </div>
                    </form>

                    <div class="perfil-titulo">
                        <?= htmlspecialchars($dados['nome']) ?>
                    </div>
                    <div class="perfil-sub">
                        <?= htmlspecialchars($dados['perfil']) ?>
                    </div>
                    <?php if (!empty($dados['especialidade'])): ?>
                        <div style="font-size:13px; color:var(--azul); margin-top:8px; font-weight: 500;">
                            🩺
                            <?= htmlspecialchars($dados['especialidade']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- DIREITA: FORMULÁRIOS DE DADOS -->
                <div>

                    <!-- Dados Básicos -->
                    <div class="form-card">
                        <div class="card-header">Os Seus Dados</div>
                        <?php if ($mensagem): ?>
                            <div class="alerta alerta-sucesso">✓
                                <?= htmlspecialchars($mensagem) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($erro): ?>
                            <div class="alerta alerta-perigo">⚠
                                <?= htmlspecialchars($erro) ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?= BASE_URL ?>app/controllers/perfil.php" method="POST">
                            <input type="hidden" name="acao" value="actualizar">

                            <div class="form-group">
                                <label>Nome Completo</label>
                                <input type="text" name="nome" value="<?= htmlspecialchars($dados['nome']) ?>" required>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label>Nome de Utilizador (Acesso) <span style="font-weight:normal;color:gray">(Não
                                            editável)</span></label>
                                    <input type="text" value="<?= htmlspecialchars($dados['nome_utilizador']) ?>"
                                        readonly style="background:#f9fafb; cursor:not-allowed">
                                </div>
                                <div class="form-group">
                                    <label>Telefone</label>
                                    <input type="text" name="telefone"
                                        value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primario" style="margin-top:8px;">Guardar
                                Alterações</button>
                        </form>
                    </div>

                    <!-- Alterar Senha -->
                    <div class="form-card" id="card-senha">
                        <div class="card-header" style="color:var(--vermelho)">🔐 Segurança e Palavra-passe</div>
                        <?php if ($mensagem_senha): ?>
                            <div class="alerta alerta-sucesso">✓
                                <?= htmlspecialchars($mensagem_senha) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($erro_senha): ?>
                            <div class="alerta alerta-perigo">⚠
                                <?= htmlspecialchars($erro_senha) ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?= BASE_URL ?>app/controllers/perfil.php" method="POST">
                            <input type="hidden" name="acao" value="senha">

                            <div class="form-group">
                                <label>Palavra-passe Actual</label>
                                <input type="password" name="senha_antiga" required>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label>Nova Palavra-passe</label>
                                    <input type="password" name="senha_nova" minlength="6" required>
                                </div>
                                <div class="form-group">
                                    <label>Confirmar Nova Palavra-passe</label>
                                    <input type="password" name="senha_conf" minlength="6" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-perigo" style="margin-top:8px;">Alterar Senha</button>
                        </form>
                    </div>

                </div>

            </div>
</main>
</div>
</body>

</html>