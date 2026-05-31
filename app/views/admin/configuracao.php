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
$consultorios = Disponibilidade::listarConsultorios(true);
$especialidades = Disponibilidade::listarEspecialidades(true);
$tipos = Disponibilidade::listarTiposAtendimento(true);
$disponibilidades = Disponibilidade::listarTodas(true);
$bloqueios = Disponibilidade::listarBloqueios();
$medicos = Disponibilidade::listarMedicos();

$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

$tabs = [
    'consultorios'=>'Consultórios', 'especialidades'=>'Especialidades',
    'tipos'=>'Tipos Atend.', 'disponibilidade'=>'Disponibilidade', 'vinculos'=>'Vínculos', 'bloqueios'=>'Bloqueios'
];
?>
<!DOCTYPE html>
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Configuração — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .glass-panel { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .bento-card { background: var(--cor-surface-container-lowest); border-radius: 2rem; padding: 1.5rem; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid rgba(226, 226, 226, 0.5); box-shadow: none !important; }
        .bento-card:hover { transform: translateY(-4px) scale(1.02); border-color: rgba(196, 199, 201, 0.8); box-shadow: none !important; }
        .bento-card:hover .icon-shift { transform: translateY(-2px); }
        .input-recessed { background-color: var(--cor-surface-container-low); border: 1px solid transparent; transition: all 0.2s; }
        .input-recessed:focus { background-color: var(--cor-surface-container-lowest); border-color: transparent; outline: none; }
        
        @keyframes fade-slide-up { 0% { transform: translateY(20px); opacity: 0; } 100% { transform: translateY(0); opacity: 1; } }
        .stagger-1 { animation: fade-slide-up 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; animation-delay: 0.1s; }
        .stagger-2 { animation: fade-slide-up 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; animation-delay: 0.2s; }
        .stagger-3 { animation: fade-slide-up 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; animation-delay: 0.3s; }
        .panel-enter { animation: fade-slide-up 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; animation-delay: 0.2s; }

        @keyframes pulse-glow { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
        .status-pulse { animation: pulse-glow 2s infinite; }
    </style>
</head>
<body class="text-on-surface bg-background">
<?php $paginaActual='configuracao'; include __DIR__.'/../comum/sidebar.php'; ?>
<?php $tituloPagina='Configuração'; $accoesPagina=''; include __DIR__.'/../comum/header.php'; ?>

<!-- Toast Notifications Removed as requested -->

<div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
<div class="w-full pb-24 space-y-8">



    <!-- Navigation Tabs -->
    <div class="sticky top-28 z-40 w-full mb-8">
        <div class="rounded-2xl bg-white shadow-[0_10px_40px_-10px_rgba(0,0,0,0.04)] border border-surface-container px-6 py-2 flex items-center gap-2 overflow-x-auto hide-scrollbar w-full" id="tabs-container">
            <?php foreach($tabs as $k=>$v): ?>
                <button onclick="switchTab('<?= $k ?>')" id="tab-btn-<?= $k ?>" class="tab-btn px-6 py-2.5 rounded-full font-bold text-sm transition-all relative z-10 <?= $tab===$k ? 'text-white bg-primary shadow-md' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' ?>"><?= $v ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bento Grid Layout -->

        <!-- TAB: CONSULTÓRIOS -->
        <div id="tab-content-consultorios" class="tab-content" style="<?= $tab === 'consultorios' ? '' : 'display:none;' ?>">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        <div class="xl:col-span-8 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="font-headline font-extrabold text-2xl text-on-surface">Consultórios Activos</h3>
                <span class="text-sm font-bold text-on-surface bg-white shadow-sm border border-surface-container-high px-4 py-1.5 rounded-full"><span id="total-count"><?= count($consultorios) ?></span> Total</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6" id="consultorios-grid">
                <?php foreach($consultorios as $index => $c): ?>
                    <div class="bento-card group relative overflow-hidden bg-white stagger-<?= ($index % 3) + 1 ?> <?= $c['activo'] == 0 ? 'opacity-60' : '' ?>">
                        <div class="flex justify-between items-start mb-6 relative z-10">
                            <div class="bg-surface-container-low p-3.5 rounded-2xl group-hover:bg-primary-container transition-colors duration-300 icon-shift">
                                <span class="material-symbols-outlined text-on-surface text-[28px]">door_front</span>
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                                <button onclick="abrirModalEditConsultorio(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nome'])) ?>', '<?= htmlspecialchars(addslashes($c['responsavel'] ?? '')) ?>')" class="p-2 text-on-surface-variant hover:bg-surface-container rounded-full" title="Editar"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="inline m-0">
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="acao" value="editar_consultorio">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="nome" value="<?= htmlspecialchars($c['nome']) ?>">
                                    <input type="hidden" name="responsavel" value="<?= htmlspecialchars($c['responsavel']??'') ?>">
                                    <input type="hidden" name="activo" value="<?= $c['activo'] == 1 ? '0' : '1' ?>">
                                    <button class="p-2 <?= $c['activo'] == 1 ? 'text-red-500 hover:bg-error-container' : 'text-green-500 hover:bg-green-100' ?> rounded-full" title="<?= $c['activo'] == 1 ? 'Desactivar' : 'Activar' ?>">
                                        <span class="material-symbols-outlined text-[20px]"><?= $c['activo'] == 1 ? 'power_settings_new' : 'check_circle' ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="relative z-10">
                            <h4 class="font-extrabold text-xl text-on-surface mb-1.5"><?= htmlspecialchars($c['nome']) ?></h4>
                            <p class="text-sm text-on-surface-variant flex items-center gap-2 mb-6 font-medium">
                                <span class="material-symbols-outlined text-[18px]">person</span> <?= htmlspecialchars($c['responsavel'] ?: 'Sem Responsável') ?>
                            </p>
                            <div class="flex items-center justify-between pt-5 border-t border-surface-container-highest/50">
                                <?php if($c['activo'] == 1): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-[var(--cor-success-light)] text-[var(--cor-success-dark)]">
                                        <span class="w-2 h-2 rounded-full bg-[var(--cor-success)] status-pulse"></span> Operacional
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-surface-container-low text-[var(--cor-inactive-text)]">
                                        <span class="w-2 h-2 rounded-full bg-[var(--cor-inactive-dot)]"></span> Inactivo
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Formulário Direito -->
        <div class="xl:col-span-4 self-start sticky top-32 z-10">
            <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_12px_40px_rgb(0,0,0,0.06)] border border-surface-container-high panel-enter">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="font-headline font-extrabold text-2xl text-on-surface">Novo Consultório</h3>
                        <p class="text-sm text-on-surface-variant mt-1.5 font-medium">Insira os dados estruturais.</p>
                    </div>
                    <div class="bg-surface-container-low p-3 rounded-2xl"><span class="material-symbols-outlined text-on-surface text-2xl">add_business</span></div>
                </div>
                <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                    <input type="hidden" name="acao" value="criar_consultorio">
                    
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Nome da Sala</label>
                        <input name="nome" required type="text" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary/20" placeholder="Ex: Consultório 5">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Responsável (Opcional)</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-outline-variant text-[20px]">person</span>
                            <input name="responsavel" type="text" class="input-recessed w-full rounded-2xl pl-12 pr-5 py-4 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary/20" placeholder="Nome...">
                        </div>
                    </div>
                    <button type="button" class="form-submit-btn w-full bg-primary text-white font-extrabold py-4 rounded-xl mt-4 hover:bg-inverse-surface transition-all duration-300 shadow-md hover:-translate-y-0.5 flex items-center justify-center gap-2 relative overflow-hidden group">
                        <span class="material-symbols-outlined text-[22px] transition-transform duration-300 group-hover:rotate-90 btn-icon">add</span>
                        <span class="btn-text">Criar Consultório</span>
                        <div class="loader absolute hidden w-[20px] h-[20px] rounded-full border-2 border-white/30 border-t-white animate-spin btn-loader"></div>
                    </button>
                </form>
            </div>
        </div>
        </div>
        </div>

        <!-- TAB: ESPECIALIDADES -->
        <div id="tab-content-especialidades" class="tab-content" style="<?= $tab === 'especialidades' ? '' : 'display:none;' ?>">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        <div class="xl:col-span-8 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="font-headline font-extrabold text-2xl text-on-surface">Especialidades</h3>
                <span class="text-sm font-bold text-on-surface bg-white shadow-sm border border-surface-container-high px-4 py-1.5 rounded-full"><?= count($especialidades) ?> Total</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php foreach($especialidades as $index => $e): ?>
                    <div class="bento-card group relative overflow-hidden bg-white stagger-<?= ($index % 3) + 1 ?> <?= $e['activo'] == 0 ? 'opacity-60' : '' ?>">
                        <div class="flex justify-between items-start mb-6 relative z-10">
                            <div class="bg-surface-container-low p-3.5 rounded-2xl group-hover:bg-primary-container transition-colors duration-300 icon-shift">
                                <span class="material-symbols-outlined text-on-surface text-[28px]">medical_services</span>
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                                <button onclick="abrirModalEditEspecialidade(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['nome'])) ?>', '<?= htmlspecialchars(addslashes($e['descricao'] ?? '')) ?>')" class="p-2 text-on-surface-variant hover:bg-surface-container rounded-full" title="Editar"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="inline m-0">
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="acao" value="editar_especialidade">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <input type="hidden" name="nome" value="<?= htmlspecialchars($e['nome']) ?>">
                                    <input type="hidden" name="descricao" value="<?= htmlspecialchars($e['descricao']??'') ?>">
                                    <input type="hidden" name="activo" value="<?= $e['activo'] == 1 ? '0' : '1' ?>">
                                    <button class="p-2 <?= $e['activo'] == 1 ? 'text-red-500 hover:bg-error-container' : 'text-green-500 hover:bg-green-100' ?> rounded-full" title="<?= $e['activo'] == 1 ? 'Desactivar' : 'Activar' ?>">
                                        <span class="material-symbols-outlined text-[20px]"><?= $e['activo'] == 1 ? 'power_settings_new' : 'check_circle' ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="relative z-10">
                            <h4 class="font-extrabold text-xl text-on-surface mb-1.5"><?= htmlspecialchars($e['nome']) ?></h4>
                            <p class="text-sm text-on-surface-variant font-medium line-clamp-2 min-h-[40px]">
                                <?= htmlspecialchars($e['descricao'] ?: 'Sem descrição.') ?>
                            </p>
                            <div class="flex items-center justify-between pt-5 mt-4 border-t border-surface-container-highest/50">
                                <?php if($e['activo'] == 1): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-[var(--cor-success-light)] text-[var(--cor-success-dark)]"><span class="w-2 h-2 rounded-full bg-[var(--cor-success)] status-pulse"></span> Activa</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-surface-container-low text-[var(--cor-inactive-text)]"><span class="w-2 h-2 rounded-full bg-[var(--cor-inactive-dot)]"></span> Inactiva</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Formulário -->
        <div class="xl:col-span-4 self-start sticky top-32 z-10">
            <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_12px_40px_rgb(0,0,0,0.06)] border border-surface-container-high panel-enter">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="font-headline font-extrabold text-2xl text-on-surface">Nova Especialidade</h3>
                    </div>
                    <div class="bg-surface-container-low p-3 rounded-2xl"><span class="material-symbols-outlined text-on-surface text-2xl">post_add</span></div>
                </div>
                <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                    <input type="hidden" name="acao" value="criar_especialidade">
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Nome</label>
                        <input name="nome" required type="text" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary/20" placeholder="Ex: Cardiologia">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Descrição</label>
                        <input name="descricao" type="text" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary/20" placeholder="Descrição rápida...">
                    </div>
                    <button type="button" class="form-submit-btn w-full bg-primary text-white font-extrabold py-4 rounded-xl mt-4 hover:bg-inverse-surface transition-all duration-300 shadow-md hover:-translate-y-0.5 flex items-center justify-center gap-2 relative overflow-hidden group">
                        <span class="material-symbols-outlined text-[22px] transition-transform duration-300 group-hover:rotate-90 btn-icon">add</span>
                        <span class="btn-text">Criar Especialidade</span>
                        <div class="loader absolute hidden w-[20px] h-[20px] rounded-full border-2 border-white/30 border-t-white animate-spin btn-loader"></div>
                    </button>
                </form>
            </div>
        </div>
        </div>
        </div>

        <!-- TAB: TIPOS -->
        <div id="tab-content-tipos" class="tab-content" style="<?= $tab === 'tipos' ? '' : 'display:none;' ?>">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        <div class="xl:col-span-8 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="font-headline font-extrabold text-2xl text-on-surface">Tipos de Atendimento</h3>
                <span class="text-sm font-bold text-on-surface bg-white shadow-sm border border-surface-container-high px-4 py-1.5 rounded-full"><?= count($tipos) ?> Total</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php foreach($tipos as $index => $t): ?>
                    <div class="bento-card group relative overflow-hidden bg-white stagger-<?= ($index % 3) + 1 ?> <?= $t['activo'] == 0 ? 'opacity-60' : '' ?>">
                        <div class="flex justify-between items-start mb-6 relative z-10">
                            <div class="bg-surface-container-low p-3.5 rounded-2xl font-mono text-2xl font-black text-on-surface group-hover:bg-primary-container transition-colors duration-300 icon-shift">
                                <?= htmlspecialchars($t['prefixo']) ?>
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                                <button onclick="abrirModalEditTipo(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nome'])) ?>', '<?= htmlspecialchars(addslashes($t['prefixo'])) ?>', '<?= $t['especialidade_id'] ?? '' ?>')" class="p-2 text-on-surface-variant hover:bg-surface-container rounded-full" title="Editar"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="inline m-0">
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="acao" value="editar_tipo">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <input type="hidden" name="nome" value="<?= htmlspecialchars($t['nome']) ?>">
                                    <input type="hidden" name="prefixo" value="<?= htmlspecialchars($t['prefixo']) ?>">
                                    <input type="hidden" name="especialidade_id" value="<?= htmlspecialchars($t['especialidade_id']??'') ?>">
                                    <input type="hidden" name="activo" value="<?= $t['activo'] == 1 ? '0' : '1' ?>">
                                    <button class="p-2 <?= $t['activo'] == 1 ? 'text-red-500 hover:bg-error-container' : 'text-green-500 hover:bg-green-100' ?> rounded-full" title="<?= $t['activo'] == 1 ? 'Desactivar' : 'Activar' ?>">
                                        <span class="material-symbols-outlined text-[20px]"><?= $t['activo'] == 1 ? 'power_settings_new' : 'check_circle' ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="relative z-10">
                            <h4 class="font-extrabold text-xl text-on-surface mb-1.5"><?= htmlspecialchars($t['nome']) ?></h4>
                            <p class="text-sm text-on-surface-variant flex items-center gap-2 mb-2 font-medium">
                                <span class="material-symbols-outlined text-[18px]">medical_services</span> Especialidade: <?= htmlspecialchars($t['especialidade_nome'] ?: 'Nenhuma') ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Formulário -->
        <div class="xl:col-span-4 self-start sticky top-32 z-10">
            <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_12px_40px_rgb(0,0,0,0.06)] border border-surface-container-high panel-enter">
                <div class="flex justify-between items-start mb-8">
                    <div><h3 class="font-headline font-extrabold text-2xl text-on-surface">Novo Tipo</h3></div>
                    <div class="bg-surface-container-low p-3 rounded-2xl"><span class="material-symbols-outlined text-on-surface text-2xl">category</span></div>
                </div>
                <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                    <input type="hidden" name="acao" value="criar_tipo">
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Nome</label>
                        <input name="nome" required type="text" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-primary/20" placeholder="Urgência">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Prefixo (1-2 letras)</label>
                        <input name="prefixo" maxlength="2" required type="text" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-bold uppercase focus:ring-2 focus:ring-primary/20" placeholder="UR">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Especialidade Vinculada</label>
                        <select name="especialidade_id" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-primary/20">
                            <option value="">Nenhuma</option>
                            <?php foreach($especialidades as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="form-submit-btn w-full bg-primary text-white font-extrabold py-4 rounded-xl mt-4 hover:bg-inverse-surface transition-all duration-300 shadow-md hover:-translate-y-0.5 flex items-center justify-center gap-2 relative overflow-hidden group">
                        <span class="material-symbols-outlined text-[22px] transition-transform duration-300 group-hover:rotate-90 btn-icon">add</span>
                        <span class="btn-text">Criar Tipo</span>
                        <div class="loader absolute hidden w-[20px] h-[20px] rounded-full border-2 border-white/30 border-t-white animate-spin btn-loader"></div>
                    </button>
                </form>
            </div>
        </div>
        </div>
        </div>

        <!-- TAB: DISPONIBILIDADE -->
        <div id="tab-content-disponibilidade" class="tab-content" style="<?= $tab === 'disponibilidade' ? '' : 'display:none;' ?>">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        <div class="xl:col-span-8 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="font-headline font-extrabold text-2xl text-on-surface">Agendas Futuras</h3>
            </div>
            <?php if(empty($disponibilidades)): ?>
                <div class="bento-card bg-white flex flex-col items-center justify-center min-h-[250px] text-center">
                    <span class="material-symbols-outlined text-4xl text-outline-variant mb-4">event_busy</span>
                    <h4 class="font-bold text-lg text-on-surface">Sem Agendas</h4>
                    <p class="text-sm text-on-surface-variant">Nenhuma disponibilidade futura configurada.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php foreach($disponibilidades as $index => $d): ?>
                    <div class="bento-card group relative bg-white stagger-<?= ($index % 3) + 1 ?>">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="font-extrabold text-lg text-on-surface"><?= htmlspecialchars($d['medico_nome']) ?></h4>
                                <span class="text-[11px] uppercase font-bold tracking-widest text-on-surface-variant bg-surface-container-low px-2 py-1 rounded-md"><?= date('d/m/Y', strtotime($d['data_disponibilidade'])) ?></span>
                            </div>
                            <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php">
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="acao" value="remover_disponibilidade">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <button class="text-error bg-error-container/20 p-2 rounded-full hover:bg-error-container transition-colors"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                            </form>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-surface-container-highest/50">
                            <div><p class="text-[10px] text-on-surface-variant uppercase font-bold">Turno</p><p class="text-sm font-bold text-on-surface capitalize"><?= $d['turno'] ?></p></div>
                            <div><p class="text-[10px] text-on-surface-variant uppercase font-bold">Capacidade</p><p class="text-sm font-bold text-on-surface"><?= $d['capacidade'] ?> pac.</p></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- Formulário -->
        <div class="xl:col-span-4 self-start sticky top-32 z-10">
            <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_12px_40px_rgb(0,0,0,0.06)] border border-surface-container-high panel-enter relative z-50">
                <div class="flex justify-between items-start mb-8">
                    <div><h3 class="font-headline font-extrabold text-2xl text-on-surface">Novo Horário</h3></div>
                    <div class="bg-surface-container-low p-3 rounded-2xl"><span class="material-symbols-outlined text-on-surface text-2xl">event_available</span></div>
                </div>
                <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                    <input type="hidden" name="acao" value="guardar_disponibilidade">
                    
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Médico</label>
                        <select name="medico_id" id="disp_medico_id" required onchange="carregarVinculos()" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-primary/20">
                            <option value="">Seleccionar...</option>
                            <?php foreach($medicos as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Especialidade</label>
                        <select name="especialidade_id" id="disp_especialidade_id" required class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-primary/20">
                            <option value="">Selecione médico primeiro...</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Consultório</label>
                        <select name="consultorio_id" id="disp_consultorio_id" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-primary/20">
                            <option value="">Nenhum</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-3 mt-1">
                        <div class="w-full">
                            <label class="text-[10px] uppercase font-black tracking-widest text-on-surface-variant ml-1 mb-1 block">Data</label>
                            <?php 
                            $cal_id = 'cal-disponibilidade';
                            $cal_name = 'data_disponibilidade';
                            $cal_value = '';
                            $cal_min = date('Y-m-d');
                            $cal_right = true;
                            $cal_class = 'w-full';
                            $cal_label = 'Seleccione a data...';
                            require __DIR__ . '/../comum/calendario_dropdown.php'; 
                            ?>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Turno</label>
                            <select name="turno" required class="input-recessed w-full rounded-2xl px-4 py-4 text-sm font-medium focus:ring-2 focus:ring-primary/20">
                                <option value="manha">Manhã</option><option value="tarde">Tarde</option><option value="ambos">Ambos</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Capacidade (Qtd.)</label>
                        <input type="number" name="capacidade" value="10" min="1" max="50" required class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-primary/20">
                    </div>
                    <button type="button" class="form-submit-btn w-full bg-primary text-white font-extrabold py-4 rounded-xl mt-6 hover:bg-inverse-surface transition-all duration-300 shadow-md hover:-translate-y-0.5 flex items-center justify-center gap-2 relative overflow-hidden group">
                        <span class="material-symbols-outlined text-[22px] transition-transform duration-300 group-hover:rotate-90 btn-icon">event_available</span>
                        <span class="btn-text">Guardar Horário</span>
                        <div class="loader absolute hidden w-[20px] h-[20px] rounded-full border-2 border-white/30 border-t-white animate-spin btn-loader"></div>
                    </button>
                </form>
            </div>
        </div>
        </div>
        </div>

        <!-- TAB: VÍNCULOS -->
        <div id="tab-content-vinculos" class="tab-content" style="<?= $tab === 'vinculos' ? '' : 'display:none;' ?>">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        <div class="lg:col-span-12 stagger-1 bg-white rounded-[2.5rem] p-8 md:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-surface-container-high">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="font-headline font-extrabold text-3xl text-on-surface">Vínculos Operacionais</h3>
                        <p class="text-on-surface-variant font-medium mt-2">Associe médicos às suas especialidades e locais de atendimento para libertá-los no módulo de agendas.</p>
                    </div>
                    <div class="bg-primary-container p-4 rounded-2xl"><span class="material-symbols-outlined text-primary text-3xl">link</span></div>
                </div>
                
                <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                    <input type="hidden" name="acao" value="sincronizar_vinculos">
                    
                    <div class="space-y-4 md:col-span-1 flex flex-col">
                        <div class="p-6 bg-surface-container-low rounded-[2.5rem] flex-1">
                            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase block mb-3">Seleccione o Médico</label>
                            <select name="medico_id" id="vinculos_medico_id" required class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-primary/20 bg-white" onchange="carregarVinculosEdicao()">
                                <option value="">Escolher Médico...</option>
                                <?php foreach($medicos as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option><?php endforeach; ?>
                            </select>
                            <div class="mt-8 text-sm text-on-surface-variant font-medium">
                                <span class="material-symbols-outlined text-primary mb-2 text-3xl">touch_app</span><br>
                                Seleccione um médico para ver e editar os seus vínculos activos. Pode marcar ou desmarcar as opções livremente nas listas à direita.
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4 md:col-span-1 flex flex-col h-[400px]">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase block shrink-0 pl-2">Especialidades</label>
                        <div class="bg-white border border-surface-container-high rounded-[2.5rem] flex-1 overflow-hidden shadow-sm flex flex-col p-2">
                            <div class="flex-1 overflow-y-auto custom-scrollbar px-1">
                                <div class="space-y-1 py-1" id="vinculos_especialidades">
                                    <?php foreach($especialidades as $e): ?>
                                        <label class="flex items-center justify-between p-3 rounded-2xl cursor-pointer transition-all duration-200 border border-transparent hover:bg-surface-container-low has-[:checked]:bg-primary-container has-[:checked]:border-primary/20 group">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-5 h-5 rounded-md border-2 border-outline flex items-center justify-center bg-white group-has-[:checked]:bg-primary group-has-[:checked]:border-primary transition-colors shrink-0">
                                                    <span class="material-symbols-outlined text-white text-[14px] opacity-0 group-has-[:checked]:opacity-100 transition-opacity font-bold">check</span>
                                                </div>
                                                <div class="w-8 h-8 rounded-full bg-surface-container-highest/50 flex items-center justify-center group-has-[:checked]:bg-primary/20 transition-colors shrink-0">
                                                    <span class="material-symbols-outlined text-on-surface-variant group-has-[:checked]:text-primary text-[18px] transition-colors">medical_services</span>
                                                </div>
                                                <span class="text-sm font-bold text-on-surface group-has-[:checked]:text-primary transition-colors truncate"><?= htmlspecialchars($e['nome']) ?></span>
                                            </div>
                                            <input type="checkbox" name="especialidades[]" value="<?= $e['id'] ?>" class="hidden checkbox-especialidade">
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 md:col-span-1 flex flex-col h-[400px]">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase block shrink-0 pl-2">Consultórios</label>
                        <div class="bg-white border border-surface-container-high rounded-[2.5rem] flex-1 overflow-hidden shadow-sm flex flex-col p-2">
                            <div class="flex-1 overflow-y-auto custom-scrollbar px-1">
                                <div class="space-y-1 py-1" id="vinculos_consultorios">
                                    <?php foreach($consultorios as $c): ?>
                                        <label class="flex items-center justify-between p-3 rounded-2xl cursor-pointer transition-all duration-200 border border-transparent hover:bg-surface-container-low has-[:checked]:bg-primary-container has-[:checked]:border-primary/20 group">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-5 h-5 rounded-md border-2 border-outline flex items-center justify-center bg-white group-has-[:checked]:bg-primary group-has-[:checked]:border-primary transition-colors shrink-0">
                                                    <span class="material-symbols-outlined text-white text-[14px] opacity-0 group-has-[:checked]:opacity-100 transition-opacity font-bold">check</span>
                                                </div>
                                                <div class="w-8 h-8 rounded-full bg-surface-container-highest/50 flex items-center justify-center group-has-[:checked]:bg-primary/20 transition-colors shrink-0">
                                                    <span class="material-symbols-outlined text-on-surface-variant group-has-[:checked]:text-primary text-[18px] transition-colors">meeting_room</span>
                                                </div>
                                                <span class="text-sm font-bold text-on-surface group-has-[:checked]:text-primary transition-colors truncate"><?= htmlspecialchars($c['nome']) ?></span>
                                            </div>
                                            <input type="checkbox" name="consultorios[]" value="<?= $c['id'] ?>" class="hidden checkbox-consultorio">
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-3 pt-6 border-t border-surface-container flex justify-end">
                        <button type="button" class="form-submit-btn bg-primary text-white font-extrabold px-8 py-4 rounded-xl hover:bg-inverse-surface transition-all duration-300 shadow-md hover:-translate-y-0.5 flex items-center justify-center gap-2 relative overflow-hidden group">
                            <span class="material-symbols-outlined text-[22px] transition-transform duration-300 group-hover:rotate-180 btn-icon">sync</span>
                            <span class="btn-text">Sincronizar Vínculos</span>
                            <div class="loader absolute hidden w-[20px] h-[20px] rounded-full border-2 border-white/30 border-t-white animate-spin btn-loader"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>

        <!-- TAB: BLOQUEIOS -->
        <div id="tab-content-bloqueios" class="tab-content" style="<?= $tab === 'bloqueios' ? '' : 'display:none;' ?>">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        <div class="xl:col-span-8 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="font-headline font-extrabold text-2xl text-on-surface">Bloqueios de Agenda</h3>
            </div>
            <?php if(empty($bloqueios)): ?>
                <div class="bento-card bg-white flex flex-col items-center justify-center min-h-[250px] text-center">
                    <span class="material-symbols-outlined text-4xl text-outline-variant mb-4">check_circle</span>
                    <h4 class="font-bold text-lg text-on-surface">Tudo Operacional</h4>
                    <p class="text-sm text-on-surface-variant">Nenhum bloqueio ou feriado configurado no momento.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php foreach($bloqueios as $index => $b): ?>
                    <div class="bento-card group relative bg-[var(--cor-danger-light)] border-[var(--cor-danger-border)] stagger-<?= ($index % 3) + 1 ?>">
                        <div class="flex justify-between items-start mb-4">
                            <div class="bg-[var(--cor-danger-icon-bg)] p-2 rounded-xl text-[var(--cor-danger)]">
                                <span class="material-symbols-outlined">block</span>
                            </div>
                            <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php">
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="acao" value="remover_bloqueio">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button class="text-error bg-white/50 p-2 rounded-full hover:bg-white transition-colors"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                            </form>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-lg text-[var(--cor-danger)] mb-1"><?= htmlspecialchars($b['motivo']) ?></h4>
                            <p class="text-xs font-bold text-[var(--cor-danger-subtitle)] uppercase tracking-wider mb-4">
                                <?= date('d/m/Y', strtotime($b['data_bloqueio'])) ?> — <?= $b['turno']==='manha'?'Manhã':'Tarde' ?>
                            </p>
                            <div class="pt-4 border-t border-[var(--cor-danger-icon-bg)]/50 text-sm font-semibold text-[var(--cor-danger-body)]">
                                Alvo: <?= htmlspecialchars($b['medico_nome'] ?? 'Bloqueio Geral (Todo o Hospital)') ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- Formulário -->
        <div class="xl:col-span-4 self-start sticky top-32 z-10">
            <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_12px_40px_rgb(0,0,0,0.06)] border border-surface-container-high panel-enter relative z-50">
                <div class="flex justify-between items-start mb-8">
                    <div><h3 class="font-headline font-extrabold text-2xl text-on-surface">Novo Bloqueio</h3></div>
                    <div class="bg-error-container text-error p-3 rounded-2xl"><span class="material-symbols-outlined text-2xl">block</span></div>
                </div>
                <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                    <input type="hidden" name="acao" value="criar_bloqueio">
                    
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Alvo (Deixe vazio para Geral)</label>
                        <select name="medico_id" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-error/20">
                            <option value="">Bloqueio Geral (Todos)</option>
                            <?php foreach($medicos as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex flex-col gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Data</label>
                            <?php 
                            $cal_id = 'cal-bloqueio';
                            $cal_name = 'data_bloqueio';
                            $cal_value = '';
                            $cal_min = date('Y-m-d');
                            $cal_right = true;
                            $cal_class = 'w-full';
                            $cal_label = 'Seleccione a data...';
                            require __DIR__ . '/../comum/calendario_dropdown.php'; 
                            ?>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Turno</label>
                            <select name="turno" required class="input-recessed w-full rounded-2xl px-4 py-4 text-sm font-medium focus:ring-2 focus:ring-error/20">
                                <option value="manha">Manhã</option><option value="tarde">Tarde</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Motivo</label>
                        <input name="motivo" required type="text" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium focus:ring-2 focus:ring-error/20" placeholder="Feriado, Reunião, Obras...">
                    </div>
                    <button type="button" class="form-submit-btn w-full bg-error text-white font-extrabold py-4 rounded-xl mt-6 hover:bg-[var(--cor-danger-hover)] transition-all duration-300 shadow-md hover:-translate-y-0.5 flex items-center justify-center gap-2 relative overflow-hidden group">
                        <span class="material-symbols-outlined text-[22px] transition-transform duration-300 group-hover:rotate-90 btn-icon">block</span>
                        <span class="btn-text">Criar Bloqueio</span>
                        <div class="loader absolute hidden w-[20px] h-[20px] rounded-full border-2 border-white/30 border-t-white animate-spin btn-loader"></div>
                    </button>
                </form>
            </div>
        </div>
        </div>
        </div>

</div>
</main>
</div> <!-- End Main Content Area -->

<!-- Modais de Edição com UI Adaptada (Bento style) -->
<dialog id="modalEditConsultorio" class="bg-white rounded-[2.5rem] p-8 backdrop:bg-primary/40 shadow-none w-full max-w-md m-auto border border-surface-container-high">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-headline font-extrabold text-2xl text-on-surface">Editar Consultório</h3>
        <button onclick="document.getElementById('modalEditConsultorio').close()" class="text-on-surface-variant hover:text-black font-bold p-2 bg-surface-container-low rounded-full"><span class="material-symbols-outlined text-[20px]">close</span></button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="acao" value="editar_consultorio">
        <input type="hidden" name="id" id="editConsId">
        <input type="hidden" name="activo" value="1">
        <div class="space-y-2">
            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Nome</label>
            <input type="text" name="nome" id="editConsNome" required class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium text-on-surface">
        </div>
        <div class="space-y-2">
            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Responsável</label>
            <input type="text" name="responsavel" id="editConsResp" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium text-on-surface">
        </div>
        <button type="submit" class="w-full bg-primary text-white font-extrabold py-4 rounded-xl mt-4 hover:bg-inverse-surface transition-all">Guardar Alterações</button>
    </form>
</dialog>

<dialog id="modalEditEspecialidade" class="bg-white rounded-[2.5rem] p-8 backdrop:bg-primary/40 shadow-none w-full max-w-md m-auto border border-surface-container-high">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-headline font-extrabold text-2xl text-on-surface">Editar Especialidade</h3>
        <button onclick="document.getElementById('modalEditEspecialidade').close()" class="text-on-surface-variant hover:text-black font-bold p-2 bg-surface-container-low rounded-full"><span class="material-symbols-outlined text-[20px]">close</span></button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="acao" value="editar_especialidade">
        <input type="hidden" name="id" id="editEspId">
        <input type="hidden" name="activo" value="1">
        <div class="space-y-2">
            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Nome</label>
            <input type="text" name="nome" id="editEspNome" required class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium text-on-surface">
        </div>
        <div class="space-y-2">
            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Descrição</label>
            <input type="text" name="descricao" id="editEspDesc" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium text-on-surface">
        </div>
        <button type="submit" class="w-full bg-primary text-white font-extrabold py-4 rounded-xl mt-4 hover:bg-inverse-surface transition-all">Guardar Alterações</button>
    </form>
</dialog>

<dialog id="modalEditTipo" class="bg-white rounded-[2.5rem] p-8 backdrop:bg-primary/40 shadow-none w-full max-w-md m-auto border border-surface-container-high">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-headline font-extrabold text-2xl text-on-surface">Editar Tipo</h3>
        <button onclick="document.getElementById('modalEditTipo').close()" class="text-on-surface-variant hover:text-black font-bold p-2 bg-surface-container-low rounded-full"><span class="material-symbols-outlined text-[20px]">close</span></button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>app/controllers/admin_config.php" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="acao" value="editar_tipo">
        <input type="hidden" name="id" id="editTipoId">
        <input type="hidden" name="activo" value="1">
        <div class="space-y-2">
            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Nome</label>
            <input type="text" name="nome" id="editTipoNome" required class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium">
        </div>
        <div class="space-y-2">
            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Prefixo</label>
            <input type="text" name="prefixo" id="editTipoPrefixo" maxlength="2" required class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium">
        </div>
        <div class="space-y-2">
            <label class="text-xs font-extrabold text-on-surface tracking-wider uppercase pl-1">Especialidade</label>
            <select name="especialidade_id" id="editTipoEsp" class="input-recessed w-full rounded-2xl px-5 py-4 text-sm font-medium">
                <option value="">Nenhuma</option>
                <?php foreach($especialidades as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="w-full bg-primary text-white font-extrabold py-4 rounded-xl mt-4 hover:bg-inverse-surface transition-all">Guardar Alterações</button>
    </form>
</dialog>

<script>
// Tab switching
function switchTab(tabId) {
    // Esconder todos
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('text-white', 'bg-primary', 'shadow-md');
        btn.classList.add('text-on-surface-variant', 'hover:text-on-surface', 'hover:bg-surface-container-low');
    });

    // Mostrar activo
    document.getElementById('tab-content-' + tabId).style.display = 'block';
    const activeBtn = document.getElementById('tab-btn-' + tabId);
    if(activeBtn) {
        activeBtn.classList.add('text-white', 'bg-primary', 'shadow-md');
        activeBtn.classList.remove('text-on-surface-variant', 'hover:text-on-surface', 'hover:bg-surface-container-low');
    }
    
    // Atualizar URL sem recarregar a página para manter o histórico correto
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.pushState({}, '', url);
}

// Interação botões submit usando Fetch
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.form-submit-btn').forEach(submitBtn => {
        submitBtn.addEventListener('click', async () => {
            const btnIcon = submitBtn.querySelector('.btn-icon');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoader = submitBtn.querySelector('.btn-loader');
            const form = submitBtn.closest('form');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const originalText = btnText.textContent;
            const originalIcon = btnIcon.textContent;

            submitBtn.classList.add('cursor-not-allowed', 'opacity-90');
            btnIcon.classList.add('hidden');
            btnText.classList.add('invisible');
            btnLoader.classList.remove('hidden');

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    redirect: 'follow'
                });
                
                // Atualizar UI sem recarregar a página
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Atualizar as grelhas e contadores de todas as abas normais (substitui a coluna da esquerda)
                const tabsComGrid = ['consultorios', 'especialidades', 'tipos', 'disponibilidade', 'bloqueios'];
                tabsComGrid.forEach(t => {
                    const selector = '#tab-content-' + t + ' > div.grid > div:first-child';
                    const newGrid = doc.querySelector(selector);
                    const oldGrid = document.querySelector(selector);
                    if (newGrid && oldGrid) {
                        oldGrid.innerHTML = newGrid.innerHTML;
                    }
                });
                
                // Atualizar a tabela de vínculos
                const newTable = doc.querySelector('#tab-content-vinculos table');
                const oldTable = document.querySelector('#tab-content-vinculos table');
                if (newTable && oldTable) {
                    oldTable.innerHTML = newTable.innerHTML;
                }
                
            } catch (error) {
                console.error("Erro ao submeter:", error);
            }

            setTimeout(() => {
                btnLoader.classList.add('hidden');
                btnIcon.classList.remove('hidden', 'group-hover:rotate-90', 'group-hover:rotate-180');
                btnIcon.textContent = 'check';
                submitBtn.classList.add('!bg-[var(--cor-success)]');
                btnText.classList.remove('invisible');
                btnText.textContent = 'Sucesso!';

                // Voltar ao estado original após 2 segundos
                setTimeout(() => {
                    form.reset();
                    btnIcon.textContent = originalIcon;
                    if(originalIcon === 'sync') {
                        btnIcon.classList.add('group-hover:rotate-180');
                    } else {
                        btnIcon.classList.add('group-hover:rotate-90');
                    }
                    submitBtn.classList.remove('!bg-[var(--cor-success)]', 'cursor-not-allowed', 'opacity-90');
                    btnText.textContent = originalText;
                }, 2000);
            }, 600);
        });
    });
});

