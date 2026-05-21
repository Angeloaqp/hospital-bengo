<?php
// ================================================
// Hospital Geral do Bengo — Admin: Configuração Operacional
// Consultórios, Especialidades, Tipos, Disponibilidade, Bloqueios
// ================================================
require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Disponibilidade.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

exigirPerfil(['admin']);
$meuPerfilObject = Utilizador::obter((int) sessao('utilizador_id'));

$tab = trim($_GET['tab'] ?? 'consultorios');
$consultorios = Disponibilidade::listarConsultorios();
$especialidades = Disponibilidade::listarEspecialidades();
$tipos = Disponibilidade::listarTiposAtendimento();
$disponibilidades = Disponibilidade::listarTodas();
$bloqueios = Disponibilidade::listarBloqueios();
$medicos = Disponibilidade::listarMedicos();

$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

$diasSemana = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',7=>'Domingo'];
$tabs = [
    'consultorios'=>'Consultórios','especialidades'=>'Especialidades',
    'tipos'=>'Tipos Atend.','disponibilidade'=>'Disponibilidade','bloqueios'=>'Bloqueios'
];
?>
<!DOCTYPE html>
<html lang="pt"><head>
<meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Configuração — <?= APP_NOME ?></title>
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
<?php $paginaActual='configuracao'; include __DIR__.'/../comum/sidebar.php'; ?>
<?php $tituloPagina='Configuração'; $accoesPagina=''; include __DIR__.'/../comum/header.php'; ?>

<div id="alertas-iniciais" style="display:none" data-mensagem="<?= htmlspecialchars($mensagem) ?>" data-erro="<?= htmlspecialchars($erro) ?>"></div>

<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1200px]">

<h2 class="text-3xl font-extrabold text-black tracking-tight mb-6">Configuração Operacional</h2>

<!-- Tabs -->
<div class="flex gap-2 mb-6 flex-wrap">
<?php foreach($tabs as $k=>$v): ?>
    <a href="?tab=<?= $k ?>" class="px-5 py-2 rounded-full text-xs font-black transition-all <?= $tab===$k ? 'bg-black text-white shadow-md' : 'bg-white text-on-surface-variant hover:bg-surface-container-low border border-white floating-card' ?>"><?= $v ?></a>
<?php endforeach; ?>
</div>

<?php if($mensagem): ?><div class="bg-green-50 border border-green-200 rounded-2xl p-4 mb-4"><p class="text-green-700 text-sm font-bold">✓ <?= htmlspecialchars($mensagem) ?></p></div><?php endif; ?>
<?php if($erro): ?><div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-4"><p class="text-red-600 text-sm font-bold"><?= htmlspecialchars($erro) ?></p></div><?php endif; ?>

<!-- ===== CONSULTÓRIOS ===== -->
<?php if($tab === 'consultorios'): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Consultórios Activos</h3>
<table class="w-full text-left"><thead><tr class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant border-b"><th class="pb-3">Nome</th><th class="pb-3">Responsável</th><th class="pb-3 text-right">Acções</th></tr></thead>
<tbody class="divide-y divide-surface-container-low/50">
<?php foreach($consultorios as $c): ?>
<tr class="hover:bg-surface-container-low/30"><td class="py-3 font-bold text-sm"><?= htmlspecialchars($c['nome']) ?></td><td class="py-3 text-xs text-on-surface-variant"><?= htmlspecialchars($c['responsavel'] ?? '—') ?></td>
<td class="py-3 text-right"><form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="inline m-0">
<input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>"><input type="hidden" name="acao" value="editar_consultorio">
<input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="nome" value="<?= htmlspecialchars($c['nome']) ?>">
<input type="hidden" name="responsavel" value="<?= htmlspecialchars($c['responsavel']??'') ?>"><input type="hidden" name="activo" value="0">
<button class="text-red-500 text-[10px] font-bold hover:underline">Desactivar</button></form></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
<div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Novo Consultório</h3>
<form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php">
<input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>"><input type="hidden" name="acao" value="criar_consultorio">
<div class="space-y-3">
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Nome</label>
<input type="text" name="nome" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1" placeholder="Consultório 5"></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Responsável</label>
<input type="text" name="responsavel" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm mt-1" placeholder="Opcional"></div>
<button type="submit" class="w-full bg-black text-white py-2.5 rounded-full font-black text-xs">Criar</button>
</div></form>
</div>
</div>
<?php endif; ?>

