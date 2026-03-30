<?php
// ================================================
// Hospital Geral do Bengo
// Formulário de Registo de Paciente
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Paciente.php';
require_once __DIR__ . '/../../../app/models/Senha.php';

exigirPerfil(['recepcionista', 'admin']);

$tipos = Paciente::tiposAtendimento();
$erros = $_SESSION['erros_form'] ?? [];
$antigos = $_SESSION['dados_form'] ?? [];
unset($_SESSION['erros_form'], $_SESSION['dados_form']);

// Pré-selecciona urgência se vier do botão "Urgência"
$isUrgencia = isset($_GET['urgencia']);
$prioPadrao = $isUrgencia ? '1' : '4';

$prioridades = [
    1 => [
        'label' => 'Urgente',
        'cor' => '#DC2626',
        'bg' => '#FEE2E2',
        'icone' => '⚡'
    ],
    2 => [
        'label' => 'Idoso',
        'cor' => '#D97706',
        'bg' => '#FEF3C7',
        'icone' => '👴'
    ],
    3 => [
        'label' => 'Grávida',
        'cor' => '#7C3AED',
        'bg' => '#EDE9FE',
        'icone' => '🤰'
    ],
    4 => [
        'label' => 'Normal',
        'cor' => '#1E6FD9',
        'bg' => '#E0F2FE',
        'icone' => '👤'
    ],
];
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Paciente — <?= APP_NOME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/style.css">
    <style>
        .form-registo {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 16px;
            align-items: start;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-size: 13px;
            color: #1A1A2E;
            font-family: inherit;
            transition: border-color .15s, box-shadow .15s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1E6FD9;
            box-shadow: 0 0 0 3px rgba(30, 111, 217, .12);
        }

        .form-group input.erro {
            border-color: #DC2626;
        }

        .form-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .secao-titulo {
            font-size: 11px;
            font-weight: 600;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin: 16px 0 10px;
        }

        .prio-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .prio-opcao {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 8px;
            border-radius: 8px;
            border: 2px solid #E5E7EB;
            cursor: pointer;
            transition: all .15s;
            text-align: center;
        }

        .prio-opcao input[type=radio] {
            display: none;
        }

        .prio-opcao .prio-icone {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .prio-opcao .prio-label {
            font-size: 12px;
            font-weight: 600;
        }

        .prio-opcao:hover {
            border-color: #9CA3AF;
        }

        .tipo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .tipo-opcao {
            padding: 10px 8px;
            border-radius: 8px;
            border: 2px solid #E5E7EB;
            cursor: pointer;
            text-align: center;
            font-size: 12px;
            font-weight: 500;
            color: #374151;
            transition: all .15s;
        }

        .tipo-opcao input[type=radio] {
            display: none;
        }

        .tipo-opcao:hover {
            border-color: #1E6FD9;
            background: #EFF6FF;
        }

        .preview-card {
            background: #1E6FD9;
            border-radius: 12px;
            padding: 20px;
            color: #fff;
            text-align: center;
            margin-bottom: 12px;
        }

        .preview-label {
            font-size: 11px;
            opacity: .75;
            margin-bottom: 6px;
        }

        .preview-senha {
            font-size: 44px;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1;
        }

        .preview-desc {
            font-size: 12px;
            opacity: .8;
            margin-top: 6px;
        }

        .preview-posicao {
            background: #fff;
            border: 0.5px solid #E5E7EB;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin-bottom: 12px;
        }

        .posicao-num {
            font-size: 32px;
            font-weight: 700;
            color: #1A1A2E;
        }

        .posicao-desc {
            font-size: 12px;
            color: #6B7280;
            margin-top: 4px;
        }

        #campo-peso {
            display: none;
        }

        @media (max-width: 768px) {
            .form-registo {
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
                HGB <span>Sistema</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="dot"></span>Visão geral
                </a>
                <a href="registar.php" class="nav-item activo">
                    <span class="dot"></span>Novo paciente
                </a>
                <div class="nav-item-logout">
                    <form method="POST" action="<?= BASE_URL ?>app/controllers/auth.php">
                        <input type="hidden" name="acao" value="logout">
                        <button type="submit" class="nav-item" style="width:100%;background:none;
                               border:none;cursor:pointer">
                            <span class="dot"></span>Sair
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <!-- CONTEÚDO -->
        <main class="conteudo">

            <div class="page-header">
                <div>
                    <h2>Registar novo paciente</h2>
                    <div class="sub">
                        Preencha todos os campos obrigatórios (*)
                    </div>
                </div>
                <a href="dashboard.php" class="btn">
                    ← Voltar
                </a>
            </div>

            <!-- ERROS -->
            <?php if (!empty($erros)): ?>
                <div class="alerta alerta-perigo" style="flex-direction:column;
                    align-items:flex-start;gap:4px">
                    <?php foreach ($erros as $e): ?>
                        <div>⚠ <?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- FORMULÁRIO -->
            <form method="POST" action="<?= BASE_URL ?>app/controllers/pacientes.php" id="form-registo">
                <input type="hidden" name="acao" value="registar">

                <div class="form-registo">

                    <!-- COLUNA ESQUERDA: campos -->
                    <div class="card">

                        <div class="secao-titulo">
                            Dados pessoais
                        </div>

                        <div class="form-group">
                            <label for="nome">
                                Nome completo *
                            </label>
                            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars(
                                $antigos['nome'] ?? ''
                            ) ?>" placeholder="Nome do paciente" required minlength="3">
                        </div>

                        <div class="form-2col">
                            <div class="form-group">
                                <label for="idade">Idade *</label>
                                <input type="number" id="idade" name="idade" value="<?= htmlspecialchars(
                                    $antigos['idade'] ?? ''
                                ) ?>" placeholder="Ex: 34" min="0" max="120" required>
                            </div>
                            <div class="form-group">
                                <label for="morada">Morada *</label>
                                <input type="text" id="morada" name="morada" value="<?= htmlspecialchars(
                                    $antigos['morada'] ?? ''
                                ) ?>" placeholder="Localidade" required>
                            </div>
                        </div>

                        <!-- Campo Peso — só para menores -->
                        <div class="form-group" id="campo-peso">
                            <label for="peso" style="color:#D97706">
                                Peso (kg) — obrigatório para menores *
                            </label>
                            <input type="number" id="peso" name="peso" value="<?= htmlspecialchars(
                                $antigos['peso'] ?? ''
                            ) ?>" placeholder="Ex: 32.5" step="0.1" min="1" max="200"
                                style="border-color:#D97706">
                        </div>

                        <div class="secao-titulo">
                            Tipo de atendimento *
                        </div>

                        <div class="tipo-grid" id="tipo-grid">
                            <?php foreach ($tipos as $t): ?>
                                <label class="tipo-opcao" id="tipo-label-<?= $t['id'] ?>">
                                    <input type="radio" name="tipo_atendimento_id" value="<?= $t['id'] ?>"
                                        <?= ($antigos['tipo_atendimento_id']
                                            ?? '1') == $t['id']
                                            ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($t['nome']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="secao-titulo" style="margin-top:16px">
                            Nível de prioridade *
                        </div>

                        <div class="prio-grid">
                            <?php foreach ($prioridades as $v => $p): ?>
                                <label class="prio-opcao" id="prio-label-<?= $v ?>" style="<?= ($antigos['prioridade']
                                      ?? $prioPadrao) == $v
                                      ? "border-color:{$p['cor']};
                                          background:{$p['bg']}"
                                      : '' ?>">
                                    <input type="radio" name="prioridade" value="<?= $v ?>" <?= ($antigos['prioridade']
                                          ?? $prioPadrao) == $v
                                          ? 'checked' : '' ?>>
                                    <span class="prio-icone">
                                        <?= $p['icone'] ?>
                                    </span>
                                    <span class="prio-label" style="color:<?= $p['cor'] ?>">
                                        <?= $p['label'] ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <!-- COLUNA DIREITA: prévia -->
                    <div>
                        <div class="preview-card" id="preview-card">
                            <div class="preview-label">
                                Senha a emitir
                            </div>
                            <div class="preview-senha" id="preview-codigo">
                                <?= $isUrgencia ? 'U-???' : 'N-???' ?>
                            </div>
                            <div class="preview-desc" id="preview-desc">
                                <?= $isUrgencia
                                    ? 'Urgente — Prioridade máxima'
                                    : 'Normal — Atendimento geral' ?>
                            </div>
                        </div>

                        <div class="preview-posicao">
                            <div class="posicao-num" id="preview-pos">—</div>
                            <div class="posicao-desc">
                                Posição estimada na fila
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primario" style="width:100%;
                                   justify-content:center;
                                   padding:12px;font-size:14px" id="btn-registar">
                            Registar e Emitir Senha
                        </button>
                    </div>

                </div>
            </form>

        </main>
    </div>

    <script>
        // ================================================
        // Lógica do formulário de registo
        // ================================================

        const prefixos = { 1: 'U', 2: 'I', 3: 'G', 4: 'N' };
        const descricoes = {
            1: 'Urgente — Prioridade máxima',
            2: 'Idoso — Prioridade alta',
            3: 'Grávida — Prioridade alta',
            4: 'Normal — Atendimento geral'
        };
        const cores = {
            1: '#DC2626', 2: '#D97706',
            3: '#7C3AED', 4: '#1E6FD9'
        };
        const prioInfo = {
            1: { cor: '#DC2626', bg: '#FEE2E2' },
            2: { cor: '#D97706', bg: '#FEF3C7' },
            3: { cor: '#7C3AED', bg: '#EDE9FE' },
            4: { cor: '#1E6FD9', bg: '#E0F2FE' }
        };

        // ---- Actualiza prévia ao mudar prioridade ----
        document.querySelectorAll(
            'input[name="prioridade"]'
        ).forEach(radio => {
            radio.addEventListener('change', () => {
                const v = parseInt(radio.value);
                const pre = prefixos[v] || 'N';

                // Actualiza código
                document.getElementById('preview-codigo')
                    .textContent = pre + '-???';
                document.getElementById('preview-desc')
                    .textContent = descricoes[v];

                // Actualiza cor do card
                document.getElementById('preview-card')
                    .style.background = cores[v];

                // Actualiza estilos das opções
                document.querySelectorAll('.prio-opcao')
                    .forEach(l => {
                        l.style.borderColor = '#E5E7EB';
                        l.style.background = '';
                    });
                const lbl = document.getElementById(
                    'prio-label-' + v
                );
                if (lbl) {
                    lbl.style.borderColor = prioInfo[v].cor;
                    lbl.style.background = prioInfo[v].bg;
                }
            });
        });

        // ---- Tipo de atendimento — destaque visual ----
        document.querySelectorAll(
            'input[name="tipo_atendimento_id"]'
        ).forEach(radio => {
            radio.addEventListener('change', () => {
                document.querySelectorAll('.tipo-opcao')
                    .forEach(l => {
                        l.style.borderColor = '#E5E7EB';
                        l.style.background = '';
                        l.style.color = '#374151';
                    });
                const lbl = radio.closest('.tipo-opcao');
                if (lbl) {
                    lbl.style.borderColor = '#1E6FD9';
                    lbl.style.background = '#EFF6FF';
                    lbl.style.color = '#1E6FD9';
                }
            });
        });

        // ---- Campo peso condicional ----
        document.getElementById('idade')
            .addEventListener('input', function () {
                const campoPeso = document.getElementById('campo-peso');
                const inputPeso = document.getElementById('peso');
                if (parseInt(this.value) < 18 && this.value !== '') {
                    campoPeso.style.display = 'block';
                    inputPeso.required = true;
                } else {
                    campoPeso.style.display = 'none';
                    inputPeso.required = false;
                    inputPeso.value = '';
                }
            });

        // ---- Feedback ao submeter ----
        document.getElementById('form-registo')
            .addEventListener('submit', () => {
                const btn = document.getElementById('btn-registar');
                btn.disabled = true;
                btn.textContent = 'A registar...';
            });

        // ---- Inicializa estilos das opções seleccionadas ----
        (function () {
            const checkedPrio = document.querySelector(
                'input[name="prioridade"]:checked'
            );
            if (checkedPrio) {
                const v = parseInt(checkedPrio.value);
                const lbl = document.getElementById(
                    'prio-label-' + v
                );
                if (lbl && prioInfo[v]) {
                    lbl.style.borderColor = prioInfo[v].cor;
                    lbl.style.background = prioInfo[v].bg;
                }
                document.getElementById('preview-card')
                    .style.background = cores[v] || '#1E6FD9';
                document.getElementById('preview-codigo')
                    .textContent = (prefixos[v] || 'N') + '-???';
                document.getElementById('preview-desc')
                    .textContent = descricoes[v];
            }
            const checkedTipo = document.querySelector(
                'input[name="tipo_atendimento_id"]:checked'
            );
            if (checkedTipo) {
                const lbl = checkedTipo.closest('.tipo-opcao');
                if (lbl) {
                    lbl.style.borderColor = '#1E6FD9';
                    lbl.style.background = '#EFF6FF';
                    lbl.style.color = '#1E6FD9';
                }
            }
        })();
    </script>

</body>

</html>