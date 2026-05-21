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

$dataFiltro = trim($_GET['data'] ?? date('Y-m-d'));
$turnoFiltro = trim($_GET['turno'] ?? '');
$medicoFiltro = !empty($_GET['medico_id']) ? (int) $_GET['medico_id'] : null;
$estadoFiltro = trim($_GET['estado'] ?? '');
$checkinId = (int) ($_GET['checkin'] ?? 0);

$agenda = Marcacao::listarAgendaDia($dataFiltro, $medicoFiltro, null, $estadoFiltro ?: null, $turnoFiltro ?: null);
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
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
<?php $tituloPagina='Agenda'; $subtituloPagina=''; $accoesPagina=''; include __DIR__.'/../comum/header.php'; ?>

<div id="alertas-iniciais" style="display:none"
    data-mensagem="<?= htmlspecialchars($mensagem) ?>"
    data-erro="<?= htmlspecialchars($erro) ?>"
    data-senha="<?= htmlspecialchars($ultimaSenha) ?>"></div>

<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1500px]">

<!-- Header + Ações -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-3xl font-extrabold text-black tracking-tight">Agenda do Dia</h2>
        <p class="text-on-surface-variant font-semibold mt-1 text-sm"><?= date('l, d \d\e F \d\e Y', strtotime($dataFiltro)) ?></p>
    </div>
    <div class="flex gap-3">
        <a href="marcacao.php" class="bg-black text-white px-6 py-2.5 rounded-full font-black text-xs flex items-center gap-2 hover:scale-[1.02] transition-transform shadow-md no-underline">
            <span class="material-symbols-outlined text-[18px]">add</span> Nova Marcação
        </a>
        <a href="marcacao.php?origem=mesmo_dia" class="bg-white text-black px-6 py-2.5 rounded-full font-black text-xs flex items-center gap-2 hover:scale-[1.02] transition-transform shadow-md no-underline border border-black/10">
            <span class="material-symbols-outlined text-[18px]">bolt</span> Mesmo Dia
        </a>
    </div>
</div>

<!-- Métricas -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
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
<form method="GET" class="bg-white rounded-[1.5rem] p-5 floating-card border border-white mb-6">
<div class="flex flex-wrap gap-3 items-end">
    <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Data</label>
    <input type="date" name="data" value="<?= $dataFiltro ?>" class="rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold" onchange="this.form.submit()"></div>
    <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Turno</label>
    <select name="turno" class="rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="manha" <?= $turnoFiltro==='manha'?'selected':'' ?>>Manhã</option>
        <option value="tarde" <?= $turnoFiltro==='tarde'?'selected':'' ?>>Tarde</option>
    </select></div>
    <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Médico</label>
    <select name="medico_id" class="rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach($medicos as $med): ?>
        <option value="<?= $med['id'] ?>" <?= $medicoFiltro==(int)$med['id']?'selected':'' ?>><?= htmlspecialchars($med['nome']) ?></option>
        <?php endforeach; ?>
    </select></div>
    <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant block mb-1">Estado</label>
    <select name="estado" class="rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="marcada" <?= $estadoFiltro==='marcada'?'selected':'' ?>>Marcada</option>
        <option value="confirmada" <?= $estadoFiltro==='confirmada'?'selected':'' ?>>Confirmada</option>
        <option value="em_atendimento" <?= $estadoFiltro==='em_atendimento'?'selected':'' ?>>Em Atendimento</option>
        <option value="concluida" <?= $estadoFiltro==='concluida'?'selected':'' ?>>Concluída</option>
        <option value="cancelada" <?= $estadoFiltro==='cancelada'?'selected':'' ?>>Cancelada</option>
        <option value="falta" <?= $estadoFiltro==='falta'?'selected':'' ?>>Falta</option>
    </select></div>
    <div class="flex gap-2">
        <a href="?data=<?= date('Y-m-d', strtotime($dataFiltro.' -1 day')) ?>" class="bg-surface-container-low px-3 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">← Anterior</a>
        <a href="?data=<?= date('Y-m-d') ?>" class="bg-black text-white px-3 py-2 rounded-xl text-sm font-bold hover:scale-105 transition-transform">Hoje</a>
        <a href="?data=<?= date('Y-m-d', strtotime($dataFiltro.' +1 day')) ?>" class="bg-surface-container-low px-3 py-2 rounded-xl text-sm font-bold hover:bg-surface-container transition-colors">Seguinte →</a>
    </div>
