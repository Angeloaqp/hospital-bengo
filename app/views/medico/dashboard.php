<?php
// ================================================
// Hospital Geral do Bengo
// Dashboard do Médico — Gestão de Atendimentos
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Senha.php';

exigirPerfil(['medico', 'admin']);

$medicoId = (int) sessao('utilizador_id');
$proxima = Senha::proxima();
$emAtend = Senha::emAtendimento($medicoId);
$filaEspera = Senha::filaEspera();
$consultorio = Senha::consultorioDoMedico($medicoId);
$urgentes = Senha::contarUrgentes();
$emEspera = Senha::contarPorEstado('espera');

// Verifica janela de desfazer (15 segundos)
$podeDesfazer = false;
$ultimaChamada = $_SESSION['ultima_chamada'] ?? 0;
$chamadaTs = $_SESSION['chamada_ts'] ?? 0;
if ($ultimaChamada && $chamadaTs) {
    $segundos = time() - $chamadaTs;
    $podeDesfazer = $segundos <= 15;
    $restoUndo = max(0, 15 - $segundos);
}

$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

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
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médico — <?= APP_NOME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/style.css">
    <meta http-equiv="refresh" content="20">
    <style>
        .card-acao {
            background: var(--azul);
            border-radius: var(--radius);
            padding: 20px;
            color: #fff;
            margin-bottom: 16px;
        }

        .card-acao .next-label {
            font-size: 11px;
            opacity: .75;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .card-acao .next-senha {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1;
        }

        .card-acao .next-info {
            font-size: 13px;
            opacity: .85;
            margin-top: 4px;
        }

        .card-acao .acoes-btn {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .btn-chamar {
            background: #fff;
            color: var(--azul);
            border: none;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all .15s;
        }

        .btn-chamar:hover {
            background: #EFF6FF;
        }

        .btn-ausente {
            background: rgba(255, 255, 255, .15);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .35);
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            transition: all .15s;
        }

        .btn-ausente:hover {
            background: rgba(255, 255, 255, .25);
        }

        .card-em-atendimento {
            border: 2px solid var(--verde);
            border-radius: var(--radius);
            padding: 16px;
            background: #F0FDF4;
            margin-bottom: 16px;
        }

        .card-em-atendimento .titulo {
            font-size: 11px;
            font-weight: 600;
            color: var(--verde);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 8px;
        }

        .card-em-atendimento .senha-grande {
            font-size: 28px;
            font-weight: 800;
            color: var(--texto);
            letter-spacing: -1px;
        }

        .card-em-atendimento .info {
            font-size: 13px;
            color: var(--texto-muted);
            margin-top: 4px;
        }

        .card-em-atendimento .acoes-concluir {
            display: flex;
            gap: 8px;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .undo-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: #1E293B;
            border-radius: var(--radius-sm);
            color: #fff;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .undo-bar .undo-btn {
            background: rgba(255, 255, 255, .15);
            border: 1px solid rgba(255, 255, 255, .3);
            color: #fff;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            font-family: inherit;
        }

        .undo-bar .undo-btn:hover {
            background: rgba(255, 255, 255, .25);
        }

        .undo-progress {
            height: 3px;
            background: rgba(255, 255, 255, .2);
            border-radius: 2px;
            margin-top: 6px;
            overflow: hidden;
        }

        .undo-progress-fill {
            height: 100%;
            background: var(--azul);
            border-radius: 2px;
            transition: width .5s linear;
        }

        .consultorio-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--fundo);
            border: 0.5px solid var(--borda);
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 12px;
            color: var(--texto-muted);
        }
    </style>
</head>

