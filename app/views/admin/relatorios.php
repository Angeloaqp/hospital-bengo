<?php
// ================================================
// Hospital Geral do Bengo
// Dashboard de Relatórios (Admin) — Tactile Editorial
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Estatistica.php';

exigirPerfil(['admin']);

// Por defeito, os últimos 7 dias
$dataFim = $_GET['df'] ?? date('Y-m-d');
$dataInicio = $_GET['di'] ?? date('Y-m-d', strtotime('-7 days'));

$resumoPeriodo = Estatistica::resumoPorPeriodo($dataInicio, $dataFim);
$produtividadeMedico = Estatistica::porMedico($dataInicio, $dataFim);
$horasPico = Estatistica::horasPico($dataInicio, $dataFim);

// Dados Demográficos (Etapa 2)
$demografiaIdade = Estatistica::demografiaIdade($dataInicio, $dataFim);
$demografiaPrioridade = Estatistica::demografiaPrioridade($dataInicio, $dataFim);
$topEspecialidades = Estatistica::topEspecialidades($dataInicio, $dataFim);

// Estruturação JSON para os gráficos (JS)
$dadosGraficoPeriodo = [
    'labels' => array_column($resumoPeriodo, 'data_dia'),
    'total' => array_column($resumoPeriodo, 'total'),
    'concluidos' => array_column($resumoPeriodo, 'concluidos'),
    'cancelados' => array_column($resumoPeriodo, 'cancelados')
];

$dadosProdutividade = [
    'labels' => array_column($produtividadeMedico, 'medico'),
    'total' => array_column($produtividadeMedico, 'total_atendidos'),
    'tempo_medio' => array_column($produtividadeMedico, 'tempo_medio_espera')
];

$dadosPico = [
    'labels' => array_map(function ($h) {
        return $h . ':00'; }, array_column($horasPico, 'hora')),
    'volume' => array_column($horasPico, 'volume')
];

// Calcular totais resumo para as stat-cards
$totalPeriodo = array_sum($dadosGraficoPeriodo['total']);
$totalConcluidos = array_sum($dadosGraficoPeriodo['concluidos']);
$totalCancelados = array_sum($dadosGraficoPeriodo['cancelados']);
$taxaSucesso = $totalPeriodo > 0 ? round(($totalConcluidos / $totalPeriodo) * 100) : 0;

