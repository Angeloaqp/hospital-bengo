<?php
// ================================================
// Hospital Geral do Bengo
// Vista (Admin): Histórico de um Funcionário Específico
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
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espião de Perfil:
        <?= htmlspecialchars($dados['nome']) ?> —
        <?= APP_NOME ?>
    </title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .topo-perfil {
            background: #fff;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .avatar-largo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--azul);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            object-fit: cover;
        }

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
        }

        .historico-tabela {
            background: #fff;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
        }

        .sparkline-wrapper {
            position: relative;
            height: 120px;
            width: 100%;
        }
    </style>
</head>

<body class="text-on-surface">
<?php $paginaActual = 'utilizadores'; ?>
        <?php include __DIR__ . '/../comum/sidebar.php'; ?>

        <?php $tituloPagina = 'Perfil de ' . htmlspecialchars($dados['nome']); $subtituloPagina = ucfirst($dados['perfil']); ?>
        <?php include __DIR__ . '/../comum/header.php'; ?>
<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1500px]">
<!-- TOPO (IDENTIFICAÇÃO) -->
            <div class="topo-perfil">
                <?php if (!empty($dados['foto_path'])): ?>
                    <img src="<?= BASE_URL . 'public/' . $dados['foto_path'] ?>" class="avatar-largo" alt="Foto">
                <?php else: ?>
                    <div class="avatar-largo">
                        <?= strtoupper(substr($dados['nome'], 0, 1)) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <h3 style="margin: 0 0 8px 0; font-size:24px; color:var(--texto)">
                        <?= htmlspecialchars($dados['nome']) ?>
                    </h3>
                    <div style="color:var(--texto-muted); font-size:14px; font-weight:600">
                        ID #
                        <?= $dados['id'] ?> —
                        <?= strtoupper($dados['perfil']) ?>
                        <?= $dados['especialidade'] ? "({$dados['especialidade']})" : "" ?>
                    </div>
                    <div style="font-size:13px; color:var(--azul); margin-top:8px;">
                        Nome de Utilizador (Acesso):
                        <?= htmlspecialchars($dados['nome_utilizador']) ?> | Tel:
                        <?= htmlspecialchars($dados['telefone'] ?: '--') ?>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Acções Hoje</div>
                    <div class="stat-value">
                        <?= $estatisticas['hoje'] ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Esta Semana</div>
                    <div class="stat-value">
                        <?= $estatisticas['semana'] ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">No Mês</div>
                    <div class="stat-value">
                        <?= $estatisticas['mes'] ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Cancelamentos / Fugas</div>
                    <div class="stat-value" style="color:var(--vermelho)">
                        <?= $estatisticas['ausencias'] ?>
                    </div>
                </div>
            </div>

            <div class="historico-tabela" style="margin-bottom: 24px;">
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Sparkline da Produtividade</div>
                <div class="sparkline-wrapper">
                    <canvas id="graficoInd"></canvas>
                </div>
            </div>

            <div class="historico-tabela">
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">20 Acções Mais Recentes (Raw)</div>
                <div class="tabela-responsiva">
                    <table class="tabela">
                        <?php if ($perfilTarget === 'medico'): ?>
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
                                            <?= htmlspecialchars($h['estado']) ?>
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
                        <?php elseif ($perfilTarget === 'recepcionista'): ?>
                            <thead>
                                <tr>
                                    <th>Paciente Registado</th>
                                    <th>Código Gerado</th>
                                    <th>Tipo Ponto</th>
                                    <th>Estado Actual</th>
                                    <th>Emitido a</th>
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
                                    <th>Acção</th>
                                    <th>Detalhes do Motor</th>
                                    <th>Data/Hora de Operação</th>
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
                                            <?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>
                    <?php if (empty($historico)): ?>
                        <p style="text-align:center; padding:16px;">Nunca realizou nenhuma acção do seu escopo.</p>
                    <?php endif; ?>
                </div>
            </div>

        </main>

    </div>

    <script>
        const DADOS_CHART = <?= json_encode($grafico) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('graficoInd');
            if (ctx && DADOS_CHART.labels) {
                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: DADOS_CHART.labels,
                        datasets: [{
                            label: 'Acções',
                            data: DADOS_CHART.data,
                            backgroundColor: 'rgba(217, 119, 6, 0.2)',
                            borderColor: '#D97706',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        });
    </script>

</body>

</html>