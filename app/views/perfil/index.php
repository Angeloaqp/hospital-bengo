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

// Obter Dados Sensíveis, Métricas e Gráficos
$dados = Utilizador::obter($meuId);
$estatisticas = Utilizador::estatisticasPessoais($meuId, $meuPerfil);
$historico = Utilizador::ultimasAccoes($meuId, $meuPerfil);
$sparkline = Utilizador::sparkline7Dias($meuId, $meuPerfil);

// Calcular Percentagem de Conclusão do Perfil
$completedFields = 0;
$totalFields = 5;
if (!empty($dados['nome'])) $completedFields++;
if (!empty($dados['telefone'])) $completedFields++;
if (!empty($dados['foto_path'])) $completedFields++;
if (!empty($dados['nome_utilizador'])) $completedFields++;
if ($meuPerfil !== 'medico' || !empty($dados['especialidade'])) $completedFields++;
else $totalFields--; // Médicos têm especialidade, outros não

$completionPercent = round(($completedFields / $totalFields) * 100);

// Cores e Ícones
$corCargoBg = 'bg-gray-100 text-gray-800';
$iconeCargo = 'person';
$accentColor = 'text-primary';

if ($meuPerfil === 'medico') {
    $corCargoBg = 'bg-blue-50 text-blue-700 border border-blue-100';
    $iconeCargo = 'stethoscope';
    $accentColor = 'text-blue-600';
} elseif ($meuPerfil === 'recepcionista') {
    $corCargoBg = 'bg-amber-50 text-amber-700 border border-amber-100';
    $iconeCargo = 'front_desk';
    $accentColor = 'text-amber-600';
} elseif ($meuPerfil === 'admin') {
    $corCargoBg = 'bg-red-50 text-red-700 border border-red-100';
    $iconeCargo = 'admin_panel_settings';
    $accentColor = 'text-red-600';
}

$criadoA = isset($dados['criado_em']) ? dataFormatoPT($dados['criado_em'], 'curto') : '—';
$ultimoAcesso = isset($dados['ultimo_acesso']) ? date('d/m/Y H:i', strtotime($dados['ultimo_acesso'])) : 'Agora mesmo';
$_username = $dados['nome_utilizador'] ?? $dados['username'] ?? sessao('nome_utilizador') ?? 'utilizador';
$_fotoPath = $dados['foto_path'] ?? '';
$_inicial = strtoupper(substr($dados['nome'] ?? 'U', 0, 1));

// Cálculo dos Pontos do Sparkline
$maxVal = max($sparkline['data']) ?: 1;
$svgPoints = [];
$width = 300;
$height = 80;
$paddingX = 10;
$paddingY = 15;
$count = count($sparkline['data']);

foreach ($sparkline['data'] as $idx => $val) {
    $x = $paddingX + ($idx * (($width - 2 * $paddingX) / ($count - 1)));
    $y = $height - $paddingY - ($val / $maxVal * ($height - 2 * $paddingY));
    $svgPoints[] = "$x,$y";
}
$pointsStr = implode(' ', $svgPoints);

$fillPoints = $svgPoints;
$fillPoints[] = ($width - $paddingX) . "," . ($height - $paddingY);
$fillPoints[] = $paddingX . "," . ($height - $paddingY);
$fillPointsStr = implode(' ', $fillPoints);
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

        .btn-action-premium { transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .btn-action-premium:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); }
        .btn-action-premium:active { transform: scale(0.98); }

        .feed-row { transition: all 0.3s ease; }
        .feed-row:hover { background-color: var(--cor-surface-container-lowest); border-radius: 1.25rem; }
    </style>
</head>