// Formatação do período para exibição
$periodoLabel = dataFormatoPT($dataInicio, 'dia_mes') . ' — ' . dataFormatoPT($dataFim, 'curto');
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>

    <script src="<?= BASE_URL ?>public/assets/js/chart.js"></script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }

        @keyframes bentoIn {
            0% { opacity: 0; transform: translateY(24px) scale(0.98); filter: blur(4px); }
            100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }
        .bento-card { animation: bentoIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .delay-1 { animation-delay: 0.05s; }
        .delay-2 { animation-delay: 0.10s; }
        .delay-3 { animation-delay: 0.15s; }
        .delay-4 { animation-delay: 0.20s; }
        .delay-5 { animation-delay: 0.25s; }
        .delay-6 { animation-delay: 0.30s; }

        /* Floating Label inputs para os Date Pickers */
        .field-wrap { position: relative; }
        .field-wrap input[type="date"] {
            width: 100%; padding: 14px 16px 6px 16px; border: 2px solid #e5e7eb; border-radius: 1rem;
            font-size: 14px; font-weight: 600; color: #111; background: #fff;
            transition: border-color 0.3s, box-shadow 0.3s; outline: none; font-family: inherit;
        }
        .field-wrap input[type="date"]:focus { border-color: #000; box-shadow: 0 0 0 4px rgba(0,0,0,0.06); }
        .field-wrap label {
            position: absolute; top: 6px; left: 16px;
            font-size: 10px; font-weight: 800; color: #9ca3af;
            text-transform: uppercase; letter-spacing: 0.1em; pointer-events: none;
        }

        /* Médico rank bar */
        .rank-bar { transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>

<body class="text-on-surface bg-background">
    <?php $paginaActual = 'relatorios'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php
    $tituloPagina = 'Relatórios';
    ob_start(); ?>
    <a href="<?= BASE_URL ?>app/controllers/exportar.php?acao=csv_medicos&di=<?= $dataInicio ?>&df=<?= $dataFim ?>"
       class="px-5 py-2.5 bg-primary text-white rounded-full flex items-center gap-2 transition-all shadow-sm hover:shadow-lg hover:shadow-black/20 hover:-translate-y-0.5">
        <span class="material-symbols-outlined text-[18px]">download</span>
        <span class="text-xs font-bold">Exportar CSV</span>
    </a>
    <?php $accoesPagina = ob_get_clean(); ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
        <div class="pb-32">

            <!-- TÍTULO + FILTRO DE DATAS -->
            <div class="bento-card flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-10">
                <div>
                    <h2 class="text-[2rem] font-headline font-black text-on-surface tracking-tight leading-none">Análise Organizacional</h2>
                    <p class="text-sm font-bold text-gray-400 mt-2">Resumos, tendências e produtividade do hospital no período seleccionado.</p>
                </div>
                
                <!-- Filtro Período -->
                <form method="GET" class="flex items-end gap-4 shrink-0">
                    <div class="field-wrap">
                        <label>Data Início</label>
                        <input type="date" name="di" value="<?= htmlspecialchars($dataInicio) ?>" required>
                    </div>
                    <div class="field-wrap">
                        <label>Data Fim</label>
                        <input type="date" name="df" value="<?= htmlspecialchars($dataFim) ?>" required>
                    </div>
                    <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl font-bold text-sm flex items-center gap-2 hover:bg-gray-800 transition-colors shrink-0">
                        <span class="material-symbols-outlined text-[18px]">filter_alt</span>
                        Aplicar
                    </button>
                </form>
            </div>

            <!-- STAT CARDS (4-cols) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bento-card delay-1 bg-white rounded-[2rem] p-6 border border-black/5 shadow-sm">
                    <div class="flex items-center justify-between mb-6 text-gray-400">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest">Total Período</span>
                        <span class="material-symbols-outlined text-[18px]">bar_chart</span>
                    </div>
                    <div class="text-5xl font-headline font-black text-on-surface tracking-tighter"><?= $totalPeriodo ?></div>
                    <div class="text-xs font-bold text-gray-400 mt-2"><?= $periodoLabel ?></div>
                </div>

                <div class="bento-card delay-2 bg-white rounded-[2rem] p-6 border border-black/5 shadow-sm">
                    <div class="flex items-center justify-between mb-6 text-gray-400">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest">Concluídos</span>
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    </div>
                    <div class="text-5xl font-headline font-black text-green-600 tracking-tighter"><?= $totalConcluidos ?></div>
                    <div class="text-xs font-bold text-green-400 mt-2">Atendimentos completos</div>
                </div>

                <div class="bento-card delay-3 bg-white rounded-[2rem] p-6 border border-black/5 shadow-sm">
                    <div class="flex items-center justify-between mb-6 text-gray-400">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest">Cancelados</span>
                        <span class="material-symbols-outlined text-[18px]">cancel</span>
                    </div>
                    <div class="text-5xl font-headline font-black text-red-500 tracking-tighter"><?= $totalCancelados ?></div>
                    <div class="text-xs font-bold text-red-300 mt-2">Ausências / Desistências</div>
                </div>

                <div class="bento-card delay-4 bg-white rounded-[2rem] p-6 border border-black/5 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-green-500 opacity-5 transition-transform duration-500 group-hover:scale-110">
                        <span class="material-symbols-outlined" style="font-size: 88px;">trending_up</span>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6 text-gray-400">
                            <span class="text-[10px] font-extrabold uppercase tracking-widest">Taxa de Sucesso</span>
                            <span class="material-symbols-outlined text-[18px]">percent</span>
                        </div>
                        <div class="text-5xl font-headline font-black <?= $taxaSucesso >= 70 ? 'text-green-600' : ($taxaSucesso >= 40 ? 'text-amber-500' : 'text-red-500') ?> tracking-tighter">
                            <?= $taxaSucesso ?>%
                        </div>
                        <div class="text-xs font-bold text-gray-400 mt-2">Conclusão efetiva</div>
                    </div>
                </div>
            </div>

            <!-- GRÁFICO PRINCIPAL: Fluxo Diário (Full-width) -->
            <div class="bento-card delay-5 bg-white rounded-[2.5rem] p-8 border border-black/5 shadow-sm mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-widest flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">show_chart</span>
                        Fluxo Diário de Pacientes
                    </h3>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= $periodoLabel ?></span>
                </div>
                <div class="w-full h-72">
                    <canvas id="chartPeriodo"></canvas>
                </div>
            </div>

            <!-- GRÁFICOS SECUNDÁRIOS (2-cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Produtividade Médico (Donut) -->
                <div class="bento-card delay-5 bg-white rounded-[2.5rem] p-8 border border-black/5 shadow-sm">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-widest flex items-center gap-2 mb-6">
                        <span class="material-symbols-outlined text-[18px]">donut_large</span>
                        Distribuição por Médico
                    </h3>
                    <div class="w-full h-72">
                        <canvas id="chartProdutividade"></canvas>
                    </div>
                </div>

                <!-- Pico de Horas (Bar Chart) -->
                <div class="bento-card delay-6 bg-white rounded-[2.5rem] p-8 border border-black/5 shadow-sm">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-widest flex items-center gap-2 mb-6">
                        <span class="material-symbols-outlined text-[18px]">schedule</span>
                        Horas de Pico
                    </h3>
                    <div class="w-full h-72">
                        <canvas id="chartPico"></canvas>
                    </div>
                </div>
            </div>

            <!-- ANÁLISE DEMOGRÁFICA (Bento Grid) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">

                <!-- Card: Distribuição Etária (Doughnut) -->
                <div class="bento-card delay-5 bg-white rounded-[2.5rem] p-8 border border-black/5 shadow-sm lg:col-span-1">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-widest flex items-center gap-2 mb-6">
                        <span class="material-symbols-outlined text-[18px]">demography</span>
                        Faixas Etárias
                    </h3>
                    <div class="w-full h-56 flex items-center justify-center">
                        <canvas id="chartIdade"></canvas>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <?php
                        $faixas = [
                            ['label' => '<5 anos', 'key' => 'criancas', 'cor' => '#F59E0B'],
                            ['label' => '5-17', 'key' => 'jovens', 'cor' => '#8B5CF6'],
                            ['label' => '18-35', 'key' => 'adultos_jovens', 'cor' => '#3B82F6'],
                            ['label' => '36-59', 'key' => 'adultos', 'cor' => '#10B981'],
                            ['label' => '60+', 'key' => 'idosos', 'cor' => '#EF4444'],
                        ];
                        foreach ($faixas as $f):
                        ?>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?= $f['cor'] ?>"></span>
                                <span class="text-[11px] font-bold text-gray-500"><?= $f['label'] ?>: <strong class="text-on-surface"><?= (int)($demografiaIdade[$f['key']] ?? 0) ?></strong></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Card: Prioridades -->
                <div class="bento-card delay-6 bg-white rounded-[2.5rem] p-8 border border-black/5 shadow-sm">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-widest flex items-center gap-2 mb-6">
                        <span class="material-symbols-outlined text-[18px]">priority_high</span>
                        Distribuição por Prioridade
                    </h3>
                    <div class="flex flex-col gap-4">
                        <?php foreach ($demografiaPrioridade as $pr): ?>
                            <div class="flex items-center gap-4 p-4 rounded-2xl bg-gray-50/80 border border-gray-100 hover:shadow-sm transition-shadow">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:<?= $pr['cor'] ?>15;">
                                    <span class="material-symbols-outlined text-[20px]" style="color:<?= $pr['cor'] ?>;font-variation-settings:'FILL' 1;"><?= $pr['icon'] ?></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-extrabold text-gray-700"><?= $pr['label'] ?></span>
                                        <span class="text-lg font-black" style="color:<?= $pr['cor'] ?>"><?= $pr['total'] ?></span>
                                    </div>
                                    <?php $pctPr = ($totalPeriodo > 0) ? round(($pr['total'] / $totalPeriodo) * 100) : 0; ?>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-700" style="width:<?= $pctPr ?>%;background:<?= $pr['cor'] ?>"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Card: Top Especialidades -->
                <div class="bento-card delay-6 bg-white rounded-[2.5rem] p-8 border border-black/5 shadow-sm">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-widest flex items-center gap-2 mb-6">
                        <span class="material-symbols-outlined text-[18px]">clinical_notes</span>
                        Top Especialidades
                    </h3>
                    <?php if (!empty($topEspecialidades)):
                        $maxEsp = (int)($topEspecialidades[0]['total'] ?? 1);
                    ?>
                        <div class="flex flex-col gap-4">
                            <?php foreach ($topEspecialidades as $ei => $esp):
                                $pctEsp = round(((int)$esp['total'] / $maxEsp) * 100);
                            ?>
                                <div class="flex items-center gap-4">
                                    <div class="w-8 h-8 rounded-lg <?= $ei === 0 ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500' ?> flex items-center justify-center font-black text-xs shrink-0">
                                        <?= $ei + 1 ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-bold text-gray-700 truncate"><?= htmlspecialchars($esp['especialidade']) ?></span>
                                            <span class="text-sm font-black text-on-surface shrink-0 ml-2"><?= $esp['total'] ?></span>
                                        </div>
                                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="rank-bar h-full bg-primary rounded-full" style="width:<?= $pctEsp ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <span class="material-symbols-outlined text-[32px] text-gray-300 mb-2">info</span>
                            <p class="text-xs font-bold text-gray-400">Sem dados no período.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TABELA RANKING DE MÉDICOS -->
            <div class="bento-card delay-6 bg-white rounded-[2.5rem] border border-black/5 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-widest flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">leaderboard</span>
                        Ranking de Produtividade Médica
                    </h3>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= count($produtividadeMedico) ?> profissionais</span>
                </div>

                <?php if (!empty($produtividadeMedico)):
                    $maxAtendidos = max(array_column($produtividadeMedico, 'total_atendidos')) ?: 1;
                ?>
                    <div class="p-8 flex flex-col gap-6">
                        <?php foreach ($produtividadeMedico as $idx => $med):
                            $pct = round(($med['total_atendidos'] / $maxAtendidos) * 100);
                        ?>
                            <div class="flex items-center gap-6">
                                <!-- Posição -->
                                <div class="w-10 h-10 rounded-xl <?= $idx === 0 ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500' ?> flex items-center justify-center font-black text-sm shrink-0">
                                    <?= $idx + 1 ?>º
                                </div>
                                <!-- Info + Barra -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-bold text-on-surface truncate"><?= htmlspecialchars($med['medico']) ?></span>
                                        <div class="flex items-center gap-4 shrink-0 ml-4">
                                            <span class="text-xs font-bold text-gray-400">
                                                ~<?= htmlspecialchars($med['tempo_medio_espera']) ?> min
                                            </span>
                                            <span class="text-lg font-black text-on-surface"><?= $med['total_atendidos'] ?></span>
                                        </div>
                                    </div>
                                    <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="rank-bar h-full bg-primary rounded-full" style="width: <?= $pct ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-16 text-center">
                        <span class="material-symbols-outlined text-[32px] text-gray-300 mb-2">person_off</span>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Sem dados de produtividade no período.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

    <!-- Variáveis globais para os scripts -->
    <script>
        const DADOS_PERIODO = <?= json_encode($dadosGraficoPeriodo) ?>;
        const DADOS_PRODUTIVIDADE = <?= json_encode($dadosProdutividade) ?>;
        const DADOS_PICO = <?= json_encode($dadosPico) ?>;
        const DADOS_IDADE = <?= json_encode([
            'labels' => ['<5 anos', '5-17', '18-35', '36-59', '60+'],
            'data' => [
                (int)($demografiaIdade['criancas'] ?? 0),
                (int)($demografiaIdade['jovens'] ?? 0),
                (int)($demografiaIdade['adultos_jovens'] ?? 0),
                (int)($demografiaIdade['adultos'] ?? 0),
                (int)($demografiaIdade['idosos'] ?? 0),
            ],
            'cores' => ['#F59E0B', '#8B5CF6', '#3B82F6', '#10B981', '#EF4444']
        ]) ?>;
    </script>

    <!-- Script dos Gráficos separados (MVC/JS clean approach) -->
    <script src="<?= BASE_URL ?>public/assets/js/graficos.js"></script>

</body>
</html>