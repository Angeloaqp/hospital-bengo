<?php
// ================================================
// Hospital Geral do Bengo
// Dashboard da Recepcionista — Fila em tempo real
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Senha.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';
$meuPerfilObject = Utilizador::obter((int) sessao('utilizador_id'));

// Só recepcionistas e admins podem aceder
exigirPerfil(['recepcionista', 'admin']);

// Carrega dados para as métricas
$emEspera = Senha::contarPorEstado('espera');
$urgentes = Senha::contarUrgentes();
$atendidos = Senha::atendidosHoje();
$tempoMedio = Senha::tempoMedioEspera();
$filaEspera = Senha::filaEspera();
$fluxoGrafico = Senha::fluxoHorario();
$ultimaChamada = Senha::emChamadaAgora();

// Mapa de prioridades
$prioridades = [
    1 => ['label' => 'Urgente',  'badge' => 'bg-error'],
    2 => ['label' => 'Idoso',    'badge' => 'bg-[#F59E0B]'],
    3 => ['label' => 'Grávida',  'badge' => 'bg-purple-600'],
    4 => ['label' => 'Normal',   'badge' => 'bg-blue-600'],
];

// Mensagem flash (após registo de paciente)
$mensagem = $_SESSION['mensagem'] ?? '';
$ultimaSenha = $_SESSION['ultima_senha'] ?? '';
$ultimoProcesso = $_SESSION['ultimo_processo'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['ultima_senha'], $_SESSION['ultimo_processo']);
?>
<!DOCTYPE html>
<html lang="pt"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Dashboard Recepção — <?= APP_NOME ?></title>
<?php include __DIR__ . '/../comum/head_assets.php'; ?>
<script src="<?= BASE_URL ?>public/assets/js/chart.js"></script>
<style>
    .floating-card {
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 10px -2px rgba(0, 0, 0, 0.03);
    }
    
    @media print {
        body * {
            visibility: hidden;
        }
        #print-ticket, #print-ticket * {
            visibility: visible;
        }
        #print-ticket {
            position: absolute;
            left: 0;
            top: 0;
            width: 300px;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        /* Hide all UI elements including modal backgrounds */
        .no-print {
            display: none !important;
        }
    }
</style>
</head>
<body class="text-on-surface bg-surface-container-low">

<?php
// ── SIDEBAR ──
$paginaActual = 'dashboard';
include __DIR__ . '/../comum/sidebar.php';
?>

<?php
// ── HEADER ──
$tituloPagina = 'Dashboard';
$subtituloPagina = '';
$accoesPagina = '';
include __DIR__ . '/../comum/header.php';
?>


<!-- Dados Ocultos para o Polling AJAX ler -->
<div id="memoria-chamada" style="display:none" data-codigo="<?= $ultimaChamada ? $ultimaChamada['codigo'] : '' ?>"
    data-paciente="<?= $ultimaChamada ? explode(' ', $ultimaChamada['paciente_nome'])[0] : '' ?>">
</div>

