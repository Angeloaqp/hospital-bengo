<?php
// ================================================
// Hospital Geral do Bengo — Agenda da Recepção
// ================================================
require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Marcacao.php';
require_once __DIR__ . '/../../../app/models/Disponibilidade.php';
require_once __DIR__ . '/../../../app/models/Notificacao.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

exigirPerfil(['recepcionista', 'admin']);
$meuPerfilObject = Utilizador::obter((int) sessao('utilizador_id'));

$dataInicio = trim($_GET['data'] ?? date('Y-m-d'));
$dataFim = date('Y-m-d', strtotime("$dataInicio + 6 days"));
$dataFiltro = $dataInicio;

$turnoFiltro = trim($_GET['turno'] ?? '');
$medicoFiltro = !empty($_GET['medico_id']) ? (int) $_GET['medico_id'] : null;
$especialidadeFiltro = !empty($_GET['especialidade_id']) ? (int) $_GET['especialidade_id'] : null;
$estadoFiltro = trim($_GET['estado'] ?? '');
$checkinId = (int) ($_GET['checkin'] ?? 0);

$agendaRaw = Marcacao::listarAgendaIntervalo($dataInicio, $dataFim, $medicoFiltro, $especialidadeFiltro, $estadoFiltro ?: null, $turnoFiltro ?: null);

// Agrupar por data para a grelha
$agendaSemana = [];
$diasSemana = [];
for ($i = 0; $i < 7; $i++) {
    $d = date('Y-m-d', strtotime("$dataInicio + $i days"));
    $diasSemana[] = $d;
    $agendaSemana[$d] = [];
}

foreach ($agendaRaw as $m) {
    $data = $m['data_consulta'];
    if (isset($agendaSemana[$data])) {
        $agendaSemana[$data][] = $m;
    }
}

$estatsDia = Marcacao::estatisticasDia($dataFiltro);
$medicos = Disponibilidade::listarMedicos();
$especialidades = Disponibilidade::listarEspecialidades();
$falhasNotif = Notificacao::listarFalhasRecentes(5);