</div>
</form>

<!-- Tabela da Agenda -->
<div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white mb-6">
<h3 class="text-lg font-black tracking-tight mb-4"><?= count($agenda) ?> marcações</h3>
<?php if(empty($agenda)): ?>
    <div class="text-center py-12 text-on-surface-variant font-semibold">Nenhuma marcação para este dia.</div>
<?php else: ?>
<div class="overflow-x-auto">
<table class="w-full text-left">
<thead class="border-b border-surface-container-low">
<tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.15em]">
    <th class="pb-3">Turno</th><th class="pb-3">Paciente</th><th class="pb-3">Especialidade</th>
    <th class="pb-3">Médico</th><th class="pb-3 text-center">Senha</th><th class="pb-3 text-center">Prioridade</th>
    <th class="pb-3 text-center">Estado</th><th class="pb-3">Origem</th><th class="pb-3 text-right">Acções</th>
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
    <td class="py-3 text-xs font-medium"><?= htmlspecialchars($m['medico_nome']) ?></td>
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
    <td class="py-3 text-right">
        <div class="flex gap-1 justify-end">
        <?php if($m['estado']==='confirmada'): ?>
            <button onclick="abrirTriagem(<?= $m['id'] ?>)" class="bg-blue-600 text-white px-3 py-1 rounded-full text-[10px] font-black hover:scale-105 transition-transform flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">vital_signs</span> Fazer Triagem
            </button>
            <button onclick="abrirRemarcar(<?= $m['id'] ?>)" class="bg-surface-container-low px-3 py-1 rounded-full text-[10px] font-bold hover:bg-surface-container transition-colors">Remarcar</button>
            <form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" class="inline m-0" onsubmit="return confirm('Marcar como falta?')">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="falta"><input type="hidden" name="marcacao_id" value="<?= $m['id'] ?>">
                <button class="bg-orange-100 text-orange-600 px-3 py-1 rounded-full text-[10px] font-bold hover:bg-orange-200 transition-colors">Falta</button>
            </form>
            <form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" class="inline m-0" onsubmit="return confirm('Cancelar esta marcação?')">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="cancelar"><input type="hidden" name="marcacao_id" value="<?= $m['id'] ?>">
                <input type="hidden" name="motivo" value="Cancelamento pela recepção">
                <button class="bg-red-100 text-red-600 px-3 py-1 rounded-full text-[10px] font-bold hover:bg-red-200 transition-colors">Cancelar</button>
            </form>
        <?php elseif($m['estado']==='marcada'): ?>
            <button onclick="abrirTriagem(<?= $m['id'] ?>)" class="bg-blue-600 text-white px-3 py-1 rounded-full text-[10px] font-black hover:scale-105 transition-transform flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">vital_signs</span> Fazer Triagem
            </button>
            <button onclick="abrirRemarcar(<?= $m['id'] ?>)" class="bg-surface-container-low px-3 py-1 rounded-full text-[10px] font-bold hover:bg-surface-container transition-colors">Remarcar</button>
            <form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" class="inline m-0" onsubmit="return confirm('Cancelar esta marcação?')">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="cancelar"><input type="hidden" name="marcacao_id" value="<?= $m['id'] ?>">
                <input type="hidden" name="motivo" value="Cancelamento pela recepção">
                <button class="bg-red-100 text-red-600 px-3 py-1 rounded-full text-[10px] font-bold hover:bg-red-200 transition-colors">Cancelar</button>
            </form>
        <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>

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
<div id="modal-triagem" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
<div class="bg-white rounded-[2rem] w-full max-w-lg p-8 floating-card max-h-[90vh] overflow-y-auto">
<h3 class="text-xl font-black mb-6 flex items-center gap-2"><span class="material-symbols-outlined text-blue-600">vital_signs</span> Triagem Clínica</h3>
<form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php" id="form-triagem">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
    <input type="hidden" name="acao" value="checkin">
    <input type="hidden" name="marcacao_id" id="triagem-marcacao-id" value="">
    <div class="space-y-4">
        <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Sintomas / Queixa</label>
        <textarea name="sintomas" rows="2" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm mt-1" placeholder="Descreva os sintomas..."></textarea></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Temperatura (°C)</label>
            <input type="number" step="0.1" name="temperatura" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm mt-1" placeholder="36.5"></div>
            <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Pressão Arterial</label>
            <input type="text" name="pressao_arterial" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm mt-1" placeholder="120/80"></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Peso (kg)</label>
            <input type="number" step="0.1" name="peso" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm mt-1" placeholder="70"></div>
            <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Freq. Cardíaca (bpm)</label>
            <input type="number" name="frequencia_cardiaca" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm mt-1" placeholder="72"></div>
        </div>
        <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Observações da Triagem</label>
        <textarea name="observacoes_triagem" rows="2" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm mt-1"></textarea></div>
        <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Prioridade Clínica</label>
        <select name="prioridade_clinica" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1">
            <option value="4">Normal</option><option value="3">Moderada</option>
            <option value="2">Alta (Idoso/Grávida)</option><option value="1">Urgente</option>
        </select></div>
    </div>
    <div class="flex gap-3 mt-6">
        <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-full font-black text-sm hover:scale-[1.02] transition-transform shadow-md flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[18px]">check</span> Confirmar Triagem
        </button>
        <button type="button" onclick="fecharTriagem()" class="px-6 py-3 rounded-full font-bold text-sm bg-surface-container-low hover:bg-surface-container transition-colors">Cancelar</button>
    </div>