<?php if ($ultimaSenha): ?>
<!-- TICKET DE IMPRESSÃO (MODAL) -->
<div id="ticket-modal" class="fixed inset-0 z-[100] flex justify-center items-center bg-primary/60 backdrop-blur-sm no-print">
    <div class="bg-white rounded-3xl p-8 max-w-sm w-full mx-4 shadow-2xl flex flex-col items-center">
        <h3 class="font-black text-xl mb-6 tracking-tight">Imprimir Senha</h3>
        
        <!-- Ticket Físico Renderizado -->
        <div id="print-ticket" class="w-full border-2 border-primary border-dashed rounded-xl p-6 flex flex-col items-center justify-center bg-white mb-8">
            <h4 class="font-extrabold text-lg mb-1"><?= APP_NOME ?></h4>
            <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-6">Recepção Principal</p>
            
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-on-surface-variant mb-1">Senha de Atendimento</p>
            <p class="text-5xl font-mono font-black text-black mb-4"><?= htmlspecialchars($ultimaSenha) ?></p>
            
            <?php if ($ultimoProcesso): ?>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-on-surface-variant mb-1 mt-2">Nº Processo (Vitalício)</p>
            <p class="text-lg font-black text-primary bg-surface-container px-3 py-1 rounded-md mb-2"><?= htmlspecialchars($ultimoProcesso) ?></p>
            <?php endif; ?>
            
            <div class="w-full border-t border-primary/10 my-4"></div>
            <p class="text-[9px] font-bold text-on-surface-variant text-center">Data: <?= date('d/m/Y H:i') ?></p>
            <p class="text-[9px] font-bold text-on-surface-variant text-center mt-1">Aguarde ser chamado no painel.</p>
        </div>

        <div class="flex gap-3 w-full">
            <button onclick="document.getElementById('ticket-modal').style.display='none'" class="flex-1 bg-surface-container-low text-black py-3 rounded-xl font-bold text-sm hover:bg-surface-container transition-colors">Fechar</button>
            <button onclick="window.print()" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold text-sm hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-2 shadow-lg">
                <span class="material-symbols-outlined text-[18px]">print</span>
                Imprimir
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main Content Area -->
<div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">

    <!-- Welcome Header -->
    <div class="mb-6">
        <h2 class="text-3xl font-extrabold text-black tracking-tight">Visão Geral do Dia</h2>
        <p class="text-on-surface-variant font-semibold mt-1 text-sm"><?= dataFormatoPT() ?> — Recepção Principal</p>
    </div>

    <!-- Hidden Initial Alerts Data for ux.js -->
    <div id="alertas-iniciais" style="display:none;" 
        data-mensagem="<?= htmlspecialchars($mensagem) ?>"
        data-pico="<?= $emEspera >= 15 ? 'true' : 'false' ?>"
        data-urgentes="<?= $urgentes > 0 ? $urgentes : '0' ?>"
        data-pico-desc="<?= $emEspera ?> pacientes em espera."
    ></div>
    <!-- Metrics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white px-6 py-5 rounded-[1.5rem] floating-card border border-white fade-in-delay-1">
            <p class="text-on-surface-variant font-bold uppercase tracking-widest text-[10px]">Em espera</p>
            <p class="text-4xl font-extrabold text-black mt-2"><?= $emEspera ?></p>
        </div>
        <div class="bg-white px-6 py-5 rounded-[1.5rem] floating-card border border-white fade-in-delay-2">
            <p class="text-error font-black uppercase tracking-widest text-[10px]">Urgentes</p>
            <p class="text-4xl font-extrabold text-error mt-2"><?= $urgentes ?></p>
        </div>
        <div class="bg-white px-6 py-5 rounded-[1.5rem] floating-card border border-white fade-in-delay-3">
            <p class="text-[#10B981] font-black uppercase tracking-widest text-[10px]">Atendidos hoje</p>
            <p class="text-4xl font-extrabold text-black mt-2"><?= $atendidos ?></p>
        </div>
        <div class="bg-white px-6 py-5 rounded-[1.5rem] floating-card border border-white fade-in-delay-4">
            <p class="text-on-surface-variant font-bold uppercase tracking-widest text-[10px]">Tempo médio</p>
            <div class="flex items-baseline gap-1 mt-2">
                <span class="text-4xl font-extrabold text-black"><?= $tempoMedio > 0 ? $tempoMedio : '--' ?></span>
                <span class="text-lg font-black text-on-surface-variant">m</span>
            </div>
        </div>
    </div>

    <!-- Main Layout Columns -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- Coluna Principal (Fila de Espera) -->
        <div class="xl:col-span-3 fade-in-delay-1">
            <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-black tracking-tight">Fila de Espera Atual</h3>
                    <a href="registar.php" class="bg-primary text-white px-6 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 hover:scale-[1.02] transition-transform shadow-md no-underline">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Novo Atendimento
                    </a>
                </div>

                <?php if (empty($filaEspera)): ?>
                    <div class="text-center py-12 text-on-surface-variant font-semibold">
                        Nenhum paciente em espera de momento.
                    </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b border-surface-container-low">
                            <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.15em]">
                                <th class="pb-4">Senha</th>
                                <th class="pb-4">Nome do Paciente</th>
                                <th class="pb-4 text-center">Prioridade</th>
                                <th class="pb-4 text-right">Chegada</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low/50">
                            <?php
                            $visíveis = array_slice($filaEspera, 0, 8);
                            $restantes = count($filaEspera) - count($visíveis);
                            foreach ($visíveis as $s):
                                $p = $prioridades[$s['prioridade']];
                                $hora = date('H:i', strtotime($s['criado_em']));
                                $isUrgente = ($s['prioridade'] == 1);
                            ?>
                            <tr class="group hover:bg-surface-container-low/30 transition-colors">
                                <td class="py-4 font-black <?= $isUrgente ? 'text-error' : 'text-black' ?> text-base"><?= htmlspecialchars($s['codigo']) ?></td>
                                <td class="py-4 font-bold text-black text-sm"><?= htmlspecialchars($s['paciente_nome']) ?></td>
                                <td class="py-4 text-center">
                                    <span class="px-3 py-1 <?= $p['badge'] ?> text-white text-[9px] font-black rounded-full"><?= strtoupper($p['label']) ?></span>
                                </td>
                                <td class="py-4 text-right text-sm font-bold text-black"><?= $hora ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($restantes > 0): ?>
                    <div class="text-center pt-6 text-on-surface-variant font-semibold text-xs">
                        + <?= $restantes ?> pacientes a aguardar atendimento
                    </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna Lateral (Widgets) -->
        <div class="space-y-6 fade-in-delay-2">
            <!-- Fluxo Widget -->
            <div class="bg-white rounded-[1.5rem] p-6 floating-card border border-white">
                <h3 class="font-black mb-6 text-[10px] uppercase tracking-[0.2em] flex items-center gap-2 text-on-surface-variant">
                    <span class="material-symbols-outlined text-black text-[18px]">trending_up</span>
                    Fluxo de Entradas
                </h3>
                <div class="h-32 flex items-end gap-2 justify-between px-1">
                    <?php
                    // fluxoGrafico = ['labels' => [...], 'data' => [...]]
                    $fluxoLabels = $fluxoGrafico['labels'] ?? [];
                    $fluxoData   = $fluxoGrafico['data'] ?? [];
                    // Pegar as últimas 6 horas
                    $fluxoLabels = array_slice($fluxoLabels, -6);
                    $fluxoData   = array_slice($fluxoData, -6);
                    $maxFluxo    = max(array_merge($fluxoData, [1]));
                    $totalBars   = count($fluxoData);
                    foreach ($fluxoData as $i => $qtd):
                        $pct = ($qtd / $maxFluxo) * 100;
                        $pct = max($pct, 8); // mínimo visual
                        $isHighest = ($qtd === $maxFluxo && $qtd > 0);
                    ?>
                        <div class="w-full <?= $isHighest ? 'bg-primary shadow-md' : 'bg-surface-container-low' ?> rounded-lg" style="height: <?= $pct ?>%"></div>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-between mt-4 text-[9px] font-black text-on-surface-variant tracking-wider">
                    <?php if (count($fluxoLabels) >= 2): ?>
                    <span><?= $fluxoLabels[0] ?></span>
                    <span><?= end($fluxoLabels) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dica Widget -->
            <div class="bg-primary text-white rounded-[1.5rem] p-6 floating-card relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-yellow-400 text-[18px]" style="font-variation-settings: 'FILL' 1;">lightbulb</span>
                        <h3 class="font-black text-[10px] uppercase tracking-[0.2em]">Dica de Produtividade</h3>
                    </div>
                    <p class="text-[11px] font-semibold leading-relaxed opacity-90">
                        Ao triar prioritários, verifique leitos livres para reduzir esperas no corredor. Use o atalho <b>Paciente Frequente</b> para registos rápidos.
                    </p>
                </div>
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/5 rounded-full blur-2xl"></div>
            </div>
        </div>
    </div>

</main>
</div>

<!-- Hidden chart canvas for AJAX polling compatibility -->
<div style="display:none">
    <canvas id="graficoFluxo"></canvas>
</div>

<script>
    const DADOS_FLUXO = <?= json_encode($fluxoGrafico) ?>;
</script>
<script src="<?= BASE_URL ?>public/assets/js/fila.js"></script>

</body></html>