$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
$ultimaSenha = $_SESSION['ultima_senha'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro'], $_SESSION['ultima_senha']);

$prioLabels = [1=>'Urgente',2=>'Idoso',3=>'Grávida',4=>'Normal'];
$prioBadge = [1=>'bg-red-600',2=>'bg-amber-500',3=>'bg-purple-600',4=>'bg-blue-600'];
$estadoBadge = [
    'marcada'=>'bg-blue-100 text-blue-700','confirmada'=>'bg-green-100 text-green-700',
    'em_atendimento'=>'bg-yellow-100 text-yellow-700','concluida'=>'bg-gray-100 text-gray-500',
    'cancelada'=>'bg-red-100 text-red-600','falta'=>'bg-orange-100 text-orange-600',
    'remarcada'=>'bg-indigo-100 text-indigo-600',
];
$turnoLabel = ['manha'=>'Manhã','tarde'=>'Tarde'];
?>
<!DOCTYPE html>
<html lang="pt"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Agenda — <?= APP_NOME ?></title>
<?php include __DIR__ . '/../comum/head_assets.php'; ?>
<style>
.floating-card{box-shadow:0 4px 20px -2px rgba(0,0,0,.05),0 2px 10px -2px rgba(0,0,0,.03)}
</style>
</head>
<body class="text-on-surface bg-surface-container-low">
<?php $paginaActual='agenda'; include __DIR__.'/../comum/sidebar.php'; ?>
<?php $tituloPagina='Agenda'; $subtituloPagina=''; $accoesPagina=''; include __DIR__.'/../comum/header.php'; ?>

<div id="alertas-iniciais" style="display:none"
    data-mensagem="<?= htmlspecialchars($mensagem) ?>"
    data-erro="<?= htmlspecialchars($erro) ?>"
    data-senha="<?= htmlspecialchars($ultimaSenha) ?>"></div>

<div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">

<!-- Header + Ações -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 fade-in">
    <div>
        <h2 class="text-3xl font-extrabold text-black tracking-tight">Agenda do Dia</h2>
        <p class="text-on-surface-variant font-semibold mt-1 text-sm"><?= dataFormatoPT($dataFiltro) ?></p>
    </div>
    <div class="flex gap-3">
        <a href="marcacao.php" class="bg-primary text-white px-6 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 hover:scale-[1.02] transition-transform shadow-md no-underline">
            <span class="material-symbols-outlined text-[18px]">add</span> Nova Marcação
        </a>
        <a href="marcacao.php?origem=mesmo_dia" class="bg-white text-black px-6 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 hover:scale-[1.02] transition-transform shadow-md no-underline border border-primary/10">
            <span class="material-symbols-outlined text-[18px]">bolt</span> Mesmo Dia
        </a>
    </div>
</div>

<!-- Métricas -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6 fade-in-delay-1">
    <?php
    $metricas = [
        ['label'=>'Total','valor'=>$estatsDia['total']??0,'cor'=>'text-black'],
        ['label'=>'Marcadas','valor'=>$estatsDia['marcadas']??0,'cor'=>'text-blue-600'],
        ['label'=>'Check-in','valor'=>$estatsDia['confirmadas']??0,'cor'=>'text-green-600'],
        ['label'=>'Concluídas','valor'=>$estatsDia['concluidas']??0,'cor'=>'text-gray-500'],
        ['label'=>'Faltas','valor'=>$estatsDia['faltas']??0,'cor'=>'text-orange-600'],
    ];
    foreach($metricas as $m): ?>
    <div class="bg-white px-5 py-4 rounded-[1.5rem] floating-card border border-white">
        <p class="text-on-surface-variant font-bold uppercase tracking-widest text-[10px]"><?= $m['label'] ?></p>
        <p class="text-3xl font-extrabold <?= $m['cor'] ?> mt-1"><?= $m['valor'] ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtros -->
<form id="filtro-agenda" class="bg-white/90 backdrop-blur-md rounded-[1.5rem] p-3 px-4 floating-card border border-white mb-6 sticky top-24 z-[90] fade-in-delay-2 shadow-lg">
<div class="flex flex-wrap xl:flex-nowrap gap-2 lg:gap-3 items-end justify-center w-full">
    <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Data</label>
    <?php 
    $cal_id = 'cal-filtro';
    $cal_name = 'data';
    $cal_value = $dataFiltro;
    $cal_onchange = 'atualizarAgenda()';
    require __DIR__ . '/../comum/calendario_dropdown.php'; 
    ?>
    </div>
    <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Turno</label>
    <?php
    $sel_id = 'cs-turno';
    $sel_name = 'turno';
    $sel_icon = 'schedule';
    $sel_placeholder = 'Todos';
    $sel_value = $turnoFiltro;
    $sel_onchange = 'atualizarAgenda()';
    $sel_size = 'sm';
    $sel_options = [
        '' => ['label' => 'Todos', 'icon' => 'filter_list', 'color' => 'text-on-surface-variant'],
        'manha' => ['label' => 'Manhã', 'icon' => 'light_mode', 'color' => 'text-amber-500'],
        'tarde' => ['label' => 'Tarde', 'icon' => 'routine', 'color' => 'text-orange-500'],
    ];
    include __DIR__ . '/../comum/custom_select.php';
    ?></div>
    <div>
        <label class="text-[11px] font-black uppercase tracking-widest text-primary flex items-center gap-1 mb-1 bg-primary/10 px-2 py-0.5 rounded-md w-max">
            <span class="material-symbols-outlined text-[14px]">medical_services</span>
            Especialidade
        </label>
    <?php
    $sel_id = 'cs-especialidade';
    $sel_name = 'especialidade_id';
    $sel_icon = 'medical_services';
    $sel_placeholder = 'Todas';
    $sel_value = (string)$especialidadeFiltro;
    $sel_onchange = 'atualizarAgenda()';
    $sel_size = 'sm';
    $sel_options = ['' => ['label' => 'Todas', 'icon' => 'category', 'color' => 'text-on-surface-variant']];
    foreach($especialidades as $esp) {
        $sel_options[(string)$esp['id']] = ['label' => htmlspecialchars($esp['nome']), 'icon' => 'medical_services', 'color' => 'text-indigo-600'];
    }
    include __DIR__ . '/../comum/custom_select.php';
    ?></div>
    <div>
        <label class="text-[11px] font-black uppercase tracking-widest text-primary flex items-center gap-1 mb-1 bg-primary/10 px-2 py-0.5 rounded-md w-max">
            <span class="material-symbols-outlined text-[14px]">stethoscope</span>
            Médico
        </label>
    <?php
    $sel_id = 'cs-medico';
    $sel_name = 'medico_id';
    $sel_icon = 'person';
    $sel_placeholder = 'Todos';
    $sel_value = (string)$medicoFiltro;
    $sel_onchange = 'atualizarAgenda()';
    $sel_size = 'sm';
    $sel_options = ['' => ['label' => 'Todos', 'icon' => 'groups', 'color' => 'text-on-surface-variant']];
    foreach($medicos as $med) {
        $sel_options[(string)$med['id']] = ['label' => htmlspecialchars($med['nome']), 'icon' => 'person', 'color' => 'text-blue-600'];
    }
    include __DIR__ . '/../comum/custom_select.php';
    ?></div>
    <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Estado</label>
    <?php
    $sel_id = 'cs-estado';
    $sel_name = 'estado';
    $sel_icon = 'info';
    $sel_placeholder = 'Todos';
    $sel_value = $estadoFiltro;
    $sel_onchange = 'atualizarAgenda()';
    $sel_size = 'sm';
    $sel_options = [
        '' => ['label' => 'Todos', 'icon' => 'filter_list', 'color' => 'text-on-surface-variant'],
        'marcada' => ['label' => 'Marcada', 'icon' => 'event', 'color' => 'text-blue-600'],
        'confirmada' => ['label' => 'Confirmada', 'icon' => 'check_circle', 'color' => 'text-green-600'],
        'em_atendimento' => ['label' => 'Em Atend.', 'icon' => 'pending', 'color' => 'text-yellow-600'],
        'concluida' => ['label' => 'Concluída', 'icon' => 'task_alt', 'color' => 'text-gray-500'],
        'cancelada' => ['label' => 'Cancelada', 'icon' => 'cancel', 'color' => 'text-red-600'],
        'falta' => ['label' => 'Falta', 'icon' => 'person_off', 'color' => 'text-orange-600'],
    ];
    include __DIR__ . '/../comum/custom_select.php';
    ?></div>
    
    <div class="flex gap-1.5 ml-0 lg:ml-2 pb-0.5">
        <button type="button" onclick="atualizarAgenda('?data=<?= date('Y-m-d', strtotime($dataInicio.' -7 days')) ?>&medico_id=<?= $medicoFiltro ?>&turno=<?= $turnoFiltro ?>&estado=<?= $estadoFiltro ?>')" class="bg-surface-container-low px-2 py-1.5 rounded-lg text-[11px] font-bold hover:bg-surface-container transition-colors whitespace-nowrap">← Anterior</button>
        <button type="button" onclick="atualizarAgenda('?data=<?= date('Y-m-d') ?>&medico_id=<?= $medicoFiltro ?>&turno=<?= $turnoFiltro ?>&estado=<?= $estadoFiltro ?>')" class="bg-primary text-white px-2 py-1.5 rounded-lg text-[11px] font-bold hover:scale-105 transition-transform whitespace-nowrap">Semana Atual</button>
        <button type="button" onclick="atualizarAgenda('?data=<?= date('Y-m-d', strtotime($dataInicio.' +7 days')) ?>&medico_id=<?= $medicoFiltro ?>&turno=<?= $turnoFiltro ?>&estado=<?= $estadoFiltro ?>')" class="bg-surface-container-low px-2 py-1.5 rounded-lg text-[11px] font-bold hover:bg-surface-container transition-colors whitespace-nowrap">Seguinte →</button>
    </div>
</div>
</form>

<!-- Calendário Semanal -->
<div id="calendario-container">
<div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white mb-6 fade-in-delay-3 overflow-hidden relative">
    <!-- Indicador de Carregamento -->
    <div id="loading-overlay" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
        <span class="material-symbols-outlined animate-spin text-primary text-4xl">autorenew</span>
    </div>
<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-headline font-extrabold tracking-tight">Semana de <?= dataFormatoPT($dataInicio) ?> a <?= dataFormatoPT($dataFim) ?></h3>
    <p class="text-on-surface-variant font-bold text-sm"><?= count($agendaRaw) ?> marcações</p>
</div>

<div class="overflow-x-auto pb-4">
<div class="grid grid-cols-7 gap-4 min-w-[1100px]">
    <?php 
    $diasNomes = ['Sun'=>'DOM','Mon'=>'SEG','Tue'=>'TER','Wed'=>'QUA','Thu'=>'QUI','Fri'=>'SEX','Sat'=>'SÁB'];
    foreach($diasSemana as $dia): 
        $marcacoesDia = $agendaSemana[$dia];
        $isHoje = ($dia === date('Y-m-d'));
        $diaStr = date('D', strtotime($dia));
        $nomeDia = $diasNomes[$diaStr];
    ?>
    <div class="flex flex-col border-r border-surface-container-low last:border-0 pr-4 last:pr-0">
        <!-- Cabecalho do Dia -->
        <div class="text-center mb-4 pb-2 border-b <?= $isHoje ? 'border-primary' : 'border-surface-container-low' ?>">
            <p class="text-[10px] font-black uppercase tracking-widest <?= $isHoje ? 'text-primary' : 'text-on-surface-variant' ?>">
                <?= $nomeDia ?>
            </p>
            <p class="text-xl font-extrabold <?= $isHoje ? 'text-primary' : 'text-black' ?>">
                <?= date('d/m', strtotime($dia)) ?>
            </p>
            <div class="flex justify-center mt-1">
                <?php 
                $qtdMarcacoes = count($marcacoesDia);
                if ($qtdMarcacoes === 0): ?>
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300" title="Livre / Sem Marcações"></span>
                <?php elseif ($qtdMarcacoes >= 15): ?>
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse" title="Agenda Cheia"></span>
                <?php else: ?>
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500" title="Vagas Disponíveis (<?= $qtdMarcacoes ?> agendada<?= $qtdMarcacoes > 1 ? 's' : '' ?>)"></span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Cartões de Marcações -->
        <div class="flex flex-col gap-3 pb-4">
            <?php if(empty($marcacoesDia)): ?>
                <p class="text-center text-xs text-on-surface-variant/50 mt-4 font-bold">Livre</p>
            <?php else: ?>
                <?php foreach($marcacoesDia as $m): 
                    $eb = $estadoBadge[$m['estado']] ?? 'bg-gray-100 text-gray-500';
                    $cardColor = 'bg-surface-container-low';
                    if($m['estado'] === 'confirmada') $cardColor = 'bg-green-50/70 border-green-100';
                    elseif($m['estado'] === 'em_atendimento') $cardColor = 'bg-yellow-50/70 border-yellow-100';
                    elseif($m['estado'] === 'concluida') $cardColor = 'bg-gray-50/70 border-gray-100';
                    elseif($m['estado'] === 'falta' || $m['estado'] === 'cancelada') $cardColor = 'bg-red-50/70 border-red-100';
                    else $cardColor = 'bg-blue-50/70 border-blue-100'; // marcada
                ?>
                    <div class="<?= $cardColor ?> rounded-2xl p-3 shadow-sm border border-black/5 hover:shadow-md hover:border-primary/30 transition-all cursor-pointer relative group flex flex-col" onclick='abrirDetalhes(<?= json_encode($m) ?>)'>
                        <!-- Top Info -->
                        <div class="flex justify-between items-start mb-2 gap-1">
                            <span class="px-1.5 py-0.5 <?= $eb ?> text-[7.5px] font-black rounded-md truncate max-w-[65%]">
                                <?= strtoupper($m['estado']) ?>
                            </span>
                            <span class="text-[10px] font-black text-on-surface-variant whitespace-nowrap shrink-0">
                                <?= !empty($m['hora_formatada']) ? $m['hora_formatada'] : ($m['turno'] === 'manha' ? 'Manhã' : 'Tarde') ?>
                            </span>
                        </div>
                        
                        <!-- Paciente -->
                        <h4 class="font-bold text-[12px] text-black leading-tight mb-1 line-clamp-2" title="<?= htmlspecialchars($m['paciente_nome']) ?>">
                            <?= htmlspecialchars($m['paciente_nome']) ?>
                        </h4>
                        
                        <!-- Medico e Especialidade -->
                        <p class="text-[9px] text-on-surface-variant font-medium leading-tight mb-3">
                            <span class="truncate block w-full"><?= htmlspecialchars($m['especialidade_nome']) ?></span>
                            <span class="truncate block w-full">Dr. <?= htmlspecialchars(explode(' ', $m['medico_nome'])[0]) ?></span>
                        </p>
                        
                        <!-- Bottom Info -->
                        <div class="mt-auto flex items-center justify-between gap-1 pt-2 border-t border-black/5">
                            <?php if(!empty($m['senha_codigo'])): ?>
                                <div class="bg-white/80 px-2 py-1 rounded-lg border border-black/5 flex-grow overflow-hidden text-center" title="<?= htmlspecialchars($m['senha_codigo']) ?>">
                                    <span class="text-[9px] font-black text-primary truncate block w-full">
                                        <?= htmlspecialchars($m['senha_codigo']) ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            
                            <?php if(!empty($m['triagem_id'])): ?>
                                <div class="shrink-0 w-6 h-6 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center" title="Triagem Efetuada">
                                    <span class="material-symbols-outlined text-[12px]">vital_signs</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</div>
</div>
</div>
<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 4px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); }
</style>

