<?php
// ================================================
// Hospital Geral do Bengo
// Dashboard do Administrador — Visão Geral
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Estatistica.php';

exigirPerfil(['admin']);

$resumo = Estatistica::resumoHoje();
$porPrio = Estatistica::porPrioridade();
$porTipo = Estatistica::porTipoAtendimento();
$ultimos = Estatistica::ultimosAtendimentos(8);

$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

// Calcula máximo para escala das barras
$maxPrio = max(array_column($porPrio, 'total') ?: [1]);
$maxTipo = !empty($porTipo)
    ? max(array_column($porTipo, 'total'))
    : 1;

$estadoBadge = [
    'espera' => 'badge-normal',
    'chamada' => 'badge-idoso',
    'concluida' => 'badge-concluido',
    'cancelada' => 'badge-cancelado',
];
$estadoLabel = [
    'espera' => 'Em espera',
    'chamada' => 'Em atendimento',
    'concluida' => 'Concluído',
    'cancelada' => 'Ausente',
];
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador — <?= APP_NOME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .dois-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .bar-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .bar-label {
            font-size: 12px;
            color: var(--texto-muted);
            min-width: 80px;
        }

        .bar-track {
            flex: 1;
            height: 10px;
            background: var(--fundo);
            border-radius: 5px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.6s ease;
        }

        .bar-val {
            font-size: 12px;
            color: var(--texto-muted);
            min-width: 24px;
            text-align: right;
        }

        .tabela {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .tabela th {
            text-align: left;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 600;
            color: var(--texto-muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 1px solid var(--borda);
            background: var(--fundo);
        }

        .tabela td {
            padding: 10px 12px;
            border-bottom: 0.5px solid var(--borda);
            color: var(--texto);
            vertical-align: middle;
        }

        .tabela tr:last-child td {
            border-bottom: none;
        }

        .tabela tr:hover td {
            background: var(--fundo);
        }

        .tempo-chip {
            display: inline-flex;
            align-items: center;
            background: var(--fundo);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 11px;
            color: var(--texto-muted);
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .dois-col {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                HGB <span>Admin</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item activo">
                    <span class="dot"></span>
                    Estatísticas
                </a>
                <a href="utilizadores.php" class="nav-item">
                    <span class="dot"></span>
                    Utilizadores
                </a>
                <div class="nav-item-logout">
                    <form method="POST" action="<?= BASE_URL ?>app/controllers/auth.php">
                        <input type="hidden" name="acao" value="logout">
                        <button type="submit" class="nav-item" style="width:100%;background:none;
                               border:none;cursor:pointer">
                            <span class="dot"></span>
                            Sair
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <!-- CONTEÚDO -->
        <main class="conteudo">

            <div class="page-header">
                <div>
                    <h2>Estatísticas de Atendimento</h2>
                    <div class="sub">
                        Hoje — <?= date('d \d\e F \d\e Y') ?>
                    </div>
                </div>
                <span class="chip-tempo">
                    <?= date('H:i') ?>
                </span>
            </div>

            <?php if ($mensagem): ?>
                <div class="alerta alerta-sucesso">
                    ✓ <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alerta alerta-perigo">
                    ⚠ <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <!-- MÉTRICAS PRINCIPAIS -->
            <div class="stats-grid">
                <div class="metrica-card">
                    <div class="valor">
                        <?= $resumo['total'] ?>
                    </div>
                    <div class="rotulo">Total hoje</div>
                </div>
                <div class="metrica-card">
                    <div class="valor" style="color:var(--verde)">
                        <?= $resumo['concluidos'] ?>
                    </div>
                    <div class="rotulo">Concluídos</div>
                </div>
                <div class="metrica-card">
                    <div class="valor" style="color:var(--vermelho)">
                        <?= $resumo['cancelados'] ?>
                    </div>
                    <div class="rotulo">Ausentes</div>
                </div>
                <div class="metrica-card">
                    <div class="valor" style="color:var(--azul)">
                        <?= $resumo['em_espera'] ?>
                    </div>
                    <div class="rotulo">Em espera</div>
                </div>
                <div class="metrica-card">
                    <div class="valor">
                        <?= $resumo['tempo_medio'] > 0
                            ? $resumo['tempo_medio'] . 'm'
                            : '--' ?>
                    </div>
                    <div class="rotulo">Tempo médio</div>
                </div>
            </div>

            <!-- GRÁFICOS -->
            <div class="dois-col">

                <!-- Por Prioridade -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-titulo">
                            Por prioridade
                        </span>
                    </div>
                    <?php foreach ($porPrio as $p):
                        $pct = $maxPrio > 0
                            ? round(($p['total'] / $maxPrio) * 100)
                            : 0;
                        ?>
                        <div class="bar-row">
                            <div class="bar-label">
                                <?= htmlspecialchars($p['label']) ?>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:<?= $pct ?>%;
                                    background:<?= $p['cor'] ?>">
                                </div>
                            </div>
                            <div class="bar-val">
                                <?= $p['total'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Por Tipo de Atendimento -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-titulo">
                            Por tipo de atendimento
                        </span>
                    </div>
                    <?php if (!empty($porTipo)):
                        $cores = [
                            '#1E6FD9',
                            '#DC2626',
                            '#16A34A',
                            '#7C3AED'
                        ];
                        foreach ($porTipo as $i => $t):
                            $pct = $maxTipo > 0
                                ? round(($t['total'] / $maxTipo) * 100)
                                : 0;
                            $cor = $cores[$i % count($cores)];
                            ?>
                            <div class="bar-row">
                                <div class="bar-label">
                                    <?= htmlspecialchars($t['tipo']) ?>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width:<?= $pct ?>%;
                                    background:<?= $cor ?>">
                                    </div>
                                </div>
                                <div class="bar-val">
                                    <?= $t['total'] ?>
                                </div>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <div class="fila-vazia">
                            Sem dados para hoje.
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- TABELA DE ÚLTIMOS ATENDIMENTOS -->
            <div class="card">
                <div class="card-header">
                    <span class="card-titulo">
                        Últimos atendimentos
                    </span>
                    <a href="utilizadores.php" class="btn btn-sm">
                        Gerir utilizadores →
                    </a>
                </div>

                <?php if (!empty($ultimos)): ?>
                    <div style="overflow-x:auto">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th>Senha</th>
                                    <th>Paciente</th>
                                    <th>Tipo</th>
                                    <th>Médico</th>
                                    <th>Entrada</th>
                                    <th>Espera</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimos as $a):
                                    // Calcula tempo de espera
                                    $espera = '—';
                                    if ($a['hora_chamada']) {
                                        $diff = strtotime(
                                            $a['hora_chamada']
                                        ) - strtotime($a['criado_em']);
                                        $espera = round($diff / 60) . 'm';
                                    }
                                    $badge = $estadoBadge[
                                        $a['estado']
                                    ] ?? 'badge-normal';
                                    $label = $estadoLabel[
                                        $a['estado']
                                    ] ?? $a['estado'];
                                    $hora = date('H:i', strtotime(
                                        $a['criado_em']
                                    ));
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $a['codigo']
                                                ) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(
                                                $a['paciente']
                                            ) ?>
                                            <span style="color:var(--texto-muted);
                                      font-size:11px">
                                                (<?= $a['idade'] ?>a)
                                            </span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(
                                                $a['tipo']
                                            ) ?>
                                        </td>
                                        <td>
                                            <?= $a['medico']
                                                ? htmlspecialchars(
                                                    explode(
                                                        ' ',
                                                        $a['medico']
                                                    )[0]
                                                )
                                                : '—' ?>
                                        </td>
                                        <td>
                                            <span class="tempo-chip">
                                                <?= $hora ?>
                                            </span>
                                        </td>
                                        <td><?= $espera ?></td>
                                        <td>
                                            <span class="badge
                                      <?= $badge ?>">
                                                <?= $label ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="fila-vazia">
                        Nenhum atendimento registado hoje.
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

</body>

</html>