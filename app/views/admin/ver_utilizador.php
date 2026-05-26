<?php
// ================================================
// Hospital Geral do Bengo
// Vista (Admin): Histórico de um Funcionário Específico (Tactile Editorial)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

// Segurança apenas para admin
exigirPerfil(['admin']);

$meuId = (int) sessao('utilizador_id');
$meuPerfilObject = Utilizador::obter($meuId);

$idTarget = (int) ($_GET['id'] ?? 0);
if ($idTarget === 0) {
    header('Location: ' . BASE_URL . 'app/views/admin/utilizadores.php');
    exit;
}

$dados = Utilizador::obter($idTarget);
if (!$dados) {
    die("Utilizador não encontrado.");
}
$perfilTarget = strtolower($dados['perfil']);

$estatisticas = Utilizador::estatisticasPessoais($idTarget, $perfilTarget);
$historico = Utilizador::ultimasAccoes($idTarget, $perfilTarget);
$grafico = Utilizador::sparkline7Dias($idTarget, $perfilTarget);

$primeiroNome = explode(' ', $dados['nome'] ?? '')[0];
$inicial = strtoupper(substr($dados['nome'], 0, 1));
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?= htmlspecialchars($dados['nome']) ?> — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <script src="<?= BASE_URL ?>public/assets/js/chart.js"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
        .tactile-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-weight: 800; letter-spacing: -0.05em; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        .fade-in-delay-1 { animation: fadeIn 0.5s ease-out 0.1s forwards; opacity: 0; }
        .fade-in-delay-2 { animation: fadeIn 0.5s ease-out 0.2s forwards; opacity: 0; }
        .fade-in-delay-3 { animation: fadeIn 0.5s ease-out 0.3s forwards; opacity: 0; }
        .floating-card { box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05), 0 2px 10px -2px rgba(0,0,0,0.03); }
    </style>
