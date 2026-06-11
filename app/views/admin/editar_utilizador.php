<?php
// ================================================
// Hospital Geral do Bengo
// Editar Utilizador — Admin (Tactile Editorial)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Estatistica.php';

exigirPerfil(['admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . 'app/views/admin/utilizadores.php');
    exit;
}

$u = Estatistica::obterUtilizador($id);
if (!$u) {
    $_SESSION['erro'] = 'Utilizador não encontrado.';
    header('Location: ' . BASE_URL . 'app/views/admin/utilizadores.php');
    exit;
}

$especialidades = Estatistica::listarEspecialidades();
$consultorios = Estatistica::listarConsultorios();

$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Utilizador — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--cor-scrollbar-light); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--cor-scrollbar-light-hover); }

        /* ─── Entrance Animations ─── */
        @keyframes glideIn {
            0% { opacity: 0; transform: translateY(30px) scale(0.97); filter: blur(6px); }
            100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }
        .glide-in { animation: glideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .stagger-1 { animation-delay: 0.08s; }
        .stagger-2 { animation-delay: 0.16s; }

        /* ─── Floating Label Field ─── */
        .field-wrap {
            position: relative;
            width: 100%;
            height: 3.8rem;
        }
        .field-wrap .field-icon {
            position: absolute;
            left: 1.15rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            color: var(--cor-input-placeholder);
            pointer-events: none;
            transition: color 0.3s ease;
            z-index: 2;
        }
        .field-wrap .fi {
            width: 100%;
            height: 100%;
            background: var(--cor-input-bg);
            border: 2px solid transparent;
            border-radius: 0.75rem;
            padding: 1.6rem 1.25rem 0.5rem 3.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--cor-on-surface);
            font-family: 'Manrope', sans-serif;
            outline: none;
            transition: all 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
            -webkit-appearance: none;
            appearance: none;
            line-height: 1.2;
        }
        .field-wrap .fi::placeholder { color: transparent; }
        .field-wrap .fi:focus {
            background: var(--cor-surface-container-lowest);
            border-color: var(--cor-on-surface);
            box-shadow: 0 6px 24px -4px rgba(0,0,0,0.06);
        }
        .field-wrap .fi:focus ~ .field-icon { color: var(--cor-on-surface); }

        /* Floating label */
        .field-wrap .fl {
            position: absolute;
            left: 3.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--cor-input-label);
            pointer-events: none;
            transition: all 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
            transform-origin: left center;
            z-index: 2;
        }
        .field-wrap .fi:focus ~ .fl,
        .field-wrap .fi:not(:placeholder-shown) ~ .fl,
        .field-wrap .fi.has-value ~ .fl {
            top: 0.85rem;
            transform: translateY(-50%) scale(0.75);
            font-weight: 800;
            color: var(--cor-on-surface);
            letter-spacing: 0.04em;
        }

        /* Select arrow */
        .field-wrap .select-arrow {
            position: absolute;
            right: 1.15rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            color: var(--cor-input-placeholder);
            pointer-events: none;
            transition: all 0.3s ease;
        }
        .field-wrap .fi:focus ~ .select-arrow { color: var(--cor-on-surface); transform: translateY(-50%) rotate(180deg); }

        /* Hint text below field */
        .field-hint {
            margin-top: 0.4rem;
            margin-left: 1rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--cor-input-placeholder);
            letter-spacing: 0.02em;
        }

        /* ─── Buttons ─── */
        .btn-action { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 14px 30px -6px rgba(0,0,0,0.15); }
        .btn-action:active { transform: scale(0.97) translateY(0); }

        /* ─── Conditional fields ─── */
        .campo-medico {
            display: none; max-height: 0; opacity: 0;
            overflow: hidden;
            transition: max-height 0.5s cubic-bezier(0.16, 1, 0.3, 1),
                        opacity 0.4s ease 0.1s;
        }
        .campo-medico.visivel {
            display: block; max-height: 200px; opacity: 1; overflow: visible;
        }

        /* ─── Section divider ─── */
        .section-divider {
            display: flex; align-items: center; gap: 1rem;
            margin: 2.5rem 0 2rem;
        }
        .section-divider::before,
        .section-divider::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,0,0,0.06), transparent);
        }
        .section-divider span {
            font-size: 0.7rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.12em;
            color: var(--cor-input-placeholder); white-space: nowrap;
        }
    </style>
</head>

