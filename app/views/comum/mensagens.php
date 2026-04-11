<?php
// ================================================
// Hospital Geral do Bengo
// Caixa de Mensagens (Comum a todos os perfis)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../app/models/Mensagem.php';

// Segurança: qualquer perfil logado
exigirPerfil(['admin', 'medico', 'recepcionista']);

$meuId = (int) sessao('utilizador_id');
$meuPerfil = sessao('perfil');

// Processa mensagens
$recebidas = Mensagem::caixaDeEntrada($meuId);
$enviadas = Mensagem::caixaDeSaida($meuId);
$naoLidas = Mensagem::contarNaoLidas($meuId);
$utilizadores = Mensagem::destinatarios($meuId);

$tab = $_GET['tab'] ?? 'entrada';
$msgSelecionada = null;

if ($tab === 'ler') {
    $msgId = (int) ($_GET['id'] ?? 0);
    if ($msgId > 0) {
        $msgSelecionada = Mensagem::obter($msgId, $meuId);
        // Marca como lida se for o destinatário
        if ($msgSelecionada && $msgSelecionada['destinatario_id'] === $meuId && $msgSelecionada['lida'] == 0) {
            Mensagem::marcarComoLida($msgId, $meuId);
            $msgSelecionada['lida'] = 1;
            $naoLidas--;
        }
    }
}

// Retorna ao dashboard correto de cada perfil
$urlVoltar = BASE_URL . "app/views/{$meuPerfil}/dashboard.php";

$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
$form_assunto = $_SESSION['form_msg_assunto'] ?? '';
$form_conteudo = $_SESSION['form_msg_conteudo'] ?? '';

unset($_SESSION['mensagem'], $_SESSION['erro'], $_SESSION['form_msg_assunto'], $_SESSION['form_msg_conteudo']);

function tempoRelativo($data)
{
    $ts = strtotime($data);
    $agora = time();
    $diff = $agora - $ts;

    if ($diff < 60)
        return "Agora mesmo";
    if ($diff < 3600)
        return round($diff / 60) . " min atrás";
    if ($diff < 86400) {
        $hrs = round($diff / 3600);
        return $hrs == 1 ? "Há 1 h" : "Há {$hrs} h";
    }
    if ($diff < 172800)
        return "Ontem às " . date('H:i', $ts);
    return date('d/m/Y H:i', $ts);
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens —
        <?= APP_NOME ?>
    </title>
    <?php include __DIR__ . '/head_assets.php'; ?>
    <style>
        .msg-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 20px;
            align-items: start;
        }

        .caixa-menu {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .menu-item {
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--texto);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 1px solid var(--borda);
            transition: background 0.2s;
            cursor: pointer;
        }

        .menu-item:hover {
            background: var(--fundo);
        }

        .menu-item.activo {
            background: var(--azul);
            color: #fff;
            border-color: var(--azul);
        }

        .badge-contador {
            background: var(--vermelho);
            color: #fff;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 12px;
            font-weight: bold;
        }

        .lista-mensagens {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
        }

        .msg-linha {
            display: flex;
            padding: 14px 16px;
            border-bottom: 1px solid var(--borda);
            gap: 16px;
            align-items: center;
            text-decoration: none;
            color: var(--texto);
            transition: background 0.15s;
        }

        .msg-linha:hover {
            background: #F8FAFC;
        }

        .msg-linha.nao-lida {
            background: #F0F9FF;
            font-weight: 700;
        }

        .msg-remetente {
            width: 140px;
            font-size: 13px;
            color: var(--texto);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .msg-assunto {
            flex: 1;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .msg-hora {
            font-size: 12px;
            color: var(--texto-muted);
            white-space: nowrap;
        }

        .msg-ler {
            padding: 24px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--borda);
        }

        .ler-header {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--borda);
        }

        .ler-assunto {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--texto);
        }

        .ler-meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--texto-muted);
        }

        .ler-conteudo {
            font-size: 14px;
            line-height: 1.6;
            color: var(--texto);
            white-space: pre-wrap;
        }

        .form-mensagem {
            background: #fff;
            padding: 24px;
            border-radius: var(--radius);
            border: 1px solid var(--borda);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--texto);
        }

        /* Checkbox list para Destinatários */
        .dest-lista {
            border: 1px solid var(--borda);
            border-radius: var(--radius-sm);
            max-height: 200px;
            overflow-y: auto;
            padding: 8px;
            background: #FAFAFA;
        }

        .dest-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            font-size: 13px;
            border-radius: 4px;
            cursor: pointer;
        }

        .dest-item:hover {
            background: #fff;
        }

        .input-assunto {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--borda);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: inherit;
        }

        .input-conteudo {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--borda);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: inherit;
            min-height: 160px;
            resize: vertical;
        }

        .vazio {
            padding: 32px;
            text-align: center;
            color: var(--texto-muted);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .msg-layout {
                grid-template-columns: 1fr;
            }

            .msg-remetente {
                width: 100px;
            }
        }
    </style>
