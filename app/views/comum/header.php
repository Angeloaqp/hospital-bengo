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
<div id="header-wrapper" class="fixed top-0 left-[17rem] right-6 z-50 h-28 bg-transparent font-['Manrope'] antialiased transition-all duration-300">
    <!-- Mask: esconde conteúdo ao rolar, alargado para cobrir o fosso lateral -->
    <div class="absolute inset-y-0 -left-6 -right-6" style="z-index:-1; background: linear-gradient(to bottom, rgba(243,244,246,1) 60%, rgba(243,244,246,0) 100%);"></div>
    <header class="h-16 mt-6 w-full ">
        <div class="rounded-2xl h-full px-6 flex items-center justify-between shadow-[0_10px_40px_-10px_rgba(0,0,0,0.04)] border border-white/50 bg-white">
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
                        <?= str_replace(['btn-sm', 'btn-primario', 'btn'], ['text-xs px-4 py-2', 'bg-black text-white rounded-xl font-bold shadow hover:scale-105', 'rounded-xl px-4 py-2 font-bold transition-all text-xs'], $accoesPagina ?? '') ?>
                    </div>
                <?php endif; ?>

                <!-- Notificações -->
                <div class="relative group" id="notif-dropdown-wrapper">
                    <button type="button" class="relative flex items-center justify-center w-9 h-9 rounded-full bg-surface-container-low hover:bg-surface-container text-black transition-colors" title="Notificações" onclick="document.getElementById('notif-menu').classList.toggle('hidden')">
                        <span class="material-symbols-outlined text-[20px]">notifications</span>
                        <?php if ($_naoLidas > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-error text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center border-2 border-white"><?= $_naoLidas > 9 ? '9+' : $_naoLidas ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Dropdown Card (Hidden by default) -->
                    <div id="notif-menu" class="hidden absolute right-0 mt-3 w-80 bg-white rounded-[1.5rem] shadow-2xl border border-black/5 overflow-hidden z-50 flex flex-col transform origin-top-right transition-all">
                        <div class="px-5 py-4 flex items-center justify-between border-b border-black/5 bg-surface-container-low/30">
                            <h3 class="text-sm font-black text-black tracking-tight">Notificações</h3>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-widest"><?= $_naoLidas ?> Novas</span>
                        </div>
                        <div class="flex flex-col max-h-[320px] overflow-y-auto custom-scrollbar">
                            <?php if ($_naoLidas > 0): ?>
                                <!-- Exemplo Mockup de Notificação Recente -->
                                <div class="px-5 py-4 hover:bg-surface-container-low transition-colors cursor-pointer border-b border-black/5 flex gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[16px]">info</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest mb-0.5">Sistema</p>
                                        <p class="text-xs font-bold text-black leading-tight">Existem mensagens não lidas no seu painel.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="p-8 text-center flex flex-col items-center">
                                    <span class="material-symbols-outlined text-4xl text-surface-container-highest mb-2">notifications_paused</span>
                                    <p class="text-xs font-bold text-on-surface-variant">Tudo tranquilo.</p>
                                    <p class="text-[10px] text-on-surface-variant/70 mt-1">Não tem notificações pendentes.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="<?= BASE_URL ?>app/views/comum/mensagens.php" class="block w-full px-5 py-3 text-center text-xs font-bold text-black hover:bg-surface-container-low transition-colors border-t border-black/5">
                            Ver Todas as Mensagens
                        </a>
                    </div>
                </div>

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

// Fechar notificação ao clicar fora
document.addEventListener('click', function(event) {
    const wrapper = document.getElementById('notif-dropdown-wrapper');
    const menu = document.getElementById('notif-menu');
    if (wrapper && menu && !wrapper.contains(event.target)) {
        menu.classList.add('hidden');
    }
});
</script>
