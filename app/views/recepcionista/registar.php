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
<body class="bg-[#f9f9f9] text-[#1a1c1c] font-['Inter'] antialiased">

<?php $paginaActual = 'registar'; ?>
<?php include __DIR__ . '/../comum/sidebar.php'; ?>

<?php $tituloPagina = 'Novo Paciente'; $subtituloPagina = 'Registar novo paciente no sistema'; ?>
<?php include __DIR__ . '/../comum/header.php'; ?>

<div class="ml-0 lg:ml-56 pt-28 px-4 sm:px-8 pb-24 lg:pb-8 flex justify-center min-h-screen">
    <main class="w-full max-w-[900px] relative mt-4">

        <form method="POST" action="<?= BASE_URL ?>app/controllers/pacientes_api.php" id="form-registo">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
            <input type="hidden" name="acao" value="registar_apenas">
            <input type="hidden" name="paciente_id" id="paciente_id" value="">

            <!-- ERROS -->
            <?php if (!empty($erros)): ?>
                <div class="bg-[#ffdad6] text-[#410002] px-6 py-5 rounded-[1.5rem] text-sm font-bold shadow-sm mb-8 flex gap-3 items-start">
                    <span class="material-symbols-outlined shrink-0">error</span>
                    <div>
                        <?php foreach ($erros as $e): ?>
                            <p class="mb-1 last:mb-0"><?= htmlspecialchars($e) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Section: Dados Pessoais -->
            <section class="bg-white rounded-[2.5rem] p-8 sm:p-12 ambient-shadow mb-8 transition-all duration-500 relative z-10" id="section-form">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-[1rem] bg-[#f3f3f3] flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[#1a1c1c] text-2xl">badge</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-['Manrope'] font-extrabold tracking-tight text-[#1a1c1c]">Dados Demográficos</h3>
                        <p class="text-sm font-medium text-[#474747] mt-1">Informações principais de identificação do paciente.</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-8">
                    <div class="md:col-span-2 group">
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-[#474747] mb-2 ml-1" for="nome">Nome Completo *</label>
                        <input id="nome" name="nome" value="<?= htmlspecialchars($antigos['nome'] ?? '') ?>" required minlength="3" class="tactile-input w-full h-14 px-5 rounded-[1rem] font-semibold text-base text-[#1a1c1c] placeholder-[#1a1c1c]/30" placeholder="Ex: António Kiala" type="text" />
                    </div>
                    <div class="group">
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-[#474747] mb-2 ml-1" for="bi_nif">BI ou NIF</label>
                        <input id="bi_nif" name="bi_nif" value="<?= htmlspecialchars($antigos['bi_nif'] ?? '') ?>" class="tactile-input w-full h-14 px-5 rounded-[1rem] font-semibold text-base text-[#1a1c1c] placeholder-[#1a1c1c]/30" placeholder="000000000XX000" type="text" />
                    </div>
                    <div class="group">
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-[#474747] mb-2 ml-1" for="idade">Idade *</label>
                        <input id="idade" name="idade" value="<?= htmlspecialchars($antigos['idade'] ?? '') ?>" required min="0" max="120" class="tactile-input w-full h-14 px-5 rounded-[1rem] font-semibold text-base text-[#1a1c1c] placeholder-[#1a1c1c]/30" placeholder="Anos" type="number" />
                    </div>
                    <div class="group">
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-[#474747] mb-2 ml-1" for="sexo">Género</label>
                        <div class="relative">
                            <select id="sexo" name="sexo" class="tactile-input w-full h-14 px-5 rounded-[1rem] font-semibold text-base text-[#1a1c1c] appearance-none cursor-pointer">
                                <option value="">Seleccionar...</option>
                                <option value="M" <?= ($antigos['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                                <option value="F" <?= ($antigos['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-[#474747]">expand_more</span>
                        </div>
                    </div>
                    <div class="group">
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-[#474747] mb-2 ml-1" for="morada">Morada *</label>
                        <input id="morada" name="morada" value="<?= htmlspecialchars($antigos['morada'] ?? '') ?>" required class="tactile-input w-full h-14 px-5 rounded-[1rem] font-semibold text-base text-[#1a1c1c] placeholder-[#1a1c1c]/30" placeholder="Ex: Centralidade do Sequele" type="text" />
                    </div>
                    
                    <!-- Campo de peso se for menor de idade (Tactile Soft Section) -->
                    <div class="md:col-span-2 overflow-hidden transition-all duration-500 ease-in-out" id="campo-peso-wrapper" style="max-height: 0; opacity: 0;">
                        <div class="p-6 bg-[#f9f9f9] rounded-[1.5rem] mt-2 relative overflow-hidden">
                            <!-- Indicador lateral suave -->
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-400"></div>
                            
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pl-4">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-full bg-[#f3f3f3] flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[#1a1c1c]">child_care</span>
                                    </div>
                                    <div>
                                        <p class="text-base font-bold text-[#1a1c1c]">Paciente Pediátrico</p>
                                        <p class="text-xs text-[#474747] font-medium mt-1 leading-relaxed">Sendo menor de 18 anos, informe o peso (kg) obrigatório para cálculo seguro de dosagens.</p>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1 sm:w-32 shrink-0">
                                    <input id="peso" name="peso" value="<?= htmlspecialchars($antigos['peso'] ?? '') ?>" step="0.1" min="1" max="200" class="tactile-input h-14 px-5 rounded-[1rem] font-bold text-base text-[#1a1c1c] placeholder-[#1a1c1c]/30 w-full" type="number" placeholder="Peso (kg)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12 flex justify-end">
                    <button type="submit" id="btn-registar" class="btn-gradient text-[#e5e2e1] px-8 py-4 rounded-[2rem] font-black text-sm hover:scale-[1.02] hover:shadow-[0_8px_20px_rgba(0,0,0,0.15)] transition-all flex items-center gap-3 active:scale-95">
                        <span class="material-symbols-outlined text-[20px]">person_add</span>
                        <span id="btn-text">Registar Paciente</span>
                    </button>
                </div>
            </section>

            <!-- Painel de Sucesso (Oculto inicialmente) -->
            <section class="bg-white rounded-[2.5rem] p-8 sm:p-14 ambient-shadow text-center flex flex-col items-center justify-center opacity-0 pointer-events-none absolute inset-0 transition-all duration-700 translate-y-8 z-0" id="section-sucesso">
                
                <!-- Circulo Decorativo Tactile -->
                <div class="relative w-28 h-28 mb-8">
                    <div class="absolute inset-0 bg-[#e2e2e2] rounded-full animate-ping opacity-20"></div>
                    <div class="absolute inset-0 bg-[#f3f3f3] rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-[#1a1c1c] text-5xl font-bold">check_circle</span>
                    </div>
                </div>

                <h3 class="text-3xl sm:text-4xl font-['Manrope'] font-extrabold text-[#1a1c1c] tracking-tight mb-3">Paciente Registado!</h3>
                <p class="text-[#474747] font-medium text-base max-w-md mx-auto mb-12 leading-relaxed">O registo do paciente foi guardado na base de dados com sucesso. Escolha o próximo passo lógico.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-2xl mx-auto">
                    <!-- Card 1 -->
                    <a href="#" id="link-mesmo-dia" class="group p-8 bg-[#f9f9f9] rounded-[2rem] hover:bg-white ambient-shadow-hover transition-all text-left flex flex-col justify-between h-full relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/[0.02] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 rounded-[1rem] bg-white flex items-center justify-center mb-6 shadow-sm group-hover:scale-110 transition-transform duration-500">
                                <span class="material-symbols-outlined text-[#1a1c1c] text-[24px]">local_hospital</span>
                            </div>
                            <h4 class="text-xl font-['Manrope'] font-bold tracking-tight mb-2 text-[#1a1c1c]">Atendimento Imediato</h4>
                            <p class="text-sm text-[#474747] font-medium leading-relaxed">Encaminhar o paciente agora mesmo para a triagem ou urgência.</p>
                        </div>
                        <div class="relative z-10 mt-8 flex justify-end">
                            <span class="w-8 h-8 rounded-full bg-[#1a1c1c] text-white flex items-center justify-center group-hover:bg-[#3c3b3b] transition-colors">
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </a>
                    
                    <!-- Card 2 -->
                    <a href="#" id="link-marcacao" class="group p-8 bg-[#f9f9f9] rounded-[2rem] hover:bg-white ambient-shadow-hover transition-all text-left flex flex-col justify-between h-full relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/[0.02] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 rounded-[1rem] bg-white flex items-center justify-center mb-6 shadow-sm group-hover:scale-110 transition-transform duration-500">
                                <span class="material-symbols-outlined text-[#1a1c1c] text-[24px]">calendar_month</span>
                            </div>
                            <h4 class="text-xl font-['Manrope'] font-bold tracking-tight mb-2 text-[#1a1c1c]">Fazer Marcação</h4>
                            <p class="text-sm text-[#474747] font-medium leading-relaxed">Agendar consulta médica para uma data e horário futuro.</p>
                        </div>
                        <div class="relative z-10 mt-8 flex justify-end">
                            <span class="w-8 h-8 rounded-full bg-[#1a1c1c] text-white flex items-center justify-center group-hover:bg-[#3c3b3b] transition-colors">
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </a>
                </div>
                
                <div class="mt-12 flex flex-wrap justify-center gap-6">
                    <button type="button" onclick="editarPaciente()" class="group flex items-center gap-2 text-xs font-bold text-[#474747] hover:text-[#1a1c1c] uppercase tracking-widest transition-colors">
                        <span class="material-symbols-outlined text-[16px] group-hover:-translate-x-1 transition-transform">edit</span>
                        Editar Dados
                    </button>
                    <span class="text-[#dadada] hidden sm:block">|</span>
                    <button type="button" onclick="window.location.reload()" class="group flex items-center gap-2 text-xs font-bold text-[#474747] hover:text-[#1a1c1c] uppercase tracking-widest transition-colors">
                        <span class="material-symbols-outlined text-[16px] group-hover:rotate-180 transition-transform duration-500">refresh</span>
                        Registar Outro
                    </button>
                </div>
            </section>

        </form>

    </main>
</div>

<script>
    const inputIdade = document.getElementById('idade');
    const campoPesoWrapper = document.getElementById('campo-peso-wrapper');
    const inputPeso = document.getElementById('peso');

    // Lógica da Idade: Exibir secção pediátrica de forma animada e elegante
    inputIdade.addEventListener('input', function () {
        if (parseInt(this.value) < 18 && this.value !== '') {
            campoPesoWrapper.style.maxHeight = '200px';
            campoPesoWrapper.style.opacity = '1';
            inputPeso.required = true;
        } else {
            campoPesoWrapper.style.maxHeight = '0';
            campoPesoWrapper.style.opacity = '0';
            inputPeso.required = false;
            inputPeso.value = '';
        }
    });
    
    // Animação de submissão do formulário com feedback tactile
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
                // Configurar links baseados no ID retornado do paciente
                document.getElementById('link-mesmo-dia').href = `marcacao.php?origem=mesmo_dia&paciente_id=${result.paciente_id}`;
                document.getElementById('link-marcacao').href = `marcacao.php?origem=marcacao&paciente_id=${result.paciente_id}`;
                document.getElementById('paciente_id').value = result.paciente_id;
                
                // Animar transição (Fade Out Form, Fade In Sucesso) usando Tonal Layering
                const sectionForm = document.getElementById('section-form');
                const sectionSucesso = document.getElementById('section-sucesso');
                
                sectionForm.classList.remove('z-10');
                sectionForm.classList.add('opacity-0', 'scale-[0.98]', 'pointer-events-none', '-translate-y-4', 'z-0');
                
                setTimeout(() => {
                    sectionSucesso.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-8', 'z-0');
                    sectionSucesso.classList.add('opacity-100', 'translate-y-0', 'z-10');
                    sectionForm.style.display = 'none';
                }, 400);
                
                if (typeof window.showToast === 'function') window.showToast('Paciente guardado com sucesso!', 'success');
            } else {
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-wait');
                btnText.textContent = document.getElementById('paciente_id').value ? 'Salvar Alterações' : 'Registar Paciente';
                if (typeof window.showToast === 'function') window.showToast((result.erros || []).join(', '), 'error');
                else alert('Erros:\n' + (result.erros || []).join('\n'));
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
        const sectionForm = document.getElementById('section-form');
        
        sectionSucesso.classList.remove('z-10');
        sectionSucesso.classList.add('opacity-0', 'pointer-events-none', 'translate-y-8', 'z-0');
        sectionSucesso.classList.remove('opacity-100', 'translate-y-0');
        
        setTimeout(() => {
            sectionForm.style.display = 'block';
            setTimeout(() => {
                sectionForm.classList.remove('opacity-0', 'scale-[0.98]', 'pointer-events-none', '-translate-y-4', 'z-0');
                sectionForm.classList.add('z-10');
                
                const btn = document.getElementById('btn-registar');
                document.getElementById('btn-text').textContent = 'Salvar Alterações';
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-wait');
            }, 50);
        }, 400);
    }
</script>

</body>
</html>