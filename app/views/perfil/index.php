<?php
// ================================================
// Hospital Geral do Bengo
// O Meu Perfil - Bento Grid Design Moderno (Premium)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

// Segurança
exigirPerfil(['admin', 'medico', 'recepcionista']);

$meuId = (int) sessao('utilizador_id');
$meuPerfil = sessao('perfil');

// Obter Dados Sensíveis e Métricas
$dados = Utilizador::obter($meuId);
$estatisticas = Utilizador::estatisticasPessoais($meuId, $meuPerfil);
$historico = Utilizador::ultimasAccoes($meuId, $meuPerfil);

// Cores e Ícones
$corCargoBg = 'bg-gray-100/80 text-gray-800';
$iconeCargo = 'person';

if ($meuPerfil === 'medico') {
    $corCargoBg = 'bg-blue-50 text-blue-700';
    $iconeCargo = 'stethoscope';
} elseif ($meuPerfil === 'recepcionista') {
    $corCargoBg = 'bg-amber-50 text-amber-700';
    $iconeCargo = 'front_desk';
} elseif ($meuPerfil === 'admin') {
    $corCargoBg = 'bg-primary text-white';
    $iconeCargo = 'admin_panel_settings';
}

$criadoA = isset($dados['criado_em']) ? dataFormatoPT($dados['criado_em'], 'curto') : '—';
$ultimoAcesso = isset($dados['ultimo_acesso']) ? date('d/m/Y H:i', strtotime($dados['ultimo_acesso'])) : 'Agora mesmo';
$_username = $dados['nome_utilizador'] ?? $dados['username'] ?? sessao('nome_utilizador') ?? 'admin';
$_fotoPath = $dados['foto_path'] ?? '';
$_inicial = strtoupper(substr($dados['nome'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O Meu Perfil — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--cor-scrollbar-light); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--cor-scrollbar-light-hover); }

        @keyframes bentoIn {
            0% { opacity: 0; transform: translateY(30px) scale(0.98); filter: blur(5px); }
            100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }
        .bento-card { animation: bentoIn 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        
        .delay-1 { animation-delay: 0.05s; }
        .delay-2 { animation-delay: 0.15s; }
        .delay-3 { animation-delay: 0.25s; }
        .delay-4 { animation-delay: 0.35s; }

        .btn-black { transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .btn-black:hover { transform: translateY(-3px); box-shadow: 0 14px 20px -8px rgba(0,0,0,0.3); }
        .btn-black:active { transform: scale(0.98); box-shadow: none; }

        .feed-row { transition: all 0.3s ease; }
        .feed-row:hover { background-color: var(--cor-surface-container-lowest); border-radius: 1rem; padding-left: 0.5rem; padding-right: 0.5rem; margin-left: -0.5rem; margin-right: -0.5rem; }
    </style>
</head>

<body class="text-on-surface bg-background">

    <?php $paginaActual = 'perfil'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php $tituloPagina = 'A Minha Conta'; ob_start(); ?>
    <!-- Vazio, o botao estara no próprio bento cartao -->
    <?php $accoesPagina = ob_get_clean(); ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
        <div class="pb-32">

            <div class="mb-10 bento-card">
                <h2 class="text-[2rem] font-headline font-black text-on-surface tracking-tight leading-none">Visão Pessoal</h2>
                <p class="text-[0.9rem] font-bold text-gray-400 mt-2">Os seus dados, actividade, e definições de segurança.</p>
            </div>

            <!-- BENTO GRID -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                
                <!-- COLUNA IDENTIDADE (Esquerda, Col-4) -->
                <div class="xl:col-span-4 flex flex-col gap-8">
                    
                    <!-- CARTÃO PRINCIPAL -->
                    <div class="bento-card delay-1 bg-white rounded-[2.5rem] p-10 border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col items-center">
                        
                        <div class="relative mb-6">
                            <div class="w-28 h-28 rounded-[2rem] bg-primary flex items-center justify-center overflow-hidden shadow-lg shadow-black/10 transition-transform duration-500 hover:scale-[1.03]">
                                <?php if (!empty($_fotoPath)): ?>
                                    <img src="<?= BASE_URL . 'public/' . $_fotoPath ?>" class="w-full h-full object-cover" alt="Foto Avatar">
                                <?php else: ?>
                                    <span class="text-white text-4xl font-black font-headline"><?= $_inicial ?></span>
                                <?php endif; ?>
                            </div>
                            <!-- Bolinha de Status Ativo -->
                            <div class="absolute -bottom-2 -right-2 w-7 h-7 bg-white rounded-xl flex items-center justify-center shadow-sm">
                                <div class="w-3.5 h-3.5 bg-green-500 rounded-full shadow-[0_0_8px_rgba(34,197,94,0.7)] animate-pulse"></div>
                            </div>
                        </div>

                        <h1 class="text-2xl font-headline font-black text-center text-on-surface mb-1 line-clamp-2"><?= htmlspecialchars($dados['nome']) ?></h1>
                        <p class="text-[13px] font-extrabold text-gray-400 mb-6">@<?= htmlspecialchars($_username) ?></p>

                        <div class="flex flex-wrap items-center justify-center gap-2 mb-8">
                            <span class="px-3 py-1.5 <?= $corCargoBg ?> rounded-full text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]"><?= $iconeCargo ?></span>
                                <?= htmlspecialchars(ucfirst($meuPerfil)) ?>
                            </span>

                            <?php if (!empty($dados['especialidade'])): ?>
                                <span class="px-3 py-1.5 bg-gray-50 border border-black/5 text-on-surface rounded-full text-[10px] font-black uppercase tracking-widest">
                                    <?= htmlspecialchars($dados['especialidade']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="w-full h-px bg-gray-100 mb-8"></div>

                        <!-- Mini Meta -->
                        <div class="w-full flex flex-col gap-4 mb-10">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[14px]">event</span> Empregado a
                                </span>
                                <span class="text-xs font-bold text-on-surface"><?= $criadoA ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[14px]">login</span> Último Acesso
                                </span>
                                <span class="text-xs font-bold text-on-surface truncate ml-2 text-right"><?= $ultimoAcesso ?></span>
                            </div>
                        </div>

                        <a href="editar.php" class="w-full bg-primary text-white px-6 py-4 rounded-[1.25rem] font-bold text-sm flex items-center justify-center gap-2 btn-black">
                            <span class="material-symbols-outlined text-[18px]">edit_square</span>
                            Editar Perfil Completo
                        </a>
                    </div>
                </div>

                <!-- COLUNA DIREITA ESTÁTISTICAS & FEED (Col-8) -->
                <div class="xl:col-span-8 flex flex-col gap-8">
                    
                    <!-- CARTÕES DE ESTATÍSTICA (Grelha Interna 2-cols) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Stat 1 -->
                        <div class="bento-card delay-2 bg-white rounded-[2.5rem] p-8 px-10 border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] relative overflow-hidden group">
                            <div class="absolute -right-6 -top-6 text-gray-50 opacity-50 group-hover:opacity-100 transition-opacity duration-500">
                                <span class="material-symbols-outlined" style="font-size: 140px;">task_alt</span>
                            </div>
                            <div class="relative z-10 flex flex-col h-full justify-between">
                                <div class="mb-8">
                                    <span class="material-symbols-outlined text-[28px] text-green-500 bg-green-50 w-12 h-12 flex items-center justify-center rounded-[1rem] shadow-sm">
                                        check_circle
                                    </span>
                                </div>
                                <div>
                                    <div class="text-[4rem] font-headline font-black text-on-surface leading-none tracking-tighter mb-2">
                                        <?= htmlspecialchars($estatisticas['hoje']['total'] ?? 0) ?>
                                    </div>
                                    <div class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest">
                                        <?= $meuPerfil === 'medico' ? 'Pacientes Atendidos Hoje' : 'Acções de Hoje' ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stat 2 -->
                        <div class="bento-card delay-3 bg-white rounded-[2.5rem] p-8 px-10 border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] relative overflow-hidden group">
                            <div class="absolute -right-6 -top-6 text-gray-50 opacity-50 group-hover:opacity-100 transition-opacity duration-500">
                                <span class="material-symbols-outlined" style="font-size: 140px;">hourglass_empty</span>
                            </div>
                            <div class="relative z-10 flex flex-col h-full justify-between">
                                <div class="mb-8">
                                    <span class="material-symbols-outlined text-[28px] text-amber-500 bg-amber-50 w-12 h-12 flex items-center justify-center rounded-[1rem] shadow-sm">
                                        pending_actions
                                    </span>
                                </div>
                                <div>
                                    <div class="text-[4rem] font-headline font-black text-on-surface leading-none tracking-tighter mb-2">
                                        <?= htmlspecialchars($estatisticas['pendentes'] ?? 0) ?>
                                    </div>
                                    <div class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest">
                                        Fila Geral do Hospital
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARTÃO FEED DE ATIVIDADE -->
                    <div class="bento-card delay-4 bg-white rounded-[2.5rem] border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex-1 overflow-hidden flex flex-col min-h-[400px]">
                        <div class="px-10 py-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/30">
                            <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-widest flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">history</span>
                                Registo de Atividade
                            </h3>
                            <a href="historico.php" class="px-4 py-2 bg-white border border-gray-200 text-on-surface hover:bg-primary hover:text-white hover:border-primary rounded-full flex items-center gap-2 transition-all shadow-sm text-[10px] font-extrabold uppercase tracking-widest">
                                Ver Completo
                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                        </div>
                        
                        <div class="p-10 flex-1 overflow-y-auto custom-scrollbar">
                            <?php if (empty($historico)): ?>
                                <div class="flex flex-col items-center justify-center h-full text-center py-10">
                                    <div class="w-16 h-16 bg-gray-50 rounded-[1.5rem] flex items-center justify-center mb-6">
                                        <span class="material-symbols-outlined text-[32px] text-gray-300">work_history</span>
                                    </div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest leading-relaxed">Nenhuma ação recente<br>foi encontrada hoje.</p>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col gap-6">
                                    <?php foreach (array_slice($historico, 0, 10) as $idx => $accao): 
                                        $desc = htmlspecialchars($accao['descricao'] ?? $accao['accao'] ?? 'Ação do sistema');
                                        
                                        // Mapeamento dinâmico de ícones premium e cores
                                        $ic = 'bolt';
                                        $corBg = 'bg-gray-100 text-on-surface';
                                        if (stripos($desc, 'senha') !== false || stripos($desc, 'chamou') !== false) {
                                            $ic = 'campaign'; $corBg = 'bg-blue-50 text-blue-600';
                                        } elseif (stripos($desc, 'cancelar') !== false) {
                                            $ic = 'cancel'; $corBg = 'bg-red-50 text-red-600';
                                        } elseif (stripos($desc, 'atendimento') !== false || stripos($desc, 'concluiu') !== false) {
                                            $ic = 'check_circle'; $corBg = 'bg-green-50 text-green-600';
                                        } elseif (stripos($desc, 'login') !== false || stripos($desc, 'logout') !== false) {
                                            $ic = 'vpn_key'; $corBg = 'bg-amber-50 text-amber-600';
                                        }
                                    ?>
                                        <div class="feed-row flex items-center gap-6 py-2 cursor-default">
                                            <div class="w-12 h-12 rounded-[1rem] <?= $corBg ?> flex items-center justify-center shrink-0 border border-black/5">
                                                <span class="material-symbols-outlined text-[20px]"><?= $ic ?></span>
                                            </div>
                                            <div class="flex-1">
                                                <div class="text-[0.95rem] font-bold text-on-surface mb-1"><?= $desc ?></div>
                                                <div class="text-[11px] font-extrabold text-gray-400 tracking-widest uppercase">
                                                    <?= date('H:i \· d/m', strtotime($accao['data_hora'] ?? 'now')) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

</body>
</html>