<body class="text-on-surface h-screen overflow-hidden bg-background">
    <?php $paginaActual = 'utilizadores'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>
    
    <?php
    $tituloPagina = 'Utilizadores';
    ob_start(); ?>
    <a href="<?= BASE_URL ?>app/views/admin/ver_utilizador.php?id=<?= $u['id'] ?>" class="px-4 py-2 bg-white rounded-full flex items-center gap-2 border border-primary/5 shadow-sm hover:bg-gray-50 transition-colors">
        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">arrow_back</span>
        <span class="text-xs font-bold text-on-surface">Voltar ao Perfil</span>
    </a>
    <?php $accoesPagina = ob_get_clean(); ?>

    <?php include __DIR__ . '/../comum/header.php'; ?>

    <main class="ml-64 pt-24 h-screen overflow-y-auto custom-scrollbar">
        <div class="p-8 max-w-[1400px] mx-auto min-h-full pb-24">
            
            <?php if ($erro): ?>
                <div class="mb-6 p-4 bg-red-50 rounded-2xl flex items-center gap-3 border border-red-100 glide-in">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-white text-[16px]">error</span>
                    </div>
                    <p class="text-sm font-bold text-red-800"><?= htmlspecialchars($erro) ?></p>
                </div>
            <?php endif; ?>

            <!-- Page Title -->
            <div class="mb-10 flex justify-between items-end glide-in">
                <div>
                    <h2 class="text-3xl font-headline font-extrabold text-on-surface tracking-tight">Editar Conta</h2>
                    <p class="text-sm text-on-surface-variant font-medium mt-1">Modifique as credenciais, dados pessoais ou nível de acesso.</p>
                </div>
            </div>

            <!-- Split Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 glide-in stagger-1">
                
                <!-- Left: Info Panel -->
                <div class="lg:col-span-4 flex flex-col gap-6">
                    <div class="bg-primary text-white rounded-[2rem] p-8 shadow-lg relative overflow-hidden sticky top-8">
                        <div class="absolute -right-10 -bottom-10 opacity-[0.07]">
                            <span class="material-symbols-outlined text-[160px]">manage_accounts</span>
                        </div>
                        <div class="w-12 h-12 bg-white/15 rounded-2xl flex items-center justify-center mb-6 backdrop-blur-sm">
                            <span class="material-symbols-outlined text-white text-xl">shield_person</span>
                        </div>
                        <h3 class="font-headline font-extrabold text-2xl mb-3 tracking-tight">Actualização Segura</h3>
                        <p class="text-sm text-white/70 font-medium leading-relaxed mb-8">
                            Modifique as permissões de acesso com cuidado. Qualquer mudança de nível de acesso entra em vigor imediatamente para a segurança dos dados hospitalares.
                        </p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3 text-sm font-semibold">
                                <span class="material-symbols-outlined text-[18px] text-white/60" style="font-variation-settings: 'FILL' 1;">admin_panel_settings</span> Administrador
                            </div>
                            <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3 text-sm font-semibold">
                                <span class="material-symbols-outlined text-[18px] text-white/60" style="font-variation-settings: 'FILL' 1;">stethoscope</span> Médico
                            </div>
                            <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3 text-sm font-semibold">
                                <span class="material-symbols-outlined text-[18px] text-white/60" style="font-variation-settings: 'FILL' 1;">concierge</span> Recepção
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Form -->
                <div class="lg:col-span-8">
                    <div class="bg-white rounded-[2rem] p-8 md:p-10 border border-white/50 shadow-sm glide-in stagger-2">
                        <form method="POST" id="form-editar" action="<?= BASE_URL ?>app/controllers/estatisticas.php" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="acao" value="editar_utilizador">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            
                            <!-- Section: Identity -->
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-9 h-9 bg-gray-100 rounded-xl flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px] text-on-surface">badge</span>
                                </div>
                                <h4 class="font-extrabold text-lg text-on-surface tracking-tight">Dados do Colaborador</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Nome Completo -->
                                <div class="field-wrap">
                                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($u['nome']) ?>" 
                                        required minlength="3" placeholder=" " class="fi">
                                    <span class="material-symbols-outlined field-icon">person</span>
                                    <label for="nome" class="fl">Nome Completo *</label>
                                </div>

                                <!-- Sexo -->
                                <?php
                                $sel_id = 'sexo';
                                $sel_name = 'sexo';
                                $sel_icon = 'wc';
                                $sel_label = 'Sexo *';
                                $sel_value = $u['sexo'] ?? 'M';
                                $sel_required = true;
                                $sel_options = [
                                    'M' => ['label' => 'Masculino (M)'],
                                    'F' => ['label' => 'Feminino (F)']
                                ];
                                include __DIR__ . '/../comum/custom_select_floating.php';
                                ?>

                                <!-- Nome de Utilizador -->
                                <div>
                                    <div class="field-wrap">
                                        <input type="text" id="nome_utilizador" name="nome_utilizador" value="<?= htmlspecialchars($u['nome_utilizador']) ?>" 
                                            required minlength="3" placeholder=" " pattern="[a-zA-Z0-9_\.]+" class="fi">
                                        <span class="material-symbols-outlined field-icon">alternate_email</span>
                                        <label for="nome_utilizador" class="fl">Nome de Utilizador *</label>
                                    </div>
                                    <p class="field-hint">Apenas letras, números, _ e ponto.</p>
                                </div>

                                <!-- Senha -->
                                <div>
                                    <div class="field-wrap">
                                        <input type="password" id="senha" name="senha" minlength="6" placeholder=" " class="fi">
                                        <span class="material-symbols-outlined field-icon">lock</span>
                                        <label for="senha" class="fl">Nova Senha</label>
                                    </div>
                                    <p class="field-hint">Deixe em branco para manter a actual.</p>
                                </div>

                                <!-- Telefone -->
                                <div class="field-wrap">
                                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($u['telefone'] ?? '') ?>" 
                                        placeholder=" " class="fi">
                                    <span class="material-symbols-outlined field-icon">call</span>
                                    <label for="telefone" class="fl">Telefone de Contacto</label>
                                </div>
                            </div>

                            <!-- Section Divider -->
                            <div class="section-divider">
                                <span>Permissões</span>
                            </div>

                            <!-- Section: Permissions -->
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-9 h-9 bg-gray-100 rounded-xl flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px] text-on-surface">tune</span>
                                </div>
                                <h4 class="font-extrabold text-lg text-on-surface tracking-tight">Perfil e Especialidade</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Perfil de Acesso -->
                                <?php
                                $sel_id = 'perfil';
                                $sel_name = 'perfil';
                                $sel_icon = 'security';
                                $sel_label = 'Perfil de Acesso *';
                                $sel_value = $u['perfil'];
                                $sel_required = true;
                                $sel_options = [
                                    '' => ['label' => ' '],
                                    'recepcionista' => ['label' => 'Recepcionista'],
                                    'medico' => ['label' => 'Médico'],
                                    'admin' => ['label' => 'Administrador']
                                ];
                                include __DIR__ . '/../comum/custom_select_floating.php';
                                ?>

                                <!-- Especialidade (condicional) -->
                                <div class="campo-medico" id="campo-especialidade">
                                    <?php
                                    $sel_id = 'especialidade_id';
                                    $sel_name = 'especialidade_id';
                                    $sel_icon = 'medical_information';
                                    $sel_label = 'Especialidade *';
                                    $sel_value = (string)($u['especialidade_id'] ?? '0');
                                    $sel_options = ['0' => ['label' => ' ']];
                                    foreach ($especialidades as $e) {
                                        $sel_options[(string)$e['id']] = ['label' => htmlspecialchars($e['nome'])];
                                    }
                                    include __DIR__ . '/../comum/custom_select_floating.php';
                                    ?>
                                </div>

                                <!-- Consultório (condicional) -->
                                <div class="campo-medico" id="campo-consultorio">
                                    <?php
                                    $sel_id = 'consultorio_id';
                                    $sel_name = 'consultorio_id';
                                    $sel_icon = 'meeting_room';
                                    $sel_label = 'Consultório';
                                    $sel_value = (string)($u['consultorio_id'] ?? '0');
                                    $sel_options = ['0' => ['label' => ' ']];
                                    foreach ($consultorios as $c) {
                                        $sel_options[(string)$c['id']] = ['label' => htmlspecialchars($c['nome'])];
                                    }
                                    include __DIR__ . '/../comum/custom_select_floating.php';
                                    ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-4 mt-12 pt-6 border-t border-primary/5">
                                <a href="<?= BASE_URL ?>app/views/admin/ver_utilizador.php?id=<?= $u['id'] ?>" class="font-bold text-sm text-on-surface-variant hover:text-primary transition-colors px-6 py-3 rounded-xl hover:bg-gray-50">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-primary text-white px-9 py-4 rounded-xl font-bold text-sm flex items-center gap-2.5 btn-action shadow-lg shadow-black/10">
                                    <span class="material-symbols-outlined text-[20px]">save</span>
                                    Guardar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        // ─── Toggle Médico fields ───
        const perfil = document.getElementById('perfil-native');
        const camposMedico = document.querySelectorAll('.campo-medico');

        function toggleCamposMedico() {
            const show = perfil.value === 'medico';
            camposMedico.forEach(c => {
                c.classList.toggle('visivel', show);
            });
            if (!show) {
                if(typeof CustomSelect !== 'undefined') {
                    CustomSelect.select('especialidade_id', '0', ' ', '', '');
                    CustomSelect.select('consultorio_id', '0', ' ', '', '');
                } else {
                    document.getElementById('especialidade_id-native').value = '0';
                    document.getElementById('consultorio_id-native').value = '0';
                }
            }
            // Sync floating labels for all selects
            document.querySelectorAll('select[id$="-native"]').forEach(sel => {
                if(typeof syncFloatingLabel === 'function') syncFloatingLabel(sel);
            });
        }

        perfil.addEventListener('change', toggleCamposMedico);
        
        // Init on load
        toggleCamposMedico();
        // Allow a small delay for custom select components to initialize before syncing labels
        setTimeout(() => {
            document.querySelectorAll('select[id$="-native"]').forEach(sel => {
                if(typeof syncFloatingLabel === 'function') syncFloatingLabel(sel);
            });
        }, 100);
    </script>
</body>
</html>