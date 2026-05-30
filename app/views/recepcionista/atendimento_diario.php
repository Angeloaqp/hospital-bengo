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

$origem = 'mesmo_dia';
$mesmoDia = true;
$pacienteIdGet = (int) ($_GET['paciente_id'] ?? 0);

$especialidades = Disponibilidade::listarEspecialidades();
$tipos = Disponibilidade::listarTiposAtendimento();
$consultorios = Disponibilidade::listarConsultorios();

$erros = $_SESSION['erros_form'] ?? [];
$dados = $_SESSION['dados_form'] ?? [];
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['erros_form'], $_SESSION['dados_form'], $_SESSION['erro']);

$tituloPagina = 'Atendimento Mesmo Dia';
$subtituloPagina = 'Encaminhamento imediato para triagem';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-radio:checked + div { border-color: #000; background-color: #fff; }
        .custom-radio:checked + div .radio-icon { color: #000; }
        .custom-radio:checked + div .radio-text { color: #000; }
    </style>
</head>

<body class="text-on-surface bg-background">

<?php $paginaActual = 'marcacao'; ?>
<?php include __DIR__ . '/../comum/sidebar.php'; ?>

<?php include __DIR__ . '/../comum/header.php'; ?>

<div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full relative">

    <form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" id="form-marcacao">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="acao" value="criar">
        <input type="hidden" name="origem" value="<?= $mesmoDia ? 'mesmo_dia' : 'marcacao' ?>">

        <?php if(!empty($erros)): ?>
        <div class="bg-error-container text-error px-5 py-4 rounded-2xl text-sm font-bold shadow-sm mb-6">
            <?php foreach($erros as $e): ?><p class="mb-1 last:mb-0">⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if($erro): ?>
        <div class="bg-error-container text-error px-5 py-4 rounded-2xl text-sm font-bold shadow-sm mb-6"><p>⚠ <?= htmlspecialchars($erro) ?></p></div>
        <?php endif; ?>

        <!-- 1. Paciente -->
        <section class="bg-white rounded-[2rem] p-8 floating-card border border-white mb-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                    <span class="material-symbols-outlined text-on-surface">person_search</span>
                </div>
                <h3 class="text-xl font-black tracking-tight">Identificação do Paciente</h3>
            </div>
            
            <div id="container-pesquisa" class="mb-4">
                <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1">Pesquisar Paciente</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input type="text" id="pesquisa-paciente" placeholder="Nome, BI, NIF..." class="w-full h-14 pl-12 pr-5 bg-surface-container-low border-none rounded-2xl font-semibold text-sm focus:ring-2 focus:ring-black/10 transition-all" autocomplete="off">
                </div>
                <div id="resultados-paciente" class="bg-white border border-surface-container-high rounded-xl mt-2 max-h-48 overflow-y-auto hidden shadow-xl absolute z-10 w-full max-w-[800px]"></div>
            </div>

            <input type="hidden" name="paciente_id" id="paciente-id" value="<?= $dados['paciente_id'] ?? '' ?>">
            
            <div id="paciente-info" class="bg-blue-50 border border-blue-100 rounded-2xl p-5 hidden transition-all">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-lg" id="paciente-iniciais">P</div>
                        <div>
                            <h4 id="paciente-nome-display" class="font-black text-base text-on-surface leading-tight">N/A</h4>
                            <p id="paciente-extra" class="text-blue-800 text-xs font-bold tracking-wide mt-0.5">N/A</p>
                        </div>
                    </div>
                    <button type="button" onclick="limparPaciente()" class="text-xs font-black text-red-600 hover:text-red-800 uppercase tracking-widest bg-red-100 px-4 py-2 rounded-xl transition-colors">
                        Alterar
                    </button>
                </div>
            </div>
        </section>

        <!-- 2. Consulta & Especialidade -->
        <section class="bg-white rounded-[2rem] p-8 floating-card border border-white mb-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                    <span class="material-symbols-outlined text-on-surface">medical_services</span>
                </div>
                <h3 class="text-xl font-black tracking-tight">Tipo de Consulta</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1">Especialidade da Consulta *</label>
                    <?php
                    $sel_id = 'sel-especialidade';
                    $sel_name = 'especialidade_id';
                    $sel_icon = 'medical_services';
                    $sel_placeholder = 'Seleccione a Especialidade...';
                    $sel_value = '';
                    $sel_required = true;
                    $sel_options = [];
                    foreach($especialidades as $e) {
                        $sel_options[(string)$e['id']] = ['label' => htmlspecialchars($e['nome']), 'icon' => 'medical_services', 'color' => 'text-blue-600'];
                    }
                    include __DIR__ . '/../comum/custom_select.php';
                    ?>
                </div>
            </div>
        </section>

        <!-- 3. Médico -->
        <section class="bg-white rounded-[2rem] p-8 floating-card border border-white mb-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-surface">stethoscope</span>
                    </div>
                    <h3 class="text-xl font-black tracking-tight">Médico Responsável</h3>
                </div>
                <div id="medicos-loading" class="hidden"><span class="material-symbols-outlined animate-spin text-on-surface-variant">progress_activity</span></div>
            </div>
            
            <div id="container-medicos" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-full text-center py-6 text-on-surface-variant text-sm font-bold bg-surface-container-low rounded-2xl border border-dashed border-black/10">
                    Seleccione a Especialidade ou Tipo de Atendimento acima para ver os médicos disponíveis.
                </div>
            </div>
            
            <input type="hidden" name="medico_id" id="medico-id" required>
            <input type="hidden" name="consultorio_id" id="consultorio-id">
        </section>

        <!-- 4. Data e Turno -->
        <section class="bg-white rounded-[2rem] p-8 floating-card border border-white mb-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-surface">calendar_clock</span>
                    </div>
                    <h3 class="text-xl font-black tracking-tight">Data e Horário</h3>
                </div>
            </div>

            <?php if($mesmoDia): 
                $horaActual = date('H');
                $turnoActual = ($horaActual < 13) ? 'manha' : 'tarde';
            ?>
                <input type="hidden" name="data_consulta" id="sel-data" value="<?= date('Y-m-d') ?>">
                <input type="hidden" name="turno" id="sel-turno" value="<?= $turnoActual ?>">
                <div class="flex items-center gap-4 p-5 bg-green-50 rounded-2xl border border-green-100">
                    <span class="material-symbols-outlined text-green-600 text-3xl">today</span>
                    <div>
                        <p class="text-base font-black text-green-800">Atendimento Imediato (Hoje)</p>
                        <p class="text-xs font-bold text-green-600/80"><?= date('d/m/Y') ?> — Turno da <?= $turnoActual == 'manha' ? 'Manhã' : 'Tarde' ?>. O paciente aguardará na fila.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <label class="relative cursor-pointer group">
                        <input checked class="peer sr-only custom-radio" name="modo_data" type="radio" value="manual" onchange="toggleModoData()" />
                        <div class="p-5 rounded-2xl bg-surface-container-low border-2 border-transparent transition-all flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant radio-icon">edit_calendar</span>
                            <span class="text-sm font-black text-on-surface-variant radio-text">Definir Data Manualmente</span>
                        </div>
                    </label>
                    <label class="relative cursor-pointer group">
                        <input class="peer sr-only custom-radio" name="modo_data" type="radio" value="auto" onchange="toggleModoData()" />
                        <div class="p-5 rounded-2xl bg-surface-container-low border-2 border-transparent transition-all flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant radio-icon">auto_awesome</span>
                            <span class="text-sm font-black text-on-surface-variant radio-text">Próxima Vaga Disponível</span>
                        </div>
                    </label>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-50" id="area-data-manual">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1">Data da Consulta *</label>
                        <?php 
                        $cal_id = 'cal-atendimento-diario';
                        $cal_name = 'data_consulta';
                        $cal_value = $dados['data_consulta'] ?? date('Y-m-d');
                        $cal_min = date('Y-m-d');
                        $cal_class = 'w-full';
                        require __DIR__ . '/../comum/calendario_dropdown.php'; 
                        ?>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1">Turno *</label>
                        <?php
                        $sel_id = 'sel-turno';
                        $sel_name = 'turno';
                        $sel_icon = 'light_mode';
                        $sel_placeholder = 'Manhã (08:00 - 13:00)';
                        $sel_value = 'manha';
                        $sel_required = true;
                        $sel_options = [
                            'manha' => ['label' => 'Manhã (08:00 - 13:00)', 'icon' => 'light_mode', 'color' => 'text-amber-500'],
                            'tarde' => ['label' => 'Tarde (13:00 - 18:00)', 'icon' => 'routine', 'color' => 'text-orange-500'],
                        ];
                        include __DIR__ . '/../comum/custom_select.php';
                        ?>
                    </div>
                </div>
                
                <div id="area-data-auto" class="hidden p-5 bg-surface-container-low rounded-2xl border border-black/10 text-center">
                    <p class="text-sm font-bold text-on-surface-variant mb-1">A procurar vaga automaticamente...</p>
                    <p class="text-xs font-bold text-on-surface" id="data-auto-resultado">Por favor seleccione o médico primeiro.</p>
                </div>
            <?php endif; ?>
            
            <?php if(!$mesmoDia): ?>
            <!-- Capacidade Badge -->
            <div id="info-capacidade" class="mt-6 hidden">
                <div class="flex items-center gap-2 p-3 rounded-xl inline-flex text-xs font-bold" id="cap-badge">
                    <span class="material-symbols-outlined text-lg" id="cap-icon">info</span>
                    <span id="cap-texto">A verificar lotação...</span>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- 5. Prioridade -->
        <section class="bg-white rounded-[2rem] p-8 floating-card border border-white mb-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                    <span class="material-symbols-outlined text-on-surface">priority_high</span>
                </div>
                <h3 class="text-xl font-black tracking-tight">Prioridade & Observações</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1">Nível de Prioridade *</label>
                    <?php
                    $sel_id = 'cs-prioridade-atd';
                    $sel_name = 'prioridade';
                    $sel_icon = 'check_circle';
                    $sel_placeholder = 'Fila Regular (Normal)';
                    $sel_value = '4';
                    $sel_required = true;
                    $sel_options = [
                        '4' => ['label' => 'Fila Regular (Normal)', 'icon' => 'check_circle', 'color' => 'text-blue-600'],
                        '3' => ['label' => 'Prioritário: Grávida', 'icon' => 'pregnant_woman', 'color' => 'text-purple-600'],
                        '2' => ['label' => 'Prioritário: Terceira Idade', 'icon' => 'elderly', 'color' => 'text-amber-500'],
                        '1' => ['label' => 'Atendimento Urgente', 'icon' => 'notification_important', 'color' => 'text-red-600'],
                    ];
                    include __DIR__ . '/../comum/custom_select.php';
                    ?>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1">Observações / Motivo</label>
                    <input type="text" name="observacoes" value="<?= htmlspecialchars($dados['observacoes'] ?? '') ?>" class="w-full h-14 px-5 bg-surface-container-low border-none rounded-2xl font-semibold text-sm focus:ring-2 focus:ring-black/10 transition-all" placeholder="Opcional..." />
                </div>
            </div>
        </section>

        <!-- 6. Contactos / Lembretes -->
        <section class="bg-white rounded-[2rem] p-8 floating-card border border-white mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-surface">notifications_active</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-black tracking-tight">Lembretes & Contactos</h3>
                        <p class="text-xs text-on-surface-variant font-bold mt-0.5">Contactos para enviar notificações automáticas</p>
                    </div>
                </div>
                <button type="button" onclick="adicionarContacto()" class="bg-surface-container-low text-on-surface px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-primary hover:text-white transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-base">add</span> Novo
                </button>
            </div>
            
            <div id="contactos-container" class="space-y-3">
                <!-- Vazio inicialmente, populado via JS -->
            </div>
            <div id="contactos-vazio" class="hidden text-center py-6 bg-surface-container-low/50 rounded-2xl border border-dashed border-black/10 text-xs font-bold text-on-surface-variant">
                Nenhum contacto associado para receber lembretes.
            </div>
        </section>

        <!-- Ações -->
        <div class="flex flex-col sm:flex-row justify-end gap-4" id="form-actions">
            <a href="agenda.php" class="px-8 py-4 rounded-xl font-black text-sm bg-surface-container-low text-on-surface hover:bg-surface-container transition-colors text-center shadow-sm">
                Cancelar
            </a>
            <button type="submit" id="btn-submit" class="bg-primary text-white px-8 py-4 rounded-xl font-black text-sm hover:scale-[1.02] transition-transform shadow-lg flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]"><?= $mesmoDia ? 'assignment_turned_in' : 'event_available' ?></span>
                <span id="btn-text"><?= $mesmoDia ? 'Confirmar & Ir para Check-in' : 'Agendar Consulta' ?></span>
            </button>
        </div>

    </form>

    <!-- Painel de Sucesso (escondido inicialmente) -->
    <section class="bg-white rounded-[2rem] p-10 floating-card border border-white text-center flex flex-col items-center justify-center opacity-0 pointer-events-none absolute inset-0 transition-all duration-500 translate-y-8" id="section-sucesso">
        <div class="w-24 h-24 rounded-full bg-green-50 flex items-center justify-center mb-6 shadow-sm border border-green-100">
            <span class="material-symbols-outlined text-green-500 text-5xl font-bold">check_circle</span>
        </div>
        <h3 class="text-3xl font-headline font-extrabold text-on-surface tracking-tight mb-2">Consulta Agendada!</h3>
        <p class="text-on-surface-variant font-medium text-sm max-w-sm mb-6">A marcação foi registada e a senha foi gerada automaticamente.</p>
        
        <!-- Senha em destaque -->
        <div class="bg-surface-container-low rounded-2xl px-10 py-6 mb-8 border border-black/5 inline-block">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-on-surface-variant mb-2">Senha do Paciente</p>
            <p class="text-5xl font-headline font-extrabold text-on-surface tracking-tight" id="sucesso-senha-codigo">---</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
            <a href="agenda.php" class="group p-6 bg-surface-container-low rounded-2xl hover:bg-primary hover:text-white transition-all text-left flex flex-col justify-between border border-transparent">
                <div>
                    <span class="material-symbols-outlined text-on-surface group-hover:text-white mb-4 text-3xl">calendar_month</span>
                    <h4 class="text-lg font-black tracking-tight mb-1">Ir para a Agenda</h4>
                    <p class="text-xs text-on-surface-variant group-hover:text-white/70 font-semibold leading-relaxed">Consultar marcações do dia e fazer triagens.</p>
                </div>
                <span class="material-symbols-outlined self-end mt-4 text-on-surface group-hover:text-white">arrow_forward</span>
            </a>
            
            <button type="button" onclick="window.location.reload()" class="group p-6 bg-surface-container-low rounded-2xl hover:bg-primary hover:text-white transition-all text-left flex flex-col justify-between border border-transparent">
                <div>
                    <span class="material-symbols-outlined text-on-surface group-hover:text-white mb-4 text-3xl">add_circle</span>
                    <h4 class="text-lg font-black tracking-tight mb-1">Nova Marcação</h4>
                    <p class="text-xs text-on-surface-variant group-hover:text-white/70 font-semibold leading-relaxed">Agendar outra consulta para outro paciente.</p>
                </div>
                <span class="material-symbols-outlined self-end mt-4 text-on-surface group-hover:text-white">arrow_forward</span>
            </button>
        </div>
    </section>

</main>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
let contactoIdx = 0;
const mesmoDia = <?= $mesmoDia ? 'true' : 'false' ?>;
let pacienteIdInicial = <?= $pacienteIdGet ?>;

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
            if(!d.resultados.length){
                divResult.innerHTML='<div class="px-5 py-4 text-xs font-bold text-on-surface-variant">Nenhum paciente encontrado.</div>';
                divResult.classList.remove('hidden');
                return;
            }
            divResult.innerHTML = d.resultados.map(p=>
                `<div class="px-5 py-3 hover:bg-surface-container-low cursor-pointer transition-colors border-b border-surface-container-low/50 last:border-0" onclick="seleccionarPaciente(${p.id},'${p.nome.replace(/'/g,"\\'")}','${p.bi_nif||''}','${p.idade||''}')">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-sm text-on-surface">${p.nome}</span>
                        <span class="text-xs font-bold text-on-surface-variant bg-surface-container px-2 py-1 rounded-md">${p.idade||'?'} anos</span>
                    </div>
                    <div class="text-[10px] text-on-surface-variant font-bold mt-1 uppercase tracking-widest">${p.bi_nif ? 'BI: '+p.bi_nif : ''}</div>
                </div>`
            ).join('');
            divResult.classList.remove('hidden');
        });
    }, 300);
});

