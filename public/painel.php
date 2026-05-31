<?php
// ================================================
// Hospital Geral do Bengo
// Painel Público — Sala de Espera (TV) - Template Premium
// ================================================

require_once __DIR__ . '/../config/base_url.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Senha.php';

$emChamada = Senha::emChamadaAgora();
$proximas = Senha::proximasParaPainel(5);
$concluidas = Senha::ultimasConcluidas(4);
$canceladas = Senha::ultimasCanceladas(4);
$tempoMedio = Senha::tempoMedioPublico();
$emEspera = Senha::contarPorEstado('espera');

// Configuração de cores e ícones consoante a prioridade para encaixar no painel premium
$pColors = [
    1 => ['class' => 'urgent', 'icon' => 'warning', 'label' => 'Urgente'],
    2 => ['class' => 'elderly', 'icon' => 'elderly', 'label' => 'Idoso'],
    3 => ['class' => 'priority', 'icon' => 'pregnant_woman', 'label' => 'Prioridade'],
    4 => ['class' => 'standard', 'icon' => 'person', 'label' => 'Normal'],
];
?>
<!DOCTYPE html>
<html class="light" lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Painel TV - Hospital Premium</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700;800&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- Link para a folha de cores global para referências no futuro -->
    <link rel="stylesheet" href="/hospital-bengo/public/css/colors.css?v=<?= time() ?>">
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        // Cores Centrais do Design System
                        "primary": "#0A58CA",
                        "on-primary": "#FFFFFF",
                        "background": "#F4F6F9", 
                        "surface": "#FFFFFF",
                        "surface-container-low": "#F1F5F9",
                        "on-surface": "#111827",
                        "on-surface-variant": "#6B7280",
                        "error": "#EF4444",
                        "inverse-surface": "#1C1E23",
                        "on-inverse-surface": "#FFFFFF",
                        
                        // Cores Semânticas de Fila
                        "urgent": "#EF4444",
                        "elderly": "#F59E0B",
                        "priority": "#8B5CF6",
                        "standard": "#0A58CA"
                    },
                    borderRadius: {
                        "DEFAULT": "1rem",
                        "lg": "1.5rem",
                        "xl": "2rem",
                        "2xl": "2.5rem",
                        "3xl": "3rem",
                        "full": "9999px"
                    },
                    fontFamily: {
                        "headline": ["Inter", "sans-serif"],
                        "body": ["Inter", "sans-serif"],
                        "mono": ["Space Mono", "monospace"]
                    },
                    boxShadow: {
                        'ambient': '0px 10px 30px rgba(0, 0, 0, 0.03)',
                        'ambient-lg': '0px 20px 50px rgba(0, 0, 0, 0.05)',
                        'inner-glow': 'inset 0px 2px 10px rgba(255, 255, 255, 0.5)'
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F4F6F9; color: #111827; overflow: hidden; } 
        h1, h2, h3, h4, h5, h6, .font-headline { font-family: 'Inter', sans-serif; } 
        .text-display-giant { font-size: 7.5rem; line-height: 1; letter-spacing: -0.05em; font-weight: 900; } 
        .text-display-md { font-size: 2.25rem; line-height: 1.1; letter-spacing: -0.04em; font-weight: 700; } 
        .text-title-lg { font-size: 1.15rem; line-height: 1.2; letter-spacing: -0.02em; font-weight: 700; } 
        .text-label-md { font-size: 0.8rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; } 
        .text-label-sm { font-size: 0.7rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; } 
        @keyframes pulse-soft { 
            0%, 100% { transform: scale(1); box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.03); } 
            50% { transform: scale(1.005); box-shadow: 0px 15px 35px rgba(0, 0, 0, 0.05); } 
        } 
        .animate-pulse-soft { animation: pulse-soft 4s infinite ease-in-out; } 
        .clean-panel { background: #ffffff; border-radius: 1.25rem; box-shadow: 0px 8px 30px rgba(0, 0, 0, 0.03); border: none; } 
        @keyframes marquee { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } } 
        .animate-marquee-container { display: flex; white-space: nowrap; overflow: hidden; width: 100%; } 
        .animate-marquee { animation: marquee 25s linear infinite; }
        
        /* Animação que é activada no JS via class na actualização para pulsar o cartao quando há chamada  */
        .chamada-pulsante { animation: flash-highlight 1.5s cubic-bezier(0.4, 0, 0.2, 1) forwards !important; }
        @keyframes flash-highlight {
            0%, 100% { transform: scale(1); filter: brightness(1); }
            15% { transform: scale(1.03); filter: brightness(0.95); box-shadow: 0 0 0 10px rgba(0,0,0,0.1); }
        }
    </style>
</head>

