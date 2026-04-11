<?php
// ================================================
// Hospital Geral do Bengo
// Vista: O Meu Histórico (Métricas pessoais)
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
    <title>Meu Histórico —
        <?= APP_NOME ?>
    </title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--azul);
        }

        .stat-label {
            font-size: 13px;
            color: var(--texto-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .historico-tabela {
            background: #fff;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
        }

        .historico-header {
            font-size: 16px;
            font-weight: 600;
            color: var(--texto);
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sparkline-wrapper {
            position: relative;
            height: 120px;
            width: 100%;
        }

        .tag-estado {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .tag-concluido {
            background: var(--verde-claro);
            color: var(--verde);
        }

        .tag-cancelado {
            background: var(--vermelho-claro);
            color: var(--vermelho);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body class="text-on-surface">
<?php $paginaActual = 'historico'; ?>
        <?php include __DIR__ . '/../comum/sidebar.php'; ?>

        <?php
        $tituloPagina = 'Histórico';
        $subtituloPagina = '';
        ?>
        <?php include __DIR__ . '/../comum/header.php'; ?>
<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1500px]">
<div class="flex items-center justify-between mb-6">
                <div>
                    <h2>Meu Histórico</h2>
                    <div class="sub"><?= ($meuPerfil === 'medico' && $dados['especialidade'] ? htmlspecialchars($dados['especialidade']) : ucfirst($meuPerfil)) ?></div>
                </div>
                <a href="editar.php" class="btn btn-sm">← Voltar</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Realizados Hoje</div>
                    <div class="stat-value">
                        <?= $estatisticas['hoje'] ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Esta Semana</div>
                    <div class="stat-value">
                        <?= $estatisticas['semana'] ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total GERAL</div>
                    <div class="stat-value" style="color:#111827">
                        <?= $estatisticas['total'] ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Tempo Médio / Taxa</div>
                    <div class="stat-value" style="color:var(--verde)">
                        <?= $estatisticas['tempo_medio'] ?>
                    </div>
                </div>
            </div>

            <!-- GRÁFICO (Sparkline Últimos 7 dias) -->
            <div class="historico-tabela" style="margin-bottom: 24px;">
                <div class="historico-header">Tendência de Produtividade (Últimos 7 dias)</div>
                <div class="sparkline-wrapper">
                    <canvas id="meuGrafico"></canvas>
                </div>
            </div>

            <div class="historico-tabela">
                <div class="historico-header">Listagem das Últimas 20 Acções</div>

                <div class="tabela-responsiva">
                    <table class="tabela">
                        <?php if ($meuPerfil === 'medico'): ?>
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Código</th>
                                    <th>Atendimento</th>
                                    <th>Estado</th>
                                    <th>Tempo (Dur.)</th>
                                    <th>Data / Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $h): ?>
                                    <tr>
                                        <td><strong>
                                                <?= htmlspecialchars($h['paciente_nome']) ?>
                                            </strong></td>
                                        <td>
                                            <?= htmlspecialchars($h['codigo']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($h['atendimento_tipo']) ?>
                                        </td>
                                        <td>
                                            <?php if ($h['estado'] === 'concluida'): ?>
                                                <span class="tag-estado tag-concluido">Concluída</span>
                                            <?php else: ?>
                                                <span class="tag-estado tag-cancelado">Cancelada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $h['duracao'] !== null ? $h['duracao'] . ' min' : '--' ?>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y H:i', strtotime($h['hora_chamada'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        <?php elseif ($meuPerfil === 'recepcionista'): ?>
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Código (Senha)</th>
                                    <th>Tipo Emitido</th>
                                    <th>Estado Actual</th>
                                    <th>Gerado em</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $h): ?>
                                    <tr>
                                        <td><strong>
                                                <?= htmlspecialchars($h['paciente_nome']) ?>
                                            </strong></td>
                                        <td>
                                            <?= htmlspecialchars($h['codigo']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($h['atendimento_tipo']) ?>
                                        </td>
                                        <td>
                                            <?= ucfirst($h['estado']) ?>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        <?php else: ?>
                            <!-- ADMIN -->
                            <thead>
                                <tr>
                                    <th>Acção Base</th>
                                    <th>Detalhes e Dados Modificados</th>
                                    <th>IP do Gestor</th>
                                    <th>Data e Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $h): ?>
                                    <tr>
                                        <td><strong>
                                                <?= htmlspecialchars($h['accao']) ?>
                                            </strong></td>
                                        <td>
                                            <?= htmlspecialchars($h['detalhes']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($h['ip']) ?>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>

                    <?php if (empty($historico)): ?>
                        <p style="text-align:center; padding:24px; color:gray">Nenhum histórico encontrado recentemente.</p>
                    <?php endif; ?>
                </div>
            </div>

        </main>

    </div>

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
                            label: 'Produtividade / Trabalhos Registados',
                            data: DADOS_CHART.data,
                            backgroundColor: 'rgba(30, 111, 217, 0.8)',
                            borderColor: '#1E6FD9',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { stepSize: 1 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        });
    </script>

</body>

</html>