function seleccionarPaciente(id, nome, bi, idade){
    document.getElementById('paciente-id').value = id;
    document.getElementById('paciente-nome-display').textContent = nome;
    document.getElementById('paciente-extra').textContent = (bi ? 'BI: ' + bi : 'S/ BI') + (idade ? ' • ' + idade + ' anos' : '');
    document.getElementById('paciente-iniciais').textContent = nome.charAt(0).toUpperCase();
    
    document.getElementById('paciente-info').classList.remove('hidden');
    document.getElementById('container-pesquisa').classList.add('hidden');
    divResult.classList.add('hidden');
    
    carregarContactosPaciente(id);
}

function limparPaciente(){
    document.getElementById('paciente-id').value = '';
    document.getElementById('paciente-info').classList.add('hidden');
    document.getElementById('container-pesquisa').classList.remove('hidden');
    inputPesq.value = '';
    inputPesq.focus();
    
    document.getElementById('contactos-container').innerHTML = '';
    document.getElementById('contactos-vazio').classList.remove('hidden');
    contactoIdx = 0;
}

document.addEventListener('click', e => {
    if(!inputPesq.contains(e.target) && !divResult.contains(e.target)) divResult.classList.add('hidden');
});

// ==========================================
// 2. CARREGAR PACIENTE INICIAL (SE HOUVER)
// ==========================================
if(pacienteIdInicial > 0) {
    fetch(BASE+'app/controllers/agenda_api.php?acao=obter_paciente&paciente_id='+pacienteIdInicial)
    .then(r=>r.json()).then(d=>{
        if(d.paciente) {
            seleccionarPaciente(d.paciente.id, d.paciente.nome, d.paciente.bi_nif, d.paciente.idade);
        }
    });
}

