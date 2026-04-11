<?php
// ================================================
// Hospital Geral do Bengo
// Logs de Auditoria — Admin
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Auditoria.php';

exigirPerfil(['admin']);

// Filtros
$filtroAccao = trim($_GET['accao'] ?? '');
$filtroUser = (int) ($_GET['user'] ?? 0);
$dataInicio = trim($_GET['di'] ?? '');
$dataFim = trim($_GET['df'] ?? '');

$logs = Auditoria::listar(
    100,
    $filtroAccao ?: null,
    $filtroUser ?: null,
    $dataInicio ?: null,
    $dataFim ?: null
);

$utilizadores = Auditoria::utilizadoresParaFiltro();
$totalHoje = Auditoria::totalHoje();

// Ícones por tipo de acção
$iconeAccao = [
    'login' => '🔑',
    'logout' => '🚪',
    'chamar_paciente' => '📢',
    'concluir_atendimento' => '✅',
    'cancelar_paciente' => '❌',
    'desfazer_chamada' => '↩️',
    'registar_paciente' => '📝',
    'rechamar_paciente' => '🔁',
    'criar_utilizador' => '👤',
    'editar_utilizador' => '✏️',
    'toggle_utilizador' => '🔄',
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
    <title>Auditoria — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
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
            border-bottom: .5px solid var(--borda);
            vertical-align: middle;
        }

        .tabela tr:last-child td {
            border-bottom: none;
        }

        .tabela tr:hover td {
            background: var(--fundo);
        }

        .filtros {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            align-items: flex-end;
        }

        .filtro-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filtro-group label {
            font-size: 11px;
            font-weight: 600;
            color: var(--texto-muted);
            text-transform: uppercase;
        }

        .filtro-group input,
        .filtro-group select {
            padding: 8px 10px;
            border: 1px solid var(--borda);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: inherit;
        }

        .accao-cell {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detalhes-text {
            font-size: 12px;
            color: var(--texto-muted);
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ip-text {
            font-size: 11px;
            color: var(--texto-muted);
            font-family: monospace;
        }
    </style>
</head>

<body class="text-on-surface">
<?php $paginaActual = 'auditoria'; ?>
        <?php include __DIR__ . '/../comum/sidebar.php'; ?>

        <?php
        $tituloPagina = 'Auditoria';
        $subtituloPagina = $totalHoje . ' acções registadas hoje';
        ?>
        <?php include __DIR__ . '/../comum/header.php'; ?>
<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1500px]">
<!-- FILTROS -->
            <form method="GET" class="filtros">
                <div class="filtro-group">
                    <label>Acção</label>
                    <select name="accao">
                        <option value="">Todas</option>
                        <?php foreach ($iconeAccao as $k => $ic): ?>
                            <option value="<?= $k ?>" <?= $filtroAccao === $k
                                  ? 'selected' : '' ?>>
                                <?= $ic ?>     <?= ucfirst(
                                           str_replace('_', ' ', $k)
                                       ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-group">
                    <label>Utilizador</label>
                    <select name="user">
                        <option value="0">Todos</option>
                        <?php foreach ($utilizadores as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $filtroUser == $u['id']
                                  ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-group">
                    <label>De</label>
                    <input type="date" name="di" value="<?= htmlspecialchars($dataInicio) ?>">
                </div>
                <div class="filtro-group">
                    <label>Até</label>
                    <input type="date" name="df" value="<?= htmlspecialchars($dataFim) ?>">
                </div>
                <button type="submit" class="btn btn-primario">
                    Filtrar
                </button>
                <?php if (
                    $filtroAccao || $filtroUser
                    || $dataInicio || $dataFim
                ): ?>
                    <a href="auditoria.php" class="btn">Limpar</a>
                <?php endif; ?>
            </form>

            <!-- TABELA -->
            <div class="card">
                <?php if (empty($logs)): ?>
                    <div style="text-align:center;padding:24px;
                        color:var(--texto-muted);font-size:13px">
                        Nenhuma acção registada
                        <?= ($filtroAccao || $filtroUser)
                            ? 'com estes filtros.' : 'ainda.' ?>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th>Acção</th>
                                    <th>Utilizador</th>
                                    <th>Detalhes</th>
                                    <th>IP</th>
                                    <th>Data/Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $l):
                                    $ic = $iconeAccao[$l['accao']] ?? '📋';
                                    $badge = $perfilBadge[$l['perfil']]
                                        ?? 'badge-normal';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="accao-cell">
                                                <span><?= $ic ?></span>
                                                <strong>
                                                    <?= ucfirst(str_replace(
                                                        '_',
                                                        ' ',
                                                        $l['accao']
                                                    )) ?>
                                                </strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(
                                                $l['utilizador_nome']
                                            ) ?>
                                            <br>
                                            <span class="badge <?= $badge ?>" style="font-size:10px">
                                                <?= ucfirst($l['perfil']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($l['detalhes']): ?>
                                                <span class="detalhes-text" title="<?= htmlspecialchars(
                                                    $l['detalhes']
                                                ) ?>">
                                                    <?= htmlspecialchars(
                                                        $l['detalhes']
                                                    ) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color:var(--texto-muted)">
                                                    —
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="ip-text">
                                                <?= htmlspecialchars(
                                                    $l['ip'] ?? '—'
                                                ) ?>
                                            </span>
                                        </td>
                                        <td style="color:var(--texto-muted);
                                   white-space:nowrap">
                                            <?= date(
                                                'd/m/Y H:i:s',
                                                strtotime($l['criado_em'])
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
</main>
</div>
</body>

</html>