<body>

    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                HGB <span>Médico</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item activo">
                    <span class="dot"></span>
                    Fila actual
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

            <!-- HEADER -->
            <div class="page-header">
                <div>
                    <h2>
                        <?= htmlspecialchars(
                            sessao('utilizador_nome')
                        ) ?>
                    </h2>
                    <div class="sub">
                        <?= $emEspera ?> paciente(s) em espera
                        &mdash; <?= date('H:i') ?>
                    </div>
                </div>
                <div class="consultorio-chip">
                    📍
                    <?= $consultorio
                        ? htmlspecialchars($consultorio['nome'])
                        : 'Consultório não definido' ?>
                </div>
            </div>

            <!-- ALERTA URGÊNCIA -->
            <?php if ($urgentes > 0): ?>
                <div class="alerta alerta-perigo">
                    ⚡ <strong>
                        <?= $urgentes ?> urgência(s) activa(s)
                    </strong> — requer atendimento imediato.
                </div>
            <?php endif; ?>

            <!-- MENSAGEM / ERRO -->
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

            <!-- BARRA DE DESFAZER -->
            <?php if ($podeDesfazer): ?>
                <div class="undo-bar" id="undo-bar">
                    <span>Paciente chamado — pode desfazer</span>
                    <form method="POST" action="<?= BASE_URL ?>app/controllers/senhas.php" style="display:inline">
                        <input type="hidden" name="acao" value="desfazer">
                        <input type="hidden" name="senha_id" value="<?= $ultimaChamada ?>">
                        <button type="submit" class="undo-btn">
                            ↩ Desfazer
                        </button>
                    </form>
                </div>
                <div class="undo-progress" style="margin-bottom:12px">
                    <div class="undo-progress-fill" id="undo-fill" style="width:<?= ($restoUndo / 15) * 100 ?>%">
                    </div>
                </div>
            <?php endif; ?>

            <!-- EM ATENDIMENTO AGORA -->
            <?php if ($emAtend): ?>
                <div class="card-em-atendimento">
                    <div class="titulo">
                        ✓ Em atendimento agora
                    </div>
                    <div class="senha-grande">
                        <?= htmlspecialchars($emAtend['codigo']) ?>
                    </div>
                    <div class="info">
                        <?= htmlspecialchars(
                            $emAtend['paciente_nome']
                        ) ?>
                        &mdash;
                        <?= htmlspecialchars(
                            $emAtend['tipo_atendimento']
                        ) ?>
                        &mdash;
                        <?= $emAtend['consultorio']
                            ? htmlspecialchars(
                                $emAtend['consultorio']
                            )
                            : '' ?>
                    </div>
                    <div class="acoes-concluir">
                        <form method="POST" action="<?= BASE_URL ?>app/controllers/senhas.php">
                            <input type="hidden" name="acao" value="concluir">
                            <input type="hidden" name="senha_id" value="<?= $emAtend['id'] ?>">
                            <button type="submit" class="btn btn-primario">
                                ✓ Concluir Atendimento
                            </button>
                        </form>
                        <form method="POST" action="<?= BASE_URL ?>app/controllers/senhas.php">
                            <input type="hidden" name="acao" value="cancelar">
                            <input type="hidden" name="senha_id" value="<?= $emAtend['id'] ?>">
                            <button type="submit" class="btn btn-perigo">
                                ✗ Paciente Ausente
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- PRÓXIMO A CHAMAR -->
            <?php if ($proxima): ?>
                <?php
                $corCard = [
                    1 => '#DC2626',
                    2 => '#D97706',
                    3 => '#7C3AED',
                    4 => '#1E6FD9'
                ];
                $cor = $corCard[$proxima['prioridade']] ?? '#1E6FD9';
                $hora = date(
                    'H:i',
                    strtotime($proxima['criado_em'])
                );
                ?>
                <div class="card-acao" style="background:<?= $cor ?>">
                    <div class="next-label">
                        Próximo a chamar
                    </div>
                    <div class="next-senha">
                        <?= htmlspecialchars($proxima['codigo']) ?>
                    </div>
                    <div class="next-info">
                        <?= htmlspecialchars(
                            $proxima['paciente_nome']
                        ) ?>
                        &mdash;
                        <?= htmlspecialchars(
                            $proxima['tipo_atendimento']
                        ) ?>
                        &mdash; chegou às <?= $hora ?>
                    </div>
                    <div class="acoes-btn">
                        <?php if (!$emAtend): ?>
                            <form method="POST" action="<?= BASE_URL ?>app/controllers/senhas.php">
                                <input type="hidden" name="acao" value="chamar">
                                <input type="hidden" name="senha_id" value="<?= $proxima['id'] ?>">
                                <button type="submit" class="btn-chamar">
                                    📢 Chamar Paciente
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="font-size:12px;opacity:.75">
                                Conclua o atendimento actual primeiro
                            </span>
                        <?php endif; ?>

                        <form method="POST" action="<?= BASE_URL ?>app/controllers/senhas.php">
                            <input type="hidden" name="acao" value="cancelar">
                            <input type="hidden" name="senha_id" value="<?= $proxima['id'] ?>">
                            <button type="submit" class="btn-ausente">
                                Ausente
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="fila-vazia">
                        Não há pacientes em espera de momento.
                    </div>
                </div>
            <?php endif; ?>

            <!-- FILA A SEGUIR -->
            <?php
            $filaRestante = array_filter(
                $filaEspera,
                fn($s) => $s['id'] !== ($proxima['id'] ?? 0)
            );
            $filaRestante = array_slice(
                array_values($filaRestante),
                0,
                5
            );
            ?>
            <?php if (!empty($filaRestante)): ?>
                <div class="card">
                    <div class="card-header">
                        <span class="card-titulo">
                            A seguir na fila
                        </span>
                    </div>
                    <?php foreach ($filaRestante as $s):
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
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        // Countdown da barra de desfazer
        (function () {
            const fill = document.getElementById('undo-fill');
            if (!fill) return;
            const resto = <?= $restoUndo ?? 0 ?>;
            let pct = (resto / 15) * 100;
            const iv = setInterval(() => {
                pct -= (100 / 15) * 0.5;
                if (pct <= 0) {
                    clearInterval(iv);
                    const bar = document.getElementById('undo-bar');
                    if (bar) bar.style.display = 'none';
                    return;
                }
                fill.style.width = pct + '%';
            }, 500);
        })();
    </script>

</body>

</html>