// ==========================================
// 3. ESPECIALIDADE -> MÉDICOS
// ==========================================
const selEsp = document.getElementById('sel-especialidade');
const contMedicos = document.getElementById('container-medicos');
const loadingMed = document.getElementById('medicos-loading');

function carregarMedicos() {
    let espId = selEsp.value;
    
    if(!espId) {
        contMedicos.innerHTML = `<div class="col-span-full text-center py-6 text-on-surface-variant text-sm font-bold bg-surface-container-low rounded-2xl border border-dashed border-black/10">Seleccione a Especialidade para ver os médicos disponíveis.</div>`;
        return;
    }
    
    loadingMed.classList.remove('hidden');
    contMedicos.style.opacity = '0.5';
    
    fetch(BASE+'app/controllers/agenda_api.php?acao=medicos_da_especialidade&especialidade_id='+espId)
    .then(r=>r.json()).then(d=>{
        loadingMed.classList.add('hidden');
        contMedicos.style.opacity = '1';
        
        if(!d.medicos || !d.medicos.length) {
            contMedicos.innerHTML = `<div class="col-span-full text-center py-6 text-error text-sm font-bold bg-error-container rounded-2xl">Nenhum médico registado para esta especialidade.</div>`;
            return;
        }
        
        contMedicos.innerHTML = d.medicos.map(m=>
            `<label class="relative cursor-pointer group">
                <input class="peer sr-only custom-radio" name="medico_seleccao" type="radio" value="${m.id}" data-cons="${m.consultorio_id||''}" onchange="seleccionarMedico(this)" />
                <div class="p-4 rounded-2xl bg-surface-container-low border-2 border-transparent hover:border-black/20 transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-on-surface font-black border border-black/10">
                            Dr
                        </div>
                        <div>
                            <p class="text-sm font-black text-on-surface leading-tight">${m.nome}</p>
                            <p class="text-[10px] text-on-surface-variant font-bold uppercase tracking-widest mt-0.5">${m.especialidade_nome || 'Clínico Geral'} • ${m.consultorio_nome || 'Sem Consultório Base'}</p>
                        </div>
                        <span class="material-symbols-outlined ml-auto text-on-surface opacity-0 peer-checked:opacity-100 radio-icon">check_circle</span>
                    </div>
                </div>
            </label>`
        ).join('');
    });
}