<!-- Falhas de Notificação -->
<?php if(!empty($falhasNotif)): ?>
<div class="bg-white rounded-[1.5rem] p-6 floating-card border border-orange-200 mb-6">
<h3 class="text-sm font-black tracking-tight mb-3 flex items-center gap-2 text-orange-600">
    <span class="material-symbols-outlined text-[18px]">warning</span> Notificações Falhadas
</h3>
<div class="space-y-2">
<?php foreach($falhasNotif as $fn): ?>
<div class="flex items-center justify-between bg-orange-50 rounded-xl px-4 py-2">
    <div class="text-xs"><strong><?= htmlspecialchars($fn['paciente_nome']) ?></strong> — <?= $fn['canal'] ?> → <?= htmlspecialchars($fn['destino']) ?>
    <span class="text-orange-500 text-[10px] ml-2"><?= htmlspecialchars(substr($fn['ultimo_erro']??'',0,60)) ?></span></div>
    <form method="POST" action="<?= BASE_URL ?>app/controllers/notificacoes.php" class="m-0">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="acao" value="reenviar"><input type="hidden" name="notificacao_id" value="<?= $fn['id'] ?>">
        <button class="bg-orange-600 text-white px-3 py-1 rounded-full text-[10px] font-bold hover:scale-105 transition-transform">Reenviar</button>
    </form>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