</head>
<body class="text-on-surface bg-[#f3f4f6]">

    <?php $paginaActual = 'utilizadores'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php ob_start(); ?>
        <a href="<?= BASE_URL ?>app/views/admin/utilizadores.php" class="text-xs px-4 py-2 bg-surface-container-low text-on-surface-variant hover:bg-surface-container hover:text-black rounded-full font-bold transition-all border border-black/5 flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar à Lista
        </a>
    <?php $accoesPagina = ob_get_clean(); ?>

    <?php $tituloPagina = 'Perfil: ' . htmlspecialchars($primeiroNome); ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <main class="w-full pb-24 pt-4">

                <!-- Header do Perfil -->
                <header class="mb-8 flex items-center justify-between fade-in bg-white rounded-[2.5rem] p-8 floating-card border border-white">
                    <div class="flex items-center gap-8">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-black text-white flex items-center justify-center font-bold text-5xl ring-4 ring-surface-container-low">
                            <?php if (!empty($dados['foto_path'])): ?>
                                <img src="<?= BASE_URL . 'public/' . $dados['foto_path'] ?>" class="w-full h-full object-cover" alt="Foto">
                            <?php else: ?>
                                <?= $inicial ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-4xl font-headline font-extrabold tracking-tighter mb-2 text-black">
                                <?= htmlspecialchars($dados['nome']) ?>
                            </h2>
                            <div class="flex items-center gap-3 text-on-surface-variant text-sm font-semibold mb-3">
                                <span class="px-3 py-1 bg-surface-container-low rounded-full text-[10px] font-black uppercase tracking-widest text-black border border-black/5">
                                    <?= ucfirst($dados['perfil']) ?>
                                </span>
                                <?php if ($dados['especialidade']): ?>
                                    <span class="w-1 h-1 bg-surface-container-highest rounded-full"></span>
                                    <span><?= htmlspecialchars($dados['especialidade']) ?></span>
                                <?php endif; ?>
                                <span class="w-1 h-1 bg-surface-container-highest rounded-full"></span>
                                <span>ID: #<?= $dados['id'] ?></span>
                            </div>
                            <div class="flex items-center gap-6 text-sm text-on-surface-variant font-medium">
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[18px]">badge</span>
                                    <span><?= htmlspecialchars($dados['nome_utilizador']) ?></span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[18px]">call</span>
                                    <span><?= htmlspecialchars($dados['telefone'] ?: '--') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <a href="<?= BASE_URL ?>app/views/admin/editar_utilizador.php?id=<?= $dados['id'] ?>" class="px-6 py-3 bg-black text-white rounded-full font-bold text-sm hover:scale-105 transition-all text-center flex items-center gap-2 justify-center shadow">
                            <span class="material-symbols-outlined text-[18px]">edit</span> Editar Conta
                        </a>
                    </div>
                </header>

                <!-- KPI Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Hoje -->
                    <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white fade-in-delay-1">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                                <span class="material-symbols-outlined text-black">today</span>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant">Hoje</span>
                        </div>
                        <p class="text-4xl tactile-mono text-black mb-1"><?= $estatisticas['hoje'] ?></p>
                        <p class="text-[11px] font-bold text-on-surface-variant">Acções/Atendimentos</p>
                    </div>

                    <!-- Semana -->
                    <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white fade-in-delay-1">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                                <span class="material-symbols-outlined text-black">date_range</span>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant">Semana</span>
                        </div>
                        <p class="text-4xl tactile-mono text-black mb-1"><?= $estatisticas['semana'] ?></p>
                        <p class="text-[11px] font-bold text-on-surface-variant">Nesta semana</p>
                    </div>

                    <!-- Mês -->
                    <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white fade-in-delay-2">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                                <span class="material-symbols-outlined text-black">calendar_month</span>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant">Mês</span>
                        </div>
                        <p class="text-4xl tactile-mono text-black mb-1"><?= $estatisticas['mes'] ?></p>
                        <p class="text-[11px] font-bold text-on-surface-variant">Neste mês</p>
                    </div>

                    <!-- Ausências / Cancelamentos -->
                    <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white fade-in-delay-2">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-2xl <?= $estatisticas['ausencias'] > 0 ? 'bg-error/10' : 'bg-surface-container-low' ?> flex items-center justify-center">
                                <span class="material-symbols-outlined <?= $estatisticas['ausencias'] > 0 ? 'text-error' : 'text-on-surface-variant' ?>">person_off</span>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant">Perdas</span>
                        </div>
                        <p class="text-4xl tactile-mono <?= $estatisticas['ausencias'] > 0 ? 'text-error' : 'text-black' ?> mb-1"><?= $estatisticas['ausencias'] ?></p>
                        <p class="text-[11px] font-bold text-on-surface-variant">Cancelamentos/Fugas</p>
                    </div>
                </div>

                <!-- Gráfico e Histórico -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
                    
                    <!-- Gráfico 7 Dias -->
                    <section class="lg:col-span-2 bg-white rounded-[2rem] p-8 floating-card border border-white fade-in-delay-3 flex flex-col justify-between">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                                <span class="material-symbols-outlined text-black">show_chart</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-headline font-extrabold tracking-tight">Produtividade (7 Dias)</h3>
                                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Actividade Recente</p>
                            </div>
                        </div>
                        <div class="flex-1 w-full relative min-h-[200px]">
                            <canvas id="graficoInd"></canvas>
                        </div>
                    </section>

                    <!-- Tabela de Histórico -->
                    <section class="lg:col-span-3 bg-white rounded-[2rem] overflow-hidden floating-card border border-white fade-in-delay-3 flex flex-col">
                        <div class="p-8 pb-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                                    <span class="material-symbols-outlined text-black">history</span>
                                </div>
                                <h4 class="text-lg font-headline font-extrabold tracking-tight text-black">Últimas Acções</h4>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant"><?= count($historico) ?> registos visíveis</span>
                        </div>
                        
                        <?php if (!empty($historico)): ?>
                            <div class="overflow-x-auto flex-1">
                                <table class="w-full text-left">
                                    <?php if ($perfilTarget === 'medico'): ?>
                                        <thead>
                                            <tr class="bg-surface-container-low/30 border-b border-surface-container-low">
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Paciente</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Atendimento</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Estado</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em] text-right">Duração</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-surface-container-low/50">
                                            <?php foreach (array_slice($historico, 0, 10) as $h): ?>
                                                <tr class="hover:bg-surface-container-low/20 transition-colors">
                                                    <td class="px-8 py-5">
                                                        <div class="font-bold text-black text-sm"><?= htmlspecialchars($h['paciente_nome']) ?></div>
                                                        <div class="tactile-mono text-xs text-on-surface-variant mt-1"><?= htmlspecialchars($h['codigo']) ?></div>
                                                    </td>
                                                    <td class="px-8 py-5 text-xs text-on-surface-variant font-semibold">
                                                        <?= htmlspecialchars($h['atendimento_tipo']) ?><br>
                                                        <span class="text-[10px] text-on-surface-variant/70"><?= date('d/m/Y H:i', strtotime($h['hora_chamada'])) ?></span>
                                                    </td>
                                                    <td class="px-8 py-5">
                                                        <?php if ($h['estado'] === 'concluida'): ?>
                                                            <span class="px-3 py-1 bg-green-50 text-green-700 rounded-full text-[10px] font-black uppercase tracking-wider">Concluída</span>
                                                        <?php else: ?>
                                                            <span class="px-3 py-1 bg-red-50 text-red-600 rounded-full text-[10px] font-black uppercase tracking-wider">Ausente</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-8 py-5 text-right font-black text-on-surface-variant text-xs">
                                                        <?= $h['duracao'] ? $h['duracao'] . ' min' : '--' ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php elseif ($perfilTarget === 'recepcionista'): ?>
                                        <thead>
                                            <tr class="bg-surface-container-low/30 border-b border-surface-container-low">
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Paciente Registado</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Atendimento</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Estado Actual</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-surface-container-low/50">
                                            <?php foreach (array_slice($historico, 0, 10) as $h): ?>
                                                <tr class="hover:bg-surface-container-low/20 transition-colors">
                                                    <td class="px-8 py-5">
                                                        <div class="font-bold text-black text-sm"><?= htmlspecialchars($h['paciente_nome']) ?></div>
                                                        <div class="tactile-mono text-xs text-on-surface-variant mt-1"><?= htmlspecialchars($h['codigo']) ?></div>
                                                    </td>
                                                    <td class="px-8 py-5 text-xs text-on-surface-variant font-semibold">
                                                        <?= htmlspecialchars($h['atendimento_tipo']) ?><br>
                                                        <span class="text-[10px] text-on-surface-variant/70">Emitido: <?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?></span>
                                                    </td>
                                                    <td class="px-8 py-5">
                                                        <span class="px-3 py-1 bg-surface-container-low text-black rounded-full text-[10px] font-black uppercase tracking-wider"><?= ucfirst($h['estado']) ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php else: ?>
                                        <!-- ADMIN -->
                                        <thead>
                                            <tr class="bg-surface-container-low/30 border-b border-surface-container-low">
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Acção</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Detalhes</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em] text-right">Data/Hora</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-surface-container-low/50">
                                            <?php foreach (array_slice($historico, 0, 10) as $h): ?>
                                                <tr class="hover:bg-surface-container-low/20 transition-colors">
                                                    <td class="px-8 py-5 font-bold text-black text-xs uppercase tracking-wider">
                                                        <?= htmlspecialchars($h['accao']) ?>
                                                    </td>
                                                    <td class="px-8 py-5 text-sm text-on-surface-variant font-medium">
                                                        <?= htmlspecialchars($h['detalhes']) ?>
                                                    </td>
                                                    <td class="px-8 py-5 text-right font-black text-on-surface-variant text-xs">
                                                        <?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php endif; ?>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-12 text-center flex-1 flex flex-col justify-center items-center">
                                <span class="material-symbols-outlined text-5xl text-surface-container-highest mb-4 block">history_toggle_off</span>
                                <p class="text-on-surface-variant font-bold">Sem histórico recente.</p>
                                <p class="text-xs text-on-surface-variant/60 mt-1">Este utilizador não tem acções registadas.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

            </main>
        </div>
    </main>
</div>

    <script>
        const DADOS_CHART = <?= json_encode($grafico) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('graficoInd');
            if (ctx && DADOS_CHART.labels && DADOS_CHART.data) {
                // Configuração baseada em Tactile Editorial
                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: DADOS_CHART.labels,
                        datasets: [{
                            label: 'Acções',
                            data: DADOS_CHART.data,
                            backgroundColor: 'rgba(0, 0, 0, 0.05)',
                            borderColor: '#000000',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4, // Curvas suaves
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#000000',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#000000',
                                titleFont: { family: 'Manrope', size: 12, weight: 'bold' },
                                bodyFont: { family: 'Manrope', size: 13, weight: 'bold' },
                                padding: 12,
                                cornerRadius: 8,
                                displayColors: false
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: true, 
                                ticks: { stepSize: 1, font: { family: 'Manrope', weight: 'bold' } },
                                grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false }
                            },
                            x: { 
                                grid: { display: false },
                                ticks: { font: { family: 'Manrope', weight: 'bold', size: 10 } }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                    }
                });
            }
        });
    </script>
</body>
</html>