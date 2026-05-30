<?php
// ================================================
// Hospital Geral do Bengo
// Formulário de Registo de Paciente
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Paciente.php';

exigirPerfil(['recepcionista', 'admin']);

$erros = $_SESSION['erros_form'] ?? [];
$antigos = $_SESSION['dados_form'] ?? [];
unset($_SESSION['erros_form'], $_SESSION['dados_form']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Paciente — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        /* Tactile Editorial Custom Styles */
        .tactile-input {
            background-color: #f3f3f3; /* surface-container-low */
            border: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .tactile-input:hover {
            background-color: #eeeeee; /* surface-container */
        }
        .tactile-input:focus {
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(0,0,0,0.04);
            outline: none;
        }
        .ambient-shadow {
            box-shadow: 0px 20px 40px rgba(0, 0, 0, 0.04);
        }
        .ambient-shadow-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .ambient-shadow-hover:hover {
            box-shadow: 0px 24px 48px rgba(0, 0, 0, 0.08);
            transform: translateY(-4px);
        }
        .btn-gradient {
            background: linear-gradient(135deg, #000000 0%, #3c3b3b 100%);
        }
    </style>
</head>
<body class="bg-surface-container-low text-[#1a1c1c] font-['Inter'] antialiased">

<?php $paginaActual = 'registar'; ?>
<?php include __DIR__ . '/../comum/sidebar.php'; ?>

<?php $tituloPagina = 'Novo Paciente'; $subtituloPagina = 'Registar novo paciente no sistema'; ?>
<?php include __DIR__ . '/../comum/header.php'; ?>

<div class="ml-0 lg:ml-[17rem] lg:mr-6 px-4 sm:px-6 lg:px-0 mt-28 pb-24 lg:pb-8 flex justify-center min-h-screen">
    <main class="w-full relative mt-4">

        <div class="mb-4 transition-all duration-500 fade-in" id="page-header">
            <h1 class="font-headline text-3xl font-black text-black tracking-tight">Novo Paciente</h1>
            <p class="font-body text-[#474747] mt-2 text-sm">Preencha os detalhes abaixo para registar um novo paciente no sistema HGB.</p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>app/controllers/pacientes_api.php" id="form-registo" class="bg-white rounded-[32px] floating-card border border-white/50 overflow-hidden relative z-10 transition-all duration-500 fade-in-delay-1">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
            <input type="hidden" name="acao" value="registar_apenas">
            <input type="hidden" name="paciente_id" id="paciente_id" value="">

            <!-- ERROS -->
            <?php if (!empty($erros)): ?>
                <div class="bg-[#ffdad6] text-[#410002] px-6 py-5 mx-8 mt-8 rounded-[1.5rem] text-sm font-bold shadow-sm flex gap-3 items-start">
                    <span class="material-symbols-outlined shrink-0">error</span>
                    <div>
                        <?php foreach ($erros as $e): ?>
                            <p class="mb-1 last:mb-0"><?= htmlspecialchars($e) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="p-8 md:p-12 flex flex-col gap-12" id="section-form-content">
                <!-- Section 1: Dados Pessoais -->
                <section>
                    <div class="border-b border-surface-container pb-4 mb-8">
                        <h2 class="font-headline text-xl font-bold text-black flex items-center gap-2">
                            <span class="material-symbols-outlined text-black text-[24px]">person</span>
                            Dados Pessoais
                        </h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <!-- Nome Completo -->
                        <div class="flex flex-col gap-2">
                            <label class="font-headline text-sm font-bold text-black flex items-center gap-1">
                                Nome Completo <span aria-label="Required" class="w-1.5 h-1.5 rounded-full bg-error inline-block"></span>
                            </label>
                            <div class="bg-surface-container-low p-3.5 flex items-center input-focus-ring rounded-2xl bg-[#f3f3f3]/50">
                                <input name="nome" value="<?= htmlspecialchars($antigos['nome'] ?? '') ?>" required minlength="3" class="bg-transparent border-none focus:ring-0 p-0 w-full text-black font-body text-sm placeholder-outline" placeholder="Ex: Maria João Silva" type="text"/>
                            </div>
                        </div>
                        <!-- Idade -->
                        <div class="flex flex-col gap-2">
                            <label class="font-headline text-sm font-bold text-black flex items-center gap-1">
                                Idade <span aria-label="Required" class="w-1.5 h-1.5 rounded-full bg-error inline-block"></span>
                            </label>
                            <div class="bg-surface-container-low p-3.5 flex items-center input-focus-ring rounded-2xl bg-[#f3f3f3]/50">
                                <input id="idade" name="idade" value="<?= htmlspecialchars($antigos['idade'] ?? '') ?>" required type="number" min="0" max="120" class="bg-transparent border-none focus:ring-0 p-0 w-full text-black font-body text-sm placeholder-outline" placeholder="Anos"/>
                            </div>
                        </div>
                        <!-- Género -->
                        <div class="flex flex-col gap-2">
                            <label class="font-headline text-sm font-bold text-black">Género</label>
                            <?php
                            $sel_id = 'cs-genero';
                            $sel_name = 'sexo';
                            $sel_icon = 'wc';
                            $sel_placeholder = 'Selecione o género...';
                            $sel_value = $antigos['sexo'] ?? '';
                            $sel_class = 'w-full';
                            $sel_options = [
                                'M' => ['label' => 'Masculino', 'icon' => 'male', 'color' => 'text-blue-500'],
                                'F' => ['label' => 'Feminino', 'icon' => 'female', 'color' => 'text-pink-500']
                            ];
                            include __DIR__ . '/../comum/custom_select.php';
                            ?>
                        </div>
                        <!-- BI / Passaporte -->
                        <div class="flex flex-col gap-2">
                            <label class="font-headline text-sm font-bold text-black">BI / Passaporte</label>
                            <div class="bg-surface-container-low p-3.5 flex items-center input-focus-ring rounded-2xl bg-[#f3f3f3]/50">
                                <input name="bi_nif" value="<?= htmlspecialchars($antigos['bi_nif'] ?? '') ?>" class="bg-transparent border-none focus:ring-0 p-0 w-full text-black font-body text-sm placeholder-outline" placeholder="Nº do documento" type="text"/>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 2: Contactos e Morada -->
                <section>
                    <div class="border-b border-surface-container pb-4 mb-8">
                        <h2 class="font-headline text-xl font-bold text-black flex items-center gap-2">
                            <span class="material-symbols-outlined text-black text-[24px]">contact_phone</span>
                            Contactos e Morada
                        </h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <!-- Morada Completa -->
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <label class="font-headline text-sm font-bold text-black flex items-center gap-1">Morada Completa <span aria-label="Required" class="w-1.5 h-1.5 rounded-full bg-error inline-block"></span></label>
                            <div class="bg-surface-container-low p-3.5 flex items-start input-focus-ring rounded-2xl bg-[#f3f3f3]/50">
                                <textarea name="morada" required class="bg-transparent border-none focus:ring-0 p-0 w-full text-black font-body text-sm placeholder-outline resize-none" placeholder="Rua, Bairro, Município..." rows="3"><?= htmlspecialchars($antigos['morada'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </section>



                <!-- Footer Actions -->
                <div class="pt-6 border-t border-surface-container flex items-center justify-end gap-4">
                    <button type="button" onclick="window.history.back()" class="px-6 py-3 font-headline font-bold text-sm text-[#474747] hover:bg-[#f3f3f3] transition-colors duration-300 ease-in-out rounded-2xl">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-registar" class="px-8 py-3 bg-primary text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all duration-300 ease-in-out active:scale-95 flex items-center gap-2 rounded-2xl">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                        <span id="btn-text">Registar Paciente</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Sucesso Screen -->
        <div id="section-sucesso" class="min-h-[70vh] flex flex-col items-center justify-center text-center opacity-0 pointer-events-none absolute inset-0 transition-all duration-700 translate-y-8 z-0 hidden w-full">
            <div class="mb-12 flex flex-col items-center">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-6" style="animation: successBounce 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;">
                    <span class="material-symbols-outlined text-[64px] text-green-600 font-bold">check</span>
                </div>
                <h1 class="font-headline text-4xl font-black text-black tracking-tight mb-3">Paciente Registado!</h1>
                <p class="font-body text-[#474747] text-lg max-w-xl">O registo do paciente foi guardado na base de dados com sucesso. Escolha o próximo passo lógico.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-4xl mb-12">
                <!-- Card 1 -->
                <a href="#" id="link-mesmo-dia" class="bg-white p-10 rounded-[2.5rem] floating-card border border-white/50 text-left flex flex-col relative group cursor-pointer hover:-translate-y-1 hover:shadow-[0_12px_30px_-4px_rgba(0,0,0,0.1)] transition-all duration-300">
                    <div class="w-14 h-14 bg-[#f3f3f3] rounded-2xl flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-black text-[28px]">medical_services</span>
                    </div>
                    <h3 class="font-headline text-xl font-bold text-black mb-2">Atendimento Imediato</h3>
                    <p class="font-body text-[#474747] text-sm pr-12">Encaminhar o paciente agora mesmo para a triagem ou urgência.</p>
                    <div class="absolute bottom-8 right-8 w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </div>
                </a>
                <!-- Card 2 -->
                <a href="#" id="link-marcacao" class="bg-white p-10 rounded-[2.5rem] floating-card border border-white/50 text-left flex flex-col relative group cursor-pointer hover:-translate-y-1 hover:shadow-[0_12px_30px_-4px_rgba(0,0,0,0.1)] transition-all duration-300">
                    <div class="w-14 h-14 bg-[#f3f3f3] rounded-2xl flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-black text-[28px]">calendar_month</span>
                    </div>
                    <h3 class="font-headline text-xl font-bold text-black mb-2">Fazer Marcação</h3>
                    <p class="font-body text-[#474747] text-sm pr-12">Agendar consulta médica para uma data e horário futuro.</p>
                    <div class="absolute bottom-8 right-8 w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </div>
                </a>
            </div>
            <div class="flex items-center justify-center gap-4 mb-12">
                <button type="button" onclick="editarPaciente()" class="flex items-center gap-2 px-6 py-3 bg-[#f3f3f3] text-[#474747] font-headline font-bold text-sm rounded-2xl hover:bg-[#e8e8e8] transition-colors">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                    Editar Dados
                </button>
                <button type="button" onclick="window.location.reload()" class="flex items-center gap-2 px-6 py-3 bg-[#f3f3f3] text-[#474747] font-headline font-bold text-sm rounded-2xl hover:bg-[#e8e8e8] transition-colors">
                    <span class="material-symbols-outlined text-[20px]">person_add</span>
                    Registar Outro
                </button>
            </div>
        </div>

    </main>

<script>

    
    // Animação de submissão do formulário
    document.getElementById('form-registo').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btn-registar');
        const btnText = document.getElementById('btn-text');
        
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-wait');
        btnText.textContent = 'A processar...';
        
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.status === 'success') {
                document.getElementById('link-mesmo-dia').href = `marcacao.php?origem=mesmo_dia&paciente_id=${result.paciente_id}`;
                document.getElementById('link-marcacao').href = `marcacao.php?origem=marcacao&paciente_id=${result.paciente_id}`;
                document.getElementById('paciente_id').value = result.paciente_id;
                
                const sectionForm = document.getElementById('form-registo');
                const pageHeader = document.getElementById('page-header');
                const sectionSucesso = document.getElementById('section-sucesso');
                
                sectionForm.classList.remove('z-10');
                sectionForm.classList.add('opacity-0', 'scale-[0.98]', 'pointer-events-none', '-translate-y-4', 'z-0');
                
                if (pageHeader) {
                    pageHeader.classList.add('opacity-0', 'scale-[0.98]', 'pointer-events-none', '-translate-y-4');
                }
                
                setTimeout(() => {
                    sectionSucesso.classList.remove('hidden');
                    // Add small delay so browser renders display block before transitioning opacity
                    setTimeout(() => {
                        sectionSucesso.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-8', 'z-0');
                        sectionSucesso.classList.add('opacity-100', 'translate-y-0', 'z-10');
                    }, 50);
                    sectionForm.style.display = 'none';
                    if (pageHeader) pageHeader.style.display = 'none';
                }, 400);
                
                if (typeof window.showToast === 'function') window.showToast('Paciente guardado com sucesso!', 'success');
            } else {
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-wait');
                btnText.textContent = document.getElementById('paciente_id').value ? 'Salvar Alterações' : 'Registar Paciente';
                if (typeof window.showToast === 'function') window.showToast((result.erros || []).join(', '), 'error');
                else alert('Erros:\\n' + (result.erros || []).join('\\n'));
            }
        } catch (e) {
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-wait');
            btnText.textContent = document.getElementById('paciente_id').value ? 'Salvar Alterações' : 'Registar Paciente';
            if (typeof window.showToast === 'function') window.showToast('Erro de conexão.', 'error');
            else alert('Erro de conexão.');
        }
    });

    // Função para voltar atrás no painel de sucesso (Editar paciente)
    function editarPaciente() {
        const sectionSucesso = document.getElementById('section-sucesso');
        const sectionForm = document.getElementById('form-registo');
        
        sectionSucesso.classList.remove('z-10', 'opacity-100', 'translate-y-0');
        sectionSucesso.classList.add('opacity-0', 'pointer-events-none', 'translate-y-8', 'z-0');
        
        setTimeout(() => {
            sectionForm.style.display = 'block';
            const pageHeader = document.getElementById('page-header');
            if (pageHeader) pageHeader.style.display = 'block';

            setTimeout(() => {
                sectionForm.classList.remove('opacity-0', 'scale-[0.98]', 'pointer-events-none', '-translate-y-4', 'z-0');
                sectionForm.classList.add('z-10', 'opacity-100');
                if (pageHeader) pageHeader.classList.remove('opacity-0', 'scale-[0.98]', 'pointer-events-none', '-translate-y-4');
                
                const btn = document.getElementById('btn-registar');
                document.getElementById('btn-text').textContent = 'Salvar Alterações';
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-wait');
            }, 50);
            
            setTimeout(() => {
                sectionSucesso.classList.add('hidden');
            }, 700);
        }, 400);
    }
</script>

</body>
</html>