selEsp.addEventListener('change', carregarMedicos);

function seleccionarMedico(radio) {
    document.getElementById('medico-id').value = radio.value;
    document.getElementById('consultorio-id').value = radio.dataset.cons || '';
    if(!mesmoDia) checkCapacidade();
}

// ==========================================
// 4. DATA E CAPACIDADE
// ==========================================
function toggleModoData() {
    const modo = document.querySelector('input[name="modo_data"]:checked')?.value;
    const manual = document.getElementById('area-data-manual');
    const auto = document.getElementById('area-data-auto');
    
    if(modo === 'manual') {
        manual.classList.remove('hidden');
        auto.classList.add('hidden');
        checkCapacidade();
    } else {
        manual.classList.add('hidden');
        auto.classList.remove('hidden');
        procurarProximaData();
    }
}

function procurarProximaData() {
    const medicoId = document.getElementById('medico-id').value;
    const result = document.getElementById('data-auto-resultado');
    if(!medicoId) {
        result.textContent = 'Por favor seleccione o médico primeiro.';
        return;
    }
    // Simplificação: apenas avisar que vai agendar logo para amanhã, 
    // ou podíamos chamar o endpoint de próxima_data.
    result.textContent = 'O sistema alocará automaticamente a próxima vaga livre a partir de amanhã.';
    document.getElementById('info-capacidade')?.classList.add('hidden');
}

