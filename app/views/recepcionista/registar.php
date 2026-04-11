<?php
// ================================================
// Hospital Geral do Bengo
// Formulário de Registo de Paciente
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Paciente.php';
require_once __DIR__ . '/../../../app/models/Senha.php';

exigirPerfil(['recepcionista', 'admin']);

$tipos = Paciente::tiposAtendimento();
$erros = $_SESSION['erros_form'] ?? [];
$antigos = $_SESSION['dados_form'] ?? [];
unset($_SESSION['erros_form'], $_SESSION['dados_form']);

// Pré-selecciona urgência se vier do botão "Urgência"
$isUrgencia = isset($_GET['urgencia']);
$prioPadrao = $isUrgencia ? '1' : '4';

$prioridades = [
    1 => [
        'label' => 'Urgente',
        'cor' => '#DC2626',
        'bg' => '#FEE2E2',
        'icone' => '⚡'
    ],
    2 => [
        'label' => 'Idoso',
        'cor' => '#D97706',
        'bg' => '#FEF3C7',
        'icone' => '👴'
    ],
    3 => [
        'label' => 'Grávida',
        'cor' => '#7C3AED',
        'bg' => '#EDE9FE',
        'icone' => '🤰'
    ],
    4 => [
        'label' => 'Normal',
        'cor' => '#1E6FD9',
        'bg' => '#E0F2FE',
        'icone' => '👤'
    ],
];
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Paciente — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        /* Custom scrollbar for fixed sidebar se o ticket exceder a altura */
        .sticky-sidebar-container::-webkit-scrollbar {
            width: 4px;
        }
        .sticky-sidebar-container::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.1);
            border-radius: 10px;
        }
    </style>
</head>

<body class="text-on-surface">

<?php $paginaActual = 'registar'; ?>
<?php include __DIR__ . '/../comum/sidebar.php'; ?>

<?php $tituloPagina = 'Novo Paciente'; $subtituloPagina = ''; ?>
<?php include __DIR__ . '/../comum/header.php'; ?>