<!-- ===== ESPECIALIDADES ===== -->
<?php if($tab === 'especialidades'): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Especialidades</h3>
<table class="w-full text-left"><thead><tr class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant border-b"><th class="pb-3">Nome</th><th class="pb-3">Descrição</th></tr></thead>
<tbody class="divide-y divide-surface-container-low/50">
<?php foreach($especialidades as $e): ?>
<tr><td class="py-3 font-bold text-sm"><?= htmlspecialchars($e['nome']) ?></td><td class="py-3 text-xs text-on-surface-variant"><?= htmlspecialchars($e['descricao'] ?? '') ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
<div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Nova Especialidade</h3>
<form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php">
<input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>"><input type="hidden" name="acao" value="criar_especialidade">
<div class="space-y-3">
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Nome</label>
<input type="text" name="nome" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1"></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Descrição</label>
<input type="text" name="descricao" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm mt-1"></div>
<button type="submit" class="w-full bg-black text-white py-2.5 rounded-full font-black text-xs">Criar</button>
</div></form>
</div>
</div>
<?php endif; ?>

<!-- ===== TIPOS DE ATENDIMENTO ===== -->
<?php if($tab === 'tipos'): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Tipos de Atendimento</h3>
<table class="w-full text-left"><thead><tr class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant border-b"><th class="pb-3">Nome</th><th class="pb-3">Prefixo</th><th class="pb-3">Especialidade</th></tr></thead>
<tbody class="divide-y divide-surface-container-low/50">
<?php foreach($tipos as $t): ?>
<tr><td class="py-3 font-bold text-sm"><?= htmlspecialchars($t['nome']) ?></td><td class="py-3 text-xs font-mono font-bold"><?= htmlspecialchars($t['prefixo']) ?></td><td class="py-3 text-xs text-on-surface-variant"><?= htmlspecialchars($t['especialidade_nome'] ?? '—') ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
<div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Novo Tipo</h3>
<form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php">
<input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>"><input type="hidden" name="acao" value="criar_tipo">
<div class="space-y-3">
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Nome</label>
<input type="text" name="nome" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1"></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Prefixo (1-2 letras)</label>
<input type="text" name="prefixo" maxlength="2" value="N" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1"></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Especialidade</label>
<select name="especialidade_id" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1"><option value="">Nenhuma</option>
<?php foreach($especialidades as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option><?php endforeach; ?>
</select></div>
<button type="submit" class="w-full bg-black text-white py-2.5 rounded-full font-black text-xs">Criar</button>
</div></form>
</div>
</div>
<?php endif; ?>

<!-- ===== DISPONIBILIDADE MÉDICA ===== -->
<?php if($tab === 'disponibilidade'): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Horários Configurados</h3>
<?php if(empty($disponibilidades)): ?>
<p class="text-on-surface-variant text-sm font-semibold py-8 text-center">Nenhuma disponibilidade configurada.</p>
<?php else: ?>
<table class="w-full text-left"><thead><tr class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant border-b"><th class="pb-3">Médico</th><th class="pb-3">Dia</th><th class="pb-3">Turno</th><th class="pb-3">Capacidade</th><th class="pb-3 text-right">Acção</th></tr></thead>
<tbody class="divide-y divide-surface-container-low/50">
<?php foreach($disponibilidades as $d): ?>
<tr><td class="py-3 font-bold text-sm"><?= htmlspecialchars($d['medico_nome']) ?></td>
<td class="py-3 text-xs"><?= $diasSemana[$d['dia_semana']] ?? $d['dia_semana'] ?></td>
<td class="py-3 text-xs font-bold"><?= $d['turno']==='manha'?'Manhã':'Tarde' ?></td>
<td class="py-3 text-xs font-bold"><?= $d['capacidade'] ?> pac.</td>
<td class="py-3 text-right"><form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="inline m-0">
<input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>"><input type="hidden" name="acao" value="remover_disponibilidade"><input type="hidden" name="id" value="<?= $d['id'] ?>">
<button class="text-red-500 text-[10px] font-bold hover:underline">Remover</button></form></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Adicionar Horário</h3>
<form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php">
<input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>"><input type="hidden" name="acao" value="guardar_disponibilidade">
<div class="space-y-3">
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Médico</label>
<select name="medico_id" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1">
<option value="">Seleccionar...</option>
<?php foreach($medicos as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option><?php endforeach; ?>
</select></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Especialidade</label>
<select name="especialidade_id" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1">
<?php foreach($especialidades as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option><?php endforeach; ?>
</select></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Consultório</label>
<select name="consultorio_id" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1">
<option value="">Nenhum</option>
<?php foreach($consultorios as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option><?php endforeach; ?>
</select></div>
<div class="grid grid-cols-2 gap-2">
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Dia</label>
<select name="dia_semana" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1">
<?php foreach($diasSemana as $n=>$d): ?><option value="<?= $n ?>"><?= $d ?></option><?php endforeach; ?>
</select></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Turno</label>
<select name="turno" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1">
<option value="manha">Manhã</option><option value="tarde">Tarde</option>
</select></div>
</div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Capacidade (pacientes)</label>
<input type="number" name="capacidade" value="10" min="1" max="50" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1"></div>
<button type="submit" class="w-full bg-black text-white py-2.5 rounded-full font-black text-xs">Guardar</button>
</div></form>
</div>
</div>
<?php endif; ?>

<!-- ===== BLOQUEIOS ===== -->
<?php if($tab === 'bloqueios'): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Bloqueios Activos</h3>
<?php if(empty($bloqueios)): ?>
<p class="text-on-surface-variant text-sm font-semibold py-8 text-center">Nenhum bloqueio activo.</p>
<?php else: ?>
<table class="w-full text-left"><thead><tr class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant border-b"><th class="pb-3">Data</th><th class="pb-3">Turno</th><th class="pb-3">Médico</th><th class="pb-3">Motivo</th><th class="pb-3 text-right">Acção</th></tr></thead>
<tbody class="divide-y divide-surface-container-low/50">
<?php foreach($bloqueios as $b): ?>
<tr><td class="py-3 text-sm font-bold"><?= date('d/m/Y', strtotime($b['data_bloqueio'])) ?></td>
<td class="py-3 text-xs font-bold"><?= $b['turno']==='manha'?'Manhã':'Tarde' ?></td>
<td class="py-3 text-xs"><?= htmlspecialchars($b['medico_nome'] ?? 'Geral') ?></td>
<td class="py-3 text-xs text-on-surface-variant"><?= htmlspecialchars($b['motivo']) ?></td>
<td class="py-3 text-right"><form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="inline m-0">
<input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>"><input type="hidden" name="acao" value="remover_bloqueio"><input type="hidden" name="id" value="<?= $b['id'] ?>">
<button class="text-red-500 text-[10px] font-bold hover:underline">Remover</button></form></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white">
<h3 class="text-base font-black mb-4">Novo Bloqueio</h3>
<form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php">
<input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>"><input type="hidden" name="acao" value="criar_bloqueio">
<div class="space-y-3">
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Médico (vazio = geral)</label>
<select name="medico_id" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1">
<option value="">Bloqueio Geral</option>
<?php foreach($medicos as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option><?php endforeach; ?>
</select></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Data</label>
<input type="date" name="data_bloqueio" required min="<?= date('Y-m-d') ?>" class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1"></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Turno</label>
<select name="turno" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1">
<option value="manha">Manhã</option><option value="tarde">Tarde</option>
</select></div>
<div><label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Motivo</label>
<input type="text" name="motivo" required class="w-full rounded-xl border-surface-container-high px-3 py-2 text-sm font-bold mt-1" placeholder="Feriado, formação, etc."></div>
<button type="submit" class="w-full bg-black text-white py-2.5 rounded-full font-black text-xs">Criar Bloqueio</button>
</div></form>
</div>
</div>
<?php endif; ?>

</main>
</div>
</body></html>
