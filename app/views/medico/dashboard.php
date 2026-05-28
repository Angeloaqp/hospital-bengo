<?php
// ================================================
// Hospital Geral do Bengo
// Dashboard do Médico — Visão Geral (Tactile Editorial)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Senha.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

exigirPerfil(['medico', 'admin']);

$medicoId = (int) sessao('utilizador_id');
$meuPerfilObject = Utilizador::obter($medicoId);
$especialidade = Senha::especialidadeDoMedico($medicoId);
$consultorio = Senha::consultorioDoMedicoV2($medicoId);
$emAtend = Senha::emAtendimento($medicoId);
$emEspera = Senha::contarEsperaDoMedico($medicoId);
$urgentes = Senha::contarUrgentes();

// Estatísticas pessoais
$stats = Utilizador::estatisticasPessoais($medicoId, 'medico');
$sparkline = Utilizador::sparkline7Dias($medicoId, 'medico');
$accoes = Utilizador::ultimasAccoes($medicoId, 'medico');
$distribuicao = Senha::distribuicaoPrioridade($medicoId);

// Saudação contextual
$hora = (int) date('H');
if ($hora < 12) $saudacao = 'Bom dia';
elseif ($hora < 18) $saudacao = 'Boa tarde';
else $saudacao = 'Boa noite';

$primeiroNome = explode(' ', $meuPerfilObject['nome'] ?? '')[0];

// Dados para gráfico 7 dias
$maxSpark = max(1, max($sparkline['data']));
$diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

// Prioridades
$prioridades = [
    1 => ['label' => 'Urgente', 'color' => '#DC2626', 'bg' => 'bg-red-500', 'light' => 'bg-red-50 text-red-700'],
    2 => ['label' => 'Idoso', 'color' => '#F59E0B', 'bg' => 'bg-amber-500', 'light' => 'bg-amber-50 text-amber-700'],
    3 => ['label' => 'Grávida', 'color' => '#7C3AED', 'bg' => 'bg-purple-500', 'light' => 'bg-purple-50 text-purple-700'],
    4 => ['label' => 'Normal', 'color' => '#2563EB', 'bg' => 'bg-blue-500', 'light' => 'bg-blue-50 text-blue-700'],
];

$totalFila = array_sum($distribuicao);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Médico — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
        .tactile-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-weight: 800; letter-spacing: -0.05em; }
        @keyframes growUp { from { transform: scaleY(0); } to { transform: scaleY(1); } }
        .bar-grow { animation: growUp 0.6s ease-out forwards; transform-origin: bottom; }
        .floating-card { box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05), 0 2px 10px -2px rgba(0,0,0,0.03); }
    </style>