</main>
</div>

<!-- Modal Triagem -->
<div id="modal-triagem" class="fixed inset-0 bg-primary/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
<div class="bg-white rounded-[2rem] w-full max-w-2xl p-8 floating-card max-h-[90vh] overflow-y-auto border border-blue-100 shadow-2xl">
<div class="flex items-center gap-3 mb-2">
    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
        <span class="material-symbols-outlined">vital_signs</span>
    </div>
    <div>
        <h3 class="text-2xl font-black text-on-surface">Triagem Clínica</h3>
        <p class="text-sm text-on-surface-variant font-medium">Registo de sinais vitais e avaliação inicial do paciente</p>
    </div>
</div>
<hr class="border-surface-container-high my-6">

<form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" id="form-triagem">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
    <input type="hidden" name="acao" value="triagem">
    <input type="hidden" name="ajax" value="1">
    <input type="hidden" name="marcacao_id" id="triagem-marcacao-id" value="">
    
    <div class="space-y-6">
        <!-- Sintomas -->
        <div>
            <label class="text-[11px] font-black uppercase tracking-widest text-on-surface-variant flex items-center gap-1 mb-2">
                <span class="material-symbols-outlined text-[14px]">psychology</span> Sintomas / Queixa Principal
            </label>
            <textarea name="sintomas" rows="2" class="w-full rounded-2xl border-surface-container-highest px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all resize-none shadow-sm" placeholder="Ex: Paciente refere dores de cabeça intensas desde ontem..."></textarea>
        </div>

        <!-- Sinais Vitais -->
        <div class="bg-white/80 border border-blue-100 rounded-3xl p-5 shadow-sm">
            <h4 class="text-xs font-black uppercase tracking-widest text-blue-600 mb-4 flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">monitor_heart</span> Sinais Vitais
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1 block">Temperatura</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-on-surface-variant/50 text-[18px]">device_thermostat</span>
                        <input type="number" step="0.1" name="temperatura" class="w-full rounded-xl border-surface-container-highest pl-10 pr-10 py-2.5 text-sm font-bold focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="36.5">
                        <span class="absolute right-4 text-xs font-bold text-on-surface-variant/50">°C</span>
                    </div>
                </div>
                
                <div class="relative">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1 block">Pressão Arterial</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-on-surface-variant/50 text-[18px]">blood_pressure</span>
                        <input type="text" name="pressao_arterial" class="w-full rounded-xl border-surface-container-highest pl-10 py-2.5 text-sm font-bold focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="120/80">
                    </div>
                </div>
                
                <div class="relative">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1 block">Peso</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-on-surface-variant/50 text-[18px]">weight</span>
                        <input type="number" step="0.1" name="peso" class="w-full rounded-xl border-surface-container-highest pl-10 pr-10 py-2.5 text-sm font-bold focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="70">
                        <span class="absolute right-4 text-xs font-bold text-on-surface-variant/50">kg</span>
                    </div>
                </div>
                
                <div class="relative">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1 block">Frequência Cardíaca</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-on-surface-variant/50 text-[18px]">favorite</span>
                        <input type="number" name="frequencia_cardiaca" class="w-full rounded-xl border-surface-container-highest pl-10 pr-10 py-2.5 text-sm font-bold focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="72">
                        <span class="absolute right-4 text-xs font-bold text-on-surface-variant/50">bpm</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Observações -->
        <div>
            <label class="text-[11px] font-black uppercase tracking-widest text-on-surface-variant flex items-center gap-1 mb-2">
                <span class="material-symbols-outlined text-[14px]">edit_note</span> Observações da Triagem
            </label>
            <textarea name="observacoes_triagem" rows="2" class="w-full rounded-2xl border-surface-container-highest px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all resize-none shadow-sm" placeholder="Outras notas relevantes..."></textarea>
        </div>

        <!-- Prioridade -->
        <div class="bg-white/80 rounded-2xl p-4 border border-blue-100 shadow-sm">
            <label class="text-[11px] font-black uppercase tracking-widest text-blue-800 flex items-center gap-1 mb-2">
                <span class="material-symbols-outlined text-[14px]">flag</span> Prioridade Clínica
            </label>
            <div><?php
            $sel_id = 'cs-prioridade-triagem';
            $sel_name = 'prioridade_clinica';
            $sel_icon = 'check_circle';
            $sel_placeholder = 'Normal';
            $sel_value = '4';
            $sel_size = 'md';
            $sel_options = [
                '4' => ['label' => 'Normal', 'icon' => 'check_circle', 'color' => 'text-blue-600'],
                '3' => ['label' => 'Moderada', 'icon' => 'warning', 'color' => 'text-amber-500'],
                '2' => ['label' => 'Alta (Idoso/Grávida)', 'icon' => 'elderly', 'color' => 'text-orange-500'],
                '1' => ['label' => 'Urgente', 'icon' => 'notification_important', 'color' => 'text-red-600'],
            ];
            include __DIR__ . '/../comum/custom_select.php';
            ?></div>
        </div>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-3 mt-8">
        <button type="submit" class="flex-1 bg-blue-600 text-white py-3.5 rounded-2xl font-black text-sm hover:bg-blue-700 hover:scale-[1.02] transition-all shadow-md shadow-blue-500/20 flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span> Guardar Triagem
        </button>
        <button type="button" onclick="fecharTriagem()" class="sm:w-32 py-3.5 rounded-2xl font-bold text-sm bg-surface-container-lowest border border-surface-container-high text-on-surface hover:bg-surface-container-low transition-colors">Cancelar</button>
    </div>
