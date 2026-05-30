<?php
// ================================================
// Hospital Geral do Bengo
// Dashboard do Administrador — Visão Geral (Tactile)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Estatistica.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

// Segurança apenas para admin
exigirPerfil(['admin']);

$meuPerfilObject = Utilizador::obter((int) sessao('utilizador_id'));

$resumo = Estatistica::resumoHoje();
$porPrio = Estatistica::porPrioridade();
$porTipo = Estatistica::porTipoAtendimento();
$ultimos = Estatistica::ultimosAtendimentos(8);

$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

// Calcula máximo para escala das barras
$maxPrio = max(array_column($porPrio, 'total') ?: [1]);
$maxTipo = !empty($porTipo)
    ? max(array_column($porTipo, 'total'))
    : 1;

// Mapeamento visual das badges
$badgeConfig = [
    'espera'    => ['color' => 'bg-yellow-100 text-yellow-800', 'dot' => 'bg-yellow-500', 'label' => 'Em espera'],
    'chamada'   => ['color' => 'bg-blue-100 text-blue-800',  'dot' => 'bg-blue-500',  'label' => 'Em atendimento'],
    'concluida' => ['color' => 'bg-green-100 text-green-800', 'dot' => 'bg-green-500', 'label' => 'Concluído'],
    'cancelada' => ['color' => 'bg-red-100 text-red-800',    'dot' => 'bg-red-500',   'label' => 'Ausente'],
];

// Calcular alguns valores compostos amigáveis
$totalHoje = $resumo['total'] ?? 0;
$concluidosHoje = $resumo['concluidos'] ?? 0;
$esperaHoje = $resumo['em_espera'] ?? 0;
$ausentesHoje = $resumo['cancelados'] ?? 0;
$tempoMedio = $resumo['tempo_medio'] > 0 ? $resumo['tempo_medio'] . ' min' : '--';