</head>
<body class="text-on-surface bg-[#f3f4f6]">

    <?php $paginaActual = 'dashboard'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <!-- Especialidade + Consultório + Relógio no Header -->
    <?php ob_start(); ?>
        <div class="hidden md:flex items-center gap-3">
            <?php if ($especialidade): ?>
                <div class="px-3 py-1.5 bg-surface-container-low rounded-full flex items-center gap-1.5 border border-black/5">
                    <span class="text-[12px]">🩺</span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant"><?= htmlspecialchars($especialidade['nome']) ?></span>
                </div>
            <?php endif; ?>
            <div class="px-3 py-1.5 bg-surface-container-low rounded-full flex items-center gap-1.5 border border-black/5">
                <span class="text-[12px]">📍</span>
                <span class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant"><?= $consultorio ? htmlspecialchars($consultorio['nome']) : 'Não Definido' ?></span>
            </div>
        </div>
    <?php $accoesPagina = ob_get_clean(); ?>

    <?php $tituloPagina = 'Dashboard'; ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <main class="w-full pb-24 pt-4">

                <!-- ============================================ -->
                <!-- SECÇÃO 1: Cabeçalho de Boas-Vindas           -->
                <!-- ============================================ -->
                <header class="mb-10 flex items-end justify-between fade-in">
                    <div>
                        <h2 class="text-4xl font-headline font-extrabold tracking-tighter mb-2">
                            <?= $saudacao ?>, Dr. <?= htmlspecialchars($primeiroNome) ?>
                        </h2>
                        <div class="flex items-center gap-3 text-on-surface-variant text-sm font-semibold">
                            <span><?= $especialidade ? htmlspecialchars($especialidade['nome']) : 'Clínica Geral' ?></span>
                            <span class="w-1 h-1 bg-surface-container-highest rounded-full"></span>
                            <span><?= $consultorio ? htmlspecialchars($consultorio['nome']) : 'Consultório' ?></span>
                            <span class="w-1 h-1 bg-surface-container-highest rounded-full"></span>
                            <span><?= date('d') ?> de <?php
                                $meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                                echo $meses[(int)date('m')-1];
                            ?> de <?= date('Y') ?></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($emAtend): ?>
                            <div class="flex items-center gap-2 bg-green-50 text-green-700 px-4 py-2 rounded-full font-black text-[10px] uppercase tracking-widest border border-green-100">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                Em Consulta
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-2 bg-surface-container-low text-on-surface-variant px-4 py-2 rounded-full font-black text-[10px] uppercase tracking-widest border border-black/5">
                                <span class="w-2 h-2 bg-surface-container-highest rounded-full"></span>
                                Disponível
                            </div>
                        <?php endif; ?>
                    </div>
                </header>

                <!-- Alerta de Urgência -->
                <?php if ($urgentes > 0): ?>
                    <div class="mb-8 p-4 bg-error/5 rounded-2xl flex items-center gap-4 border border-error/10 fade-in">
                        <div class="bg-error w-10 h-10 rounded-full flex items-center justify-center text-white shrink-0 shadow-lg shadow-error/20">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">bolt</span>
                        </div>
                        <div>
                            <p class="font-headline font-bold text-error tracking-tight">Tem <?= $urgentes ?> urgência(s) activa(s) na sua fila.</p>
                            <p class="text-xs text-error/80 font-medium">Priorize o atendimento na <a href="<?= BASE_URL ?>app/views/medico/fila_actual.php" class="underline font-bold">Fila Actual</a>.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ============================================ -->
                <!-- SECÇÃO 2: KPI Cards                          -->
                <!-- ============================================ -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Atendidos Hoje -->
                    <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white fade-in-delay-1">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-green-50 flex items-center justify-center">
                                <span class="material-symbols-outlined text-green-600">check_circle</span>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant">Hoje</span>
                        </div>
                        <p class="text-4xl tactile-mono text-black mb-1"><?= $stats['hoje'] ?></p>
                        <p class="text-[11px] font-bold text-on-surface-variant">Atendimentos concluídos</p>
                    </div>

                    <!-- Em Espera -->
                    <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white fade-in-delay-2">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-2xl <?= $emEspera > 8 ? 'bg-amber-50' : 'bg-blue-50' ?> flex items-center justify-center">
                                <span class="material-symbols-outlined <?= $emEspera > 8 ? 'text-amber-600' : 'text-blue-600' ?>">group</span>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant">Fila</span>
                        </div>
                        <p class="text-4xl tactile-mono <?= $emEspera > 8 ? 'text-amber-600' : 'text-black' ?> mb-1"><?= $emEspera ?></p>
                        <p class="text-[11px] font-bold text-on-surface-variant">Pacientes em espera</p>
                    </div>

                    <!-- Tempo Médio -->
                    <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white fade-in-delay-3">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                                <span class="material-symbols-outlined text-on-surface-variant">schedule</span>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant">Média</span>
                        </div>
                        <p class="text-4xl tactile-mono text-black mb-1"><?= $stats['tempo_medio'] ?></p>
                        <p class="text-[11px] font-bold text-on-surface-variant">Tempo médio por paciente</p>
                    </div>

                    <!-- Ausências -->
                    <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white fade-in-delay-4">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-2xl <?= $stats['ausencias'] > 0 ? 'bg-red-50' : 'bg-surface-container-low' ?> flex items-center justify-center">
                                <span class="material-symbols-outlined <?= $stats['ausencias'] > 0 ? 'text-red-500' : 'text-on-surface-variant' ?>">person_off</span>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant">Total</span>
                        </div>
                        <p class="text-4xl tactile-mono <?= $stats['ausencias'] > 0 ? 'text-red-500' : 'text-black' ?> mb-1"><?= $stats['ausencias'] ?></p>
                        <p class="text-[11px] font-bold text-on-surface-variant">Pacientes ausentes</p>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- SECÇÃO 3 + 4: Gráfico 7 Dias + Atalho Fila   -->
                <!-- ============================================ -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">

                    <!-- Gráfico 7 Dias -->
                    <section class="lg:col-span-3 bg-white rounded-[2rem] p-8 floating-card border border-white fade-in-delay-2">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                                    <span class="material-symbols-outlined text-black">bar_chart</span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-headline font-extrabold tracking-tight">Actividade Semanal</h3>
                                    <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Últimos 7 dias</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl tactile-mono text-black"><?= $stats['semana'] ?></p>
                                <p class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant">Total semana</p>
                            </div>
                        </div>
                        <!-- Chart area -->
                        <div class="relative" style="height: 150px;">
                            <!-- Horizontal guides -->
                            <div class="absolute inset-x-0 top-0 border-b border-dashed border-black/[0.04]"></div>
                            <div class="absolute inset-x-0 top-1/3 border-b border-dashed border-black/[0.04]"></div>
                            <div class="absolute inset-x-0 top-2/3 border-b border-dashed border-black/[0.04]"></div>
                            <div class="absolute inset-x-0 bottom-0 border-b border-black/[0.06]"></div>

                            <!-- Bars container -->
                            <div class="h-full flex items-end justify-between px-6">
                                <?php foreach ($sparkline['data'] as $i => $val): 
                                    $pct = $maxSpark > 0 ? ($val / $maxSpark) * 100 : 0;
                                    $isToday = ($sparkline['labels'][$i] === date('Y-m-d'));
                                    $barH = $val > 0 ? max(8, round($pct * 1.3)) : 3;
                                ?>
                                    <div class="flex flex-col items-center gap-1.5" style="width: 48px;">
                                        <span class="text-[10px] font-black tabular-nums <?= $isToday ? 'text-black' : ($val > 0 ? 'text-on-surface-variant' : 'text-on-surface-variant/30') ?>"><?= $val ?></span>
                                        <div class="w-5 rounded-full <?= $isToday ? 'bg-black' : ($val > 0 ? 'bg-black/15' : 'bg-black/[0.05]') ?> bar-grow"
                                             style="height: <?= $barH ?>px; animation-delay: <?= $i * 0.08 ?>s;"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Labels -->
                        <div class="flex items-center justify-between px-6 mt-3">
                            <?php foreach ($sparkline['labels'] as $i => $dateStr): 
                                $dayIdx = (int) date('w', strtotime($dateStr));
                                $isToday = ($dateStr === date('Y-m-d'));
                            ?>
                                <div class="text-center" style="width: 48px;">
                                    <span class="text-[9px] font-black uppercase tracking-wider <?= $isToday ? 'text-black' : 'text-on-surface-variant/50' ?>"><?= $diasSemana[$dayIdx] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Atalho Fila Actual -->
                    <section class="lg:col-span-2 bg-black rounded-[2rem] p-8 floating-card border border-white/10 text-white flex flex-col justify-between fade-in-delay-3 relative overflow-hidden">
                        <div class="absolute -top-16 -right-16 w-48 h-48 bg-white/5 rounded-full pointer-events-none"></div>
                        <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-white/5 rounded-full pointer-events-none"></div>
                        
                        <div class="relative z-10">
                            <span class="px-3 py-1 bg-white/10 rounded-full text-[10px] font-black uppercase tracking-widest inline-block mb-4">Fila Actual</span>
                            
                            <?php if ($emAtend): ?>
                                <h3 class="text-5xl tactile-mono text-white mb-3"><?= htmlspecialchars($emAtend['codigo']) ?></h3>
                                <p class="text-white/80 font-semibold text-sm mb-1"><?= htmlspecialchars($emAtend['paciente_nome']) ?></p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-green-400">Em consulta agora</span>
                                </div>
                            <?php else: ?>
                                <p class="text-2xl font-headline font-extrabold tracking-tight mb-2">Nenhum paciente</p>
                                <p class="text-white/60 font-semibold text-sm">em consulta de momento.</p>
                                <?php if ($emEspera > 0): ?>
                                    <div class="flex items-center gap-2 mt-3">
                                        <span class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-amber-400"><?= $emEspera ?> à espera</span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <a href="<?= BASE_URL ?>app/views/medico/fila_actual.php" 
                           class="mt-8 w-full bg-white text-black py-4 px-6 rounded-2xl font-black text-center flex items-center justify-center gap-3 hover:bg-white/90 active:scale-[0.98] transition-all relative z-10">
                            <span class="material-symbols-outlined">queue</span>
                            Ir para a Fila
                        </a>
                    </section>
                </div>

                <!-- ============================================ -->
                <!-- SECÇÃO 5 + 6: Histórico + Composição         -->
                <!-- ============================================ -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

                    <!-- Histórico Recente -->
                    <section class="lg:col-span-2 bg-white rounded-[2rem] overflow-hidden floating-card border border-white fade-in-delay-3">
                        <div class="p-8 pb-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                                    <span class="material-symbols-outlined text-black">history</span>
                                </div>
                                <h4 class="text-lg font-headline font-extrabold tracking-tight text-black">Histórico Recente</h4>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant"><?= count($accoes) ?> registos</span>
                        </div>
                        <?php if (!empty($accoes)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-surface-container-low/30 border-b border-surface-container-low">
                                            <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Senha</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Paciente</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Tipo</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Estado</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em] text-right">Duração</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-surface-container-low/50">
                                        <?php foreach (array_slice($accoes, 0, 10) as $a): ?>
                                            <tr class="hover:bg-surface-container-low/20 transition-colors">
                                                <td class="px-8 py-5">
                                                    <span class="tactile-mono text-sm px-2.5 py-1 bg-surface-container-high rounded text-black"><?= htmlspecialchars($a['codigo']) ?></span>
                                                </td>
                                                <td class="px-8 py-5 font-bold text-black text-sm"><?= htmlspecialchars($a['paciente_nome']) ?></td>
                                                <td class="px-8 py-5 text-xs text-on-surface-variant font-semibold"><?= htmlspecialchars($a['atendimento_tipo']) ?></td>
                                                <td class="px-8 py-5">
                                                    <?php if ($a['estado'] === 'concluida'): ?>
                                                        <span class="px-3 py-1 bg-green-50 text-green-700 rounded-full text-[10px] font-black uppercase tracking-wider">Concluída</span>
                                                    <?php else: ?>
                                                        <span class="px-3 py-1 bg-red-50 text-red-600 rounded-full text-[10px] font-black uppercase tracking-wider">Ausente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-8 py-5 text-right font-black text-on-surface-variant text-xs">
                                                    <?= $a['duracao'] ? $a['duracao'] . ' min' : '--' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-12 text-center">
                                <span class="material-symbols-outlined text-5xl text-surface-container-highest mb-4 block">inbox</span>
                                <p class="text-on-surface-variant font-bold">Nenhum atendimento registado ainda.</p>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- Composição da Fila -->
                    <section class="bg-white rounded-[2rem] p-8 floating-card border border-white fade-in-delay-4">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                                <span class="material-symbols-outlined text-black">donut_small</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-headline font-extrabold tracking-tight text-black">Composição da Fila</h4>
                                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest"><?= $totalFila ?> pacientes</p>
                            </div>
                        </div>

                        <?php if ($totalFila > 0): ?>
                            <div class="space-y-5">
                                <?php foreach ($prioridades as $pId => $p): 
                                    $count = $distribuicao[$pId];
                                    $pct = round(($count / $totalFila) * 100);
                                ?>
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full <?= $p['bg'] ?>"></div>
                                                <span class="text-xs font-bold text-black"><?= $p['label'] ?></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-on-surface-variant"><?= $count ?></span>
                                                <span class="text-[9px] font-black text-on-surface-variant/50"><?= $pct ?>%</span>
                                            </div>
                                        </div>
                                        <div class="h-2 bg-surface-container-low rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-700 bar-grow" 
                                                 style="width: <?= max(2, $pct) ?>%; background-color: <?= $p['color'] ?>; animation-delay: <?= ($pId - 1) * 0.15 ?>s;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Resumo rápido -->
                            <div class="mt-8 pt-6 border-t border-surface-container-low space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Esta semana</span>
                                    <span class="text-sm font-bold text-black"><?= $stats['semana'] ?> atendidos</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Este mês</span>
                                    <span class="text-sm font-bold text-black"><?= $stats['mes'] ?> atendidos</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Total geral</span>
                                    <span class="text-sm font-bold text-primary"><?= $stats['total'] ?> atendidos</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <span class="material-symbols-outlined text-5xl text-surface-container-highest mb-4 block">hourglass_empty</span>
                                <p class="text-on-surface-variant font-bold">A fila está vazia.</p>
                                <p class="text-xs text-on-surface-variant/60 mt-1">Sem pacientes em espera de momento.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

            </main>
        </div>
    </main>
</div>


</body>
</html>