function checkCapacidade() {
    if(mesmoDia) return;
    const modo = document.querySelector('input[name="modo_data"]:checked')?.value;
    if(modo === 'auto') return;
    
    const med = document.getElementById('medico-id').value;
    const data = document.getElementById('sel-data')?.value;
    const turno = document.getElementById('sel-turno')?.value || 'manha';
    
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
document.getElementById('sel-data')?.addEventListener('change', checkCapacidade);
document.getElementById('sel-turno')?.addEventListener('change', checkCapacidade);

// ==========================================
// 5. CONTACTOS
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
    
    const checked = consent == 1 ? 'checked' : (tipo === 'whatsapp' ? 'checked' : '');
    
    c.insertAdjacentHTML('beforeend',`
        <div class="p-4 bg-surface-container-low rounded-xl border border-black/5" id="contacto-${i}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-3">
                    <label class="block text-[9px] font-black uppercase tracking-widest text-on-surface-variant mb-1 ml-1">Tipo</label>
                    <select name="contactos[${i}][tipo]" class="w-full rounded-xl border-none bg-white px-3 py-2 text-xs font-bold shadow-sm">
                        <option value="telefone" ${tipo==='telefone'?'selected':''}>Telefone</option>
                        <option value="whatsapp" ${tipo==='whatsapp'?'selected':''}>WhatsApp</option>
                        <option value="email" ${tipo==='email'?'selected':''}>Email</option>
                        <option value="emergencia" ${tipo==='emergencia'?'selected':''}>Emergência</option>
                    </select>
                </div>
                <div class="md:col-span-4">
                    <label class="block text-[9px] font-black uppercase tracking-widest text-on-surface-variant mb-1 ml-1">Contacto / Destino</label>
                    <input type="text" name="contactos[${i}][valor]" value="${valor}" class="w-full rounded-xl border-none bg-white px-3 py-2 text-xs font-bold shadow-sm" placeholder="Ex: 9xx xxx xxx" required>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-[9px] font-black uppercase tracking-widest text-on-surface-variant mb-1 ml-1">Nome (Se emergência)</label>
                    <input type="text" name="contactos[${i}][nome_contacto]" value="${nome}" class="w-full rounded-xl border-none bg-white px-3 py-2 text-xs font-bold shadow-sm" placeholder="Opcional">
                </div>
                <div class="md:col-span-2 flex items-center justify-between gap-2 pb-1">
                    <label class="flex items-center gap-1.5 text-[10px] font-bold cursor-pointer">
                        <input type="checkbox" name="contactos[${i}][consentimento]" value="1" ${checked} class="rounded text-on-surface border-black/30 focus:ring-black"> 
                        Lembretes
                    </label>
                    <button type="button" onclick="document.getElementById('contacto-${i}').remove()" class="text-error hover:text-error/80 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </div>
            </div>
        </div>
    `);
}

// Form submission validation & AJAX
document.getElementById('form-marcacao').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if(!document.getElementById('paciente-id').value) {
        alert('Por favor seleccione um paciente primeiro.');
        return;
    }
    if(!document.getElementById('medico-id').value) {
        alert('Por favor seleccione o médico responsável.');
        return;
    }
    
    const btnSubmit = document.getElementById('btn-submit');
    const originalText = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> A processar...';
    
    const fd = new FormData(this);
    fd.append('ajax', '1');
    
    fetch(this.action, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            // Hide form elements
            document.querySelectorAll('form > section, #form-actions').forEach(el => {
                el.style.opacity = '0';
                setTimeout(() => el.style.display = 'none', 300);
            });
            
            // Setup success panel with senha
            const successPanel = document.getElementById('section-sucesso');
            document.getElementById('sucesso-senha-codigo').textContent = d.senha_codigo || '---';
            
            // Show success panel
            setTimeout(() => {
                successPanel.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-8');
            }, 300);
            
        } else {
            alert('Erro: ' + (d.erros ? d.erros.join('
') : 'Falha desconhecida.'));
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }
    })
    .catch(err => {
        alert('Erro de rede ao processar marcação.');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalText;
    });
});
</script>
</body>
</html>