<div class="ml-56 mt-28 p-8 flex justify-center">
<form method="POST" action="<?= BASE_URL ?>app/controllers/pacientes.php" id="form-registo" class="w-full max-w-[1500px]">
<main class="grid grid-cols-1 xl:grid-cols-3 gap-8 relative">

        <!-- FORMULÁRIO (Move para cima do Main) -->
        <input type="hidden" name="acao" value="registar">

        <!-- Left Column: Forms -->
        <div class="xl:col-span-2 space-y-8">

            <!-- ERROS -->
            <?php if (!empty($erros)): ?>
                <div class="bg-error-container text-error px-5 py-4 rounded-2xl text-sm font-bold shadow-sm">
                    <?php foreach ($erros as $e): ?>
                        <p class="mb-1 last:mb-0">⚠ <?= htmlspecialchars($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Section: Dados Pessoais -->
            <section class="bg-white rounded-[2rem] p-8 floating-card border border-white">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                        <span class="material-symbols-outlined text-black">badge</span>
                    </div>
                    <h3 class="text-xl font-black tracking-tight">Dados Pessoais</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1" for="nome">Nome Completo *</label>
                        <input id="nome" name="nome" value="<?= htmlspecialchars($antigos['nome'] ?? '') ?>" required minlength="3" class="w-full h-12 px-5 bg-surface-container-high border-none rounded-2xl font-semibold text-sm" placeholder="Ex: António Kiala" type="text" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1" for="idade">Idade *</label>
                        <input id="idade" name="idade" value="<?= htmlspecialchars($antigos['idade'] ?? '') ?>" required min="0" max="120" class="w-full h-12 px-5 bg-surface-container-high border-none rounded-2xl font-semibold text-sm" placeholder="Anos" type="number" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1" for="morada">Morada *</label>
                        <input id="morada" name="morada" value="<?= htmlspecialchars($antigos['morada'] ?? '') ?>" required class="w-full h-12 px-5 bg-surface-container-high border-none rounded-2xl font-semibold text-sm" placeholder="Ex: Centralidade do Sequele" type="text" />
                    </div>
                    <div class="md:col-span-2 p-4 bg-surface-container-low rounded-2xl border border-black/5" id="campo-peso-area" style="display:none;">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-amber-600">O paciente é menor de idade?</p>
                                <p class="text-[10px] text-on-surface-variant mt-0.5">Por favor, informe obrigatoriamente o peso (dosagem pediátrica).</p>
                            </div>
                            <div class="flex flex-col gap-1 w-24">
                                <label class="text-[9px] font-black text-amber-600 uppercase" for="peso">Peso (kg)</label>
                                <input id="peso" name="peso" value="<?= htmlspecialchars($antigos['peso'] ?? '') ?>" step="0.1" min="1" max="200" class="h-9 px-3 bg-white border border-black/10 rounded-xl text-xs font-bold" type="number" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section: Especialidade de Consulta -->
            <section class="bg-white rounded-[2rem] p-8 floating-card border border-white">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                        <span class="material-symbols-outlined text-black">medical_services</span>
                    </div>
                    <h3 class="text-xl font-black tracking-tight">Especialidade da Consulta</h3>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <?php 
                    $iconesEspecialidade = [
                        'Geral' => 'stethoscope',
                        'Urgência / Emergência' => 'emergency',
                        'Pediatria' => 'child_care',
                        'Maternidade' => 'pregnant_woman',
                        'Cardiologia' => 'cardiology',
                        'Ortopedia' => 'orthopedics',
                        'Oftalmologia' => 'visibility',
                        'Estomatologia' => 'dentistry'
                    ];
                    foreach ($tipos as $t): 
                        $icone = $iconesEspecialidade[$t['nome']] ?? 'medical_services';
                        $checked = ($antigos['tipo_atendimento_id'] ?? '') == $t['id'] ? 'checked' : '';
                    ?>
                        <label class="relative cursor-pointer group">
                            <input <?= $checked ?> class="peer sr-only" name="tipo_atendimento_id" type="radio" value="<?= $t['id'] ?>" data-nome="<?= htmlspecialchars($t['nome']) ?>" required />
                            <div class="p-3.5 rounded-[1.25rem] bg-surface-container-high border border-transparent peer-checked:border-black peer-checked:bg-white transition-all text-center h-full flex flex-col justify-center shadow-sm overflow-hidden">
                                <span class="material-symbols-outlined text-xl mb-1.5 block text-on-surface-variant group-peer-checked:text-black"><?= $icone ?></span>
                                <span class="text-[9px] font-black uppercase tracking-wider block leading-tight text-on-surface-variant group-peer-checked:text-black truncate px-1 w-full" title="<?= htmlspecialchars($t['nome']) ?>"><?= htmlspecialchars($t['nome']) ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Section: Nível de Prioridade -->
            <section class="bg-white rounded-[2rem] p-8 floating-card border border-white">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                        <span class="material-symbols-outlined text-black">priority_high</span>
                    </div>
                    <h3 class="text-xl font-black tracking-tight">Nível de Prioridade</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php 
                    $prioTailwind = [
                        1 => ['bg_color' => 'bg-error', 'border_peer' => 'peer-checked:border-error', 'bg_peer' => 'peer-checked:bg-error/5', 'text_color' => 'text-error', 'sub' => 'Atendimento Imediato'],
                        2 => ['bg_color' => 'bg-amber-500', 'border_peer' => 'peer-checked:border-amber-500', 'bg_peer' => 'peer-checked:bg-amber-50', 'text_color' => 'text-amber-500', 'sub' => 'Atendimento Prioritário'],
                        3 => ['bg_color' => 'bg-purple-600', 'border_peer' => 'peer-checked:border-purple-600', 'bg_peer' => 'peer-checked:bg-purple-50', 'text_color' => 'text-purple-600', 'sub' => 'Atendimento Prioritário'],
                        4 => ['bg_color' => 'bg-blue-600', 'border_peer' => 'peer-checked:border-blue-600', 'bg_peer' => 'peer-checked:bg-blue-50', 'text_color' => 'text-blue-600', 'sub' => 'Fila Regular'],
                    ];
                    foreach ($prioridades as $v => $p): 
                        $tw = $prioTailwind[$v];
                        $checked = ($antigos['prioridade'] ?? $prioPadrao) == $v ? 'checked' : '';
                    ?>
                        <label class="relative cursor-pointer group">
                            <input <?= $checked ?> class="peer sr-only" name="prioridade" type="radio" value="<?= $v ?>" />
                            <div class="flex items-center gap-4 p-5 rounded-[1.5rem] bg-surface-container-high border border-transparent <?= $tw['border_peer'] ?> <?= $tw['bg_peer'] ?> transition-all shadow-sm">
                                <div class="w-4 h-4 rounded-full <?= $tw['bg_color'] ?> shrink-0"></div>
                                <div>
                                    <p class="text-sm font-black text-black"><?= $p['label'] ?></p>
                                    <p class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider"><?= $tw['sub'] ?></p>
                                </div>
                                <span class="material-symbols-outlined ml-auto <?= $tw['text_color'] ?> opacity-0 peer-checked:opacity-100">check_circle</span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
    </div>

    <!-- Right Column: Sticky Sidebar -->
    <div class="xl:block">
        <div class="sticky top-28 space-y-4">
            
            <!-- Ticket Card Container -->
            <div class="bg-white rounded-[2rem] p-6 floating-card border border-white flex flex-col items-center overflow-hidden relative min-h-[400px]" id="sidebar-card-content">
                
                <!-- Simulation State -->
                <div class="w-full flex flex-col items-center transition-all duration-500" id="ticket-simulation-view">
                    <h4 class="text-[9px] font-black uppercase tracking-[0.2em] text-on-surface-variant mb-6 text-center">Simulação do Ticket</h4>
                    
                    <div class="w-full border-t border-dashed border-black/10 pt-6 flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mb-4 transition-colors" id="preview-ticket-icon-bg" style="background:#2563EB;">
                            <span class="material-symbols-outlined text-white text-2xl">confirmation_number</span>
                        </div>
                        <p class="text-[9px] font-bold text-on-surface-variant uppercase tracking-widest text-center" id="preview-desc">Atendimento Geral</p>
                        <div class="text-[56px] font-black tracking-tighter leading-none my-2 text-black" id="preview-codigo">N-???</div>
                        
                        <div class="w-full bg-surface-container-low/50 rounded-2xl p-4 mt-4 space-y-2">
                            <div class="flex justify-between items-center text-[11px] font-bold">
                                <span class="text-on-surface-variant">Prioridade</span>
                                <span class="text-blue-600" id="preview-prio-nome">Normal</span>
                            </div>
                            <div class="flex justify-between items-center text-[11px] font-bold">
                                <span class="text-on-surface-variant">Setor</span>
                                <span class="text-black" id="preview-setor-nome">A Selecionar</span>
                            </div>
                        </div>
                        <div class="mt-6 opacity-20 grayscale">
                            <img alt="Código de barras" class="h-8 object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCOuGSnvPnllLldKlZr-QfOINw8hVRLebMY9D5t40zkcGQLopJ-hHte8-oD8DkBA3zng8PFVMkz26ANClW7gF31bmBg1chdRI04m66HGJfU7y_DqSUFEu9QMrTNcw0sKFapIUKRAAFvDzlFcLnRk2CTjIVKzPj3cocnPKmxd1N_EgG95QstZU5QyMCN0XJmezjr2L--9uSm5HqW8IFuruEXRkwW0PLQqyQXfOSjkVB8RWrdGS3_BE8cv1J4db55JmgwBf_3bFxNpu0f"/>
                        </div>
                        <p class="text-[7px] font-mono text-on-surface-variant mt-3 uppercase">Emitido em tempo real</p>
                    </div>
                </div>

                <!-- Success State (Hidden by default) -->
                <div class="absolute inset-0 flex flex-col items-center justify-center p-8 bg-white translate-y-full opacity-0 transition-all duration-500 pointer-events-none" id="success-state-view">
                    <div class="w-20 h-20 rounded-full bg-green-50 flex items-center justify-center mb-6 shadow-sm">
                        <span class="material-symbols-outlined text-green-600 text-5xl font-bold">check_circle</span>
                    </div>
                    <h3 class="text-lg font-black text-black text-center leading-tight mb-2">Paciente registado com sucesso</h3>
                    <div class="bg-surface-container-low px-4 py-2 rounded-xl mb-8">
                        <p class="text-[10px] text-on-surface-variant font-black uppercase tracking-widest">Senha: <span class="text-green-600 text-sm ml-1" id="success-senha-display">N-000</span></p>
                    </div>
                    <button type="button" class="w-full py-3 bg-black text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:scale-[1.02] transition-transform flex items-center justify-center gap-2" onclick="resetRegistration()">
                        <span class="material-symbols-outlined text-lg">add_circle</span>
                        Emitir Novo Registo
                    </button>
                </div>
            </div>

            <!-- Finalize Button Container (Only visible in simulation state) -->
            <div class="transition-all duration-500" id="finalize-btn-container">
                <button type="submit" id="btn-registar" class="w-full bg-black text-white floating-card hover:bg-zinc-800 active:scale-[0.98] transition-all flex flex-col items-center justify-center gap-1 group shadow-xl py-4 rounded-2xl cursor-pointer">
                    <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">print</span>
                    <span class="text-base font-black tracking-tight" id="btn-text-main">Finalizar Registro</span>
                    <span class="text-[9px] font-bold opacity-60 uppercase tracking-widest">Imprimir e Encaminhar</span>
                </button>
            </div>
        </div>
    </div>
</main>
</form>
</div>

    <script>
        // ================================================
        // Lógica do formulário de registo
        // ================================================

        const prefixos = { 1: 'U', 2: 'I', 3: 'G', 4: 'N' };
        const descricoes = {
            1: 'Urgente — Crítica',
            2: 'Terceira Idade',
            3: 'Gestante',
            4: 'Fila Regular'
        };
        const ticketBgs = {
            1: '#DC2626', 2: '#D97706',
            3: '#9333EA', 4: '#2563EB' // Purple for Gravida
        };

        function atualizarPreview() {
            const prio = document.querySelector('input[name="prioridade"]:checked');
            const tipo = document.querySelector('input[name="tipo_atendimento_id"]:checked');
            
            if (prio) {
                const v = parseInt(prio.value);
                document.getElementById('preview-codigo').textContent = (prefixos[v] || 'N') + '-???';
                document.getElementById('preview-desc').textContent = descricoes[v] || 'Atendimento Geral';
                document.getElementById('preview-prio-nome').textContent = descricoes[v]?.split('—')[0] || 'Normal';
                document.getElementById('preview-prio-nome').style.color = ticketBgs[v] || '#2563EB';
                document.getElementById('preview-ticket-icon-bg').style.backgroundColor = ticketBgs[v] || '#2563EB';
            }
            if (tipo) {
                document.getElementById('preview-setor-nome').textContent = tipo.dataset.nome || 'Geral';
            }
        }

        // Adiciona eventos aos selects customizados (Tailwind peers)
        document.querySelectorAll('input[name="prioridade"], input[name="tipo_atendimento_id"]').forEach(el => {
            el.addEventListener('change', atualizarPreview);
        });
        // Corre à partida
        atualizarPreview();

        // ---- Campo peso condicional ----
        document.getElementById('idade').addEventListener('input', function () {
            const campoPesoArea = document.getElementById('campo-peso-area');
            const inputPeso = document.getElementById('peso');
            if (parseInt(this.value) < 18 && this.value !== '') {
                campoPesoArea.style.display = 'flex';
                inputPeso.required = true;
            } else {
                campoPesoArea.style.display = 'none';
                inputPeso.required = false;
                inputPeso.value = '';
            }
        });

        // ---- Interação de Sucesso (Sidebar Dinâmica) ----
        function finalizeRegistration(senha) {
            const simulationView = document.getElementById('ticket-simulation-view');
            const successView = document.getElementById('success-state-view');
            const finalizeContainer = document.getElementById('finalize-btn-container');

            document.getElementById('success-senha-display').textContent = senha;

            // Animate simulation view out
            simulationView.classList.add('-translate-y-full', 'opacity-0');
            
            // Animate success view in
            successView.classList.remove('translate-y-full', 'opacity-0', 'pointer-events-none');
            successView.classList.add('translate-y-0', 'opacity-100');

            // Hide finalize button
            finalizeContainer.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
        }

        function resetRegistration() {
            const simulationView = document.getElementById('ticket-simulation-view');
            const successView = document.getElementById('success-state-view');
            const finalizeContainer = document.getElementById('finalize-btn-container');

            // Reset frontend UI visually
            simulationView.classList.remove('-translate-y-full', 'opacity-0');
            
            successView.classList.add('translate-y-full', 'opacity-0', 'pointer-events-none');
            successView.classList.remove('translate-y-0', 'opacity-100');

            finalizeContainer.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
            
            // Re-ativar botao de registo
            const btn = document.getElementById('btn-registar');
            btn.disabled = false;
            document.getElementById('btn-text-main').textContent = 'Finalizar Registro';

            // Reset do formulário principal
            document.getElementById('form-registo').reset();
            atualizarPreview();
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Trigger peso onload event
        document.getElementById('idade').dispatchEvent(new Event('input'));

        // ---- Submissão via AJAX ----
        document.getElementById('form-registo').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('btn-registar');
            const form = e.target;
            
            // Estado visual de loading
            btn.disabled = true;
            document.getElementById('btn-text-main').textContent = 'A Registar...';
            
            try {
                const formData = new FormData(form);
                const actionUrl = form.getAttribute('action');
                
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Executa a animação de Sucesso!
                    finalizeRegistration(result.senha);
                } else {
                    // Ocorreram erros na validação (Fallback simples de reset visual e alerta nativo)
                    btn.disabled = false;
                    document.getElementById('btn-text-main').textContent = 'Finalizar Registro';
                    alert('Erro ao registar: \n' + (result.erros || []).join('\n'));
                }
            } catch (error) {
                console.error(error);
                btn.disabled = false;
                document.getElementById('btn-text-main').textContent = 'Finalizar Registro';
                alert('Erro na comunicação com o servidor.');
            }
        });
    </script>

</body>

</html>