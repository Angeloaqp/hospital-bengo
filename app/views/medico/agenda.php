<?php
// ================================================
// Hospital Geral do Bengo — Agenda do Médico
// ================================================
require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Marcacao.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

exigirPerfil(['medico']);
$meuPerfilObject = Utilizador::obter((int) sessao('utilizador_id'));
$medicoId = (int) sessao('utilizador_id');

$dataInicio = trim($_GET['data'] ?? date('Y-m-d'));
$dataFim = date('Y-m-d', strtotime("$dataInicio + 6 days"));
$dataFiltro = $dataInicio;

$turnoFiltro = trim($_GET['turno'] ?? '');
$estadoFiltro = trim($_GET['estado'] ?? '');

$agendaRaw = Marcacao::listarAgendaIntervalo($dataInicio, $dataFim, $medicoId, null, $estadoFiltro ?: null, $turnoFiltro ?: null);

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

// Calcular as estatísticas especificamente para o médico 
$estatsMedico = [
    'total' => 0,
    'marcadas' => 0,
    'confirmadas' => 0,
    'concluidas' => 0,
    'faltas' => 0,
];

foreach ($agendaRaw as $m) {
    if ($m['data_consulta'] === $dataFiltro) {
        $estatsMedico['total']++;
        if ($m['estado'] === 'marcada') $estatsMedico['marcadas']++;
        if ($m['estado'] === 'confirmada') $estatsMedico['confirmadas']++;
        if ($m['estado'] === 'concluida') $estatsMedico['concluidas']++;
        if ($m['estado'] === 'falta') $estatsMedico['faltas']++;
    }
}

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
<html lang="pt">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Minha Agenda — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
    .floating-card{box-shadow:0 4px 20px -2px rgba(0,0,0,.05),0 2px 10px -2px rgba(0,0,0,.03)}
    </style>
</head>
<body class="text-on-surface bg-surface-container-low">
<?php $paginaActual='agenda'; include __DIR__.'/../comum/sidebar.php'; ?>
<?php $tituloPagina='Minha Agenda'; $subtituloPagina=''; $accoesPagina=''; include __DIR__.'/../comum/header.php'; ?>

<div class="ml-0 lg:ml-[17rem] lg:mr-6 px-4 sm:px-6 lg:px-0 mt-28 pb-24 lg:pb-8 flex justify-center min-h-screen">
<main class="w-full">

<!-- Header + Ações -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 fade-in">
    <div>
        <h2 class="text-3xl font-extrabold text-black tracking-tight">Minha Agenda</h2>
        <p class="text-on-surface-variant font-semibold mt-1 text-sm"><?= dataFormatoPT($dataFiltro) ?></p>
    </div>
</div>

<!-- Métricas (Dia selecionado) -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6 fade-in-delay-1">
    <?php
    $metricas = [
        ['label'=>'Total (Hoje)','valor'=>$estatsMedico['total'],'cor'=>'text-black'],
        ['label'=>'Marcadas','valor'=>$estatsMedico['marcadas'],'cor'=>'text-blue-600'],
        ['label'=>'Check-in','valor'=>$estatsMedico['confirmadas'],'cor'=>'text-green-600'],
        ['label'=>'Concluídas','valor'=>$estatsMedico['concluidas'],'cor'=>'text-gray-500'],
        ['label'=>'Faltas','valor'=>$estatsMedico['faltas'],'cor'=>'text-orange-600'],
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
        <button type="button" onclick="atualizarAgenda('?data=<?= date('Y-m-d', strtotime($dataInicio.' -7 days')) ?>&turno=<?= $turnoFiltro ?>&estado=<?= $estadoFiltro ?>')" class="bg-surface-container-low px-3 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">← Anterior</button>
        <button type="button" onclick="atualizarAgenda('?data=<?= date('Y-m-d') ?>&turno=<?= $turnoFiltro ?>&estado=<?= $estadoFiltro ?>')" class="bg-primary text-white px-3 py-2 rounded-xl text-sm font-bold hover:scale-105 transition-transform">Semana Atual</button>
        <button type="button" onclick="atualizarAgenda('?data=<?= date('Y-m-d', strtotime($dataInicio.' +7 days')) ?>&turno=<?= $turnoFiltro ?>&estado=<?= $estadoFiltro ?>')" class="bg-surface-container-low px-3 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">Seguinte →</button>
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
    <p class="text-on-surface-variant font-bold text-sm"><?= count($agendaRaw) ?> marcações nesta semana</p>
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
                    <div class="<?= $cardColor ?> rounded-2xl p-3 shadow-sm border border-black/5 hover:shadow-md transition-shadow relative group">
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-2 py-0.5 <?= $eb ?> text-[8px] font-black rounded-full"><?= strtoupper($m['estado']) ?></span>
                            <span class="text-[10px] font-bold text-on-surface-variant">
                                <?= !empty($m['hora_formatada']) ? $m['hora_formatada'] : ($m['turno'] === 'manha' ? 'Manhã' : 'Tarde') ?>
                            </span>
                        </div>
                        <h4 class="font-bold text-[13px] text-black leading-tight mb-1"><?= htmlspecialchars($m['paciente_nome']) ?></h4>
                        <p class="text-[10px] text-on-surface-variant font-medium leading-tight mb-2">
                            <?= htmlspecialchars($m['especialidade_nome']) ?>
                        </p>
                        <?php if(!empty($m['senha_codigo'])): ?>
                            <span class="inline-block px-2 py-1 bg-white text-black text-[10px] font-black rounded-lg shadow-sm">
                                <?= htmlspecialchars($m['senha_codigo']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if(!empty($m['triagem_id'])): ?>
                            <span class="inline-flex items-center justify-center w-5 h-5 bg-blue-100 text-blue-700 rounded-full shadow-sm ml-1" title="Triagem Efetuada">
                                <span class="material-symbols-outlined text-[12px]">vital_signs</span>
                            </span>
                        <?php endif; ?>
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

</main>
</div>

<script>
function atualizarAgenda(urlBase) {
    const form = document.getElementById('filtro-agenda');
    let url = urlBase || '?';
    
    if(!urlBase) {
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value && key !== 'csrf_token') {
                params.append(key, value);
            }
        }
        url += params.toString();
    }
    
    history.pushState(null, '', url);
    
    const container = document.getElementById('calendario-container');
    const overlay = document.getElementById('loading-overlay');
    if(overlay) overlay.classList.remove('hidden');
    else container.style.opacity = '0.5';

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const novoConteudo = doc.getElementById('calendario-container');
            if (novoConteudo) {
                container.innerHTML = novoConteudo.innerHTML;
            } else {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar agenda:', error);
            if(overlay) overlay.classList.add('hidden');
            else container.style.opacity = '1';
        });
}
</script>
</body>
</html>