</head>

<body class="text-on-surface">
<?php $paginaActual = 'mensagens'; ?>
        <?php include __DIR__ . '/sidebar.php'; ?>

        <?php
        $tituloPagina = 'Mensagens';
        $subtituloPagina = '';
        ob_start(); ?>
        <a href="?tab=escrever" class="btn btn-primario btn-sm">+ Nova Mensagem</a>
        <?php $accoesPagina = ob_get_clean(); ?>
        <?php include __DIR__ . '/header.php'; ?>
<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1500px]">
<div class="flex items-center justify-between mb-6">
                <div>
                    <h2>Caixa de Mensagens</h2>
                    <div class="sub">
                        Comunicação interna do hospital
                    </div>
                </div>
                <a href="?tab=escrever" class="btn btn-primario" style="margin-left:auto">
                    + Nova Mensagem
                </a>
            </div>

            <?php if ($mensagem): ?>
                <div class="alerta alerta-sucesso">✓
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>
            <?php if ($erro): ?>
                <div class="alerta alerta-perigo">⚠
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <div class="msg-layout">
                <!-- MENU CAIXA -->
                <div class="caixa-menu">
                    <a href="?tab=entrada" class="menu-item <?= $tab === 'entrada' ? 'activo' : '' ?>">
                        <span>📥 Caixa de Entrada</span>
                        <?php if ($naoLidas > 0): ?>
                            <span class="badge-contador">
                                <?= $naoLidas ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=saida" class="menu-item <?= $tab === 'saida' ? 'activo' : '' ?>">
                        <span>📤 Enviadas</span>
                    </a>
                    <a href="?tab=escrever" class="menu-item <?= $tab === 'escrever' ? 'activo' : '' ?>">
                        <span>✏️ Escrever nova</span>
                    </a>
                </div>

                <!-- CONTEÚDO (painel principal) -->
                <div class="caixa-conteudo">

                    <?php if ($tab === 'entrada'): ?>
                        <div class="lista-mensagens">
                            <?php if (empty($recebidas)): ?>
                                <div class="vazio">
                                    Nenhuma mensagem recebida.
                                </div>
                            <?php else: ?>
                                <?php foreach ($recebidas as $m):
                                    $isNaoLida = (int) $m['lida'] === 0; ?>
                                    <a href="?tab=ler&id=<?= $m['id'] ?>" class="msg-linha <?= $isNaoLida ? 'nao-lida' : '' ?>">
                                        <div class="msg-remetente">
                                            <?= htmlspecialchars($m['remetente_nome']) ?>
                                        </div>
                                        <div class="msg-assunto">
                                            <?= htmlspecialchars($m['assunto']) ?>
                                        </div>
                                        <div class="msg-hora">
                                            <?= tempoRelativo($m['criado_em']) ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($tab === 'saida'): ?>
                        <div class="lista-mensagens">
                            <?php if (empty($enviadas)): ?>
                                <div class="vazio">
                                    Nenhuma mensagem enviada.
                                </div>
                            <?php else: ?>
                                <?php foreach ($enviadas as $m): ?>
                                    <a href="?tab=ler&id=<?= $m['id'] ?>" class="msg-linha">
                                        <div class="msg-remetente">
                                            Para:
                                            <?= htmlspecialchars($m['destinatario_nome']) ?>
                                        </div>
                                        <div class="msg-assunto" style="font-weight:normal;">
                                            <?= htmlspecialchars($m['assunto']) ?>
                                            <?= (int) $m['lida'] ? '<span style="color:var(--verde);font-size:11px"> (Lida)</span>' : '' ?>
                                        </div>
                                        <div class="msg-hora">
                                            <?= tempoRelativo($m['criado_em']) ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($tab === 'ler' && $msgSelecionada): ?>
                        <div class="msg-ler">
                            <div class="ler-header">
                                <div class="ler-assunto">
                                    <?= htmlspecialchars($msgSelecionada['assunto']) ?>
                                </div>
                                <div class="ler-meta">
                                    <div>
                                        <strong>De:</strong>
                                        <?= htmlspecialchars($msgSelecionada['remetente_nome']) ?>
                                        <span style="font-size:11px;color:gray">(
                                            <?= ucfirst($msgSelecionada['remetente_perfil']) ?>)
                                        </span><br>
                                        <strong>Para:</strong>
                                        <?= htmlspecialchars($msgSelecionada['destinatario_nome']) ?>
                                    </div>
                                    <div style="text-align:right">
                                        <?= date('d/m/Y \à\s H:i', strtotime($msgSelecionada['criado_em'])) ?>
                                        <?php if ($msgSelecionada['remetente_id'] !== $meuId): ?>
                                            <div style="margin-top:8px">
                                                <a href="?tab=escrever&re=<?= urlencode('Re: ' . $msgSelecionada['assunto']) ?>&to=<?= $msgSelecionada['remetente_id'] ?>"
                                                    class="btn btn-sm">
                                                    Responder
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="ler-conteudo">
                                <?= htmlspecialchars($msgSelecionada['conteudo']) ?>
                            </div>
                        </div>

                    <?php elseif ($tab === 'escrever'):
                        $re = $_GET['re'] ?? $form_assunto;
                        $toId = (int) ($_GET['to'] ?? 0);
                        ?>
                        <div class="form-mensagem">
                            <form method="POST" action="<?= BASE_URL ?>app/controllers/mensagens.php">
                                <input type="hidden" name="acao" value="enviar">

                                <div class="form-group">
                                    <label>Para (Destinatários):</label>
                                    <div class="dest-lista">
                                        <?php foreach ($utilizadores as $u): ?>
                                            <label class="dest-item">
                                                <input type="checkbox" name="destinatarios[]" value="<?= $u['id'] ?>"
                                                    <?= $toId === $u['id'] ? 'checked' : '' ?>>
                                                <span>
                                                    <strong>
                                                        <?= htmlspecialchars($u['nome']) ?>
                                                    </strong>
                                                    <span style="color:var(--texto-muted);font-size:11px">—
                                                        <?= ucfirst($u['perfil']) ?>
                                                    </span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="assunto">Assunto</label>
                                    <input type="text" name="assunto" id="assunto" class="input-assunto" required
                                        value="<?= htmlspecialchars($re) ?>"
                                        placeholder="Ex: Informação sobre paciente U-004">
                                </div>

                                <div class="form-group">
                                    <label for="conteudo">Mensagem</label>
                                    <textarea name="conteudo" id="conteudo" class="input-conteudo" required
                                        placeholder="Escreva aqui a sua mensagem..."><?= htmlspecialchars($form_conteudo) ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primario">
                                    📤 Enviar Mensagem
                                </button>
                            </form>
                        </div>

                    <?php else: ?>
                        <div class="vazio">Mensagem não encontrada ou página inválida.</div>
                    <?php endif; ?>

                </div>
            </div>
</main>
</div>
</body>

</html>