$taxaConclusao = $totalHoje > 0 ? round(($concluidosHoje / $totalHoje) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visão Global — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
        
        /* Entrance Animations */
        @keyframes glideIn {
            0% { opacity: 0; transform: translateY(30px) scale(0.95); filter: blur(4px); }
            100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }
        .glide-in { animation: glideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }
        .stagger-4 { animation-delay: 0.4s; }
        .stagger-5 { animation-delay: 0.5s; }

        /* KPI Card Hover */
        .kpi-card { transition: all 0.5s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.06); border-color: rgba(0,0,0,0.05); }

        /* Progress Bar Animation */
        .bar-fill { transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1); width: 0%; }
        
        /* Activity Row */
        .activity-row { transition: all 0.3s ease; }
        .activity-row:hover { background-color: #f8fafc; transform: scale(1.005); box-shadow: 0 4px 15px -5px rgba(0,0,0,0.03); }
        
        /* Pulse Animation */
        @keyframes subtlePulse {
            0% { transform: scale(0.95); opacity: 0.7; }
            50% { transform: scale(1.05); opacity: 0.3; }
            100% { transform: scale(0.95); opacity: 0.7; }
        }
        .animate-subtle-pulse { animation: subtlePulse 2s infinite ease-in-out; }
    </style>
</head>

<body class="text-on-surface bg-[#f3f4f6]">

    <?php $paginaActual = 'dashboard'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php
    $tituloPagina = 'Visão Global';
    ob_start(); ?>
    <div class="px-4 py-2 bg-white rounded-full flex items-center gap-2 border border-primary/5 shadow-sm">
        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">calendar_today</span>
        <span class="text-xs font-bold text-black"><?= dataFormatoPT(null, 'curto') ?></span>
    </div>
    <?php $accoesPagina = ob_get_clean(); ?>
    
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
        <div class="pb-24">
            
            <?php if ($mensagem): ?>
                <div class="mb-6 p-4 bg-green-50 rounded-2xl flex items-center gap-3 border border-green-100 glide-in">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-white text-[16px]" style="font-variation-settings: 'FILL' 1;">check</span>
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

            <!-- Page Title -->
            <div class="mb-10 flex justify-between items-end glide-in">
                <div>
                    <h2 class="text-3xl font-headline font-extrabold text-black tracking-tight">Painel de Controlo</h2>
                    <p class="text-on-surface-variant font-medium mt-1 text-sm">Resumo da operação e fluidez do hospital hoje.</p>
                </div>
                <a href="utilizadores.php" class="bg-[#007aff] text-white px-8 py-3.5 rounded-full font-bold text-sm flex items-center gap-2 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all">
                    <span class="material-symbols-outlined text-[20px]">manage_accounts</span>
                    Gerir Utilizadores
                </a>
            </div>

            <!-- KPIs Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- KPI 1 -->
                <div class="bg-white rounded-[2rem] p-6 border border-white/50 shadow-sm kpi-card glide-in stagger-1 relative overflow-hidden">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center text-black">
                            <span class="material-symbols-outlined">receipt_long</span>
                        </div>
                        <span class="text-xs font-bold px-3 py-1 bg-gray-50 rounded-full text-on-surface-variant">Hoje</span>
                    </div>
                    <h3 class="text-on-surface-variant font-semibold text-sm mb-1 uppercase tracking-wider">Atendimentos</h3>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold text-black tracking-tight block"><?= $totalHoje ?></span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-primary/5 flex items-center justify-between text-xs font-bold text-on-surface-variant">
                        <span class="flex items-center gap-1 text-green-600"><span class="w-2 h-2 rounded-full bg-green-500"></span> <?= $concluidosHoje ?> Concluídos</span>
                        <span class="flex items-center gap-1 text-red-600"><span class="w-2 h-2 rounded-full bg-red-500"></span> <?= $ausentesHoje ?> Ausentes</span>
                    </div>
                </div>

                <!-- KPI 2 -->
                <div class="bg-white rounded-[2rem] p-6 border border-white/50 shadow-sm kpi-card glide-in stagger-2 relative overflow-hidden">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-yellow-50 rounded-2xl flex items-center justify-center text-yellow-600">
                            <span class="material-symbols-outlined">group</span>
                        </div>
                        <?php if($esperaHoje > 0): ?>
                            <div class="w-2.5 h-2.5 bg-yellow-400 rounded-full absolute top-8 right-8 animate-subtle-pulse"></div>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-on-surface-variant font-semibold text-sm mb-1 uppercase tracking-wider">Em Espera</h3>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold <?= $esperaHoje > 5 ? 'text-yellow-600' : 'text-black' ?> tracking-tight block"><?= $esperaHoje ?></span>
                        <span class="text-xs font-bold text-on-surface-variant">Pacientes</span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-primary/5 flex items-center text-xs font-bold text-on-surface-variant">
                        Fila aguardando chamada
                    </div>
                </div>

                <!-- KPI 3 -->
                <div class="bg-white rounded-[2rem] p-6 border border-white/50 shadow-sm kpi-card glide-in stagger-3 relative overflow-hidden">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                            <span class="material-symbols-outlined">timer</span>
                        </div>
                    </div>
                    <h3 class="text-on-surface-variant font-semibold text-sm mb-1 uppercase tracking-wider">Tempo Médio</h3>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold text-black tracking-tight block"><?= $tempoMedio ?></span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-primary/5 flex items-center text-xs font-bold text-on-surface-variant">
                        Da senha à chamada
                    </div>
                </div>

                <!-- KPI 4 -->
                <div class="bg-white rounded-[2rem] p-6 border border-white/50 shadow-sm kpi-card glide-in stagger-4 relative overflow-hidden">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-green-50 rounded-2xl flex items-center justify-center text-green-600">
                            <span class="material-symbols-outlined">check_circle</span>
                        </div>
                    </div>
                    <h3 class="text-on-surface-variant font-semibold text-sm mb-1 uppercase tracking-wider">Taxa de Conclusão</h3>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold text-black tracking-tight block"><?= $taxaConclusao ?>%</span>
                    </div>
                    <!-- Progressive bar tiny -->
                    <div class="mt-5 w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-[#007aff] h-full rounded-full transition-all duration-1000 ease-out" style="width: 0%" data-target-width="<?= $taxaConclusao ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
                
                <!-- Chart 1: Por Prioridade -->
                <div class="bg-white rounded-[2rem] p-8 border border-white/50 shadow-sm hover:shadow-md transition-shadow glide-in stagger-3">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-black">
                            <span class="material-symbols-outlined text-[20px]">stacked_bar_chart</span>
                        </div>
                        <h3 class="font-bold text-lg text-black tracking-tight">Por Prioridade</h3>
                    </div>
                    
                    <div class="space-y-6">
                        <?php foreach ($porPrio as $p):
                            $pct = $maxPrio > 0 ? round(($p['total'] / $maxPrio) * 100) : 0;
                            // Map colors correctly for premium feel
                            $rawColor = strtolower(trim($p['cor']));
                            $cleanColor = $rawColor === '#ff0000' || strpos($rawColor, 'red') !== false ? 'bg-red-500' :
                                         ($rawColor === '#ffcc00' || strpos($rawColor, 'yellow') !== false ? 'bg-yellow-500' : 
                                         ($rawColor === '#00cc00' || strpos($rawColor, 'green') !== false ? 'bg-green-500' : 'bg-[#007aff]'));
                        ?>
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="text-on-surface-variant uppercase tracking-wider"><?= htmlspecialchars($p['label']) ?></span>
                                <span class="text-black text-sm"><?= $p['total'] ?></span>
                            </div>
                            <div class="w-full h-3 bg-surface-container-low rounded-full overflow-hidden">
                                <div class="h-full <?= $cleanColor ?> rounded-full bar-fill" data-target-width="<?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($porPrio)): ?>
                            <div class="py-6 text-center text-sm font-bold text-on-surface-variant/50">Sem dados registados.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chart 2: Por Tipo de Atendimento -->
                <div class="bg-white rounded-[2rem] p-8 border border-white/50 shadow-sm hover:shadow-md transition-shadow glide-in stagger-4">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-black">
                            <span class="material-symbols-outlined text-[20px]">pie_chart</span>
                        </div>
                        <h3 class="font-bold text-lg text-black tracking-tight">Por Especialidade/Tipo</h3>
                    </div>
                    
                    <div class="space-y-6">
                        <?php if (!empty($porTipo)):
                            $typeColors = ['bg-indigo-500', 'bg-blue-400', 'bg-purple-500', 'bg-pink-500'];
                            foreach ($porTipo as $i => $t):
                                $pct = $maxTipo > 0 ? round(($t['total'] / $maxTipo) * 100) : 0;
                                $colorCls = $typeColors[$i % count($typeColors)];
                        ?>
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="text-on-surface-variant uppercase tracking-wider"><?= htmlspecialchars($t['tipo']) ?></span>
                                <span class="text-black text-sm"><?= $t['total'] ?></span>
                            </div>
                            <div class="w-full h-3 bg-surface-container-low rounded-full overflow-hidden">
                                <div class="h-full <?= $colorCls ?> rounded-full bar-fill" data-target-width="<?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                            <div class="py-6 text-center text-sm font-bold text-on-surface-variant/50">Sem dados registados.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity List -->
            <div class="bg-white rounded-[2rem] border border-white/50 shadow-sm overflow-hidden glide-in stagger-5">
                <div class="p-8 border-b border-primary/5 flex justify-between items-center bg-white/50 backdrop-blur-md sticky top-0 z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-black">
                            <span class="material-symbols-outlined text-[20px]">list_alt</span>
                        </div>
                        <h3 class="font-bold text-lg text-black tracking-tight">Últimos Atendimentos</h3>
                    </div>
                </div>

                <div class="p-4 pt-2">
                    <?php if (!empty($ultimos)): ?>
                        <!-- Column Headers -->
                        <div class="grid grid-cols-12 gap-4 px-6 py-3 border-b border-primary/5 text-[10px] uppercase tracking-widest font-extrabold text-on-surface-variant/80">
                            <div class="col-span-2">Senha</div>
                            <div class="col-span-3">Paciente</div>
                            <div class="col-span-2">Especialidade / Tipo</div>
                            <div class="col-span-2">Médico</div>
                            <div class="col-span-3 text-right">Espera & Estado</div>
                        </div>

                        <!-- Rows -->
                        <div class="flex flex-col gap-2 mt-4">
                            <?php foreach ($ultimos as $a):
                                $espera = '—';
                                if ($a['hora_chamada']) {
                                    $diff = strtotime($a['hora_chamada']) - strtotime($a['criado_em']);
                                    $espera = round($diff / 60) . 'm';
                                }
                                $cfg = $badgeConfig[$a['estado']] ?? ['color' => 'bg-gray-100 text-gray-800', 'dot' => 'bg-gray-400', 'label' => $a['estado']];
                                $hora = date('H:i', strtotime($a['criado_em']));
                                $medico = $a['medico'] ? htmlspecialchars(explode(' ', $a['medico'])[0]) : '—';
                            ?>
                            <div class="activity-row bg-white border border-primary/5 rounded-2xl p-4 px-6 grid grid-cols-12 gap-4 items-center">
                                <!-- Senha -->
                                <div class="col-span-2 flex items-center gap-3">
                                    <span class="px-3 py-1.5 bg-surface-container-low text-black rounded-lg font-black text-sm tracking-wide">
                                        <?= htmlspecialchars($a['codigo']) ?>
                                    </span>
                                </div>
                                <!-- Paciente -->
                                <div class="col-span-3 flex flex-col justify-center">
                                    <span class="font-bold text-sm text-black truncate"><?= htmlspecialchars($a['paciente']) ?></span>
                                    <span class="text-xs text-on-surface-variant font-semibold mt-0.5"><?= $a['idade'] ?> anos</span>
                                </div>
                                <!-- Tipo -->
                                <div class="col-span-2 flex items-center">
                                    <span class="text-xs font-bold text-on-surface-variant bg-gray-50 px-3 py-1 rounded-full truncate max-w-full">
                                        <?= htmlspecialchars($a['tipo']) ?>
                                    </span>
                                </div>
                                <!-- Médico -->
                                <div class="col-span-2 flex items-center gap-2">
                                    <?php if($medico !== '—'): ?>
                                        <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-black text-black shrink-0">
                                            <?= substr($medico, 0, 1) ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-sm font-bold text-black truncate"><?= $medico ?></span>
                                </div>
                                <!-- Estado & Tempo -->
                                <div class="col-span-3 flex items-center justify-end gap-4">
                                    <div class="flex flex-col items-end">
                                        <span class="text-xs font-extrabold text-black"><?= $hora ?></span>
                                        <span class="text-[10px] font-bold text-on-surface-variant/70 uppercase tracking-wide mt-0.5"><?= $espera ?></span>
                                    </div>
                                    <span class="px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-1.5 whitespace-nowrap <?= $cfg['color'] ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $cfg['dot'] ?>"></span>
                                        <?= $cfg['label'] ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="py-16 text-center">
                            <span class="material-symbols-outlined text-4xl text-black/10 mb-2">assignment_ind</span>
                            <p class="text-sm font-bold text-on-surface-variant">Nenhum atendimento registado hoje.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </main>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Animate progress bars smoothly on load
            setTimeout(() => {
                document.querySelectorAll('.bar-fill, [data-target-width]').forEach(el => {
                    const width = el.getAttribute('data-target-width');
                    if(width) {
                        el.style.width = width;
                    }
                });
            }, 300);
        });
    </script>
</body>
</html>