</form>
</div>
</div>

<!-- Modal Remarcar -->
<div id="modal-remarcar" class="fixed inset-0 bg-primary/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
<div class="bg-white rounded-[2rem] w-full max-w-sm p-8 floating-card">
<h3 class="text-xl font-black mb-6">Remarcar Consulta</h3>
<form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
    <input type="hidden" name="acao" value="remarcar">
    <input type="hidden" name="marcacao_id" id="remarcar-marcacao-id" value="">
    <div class="space-y-4">
        <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Nova Data</label>
        <?php 
        $cal_id = 'cal-reagendar';
        $cal_name = 'nova_data';
        $cal_value = '';
        $cal_min = date('Y-m-d');
        $cal_label = 'Seleccione a nova data...';
        require __DIR__ . '/../comum/calendario_dropdown.php'; 
        ?></div>
        <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Novo Turno</label>
        <div class="mt-1"><?php
        $sel_id = 'cs-novo-turno';
        $sel_name = 'novo_turno';
        $sel_icon = 'light_mode';
        $sel_placeholder = 'Manhã';
        $sel_value = 'manha';
        $sel_required = true;
        $sel_size = 'sm';
        $sel_options = [
            'manha' => ['label' => 'Manhã', 'icon' => 'light_mode', 'color' => 'text-amber-500'],
            'tarde' => ['label' => 'Tarde', 'icon' => 'routine', 'color' => 'text-orange-500'],
        ];
        include __DIR__ . '/../comum/custom_select.php';
        ?></div></div>
    </div>
    <div class="flex gap-3 mt-6">
        <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-full font-black text-sm">Remarcar</button>
        <button type="button" onclick="document.getElementById('modal-remarcar').classList.add('hidden')" class="px-6 py-3 rounded-full font-bold text-sm bg-surface-container-low">Cancelar</button>
    </div>
