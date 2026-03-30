<?php
// ================================================
// Hospital Geral do Bengo
// Gestão de Utilizadores — Admin
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Estatistica.php';

exigirPerfil(['admin']);

$utilizadores = Estatistica::todosUtilizadores();
$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

$perfilLabel = [
    'admin' => 'Administrador',
    'medico' => 'Médico',
    'recepcionista' => 'Recepcionista',
];
$perfilBadge = [
    'admin' => 'badge-urgente',
    'medico' => 'badge-normal',
    'recepcionista' => 'badge-gravida',
];
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilizadores — <?= APP_NOME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/style.css">
    <style>
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
            vertical-align: middle;
        }

        .tabela tr:last-child td {
            border-bottom: none;
        }

        .tabela tr:hover td {
            background: var(--fundo);
        }

        .estado-activo {
            color: var(--verde);
            font-weight: 600;
        }

        .estado-inactivo {
            color: var(--texto-muted);
        }
    </style>
</head>

<body>

    <div class="layout">

        <aside class="sidebar">
            <div class="sidebar-logo">
                HGB <span>Admin</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="dot"></span>Estatísticas
                </a>
                <a href="utilizadores.php" class="nav-item activo">
                    <span class="dot"></span>Utilizadores
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

        <main class="conteudo">

            <div class="page-header">
                <div>
                    <h2>Gestão de Utilizadores</h2>
                    <div class="sub">
                        <?= count($utilizadores) ?>
                        utilizador(es) registado(s)
                    </div>
                </div>
                <a href="dashboard.php" class="btn">
                    ← Estatísticas
                </a>
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

            <div class="card">
                <div style="overflow-x:auto">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Utilizador</th>
                                <th>Perfil</th>
                                <th>Estado</th>
                                <th>Criado em</th>
                                <th>Acção</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilizadores as $u):
                                $badge = $perfilBadge[
                                    $u['perfil']
                                ] ?? 'badge-normal';
                                $label = $perfilLabel[
                                    $u['perfil']
                                ] ?? $u['perfil'];
                                $isActivo = (int) $u['estado'] === 1;
                                $isSelf =
                                    $u['id'] == sessao('utilizador_id');
                                $novoEstado = $isActivo ? 0 : 1;
                                $dataCriado = date(
                                    'd/m/Y',
                                    strtotime($u['criado_em'])
                                );
                                ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(
                                                $u['nome']
                                            ) ?>
                                        </strong>
                                        <?php if ($isSelf): ?>
                                            <span style="font-size:11px;
                                      color:var(--azul)">
                                                (eu)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--texto-muted)">
                                        <?= htmlspecialchars(
                                            $u['nome_utilizador']
                                        ) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badge ?>">
                                            <?= $label ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="<?= $isActivo
                                            ? 'estado-activo'
                                            : 'estado-inactivo' ?>">
                                            <?= $isActivo
                                                ? '● Activo'
                                                : '○ Inactivo' ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--texto-muted)">
                                        <?= $dataCriado ?>
                                    </td>
                                    <td>
                                        <?php if (!$isSelf): ?>
                                            <form method="POST" action="<?= BASE_URL ?>app/controllers/estatisticas.php"
                                                style="display:inline">
                                                <input type="hidden" name="acao" value="toggle_utilizador">
                                                <input type="hidden" name="utilizador_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="estado" value="<?= $novoEstado ?>">
                                                <button type="submit" class="btn btn-sm
                                            <?= $isActivo
                                                ? 'btn-perigo'
                                                : 'btn-primario' ?>">
                                                    <?= $isActivo
                                                        ? 'Desactivar'
                                                        : 'Activar' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size:12px;
                                      color:var(--texto-muted)">
                                                —
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

</body>

</html>