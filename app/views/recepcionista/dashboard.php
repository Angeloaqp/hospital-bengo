<?php
// ================================================
// Hospital Geral do Bengo
// Dashboard da Recepcionista — Fila em tempo real
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Senha.php';

// Só recepcionistas e admins podem aceder
exigirPerfil(['recepcionista', 'admin']);

// Carrega dados para as métricas
$emEspera = Senha::contarPorEstado('espera');
$urgentes = Senha::contarUrgentes();
$atendidos = Senha::atendidosHoje();
$tempoMedio = Senha::tempoMedioEspera();
$filaEspera = Senha::filaEspera();

// Mapa de prioridades
$prioridades = [
    1 => [
        'label' => 'Urgente',
        'classe' => 'urgente',
        'badge' => 'badge-urgente'
    ],
    2 => [
        'label' => 'Idoso',
        'classe' => 'idoso',
        'badge' => 'badge-idoso'
    ],
    3 => [
        'label' => 'Grávida',
        'classe' => 'gravida',
        'badge' => 'badge-gravida'
    ],
    4 => [
        'label' => 'Normal',
        'classe' => 'normal',
        'badge' => 'badge-normal'
    ],
];

// Mensagem flash (após registo de paciente)
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,
          initial-scale=1.0">
    <title>Dashboard — <?= APP_NOME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/style.css">
    <meta http-equiv="refresh" content="30">
</head>

<body>

    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                HGB <span>Sistema</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item activo">
                    <span class="dot"></span>
                    Visão geral
                </a>
                <a href="registar.php" class="nav-item">
                    <span class="dot"></span>
                    Novo paciente
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

        <!-- CONTEÚDO PRINCIPAL -->
        <main class="conteudo">

            <!-- HEADER -->
            <div class="page-header">
                <div>
                    <h2>Boa tarde,
                        <?= htmlspecialchars(
                            explode(' ', sessao('utilizador_nome'))[0]
                        ) ?>
                    </h2>
                    <div class="sub">
                        <?= date('l, d \d\e F \d\e Y') ?>
                        &mdash; <?= date('H:i') ?>
                    </div>
                </div>
                <div class="header-acoes">
                    <a href="registar.php?urgencia=1" class="btn btn-perigo">
                        + Urgência
                    </a>
                    <a href="registar.php" class="btn btn-primario">
                        + Novo Paciente
                    </a>
                </div>
            </div>

            <!-- ALERTA DE PICO -->
            <?php if ($emEspera >= 15): ?>
                <div class="alerta alerta-aviso">
                    ⚠ <strong>Pico de afluência</strong> —
                    <?= $emEspera ?> pacientes em espera.
                    Tempo médio estimado:
                    <?= $tempoMedio > 0 ? $tempoMedio . ' min' : 'a calcular' ?>.
                </div>
            <?php endif; ?>

            <!-- ALERTA DE URGÊNCIA ACTIVA -->
            <?php if ($urgentes > 0): ?>
                <div class="alerta alerta-perigo">
                    ⚡ <strong>
                        <?= $urgentes ?>
                        <?= $urgentes === 1 ? 'urgência activa' : 'urgências activas' ?>
                    </strong> — aguarda atendimento imediato.
                </div>
            <?php endif; ?>

            <!-- MENSAGEM FLASH -->
            <?php if ($mensagem): ?>
                <div class="alerta alerta-sucesso">
                    ✓ <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <!-- MÉTRICAS -->
            <div class="metricas">
                <div class="metrica-card">
                    <div class="valor"><?= $emEspera ?></div>
                    <div class="rotulo">Em espera</div>
                </div>
                <div class="metrica-card">
                    <div class="valor" style="color:var(--vermelho)">
                        <?= $urgentes ?>
                    </div>
                    <div class="rotulo">Urgentes</div>
                </div>
                <div class="metrica-card">
                    <div class="valor" style="color:var(--verde)">
                        <?= $atendidos ?>
                    </div>
                    <div class="rotulo">Atendidos hoje</div>
                </div>
                <div class="metrica-card">
                    <div class="valor">
                        <?= $tempoMedio > 0
                            ? $tempoMedio . 'm'
                            : '--' ?>
                    </div>
                    <div class="rotulo">Tempo médio</div>
                </div>
            </div>

            <!-- FILA DE ESPERA -->
            <div class="card">
                <div class="card-header">
                    <span class="card-titulo">
                        Fila de espera — por prioridade
                    </span>
                    <span class="chip-tempo">
                        Actualiza a cada 30s
                    </span>
                </div>

                <?php if (empty($filaEspera)): ?>
                    <div class="fila-vazia">
                        Nenhum paciente em espera de momento.
                    </div>
                <?php else: ?>
                    <?php
                    $visíveis = array_slice($filaEspera, 0, 8);
                    $restantes = count($filaEspera) - count($visíveis);
                    foreach ($visíveis as $s):
                        $p = $prioridades[$s['prioridade']];
                        $hora = date(
                            'H:i',
                            strtotime($s['criado_em'])
                        );
                        ?>
                        <div class="fila-item <?= $p['classe'] ?>">
                            <div class="senha-codigo">
                                <?= htmlspecialchars($s['codigo']) ?>
                            </div>
                            <div>
                                <div class="fila-nome">
                                    <?= htmlspecialchars(
                                        $s['paciente_nome']
                                    ) ?>
                                </div>
                                <div class="fila-tipo">
                                    <?= htmlspecialchars(
                                        $s['tipo_atendimento']
                                    ) ?>
                                </div>
                            </div>
                            <div class="fila-meta">
                                <span class="badge <?= $p['badge'] ?>">
                                    <?= $p['label'] ?>
                                </span>
                                <span class="fila-hora">
                                    <?= $hora ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($restantes > 0): ?>
                        <div style="text-align:center;
                            padding:12px;
                            font-size:12px;
                            color:var(--texto-muted)">
                            + <?= $restantes ?> pacientes a seguir
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>

</body>

</html>