</form>
</div>
</div>

<!-- Modal Detalhes -->
<div id="modal-detalhes" class="fixed inset-0 bg-primary/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
<div class="bg-white rounded-[2rem] w-full max-w-md p-8 floating-card shadow-2xl">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h3 class="text-xl font-black text-black" id="det-paciente">Nome</h3>
            <p class="text-sm font-bold text-on-surface-variant mt-1" id="det-info">Info</p>
        </div>
        <button onclick="fecharDetalhes()" class="text-on-surface-variant hover:text-black transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    
    <div class="bg-surface-container-low rounded-xl p-4 mb-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1">Data e Hora</p>
                <p class="text-sm font-bold text-black" id="det-data-hora">--</p>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1">Estado</p>
                <span id="det-estado" class="px-2 py-0.5 text-[10px] font-black rounded-full inline-block">--</span>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1">Tipo de Atendimento</p>
                <p class="text-sm font-bold text-black" id="det-tipo-atendimento">--</p>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1">Prioridade</p>
                <p class="text-sm font-bold text-black" id="det-prioridade">--</p>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1">Consultório</p>
                <p class="text-sm font-bold text-black" id="det-consultorio">--</p>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1">Senha</p>
                <p class="text-sm font-black text-primary" id="det-senha">--</p>
            </div>
        </div>
    </div>

    <!-- Triage info moved to separate modal -->

    <!-- Acções Dinâmicas -->
    <div id="det-accoes" class="flex flex-col gap-2">
        <!-- Preenchido via JS -->
    </div>
</div>
</div>

<!-- Modal Visualizar Triagem -->
<div id="modal-visualizar-triagem" class="fixed inset-0 bg-primary/40 backdrop-blur-sm z-[120] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2rem] w-full max-w-sm p-8 floating-card shadow-2xl">
        <div class="flex justify-between items-start mb-6">
            <h3 class="text-xl font-black text-black flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">vital_signs</span>
                Dados da Triagem
            </h3>
            <button onclick="fecharVisualizarTriagem()" class="text-on-surface-variant hover:text-black transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[9px] font-bold text-blue-600/70 uppercase">Pressão Art.</p>
                    <p class="text-sm font-black text-blue-900" id="det-triagem-pa">--</p>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-blue-600/70 uppercase">Temperatura</p>
                    <p class="text-sm font-black text-blue-900" id="det-triagem-temp">--</p>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-blue-600/70 uppercase">Peso</p>
                    <p class="text-sm font-black text-blue-900" id="det-triagem-peso">--</p>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-blue-600/70 uppercase">Freq. Cardíaca</p>
                    <p class="text-sm font-black text-blue-900" id="det-triagem-fc">--</p>
                </div>
                <div class="col-span-2">
                    <p class="text-[9px] font-bold text-blue-600/70 uppercase">Sintomas</p>
                    <p class="text-sm font-black text-blue-900" id="det-triagem-sintomas">--</p>
                </div>
            </div>
        </div>
        
        <button onclick="fecharVisualizarTriagem()" class="w-full bg-surface-container-low text-black px-4 py-3 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">
            Fechar
        </button>
    </div>
</div>

<!-- Modal Confirmacao -->
<div id="modal-confirmacao" class="fixed inset-0 bg-primary/40 backdrop-blur-sm z-[110] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2rem] w-full max-w-sm p-8 floating-card text-center shadow-2xl">
        <div id="confirmacao-icon-wrapper" class="w-20 h-20 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-5">
            <span id="confirmacao-icon" class="material-symbols-outlined text-4xl text-red-600">warning</span>
        </div>
        <h3 id="confirmacao-titulo" class="text-2xl font-black mb-2 text-on-surface">Cancelar?</h3>
        <p id="confirmacao-texto" class="text-sm text-on-surface-variant font-medium mb-8">Esta acção não poderá ser revertida.</p>
        <div class="flex gap-3">
            <button type="button" onclick="fecharConfirmacao()" class="flex-1 py-3.5 rounded-2xl font-bold text-sm bg-surface-container-lowest border border-surface-container-high text-on-surface hover:bg-surface-container-low transition-colors">Voltar</button>
            <button type="button" id="confirmacao-btn" class="flex-1 bg-red-600 text-white py-3.5 rounded-2xl font-black text-sm hover:bg-red-700 transition-colors shadow-md shadow-red-600/20">Confirmar</button>
        </div>
    </div>
</div>

<script>
const estadoBadgeClass = <?= json_encode($estadoBadge) ?>;
const prioLabels = <?= json_encode($prioLabels) ?>;