// API Functions
function carregarVinculos() {
    const medicoId = document.getElementById('disp_medico_id').value;
    const espSelect = document.getElementById('disp_especialidade_id');
    const consSelect = document.getElementById('disp_consultorio_id');

    if (!medicoId) {
        espSelect.innerHTML = '<option value="">Selecione um médico primeiro...</option>';
        consSelect.innerHTML = '<option value="">Nenhum</option>';
        return;
    }

    fetch(`<?= BASE_URL ?>app/controllers/api_vinculos_medico.php?medico_id=${medicoId}`)
        .then(res => res.json())
        .then(data => {
            if (data.erro) { alert(data.erro); return; }
            espSelect.innerHTML = '';
            if (data.especialidades.length === 0) {
                espSelect.innerHTML = '<option value="">(Nenhuma especialidade vinculada)</option>';
            } else {
                data.especialidades.forEach((e, i) => {
                    espSelect.insertAdjacentHTML('beforeend', `<option value="${e.id}" ${i===0?'selected':''}>${e.nome}</option>`);
                });
            }
            consSelect.innerHTML = '';
            if (data.consultorios.length === 0) {
                consSelect.innerHTML = '<option value="">Nenhum</option>';
            } else {
                data.consultorios.forEach((c, i) => {
                    consSelect.insertAdjacentHTML('beforeend', `<option value="${c.id}" ${i===0?'selected':''}>${c.nome}</option>`);
                });
            }
        }).catch(err => console.error("Erro na API:", err));
}

