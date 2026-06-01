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
$estadoFiltro = trim($_GET['estado'] ?? '');
$checkinId = (int) ($_GET['checkin'] ?? 0);

$agendaRaw = Marcacao::listarAgendaIntervalo($dataInicio, $dataFim, $medicoFiltro, null, $estadoFiltro ?: null, $turnoFiltro ?: null);

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
<form id="filtro-agenda" class="bg-white/90 backdrop-blur-md rounded-[1.5rem] p-5 floating-card border border-white mb-6 sticky top-24 z-[90] fade-in-delay-2 shadow-lg">
<div class="flex flex-wrap gap-3 items-end">
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
            <span class="material-symbols-outlined text-[14px]">stethoscope</span>
            Filtrar por Médico
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
        'em_atendimento' => ['label' => 'Em Atendimento', 'icon' => 'pending', 'color' => 'text-yellow-600'],
        'concluida' => ['label' => 'Concluída', 'icon' => 'task_alt', 'color' => 'text-gray-500'],
        'cancelada' => ['label' => 'Cancelada', 'icon' => 'cancel', 'color' => 'text-red-600'],
        'falta' => ['label' => 'Falta', 'icon' => 'person_off', 'color' => 'text-orange-600'],
    ];
    include __DIR__ . '/../comum/custom_select.php';
    ?></div>
    <div class="flex gap-2">
    <div class="flex gap-2">
        <button type="button" onclick="atualizarAgenda('?data=<?= date('Y-m-d', strtotime($dataInicio.' -7 days')) ?>&medico_id=<?= $medicoFiltro ?>&turno=<?= $turnoFiltro ?>&estado=<?= $estadoFiltro ?>')" class="bg-surface-container-low px-3 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">← Anterior</button>
        <button type="button" onclick="atualizarAgenda('?data=<?= date('Y-m-d') ?>&medico_id=<?= $medicoFiltro ?>&turno=<?= $turnoFiltro ?>&estado=<?= $estadoFiltro ?>')" class="bg-primary text-white px-3 py-2 rounded-xl text-sm font-bold hover:scale-105 transition-transform">Semana Atual</button>
        <button type="button" onclick="atualizarAgenda('?data=<?= date('Y-m-d', strtotime($dataInicio.' +7 days')) ?>&medico_id=<?= $medicoFiltro ?>&turno=<?= $turnoFiltro ?>&estado=<?= $estadoFiltro ?>')" class="bg-surface-container-low px-3 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">Seguinte →</button>
    </div>
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
<div class="grid grid-cols-7 gap-4 min-w-[900px]">
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
        </div>
        
        <!-- Cartões de Marcações -->
        <div class="flex flex-col gap-3 h-[600px] overflow-y-auto pr-1 custom-scrollbar">
            <?php if(empty($marcacoesDia)): ?>
                <p class="text-center text-xs text-on-surface-variant/50 mt-4 font-bold">Livre</p>
            <?php else: ?>
                <?php foreach($marcacoesDia as $m): 
                    $eb = $estadoBadge[$m['estado']] ?? 'bg-gray-100 text-gray-500';
                    $cardColor = 'bg-surface-container-low';
                    if($m['estado'] === 'confirmada') $cardColor = 'bg-green-50';
                    elseif($m['estado'] === 'em_atendimento') $cardColor = 'bg-yellow-50';
                    elseif($m['estado'] === 'concluida') $cardColor = 'bg-gray-50';
                    elseif($m['estado'] === 'falta' || $m['estado'] === 'cancelada') $cardColor = 'bg-red-50';
                    else $cardColor = 'bg-blue-50'; // marcada
                ?>
                    <div class="<?= $cardColor ?> rounded-2xl p-3 shadow-sm border border-black/5 hover:shadow-md transition-shadow cursor-pointer relative group" onclick='abrirDetalhes(<?= json_encode($m) ?>)'>
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-2 py-0.5 <?= $eb ?> text-[8px] font-black rounded-full"><?= strtoupper($m['estado']) ?></span>
                            <span class="text-[10px] font-bold text-on-surface-variant">
                                <?= !empty($m['hora_formatada']) ? $m['hora_formatada'] : ($m['turno'] === 'manha' ? 'Manhã' : 'Tarde') ?>
                            </span>
                        </div>
                        <h4 class="font-bold text-[13px] text-black leading-tight mb-1"><?= htmlspecialchars($m['paciente_nome']) ?></h4>
                        <p class="text-[10px] text-on-surface-variant font-medium leading-tight mb-2">
                            <?= htmlspecialchars($m['especialidade_nome']) ?><br>
                            Dr. <?= htmlspecialchars(explode(' ', $m['medico_nome'])[0]) ?>
                        </p>
                        <?php if(!empty($m['senha_codigo'])): ?>
                            <span class="inline-block px-2 py-1 bg-white text-black text-[10px] font-black rounded-lg shadow-sm">
                                <?= htmlspecialchars($m['senha_codigo']) ?>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-black/5 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[1px]">
                            <span class="bg-white text-black px-3 py-1 rounded-full text-[10px] font-black shadow-lg">Detalhes</span>
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
<div class="bg-white rounded-[2rem] w-full max-w-2xl p-8 floating-card max-h-[90vh] overflow-y-auto">
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
        <div class="bg-surface-container-lowest border border-surface-container-high rounded-3xl p-5 shadow-sm">
            <h4 class="text-xs font-black uppercase tracking-widest text-primary mb-4 flex items-center gap-1">
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
        <div class="bg-blue-50/50 rounded-2xl p-4 border border-blue-100">
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
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1">Prioridade</p>
                <p class="text-sm font-bold text-black" id="det-prioridade">--</p>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1">Senha</p>
                <p class="text-sm font-black text-primary" id="det-senha">--</p>
            </div>
        </div>
    </div>

    <!-- Acções Dinâmicas -->
    <div id="det-accoes" class="flex flex-col gap-2">
        <!-- Preenchido via JS -->
    </div>