function abrirTriagem() {
    let m = window.marcacaoSelecionada;
    if(!m) return;
    
    document.getElementById('triagem-marcacao-id').value = m.id;
    
    // Preencher campos existentes
    document.querySelector('#form-triagem [name="sintomas"]').value = m.triagem_sintomas || '';
    document.querySelector('#form-triagem [name="temperatura"]').value = m.triagem_temperatura || '';
    document.querySelector('#form-triagem [name="pressao_arterial"]').value = m.triagem_pressao_arterial || '';
    document.querySelector('#form-triagem [name="peso"]').value = m.triagem_peso || '';
    document.querySelector('#form-triagem [name="frequencia_cardiaca"]').value = m.triagem_frequencia_cardiaca || '';
    document.querySelector('#form-triagem [name="observacoes_triagem"]').value = m.triagem_observacoes || '';
    
    let prio = m.triagem_prioridade || 4;
    let optBtn = document.querySelector('#cs-prioridade-triagem .cs-option[data-value="'+prio+'"]');
    if(optBtn) optBtn.click();

    document.getElementById('modal-triagem').classList.remove('hidden');
}

function fecharTriagem(){document.getElementById('modal-triagem').classList.add('hidden')}
function abrirRemarcar(id){document.getElementById('remarcar-marcacao-id').value=id;document.getElementById('modal-remarcar').classList.remove('hidden')}

function fecharDetalhes() {
    document.getElementById('modal-detalhes').classList.add('hidden');
}

let formParaSubmeter = null;

function confirmarAcao(acao, id) {
    let titulo, texto, iconWrapperClass, iconClass, btnClass, iconName;
    
    if (acao === 'falta') {
        titulo = "Registar Falta?";
        texto = "O paciente será marcado como ausente.";
        iconWrapperClass = "bg-orange-100";
        iconClass = "text-orange-600";
        btnClass = "bg-orange-600 hover:bg-orange-700 shadow-orange-600/20";
        iconName = "person_off";
        formParaSubmeter = document.getElementById('form-falta-' + id);
    } else if (acao === 'cancelar') {
        titulo = "Cancelar Marcação?";
        texto = "Esta acção não poderá ser revertida.";
        iconWrapperClass = "bg-red-100";
        iconClass = "text-red-600";
        btnClass = "bg-red-600 hover:bg-red-700 shadow-red-600/20";
        iconName = "cancel";
        formParaSubmeter = document.getElementById('form-cancelar-' + id);
    }
    
    document.getElementById('confirmacao-icon-wrapper').className = `w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-5 ${iconWrapperClass}`;
    document.getElementById('confirmacao-icon').className = `material-symbols-outlined text-4xl ${iconClass}`;
    document.getElementById('confirmacao-icon').textContent = iconName;
    
    document.getElementById('confirmacao-titulo').textContent = titulo;
    document.getElementById('confirmacao-texto').textContent = texto;
    
    document.getElementById('confirmacao-btn').className = `flex-1 text-white py-3.5 rounded-2xl font-black text-sm transition-colors shadow-md ${btnClass}`;
    
    document.getElementById('modal-confirmacao').classList.remove('hidden');
}

function fecharConfirmacao() {
    document.getElementById('modal-confirmacao').classList.add('hidden');
    formParaSubmeter = null;
}

function abrirVisualizarTriagem() {
    if (!window.marcacaoSelecionada || !window.marcacaoSelecionada.triagem_id) return;
    let m = window.marcacaoSelecionada;
    
    document.getElementById('det-triagem-pa').textContent = m.triagem_pressao_arterial ? m.triagem_pressao_arterial + ' mmHg' : '—';
    document.getElementById('det-triagem-temp').textContent = m.triagem_temperatura ? m.triagem_temperatura + ' °C' : '—';
    document.getElementById('det-triagem-peso').textContent = m.triagem_peso ? m.triagem_peso + ' kg' : '—';
    document.getElementById('det-triagem-fc').textContent = m.triagem_frequencia_cardiaca ? m.triagem_frequencia_cardiaca + ' bpm' : '—';
    document.getElementById('det-triagem-sintomas').textContent = m.triagem_sintomas || '—';
    
    document.getElementById('modal-visualizar-triagem').classList.remove('hidden');
}

function fecharVisualizarTriagem() {
    document.getElementById('modal-visualizar-triagem').classList.add('hidden');
}

document.getElementById('confirmacao-btn').addEventListener('click', function() {
    if (formParaSubmeter) {
        formParaSubmeter.submit();
    }
});

