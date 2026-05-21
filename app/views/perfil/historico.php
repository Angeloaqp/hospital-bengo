<?php
// ================================================
// Hospital Geral do Bengo
// Vista: O Meu Histórico (Métricas pessoais) - Tactile Editorial
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

// Segurança
exigirPerfil(['admin', 'medico', 'recepcionista']);

$meuId = (int) sessao('utilizador_id');
$meuPerfil = sessao('perfil');

// Vai buscar o URL base de regresso (dashboard) consoante o cargo logado
$urlVoltar = BASE_URL . "app/views/{$meuPerfil}/dashboard.php";

$dados = Utilizador::obter($meuId);
$estatisticas = Utilizador::estatisticasPessoais($meuId, $meuPerfil);
$historico = Utilizador::ultimasAccoes($meuId, $meuPerfil);
$grafico = Utilizador::sparkline7Dias($meuId, $meuPerfil);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes de Desempenho — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>

    <!-- Carregar Chart.js para os gráficos de barras -->
    <script src="<?= BASE_URL ?>public/assets/js/chart.js"></script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }

        @keyframes bentoIn {
            0% { opacity: 0; transform: translateY(30px) scale(0.98); filter: blur(5px); }
            100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }
        .bento-card { animation: bentoIn 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        
        .delay-1 { animation-delay: 0.05s; }
        .delay-2 { animation-delay: 0.1s; }
        .delay-3 { animation-delay: 0.15s; }
        .delay-4 { animation-delay: 0.20s; }
        .delay-5 { animation-delay: 0.25s; }

        .btn-black { transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .btn-black:hover { transform: translateY(-3px); box-shadow: 0 14px 20px -8px rgba(0,0,0,0.3); }
        .btn-black:active { transform: scale(0.98); box-shadow: none; }

        .form-row { transition: all 0.2s ease; border-bottom: 1px solid rgba(0,0,0,0.03); }
        .form-row:hover { background-color: #f8fafc; }
    </style>
</head>

<body class="text-on-surface h-screen overflow-hidden bg-[#f3f4f6]">
    <?php $paginaActual = 'perfil'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php $tituloPagina = 'Métricas e Histórico'; ob_start(); ?>
    <a href="index.php" class="px-5 py-2.5 bg-white border border-gray-200 text-black hover:bg-black hover:text-white hover:border-black rounded-full flex items-center gap-2 transition-all shadow-sm">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span class="text-xs font-bold">Resumo do Perfil</span>
    </a>
    <?php $accoesPagina = ob_get_clean(); ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <main class="ml-64 pt-24 h-screen overflow-y-auto custom-scrollbar relative">
        <div class="p-8 pb-32 max-w-[1400px] mx-auto min-h-full">

            <div class="mb-10 bento-card flex items-end justify-between">
                <div>
                    <h2 class="text-3xl font-headline font-black text-black tracking-tight leading-none">Análise de Produtividade</h2>
                    <p class="text-sm font-bold text-gray-400 mt-2">Visão geral do seu histórico de volume e tempos no sistema.</p>
                </div>
                <div class="px-4 py-2 bg-gray-100/50 rounded-xl">
                    <span class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest">Cargo Operacional: </span>
                    <span class="text-sm font-bold text-black ml-1"><?= ($meuPerfil === 'medico' && $dados['especialidade'] ? htmlspecialchars($dados['especialidade']) : ucfirst($meuPerfil)) ?></span>
                </div>
            </div>

            <!-- BLOCO 1: ESTATÍSTICAS NÚMERICAS (4 colunas) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <div class="bento-card delay-1 bg-white rounded-[2rem] p-6 border border-black/5 shadow-sm">
                    <div class="flex items-center justify-between mb-8 text-gray-400">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest">Realizados Hoje</span>
                        <span class="material-symbols-outlined text-[18px]">today</span>
                    </div>
                    <div class="text-5xl font-headline font-black text-black">
                        <?= $estatisticas['hoje'] ?>
                    </div>
                </div>

                <div class="bento-card delay-2 bg-white rounded-[2rem] p-6 border border-black/5 shadow-sm">
                    <div class="flex items-center justify-between mb-8 text-gray-400">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest">Esta Semana</span>
                        <span class="material-symbols-outlined text-[18px]">date_range</span>
                    </div>
                    <div class="text-5xl font-headline font-black text-black">
                        <?= $estatisticas['semana'] ?>
                    </div>
                </div>

                <div class="bento-card delay-3 bg-white rounded-[2rem] p-6 border border-black/5 shadow-sm">
                    <div class="flex items-center justify-between mb-8 text-gray-400">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest">Total Histórico</span>
                        <span class="material-symbols-outlined text-[18px]">functions</span>
                    </div>
                    <div class="text-5xl font-headline font-black text-black">
                        <?= $estatisticas['total'] ?>
                    </div>
                </div>

                <div class="bento-card delay-3 bg-white rounded-[2rem] p-6 border border-black/5 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-6 -bottom-6 text-green-500 opacity-10 transition-transform duration-500 group-hover:scale-110">
                        <span class="material-symbols-outlined" style="font-size: 100px;">speed</span>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-8 text-gray-400">
                            <span class="text-[10px] font-extrabold uppercase tracking-widest">Taxa / Tempo Médio</span>
                            <span class="material-symbols-outlined text-[18px]">timer</span>
                        </div>
                        <div class="text-5xl font-headline font-black text-green-600 tracking-tighter">
                            <?= $estatisticas['tempo_medio'] ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOCO 2: GRÁFICO TENDÊNCIA BENTO -->
            <div class="bento-card delay-4 bg-white rounded-[2.5rem] p-8 border border-black/5 shadow-sm mb-8">
                <h3 class="text-sm font-extrabold text-black uppercase tracking-widest mb-6">Tendência Produtiva (Últimos 7 dias)</h3>
                <div class="w-full h-48">
                    <canvas id="meuGrafico"></canvas>
                </div>
            </div>

            <!-- BLOCO 3: DATATABLE HISTÓRICO -->
            <div class="bento-card delay-5 bg-white rounded-[2.5rem] border border-black/5 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-black uppercase tracking-widest">Registo em Detalhe (Últimos 20)</h3>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <?php if ($meuPerfil === 'medico'): ?>
                            <thead>
                                <tr>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Paciente Identificado</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Código</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Tipo Consulta</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Estado</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50 text-right">Duração</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50 text-right">Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $h): ?>
                                    <tr class="form-row">
                                        <td class="py-4 px-8 font-bold text-sm text-black"><?= htmlspecialchars($h['paciente_nome']) ?></td>
                                        <td class="py-4 px-8"><span class="px-2 py-1 bg-gray-100 rounded text-xs font-mono font-bold text-gray-500"><?= htmlspecialchars($h['codigo']) ?></span></td>
                                        <td class="py-4 px-8 text-sm font-bold text-gray-500"><?= htmlspecialchars($h['atendimento_tipo']) ?></td>
                                        <td class="py-4 px-8">
                                            <?php if ($h['estado'] === 'concluida'): ?>
                                                <span class="px-3 py-1 bg-green-50 text-green-600 rounded-full text-[10px] font-extrabold uppercase">Concluído</span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 bg-red-50 text-red-600 rounded-full text-[10px] font-extrabold uppercase">Cancelada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-8 text-right text-sm font-bold text-gray-500">
                                            <?= $h['duracao'] !== null ? $h['duracao'] . ' <span class="text-xs text-gray-300">m</span>' : '<span class="text-gray-300">—</span>' ?>
                                        </td>
                                        <td class="py-4 px-8 text-right">
                                            <div class="text-sm font-bold text-gray-800"><?= dataFormatoPT($h['hora_chamada'], 'dia_mes') ?></div>
                                            <div class="text-[11px] font-extrabold text-gray-400"><?= date('H:i', strtotime($h['hora_chamada'])) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        <?php elseif ($meuPerfil === 'recepcionista'): ?>
                            <thead>
                                <tr>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Paciente Inserido</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Senha / Cod.</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Especialidade Distribuída</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Progresso do Paciente</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50 text-right">Geração / Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $h): ?>
                                    <tr class="form-row">
                                        <td class="py-4 px-8 font-bold text-sm text-black flex items-center gap-3">
                                            <span class="material-symbols-outlined text-[16px] text-gray-300">person</span>
                                            <?= htmlspecialchars($h['paciente_nome']) ?>
                                        </td>
                                        <td class="py-4 px-8"><span class="px-2 py-1 bg-gray-100 rounded text-xs font-mono font-bold text-gray-500"><?= htmlspecialchars($h['codigo']) ?></span></td>
                                        <td class="py-4 px-8 text-sm font-bold text-blue-600"><?= htmlspecialchars($h['atendimento_tipo']) ?></td>
                                        <td class="py-4 px-8">
                                            <span class="text-xs font-extrabold uppercase tracking-widest text-gray-400"><?= htmlspecialchars($h['estado']) ?></span>
                                        </td>
                                        <td class="py-4 px-8 text-right">
                                            <div class="text-sm font-bold text-gray-800"><?= dataFormatoPT($h['criado_em'], 'dia_mes') ?></div>
                                            <div class="text-[11px] font-extrabold text-gray-400"><?= date('H:i', strtotime($h['criado_em'])) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        <?php else: ?>
                            <!-- ADMIN -->
                            <thead>
                                <tr>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Ação de Gestão</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Metadados Modificados</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Sessão IP</th>
                                    <th class="py-4 px-8 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50 text-right">Data da Modificação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $h): ?>
                                    <tr class="form-row">
                                        <td class="py-4 px-8">
                                            <span class="px-3 py-1.5 bg-black text-white rounded-md text-[10px] font-extrabold uppercase tracking-widest">
                                                <?= htmlspecialchars($h['accao']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-8 text-sm font-medium text-gray-500 max-w-[250px] truncate" title="<?= htmlspecialchars($h['detalhes']) ?>">
                                            <?= htmlspecialchars($h['detalhes']) ?>
                                        </td>
                                        <td class="py-4 px-8 text-xs font-mono font-bold text-gray-400 bg-gray-50 rounded">
                                            <?= htmlspecialchars($h['ip']) ?>
                                        </td>
                                        <td class="py-4 px-8 text-right">
                                            <div class="text-sm font-bold text-black"><?= dataFormatoPT($h['criado_em'], 'curto') ?></div>
                                            <div class="text-[11px] font-extrabold text-gray-400"><?= date('H:i:s', strtotime($h['criado_em'])) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>

                    <?php if (empty($historico)): ?>
                        <div class="p-16 text-center">
                            <span class="material-symbols-outlined text-[32px] text-gray-300 mb-2">inbox</span>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Nenhuma informação arquivada recente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <script>
        const DADOS_CHART = <?= json_encode($grafico) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('meuGrafico');
            if (ctx && DADOS_CHART.labels) {
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: DADOS_CHART.labels,
                        datasets: [{
                            label: 'Volume Diário',
                            data: DADOS_CHART.data,
                            backgroundColor: '#111827', // Preto profundo tátil
                            borderRadius: 6,
                            borderSkipped: false,
                            hoverBackgroundColor: '#374151'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#000', padding: 12, titleFont: { size: 14, family: 'Manrope' } } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#f3f4f6', drawBorder: false }, border: { display: false }, ticks: { stepSize: 1, font: { weight: 'bold', color: '#9ca3af' } } },
                            x: { grid: { display: false }, border: { display: false }, ticks: { font: { weight: 'bold', color: '#6b7280' } } }
                        },
                        animation: {
                            y: { duration: 1500, easing: 'easeOutQuart' }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>