function carregarVinculosEdicao() {
    const medicoId = document.getElementById('vinculos_medico_id').value;
    const espCheckboxes = document.querySelectorAll('.checkbox-especialidade');
    const consCheckboxes = document.querySelectorAll('.checkbox-consultorio');
    if (!medicoId) {
        espCheckboxes.forEach(cb => cb.checked = false);
        consCheckboxes.forEach(cb => cb.checked = false);
        return;
    }
    fetch(`<?= BASE_URL ?>app/controllers/api_vinculos_medico.php?medico_id=${medicoId}`)
        .then(res => res.json())
        .then(data => {
            if (data.erro) return;
            const espIds = data.especialidades.map(e => e.id.toString());
            const consIds = data.consultorios.map(c => c.id.toString());
            espCheckboxes.forEach(cb => cb.checked = espIds.includes(cb.value));
            consCheckboxes.forEach(cb => cb.checked = consIds.includes(cb.value));
        });
}

// Modal actions
function abrirModalEditConsultorio(id, nome, resp) {
    document.getElementById('editConsId').value = id;
    document.getElementById('editConsNome').value = nome;
    document.getElementById('editConsResp').value = resp;
    document.getElementById('modalEditConsultorio').showModal();
}
function abrirModalEditEspecialidade(id, nome, desc) {
    document.getElementById('editEspId').value = id;
    document.getElementById('editEspNome').value = nome;
    document.getElementById('editEspDesc').value = desc;
    document.getElementById('modalEditEspecialidade').showModal();
}
function abrirModalEditTipo(id, nome, prefixo, espId) {
    document.getElementById('editTipoId').value = id;
    document.getElementById('editTipoNome').value = nome;
    document.getElementById('editTipoPrefixo').value = prefixo;
    document.getElementById('editTipoEsp').value = espId;
    document.getElementById('modalEditTipo').showModal();
}
</script>
</body></html>
