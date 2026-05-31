<?php
// ================================================
// Hospital Geral do Bengo
// Página de Login — Ponto de entrada do sistema (Tactile Editorial)
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
    <?php include __DIR__ . '/../app/views/comum/head_assets.php'; ?>
    <style>
        /* ─── Entrance Animations ─── */
        @keyframes glideIn {
            0% { opacity: 0; transform: translateY(30px) scale(0.97); filter: blur(6px); }
            100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        .glide-in { animation: glideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .fade-in { animation: fadeIn 1s ease forwards; opacity: 0; }
        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }

        /* ─── Floating Label Field ─── */
        .field-wrap {
            position: relative;
            width: 100%;
            height: 3.8rem;
            margin-bottom: 1.5rem;
        }
        .field-wrap .field-icon {
            position: absolute;
            left: 1.15rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            color: #a1a1aa;
            pointer-events: none;
            transition: color 0.3s ease;
            z-index: 2;
        }
        .field-wrap .fi {
            width: 100%;
            height: 100%;
            background: #f4f5f7;
            border: 2px solid transparent;
            border-radius: 1.25rem;
            padding: 1.6rem 1.25rem 0.5rem 3.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #111;
            font-family: 'Manrope', sans-serif;
            outline: none;
            transition: all 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
            -webkit-appearance: none;
            appearance: none;
            line-height: 1.2;
        }
        .field-wrap .fi::placeholder { color: transparent; }
        .field-wrap .fi:focus {
            background: #fff;
            border-color: var(--cor-primary);
            box-shadow: 0 6px 24px -4px rgba(0,0,0,0.06);
        }
        .field-wrap .fi:focus ~ .field-icon { color: var(--cor-primary); }

        .field-wrap .fl {
            position: absolute;
            left: 3.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.9rem;
            font-weight: 600;
            color: #71717a;
            pointer-events: none;
            transition: all 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
            transform-origin: left center;
            z-index: 2;
        }
        .field-wrap .fi:focus ~ .fl,
        .field-wrap .fi:not(:placeholder-shown) ~ .fl {
            top: 0.85rem;
            transform: translateY(-50%) scale(0.75);
            font-weight: 800;
            color: var(--cor-primary);
            letter-spacing: 0.04em;
        }

        /* ─── Buttons ─── */
        .btn-action { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 14px 30px -6px rgba(0,0,0,0.15); }
        .btn-action:active { transform: scale(0.97) translateY(0); }

        /* ─── Background Gradient ─── */
        .editorial-gradient {
            background: radial-gradient(circle at top right, #eef2ff, #f8fafc),
                        linear-gradient(to bottom right, #f8fafc, #f1f5f9);
        }
    </style>
</head>

<body class="editorial-gradient text-on-surface h-screen overflow-hidden flex font-sans">

    <!-- Esquerda: Informação & Branding -->
    <div class="hidden lg:flex lg:w-1/2 h-full bg-primary relative flex-col justify-between p-12 overflow-hidden fade-in">
        <!-- Fundo Decorativo -->
        <div class="absolute -right-20 -top-20 opacity-20 pointer-events-none">
            <span class="material-symbols-outlined text-[400px] text-white">health_and_safety</span>
        </div>
        
        <div class="relative z-10 glide-in stagger-1">
            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-md mb-6 border border-white/20">
                <span class="material-symbols-outlined text-white text-3xl font-light">vital_signs</span>
            </div>
            <h1 class="text-white text-5xl font-headline font-extrabold tracking-tight mb-4">
                <?= APP_NOME ?>
            </h1>
            <p class="text-white/70 text-lg font-medium max-w-md leading-relaxed">
                Sistema Integrado de Gestão de Filas Hospitalares.<br>
                Tecnologia de ponta para um atendimento mais humano e eficiente.
            </p>
        </div>

        <div class="relative z-10 glide-in stagger-2">
            <div class="flex items-center gap-3">
                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse shadow-[0_0_10px_rgba(74,222,128,0.5)]"></span>
                <span class="text-white/60 text-sm font-bold tracking-widest uppercase">Sistemas Operacionais</span>
            </div>
            <p class="text-white/40 text-xs mt-3">Versão <?= APP_VERSAO ?></p>
        </div>
    </div>

    <!-- Direita: Login Form -->
    <div class="w-full lg:w-1/2 h-full flex flex-col items-center justify-center p-6 sm:p-12 relative overflow-y-auto">
        <div class="w-full max-w-[420px] glide-in stagger-1">
            
            <div class="mb-10 text-center lg:text-left">
                <!-- Mobile Logo -->
                <div class="lg:hidden w-14 h-14 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-white text-2xl font-light">vital_signs</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-headline font-extrabold text-on-surface tracking-tight mb-2">Bem-vindo(a)</h2>
                <p class="text-on-surface-variant text-sm font-medium">
                    Introduza as suas credenciais para aceder ao sistema.
                </p>
            </div>

            <?php if ($erroLogin): ?>
                <div class="mb-8 p-4 bg-red-50 rounded-2xl flex items-center gap-3 border border-red-100 glide-in">
                    <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center shrink-0 shadow-lg shadow-red-500/20">
                        <span class="material-symbols-outlined text-white text-[20px]">error</span>
                    </div>
                    <p class="text-sm font-bold text-red-800 leading-tight"><?= htmlspecialchars($erroLogin) ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] p-8 md:p-10 shadow-2xl shadow-black/[0.04] border border-white/60 glide-in stagger-2">
                <form method="POST" action="<?= BASE_URL ?>app/controllers/auth.php" id="form-login">
                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                    <input type="hidden" name="acao" value="login">

                    <!-- Username -->
                    <div class="field-wrap">
                        <input type="text" id="nome_utilizador" name="nome_utilizador" 
                               placeholder=" " autocomplete="username" required autofocus class="fi">
                        <span class="material-symbols-outlined field-icon">person</span>
                        <label for="nome_utilizador" class="fl">Nome de Utilizador</label>
                    </div>

                    <!-- Senha -->
                    <div class="field-wrap">
                        <input type="password" id="senha" name="senha" 
                               placeholder=" " autocomplete="current-password" required class="fi">
                        <span class="material-symbols-outlined field-icon">lock</span>
                        <label for="senha" class="fl">Senha</label>
                    </div>

                    <!-- Info Box (Perfil Detectado) -->
                    <div class="mt-4 mb-8 bg-gray-50/50 rounded-xl p-4 flex gap-3 border border-gray-100/80 items-start">
                        <span class="material-symbols-outlined text-blue-500 text-[18px] mt-0.5" style="font-variation-settings: 'FILL' 1;">info</span>
                        <div>
                            <span class="block text-[11px] font-extrabold text-on-surface uppercase tracking-wider mb-1">Acesso Inteligente</span>
                            <span class="text-[13px] text-on-surface-variant font-medium leading-snug block">
                                O seu nível de acesso será detetado automaticamente.
                            </span>
                        </div>
                    </div>

                    <button type="submit" id="btn-entrar" class="w-full bg-primary text-white px-9 py-4 rounded-xl font-extrabold text-sm flex items-center justify-center gap-2.5 btn-action shadow-lg shadow-primary/20">
                        Aceder ao Painel
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </button>
                    
                </form>
            </div>

            <!-- Footer (Mobile empty space / Copyright) -->
            <div class="mt-12 text-center text-xs text-on-surface-variant/70 font-bold glide-in stagger-3">
                &copy; <?= date('Y') ?> <?= APP_NOME ?>. Todos os direitos reservados.
            </div>

        </div>
    </div>

    <!-- Loading state script -->
    <script>
        document.getElementById('form-login').addEventListener('submit', function(e) {
            if (this.checkValidity()) {
                const btn = document.getElementById('btn-entrar');
                btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span> A Processar...';
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none';
            }
        });
    </script>
</body>
</html>