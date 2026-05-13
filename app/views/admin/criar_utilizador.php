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
unset($_SESSION['erro'], $_SESSION['form_data']);
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
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }

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
            border-color: #111;
            box-shadow: 0 6px 24px -4px rgba(0,0,0,0.06);
        }
        .field-wrap .fi:focus ~ .field-icon { color: #111; }

        /* Floating label */
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
        .field-wrap .fi:not(:placeholder-shown) ~ .fl,
        .field-wrap .fi.has-value ~ .fl {
            top: 0.85rem;
            transform: translateY(-50%) scale(0.75);
            font-weight: 800;
            color: #111;
            letter-spacing: 0.04em;
        }

        /* Select arrow */
        .field-wrap .select-arrow {
            position: absolute;
            right: 1.15rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            color: #a1a1aa;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        .field-wrap .fi:focus ~ .select-arrow { color: #111; transform: translateY(-50%) rotate(180deg); }

        /* Hint text below field */
        .field-hint {
            margin-top: 0.4rem;
            margin-left: 1rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: #a1a1aa;
            letter-spacing: 0.02em;
        }

        /* ─── Avatar Upload ─── */
        .avatar-upload {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f4f5f7;
            border: 2px dashed #d4d4d8;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            flex-shrink: 0;
        }
        .avatar-upload:hover {
            border-color: #111;
            background: #f8fafc;
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
            display: block; max-height: 200px; opacity: 1;
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
            color: #a1a1aa; white-space: nowrap;
        }
    </style>
</head>

<body class="text-on-surface h-screen overflow-hidden bg-[#f3f4f6]">
    <?php $paginaActual = 'utilizadores'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>
    
    <?php
    $tituloPagina = 'Utilizadores';
    ob_start(); ?>
    <div class="px-4 py-2 bg-white rounded-full flex items-center gap-2 border border-black/5 shadow-sm">
        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">person_add</span>
        <span class="text-xs font-bold text-black">Novo Registo</span>
    </div>
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
                    <a href="utilizadores.php" class="text-sm font-bold text-on-surface-variant hover:text-black transition-colors flex items-center gap-1 mb-2">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                        Voltar à Gestão
                    </a>
                    <h2 class="text-3xl font-headline font-extrabold text-black tracking-tight">Criar Identidade</h2>
                </div>
            </div>

            <!-- Split Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 glide-in stagger-1">
                
                <!-- Left: Info Panel -->
                <div class="lg:col-span-4 flex flex-col gap-6">
                    <div class="bg-black text-white rounded-[2rem] p-8 shadow-lg relative overflow-hidden sticky top-8">
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
                                    <span class="material-symbols-outlined text-[18px] text-black">badge</span>
                                </div>
                                <h4 class="font-extrabold text-lg text-black tracking-tight">Dados do Colaborador</h4>
                            </div>

                            <!-- Foto Upload -->
                            <div class="flex items-center gap-5 mb-8">
                                <label for="foto" class="avatar-upload group" id="avatar-container">
                                    <span class="material-symbols-outlined text-[#a1a1aa] text-2xl avatar-icon group-hover:text-black transition-colors">add_a_photo</span>
                                    <img id="avatar-preview-img" src="" alt="Foto" class="avatar-preview">
                                </label>
                                <input type="file" id="foto" name="foto" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                                <div>
                                    <h5 class="text-sm font-extrabold text-black">Foto de Perfil</h5>
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
                                    <span class="material-symbols-outlined text-[18px] text-black">tune</span>
                                </div>
                                <h4 class="font-extrabold text-lg text-black tracking-tight">Perfil e Especialidade</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Perfil de Acesso -->
                                <div class="field-wrap">
                                    <select id="perfil" name="perfil" required class="fi cursor-pointer" style="-webkit-appearance:none;appearance:none;">
                                        <option value="" disabled <?= empty($form['perfil'] ?? '') ? 'selected' : '' ?>>  </option>
                                        <option value="recepcionista" <?= ($form['perfil'] ?? '') === 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                                        <option value="medico" <?= ($form['perfil'] ?? '') === 'medico' ? 'selected' : '' ?>>Médico</option>
                                        <option value="admin" <?= ($form['perfil'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                                    <span class="material-symbols-outlined field-icon">security</span>
                                    <label for="perfil" class="fl">Perfil de Acesso *</label>
                                    <span class="material-symbols-outlined select-arrow">expand_more</span>
                                </div>

                                <!-- Especialidade (condicional) -->
                                <div class="campo-medico" id="campo-especialidade">
                                    <div class="field-wrap">
                                        <select id="especialidade_id" name="especialidade_id" class="fi cursor-pointer" style="-webkit-appearance:none;appearance:none;">
                                            <option value="0" disabled selected>  </option>
                                            <?php foreach ($especialidades as $e): ?>
                                                <option value="<?= $e['id'] ?>" <?= ($form['especialidade_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($e['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="material-symbols-outlined field-icon">medical_information</span>
                                        <label for="especialidade_id" class="fl">Especialidade *</label>
                                        <span class="material-symbols-outlined select-arrow">expand_more</span>
                                    </div>
                                </div>

                                <!-- Consultório (condicional) -->
                                <div class="campo-medico" id="campo-consultorio">
                                    <div class="field-wrap">
                                        <select id="consultorio_id" name="consultorio_id" class="fi cursor-pointer" style="-webkit-appearance:none;appearance:none;">
                                            <option value="0" disabled selected>  </option>
                                            <?php foreach ($consultorios as $c): ?>
                                                <option value="<?= $c['id'] ?>" <?= ($form['consultorio_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($c['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="material-symbols-outlined field-icon">meeting_room</span>
                                        <label for="consultorio_id" class="fl">Consultório</label>
                                        <span class="material-symbols-outlined select-arrow">expand_more</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-4 mt-12 pt-6 border-t border-black/5">
                                <a href="utilizadores.php" class="font-bold text-sm text-on-surface-variant hover:text-black transition-colors px-6 py-3 rounded-full hover:bg-gray-50">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-black text-white px-9 py-4 rounded-full font-bold text-sm flex items-center gap-2.5 btn-action shadow-lg shadow-black/10">
                                    <span class="material-symbols-outlined text-[20px]">person_add</span>
                                    Criar Utilizador
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
        const perfil = document.getElementById('perfil');
        const camposMedico = document.querySelectorAll('.campo-medico');

        function toggleCamposMedico() {
            const show = perfil.value === 'medico';
            camposMedico.forEach(c => {
                c.classList.toggle('visivel', show);
            });
            if (!show) {
                document.getElementById('especialidade_id').value = '0';
                document.getElementById('consultorio_id').value = '0';
            }
            // Mark selects with value as "has-value" for floating label
            markSelectStates();
        }

        // ─── Floating label support for <select> ───
        function markSelectStates() {
            document.querySelectorAll('select.fi').forEach(sel => {
                const val = sel.value;
                if (val && val.trim() !== '' && val !== '0') {
                    sel.classList.add('has-value');
                } else {
                    sel.classList.remove('has-value');
                }
            });
        }

        // Listen to all selects
        document.querySelectorAll('select.fi').forEach(sel => {
            sel.addEventListener('change', markSelectStates);
        });

        perfil.addEventListener('change', toggleCamposMedico);
        
        // ─── Foto Preview ───
        function previewAvatar(input) {
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
        markSelectStates();
    </script>
</body>
</html>