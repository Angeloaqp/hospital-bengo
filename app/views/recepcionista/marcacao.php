<?php
// ================================================
// Hospital Geral do Bengo — Nova Marcação / Atendimento
// ================================================
require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Disponibilidade.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

exigirPerfil(['recepcionista', 'admin']);
$meuPerfilObject = Utilizador::obter((int) sessao('utilizador_id'));

$origem = 'marcacao';
$mesmoDia = false;
$pacienteIdGet = (int) ($_GET['paciente_id'] ?? 0);

$especialidades = Disponibilidade::listarEspecialidades();
$tipos = Disponibilidade::listarTiposAtendimento();
$consultorios = Disponibilidade::listarConsultorios();

$erros = $_SESSION['erros_form'] ?? [];
$dados = $_SESSION['dados_form'] ?? [];
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['erros_form'], $_SESSION['dados_form'], $_SESSION['erro']);

$tituloPagina = 'Nova Marcação';
$subtituloPagina = 'Agendar consulta para data futura';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .floating-card {
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 10px -2px rgba(0, 0, 0, 0.03);
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #000;
            ring: 1px;
            ring-color: #000;
        }
        
        .toggle-checkbox:checked {
            right: 0;
            border-color: #68D391;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #000;
        }
        .toggle-checkbox:checked + .toggle-label:after {
            transform: translateX(100%);
            border-color: #fff;
        }

        @keyframes scale-up {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .animate-scale-up {
            animation: scale-up 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        
        /* Hide scrollbar for select elements */
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        
        .custom-dropdown-content {
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
            transition: all 0.2s ease-in-out;
        }
        .custom-dropdown.open .custom-dropdown-content {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
    </style>
</head>

<body class="text-on-surface bg-[#f3f4f6]">

<?php $paginaActual = 'marcacao'; ?>
<?php include __DIR__ . '/../comum/sidebar.php'; ?>

<?php include __DIR__ . '/../comum/header.php'; ?>

<div class="ml-56 mt-28 p-8 flex justify-center pb-24">
    <main class="w-full max-w-[1200px]">
        
        <?php if(!empty($erros)): ?>
        <div class="bg-error-container text-error px-5 py-4 rounded-2xl text-sm font-bold shadow-sm mb-6 max-w-[800px]">
            <?php foreach($erros as $e): ?><p class="mb-1 last:mb-0">⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if($erro): ?>
        <div class="bg-error-container text-error px-5 py-4 rounded-2xl text-sm font-bold shadow-sm mb-6 max-w-[800px]"><p>⚠ <?= htmlspecialchars($erro) ?></p></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" id="form-marcacao" class="relative">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
            <input type="hidden" name="acao" value="criar">
            <input type="hidden" name="origem" value="<?= $mesmoDia ? 'mesmo_dia' : 'marcacao' ?>">

            <div class="flex flex-col lg:flex-row gap-8" id="booking-form-state">
                
                <!-- Left Column: Forms -->
                <div class="flex-1 space-y-6">
                    
                    <!-- Step 1: Identificação do Paciente -->
                    <section class="bg-white rounded-[32px] p-8 floating-card border border-white relative z-50 animate-in fade-in slide-in-from-bottom-4 duration-500 fill-mode-both">
                        <div class="absolute top-8 right-8 w-1.5 h-1.5 rounded-full bg-red-500 shadow-sm" id="step1-indicator"></div>
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-[24px] bg-surface-container-low flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-black">person_search</span>
                                </div>
                                <h3 class="text-xl font-black tracking-tight">1. Identificação do Paciente</h3>
                            </div>
                        </div>

                        <div id="container-pesquisa" class="mb-4 relative">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1">Pesquisar Paciente *</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                                <input type="text" id="pesquisa-paciente" placeholder="Nome, BI, NIF..." class="w-full h-12 pl-12 pr-5 bg-surface-container-low border-none rounded-[24px] font-semibold text-sm focus:ring-2 focus:ring-black/10 transition-all" autocomplete="off">
                            </div>
                            <div id="resultados-paciente" class="bg-white border border-surface-container-high rounded-2xl mt-2 max-h-48 overflow-y-auto hidden shadow-xl absolute z-50 w-full"></div>
                        </div>

                        <input type="hidden" name="paciente_id" id="paciente-id" value="<?= $dados['paciente_id'] ?? '' ?>" required>

                        <!-- Selected Patient Card -->
                        <div class="p-5 bg-blue-50/50 rounded-[24px] border border-blue-100 items-start gap-4 transition-all hover:shadow-sm hidden" id="paciente-info">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                <span class="font-bold text-blue-600 text-lg" id="paciente-iniciais">P</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="text-base font-black text-black" id="paciente-nome-display">Nome do Paciente</h4>
                                        <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mt-0.5" id="paciente-extra">Detalhes</p>
                                    </div>
                                    <button type="button" onclick="limparPaciente()" class="text-xs font-bold text-error hover:bg-error/10 px-3 py-1.5 rounded-[12px] transition-colors active:scale-95">Alterar</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Step 2 & 5 Row: Tipo de Consulta & Prioridade -->
                    <div class="flex flex-col sm:flex-row gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500 delay-100 fill-mode-both relative z-40">
                        
                        <!-- Step 2: Tipo de Consulta -->
                        <section class="bg-white rounded-[32px] p-8 floating-card border border-white flex-1 relative z-40">
                            <div class="absolute top-8 right-8 w-1.5 h-1.5 rounded-full bg-red-500 shadow-sm" id="step2-indicator"></div>
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-[24px] bg-surface-container-low flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-black">medical_services</span>
                                    </div>
                                    <h3 class="text-xl font-black tracking-tight">2. Tipo de Consulta</h3>
                                </div>
                            </div>
                            
                            <div class="relative custom-dropdown" id="specialty-dropdown">
                                <button type="button" class="w-full h-14 px-5 bg-surface-container-low border-none rounded-[24px] font-semibold text-sm cursor-pointer hover:bg-surface-container transition-colors flex items-center justify-between" onclick="toggleDropdown('specialty-dropdown')">
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-on-surface-variant" id="specialty-icon">medical_services</span>
                                        <span class="text-black" id="specialty-text">Seleccione a Especialidade...</span>
                                    </div>
                                    <span class="material-symbols-outlined text-on-surface-variant pointer-events-none transition-transform duration-200">expand_more</span>
                                </button>
                                
                                <div class="h-0 w-0 overflow-hidden absolute">
                                    <select name="especialidade_id" id="sel-especialidade" required>
                                        <option disabled selected value="">Seleccione a Especialidade...</option>
                                        <?php foreach($especialidades as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="custom-dropdown-content absolute top-[calc(100%+8px)] left-0 w-full bg-white rounded-[24px] p-2 floating-card border border-zinc-100 z-50 max-h-60 overflow-y-auto">
                                    <?php foreach($especialidades as $e): 
                                        $icon = 'medical_services';
                                        $name = strtolower($e['nome']);
                                        if (strpos($name, 'urg') !== false) $icon = 'emergency';
                                        elseif (strpos($name, 'pediatr') !== false) $icon = 'child_care';
                                        elseif (strpos($name, 'ortopedi') !== false) $icon = 'personal_injury';
                                        elseif (strpos($name, 'oftalmologi') !== false) $icon = 'visibility';
                                        elseif (strpos($name, 'dermatologi') !== false) $icon = 'face';
                                        elseif (strpos($name, 'neurologi') !== false) $icon = 'psychology';
                                        elseif (strpos($name, 'ginecologi') !== false) $icon = 'female';
                                        elseif (strpos($name, 'otorrino') !== false) $icon = 'hearing';
                                        elseif (strpos($name, 'cirurgia') !== false) $icon = 'content_cut';
                                    ?>
                                    <button type="button" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-container-low rounded-[16px] transition-colors text-left" onclick="selectOption('specialty-dropdown', 'specialty-text', 'specialty-icon', '<?= htmlspecialchars($e['nome'], ENT_QUOTES) ?>', '<?= $icon ?>')">
                                        <span class="material-symbols-outlined text-on-surface-variant text-[20px]"><?= $icon ?></span>
                                        <span class="text-sm font-semibold"><?= htmlspecialchars($e['nome']) ?></span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>

                        <!-- Step 5: Prioridade & Observações -->
                        <section class="bg-white rounded-[32px] p-8 floating-card border border-white flex-1 relative z-30">
                            <div class="absolute top-8 right-8 w-1.5 h-1.5 rounded-full shadow-sm" id="step5-indicator" style="background-color: transparent;"></div>
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-[24px] bg-surface-container-low flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-black">priority</span>
                                    </div>
                                    <h3 class="text-xl font-black tracking-tight">5. Prioridade</h3>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="relative custom-dropdown" id="priority-dropdown">
                                    <button type="button" class="w-full h-14 px-5 bg-surface-container-low border-none rounded-[24px] font-semibold text-sm cursor-pointer hover:bg-surface-container transition-colors flex items-center justify-between" onclick="toggleDropdown('priority-dropdown')">
                                        <div class="flex items-center gap-3">
                                            <span class="material-symbols-outlined text-[#3B82F6]" id="priority-icon">check_circle</span>
                                            <span class="text-black" id="priority-text">Normal</span>
                                        </div>
                                        <span class="material-symbols-outlined text-on-surface-variant pointer-events-none transition-transform duration-200">expand_more</span>
                                    </button>
                                    
                                    <div class="h-0 w-0 overflow-hidden absolute">
                                        <select name="prioridade" id="sel-prioridade" required>
                                            <option value="4" selected>Normal</option>
                                            <option value="3">Grávida</option>
                                            <option value="2">Idoso</option>
                                            <option value="1">Urgente</option>
                                        </select>
                                    </div>
                                    
                                    <div class="custom-dropdown-content absolute top-[calc(100%+8px)] left-0 w-full bg-white rounded-[24px] p-2 floating-card border border-zinc-100 z-50">
                                        <button type="button" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-container-low rounded-[16px] transition-colors text-left" onclick="selectOption('priority-dropdown', 'priority-text', 'priority-icon', 'Normal', 'check_circle', 'text-[#3B82F6]')">
                                            <span class="material-symbols-outlined text-[#3B82F6] text-[20px]">check_circle</span>
                                            <span class="text-sm font-semibold">Normal</span>
                                        </button>
                                        <button type="button" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-container-low rounded-[16px] transition-colors text-left" onclick="selectOption('priority-dropdown', 'priority-text', 'priority-icon', 'Grávida', 'pregnant_woman', 'text-[#8B5CF6]')">
                                            <span class="material-symbols-outlined text-[#8B5CF6] text-[20px]">pregnant_woman</span>
                                            <span class="text-sm font-semibold">Grávida</span>
                                        </button>
                                        <button type="button" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-container-low rounded-[16px] transition-colors text-left" onclick="selectOption('priority-dropdown', 'priority-text', 'priority-icon', 'Idoso', 'elderly', 'text-[#F59E0B]')">
                                            <span class="material-symbols-outlined text-[#F59E0B] text-[20px]">elderly</span>
                                            <span class="text-sm font-semibold">Idoso</span>
                                        </button>
                                        <button type="button" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-container-low rounded-[16px] transition-colors text-left" onclick="selectOption('priority-dropdown', 'priority-text', 'priority-icon', 'Urgente', 'notification_important', 'text-[#EF4444]')">
                                            <span class="material-symbols-outlined text-[#EF4444] text-[20px]">notification_important</span>
                                            <span class="text-sm font-semibold">Urgente</span>
                                        </button>
                                    </div>
                                </div>
                                <textarea name="observacoes" class="w-full p-4 bg-surface-container-low border-none rounded-[24px] font-medium text-sm resize-none h-20 hover:bg-surface-container transition-colors" placeholder="Observações (Opcional)"><?= htmlspecialchars($dados['observacoes'] ?? '') ?></textarea>
                            </div>
                        </section>
                    </div>

                    <!-- Step 3: Médico Responsável -->
                    <section class="bg-white rounded-[32px] p-8 floating-card border border-white min-h-[220px] relative animate-in fade-in slide-in-from-bottom-4 duration-500 delay-200 fill-mode-both z-30">
                        <div class="absolute top-8 right-8 w-1.5 h-1.5 rounded-full bg-red-500 shadow-sm" id="step3-indicator"></div>
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-[24px] bg-surface-container-low flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-black">groups</span>
                                </div>
                                <h3 class="text-xl font-black tracking-tight">3. Médico Responsável</h3>
                            </div>
                            <div id="medicos-loading" class="hidden"><span class="material-symbols-outlined animate-spin text-on-surface-variant">progress_activity</span></div>
                        </div>

                        <!-- Grid State -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="container-medicos">
                            <div class="col-span-full text-center py-6 text-on-surface-variant text-sm font-bold bg-surface-container-low rounded-[24px] border border-dashed border-black/10">
                                Seleccione a Especialidade acima para ver os médicos disponíveis.
                            </div>
                        </div>
                        
                        <input type="hidden" name="medico_id" id="medico-id" required>
                        <input type="hidden" name="consultorio_id" id="consultorio-id">
                    </section>

                    <!-- Step 4: Data e Horário -->
                    <section class="bg-white rounded-[32px] p-8 floating-card border border-white relative animate-in fade-in slide-in-from-bottom-4 duration-500 delay-300 fill-mode-both">
                        <div class="absolute top-8 right-8 w-1.5 h-1.5 rounded-full bg-red-500 shadow-sm" id="step4-indicator"></div>
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-[24px] bg-surface-container-low flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-black">calendar_month</span>
                                </div>
                                <h3 class="text-xl font-black tracking-tight">4. Data e Horário</h3>
                            </div>
                            
                            <!-- Mode Toggle -->
                            <div class="flex items-center justify-center w-full max-w-sm sm:w-auto">
                                <label class="flex items-center cursor-pointer hover:opacity-80 transition-opacity" for="auto-mode-toggle">
                                    <div class="relative">
                                        <input class="sr-only" type="checkbox" id="auto-mode-toggle" onchange="toggleModoData()"/>
                                        <div class="block bg-surface-container-high w-10 h-6 rounded-full transition-colors duration-300 peer-checked:bg-black" id="auto-toggle-bg"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300" id="auto-toggle-dot"></div>
                                    </div>
                                    <div class="ml-3 text-xs font-bold text-on-surface-variant">Próxima Vaga Auto</div>
                                </label>
                                <style>
                                    #auto-mode-toggle:checked ~ #auto-toggle-bg { background-color: #000; }
                                    #auto-mode-toggle:checked ~ #auto-toggle-dot { transform: translateX(100%); }
                                </style>
                            </div>
                        </div>
                        
                        <input type="hidden" name="modo_data" id="modo-data" value="manual">

                        <input type="hidden" name="data_consulta" id="sel-data" value="<?= $dados['data_consulta'] ?? date('Y-m-d') ?>">

                        <!-- Manual View -->
                        <div class="flex flex-col md:flex-row gap-8" id="manual-date-view">
                            <div class="flex-1 space-y-6">
                                <div id="custom-calendar-wrapper">
                                    <!-- Rendered by JS -->
                                </div>
                                
                                <div id="info-capacidade" class="hidden">
                                    <div class="flex items-center gap-2 p-3 rounded-xl inline-flex text-xs font-bold" id="cap-badge">
                                        <span class="material-symbols-outlined text-lg" id="cap-icon">info</span>
                                        <span id="cap-texto">A verificar lotação...</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex-1 space-y-6">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant ml-1 mb-3">Turno</p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="relative cursor-pointer group hover:scale-[1.02] transition-all block">
                                            <input checked class="peer sr-only custom-radio" name="turno" type="radio" value="manha" id="turno-manha" onchange="checkCapacidade()"/>
                                            <div class="p-4 rounded-[24px] bg-surface-container-low border-2 border-transparent peer-checked:border-black peer-checked:bg-white transition-all text-center h-full flex flex-col items-center justify-center hover:shadow-sm">
                                                <span class="material-symbols-outlined text-xl mb-1 text-on-surface-variant group-peer-checked:text-black radio-icon">light_mode</span>
                                                <span class="text-[10px] font-black uppercase tracking-wider block radio-text">Manhã</span>
                                            </div>
                                        </label>
                                        <label class="relative cursor-pointer group hover:scale-[1.02] transition-all block">
                                            <input class="peer sr-only custom-radio" name="turno" type="radio" value="tarde" id="turno-tarde" onchange="checkCapacidade()"/>
                                            <div class="p-4 rounded-[24px] bg-surface-container-low border-2 border-transparent peer-checked:border-black peer-checked:bg-white transition-all text-center h-full flex flex-col items-center justify-center hover:shadow-sm">
                                                <span class="material-symbols-outlined text-xl mb-1 text-on-surface-variant group-peer-checked:text-black radio-icon">routine</span>
                                                <span class="text-[10px] font-black uppercase tracking-wider block radio-text">Tarde</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="area-data-auto" class="hidden p-8 bg-surface-container-low rounded-[24px] text-center">
                            <p class="text-sm font-bold text-on-surface-variant mb-1">A procurar vaga automaticamente...</p>
                            <p class="text-xs font-bold text-black" id="data-auto-resultado">Por favor seleccione o médico primeiro.</p>
                        </div>
                        
                    </section>

                    <!-- Step 6: Contactos e Lembretes -->
                    <section class="bg-white rounded-[32px] p-8 floating-card border border-white relative animate-in fade-in slide-in-from-bottom-4 duration-500 delay-400 fill-mode-both">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-[24px] bg-surface-container-low flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-black">notifications_active</span>
                                </div>
                                <h3 class="text-xl font-black tracking-tight">6. Contactos e Lembretes</h3>
                            </div>
                            <button type="button" onclick="adicionarContacto()" class="flex items-center gap-1.5 text-xs font-bold text-black hover:bg-surface-container-low px-3 py-1.5 rounded-[12px] transition-colors active:scale-95">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                Novo
                            </button>
                        </div>
                        
                        <div id="contactos-container" class="space-y-3">
                            <!-- Populated via JS -->
                        </div>
                        <div id="contactos-vazio" class="hidden text-center py-6 bg-surface-container-low/50 rounded-[24px] border border-dashed border-black/10 text-xs font-bold text-on-surface-variant">
                            Nenhum contacto associado para receber lembretes.
                        </div>
                    </section>
                </div>

                <!-- Right Column: Sticky Sidebar -->
                <div class="w-full lg:w-[340px] shrink-0 animate-in fade-in slide-in-from-right-8 duration-500 delay-300 fill-mode-both">
                    <div class="sticky top-32 space-y-6 flex flex-col items-center">
                        
                        <!-- Resumo da Reserva Card -->
                        <div class="bg-white rounded-[24px] overflow-hidden floating-card border border-white flex flex-col relative w-full transition-all duration-500" id="resumo-reserva-container">
                            <div class="p-8 pb-4 relative z-10">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-[11px] font-extrabold text-on-surface-variant uppercase tracking-[0.25em] font-headline">Resumo da Reserva</h4>
                                    <div class="hidden items-center gap-1 px-3 py-1 bg-green-100 text-[10px] text-green-700 rounded-full font-black uppercase tracking-widest animate-in fade-in zoom-in duration-300" id="summary-confirm-badge">
                                        <span class="material-symbols-outlined text-[14px] animate-scale-up">check_circle</span>
                                        Confirmado
                                    </div>
                                </div>
                            </div>
                            <div class="px-8 py-2 space-y-6 relative z-10 font-headline">
                                <div class="grid grid-cols-2 gap-x-4 gap-y-6">
                                    <div class="flex flex-col gap-1.5">
                                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Paciente</p>
                                        <div>
                                            <p class="text-sm font-black text-black leading-tight" id="resumo-paciente-nome">-</p>
                                            <p class="text-[10px] font-semibold text-zinc-500 mt-0.5 uppercase" id="resumo-paciente-extra">-</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Especialidade</p>
                                        <p class="text-sm font-black text-black" id="resumo-especialidade">-</p>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Médico</p>
                                        <div>
                                            <p class="text-sm font-black text-black" id="resumo-medico">-</p>
                                            <p class="text-[10px] font-semibold text-zinc-500 mt-0.5 uppercase tracking-wider" id="resumo-consultorio">-</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Prioridade</p>
                                        <p class="text-sm font-black text-black" id="resumo-prioridade">Regular</p>
                                    </div>
                                </div>
                                <div class="pt-6 border-t border-zinc-100">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1.5">Data e Horário</p>
                                            <p class="text-xl font-black text-black tracking-tight" id="resumo-data">-</p>
                                            <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-widest mt-1" id="resumo-turno">-</p>
                                        </div>
                                        <div class="hidden text-green-600 animate-in zoom-in duration-300" id="summary-confirm-icon">
                                            <span class="material-symbols-outlined text-[32px] fill-1">verified</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative py-6 overflow-hidden">
                                <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 border-t-2 border-dashed border-zinc-200"></div>
                                <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-[#f3f4f6] shadow-inner"></div>
                                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-[#f3f4f6] shadow-inner"></div>
                            </div>
                            <div class="px-8 flex flex-col items-center pb-4">
                                <div class="hidden w-full flex flex-col items-center animate-in fade-in zoom-in-95 slide-in-from-bottom-4 duration-700 ease-out" id="summary-senha">
                                    <div class="w-full flex justify-between items-center mb-6">
                                        <div class="text-left">
                                            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Senha do Paciente</p>
                                            <p class="text-3xl font-black text-black tracking-tighter" id="sucesso-senha-codigo">---</p>
                                        </div>
                                        <div class="w-16 h-16 bg-white p-1 border border-zinc-100 rounded-lg flex items-center justify-center hover:scale-105 transition-transform cursor-pointer">
                                            <span class="material-symbols-outlined text-4xl text-zinc-200">qr_code_2</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex flex-col gap-3 w-full relative">
                            <button type="submit" id="btn-submit" class="w-full bg-black text-white hover:bg-zinc-800 transition-all flex flex-col items-center justify-center gap-0.5 shadow-xl rounded-xl group active:scale-95 py-4">
                                <span class="hidden material-symbols-outlined animate-spin text-white" id="submit-loading">progress_activity</span>
                                <div class="flex flex-col items-center" id="submit-content">
                                    <span class="text-lg font-black tracking-tight" id="submit-btn-title">Agendar Consulta</span>
                                    <span class="text-[11px] font-bold text-white/40 uppercase tracking-[0.2em] group-hover:text-white/60" id="submit-btn-subtitle">Confirmar Reserva</span>
                                </div>
                            </button>
                            <a href="agenda.php" class="w-full bg-transparent text-zinc-400 hover:text-error hover:bg-error/5 transition-all py-4 rounded-xl font-bold text-xs uppercase tracking-[0.15em] active:scale-95 text-center" id="secondary-action-btn">
                                Cancelar Agendamento
                            </a>
                            
                            <!-- Toast -->
                            <div class="absolute bottom-full mb-4 left-1/2 -translate-x-1/2 w-72 bg-black text-white px-6 py-4 rounded-[24px] shadow-[0_8px_30px_rgb(0,0,0,0.12)] flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-500 ease-out z-50" id="print-success-toast">
                                <span class="material-symbols-outlined text-green-400">check_circle</span>
                                <div>
                                    <p class="text-sm font-bold">Marcação finalizada com sucesso!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </main>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
let contactoIdx = 0;
const mesmoDia = <?= $mesmoDia ? 'true' : 'false' ?>;
let pacienteIdInicial = <?= $pacienteIdGet ?>;
let isSubmitting = false;
let bookingState = 'initial';

// Helpers to update indicators
function updateIndicator(stepId, isComplete) {
    const el = document.getElementById(stepId);
    if(el) {
        if(isComplete) {
            el.classList.remove('bg-red-500');
            el.classList.add('bg-green-500');
        } else {
            el.classList.add('bg-red-500');
            el.classList.remove('bg-green-500');
        }
    }
}

// ==========================================
// 1. GESTÃO DO PACIENTE
// ==========================================
const inputPesq = document.getElementById('pesquisa-paciente');
const divResult = document.getElementById('resultados-paciente');
let debounce;

inputPesq.addEventListener('input', function(){
    clearTimeout(debounce);
    const q = this.value.trim();
    if(q.length < 2){ divResult.classList.add('hidden'); return; }
    
    debounce = setTimeout(()=>{
        fetch(BASE+'app/controllers/agenda_api.php?acao=pesquisar_paciente&q='+encodeURIComponent(q))
        .then(r=>r.json()).then(d=>{
            if(!d || !d.resultados) {
                console.error('API Error: No resultados array');
                return;
            }
            if(!d.resultados.length){
                divResult.innerHTML='<div class="px-5 py-4 text-xs font-bold text-on-surface-variant">Nenhum paciente encontrado.</div>';
                divResult.classList.remove('hidden');
                return;
            }
            divResult.innerHTML = d.resultados.map(p=> {
                const safeId = p.id || 0;
                const safeNome = p.nome ? String(p.nome).replace(/'/g,"\\'") : '';
                const displayNome = p.nome || '';
                const safeBi = p.bi_nif || '';
                const safeIdade = p.idade || '';
                const safeProc = p.numero_processo || '';
                
                return `<div class="px-5 py-3 hover:bg-surface-container-low cursor-pointer transition-colors border-b border-surface-container-low/50 last:border-0" onclick="seleccionarPaciente(${safeId},'${safeNome}','${safeBi}','${safeIdade}','${safeProc}')">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-sm text-black">${displayNome}</span>
                        <span class="text-xs font-bold text-on-surface-variant bg-surface-container px-2 py-1 rounded-md">${safeIdade||'?'} anos</span>
                    </div>
                    <div class="text-[10px] text-on-surface-variant font-bold mt-1 uppercase tracking-widest">${safeProc ? 'Nº Proc: '+safeProc + ' • ' : ''}${safeBi ? 'BI: '+safeBi : ''}</div>
                </div>`;
            }).join('');
            divResult.classList.remove('hidden');
        }).catch(e => {
            console.error("Fetch Error:", e);
            divResult.innerHTML='<div class="px-5 py-4 text-xs font-bold text-red-500">Erro ao pesquisar pacientes.</div>';
            divResult.classList.remove('hidden');
        });
    }, 300);
});

function seleccionarPaciente(id, nome, bi, idade, numero_processo = ''){
    document.getElementById('paciente-id').value = id;
    document.getElementById('paciente-nome-display').textContent = nome;
    
    let extraParts = [];
    if (numero_processo) extraParts.push('Nº Proc: ' + numero_processo);
    if (bi) extraParts.push('BI: ' + bi);
    if (idade) extraParts.push(idade + ' anos');
    
    const extraInfo = extraParts.length > 0 ? extraParts.join(' • ') : 'S/ Dados Extra';
    
    document.getElementById('paciente-extra').textContent = extraInfo;
    document.getElementById('paciente-iniciais').textContent = nome.charAt(0).toUpperCase();
    
    document.getElementById('paciente-info').classList.remove('hidden');
    document.getElementById('paciente-info').classList.add('flex');
    document.getElementById('container-pesquisa').classList.add('hidden');
    divResult.classList.add('hidden');
    
    // Update Resumo
    document.getElementById('resumo-paciente-nome').textContent = nome;
    document.getElementById('resumo-paciente-extra').textContent = extraInfo;
    updateIndicator('step1-indicator', true);
    
    carregarContactosPaciente(id);
}

function limparPaciente(){
    document.getElementById('paciente-id').value = '';
    document.getElementById('paciente-info').classList.add('hidden');
    document.getElementById('paciente-info').classList.remove('flex');
    document.getElementById('container-pesquisa').classList.remove('hidden');
    inputPesq.value = '';
    inputPesq.focus();
    
    document.getElementById('resumo-paciente-nome').textContent = '-';
    document.getElementById('resumo-paciente-extra').textContent = '-';
    updateIndicator('step1-indicator', false);
    
    document.getElementById('contactos-container').innerHTML = '';
    document.getElementById('contactos-vazio').classList.remove('hidden');
    contactoIdx = 0;
}

document.addEventListener('click', e => {
    if(!inputPesq.contains(e.target) && !divResult.contains(e.target)) divResult.classList.add('hidden');
});

// Load initial patient if provided
if(pacienteIdInicial > 0) {
    fetch(BASE+'app/controllers/agenda_api.php?acao=obter_paciente&paciente_id='+pacienteIdInicial)
    .then(r=>r.json()).then(d=>{
        if(d.paciente) {
            seleccionarPaciente(d.paciente.id, d.paciente.nome, d.paciente.bi_nif, d.paciente.idade, d.paciente.numero_processo);
        }
    });
}

// ==========================================
// CUSTOM DROPDOWNS LOGIC
// ==========================================
window.toggleDropdown = function(id) {
    const dropdown = document.getElementById(id);
    if (!dropdown) return;
    const wasOpen = dropdown.classList.contains('open');
    
    // Close all dropdowns
    document.querySelectorAll('.custom-dropdown').forEach(d => d.classList.remove('open'));
    
    // Toggle requested dropdown
    if (!wasOpen) {
      dropdown.classList.add('open');
    }
};

window.selectOption = function(dropdownId, textId, iconId, text, icon, iconClass = '') {
    const textEl = document.getElementById(textId);
    if (textEl) textEl.innerText = text;
    
    if (iconId && icon) {
        const iconEl = document.getElementById(iconId);
        if (iconEl) {
            iconEl.innerText = icon;
            if(iconClass) {
                iconEl.className = `material-symbols-outlined ${iconClass}`;
            }
        }
    }
    
    const dropdown = document.getElementById(dropdownId);
    if(dropdown) dropdown.classList.remove('open');
    
    // Trigger underlying select update
    if (dropdownId === 'specialty-dropdown') {
        const sel = document.getElementById('sel-especialidade');
        Array.from(sel.options).forEach(opt => {
            if(opt.text === text) sel.value = opt.value;
        });
        sel.dispatchEvent(new Event('change'));
    } else if (dropdownId === 'priority-dropdown') {
        const sel = document.getElementById('sel-prioridade');
        Array.from(sel.options).forEach(opt => {
            if(opt.text === text) sel.value = opt.value;
        });
        sel.dispatchEvent(new Event('change'));
    }
};

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.custom-dropdown')) {
      document.querySelectorAll('.custom-dropdown').forEach(d => d.classList.remove('open'));
    }
});

// ==========================================
// 2 & 5. ESPECIALIDADE & PRIORIDADE
// ==========================================
const selEsp = document.getElementById('sel-especialidade');
const contMedicos = document.getElementById('container-medicos');
const loadingMed = document.getElementById('medicos-loading');

selEsp.addEventListener('change', function() {
    updateIndicator('step2-indicator', this.value !== '');
    
    // Attempt to get text from the selected option (handle native vs custom)
    let text = '-';
    if(this.options && this.selectedIndex >= 0) {
        text = this.options[this.selectedIndex].text;
    } else {
        const span = document.getElementById('especialidade-text');
        text = span ? span.innerText : '-';
    }
    document.getElementById('resumo-especialidade').textContent = text;
    carregarMedicos();
});

const selPrioridade = document.getElementById('sel-prioridade');
selPrioridade.addEventListener('change', function() {
    let text = '-';
    if(this.options && this.selectedIndex >= 0) {
        text = this.options[this.selectedIndex].text;
    } else {
        const span = document.getElementById('prioridade-text');
        text = span ? span.innerText : '-';
    }
    document.getElementById('resumo-prioridade').textContent = text;
});

function carregarMedicos() {
    let espId = selEsp.value;
    
    // Reset medico summary
    document.getElementById('resumo-medico').textContent = '-';
    document.getElementById('resumo-consultorio').textContent = '-';
    document.getElementById('medico-id').value = '';
    updateIndicator('step3-indicator', false);
    
    if(!espId) {
        contMedicos.innerHTML = `<div class="col-span-full text-center py-6 text-on-surface-variant text-sm font-bold bg-surface-container-low rounded-[24px] border border-dashed border-black/10">Seleccione a Especialidade acima para ver os médicos disponíveis.</div>`;
        return;
    }
    
    loadingMed.classList.remove('hidden');
    contMedicos.style.opacity = '0.5';
    
    fetch(BASE+'app/controllers/agenda_api.php?acao=medicos_da_especialidade&especialidade_id='+espId)
    .then(r=>r.json()).then(d=>{
        loadingMed.classList.add('hidden');
        contMedicos.style.opacity = '1';
        
        if(!d.medicos || !d.medicos.length) {
            contMedicos.innerHTML = `<div class="col-span-full text-center py-6 text-error text-sm font-bold bg-error-container rounded-[24px]">Nenhum médico registado para esta especialidade.</div>`;
            return;
        }
        
        contMedicos.innerHTML = d.medicos.map(m=>
            `<label class="relative cursor-pointer group hover:scale-[1.02] hover:shadow-md transition-all rounded-[32px]">
                <input class="peer sr-only" name="medico_seleccao" type="radio" value="${m.id}" data-cons="${m.consultorio_id||''}" data-nome="${m.nome}" data-cons-nome="${m.consultorio_nome||'Sem Consultório'}" onchange="seleccionarMedico(this)" />
                <div class="flex items-center gap-4 p-4 rounded-[32px] bg-surface-container-low border-2 border-transparent peer-checked:border-black peer-checked:bg-white transition-all h-full">
                    <div class="w-10 h-10 rounded-full bg-black flex items-center justify-center shrink-0 text-white font-bold text-xs">
                        Dr
                    </div>
                    <div>
                        <p class="text-sm font-black text-black">Dr(a). ${m.nome}</p>
                        <p class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider">${m.consultorio_nome || 'Sem Consultório Base'}</p>
                    </div>
                    <div class="absolute top-4 right-4 w-4 h-4 rounded-full border-2 border-surface-variant peer-checked:border-black peer-checked:bg-black flex items-center justify-center transition-colors">
                        <div class="w-1.5 h-1.5 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                    </div>
                </div>
            </label>`
        ).join('');
    });
}

function seleccionarMedico(radio) {
    document.getElementById('medico-id').value = radio.value;
    document.getElementById('consultorio-id').value = radio.dataset.cons || '';
    
    document.getElementById('resumo-medico').textContent = "Dr(a). " + radio.dataset.nome;
    document.getElementById('resumo-consultorio').textContent = radio.dataset.consNome;
    updateIndicator('step3-indicator', true);
    
    if(!mesmoDia) checkCapacidade();
}

// ==========================================
// 4. DATA E CAPACIDADE (CUSTOM CALENDAR)
// ==========================================
const selData = document.getElementById('sel-data');

let calDate = new Date();
if(selData.value) {
    const parts = selData.value.split('-');
    calDate = new Date(parts[0], parts[1]-1, parts[2]);
}
let currentMonth = calDate.getMonth();
let currentYear = calDate.getFullYear();
let selectedDateStr = selData.value;

const monthsPT = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

function renderCalendar() {
    const wrapper = document.getElementById('custom-calendar-wrapper');
    if(!wrapper) return;
    
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();
    
    const today = new Date();
    today.setHours(0,0,0,0);
    
    let html = `
    <div class="flex items-center justify-between mb-4 px-2">
        <button type="button" onclick="changeMonth(-1)" class="text-on-surface-variant hover:text-black hover:bg-surface-container-low rounded-full p-1 transition-colors active:scale-95"><span class="material-symbols-outlined">chevron_left</span></button>
        <p class="text-xs font-bold text-black uppercase tracking-widest">${monthsPT[currentMonth]} ${currentYear}</p>
        <button type="button" onclick="changeMonth(1)" class="text-on-surface-variant hover:text-black hover:bg-surface-container-low rounded-full p-1 transition-colors active:scale-95"><span class="material-symbols-outlined">chevron_right</span></button>
    </div>
    <div class="bg-surface-container-low rounded-[24px] p-5">
        <div class="grid grid-cols-7 gap-y-3 text-center text-xs font-semibold w-full">
            <span class="text-on-surface-variant/50">D</span><span class="">S</span><span class="">T</span><span class="">Q</span><span class="">Q</span><span class="">S</span><span class="text-on-surface-variant/50">S</span>
    `;
    
    // Previous month padding
    for(let i = 0; i < firstDay; i++) {
        let d = daysInPrevMonth - firstDay + i + 1;
        html += `<span class="text-on-surface-variant/30 flex items-center justify-center">${d}</span>`;
    }
    
    // Current month days
    for(let i = 1; i <= daysInMonth; i++) {
        let thisDate = new Date(currentYear, currentMonth, i);
        let dateStr = `${currentYear}-${String(currentMonth+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
        let isPast = thisDate < today;
        let isSelected = dateStr === selectedDateStr;
        
        if(isPast) {
            html += `<button type="button" disabled class="hover:bg-white rounded-[12px] w-8 h-8 flex items-center justify-center mx-auto transition-all disabled:opacity-30">${i}</button>`;
        } else if(isSelected) {
            html += `<button type="button" onclick="selectDate('${dateStr}')" class="bg-black text-white rounded-[12px] w-8 h-8 flex items-center justify-center mx-auto shadow-md transition-all hover:scale-[1.1]">${i}</button>`;
        } else {
            html += `<button type="button" onclick="selectDate('${dateStr}')" class="hover:bg-white rounded-[12px] w-8 h-8 flex items-center justify-center mx-auto transition-all hover:scale-[1.1] hover:shadow-sm text-black relative">${i}</button>`;
        }
    }
    
    // Next month padding
    let totalCells = firstDay + daysInMonth;
    let nextDays = 0;
    while(totalCells % 7 !== 0) {
        nextDays++;
        html += `<span class="text-on-surface-variant/30 flex items-center justify-center">${nextDays}</span>`;
        totalCells++;
    }
    
    html += `
        </div>
        <div class="flex items-center justify-center gap-4 mt-4 pt-4 border-t border-black/5">
            <div class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full bg-green-500"></div><span class="text-[9px] font-bold text-on-surface-variant uppercase">Vagas</span></div>
            <div class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full bg-error"></div><span class="text-[9px] font-bold text-on-surface-variant uppercase">Lotado</span></div>
        </div>
    </div>`;
    
    wrapper.innerHTML = html;
}

window.changeMonth = function(dir) {
    currentMonth += dir;
    if(currentMonth > 11) { currentMonth = 0; currentYear++; }
    else if(currentMonth < 0) { currentMonth = 11; currentYear--; }
    renderCalendar();
}

window.selectDate = function(dateStr) {
    selectedDateStr = dateStr;
    selData.value = dateStr;
    renderCalendar();
    checkCapacidade(); 
}

function updateResumoData() {
    const modo = document.getElementById('modo-data').value;
    const turnoNode = document.querySelector('input[name="turno"]:checked');
    const turno = turnoNode ? (turnoNode.value === 'manha' ? 'Manhã' : 'Tarde') : '-';
    
    if (modo === 'auto') {
        document.getElementById('resumo-data').textContent = "Automático (Próxima Vaga)";
        document.getElementById('resumo-turno').textContent = "Qualquer Turno";
        updateIndicator('step4-indicator', true);
    } else {
        if(selData.value) {
            const dateObj = new Date(selData.value);
            const options = { day: 'numeric', month: 'long', year: 'numeric' };
            document.getElementById('resumo-data').textContent = dateObj.toLocaleDateString('pt-PT', options);
            document.getElementById('resumo-turno').textContent = turno;
            updateIndicator('step4-indicator', true);
        } else {
            document.getElementById('resumo-data').textContent = "-";
            document.getElementById('resumo-turno').textContent = "-";
            updateIndicator('step4-indicator', false);
        }
    }
}

function toggleModoData() {
    const isAuto = document.getElementById('auto-mode-toggle').checked;
    const modoInput = document.getElementById('modo-data');
    const manual = document.getElementById('manual-date-view');
    const auto = document.getElementById('area-data-auto');
    
    if(!isAuto) {
        modoInput.value = 'manual';
        manual.classList.remove('hidden');
        manual.classList.add('flex');
        auto.classList.add('hidden');
        checkCapacidade();
    } else {
        modoInput.value = 'auto';
        manual.classList.add('hidden');
        manual.classList.remove('flex');
        auto.classList.remove('hidden');
        procurarProximaData();
    }
    updateResumoData();
}

function procurarProximaData() {
    const medicoId = document.getElementById('medico-id').value;
    const result = document.getElementById('data-auto-resultado');
    if(!medicoId) {
        result.textContent = 'Por favor seleccione o médico primeiro.';
        return;
    }
    result.textContent = 'O sistema alocará automaticamente a próxima vaga livre a partir de amanhã.';
    document.getElementById('info-capacidade')?.classList.add('hidden');
}

function checkCapacidade() {
    if(mesmoDia) return;
    const modo = document.getElementById('modo-data').value;
    if(modo === 'auto') return;
    
    const med = document.getElementById('medico-id').value;
    const data = selData.value;
    const turno = document.querySelector('input[name="turno"]:checked')?.value || 'manha';
    
    updateResumoData();
    
    if(!med || !data) return;
    
    const divInfo = document.getElementById('info-capacidade');
    divInfo.classList.remove('hidden');
    
    fetch(BASE+'app/controllers/agenda_api.php?acao=capacidade&medico_id='+med+'&data='+data+'&turno='+turno)
    .then(r=>r.json()).then(d=>{
        const badge = document.getElementById('cap-badge');
        const icon = document.getElementById('cap-icon');
        const txt = document.getElementById('cap-texto');
        
        if(d.lotado) {
            badge.className = 'flex items-center gap-2 p-3 rounded-xl inline-flex text-xs font-bold bg-error-container text-error';
            icon.textContent = 'warning';
            txt.textContent = `Agenda Lotada — ${d.ocupacao}/${d.capacidade} vagas ocupadas`;
        } else {
            badge.className = 'flex items-center gap-2 p-3 rounded-xl inline-flex text-xs font-bold bg-green-50 text-green-700 border border-green-100';
            icon.textContent = 'check_circle';
            txt.textContent = `${d.livre} vagas livres para este turno (${d.ocupacao}/${d.capacidade})`;
        }
    });
}
renderCalendar();

// Initial summary data update
updateResumoData();

// ==========================================
// 6. CONTACTOS
// ==========================================
function carregarContactosPaciente(pacienteId) {
    fetch(BASE+'app/controllers/agenda_api.php?acao=contactos_paciente&paciente_id='+pacienteId)
    .then(r=>r.json()).then(d=>{
        const cont = document.getElementById('contactos-container');
        cont.innerHTML = '';
        contactoIdx = 0;
        
        if(d.contactos && d.contactos.length) {
            document.getElementById('contactos-vazio').classList.add('hidden');
            d.contactos.forEach(c => adicionarContacto(c.tipo, c.valor, c.nome_contacto, c.receber_notificacoes));
        } else {
            document.getElementById('contactos-vazio').classList.remove('hidden');
        }
    });
}

function adicionarContacto(tipo='', valor='', nome='', consent=0) {
    document.getElementById('contactos-vazio').classList.add('hidden');
    const c = document.getElementById('contactos-container');
    const i = contactoIdx++;
    
    const isWhatsApp = tipo === 'whatsapp' || tipo === '';
    const checked = consent == 1 ? 'checked' : (isWhatsApp ? 'checked' : '');
    
    let icon = 'chat'; let iconColor = 'text-green-600'; let bg = 'bg-green-100';
    if(tipo === 'email') { icon = 'mail'; iconColor = 'text-blue-600'; bg = 'bg-blue-100'; }
    else if(tipo === 'telefone') { icon = 'call'; iconColor = 'text-zinc-600'; bg = 'bg-zinc-200'; }
    else if(tipo === 'emergencia') { icon = 'emergency'; iconColor = 'text-red-600'; bg = 'bg-red-100'; }

    c.insertAdjacentHTML('beforeend',`
        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-surface-container-low rounded-[24px] border border-transparent hover:border-black/5 transition-colors group gap-4" id="contacto-${i}">
            <div class="flex items-center gap-4 flex-1">
                <div class="w-10 h-10 rounded-full ${bg} flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined ${iconColor} text-[20px]">${icon}</span>
                </div>
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <input type="text" name="contactos[${i}][valor]" value="${valor}" class="w-full bg-transparent border-b border-black/10 focus:border-black px-1 py-1 text-sm font-bold text-black" placeholder="Contacto..." required>
                    <div class="flex gap-2">
                        <select name="contactos[${i}][tipo]" class="bg-transparent border-b border-black/10 focus:border-black px-1 py-1 text-[10px] uppercase font-semibold text-on-surface-variant w-1/2">
                            <option value="telefone" ${tipo==='telefone'?'selected':''}>Telefone</option>
                            <option value="whatsapp" ${tipo==='whatsapp'?'selected':''}>WhatsApp</option>
                            <option value="email" ${tipo==='email'?'selected':''}>Email</option>
                            <option value="emergencia" ${tipo==='emergencia'?'selected':''}>Emerg.</option>
                        </select>
                        <input type="text" name="contactos[${i}][nome_contacto]" value="${nome}" class="w-1/2 bg-transparent border-b border-black/10 focus:border-black px-1 py-1 text-xs font-semibold text-on-surface-variant" placeholder="Nome (Opcional)">
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-4 shrink-0">
                <label class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase">Lembretes</span>
                    <div class="relative">
                        <input type="checkbox" name="contactos[${i}][consentimento]" value="1" ${checked} class="sr-only peer toggle-checkbox"/>
                        <div class="block bg-surface-container-high peer-checked:bg-black w-10 h-6 rounded-full transition-colors duration-300 toggle-label"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 peer-checked:translate-x-full"></div>
                    </div>
                </label>
                <button type="button" onclick="document.getElementById('contacto-${i}').remove()" class="text-error hover:bg-error/10 p-2 rounded-full transition-colors">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
            </div>
        </div>
    `);
}

// ==========================================
// FORM SUBMIT & SUCCESS STATE
// ==========================================
document.getElementById('form-marcacao').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if(bookingState === 'confirmed') {
        // Imprimir Ticket Action
        const toast = document.getElementById('print-success-toast');
        // Update toast text for print action
        toast.innerHTML = `<span class="material-symbols-outlined text-green-400">print_connect</span><div><p class="text-sm font-bold">Ticket enviado para a impressora...</p></div>`;
        
        toast.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
        toast.classList.add('opacity-100', 'translate-y-0');
        
        setTimeout(() => {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
        }, 3000);
        return;
    }
    
    if(isSubmitting) return;
    
    if(!document.getElementById('paciente-id').value) {
        alert('Por favor seleccione um paciente primeiro.');
        return;
    }
    if(!document.getElementById('medico-id').value) {
        alert('Por favor seleccione o médico responsável.');
        return;
    }
    
    isSubmitting = true;
    const btnContent = document.getElementById('submit-content');
    const btnLoading = document.getElementById('submit-loading');
    
    btnContent.classList.add('hidden');
    btnLoading.classList.remove('hidden');
    
    const fd = new FormData(this);
    fd.append('ajax', '1');
    
    fetch(this.action, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            // Sucesso! Atualizar Resumo UI
            const badge = document.getElementById('summary-confirm-badge');
            badge.classList.remove('hidden');
            badge.classList.add('flex');
            
            document.getElementById('summary-confirm-icon').classList.remove('hidden'); 
            
            // Mostrar Senha
            document.getElementById('summary-senha').classList.remove('hidden'); 
            document.getElementById('summary-senha').classList.add('flex'); 
            document.getElementById('sucesso-senha-codigo').textContent = d.senha_codigo || 'N/A';
            
            // Alterar botão principal
            document.getElementById('submit-btn-title').innerText = 'Imprimir Ticket'; 
            document.getElementById('submit-btn-subtitle').innerText = 'Imprimir Ficha de Presença';
            
            // Alterar botão secundário para link de voltar
            const secBtn = document.getElementById('secondary-action-btn');
            secBtn.innerText = 'FINALIZAR AGENDAMENTO';
            secBtn.href = "#";
            secBtn.onclick = function(ev) { ev.preventDefault(); window.location.reload(); };
            secBtn.classList.remove('text-zinc-400', 'hover:text-error', 'hover:bg-error/5');
            secBtn.classList.add('text-black', 'bg-surface-container-low', 'hover:bg-surface-container');
            
            bookingState = 'confirmed';
            
            // Mostrar Toast de sucesso inicial
            const toast = document.getElementById('print-success-toast');
            toast.innerHTML = `<span class="material-symbols-outlined text-green-400">check_circle</span><div><p class="text-sm font-bold">Marcação finalizada com sucesso!</p></div>`;
            toast.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            toast.classList.add('opacity-100', 'translate-y-0');
            
            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
            }, 3000);
            
            // Disable further form edits except for submit button
            document.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(el => el.disabled = true);
            
        } else {
            alert('Erro: ' + (d.erros ? d.erros.join('\n') : 'Falha desconhecida.'));
            isSubmitting = false;
        }
    })
    .catch(err => {
        alert('Erro de rede ao processar marcação.');
        isSubmitting = false;
    })
    .finally(() => {
        if(!isSubmitting) {
            btnContent.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        } else {
            // Em sucesso, esconder loading e mostrar content novo
            btnContent.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        }
    });
});
</script>
</body>
</html>