</form>
</div>
</div>

<!-- Modal Remarcar -->
<div id="modal-remarcar" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
<div class="bg-white rounded-[2rem] w-full max-w-sm p-8 floating-card">
<h3 class="text-xl font-black mb-6">Remarcar Consulta</h3>
<form method="POST" action="<?= BASE_URL ?>app/controllers/marcacoes.php">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
    <input type="hidden" name="acao" value="remarcar">
    <input type="hidden" name="marcacao_id" id="remarcar-marcacao-id" value="">
    <div class="space-y-4">
        <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Nova Data</label>
        <input type="date" name="nova_data" required min="<?= date('Y-m-d') ?>" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1"></div>
        <div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Novo Turno</label>
        <select name="novo_turno" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1">
            <option value="manha">Manhã</option><option value="tarde">Tarde</option>
        </select></div>
    </div>
    <div class="flex gap-3 mt-6">
        <button type="submit" class="flex-1 bg-black text-white py-3 rounded-full font-black text-sm">Remarcar</button>
        <button type="button" onclick="document.getElementById('modal-remarcar').classList.add('hidden')" class="px-6 py-3 rounded-full font-bold text-sm bg-surface-container-low">Cancelar</button>
    </div>
</form>
</div>
</div>

<script>
function abrirTriagem(id){document.getElementById('triagem-marcacao-id').value=id;document.getElementById('modal-triagem').classList.remove('hidden')}
function fecharTriagem(){document.getElementById('modal-triagem').classList.add('hidden')}
function abrirRemarcar(id){document.getElementById('remarcar-marcacao-id').value=id;document.getElementById('modal-remarcar').classList.remove('hidden')}
</script>
<script src="<?= BASE_URL ?>public/assets/js/fila.js"></script>
</body></html>
