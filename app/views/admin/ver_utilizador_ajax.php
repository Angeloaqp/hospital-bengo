<?php
// ================================================
// Ajax: Detalhes do Utilizador para o Drawer (Tactile)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

exigirPerfil(['admin']);

$idTarget = (int) ($_GET['id'] ?? 0);
if ($idTarget === 0) {
    echo "<p class='text-red-500 font-bold'>ID inválido.</p>";
    exit;
}

$dados = Utilizador::obter($idTarget);
if (!$dados) {
    echo "<p class='text-red-500 font-bold'>Utilizador não encontrado.</p>";
    exit;
}

$perfilTarget = strtolower($dados['perfil']);
$estatisticas = Utilizador::estatisticasPessoais($idTarget, $perfilTarget);
$inicial = strtoupper(substr($dados['nome'], 0, 1));
?>

<div class="flex flex-col gap-6 fade-in">
    <!-- Header Simples -->
    <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-full overflow-hidden bg-primary text-white flex items-center justify-center font-bold text-2xl shrink-0 ring-2 ring-surface-container-low">
            <?php if (!empty($dados['foto_path'])): ?>
                <img src="<?= BASE_URL . 'public/' . $dados['foto_path'] ?>" class="w-full h-full object-cover" alt="Foto">
            <?php else: ?>
                <?= $inicial ?>
            <?php endif; ?>
        </div>
        <div>
            <h3 class="text-xl font-headline font-black text-on-surface leading-tight"><?= htmlspecialchars($dados['nome']) ?></h3>
            <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mt-1"><?= ucfirst($dados['perfil']) ?></p>
        </div>
    </div>

    <!-- Info Básica -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-surface-container-low p-3 rounded-2xl border border-primary/5">
            <span class="block text-[9px] uppercase font-black tracking-widest text-on-surface-variant mb-1">ID</span>
            <span class="font-bold text-on-surface text-sm">#<?= $dados['id'] ?></span>
        </div>
        <div class="bg-surface-container-low p-3 rounded-2xl border border-primary/5">
            <span class="block text-[9px] uppercase font-black tracking-widest text-on-surface-variant mb-1">Username</span>
            <span class="font-bold text-on-surface text-sm">@<?= htmlspecialchars($dados['nome_utilizador']) ?></span>
        </div>
        <div class="col-span-2 bg-surface-container-low p-3 rounded-2xl border border-primary/5">
            <span class="block text-[9px] uppercase font-black tracking-widest text-on-surface-variant mb-1">Telefone</span>
            <span class="font-bold text-on-surface text-sm"><?= htmlspecialchars($dados['telefone'] ?: 'Não definido') ?></span>
        </div>
    </div>

    <!-- KPIs Rápidos -->
    <h4 class="font-black text-sm uppercase tracking-widest text-on-surface-variant mt-2 border-b border-primary/5 pb-2">Actividade</h4>
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-surface-container shadow-sm flex flex-col items-center justify-center text-center">
            <span class="text-3xl font-black tactile-mono text-on-surface"><?= $estatisticas['hoje'] ?></span>
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mt-1">Hoje</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-surface-container shadow-sm flex flex-col items-center justify-center text-center">
            <span class="text-3xl font-black tactile-mono text-on-surface"><?= $estatisticas['semana'] ?></span>
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mt-1">Semana</span>
        </div>
    </div>

    <div class="mt-4 flex flex-col gap-2">
        <a href="ver_utilizador.php?id=<?= $dados['id'] ?>" class="w-full bg-primary text-white rounded-xl py-3 font-bold text-sm text-center hover:scale-[1.02] transition-transform shadow-md">
            Ver Perfil Completo
        </a>
        <a href="editar_utilizador.php?id=<?= $dados['id'] ?>" class="w-full bg-surface-container-low text-on-surface border border-primary/5 rounded-xl py-3 font-bold text-sm text-center hover:bg-surface-container transition-colors">
            Editar Conta
        </a>
    </div>
</div>