</div>
</div>

<script>
const estadoBadgeClass = <?= json_encode($estadoBadge) ?>;
const prioLabels = <?= json_encode($prioLabels) ?>;

function abrirTriagem(id){document.getElementById('triagem-marcacao-id').value=id;document.getElementById('modal-triagem').classList.remove('hidden')}
function fecharTriagem(){document.getElementById('modal-triagem').classList.add('hidden')}
function abrirRemarcar(id){document.getElementById('remarcar-marcacao-id').value=id;document.getElementById('modal-remarcar').classList.remove('hidden')}

function fecharDetalhes() {
    document.getElementById('modal-detalhes').classList.add('hidden');
}

function abrirDetalhes(m) {
    document.getElementById('det-paciente').textContent = m.paciente_nome + ' (' + m.paciente_idade + 'a)';
    document.getElementById('det-info').textContent = m.especialidade_nome + ' • Dr. ' + m.medico_nome;
    
    let hora = m.hora_formatada ? m.hora_formatada : (m.turno === 'manha' ? 'Manhã' : 'Tarde');
    document.getElementById('det-data-hora').textContent = m.data_consulta.split('-').reverse().join('/') + ' às ' + hora;
    
    let ebClass = estadoBadgeClass[m.estado] || 'bg-gray-100 text-gray-500';
    let estadoEl = document.getElementById('det-estado');
    estadoEl.className = 'px-2 py-0.5 text-[10px] font-black rounded-full inline-block ' + ebClass;
    estadoEl.textContent = m.estado.toUpperCase();
    
    document.getElementById('det-prioridade').textContent = prioLabels[m.prioridade] || 'Normal';
    document.getElementById('det-senha').textContent = m.senha_codigo || '—';
    
    let accoesHtml = '';
    
    if (m.estado === 'confirmada' || m.estado === 'marcada') {
        accoesHtml += `<button onclick="fecharDetalhes(); abrirTriagem(${m.id})" class="w-full bg-blue-600 text-white px-4 py-3 rounded-xl text-sm font-black hover:scale-[1.02] transition-transform flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[18px]">vital_signs</span> Fazer Triagem
        </button>`;
        
        accoesHtml += `<button onclick="fecharDetalhes(); abrirRemarcar(${m.id})" class="w-full bg-surface-container-low text-black px-4 py-3 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">
            Remarcar Consulta
        </button>`;
        
        if (m.estado === 'confirmada') {
            accoesHtml += `<form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" class="m-0" onsubmit="return confirm('Marcar como falta?')">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="falta"><input type="hidden" name="marcacao_id" value="${m.id}">
                <button class="w-full bg-orange-100 text-orange-600 px-4 py-3 rounded-xl text-sm font-bold hover:bg-orange-200 transition-colors">Registar Falta</button>
            </form>`;
        }
        
        accoesHtml += `<form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" class="m-0" onsubmit="return confirm('Cancelar esta marcação?')">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
            <input type="hidden" name="acao" value="cancelar"><input type="hidden" name="marcacao_id" value="${m.id}">
            <input type="hidden" name="motivo" value="Cancelamento pela recepção">
            <button class="w-full bg-red-100 text-red-600 px-4 py-3 rounded-xl text-sm font-bold hover:bg-red-200 transition-colors">Cancelar Marcação</button>
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
</script>
<script src="<?= BASE_URL ?>public/assets/js/fila.js"></script>
</body></html>
