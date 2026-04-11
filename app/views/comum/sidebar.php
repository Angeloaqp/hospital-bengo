<?php
// ================================================
// Componente Reutilizável: Sidebar Tailwind SaaS
// ================================================

$_perfil = sessao('perfil');
$_paginaActual = $paginaActual ?? '';

if (!isset($meuPerfilObject)) {
    require_once __DIR__ . '/../../../app/models/Utilizador.php';
    $meuPerfilObject = Utilizador::obter((int) sessao('utilizador_id'));
}
$_fotoPathSidebar = $meuPerfilObject['foto_path'] ?? '';
$_inicialSidebar = strtoupper(substr($meuPerfilObject['nome'] ?? sessao('nome_utilizador') ?? 'U', 0, 1));

// URLs base por perfil
$_baseAdmin = BASE_URL . 'app/views/admin/';
$_baseMedico = BASE_URL . 'app/views/medico/';
$_baseRecepcao = BASE_URL . 'app/views/recepcionista/';
$_baseComum = BASE_URL . 'app/views/comum/';
$_basePerfil = BASE_URL . 'app/views/perfil/';

// Definir links por perfil
$_navLinks = [];

if ($_perfil === 'recepcionista') {
    $_navLinks = [
        ['id' => 'dashboard',  'url' => $_baseRecepcao . 'dashboard.php',  'icon' => 'dashboard', 'titulo' => 'Dashboard'],
        ['id' => 'registar',   'url' => $_baseRecepcao . 'registar.php',   'icon' => 'person_add', 'titulo' => 'Novo Paciente'],
        ['id' => 'pesquisar',  'url' => $_baseRecepcao . 'pesquisar.php',  'icon' => 'search', 'titulo' => 'Pesquisar'],
        ['id' => 'mensagens',  'url' => $_baseComum . 'mensagens.php',     'icon' => 'mail', 'titulo' => 'Mensagens'],
    ];
} elseif ($_perfil === 'medico') {
    $_navLinks = [
        ['id' => 'dashboard',  'url' => $_baseMedico . 'dashboard.php',    'icon' => 'dashboard', 'titulo' => 'Dashboard'],
        ['id' => 'fila_actual', 'url' => $_baseMedico . 'fila_actual.php', 'icon' => 'list_alt', 'titulo' => 'Fila Actual'],
        ['id' => 'mensagens',  'url' => $_baseComum . 'mensagens.php',     'icon' => 'mail', 'titulo' => 'Mensagens'],
    ];
} elseif ($_perfil === 'admin') {
    $_navLinks = [
        ['id' => 'dashboard',     'url' => $_baseAdmin . 'dashboard.php',     'icon' => 'dashboard', 'titulo' => 'Dashboard'],
        ['id' => 'utilizadores',  'url' => $_baseAdmin . 'utilizadores.php',  'icon' => 'group', 'titulo' => 'Utilizadores'],
        ['id' => 'auditoria',     'url' => $_baseAdmin . 'auditoria.php',     'icon' => 'history', 'titulo' => 'Auditoria'],
        ['id' => 'relatorios',    'url' => $_baseAdmin . 'relatorios.php',    'icon' => 'bar_chart', 'titulo' => 'Relatórios'],
        ['id' => 'mensagens',     'url' => $_baseComum . 'mensagens.php',     'icon' => 'mail', 'titulo' => 'Mensagens'],
    ];
}
?>

<aside class="fixed left-6 top-6 bottom-6 flex flex-col py-6 z-[60] w-56 bg-white rounded-[2rem] floating-card font-['Manrope'] antialiased border border-white/50">
    <!-- Top Section: Hospital Logo -->
    <div class="mb-8 flex flex-col items-center px-6 gap-2">
        <div class="text-[18px] font-black tracking-tighter text-black flex flex-col items-center leading-tight">
            <span>HGB</span>
        </div>
        <div class="w-10 h-[1.5px] bg-black/5"></div>
    </div>
    
    <!-- Middle Section: Navigation Icons -->
    <nav class="flex flex-col gap-1.5 px-3 flex-1">
        <?php foreach ($_navLinks as $link): ?>
            <?php if ($_paginaActual === $link['id']): ?>
                <!-- Active -->
                <a href="<?= $link['url'] ?>" class="flex items-center gap-3 px-4 py-3 w-full bg-black text-white rounded-2xl transition-all shadow-md">
                    <span class="material-symbols-outlined text-[20px]"><?= $link['icon'] ?></span>
                    <span class="text-xs font-bold tracking-tight"><?= $link['titulo'] ?></span>
                </a>
            <?php else: ?>
                <!-- Inactive -->
                <a href="<?= $link['url'] ?>" class="flex items-center gap-3 px-4 py-3 w-full text-on-surface-variant hover:bg-surface-container-low hover:text-black rounded-2xl transition-all">
                    <span class="material-symbols-outlined text-[20px]"><?= $link['icon'] ?></span>
                    <span class="text-xs font-semibold tracking-tight"><?= $link['titulo'] ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    
    <!-- Bottom Section: Avatar & Logout -->
    <div class="mt-auto flex flex-col gap-1.5 px-3">
        <div class="px-4 mb-3">
            <div class="h-[1px] bg-black/5 w-full"></div>
        </div>
        <!-- Meu Perfil -->
        <a href="<?= $_basePerfil ?>index.php" class="flex items-center gap-3 px-4 py-2.5 w-full <?php echo $_paginaActual === 'perfil' ? 'bg-black text-white' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-black' ?> rounded-2xl transition-all">
            <div class="w-7 h-7 rounded-full overflow-hidden border border-surface-container-high shrink-0 bg-black text-white flex items-center justify-center font-bold text-[10px]">
                <?php if (!empty($_fotoPathSidebar)): ?>
                    <img src="<?= BASE_URL . 'public/' . $_fotoPathSidebar ?>" class="w-full h-full object-cover" alt="Foto">
                <?php else: ?>
                    <?= $_inicialSidebar ?>
                <?php endif; ?>
            </div>
            <span class="text-xs font-semibold tracking-tight">Meu Perfil</span>
        </a>
        <!-- Sair -->
        <form method="POST" action="<?= BASE_URL ?>app/controllers/auth.php" class="w-full m-0">
            <input type="hidden" name="acao" value="logout">
            <button type="submit" class="flex items-center gap-3 px-4 py-2.5 w-full text-on-surface-variant hover:text-error hover:bg-error/5 rounded-2xl transition-all cursor-pointer">
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span class="text-xs font-semibold tracking-tight text-inherit">Sair</span>
            </button>
        </form>
    </div>
</aside>
