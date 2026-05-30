<?php
// ================================================
// Hospital Geral do Bengo
// Caixa de Mensagens (Comum a todos os perfis) - Tactile Editorial
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
$lixo = Mensagem::lixo($meuId);
$totalLixo = count($lixo);

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

    if ($diff < 60) return "Agora mesmo";
    if ($diff < 3600) return round($diff / 60) . "m atrás";
    if ($diff < 86400) {
        $hrs = round($diff / 3600);
        return $hrs == 1 ? "Há 1h" : "Há {$hrs}h";
    }
    if ($diff < 172800) return "Ontem às " . date('H:i', $ts);
    return date('d/m/Y H:i', $ts);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }

        @keyframes glideIn {
            0% { opacity: 0; transform: translateY(20px); filter: blur(4px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .glide-in { animation: glideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .stagger-1 { animation-delay: 0.05s; }
        .stagger-2 { animation-delay: 0.1s; }

        /* Floating Label Field */
        .field-wrap { position: relative; width: 100%; margin-bottom: 24px; }
        .field-wrap .fi {
            width: 100%; background: #f4f5f7; border: 2px solid transparent; border-radius: 1.25rem;
            padding: 1.6rem 1.25rem 0.5rem 1.25rem; font-size: 0.95rem; font-weight: 600; color: #111;
            font-family: 'Manrope', sans-serif; outline: none; transition: all 0.35s cubic-bezier(0.2, 0.8, 0.2, 1); line-height: 1.2;
        }
        .field-wrap textarea.fi { min-height: 180px; resize: vertical; padding-top: 1.6rem; }
        .field-wrap .fi::placeholder { color: transparent; }
        .field-wrap .fi:focus { background: #fff; border-color: #111; box-shadow: 0 6px 24px -4px rgba(0,0,0,0.06); }
        
        .field-wrap .fl {
            position: absolute; left: 1.25rem; top: 1.3rem; font-size: 0.9rem; font-weight: 600; color: #71717a;
            pointer-events: none; transition: all 0.25s cubic-bezier(0.2, 0.8, 0.2, 1); transform-origin: left top; z-index: 2;
        }
        .field-wrap .fi:focus ~ .fl, .field-wrap .fi:not(:placeholder-shown) ~ .fl {
            transform: translateY(-0.75rem) scale(0.75); font-weight: 800; color: #111;
        }

        /* Buttons */
        .btn-action { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 14px 30px -6px rgba(0,0,0,0.15); }
        .btn-action:active { transform: scale(0.97) translateY(0); }

        /* Message Rows */
        .msg-row { border-left: 3px solid transparent; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .msg-row:hover { background: #fafafa; border-left-color: #e5e7eb; padding-left: calc(1rem + 2px); }
        .msg-row.nao-lida { border-left-color: #111; background: #f8fafc; }
        .msg-row.nao-lida:hover { border-left-color: #111; background: #f1f5f9; padding-left: calc(1rem + 2px); }
        
        /* Custom Checkboxes */
        .custom-checkbox input[type="checkbox"] {
            appearance: none; width: 1.25rem; height: 1.25rem; min-width: 1.25rem;
            border: 2px solid #d4d4d8; border-radius: 0.375rem;
            cursor: pointer; position: relative; transition: all 0.2s;
        }
        .custom-checkbox input[type="checkbox"]:checked {
            background-color: #111; border-color: #111;
        }
        .custom-checkbox input[type="checkbox"]:checked::after {
            content: '\e5ca'; /* Material Symbols check */
            font-family: 'Material Symbols Outlined';
            position: absolute; color: white;
            font-size: 14px; top: 50%; left: 50%; transform: translate(-50%, -50%);
            font-weight: bold;
        }
    </style>
</head>
<body class="text-on-surface bg-background">
    <?php $paginaActual = 'mensagens'; ?>
    <?php include __DIR__ . '/sidebar.php'; ?>

    <?php
    $tituloPagina = 'Mensagens';
    ob_start(); ?>
    <a href="?tab=escrever" class="px-5 py-2.5 bg-white border border-gray-200 text-on-surface rounded-full flex items-center gap-2 btn-action shadow-sm">
        <span class="material-symbols-outlined text-[18px]">edit_square</span>
        <span class="text-xs font-bold">Compor</span>
    </a>
    <?php $accoesPagina = ob_get_clean(); ?>

    <?php include __DIR__ . '/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
        <div class="pb-24">
            
            <?php if ($mensagem): ?>
                <div class="mb-6 p-4 bg-green-50 rounded-2xl flex items-center gap-3 border border-green-100 glide-in">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-white text-[16px]">check</span>
                    </div>
                    <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($mensagem) ?></p>
                </div>
            <?php endif; ?>
            <?php if ($erro): ?>
                <div class="mb-6 p-4 bg-red-50 rounded-2xl flex items-center gap-3 border border-red-100 glide-in">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-white text-[16px]">error</span>
                    </div>
                    <p class="text-sm font-bold text-red-800"><?= htmlspecialchars($erro) ?></p>
                </div>
            <?php endif; ?>

            <div class="mb-10 flex justify-between items-end glide-in">
                <div>
                    <h2 class="text-3xl font-headline font-extrabold text-on-surface tracking-tight">Comunicação Interna</h2>
                    <p class="text-sm font-semibold text-on-surface-variant mt-1 max-w-xl">Troque mensagens com administração, receção ou outros médicos com total confidencialidade.</p>
                </div>
            </div>

            <!-- Grid Layout Principal -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 glide-in stagger-1">
                
                <!-- Menu Lateral (Col 3) -->
                <div class="lg:col-span-3 flex flex-col gap-2">
                    <a href="?tab=entrada" class="flex items-center justify-between px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $tab === 'entrada' ? 'bg-primary text-white shadow-xl shadow-black/10' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[20px] <?= $tab === 'entrada' ? 'text-white' : 'text-gray-400' ?>" style="<?= $tab === 'entrada' ? 'font-variation-settings: \'FILL\' 1;' : '' ?>">inbox</span>
                            Caixa de Entrada
                        </div>
                        <?php if ($naoLidas > 0): ?>
                            <span class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full shadow-sm shadow-red-500/20"><?= $naoLidas ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="?tab=saida" class="flex items-center gap-3 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $tab === 'saida' ? 'bg-primary text-white shadow-xl shadow-black/10' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
                        <span class="material-symbols-outlined text-[20px] <?= $tab === 'saida' ? 'text-white' : 'text-gray-400' ?>" style="<?= $tab === 'saida' ? 'font-variation-settings: \'FILL\' 1;' : '' ?>">send</span>
                        Enviadas
                    </a>
                    
                    <a href="?tab=escrever" class="flex items-center gap-3 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $tab === 'escrever' ? 'bg-primary text-white shadow-xl shadow-black/10' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
                        <span class="material-symbols-outlined text-[20px] <?= $tab === 'escrever' ? 'text-white' : 'text-gray-400' ?>" style="<?= $tab === 'escrever' ? 'font-variation-settings: \'FILL\' 1;' : '' ?>">edit_square</span>
                        Nova Mensagem
                    </a>
                    
                    <a href="?tab=lixo" class="flex items-center justify-between px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $tab === 'lixo' ? 'bg-primary text-white shadow-xl shadow-black/10' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[20px] <?= $tab === 'lixo' ? 'text-white' : 'text-gray-400' ?>" style="<?= $tab === 'lixo' ? 'font-variation-settings: \'FILL\' 1;' : '' ?>">delete</span>
                            Lixo
                        </div>
                        <?php if ($totalLixo > 0): ?>
                            <span class="bg-gray-400 text-white text-[10px] px-2 py-0.5 rounded-full"><?= $totalLixo ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Quadro de Conteúdo (Col 9) -->
                <div class="lg:col-span-9">
                    <div class="bg-white rounded-[2rem] p-2 md:p-4 shadow-sm border border-primary/5 min-h-[500px] flex flex-col">
                        
                        <!-- TAB: ENTRADA -->
                        <?php if ($tab === 'entrada'): ?>
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <span class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest">A Receber</span>
                            </div>
                            <?php if (empty($recebidas)): ?>
                                <div class="flex-1 flex flex-col items-center justify-center p-16 text-center">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                        <span class="material-symbols-outlined text-[32px] text-gray-300">inbox</span>
                                    </div>
                                    <p class="text-sm font-bold text-gray-400">Tudo limpo por aqui.</p>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col">
                                    <?php foreach ($recebidas as $m): $isNaoLida = (int) $m['lida'] === 0; ?>
                                        <a href="?tab=ler&id=<?= $m['id'] ?>" class="msg-row flex flex-col md:flex-row items-start md:items-center p-4 border-b border-gray-50/50 cursor-pointer <?= $isNaoLida ? 'nao-lida' : '' ?>">
                                            <div class="w-full md:w-1/4 font-bold text-sm flex items-center gap-3 mb-2 md:mb-0 <?= $isNaoLida ? 'text-on-surface' : 'text-gray-500' ?>">
                                                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-xs text-white font-extrabold uppercase shrink-0">
                                                    <?= substr(htmlspecialchars($m['remetente_nome']), 0, 1) ?>
                                                </div>
                                                <span class="truncate block w-full"><?= htmlspecialchars($m['remetente_nome']) ?></span>
                                            </div>
                                            <div class="w-full md:w-2/4 px-0 md:px-4 text-sm truncate mb-2 md:mb-0 <?= $isNaoLida ? 'font-extrabold text-on-surface' : 'font-medium text-gray-500' ?>">
                                                <?= htmlspecialchars($m['assunto']) ?>
                                            </div>
                                            <div class="w-full md:w-1/4 text-xs font-bold text-left md:text-right <?= $isNaoLida ? 'text-gray-600' : 'text-gray-400' ?>">
                                                <?= tempoRelativo($m['criado_em']) ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        <!-- TAB: SAÍDA -->
                        <?php elseif ($tab === 'saida'): ?>
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <span class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest">Caixa de Saída</span>
                            </div>
                            <?php if (empty($enviadas)): ?>
                                <div class="flex-1 flex flex-col items-center justify-center p-16 text-center">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                        <span class="material-symbols-outlined text-[32px] text-gray-300">send</span>
                                    </div>
                                    <p class="text-sm font-bold text-gray-400">Nenhuma mensagem enviada.</p>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col">
                                    <?php foreach ($enviadas as $m): ?>
                                        <a href="?tab=ler&id=<?= $m['id'] ?>" class="msg-row flex flex-col md:flex-row items-start md:items-center p-4 border-b border-gray-50/50 cursor-pointer">
                                            <div class="w-full md:w-1/4 font-bold text-sm text-gray-500 flex items-center gap-3 mb-2 md:mb-0">
                                                <span class="text-xs text-gray-300 font-extrabold uppercase">Para</span>
                                                <span class="truncate block w-full text-on-surface"><?= htmlspecialchars($m['destinatario_nome']) ?></span>
                                            </div>
                                            <div class="w-full md:w-2/4 px-0 md:px-4 text-sm font-semibold text-gray-500 truncate mb-2 md:mb-0 flex items-center gap-2">
                                                <?= htmlspecialchars($m['assunto']) ?>
                                                <?php if ((int)$m['lida']): ?>
                                                    <span class="px-2 py-0.5 bg-green-50 text-green-600 text-[9px] rounded-full font-extrabold uppercase shrink-0">Lida</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="w-full md:w-1/4 text-xs font-bold text-gray-400 text-left md:text-right">
                                                <?= tempoRelativo($m['criado_em']) ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        <!-- TAB: LER -->
                        <?php elseif ($tab === 'ler' && $msgSelecionada): ?>
                            <div class="p-6 md:p-8 flex flex-col h-full">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 pb-6 border-b border-primary/5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-lg text-white font-extrabold uppercase shrink-0">
                                            <?= substr(htmlspecialchars($msgSelecionada['remetente_nome']), 0, 1) ?>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-headline font-extrabold text-on-surface mb-1"><?= htmlspecialchars($msgSelecionada['assunto']) ?></h3>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-extrabold text-on-surface"><?= htmlspecialchars($msgSelecionada['remetente_nome']) ?></span>
                                                <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-md uppercase">
                                                    <?= htmlspecialchars($msgSelecionada['remetente_perfil']) ?>
                                                </span>
                                            </div>
                                            <div class="text-[11px] font-bold text-gray-400 mt-1">
                                                Para: <?= htmlspecialchars($msgSelecionada['destinatario_nome']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 md:mt-0 text-right shrink-0">
                                        <div class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest">
                                            <?= dataFormatoPT($msgSelecionada['criado_em'], 'curto') ?>, <?= date('H:i', strtotime($msgSelecionada['criado_em'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-sm font-medium text-gray-800 leading-relaxed whitespace-pre-wrap mb-10 pl-2 lg:pl-16"><?= htmlspecialchars($msgSelecionada['conteudo']) ?></div>
                                
                                <div class="mt-auto pt-6 border-t border-primary/5 flex justify-end items-center gap-4">
                                    <form method="POST" action="<?= BASE_URL ?>app/controllers/mensagens.php" onsubmit="return confirm('Tem a certeza que deseja apagar esta mensagem?');">
                                        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                        <input type="hidden" name="acao" value="apagar">
                                        <input type="hidden" name="id" value="<?= $msgSelecionada['id'] ?>">
                                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-full text-sm font-extrabold btn-action transition-colors">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                            Apagar
                                        </button>
                                    </form>
                                    <?php if ($msgSelecionada['remetente_id'] !== $meuId): ?>
                                        <a href="?tab=escrever&re=<?= urlencode('Re: ' . $msgSelecionada['assunto']) ?>&to=<?= $msgSelecionada['remetente_id'] ?>" 
                                           class="inline-flex items-center gap-2 px-6 py-3 bg-white border-2 border-primary text-on-surface hover:bg-primary hover:text-white rounded-full text-sm font-extrabold btn-action transition-colors">
                                            <span class="material-symbols-outlined text-[18px]">reply</span>
                                            Responder
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <!-- TAB: ESCREVER -->
                        <?php elseif ($tab === 'escrever'): 
                            $re = $_GET['re'] ?? $form_assunto;
                            $toId = (int) ($_GET['to'] ?? 0);
                        ?>
                            <div class="p-4 md:p-8">
                                <form method="POST" action="<?= BASE_URL ?>app/controllers/mensagens.php">
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="acao" value="enviar">

                                    <div class="mb-10">
                                        <label class="block text-[11px] font-extrabold text-on-surface uppercase tracking-widest mb-4 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px] text-gray-400">group</span>
                                            Enviar Para
                                        </label>
                                        <div class="bg-gray-50/50 border border-primary/5 rounded-2xl p-4 max-h-[300px] overflow-y-auto custom-scrollbar">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                <?php foreach ($utilizadores as $u): ?>
                                                    <label class="custom-checkbox flex items-center p-3 rounded-xl bg-white border border-gray-200 hover:border-[#007aff] transition-colors cursor-pointer gap-3 shadow-sm">
                                                        <input type="checkbox" name="destinatarios[]" value="<?= $u['id'] ?>" <?= $toId === $u['id'] ? 'checked' : '' ?>>
                                                        <div class="flex flex-col truncate">
                                                            <span class="text-sm font-extrabold text-on-surface truncate"><?= htmlspecialchars($u['nome']) ?></span>
                                                            <span class="text-[10px] font-bold text-gray-500 uppercase mt-0.5"><?= ucfirst($u['perfil']) ?></span>
                                                        </div>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="field-wrap">
                                        <input type="text" name="assunto" id="assunto" class="fi" required placeholder=" " value="<?= htmlspecialchars($re) ?>">
                                        <label for="assunto" class="fl">Assunto</label>
                                    </div>

                                    <div class="field-wrap">
                                        <textarea name="conteudo" id="conteudo" class="fi" required placeholder=" "><?= htmlspecialchars($form_conteudo) ?></textarea>
                                        <label for="conteudo" class="fl">Escreva a sua mensagem...</label>
                                    </div>

                                    <div class="flex justify-end mt-4">
                                        <button type="submit" class="bg-primary text-white px-8 py-4 rounded-full font-extrabold text-sm flex items-center gap-2 btn-action shadow-lg shadow-black/10">
                                            <span class="material-symbols-outlined text-[18px]">send</span>
                                            Enviar Documento
                                        </button>
                                    </div>
                                </form>
                            </div>

                        <!-- TAB: LIXO -->
                        <?php elseif ($tab === 'lixo'): ?>
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <span class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest">Mensagens Apagadas</span>
                                <span class="text-[10px] font-bold text-gray-400"><?= $totalLixo ?> item(s)</span>
                            </div>
                            <?php if (empty($lixo)): ?>
                                <div class="flex-1 flex flex-col items-center justify-center p-16 text-center">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                        <span class="material-symbols-outlined text-[32px] text-gray-300">delete_sweep</span>
                                    </div>
                                    <p class="text-sm font-bold text-gray-400">O lixo está vazio.</p>
                                    <p class="text-xs text-gray-300 mt-1">Mensagens apagadas aparecerão aqui.</p>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col">
                                    <?php foreach ($lixo as $m): ?>
                                        <div class="msg-row flex flex-col md:flex-row items-start md:items-center p-4 border-b border-gray-50/50">
                                            <div class="w-full md:w-1/4 font-bold text-sm text-gray-400 flex items-center gap-3 mb-2 md:mb-0">
                                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-500 font-extrabold uppercase shrink-0">
                                                    <?= substr(htmlspecialchars($m['tipo'] === 'recebida' ? $m['remetente_nome'] : $m['destinatario_nome']), 0, 1) ?>
                                                </div>
                                                <div class="flex flex-col truncate">
                                                    <span class="truncate block w-full text-gray-600"><?= htmlspecialchars($m['tipo'] === 'recebida' ? $m['remetente_nome'] : $m['destinatario_nome']) ?></span>
                                                    <span class="text-[9px] text-gray-400 uppercase font-extrabold"><?= $m['tipo'] === 'recebida' ? 'Recebida' : 'Enviada' ?></span>
                                                </div>
                                            </div>
                                            <div class="w-full md:w-2/4 px-0 md:px-4 text-sm font-medium text-gray-400 truncate mb-2 md:mb-0">
                                                <?= htmlspecialchars($m['assunto']) ?>
                                            </div>
                                            <div class="w-full md:w-1/4 flex items-center justify-end gap-2">
                                                <span class="text-xs font-bold text-gray-300"><?= tempoRelativo($m['criado_em']) ?></span>
                                                <form method="POST" action="<?= BASE_URL ?>app/controllers/mensagens.php" class="m-0">
                                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                                    <input type="hidden" name="acao" value="restaurar">
                                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                    <button type="submit" class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg text-[10px] font-extrabold uppercase tracking-wider transition-colors flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-[14px]">restore</span>
                                                        Restaurar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="flex-1 flex flex-col items-center justify-center p-16 text-center">
                                <span class="material-symbols-outlined text-[48px] text-gray-300 mb-4">search_off</span>
                                <p class="text-sm font-bold text-gray-400">Página inválida ou mensagem deitada.</p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>