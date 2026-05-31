<?php
// ================================================
// Hospital Geral do Bengo
// Criar Novo Utilizador — Admin (Tactile Editorial)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Estatistica.php';

exigirPerfil(['admin']);

$especialidades = Estatistica::listarEspecialidades();
$consultorios = Estatistica::listarConsultorios();

$erro = $_SESSION['erro'] ?? '';
$form = $_SESSION['form_data'] ?? [];
$criado = $_SESSION['utilizador_criado'] ?? null;
unset($_SESSION['erro'], $_SESSION['form_data'], $_SESSION['utilizador_criado']);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Utilizador — <?= APP_NOME ?></title>
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

        /* ─── Avatar Upload ─── */
        .avatar-upload {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--cor-input-bg);
            border: 2px dashed var(--cor-outline-variant);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            flex-shrink: 0;
        }
        .avatar-upload:hover {
            border-color: var(--cor-on-surface);
            background: var(--cor-input-hover);
        }
        .avatar-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0; left: 0;
            display: none;
        }
        .avatar-upload.has-image .avatar-preview { display: block; }
        .avatar-upload.has-image .avatar-icon { display: none; }

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
    <div class="px-4 py-2 bg-white rounded-full flex items-center gap-2 border border-primary/5 shadow-sm">
        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">person_add</span>
        <span class="text-xs font-bold text-on-surface">Novo Registo</span>
    </div>
    <?php $accoesPagina = ob_get_clean(); ?>

    <?php include __DIR__ . '/../comum/header.php'; ?>

    <main class="ml-64 pt-24 h-screen overflow-y-auto custom-scrollbar">
        <div class="p-8 max-w-[1400px] mx-auto min-h-full pb-24">
            
            <?php if ($criado): ?>
            <!-- ═══════════ SUCCESS CARD ═══════════ -->
            <div class="flex flex-col items-center justify-center min-h-[60vh] glide-in">
                <!-- Success Banner -->
                <div class="w-full max-w-lg mb-8 p-4 bg-green-50 rounded-2xl flex items-center gap-3 border border-green-100">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-white text-[16px]">check</span>
                    </div>
                    <p class="text-sm font-bold text-green-800">Utilizador "<?= htmlspecialchars($criado['nome']) ?>" criado com sucesso.</p>
                </div>

                <!-- User Card -->
                <div class="bg-white rounded-[2rem] shadow-lg border border-surface-container-high/50 w-full max-w-sm overflow-hidden">
                    <!-- Card Top -->
                    <div class="p-8 pb-6 flex flex-col items-center text-center">
                        <!-- Avatar -->
                        <?php if (!empty($criado['foto_path'])): ?>
                            <div class="w-20 h-20 rounded-full bg-primary flex items-center justify-center mb-4 shadow-md overflow-hidden ring-4 ring-primary/10">
                                <img src="<?= BASE_URL ?>public/<?= htmlspecialchars($criado['foto_path']) ?>" alt="Foto" class="w-full h-full object-cover">
                            </div>
                        <?php else: ?>
                            <div class="w-20 h-20 rounded-full bg-primary flex items-center justify-center mb-4 shadow-md ring-4 ring-primary/10">
                                <span class="text-white text-3xl font-extrabold"><?= mb_strtoupper(mb_substr($criado['nome'], 0, 1)) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Name & Role -->
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-xl font-headline font-extrabold text-on-surface tracking-tight"><?= htmlspecialchars($criado['nome']) ?></h3>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-bold text-on-surface-variant capitalize"><?= htmlspecialchars(ucfirst($criado['perfil'])) ?></span>
                            <?php if (!empty($criado['especialidade'])): ?>
                                <span class="text-on-surface-variant/40">·</span>
                                <span class="text-sm font-bold text-on-surface-variant"><?= htmlspecialchars($criado['especialidade']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Status dot -->
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                            <span class="text-xs font-bold text-green-600">Activo</span>
                        </div>

                        <!-- Tags -->
                        <div class="flex flex-wrap justify-center gap-2 mt-5">
                            <span class="px-3 py-1.5 bg-surface-container-low rounded-full text-xs font-extrabold text-on-surface"><?= htmlspecialchars(ucfirst($criado['perfil'])) ?></span>
                            <?php if (!empty($criado['especialidade'])): ?>
                                <span class="px-3 py-1.5 bg-surface-container-low rounded-full text-xs font-extrabold text-on-surface"><?= htmlspecialchars($criado['especialidade']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($criado['consultorio'])): ?>
                                <span class="px-3 py-1.5 bg-surface-container-low rounded-full text-xs font-extrabold text-on-surface"><?= htmlspecialchars($criado['consultorio']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-surface-container-high/50 mx-6"></div>

                    <!-- Card Metadata -->
                    <div class="px-8 py-5 grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-xs font-extrabold text-on-surface-variant tracking-wider uppercase">Utilizador</p>
                            <p class="text-sm font-bold text-on-surface mt-1">@<?= htmlspecialchars($criado['nome_utilizador']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-extrabold text-on-surface-variant tracking-wider uppercase">Estado</p>
                            <p class="text-sm font-bold text-on-surface mt-1">Activo</p>
                        </div>
                        <div>
                            <p class="text-xs font-extrabold text-on-surface-variant tracking-wider uppercase">Registado</p>
                            <p class="text-sm font-bold text-on-surface mt-1"><?= $criado['data'] ?></p>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-surface-container-high/50 mx-6"></div>

                    <!-- Card Actions -->
                    <div class="px-6 py-5 flex items-center gap-3">
                        <a href="utilizadores.php" class="flex-1 bg-primary text-white py-3.5 rounded-xl font-extrabold text-sm text-center flex items-center justify-center gap-2 hover:brightness-110 transition-all shadow-md">
                            <span class="material-symbols-outlined text-[18px]">bar_chart</span>
                            Detalhes Rápidos
                        </a>
                        <a href="criar_utilizador.php" class="w-11 h-11 bg-surface-container-low rounded-xl flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-all shrink-0">
                            <span class="material-symbols-outlined text-[20px]">person_add</span>
                        </a>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- ═══════════ FORM ═══════════ -->

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
                    <a href="utilizadores.php" class="text-sm font-bold text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1 mb-2">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                        Voltar à Gestão
                    </a>
                    <h2 class="text-3xl font-headline font-extrabold text-on-surface tracking-tight">Criar Identidade</h2>
                </div>
            </div>

            <!-- Split Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 glide-in stagger-1">
                
                <!-- Left: Info Panel -->
                <div class="lg:col-span-4 flex flex-col gap-6">
                    <div class="bg-primary text-white rounded-[2rem] p-8 shadow-lg relative overflow-hidden sticky top-8">
                        <div class="absolute -right-10 -bottom-10 opacity-[0.07]">
                            <span class="material-symbols-outlined text-[160px]">health_and_safety</span>
                        </div>
                        <div class="w-12 h-12 bg-white/15 rounded-2xl flex items-center justify-center mb-6 backdrop-blur-sm">
                            <span class="material-symbols-outlined text-white text-xl">shield_person</span>
                        </div>
                        <h3 class="font-headline font-extrabold text-2xl mb-3 tracking-tight">Acesso Seguro</h3>
                        <p class="text-sm text-white/70 font-medium leading-relaxed mb-8">
                            Adicione novos profissionais com permissões estritas. Todos os acessos são auditados para garantir a privacidade dos pacientes.
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
                        <form method="POST" id="form-criar" action="<?= BASE_URL ?>app/controllers/estatisticas.php" autocomplete="off" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="acao" value="criar_utilizador">
                            
                            <!-- Section: Identity -->
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-9 h-9 bg-gray-100 rounded-xl flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px] text-on-surface">badge</span>
                                </div>
                                <h4 class="font-extrabold text-lg text-on-surface tracking-tight">Dados do Colaborador</h4>
                            </div>

                            <!-- Foto Upload -->
                            <div class="flex items-center gap-5 mb-8">
                                <label for="foto" class="avatar-upload group" id="avatar-container">
                                    <span class="material-symbols-outlined text-[var(--cor-input-placeholder)] text-2xl avatar-icon group-hover:text-primary transition-colors">add_a_photo</span>
                                    <img id="avatar-preview-img" src="" alt="Foto" class="avatar-preview">
                                </label>
                                <input type="file" id="foto" name="foto" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                                <div>
                                    <h5 class="text-sm font-extrabold text-on-surface">Foto de Perfil</h5>
                                    <p class="text-xs font-bold text-on-surface-variant mt-1">Carregue uma imagem nítida (JPG ou PNG).</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Nome Completo -->
                                <div class="field-wrap">
                                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($form['nome'] ?? '') ?>" 
                                        required minlength="3" placeholder=" " class="fi">
                                    <span class="material-symbols-outlined field-icon">person</span>
                                    <label for="nome" class="fl">Nome Completo *</label>
                                </div>

                                <!-- Nome de Utilizador -->
                                <div>
                                    <div class="field-wrap">
                                        <input type="text" id="nome_utilizador" name="nome_utilizador" value="<?= htmlspecialchars($form['nome_utilizador'] ?? '') ?>" 
                                            required minlength="3" placeholder=" " pattern="[a-zA-Z0-9_\.]+" class="fi">
                                        <span class="material-symbols-outlined field-icon">alternate_email</span>
                                        <label for="nome_utilizador" class="fl">Nome de Utilizador *</label>
                                    </div>
                                    <p class="field-hint">Apenas letras, números, _ e ponto.</p>
                                </div>

                                <!-- Senha -->
                                <div class="field-wrap">
                                    <input type="password" id="senha" name="senha" required minlength="6" placeholder=" " class="fi">
                                    <span class="material-symbols-outlined field-icon">lock</span>
                                    <label for="senha" class="fl">Senha de Acesso *</label>
                                </div>

                                <!-- Telefone -->
                                <div class="field-wrap">
                                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($form['telefone'] ?? '') ?>" 
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
                                $sel_value = $form['perfil'] ?? '';
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
                                    $sel_value = (string)($form['especialidade_id'] ?? '0');
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
                                    $sel_value = (string)($form['consultorio_id'] ?? '0');
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
                                <a href="utilizadores.php" class="font-bold text-sm text-on-surface-variant hover:text-primary transition-colors px-6 py-3 rounded-xl hover:bg-gray-50">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-primary text-white px-9 py-4 rounded-xl font-bold text-sm flex items-center gap-2.5 btn-action shadow-lg shadow-black/10">
                                    <span class="material-symbols-outlined text-[20px]">person_add</span>
                                    Criar Utilizador
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // ─── Toggle Médico fields ───
        const perfil = document.getElementById('perfil-native');
        if (perfil) {
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
                    }
                }
                // Sync floating labels for all selects
                document.querySelectorAll('select[id$="-native"]').forEach(sel => {
                    if(typeof syncFloatingLabel === 'function') syncFloatingLabel(sel);
                });
            }

            perfil.addEventListener('change', toggleCamposMedico);
            
            // ─── Foto Preview ───
            window.previewAvatar = function(input) {
                const container = document.getElementById('avatar-container');
                const img = document.getElementById('avatar-preview-img');
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                        container.classList.add('has-image');
                    }
                    reader.readAsDataURL(input.files[0]);
                } else {
                    img.src = '';
                    container.classList.remove('has-image');
                }
            }

            // Init on load
            toggleCamposMedico();
        }
    </script>
</body>
</html>