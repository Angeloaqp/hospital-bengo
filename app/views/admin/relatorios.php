<?php
// ================================================
// Hospital Geral do Bengo
// Dashboard de Relatórios (Admin)
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
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios —
        <?= APP_NOME ?>
    </title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>

    <!-- Script do Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .relatorios-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .grafico-card {
            background: #fff;
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
        }

        .grafico-header {
            font-size: 14px;
            font-weight: 600;
            color: var(--texto);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--borda);
        }

        .grafico-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .filtro-form {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            margin-bottom: 20px;
            background: #fff;
            padding: 16px;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
        }

        .filtro-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filtro-form label {
            font-size: 11px;
            font-weight: 600;
            color: var(--texto-muted);
            text-transform: uppercase;
        }

        .filtro-form input {
            padding: 8px 12px;
            border: 1px solid var(--borda);
            border-radius: var(--radius-sm);
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .relatorios-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="text-on-surface">
<?php $paginaActual = 'relatorios'; ?>
        <?php include __DIR__ . '/../comum/sidebar.php'; ?>

        <?php
        $tituloPagina = 'Relatórios';
        $subtituloPagina = 'Visualização gráfica de métricas';
        ob_start(); ?>
        <a href="<?= BASE_URL ?>app/controllers/exportar.php?acao=csv_medicos&di=<?= $dataInicio ?>&df=<?= $dataFim ?>"
            class="btn btn-sm">
            📥 Exportar CSV
        </a>
        <?php $accoesPagina = ob_get_clean(); ?>
        <?php include __DIR__ . '/../comum/header.php'; ?>
<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1500px]">
<!-- FILTROS -->
            <form method="GET" class="filtro-form">
                <div class="form-group">
                    <label>Data Início</label>
                    <input type="date" name="di" value="<?= htmlspecialchars($dataInicio) ?>" required>
                </div>
                <div class="form-group">
                    <label>Data Fim</label>
                    <input type="date" name="df" value="<?= htmlspecialchars($dataFim) ?>" required>
                </div>
                <button type="submit" class="btn btn-primario">Aplicar Filtro</button>
            </form>

            <div class="relatorios-grid">

                <!-- GRAFICO 1 -->
                <div class="grafico-card" style="grid-column: 1 / -1;">
                    <div class="grafico-header">Fluxo Diário de Pacientes (Concluídos vs Cancelados)</div>
                    <div class="grafico-container">
                        <canvas id="chartPeriodo"></canvas>
                    </div>
                </div>

                <!-- GRAFICO 2 -->
                <div class="grafico-card">
                    <div class="grafico-header">Produtividade por Médico</div>
                    <div class="grafico-container">
                        <canvas id="chartProdutividade"></canvas>
                    </div>
                </div>

                <!-- GRAFICO 3 -->
                <div class="grafico-card">
                    <div class="grafico-header">Volume de Pacientes por Hora (Pico de Movimento)</div>
                    <div class="grafico-container">
                        <canvas id="chartPico"></canvas>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Variáveis globais para os scripts -->
    <script>
        const DADOS_PERIODO = <?= json_encode($dadosGraficoPeriodo) ?>;
        const DADOS_PRODUTIVIDADE = <?= json_encode($dadosProdutividade) ?>;
        const DADOS_PICO = <?= json_encode($dadosPico) ?>;
    </script>

    <!-- Script dos Gráficos separados (MVC/JS clean approach) -->
    <script src="<?= BASE_URL ?>public/assets/js/graficos.js"></script>

</body>

</html>