<body class="w-screen h-screen flex flex-col antialiased bg-background p-3 gap-3">

    <!-- Top Navigation Bar / Real-time info -->
    <header class="w-full flex justify-between items-center z-50 px-2 shrink-0">
        <div class="text-lg font-black text-primary uppercase tracking-widest font-headline">
            <?= htmlspecialchars(APP_NOME) ?>
        </div>
        <div class="flex items-center gap-3 font-headline font-bold text-title-lg text-primary">
            <div id="ultima-actualizacao" class="hidden">Atualizado</div> <!-- Referencia JS oculta -->
            <div class="flex items-center gap-2 bg-surface px-3 py-1.5 rounded-full shadow-ambient">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">schedule</span>
                <span class="font-mono tracking-tighter" id="relogio"><?= date('H:i') ?></span>
            </div>
            <div class="flex items-center gap-2 bg-surface px-3 py-1.5 rounded-full shadow-ambient">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">cloud</span>
                <span class="font-mono tracking-tighter">24°C</span>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 flex gap-3 min-h-0">
        
        <!-- Left Side: Active Call & Metrics -->
        <section class="zona-principal flex-[1.2] flex flex-col h-full gap-3 relative z-10 min-h-0">
            
            <!-- Active Ticket -->
            <div class="em-atendimento-card flex-1 clean-panel flex flex-col justify-center items-center text-center animate-pulse-soft overflow-hidden p-4 relative <?= $emChamada ? '' : 'opacity-80' ?>">
                <?php if ($emChamada): 
                    $nomePartes = explode(' ', trim($emChamada['paciente_nome'] ?? ''));
                    $nomeFormatado = $nomePartes[0];
                    if (count($nomePartes) > 1) {
                        $nomeFormatado .= ' ' . end($nomePartes);
                    }
                ?>
                    <div class="inline-flex bg-surface-container-low px-4 py-1.5 rounded-full mb-2 shrink-0">
                        <span class="text-label-sm text-on-surface-variant flex items-center gap-2">
                            <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">notifications_active</span>
                            CHAMADA ATUAL
                        </span>
                    </div>
                    
                    <h1 id="senha-actual" data-id="<?= htmlspecialchars($emChamada['codigo']) ?>" class="text-display-giant font-mono text-primary mb-1 leading-none shrink-0 tracking-tighter">
                        <?= htmlspecialchars($emChamada['codigo']) ?>
                    </h1>
                    
                    <div class="bg-surface-container-low rounded-2xl p-4 w-full max-w-xl shadow-ambient mt-2 shrink-0 flex flex-col items-center">
                        <p class="text-label-sm text-on-surface-variant mb-1">PACIENTE</p>
                        <h2 class="text-display-md text-on-surface font-headline mb-4 truncate w-full">
                            <?= htmlspecialchars($nomeFormatado) ?>
                        </h2>
                        <div class="bg-primary text-on-primary rounded-full py-3 px-8 inline-flex items-center gap-4 shadow-ambient-lg">
                            <span class="material-symbols-outlined text-3xl">meeting_room</span>
                            <div class="text-left">
                                <p class="text-[0.65rem] font-bold text-on-primary/80 mb-0.5 tracking-wider uppercase">DIRIJA-SE AO</p>
                                <h3 class="text-2xl font-headline font-bold">
                                    <?= htmlspecialchars($emChamada['consultorio'] ?? 'Gabinete') ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="material-symbols-outlined text-gray-300 text-[64px] mb-4">pause_circle</span>
                    <h1 class="text-5xl font-mono text-gray-300 mb-1 leading-none shrink-0 tracking-tighter">AGUARDE...</h1>
                    <p class="text-label-sm text-gray-400 mt-2">SISTEMA EM PAUSA</p>
                    <div id="senha-actual" class="hidden"></div>
                <?php endif; ?>
            </div>

            <!-- Bottom Metrics Bar -->
            <div class="h-16 shrink-0 clean-panel rounded-2xl flex justify-center items-center px-4 gap-8">
                <div class="flex items-center gap-3">
                    <div class="bg-surface-container-low p-3 rounded-xl">
                        <span class="material-symbols-outlined text-xl text-primary" style="font-variation-settings: 'FILL' 1;">groups</span>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-[0.65rem] font-bold text-on-surface-variant mb-0.5 tracking-wider uppercase">EM ESPERA</p>
                        <p class="text-xl font-headline font-bold text-on-surface flex items-baseline gap-1"><?= $emEspera ?> <span class="text-base font-normal text-on-surface-variant">pessoas</span></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-surface-container-low p-3 rounded-xl">
                        <span class="material-symbols-outlined text-xl text-primary" style="font-variation-settings: 'FILL' 1;">timer</span>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-[0.65rem] font-bold text-on-surface-variant mb-0.5 tracking-wider uppercase">TEMPO MÉDIO</p>
                        <p class="text-xl font-headline font-bold text-on-surface flex items-baseline gap-1"><?= $tempoMedio ?> <span class="text-base font-normal text-on-surface-variant">minutos</span></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Right Side: Queues & History -->
        <aside class="zona-grid flex-1 flex flex-col gap-3 h-full min-h-0">
            
            <!-- Up Next -->
            <div class="flex-[1.3] clean-panel relative overflow-hidden flex flex-col p-4 min-h-0 flex-1">
                <h3 class="text-label-sm text-on-surface-variant mb-3 flex items-center gap-2 shrink-0">
                    <span class="material-symbols-outlined text-base">queue</span>
                    PRÓXIMAS CHAMADAS
                </h3>
                <div class="flex flex-col gap-2 overflow-y-auto pr-1 pb-1">
                    <?php if (!empty($proximas)): ?>
                        <?php foreach ($proximas as $s): 
                            $tp = $pColors[$s['prioridade']] ?? $pColors[4];
                            $nomeP = explode(' ', trim($s['paciente_nome'] ?? 'Aguardando Utente'));
                            $nomeC = count($nomeP) > 1 ? $nomeP[0] . ' ' . end($nomeP) : $nomeP[0];
                        ?>
                            <!-- Queue Item -->
                            <div class="flex items-center justify-between bg-inverse-surface px-3 rounded-lg shadow-ambient relative overflow-hidden shrink-0 h-auto py-0.5 group">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-<?= $tp['class'] ?>/20 flex items-center justify-center text-<?= $tp['class'] ?>">
                                        <span class="material-symbols-outlined text-sm"><?= $tp['icon'] ?></span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-mono font-black text-base text-on-inverse-surface leading-tight"><?= htmlspecialchars($s['codigo']) ?></span>
                                        <span class="text-[0.7rem] text-gray-400 font-medium leading-none"><?= htmlspecialchars($nomeC) ?></span>
                                    </div>
                                </div>
                                <div class="text-right flex flex-col items-end gap-0">
                                    <span class="text-[0.5rem] font-bold text-<?= $tp['class'] ?> uppercase tracking-widest"><?= $tp['label'] ?></span>
                                    <span class="material-symbols-outlined text-gray-500 text-sm opacity-50">hourglass_empty</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="h-full flex items-center justify-center text-gray-400">
                            <span class="text-xs font-bold uppercase tracking-widest">A aguardar por senhas...</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- History / Absent -->
            <div class="flex-1 clean-panel p-4 flex flex-col min-h-0">
                <div class="flex justify-between items-center mb-3 shrink-0">
                    <h3 class="text-label-sm text-on-surface-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">history</span>
                        ÚLTIMAS CHAMADAS
                    </h3>
                    <h3 class="text-label-sm text-error/80 flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">person_off</span>
                        AUSENTES
                    </h3>
                </div>
                
                <div class="flex gap-2 h-full min-h-0">
                    
                    <!-- Last Called List -->
                    <div class="flex-1 flex flex-col gap-2 overflow-y-auto pr-1 pb-1">
                        <?php foreach ($concluidas as $s): ?>
                            <div class="flex items-center justify-between px-2 bg-inverse-surface rounded-md shadow-ambient shrink-0 h-auto py-0.5">
                                <span class="font-mono font-bold text-[0.8rem] text-on-inverse-surface"><?= htmlspecialchars($s['codigo']) ?></span>
                                <span class="text-[0.6rem] font-medium text-gray-400">Concluído</span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($concluidas)) echo '<span class="text-[0.6rem] text-gray-400 text-center py-2">Sem histórico</span>'; ?>
                    </div>
                    
                    <!-- Absent List -->
                    <div class="flex-1 flex flex-col gap-2 overflow-y-auto pr-1 pb-1">
                        <?php foreach ($canceladas as $s): ?>
                            <div class="flex items-center justify-between px-2 bg-inverse-surface rounded-md shadow-ambient shrink-0 h-auto py-0.5">
                                <span class="font-mono font-bold text-[0.8rem] text-on-inverse-surface"><?= htmlspecialchars($s['codigo']) ?></span>
                                <span class="text-error font-bold uppercase tracking-widest text-[0.45rem]">CANCELADO</span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($canceladas)) echo '<span class="text-[0.6rem] text-gray-400 text-center py-2">Sem ausências</span>'; ?>
                    </div>
                    
                </div>
            </div>

        </aside>
    </main>

    <!-- Ticker Footer: Avisos e Informações -->
    <footer class="w-full h-10 shrink-0 clean-panel rounded-full flex items-center px-4 gap-4 overflow-hidden">
        <div class="bg-primary text-on-primary px-4 py-1.5 rounded-full flex items-center gap-2 font-bold whitespace-nowrap shadow-ambient z-10">
            <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">info</span>
            <span class="text-[0.65rem] tracking-widest uppercase">Avisos e Informações</span>
        </div>
        <div class="animate-marquee-container flex-1">
            <div class="animate-marquee text-base font-medium text-on-surface-variant flex gap-12 items-center">
                <span>Bem-vindos ao Hospital Geral do Bengo.</span>
                <span class="material-symbols-outlined text-primary/30 text-xs">fiber_manual_record</span>
                <span>Por favor, mantenha o distanciamento adequado na sala de espera.</span>
                <span class="material-symbols-outlined text-primary/30 text-xs">fiber_manual_record</span>
                <span>Prepare a sua documentação enquanto aguarda a sua vez.</span>
                <span class="material-symbols-outlined text-primary/30 text-xs">fiber_manual_record</span>
                <span>Tenha atenção ao ecrã para acompanhar o seu número.</span>
            </div>
        </div>
    </footer>

    <!-- LÓGICA DE ATUALIZAÇÃO -->
    <script src="assets/js/painel.js"></script>
</body>
</html>