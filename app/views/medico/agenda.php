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

$dataFiltro = trim($_GET['data'] ?? date('Y-m-d'));
$turnoFiltro = trim($_GET['turno'] ?? '');
$estadoFiltro = trim($_GET['estado'] ?? '');

$agenda = Marcacao::listarAgendaDia($dataFiltro, $medicoId, null, $estadoFiltro ?: null, $turnoFiltro ?: null);
$estatsDia = Marcacao::estatisticasDia($dataFiltro);

// Calcular as estatísticas especificamente para o médico (já que estatisticasDia devolve global)
// O ideal seria criar um método Marcacao::estatisticasDiaMedico, mas podemos calcular aqui
$estatsMedico = [
    'total' => 0,
    'marcadas' => 0,
    'confirmadas' => 0,
    'concluidas' => 0,
    'faltas' => 0,
];

foreach ($agenda as $m) {
    $estatsMedico['total']++;
    if ($m['estado'] === 'marcada') $estatsMedico['marcadas']++;
    if ($m['estado'] === 'confirmada') $estatsMedico['confirmadas']++;
    if ($m['estado'] === 'concluida') $estatsMedico['concluidas']++;
    if ($m['estado'] === 'falta') $estatsMedico['faltas']++;
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

if (!function_exists('agendaMedicoPrioridadeTriagemLabel')) {
    function agendaMedicoPrioridadeTriagemLabel(?int $prioridade): string
    {
        $labels = [1=>'Urgente',2=>'Alta',3=>'Moderada',4=>'Normal'];
        return $labels[$prioridade ?: 4] ?? 'Normal';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Minha Agenda — <?= APP_NOME ?></title>
    <script src="<?= BASE_URL ?>public/assets/js/tailwindcss.js"></script>
    <link href="<?= BASE_URL ?>public/assets/css/google_fonts.css" rel="stylesheet"/>
    <link href="<?= BASE_URL ?>public/assets/css/material_symbols.css" rel="stylesheet"/>
    <script>
    tailwind.config={darkMode:"class",theme:{extend:{colors:{background:"#f9f9f9","surface-container-highest":"#e2e2e2","on-primary":"#e5e2e1","surface-container-high":"#e8e8e8",outline:"#777777","surface-dim":"#dadada","surface-container":"#eeeeee","on-error":"#ffffff",primary:"#000000","primary-container":"#3c3b3b",secondary:"#5e5e5e","outline-variant":"#c6c6c6","on-secondary":"#ffffff","surface-variant":"#e2e2e2",surface:"#f9f9f9","on-background":"#1a1c1c","on-surface":"#1a1c1c","surface-container-low":"#f3f3f3","surface-container-lowest":"#ffffff","inverse-surface":"#2f3131","surface-bright":"#f9f9f9","on-surface-variant":"#474747",error:"#ba1a1a"},borderRadius:{DEFAULT:"1rem",lg:"1rem",xl:"0.75rem","2xl":"1rem","3xl":"1.5rem",full:"9999px"},fontFamily:{headline:["Manrope"],body:["Inter"],label:["Inter"]}}}}
    </script>
    <style>
    .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
    body{font-family:'Inter',sans-serif;background-color:#f3f4f6}
    h1,h2,h3{font-family:'Manrope',sans-serif}
    .floating-card{box-shadow:0 4px 20px -2px rgba(0,0,0,.05),0 2px 10px -2px rgba(0,0,0,.03)}
    </style>
</head>
<body class="text-on-surface bg-[#f3f4f6]">
<?php $paginaActual='agenda'; include __DIR__.'/../comum/sidebar.php'; ?>
<?php $tituloPagina='Minha Agenda'; $subtituloPagina=''; $accoesPagina=''; include __DIR__.'/../comum/header.php'; ?>

<div class="ml-[17rem] mr-6 mt-28 p-8 flex justify-center pb-24">
<main class="w-full max-w-[1200px] space-y-10">

<!-- Date Header -->
<section class="mb-2">
    <h2 class="font-headline font-black text-3xl text-black tracking-tight"><?= dataFormatoPT($dataFiltro) ?></h2>
    <p class="font-body text-sm font-semibold text-on-surface-variant mt-1 uppercase tracking-wider">Agenda do Dia</p>
</section>

<!-- KPI Cards -->
<section class="grid grid-cols-2 md:grid-cols-5 gap-4">
    <?php
    $metricas = [
        ['label'=>'Total Pacientes','valor'=>$estatsMedico['total'],'icon'=>'groups','cor'=>'text-black'],
        ['label'=>'Marcados','valor'=>$estatsMedico['marcadas'],'icon'=>'event_available','cor'=>'text-black'],
        ['label'=>'Check-in','valor'=>$estatsMedico['confirmadas'],'icon'=>'how_to_reg','cor'=>'text-black'],
        ['label'=>'Concluídas','valor'=>$estatsMedico['concluidas'],'icon'=>'check_circle','cor'=>'text-black'],
        ['label'=>'Faltas','valor'=>$estatsMedico['faltas'],'icon'=>'person_cancel','cor'=>'text-error'],
    ];
    foreach($metricas as $m): ?>
    <div class="bg-white rounded-[24px] p-6 floating-card border border-white flex flex-col justify-between h-32">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider"><?= $m['label'] ?></span>
            <span class="material-symbols-outlined <?= $m['cor'] ?>" data-icon="<?= $m['icon'] ?>"><?= $m['icon'] ?></span>
        </div>
        <div class="font-headline font-black text-3xl text-black"><?= $m['valor'] ?></div>
    </div>
    <?php endforeach; ?>
</section>

<!-- Filters Row -->
<form method="GET" class="flex flex-wrap gap-4 items-center justify-between bg-white p-4 rounded-[24px] floating-card border border-white relative z-50">
    <div class="flex items-center gap-3">
        <!-- Date Navigation -->
        <div class="flex items-center bg-surface-container-low rounded-xl p-1">
            <a href="?data=<?= date('Y-m-d', strtotime($dataFiltro.' -1 day')) ?>" class="p-2 text-on-surface-variant hover:bg-surface-container rounded-xl transition-colors inline-flex">
                <span class="material-symbols-outlined text-sm" data-icon="chevron_left">chevron_left</span>
            </a>
            <a href="?data=<?= date('Y-m-d') ?>" class="px-4 py-1.5 font-body text-xs font-bold text-black uppercase tracking-wider hover:bg-surface-container rounded-xl transition-colors">
                Hoje
            </a>
            <a href="?data=<?= date('Y-m-d', strtotime($dataFiltro.' +1 day')) ?>" class="p-2 text-on-surface-variant hover:bg-surface-container rounded-xl transition-colors inline-flex">
                <span class="material-symbols-outlined text-sm" data-icon="chevron_right">chevron_right</span>
            </a>
        </div>
        <!-- Date Picker -->
        <div class="relative group custom-calendar-dropdown" id="cal-filtro-medico-dropdown">
            <input type="hidden" name="data" id="cal-filtro-medico-input" value="<?= $dataFiltro ?>" onchange="this.form.submit()">
            <button type="button" class="flex items-center gap-2 bg-surface-container-low px-4 py-2 rounded-xl hover:bg-surface-container transition-colors text-xs font-bold text-black uppercase tracking-wider" onclick="if(typeof HospitalCalendar !== 'undefined') HospitalCalendar.toggleDropdown('cal-filtro-medico', event)">
                <span class="material-symbols-outlined text-[16px]" data-icon="calendar_today">calendar_today</span>
                <span id="cal-filtro-medico-text"><?= date('d M Y', strtotime($dataFiltro)) ?></span>
            </button>
            <div class="custom-cal-wrapper absolute top-[calc(100%+8px)] left-0 w-[300px] bg-white rounded-[32px] p-2 floating-card border border-zinc-100 z-50 shadow-2xl transition-all duration-200 opacity-0 invisible -translate-y-2 pointer-events-none" id="cal-filtro-medico-wrapper" onclick="event.stopPropagation()"></div>
            
            <?php if (!isset($GLOBALS['calendar_widget_loaded'])): ?>
                <script src="<?= BASE_URL ?>public/js/calendar_widget.js?v=<?= time() ?>"></script>
                <?php $GLOBALS['calendar_widget_loaded'] = true; ?>
            <?php endif; ?>
            <script>
                if (typeof HospitalCalendar !== 'undefined') {
                    HospitalCalendar.init('cal-filtro-medico', '<?= $dataFiltro ?>');
                } else {
                    document.addEventListener('DOMContentLoaded', function() {
                        HospitalCalendar.init('cal-filtro-medico', '<?= $dataFiltro ?>');
                    });
                }
            </script>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <!-- Turno Dropdown -->
        <div>
            <?php
            $sel_id = 'cs-turno-med';
            $sel_name = 'turno';
            $sel_icon = 'schedule';
            $sel_placeholder = 'Todos os Turnos';
            $sel_value = $turnoFiltro;
            $sel_onchange = 'this.form.submit()';
            $sel_size = 'sm';
            $sel_options = [
                '' => ['label' => 'Todos', 'icon' => 'filter_list', 'color' => 'text-on-surface-variant'],
                'manha' => ['label' => 'Manhã', 'icon' => 'light_mode', 'color' => 'text-amber-500'],
                'tarde' => ['label' => 'Tarde', 'icon' => 'routine', 'color' => 'text-orange-500'],
            ];
            include __DIR__ . '/../comum/custom_select.php';
            ?>
        </div>
        <!-- Estado Dropdown -->
        <div>
            <?php
            $sel_id = 'cs-estado-med';
            $sel_name = 'estado';
            $sel_icon = 'info';
            $sel_placeholder = 'Todos os Estados';
            $sel_value = $estadoFiltro;
            $sel_onchange = 'this.form.submit()';
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
            ?>
        </div>
    </div>
</form>

<!-- Patient List -->
<section class="space-y-4">
<?php if(empty($agenda)): ?>
    <div class="text-center py-12 text-on-surface-variant font-semibold">Nenhum paciente agendado para este dia.</div>
<?php else: ?>
    <!-- Column Headers -->
    <div class="grid grid-cols-[auto_2fr_1.5fr_1fr_1.5fr_1.5fr_1fr] gap-4 px-8 py-2 text-[10px] font-bold text-on-surface-variant uppercase tracking-[0.15em] mb-2">
        <div class="w-12 text-center">Turno</div>
        <div>Paciente</div>
        <div>Especialidade</div>
        <div>Senha</div>
        <div>Prioridade</div>
        <div>Estado</div>
        <div>Origem</div>
    </div>
    
    <?php foreach($agenda as $m):
        $iniciais = strtoupper(substr($m['paciente_nome'], 0, 1) . (strpos($m['paciente_nome'], ' ') !== false ? substr(strrchr($m['paciente_nome'], ' '), 1, 1) : substr($m['paciente_nome'], 1, 1)));
        
        $prioCorBg = $m['prioridade'] == 1 ? 'bg-[#FFEBEE]' : ($m['prioridade'] == 2 ? 'bg-[#FFF8E1]' : 'bg-[#E3F2FD]');
        $prioCorTexto = $m['prioridade'] == 1 ? 'text-[#D32F2F]' : ($m['prioridade'] == 2 ? 'text-[#FF8F00]' : 'text-[#1976D2]');
        $prioIcon = $m['prioridade'] == 1 ? 'warning' : ($m['prioridade'] == 2 ? 'elderly' : 'person');
        $prioLabel = $m['prioridade'] == 1 ? 'Urgente' : ($m['prioridade'] == 2 ? 'Alta' : 'Normal');

        $estDot = 'bg-black';
        if($m['estado'] === 'em_espera' || $m['estado'] === 'marcada') $estDot = 'bg-[#FF8F00]';
        if($m['estado'] === 'confirmada') $estDot = 'border border-outline-variant';
        if($m['estado'] === 'em_atendimento') $estDot = 'bg-black';
        if($m['estado'] === 'urgente' || $m['estado'] === 'falta' || $m['estado'] === 'cancelada') $estDot = 'bg-error';

        $opacityClass = ($m['estado'] === 'concluida' || $m['estado'] === 'cancelada') ? 'opacity-70' : '';
    ?>
    <div class="grid grid-cols-[auto_2fr_1.5fr_1fr_1.5fr_1.5fr_1fr] gap-4 items-center bg-white p-5 rounded-[24px] floating-card border border-white hover:shadow-lg transition-shadow cursor-pointer <?= $opacityClass ?>">
        <div class="w-12 flex flex-col items-center justify-center text-on-surface-variant">
            <span class="font-headline font-black text-black"><?= $m['turno'] === 'manha' ? 'Manhã' : 'Tarde' ?></span>
            <span class="material-symbols-outlined text-[14px] mt-1" data-icon="<?= $m['turno'] === 'manha' ? 'light_mode' : 'routine' ?>"><?= $m['turno'] === 'manha' ? 'light_mode' : 'routine' ?></span>
        </div>
        <div class="flex items-center gap-3">
            <div>
                <h3 class="font-headline font-black text-black text-sm line-clamp-1"><?= htmlspecialchars($m['paciente_nome']) ?></h3>
                <p class="font-body text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant mt-0.5"><?= $m['paciente_idade'] ?> anos</p>
            </div>
        </div>
        <div class="text-xs font-semibold text-on-surface-variant truncate"><?= htmlspecialchars($m['especialidade_nome']) ?></div>
        <div>
            <?php if(!empty($m['senha_codigo'])): ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-[10px] text-xs font-bold bg-surface-container-low text-black">
                    <?= htmlspecialchars($m['senha_codigo']) ?>
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-[10px] text-xs font-bold text-on-surface-variant">-</span>
            <?php endif; ?>
        </div>
        <div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] uppercase tracking-wider font-bold <?= $prioCorBg ?> <?= $prioCorTexto ?>">
                <span class="material-symbols-outlined text-[12px]" data-icon="<?= $prioIcon ?>"><?= $prioIcon ?></span> <?= $prioLabel ?>
            </span>
        </div>
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] uppercase tracking-wider font-bold bg-surface-container-low text-on-surface-variant">
                <span class="w-1.5 h-1.5 rounded-full <?= $estDot ?>"></span> <?= ucfirst(str_replace('_', ' ', $m['estado'])) ?>
            </span>
        </div>
        <div class="text-xs text-on-surface-variant font-medium"><?= $m['origem'] === 'mesmo_dia' ? 'Mesmo dia' : 'Marcação' ?></div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</section>

</main>
</div>

</body>
</html>
