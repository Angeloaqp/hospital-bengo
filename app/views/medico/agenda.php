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
    tailwind.config={darkMode:"class",theme:{extend:{colors:{background:"#f9f9f9","surface-container-highest":"#e2e2e2","on-primary":"#e5e2e1","surface-container-high":"#e8e8e8",outline:"#777777","surface-dim":"#dadada","surface-container":"#eeeeee","on-error":"#ffffff",primary:"#000000","primary-container":"#3c3b3b",secondary:"#5e5e5e","outline-variant":"#c6c6c6","on-secondary":"#ffffff","surface-variant":"#e2e2e2",surface:"#f9f9f9","on-background":"#1a1c1c","on-surface":"#1a1c1c","surface-container-low":"#f3f3f3","surface-container-lowest":"#ffffff","inverse-surface":"#2f3131","surface-bright":"#f9f9f9","on-surface-variant":"#474747",error:"#ba1a1a"},borderRadius:{DEFAULT:"1rem",lg:"2rem",xl:"3rem",full:"9999px"},fontFamily:{headline:["Manrope"],body:["Inter"],label:["Inter"]}}}}
    </script>
    <style>
    .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
    body{font-family:'Inter',sans-serif;background-color:#f3f4f6}
    h1,h2,h3{font-family:'Manrope',sans-serif}
    .floating-card{box-shadow:0 4px 20px -2px rgba(0,0,0,.05),0 2px 10px -2px rgba(0,0,0,.03)}
    </style>
</head>
<body class="text-on-surface">
<?php $paginaActual='agenda'; include __DIR__.'/../comum/sidebar.php'; ?>
<?php $tituloPagina='Minha Agenda'; $subtituloPagina=''; $accoesPagina=''; include __DIR__.'/../comum/header.php'; ?>

<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1500px]">

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-3xl font-extrabold text-black tracking-tight">Agenda do Dia</h2>
        <p class="text-on-surface-variant font-semibold mt-1 text-sm"><?= dataFormatoPT($dataFiltro) ?></p>
    </div>
</div>

<!-- Métricas (Calculadas para o Médico Logado) -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php
    $metricas = [
        ['label'=>'Total de Pacientes','valor'=>$estatsMedico['total'],'cor'=>'text-black'],
        ['label'=>'Por Atender','valor'=>$estatsMedico['marcadas'],'cor'=>'text-blue-600'],
        ['label'=>'Fizeram Check-in','valor'=>$estatsMedico['confirmadas'],'cor'=>'text-green-600'],
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
<form method="GET" class="bg-white rounded-[1.5rem] p-5 floating-card border border-white mb-6">
<div class="flex flex-wrap gap-3 items-end">
    <div>
        <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Data</label>
        <input type="date" name="data" value="<?= $dataFiltro ?>" class="rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold" onchange="this.form.submit()">
    </div>
    <div>
        <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Turno</label>
        <select name="turno" class="rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold" onchange="this.form.submit()">
            <option value="">Todos</option>
            <option value="manha" <?= $turnoFiltro==='manha'?'selected':'' ?>>Manhã</option>
            <option value="tarde" <?= $turnoFiltro==='tarde'?'selected':'' ?>>Tarde</option>
        </select>
    </div>
    <div>
        <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Estado</label>
        <select name="estado" class="rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold" onchange="this.form.submit()">
            <option value="">Todos</option>
            <option value="marcada" <?= $estadoFiltro==='marcada'?'selected':'' ?>>Marcada</option>
            <option value="confirmada" <?= $estadoFiltro==='confirmada'?'selected':'' ?>>Confirmada</option>
            <option value="em_atendimento" <?= $estadoFiltro==='em_atendimento'?'selected':'' ?>>Em Atendimento</option>
            <option value="concluida" <?= $estadoFiltro==='concluida'?'selected':'' ?>>Concluída</option>
            <option value="cancelada" <?= $estadoFiltro==='cancelada'?'selected':'' ?>>Cancelada</option>
            <option value="falta" <?= $estadoFiltro==='falta'?'selected':'' ?>>Falta</option>
        </select>
    </div>
    <div class="flex gap-2">
        <a href="?data=<?= date('Y-m-d', strtotime($dataFiltro.' -1 day')) ?>" class="bg-surface-container-low px-3 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">← Anterior</a>
        <a href="?data=<?= date('Y-m-d') ?>" class="bg-black text-white px-3 py-2 rounded-xl text-sm font-bold hover:scale-105 transition-transform">Hoje</a>
        <a href="?data=<?= date('Y-m-d', strtotime($dataFiltro.' +1 day')) ?>" class="bg-surface-container-low px-3 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">Seguinte →</a>
    </div>
</div>
</form>

<!-- Tabela da Agenda -->
<div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white mb-6">
<h3 class="text-lg font-black tracking-tight mb-4"><?= count($agenda) ?> pacientes agendados</h3>
<?php if(empty($agenda)): ?>
    <div class="text-center py-12 text-on-surface-variant font-semibold">Nenhum paciente agendado para este dia.</div>
<?php else: ?>
<div class="overflow-x-auto">
<table class="w-full text-left">
<thead class="border-b border-surface-container-low">
<tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.15em]">
    <th class="pb-3">Turno</th>
    <th class="pb-3">Paciente</th>
    <th class="pb-3">Especialidade</th>
    <th class="pb-3 text-center">Senha</th>
    <th class="pb-3 text-center">Prioridade</th>
    <th class="pb-3 text-center">Estado</th>
    <th class="pb-3">Origem</th>
</tr>
</thead>
<tbody class="divide-y divide-surface-container-low/50">
<?php foreach($agenda as $m):
    $eb = $estadoBadge[$m['estado']] ?? 'bg-gray-100 text-gray-500';
    $pb = $prioBadge[$m['prioridade']] ?? 'bg-blue-600';
    $pl = $prioLabels[$m['prioridade']] ?? 'Normal';
?>
<tr class="group hover:bg-surface-container-low/30 transition-colors">
    <td class="py-3 text-xs font-bold"><?= $turnoLabel[$m['turno']] ?? $m['turno'] ?></td>
    <td class="py-3 font-bold text-black text-sm"><?= htmlspecialchars($m['paciente_nome']) ?> <span class="text-on-surface-variant text-xs">(<?= $m['paciente_idade'] ?>a)</span></td>
    <td class="py-3 text-xs text-on-surface-variant font-medium"><?= htmlspecialchars($m['especialidade_nome']) ?></td>
    <td class="py-3 text-center">
        <?php if(!empty($m['senha_codigo'])): ?>
            <span class="px-2.5 py-1 bg-black text-white text-[11px] font-black rounded-lg tracking-wider"><?= htmlspecialchars($m['senha_codigo']) ?></span>
        <?php else: ?>
            <span class="text-on-surface-variant text-[10px] font-bold">—</span>
        <?php endif; ?>
    </td>
    <td class="py-3 text-center"><span class="px-2 py-0.5 <?= $pb ?> text-white text-[9px] font-black rounded-full"><?= strtoupper($pl) ?></span></td>
    <td class="py-3 text-center"><span class="px-2 py-0.5 <?= $eb ?> text-[9px] font-black rounded-full"><?= strtoupper($m['estado']) ?></span></td>
    <td class="py-3 text-[10px] font-bold text-on-surface-variant uppercase"><?= $m['origem'] === 'mesmo_dia' ? 'Mesmo dia' : 'Marcação' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>

</main>
</div>

</body>
</html>
