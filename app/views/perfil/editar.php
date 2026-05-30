<?php
// ================================================
// Hospital Geral do Bengo
// Vista: Meu Perfil (Editar Foto, Nome e Senha) - Tactile Editorial
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
    <title>Definições de Perfil — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--cor-scrollbar-light); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--cor-scrollbar-light-hover); }

        @keyframes glideIn {
            0% { opacity: 0; transform: translateY(20px); filter: blur(4px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .glide-in { animation: glideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .stagger-1 { animation-delay: 0.05s; }
        .stagger-2 { animation-delay: 0.1s; }

        /* Floating Label Field */
        .field-wrap { position: relative; width: 100%; margin-bottom: 24px; }
        .field-wrap .fi {
            width: 100%; background: var(--cor-input-bg); border: 2px solid transparent; border-radius: 1.25rem;
            padding: 1.6rem 1.25rem 0.5rem 3.5rem; font-size: 0.95rem; font-weight: 600; color: var(--cor-on-surface);
            font-family: 'Manrope', sans-serif; outline: none; transition: all 0.35s cubic-bezier(0.2, 0.8, 0.2, 1); line-height: 1.2;
            height: 3.8rem;
        }
        /* No icon padding adjustment */
        .field-wrap.no-icon .fi { padding-left: 1.25rem; }
        
        .field-wrap .fi::placeholder { color: transparent; }
        .field-wrap .fi:focus { background: var(--cor-surface-container-lowest); border-color: var(--cor-on-surface); box-shadow: 0 6px 24px -4px rgba(0,0,0,0.06); }
        .field-wrap .fi:disabled, .field-wrap .fi[readonly] { background: var(--cor-surface-container-low); color: var(--cor-scrollbar); cursor: not-allowed; }
        
        .field-wrap .fl {
            position: absolute; left: 3.5rem; top: 1.3rem; font-size: 0.9rem; font-weight: 600; color: var(--cor-input-label);
            pointer-events: none; transition: all 0.25s cubic-bezier(0.2, 0.8, 0.2, 1); transform-origin: left top; z-index: 2;
        }
        .field-wrap.no-icon .fl { left: 1.25rem; }
        
        .field-wrap .fi:focus ~ .fl, .field-wrap .fi:not(:placeholder-shown) ~ .fl {
            transform: translateY(-0.85rem) scale(0.75); font-weight: 800; color: var(--cor-on-surface);
        }
        .field-wrap .fi:disabled ~ .fl, .field-wrap .fi[readonly] ~ .fl { color: var(--cor-scrollbar); }

        /* Material Icons prefix */
        .field-wrap .prefix-icon {
            position: absolute; left: 1.15rem; top: 50%; transform: translateY(-50%);
            font-size: 22px; color: var(--cor-input-placeholder); flex-shrink: 0; pointer-events: none; transition: color 0.3s; z-index: 3;
        }
        .field-wrap .fi:focus ~ .prefix-icon { color: var(--cor-on-surface); }

        /* Setup Avatar Upload */
        .avatar-edit-container { position: relative; width: 180px; height: 180px; margin: 0 auto; }
        .avatar-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 4px solid var(--cor-surface-container-lowest); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .avatar-iniciais { width: 100%; height: 100%; border-radius: 50%; background: var(--cor-on-surface); color: var(--cor-surface-container-lowest); display: flex; align-items: center; justify-content: center; font-size: 64px; font-weight: 900; border: 4px solid var(--cor-surface-container-lowest); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .btn-upload {
            position: absolute; bottom: 5px; right: 5px; background: var(--cor-surface-container-lowest); color: var(--cor-on-surface); width: 44px; height: 44px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15); border: 2px solid var(--cor-surface-container-lowest); transition: all 0.3s ease;
        }
        .btn-upload:hover { transform: scale(1.1); }
        .btn-upload input[type="file"] { display: none; }

        /* Buttons */
        .btn-action { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 14px 30px -6px rgba(0,0,0,0.15); }
        .btn-action:active { transform: scale(0.97) translateY(0); }
    </style>
</head>

<body class="text-on-surface bg-background">
    <?php $paginaActual = 'perfil'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php $tituloPagina = 'Editar Perfil'; ob_start(); ?>
    <a href="index.php" class="px-5 py-2.5 bg-white border border-gray-200 text-on-surface rounded-full flex items-center gap-2 btn-action shadow-sm">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span class="text-xs font-bold">Voltar ao Perfil</span>
    </a>
    <?php $accoesPagina = ob_get_clean(); ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
        <div class="pb-24">
            
            <div class="mb-10 flex justify-between items-end glide-in">
                <div>
                    <h2 class="text-3xl font-headline font-extrabold text-on-surface tracking-tight">Definições da Conta</h2>
                    <p class="text-sm font-semibold text-on-surface-variant mt-1 max-w-xl">Mantenha as suas informações atualizadas e a sua conta segura.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- COLUNA ESQUERDA: FOTO E INFO BASE (Col 4) -->
                <div class="lg:col-span-4 glide-in stagger-1">
                    <div class="bg-white rounded-[2rem] p-8 border border-black/5 shadow-sm text-center flex flex-col items-center">
                        <form id="form-foto" action="<?= BASE_URL ?>app/controllers/perfil.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="acao" value="actualizar">
                            <input type="hidden" name="nome" value="<?= htmlspecialchars($dados['nome']) ?>">
                            <input type="hidden" name="telefone" value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>">

                            <div class="avatar-edit-container mb-6">
                                <?php if (!empty($dados['foto_path'])): ?>
                                    <img src="<?= BASE_URL . 'public/' . $dados['foto_path'] ?>" alt="Avatar" class="avatar-img">
                                <?php else: ?>
                                    <div class="avatar-iniciais">
                                        <?= strtoupper(substr($dados['nome'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>

                                <label class="btn-upload" title="Alterar Fotografia">
                                    <span class="material-symbols-outlined text-[20px]">add_a_photo</span>
                                    <input type="file" name="foto" id="foto-input" accept="image/jpeg, image/png, image/webp" onchange="document.getElementById('form-foto').submit();">
                                </label>
                            </div>
                        </form>

                        <h3 class="text-2xl font-headline font-black text-on-surface mb-1"><?= htmlspecialchars($dados['nome']) ?></h3>
                        <div class="px-3 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-widest bg-gray-100 text-gray-500 mb-3">
                            <?= htmlspecialchars($dados['perfil']) ?>
                        </div>

                        <?php if (!empty($dados['especialidade'])): ?>
                            <div class="flex items-center gap-2 text-sm font-bold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full mt-2">
                                <span class="material-symbols-outlined text-[16px]">medical_information</span>
                                <?= htmlspecialchars($dados['especialidade']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- COLUNA DIREITA: FORMULÁRIOS (Col 8) -->
                <div class="lg:col-span-8 flex flex-col gap-8 glide-in stagger-2">
                    
                    <!-- Bloco Dados Básicos -->
                    <div class="bg-white rounded-[2rem] p-8 border border-black/5 shadow-sm">
                        <h4 class="flex items-center gap-3 text-lg font-headline font-extrabold text-on-surface mb-8 border-b border-gray-100 pb-4">
                            <span class="material-symbols-outlined text-gray-400">person</span>
                            Informação Pessoal
                        </h4>

                        <?php if ($mensagem): ?>
                            <div class="mb-6 p-4 bg-green-50 rounded-2xl flex items-center gap-3 border border-green-100">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-white text-[16px]">check</span>
                                </div>
                                <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($mensagem) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($erro): ?>
                            <div class="mb-6 p-4 bg-red-50 rounded-2xl flex items-center gap-3 border border-red-100">
                                <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-white text-[16px]">error</span>
                                </div>
                                <p class="text-sm font-bold text-red-800"><?= htmlspecialchars($erro) ?></p>
                            </div>
                        <?php endif; ?>

                        <form action="<?= BASE_URL ?>app/controllers/perfil.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="acao" value="actualizar">

                            <div class="field-wrap">
                                <span class="material-symbols-outlined prefix-icon">badge</span>
                                <input type="text" name="nome" id="nome" class="fi" required placeholder=" " value="<?= htmlspecialchars($dados['nome']) ?>">
                                <label for="nome" class="fl">Nome Completo</label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                                <div class="field-wrap">
                                    <span class="material-symbols-outlined prefix-icon">alternate_email</span>
                                    <input type="text" id="username" class="fi" placeholder=" " value="<?= htmlspecialchars($dados['nome_utilizador']) ?>" readonly>
                                    <label for="username" class="fl">Nome de Utilizador</label>
                                </div>
                                
                                <div class="field-wrap">
                                    <span class="material-symbols-outlined prefix-icon">call</span>
                                    <input type="text" name="telefone" id="telefone" class="fi" placeholder=" " value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>">
                                    <label for="telefone" class="fl">Nº de Telefone</label>
                                </div>
                            </div>

                            <div class="flex justify-end mt-4">
                                <button type="submit" class="bg-primary text-white px-8 py-3.5 rounded-full font-extrabold text-sm flex items-center gap-2 btn-action shadow-lg shadow-black/10">
                                    <span class="material-symbols-outlined text-[18px]">save</span>
                                    Guardar Pessoais
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Bloco Alterar Senha -->
                    <div id="password" class="bg-white rounded-[2rem] p-8 border border-black/5 shadow-sm">
                        <h4 class="flex items-center gap-3 text-lg font-headline font-extrabold text-on-surface mb-8 border-b border-gray-100 pb-4">
                            <span class="material-symbols-outlined text-gray-400">lock</span>
                            Segurança & Palavra-passe
                        </h4>

                        <?php if ($mensagem_senha): ?>
                            <div class="mb-6 p-4 bg-green-50 rounded-2xl flex items-center gap-3 border border-green-100">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-white text-[16px]">check</span>
                                </div>
                                <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($mensagem_senha) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($erro_senha): ?>
                            <div class="mb-6 p-4 bg-red-50 rounded-2xl flex items-center gap-3 border border-red-100">
                                <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-white text-[16px]">error</span>
                                </div>
                                <p class="text-sm font-bold text-red-800"><?= htmlspecialchars($erro_senha) ?></p>
                            </div>
                        <?php endif; ?>

                        <form action="<?= BASE_URL ?>app/controllers/perfil.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="acao" value="senha">

                            <div class="field-wrap no-icon">
                                <input type="password" name="senha_antiga" id="senha_antiga" class="fi" required placeholder=" ">
                                <label for="senha_antiga" class="fl">Palavra-passe Actual</label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                                <div class="field-wrap no-icon">
                                    <input type="password" name="senha_nova" id="senha_nova" class="fi" minlength="6" required placeholder=" ">
                                    <label for="senha_nova" class="fl">Nova Palavra-passe (Min. 6 catactéres)</label>
                                </div>
                                <div class="field-wrap no-icon">
                                    <input type="password" name="senha_conf" id="senha_conf" class="fi" minlength="6" required placeholder=" ">
                                    <label for="senha_conf" class="fl">Confirmar Nova Palavra-passe</label>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-gray-50 border border-gray-100 rounded-2xl">
                                <p class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest leading-relaxed flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[16px] text-gray-400">info</span>
                                    Sessão será mantida ao alterar a senha. Use a nova no próximo login.
                                </p>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" class="bg-white border-2 border-red-500 text-red-500 hover:bg-red-500 hover:text-white transition-colors px-8 py-3.5 rounded-full font-extrabold text-sm flex items-center gap-2 btn-action">
                                    <span class="material-symbols-outlined text-[18px]">key</span>
                                    Atualizar Palavra-passe
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>