function abrirDetalhes(m) {
    window.marcacaoSelecionada = m;
    
    document.getElementById('det-paciente').textContent = m.paciente_nome + ' (' + m.paciente_idade + 'a)';
    document.getElementById('det-info').textContent = m.especialidade_nome + ' • Dr. ' + m.medico_nome;
    
    let hora = m.hora_formatada ? m.hora_formatada : (m.turno === 'manha' ? 'Manhã' : 'Tarde');
    document.getElementById('det-data-hora').textContent = m.data_consulta.split('-').reverse().join('/') + ' às ' + hora;
    
    let ebClass = estadoBadgeClass[m.estado] || 'bg-gray-100 text-gray-500';
    let estadoEl = document.getElementById('det-estado');
    estadoEl.className = 'px-2 py-0.5 text-[10px] font-black rounded-full inline-block ' + ebClass;
    estadoEl.textContent = m.estado.toUpperCase();
    
    document.getElementById('det-tipo-atendimento').textContent = m.tipo_atendimento_nome || '—';
    document.getElementById('det-consultorio').textContent = m.consultorio_nome || '—';
    document.getElementById('det-prioridade').textContent = prioLabels[m.prioridade] || 'Normal';
    document.getElementById('det-senha').textContent = m.senha_codigo || '—';
    
    let accoesHtml = '';
    
    if (m.estado === 'confirmada' || m.estado === 'marcada') {
        if (m.triagem_id) {
            accoesHtml += `<button type="button" onclick="abrirVisualizarTriagem()" class="w-full bg-blue-50 border border-blue-100 text-blue-700 px-4 py-3 rounded-xl text-sm font-black hover:bg-blue-100 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">visibility</span> Mostrar Dados da Triagem
            </button>`;
            
            accoesHtml += `<button type="button" onclick="fecharDetalhes(); abrirTriagem()" class="w-full bg-blue-600 text-white px-4 py-3 rounded-xl text-sm font-black hover:scale-[1.02] transition-transform flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">edit_square</span> Editar Triagem
            </button>`;
        } else {
            accoesHtml += `<button onclick="fecharDetalhes(); abrirTriagem()" class="w-full bg-blue-600 text-white px-4 py-3 rounded-xl text-sm font-black hover:scale-[1.02] transition-transform flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">vital_signs</span> Fazer Triagem
            </button>`;
        }
        
        accoesHtml += `<button onclick="fecharDetalhes(); abrirRemarcar(${m.id})" class="w-full bg-surface-container-low text-black px-4 py-3 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">
            Remarcar Consulta
        </button>`;
        
        if (m.estado === 'confirmada') {
            accoesHtml += `<form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" class="m-0" id="form-falta-${m.id}">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="falta"><input type="hidden" name="marcacao_id" value="${m.id}">
                <button type="button" onclick="confirmarAcao('falta', ${m.id})" class="w-full bg-orange-100 text-orange-600 px-4 py-3 rounded-xl text-sm font-bold hover:bg-orange-200 transition-colors">Registar Falta</button>
            </form>`;
        }
        
        accoesHtml += `<form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" class="m-0" id="form-cancelar-${m.id}">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
            <input type="hidden" name="acao" value="cancelar"><input type="hidden" name="marcacao_id" value="${m.id}">
            <input type="hidden" name="motivo" value="Cancelamento pela recepção">
            <button type="button" onclick="confirmarAcao('cancelar', ${m.id})" class="w-full bg-red-100 text-red-600 px-4 py-3 rounded-xl text-sm font-bold hover:bg-red-200 transition-colors">Cancelar Marcação</button>
        </form>`;
    } else {
        accoesHtml += `<p class="text-center text-xs text-on-surface-variant font-bold">Nenhuma ação disponível para o estado atual.</p>`;
    }
    
    document.getElementById('det-accoes').innerHTML = accoesHtml;
    document.getElementById('modal-detalhes').classList.remove('hidden');
}

function atualizarAgenda(url = null) {
    let form = document.getElementById('filtro-agenda');
    let overlay = document.getElementById('loading-overlay');
    let container = document.getElementById('calendario-container');
    
    // Mostra indicador de carregamento, se existir
    if(overlay) overlay.classList.remove('hidden');
    else container.style.opacity = '0.5';

    let fetchUrl = url;
    if (!fetchUrl) {
        let formData = new FormData(form);
        let queryString = new URLSearchParams(formData).toString();
        fetchUrl = '?' + queryString;
    }

    // Atualiza a URL no histórico (sem recarregar a página)
    window.history.pushState({}, '', fetchUrl);

    fetch(fetchUrl)
        .then(response => response.text())
        .then(html => {
            let parser = new DOMParser();
            let doc = parser.parseFromString(html, 'text/html');
            let novoCalendario = doc.getElementById('calendario-container');
            
            if (novoCalendario) {
                container.innerHTML = novoCalendario.innerHTML;
            } else {
                console.error("Não foi possível encontrar #calendario-container na resposta.");
                if(overlay) overlay.classList.add('hidden');
                else container.style.opacity = '1';
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar agenda:', error);
            if(overlay) overlay.classList.add('hidden');
            else container.style.opacity = '1';
        });
}

// Submeter Triagem via AJAX
document.getElementById('form-triagem').addEventListener('submit', function(e) {
    e.preventDefault();
    let btn = this.querySelector('button[type="submit"]');
    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">autorenew</span> Guardando...';
    btn.disabled = true;

    fetch(this.action, {
        method: 'POST',
        body: new FormData(this)
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        
        if (data.status === 'success') {
            fecharTriagem();
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: data.mensagem,
                timer: 2000,
                showConfirmButton: false
            });
            atualizarAgenda(); // recarregar apenas o calendário
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: data.erro || 'Ocorreu um erro ao guardar a triagem.'
            });
        }
    })
    .catch(err => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Falha de comunicação com o servidor.'
        });
    });
});
</script>
<script src="<?= BASE_URL ?>public/assets/js/fila.js"></script>
</body></html>