<body class="text-on-surface bg-background">

    <?php $paginaActual = 'perfil'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php $tituloPagina = 'A Minha Conta'; $accoesPagina = ''; ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8 ">
        <main class="w-full">
            <div class="pb-32">

                <!-- TÍTULO DA SECÇÃO -->
                <div class="mb-10 bento-card flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <h2 class="text-[2.25rem] font-headline font-black text-on-surface tracking-tight leading-none">Perfil Profissional</h2>
                        <p class="text-[0.95rem] font-medium text-on-surface-variant/70 mt-2">Visão consolidada das suas credenciais, desempenho e atividades recentes.</p>
                    </div>
                    <div>
                        <a href="editar.php" class="px-5 py-2.5 bg-white border border-black/5 text-on-surface hover:bg-surface-container-low rounded-full flex items-center gap-2 btn-action-premium shadow-sm text-xs font-bold">
                            <span class="material-symbols-outlined text-[16px]">manage_accounts</span>
                            Definições da Conta
                        </a>
                    </div>
                </div>

                <!-- BENTO GRID PRINCIPAL -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                    
                    <!-- COLUNA ESQUERDA: IDENTIDADE E INFORMAÇÕES PESSOAIS (Col-5) -->
                    <div class="xl:col-span-5 flex flex-col gap-8">
                        
                        <!-- CARD 1: IDENTIDADE (ESTILO CARTÃO MÉDICO/BADGE PREMIUM) -->
                        <div class="bento-card delay-1 bg-gradient-to-b from-white to-blue-50/20 rounded-[2.5rem] p-8 border-2 border-zinc-200/80 shadow-[0_12px_40px_rgba(0,0,0,0.04)] flex flex-col relative overflow-hidden">
                            <!-- Recorte de Fita do Crachá (Lanyard slot) -->
                            <div class="w-16 h-3.5 mx-auto bg-zinc-200/70 rounded-full mb-6 relative shadow-inner flex items-center justify-center border border-zinc-300/40">
                                <div class="w-10 h-1.5 bg-zinc-300 rounded-full"></div>
                            </div>
                            
                            <!-- Efeito Holograma / Faixa Superior -->
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-amber-500 to-red-500"></div>

                            <!-- Cabeçalho do Cartão -->
                            <div class="flex items-center justify-between mb-6 pb-2 border-b border-zinc-100/80">
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-extrabold text-zinc-400 uppercase tracking-widest leading-none">Hospital Geral</span>
                                    <span class="text-[11px] font-black text-on-surface uppercase tracking-wider font-headline leading-tight">Do Bengo</span>
                                </div>
                                <div class="w-2.5 h-2.5 rounded-full bg-primary/20 flex items-center justify-center">
                                    <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                                </div>
                            </div>

                            <!-- Informações Centrais -->
                            <div class="flex flex-col items-center mb-6">
                                <div class="relative mb-5">
                                    <!-- Avatar com moldura estilo cartão profissional -->
                                    <div class="w-32 h-32 rounded-[2.25rem] bg-zinc-50 border-4 border-white shadow-lg flex items-center justify-center overflow-hidden transition-transform duration-500 hover:scale-[1.03]">
                                        <?php if (!empty($_fotoPath)): ?>
                                            <img src="<?= BASE_URL . 'public/' . $_fotoPath ?>" class="w-full h-full object-cover" alt="Foto Avatar">
                                        <?php else: ?>
                                            <span class="text-zinc-750 text-5xl font-black font-headline"><?= $_inicial ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Bolinha de Status Ativo -->
                                    <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-white rounded-2xl flex items-center justify-center shadow-md">
                                        <div class="w-4 h-4 bg-green-500 rounded-full shadow-[0_0_8px_rgba(34,197,94,0.6)] animate-pulse"></div>
                                    </div>
                                </div>

                                <h2 class="text-xl font-headline font-black text-center text-on-surface leading-tight px-2"><?= htmlspecialchars($dados['nome']) ?></h2>
                                <p class="text-[11px] font-extrabold text-zinc-400 mt-0.5 uppercase tracking-wider">@<?= htmlspecialchars($_username) ?></p>
                            </div>

                            <!-- Detalhes do Cargo / Especialidade -->
                            <div class="space-y-3 bg-zinc-50/50 p-4 rounded-3xl border border-zinc-100 mb-6 font-headline">
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-200/40">
                                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Cargo</span>
                                    <span class="px-2.5 py-1 <?= $corCargoBg ?> rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[12px] normal-case tracking-normal"><?= $iconeCargo ?></span>
                                        <?= htmlspecialchars(ucfirst($meuPerfil)) ?>
                                    </span>
                                </div>

                                <?php if ($meuPerfil === 'medico' && !empty($dados['especialidade'])): ?>
                                    <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-200/40">
                                        <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Especialidade</span>
                                        <span class="font-black text-blue-700 uppercase tracking-wide text-[10px]"><?= htmlspecialchars($dados['especialidade']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($meuPerfil === 'medico' && !empty($dados['consultorio'])): ?>
                                    <div class="flex items-center justify-between text-xs pb-2 border-b border-zinc-200/40">
                                        <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Gabinete</span>
                                        <span class="font-black text-zinc-700 uppercase tracking-wide text-[10px]"><?= htmlspecialchars($dados['consultorio']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Nº Registo</span>
                                    <span class="font-mono font-black text-zinc-800 text-[10px]">HGB-<?= str_pad($dados['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                </div>
                            </div>

                            <!-- Progresso do Perfil -->
                            <div class="w-full bg-zinc-50 border border-zinc-100 rounded-3xl p-4 mb-2">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-zinc-400">Estado da Credencial</span>
                                    <span class="text-[10px] font-black text-primary uppercase tracking-wider"><?= $completionPercent == 100 ? 'Ativa' : 'Pendente (' . $completionPercent . '%)' ?></span>
                                </div>
                                <div class="w-full h-2 bg-zinc-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-primary to-primary/80 rounded-full transition-all duration-1000" style="width: <?= $completionPercent ?>%"></div>
                                </div>
                            </div>

                            <!-- Barcode / Holograma de Autenticidade no Rodapé -->
                            <div class="w-full flex flex-col items-center gap-1.5 mt-4 pt-4 border-t border-dashed border-zinc-200/80">
                                <svg class="h-8 w-44 opacity-70" viewBox="0 0 100 30" preserveAspectRatio="none">
                                    <rect x="0" y="0" width="2" height="30" fill="black"/>
                                    <rect x="3" y="0" width="1" height="30" fill="black"/>
                                    <rect x="6" y="0" width="3" height="30" fill="black"/>
                                    <rect x="11" y="0" width="1" height="30" fill="black"/>
                                    <rect x="14" y="0" width="4" height="30" fill="black"/>
                                    <rect x="20" y="0" width="2" height="30" fill="black"/>
                                    <rect x="24" y="0" width="1" height="30" fill="black"/>
                                    <rect x="27" y="0" width="3" height="30" fill="black"/>
                                    <rect x="32" y="0" width="2" height="30" fill="black"/>
                                    <rect x="36" y="0" width="1" height="30" fill="black"/>
                                    <rect x="39" y="0" width="4" height="30" fill="black"/>
                                    <rect x="45" y="0" width="2" height="30" fill="black"/>
                                    <rect x="49" y="0" width="1" height="30" fill="black"/>
                                    <rect x="52" y="0" width="3" height="30" fill="black"/>
                                    <rect x="57" y="0" width="1" height="30" fill="black"/>
                                    <rect x="60" y="0" width="4" height="30" fill="black"/>
                                    <rect x="66" y="0" width="2" height="30" fill="black"/>
                                    <rect x="70" y="0" width="1" height="30" fill="black"/>
                                    <rect x="73" y="0" width="3" height="30" fill="black"/>
                                    <rect x="78" y="0" width="2" height="30" fill="black"/>
                                    <rect x="82" y="0" width="1" height="30" fill="black"/>
                                    <rect x="85" y="0" width="4" height="30" fill="black"/>
                                    <rect x="91" y="0" width="2" height="30" fill="black"/>
                                    <rect x="95" y="0" width="1" height="30" fill="black"/>
                                    <rect x="98" y="0" width="2" height="30" fill="black"/>
                                </svg>
                                <span class="text-[9px] font-mono text-zinc-400 tracking-[0.25em] uppercase">HGB-<?= str_pad($dados['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            </div>
                        </div>

                        <!-- CARD 2: DETALHES INSTITUCIONAIS -->
                        <div class="bento-card delay-2 bg-white rounded-[2.5rem] p-8 border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.02)]">
                            <h3 class="text-xs font-black text-on-surface-variant uppercase tracking-widest mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">info</span>
                                Informação de Registo
                            </h3>

                            <div class="space-y-4 font-headline">
                                <div class="flex items-center justify-between pb-3.5 border-b border-surface-container-low">
                                    <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Telefone</span>
                                    <span class="text-xs font-extrabold text-on-surface"><?= !empty($dados['telefone']) ? htmlspecialchars($dados['telefone']) : 'Não associado' ?></span>
                                </div>

                                <?php if ($meuPerfil === 'medico' && !empty($dados['consultorio'])): ?>
                                    <div class="flex items-center justify-between pb-3.5 border-b border-surface-container-low">
                                        <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Consultório</span>
                                        <span class="text-xs font-extrabold text-on-surface"><?= htmlspecialchars($dados['consultorio']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between pb-3.5 border-b border-surface-container-low">
                                    <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Registado em</span>
                                    <span class="text-xs font-extrabold text-on-surface"><?= $criadoA ?></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Última Atividade</span>
                                    <span class="text-xs font-extrabold text-on-surface"><?= $ultimoAcesso ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUNA DIREITA: ESTATÍSTICAS E ATIVIDADES (Col-7) -->
                    <div class="xl:col-span-7 flex flex-col gap-8">
                        
                        <!-- SECTOR 1: ESTATÍSTICAS EM BENTO CARDS -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            
                            <!-- Stat 1: Volume Diário -->
                            <div class="bento-card delay-2 bg-white rounded-[2rem] p-6 border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] relative overflow-hidden group">

                                <div class="relative z-10 flex flex-col h-full justify-between min-h-[120px]">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="material-symbols-outlined text-[20px] text-green-600 bg-green-50 w-10 h-10 flex items-center justify-center rounded-2xl shadow-sm">
                                            today
                                        </span>
                                        <span class="text-[9px] font-black text-green-600 bg-green-50 px-2 py-0.5 rounded-full uppercase tracking-wider">Hoje</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-headline font-black text-on-surface leading-none tracking-tight mb-1">
                                            <?= htmlspecialchars($estatisticas['hoje'] ?? 0) ?>
                                        </div>
                                        <div class="text-[9px] font-extrabold text-on-surface-variant/60 uppercase tracking-widest leading-none">
                                            <?= $meuPerfil === 'medico' ? 'Pacientes Atendidos' : ($meuPerfil === 'recepcionista' ? 'Senhas Emitidas' : 'Ações Realizadas') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stat 2: Eficiência ou Desempenho -->
                            <div class="bento-card delay-3 bg-white rounded-[2rem] p-6 border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] relative overflow-hidden group">

                                <div class="relative z-10 flex flex-col h-full justify-between min-h-[120px]">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="material-symbols-outlined text-[20px] text-blue-600 bg-blue-50 w-10 h-10 flex items-center justify-center rounded-2xl shadow-sm">
                                            avg_time
                                        </span>
                                        <span class="text-[9px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full uppercase tracking-wider">Média</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-headline font-black text-on-surface leading-none tracking-tight mb-1">
                                            <?= htmlspecialchars($estatisticas['tempo_medio'] ?? '--') ?>
                                        </div>
                                        <div class="text-[9px] font-extrabold text-on-surface-variant/60 uppercase tracking-widest leading-none">
                                            <?= $meuPerfil === 'medico' ? 'Tempo de Atendimento' : ($meuPerfil === 'recepcionista' ? 'Ritmo de Emissão' : 'Métricas Indisp.') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stat 3: Total Acumulado -->
                            <div class="bento-card delay-4 bg-white rounded-[2rem] p-6 border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] relative overflow-hidden group">

                                <div class="relative z-10 flex flex-col h-full justify-between min-h-[120px]">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="material-symbols-outlined text-[20px] text-purple-600 bg-purple-50 w-10 h-10 flex items-center justify-center rounded-2xl shadow-sm">
                                            stacked_bar_chart
                                        </span>
                                        <span class="text-[9px] font-black text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full uppercase tracking-wider">Total</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-headline font-black text-on-surface leading-none tracking-tight mb-1">
                                            <?= htmlspecialchars($estatisticas['total'] ?? 0) ?>
                                        </div>
                                        <div class="text-[9px] font-extrabold text-on-surface-variant/60 uppercase tracking-widest leading-none">
                                            <?= $meuPerfil === 'medico' ? 'Total Consultas' : ($meuPerfil === 'recepcionista' ? 'Total Registos' : 'Ações Históricas') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- SECTOR 2: MINI GRÁFICO (SPARKLINE DE TRABALHO) -->
                        <div class="bento-card delay-3 bg-white rounded-[2.5rem] p-8 border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.02)]">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                                <div>
                                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-widest flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">show_chart</span>
                                        Fluxo de Trabalho Semanal
                                    </h3>
                                    <p class="text-[10px] font-semibold text-on-surface-variant/50 mt-1">Volume de ações registadas nos últimos 7 dias.</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-on-surface">
                                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                                        Volume diário
                                    </div>
                                </div>
                            </div>

                            <div class="py-4">
                                <svg viewBox="0 0 300 80" class="w-full h-24 overflow-visible">
                                    <defs>
                                        <linearGradient id="sparklineGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="var(--cor-primary)" stop-opacity="0.25" />
                                            <stop offset="100%" stop-color="var(--cor-primary)" stop-opacity="0.0" />
                                        </linearGradient>
                                    </defs>
                                    <!-- Filled Area -->
                                    <polygon points="<?= $fillPointsStr ?>" fill="url(#sparklineGrad)" />
                                    <!-- Stroke Line -->
                                    <polyline points="<?= $pointsStr ?>" fill="none" stroke="var(--cor-primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <!-- Dots on points -->
                                    <?php 
                                    foreach ($sparkline['data'] as $idx => $val) {
                                        $x = $paddingX + ($idx * (($width - 2 * $paddingX) / ($count - 1)));
                                        $y = $height - $paddingY - ($val / $maxVal * ($height - 2 * $paddingY));
                                        $label = date('d/m', strtotime($sparkline['labels'][$idx]));
                                        $isLast = ($idx === $count - 1);
                                    ?>
                                        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="<?= $isLast ? '4.5' : '3.5' ?>" fill="<?= $isLast ? 'var(--cor-primary)' : 'white' ?>" stroke="var(--cor-primary)" stroke-width="1.5" class="transition-all hover:r-5 cursor-pointer" />
                                    <?php } ?>
                                </svg>

                                <div class="flex justify-between mt-4 px-2">
                                    <?php foreach ($sparkline['labels'] as $idx => $label): ?>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-extrabold text-on-surface-variant/40 uppercase tracking-wider"><?= date('D', strtotime($label)) ?></span>
                                            <span class="text-[9px] font-black text-on-surface mt-0.5"><?= $sparkline['data'][$idx] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- SECTOR 3: FEED DE ATIVIDADES RECENTES -->
                        <div class="bento-card delay-4 bg-white rounded-[2.5rem] border border-black/5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] flex-1 overflow-hidden flex flex-col min-h-[400px]">
                            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/20">
                                <h3 class="text-xs font-black text-on-surface uppercase tracking-widest flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">history</span>
                                    Histórico de Atividade
                                </h3>
                                <a href="historico.php" class="px-4 py-2 bg-white border border-gray-200 text-on-surface hover:bg-primary hover:text-white hover:border-primary rounded-full flex items-center gap-2 transition-all shadow-sm text-[9px] font-black uppercase tracking-widest btn-action-premium">
                                    Histórico Completo
                                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                </a>
                            </div>
                            
                            <div class="p-8 flex-1 overflow-y-auto custom-scrollbar">
                                <?php if (empty($historico)): ?>
                                    <div class="flex flex-col items-center justify-center h-full text-center py-12">
                                        <div class="w-16 h-16 bg-gray-50 rounded-[1.5rem] flex items-center justify-center mb-6 border border-black/5">
                                            <span class="material-symbols-outlined text-[30px] text-gray-300">work_history</span>
                                        </div>
                                        <p class="text-xs font-black text-on-surface-variant/40 uppercase tracking-widest leading-relaxed">Nenhuma ação recente<br>foi encontrada.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col gap-4">
                                        <?php foreach (array_slice($historico, 0, 8) as $idx => $accao): 
                                            $desc = htmlspecialchars($accao['descricao'] ?? $accao['accao'] ?? 'Ação registada');
                                            
                                            // Mapeamento dinâmico de ícones e cores premium
                                            $ic = 'bolt';
                                            $corBg = 'bg-gray-50 text-on-surface border border-gray-100';
                                            if (stripos($desc, 'senha') !== false || stripos($desc, 'chamou') !== false || stripos($desc, 'emitida') !== false) {
                                                $ic = 'campaign'; $corBg = 'bg-blue-50 text-blue-700 border border-blue-100';
                                            } elseif (stripos($desc, 'cancelar') !== false || stripos($desc, 'cancelada') !== false || stripos($desc, 'ausência') !== false) {
                                                $ic = 'cancel'; $corBg = 'bg-red-50 text-red-700 border border-red-100';
                                            } elseif (stripos($desc, 'atendimento') !== false || stripos($desc, 'concluiu') !== false || stripos($desc, 'concluida') !== false) {
                                                $ic = 'check_circle'; $corBg = 'bg-green-50 text-green-700 border border-green-100';
                                            } elseif (stripos($desc, 'login') !== false || stripos($desc, 'logout') !== false || stripos($desc, 'senha_hash') !== false) {
                                                $ic = 'vpn_key'; $corBg = 'bg-amber-50 text-amber-700 border border-amber-100';
                                            }
                                        ?>
                                            <div class="feed-row flex items-center gap-5 p-3.5 border border-transparent hover:border-black/5 hover:shadow-sm cursor-default">
                                                <div class="w-11 h-11 rounded-xl <?= $corBg ?> flex items-center justify-center shrink-0">
                                                    <span class="material-symbols-outlined text-[18px]"><?= $ic ?></span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-[0.9rem] font-extrabold text-on-surface leading-tight truncate"><?= $desc ?></div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span class="text-[9px] font-black text-on-surface-variant/40 tracking-widest uppercase">
                                                            <?= date('H:i \· d/m/Y', strtotime($accao['data_hora'] ?? $accao['criado_em'] ?? 'now')) ?>
                                                        </span>
                                                        <?php if (isset($accao['duracao']) && $accao['duracao'] !== null): ?>
                                                            <span class="w-1 h-1 rounded-full bg-zinc-300"></span>
                                                            <span class="text-[9px] font-extrabold text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded uppercase tracking-wider">
                                                                <?= $accao['duracao'] ?> min de consulta
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (isset($accao['ip']) && !empty($accao['ip'])): ?>
                                                            <span class="w-1 h-1 rounded-full bg-zinc-300"></span>
                                                            <span class="text-[9px] font-mono text-on-surface-variant/40">
                                                                IP: <?= htmlspecialchars($accao['ip']) ?>
                                                            </span>
                                                        <?php endif; ?>
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
