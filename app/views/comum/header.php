<?php
// ================================================
// Componente Reutilizável: Header Tailwind SaaS
// ================================================

if (!isset($meuPerfilObject)) {
    require_once __DIR__ . '/../../../app/models/Utilizador.php';
    $meuPerfilObject = Utilizador::obter((int) sessao('utilizador_id'));
}

if (!isset($_naoLidas)) {
    require_once __DIR__ . '/../../../app/models/Mensagem.php';
    $_naoLidas = Mensagem::contarNaoLidas((int) sessao('utilizador_id'));
}

$_nome = htmlspecialchars($meuPerfilObject['nome'] ?? '');
$_primeiroNome = htmlspecialchars(explode(' ', $meuPerfilObject['nome'] ?? '')[0]);
$_perfil = sessao('perfil');
$_fotoPath = $meuPerfilObject['foto_path'] ?? '';
$_inicial = strtoupper(substr($meuPerfilObject['nome'] ?? 'U', 0, 1));
?>

<!-- Header Wrapper with Shield -->
<div class="fixed top-0 right-0 left-56 z-50 h-28 bg-gradient-to-b from-[#f3f4f6] to-transparent font-['Manrope'] antialiased">
    <header class="h-16 mt-6 px-8 max-w-[1500px] mx-auto">
        <div class="rounded-2xl h-full px-6 flex items-center justify-between floating-card border border-white/50 bg-white">
            <div class="flex items-center gap-3">
                <h1 class="text-base font-extrabold tracking-tight text-black"><?= $tituloPagina ?? 'Dashboard' ?></h1>
                
                <div class="h-4 w-[1px] bg-black/10"></div>
                <div class="flex items-center gap-1.5 text-xs text-on-surface-variant font-bold">
                    <span class="material-symbols-outlined text-[16px]">schedule</span>
                    <span id="header-relogio"><?= date('H:i') ?></span>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Acções extras da página (botões injectados pelo layout antigo que precisa de se adaptar ao Tailwind) -->
                <?php if (!empty($accoesPagina)): ?>
                    <div class="flex items-center gap-2">
                        <?= str_replace(['btn-sm', 'btn-primario', 'btn'], ['text-xs px-4 py-2', 'bg-black text-white rounded-full font-bold shadow hover:scale-105', 'rounded-full px-4 py-2 font-bold transition-all text-xs'], $accoesPagina ?? '') ?>
                    </div>
                <?php endif; ?>

                <!-- Notificações -->
                <a href="<?= BASE_URL ?>app/views/comum/mensagens.php" class="relative flex items-center justify-center w-9 h-9 rounded-full bg-surface-container-low hover:bg-surface-container text-black transition-colors" title="Mensagens">
                    <span class="material-symbols-outlined text-[20px]">notifications</span>
                    <?php if ($_naoLidas > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-error text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center border-2 border-white"><?= $_naoLidas > 9 ? '9+' : $_naoLidas ?></span>
                    <?php endif; ?>
                </a>

                <div class="h-6 w-[1px] bg-black/5 mx-1"></div>

                <!-- Utilizador & Avatar -->
                <a href="<?= $_basePerfil ?>index.php" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-black leading-none"><?= $_primeiroNome ?></p>
                        <p class="text-[9px] text-on-surface-variant uppercase tracking-widest font-black mt-0.5">
                            <?php if($_perfil == 'recepcionista') echo 'Recepção Principal'; else echo ucfirst($_perfil); ?>
                        </p>
                    </div>
                    <?php if (!empty($_fotoPath)): ?>
                        <div class="w-9 h-9 rounded-full overflow-hidden bg-surface-container-high ring-2 ring-surface-container">
                            <img src="<?= BASE_URL . 'public/' . $_fotoPath ?>" class="w-full h-full object-cover" alt="Foto">
                        </div>
                    <?php else: ?>
                        <div class="w-9 h-9 rounded-full overflow-hidden bg-black text-white flex items-center justify-center font-bold ring-2 ring-surface-container">
                            <?= $_inicial ?>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>
</div>

<script>
// Relógio ao vivo no header
(function() {
    const el = document.getElementById('header-relogio');
    if (!el) return;
    setInterval(() => {
        const now = new Date();
        el.textContent = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
    }, 30000);
})();
</script>
