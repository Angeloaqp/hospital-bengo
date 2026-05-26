<?php
// ================================================
// Hospital Geral do Bengo
// Pesquisar Pacientes — Recepção
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Historico.php';

exigirPerfil(['recepcionista', 'admin']);

$termo = trim($_GET['q'] ?? '');
$resultados = [];
$historico = [];
$paciente = null;
$verPacId = (int) ($_GET['ver'] ?? 0);

// Pesquisa por nome
if (mb_strlen($termo) >= 2) {
    $resultados = Historico::pesquisarPaciente($termo);
}

// Ver histórico de um paciente
if ($verPacId > 0) {
    $paciente = Historico::obterPaciente($verPacId);
    $historico = Historico::historicoSenhas($verPacId);
}

$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

$estadoLabel = [
    'espera' => ['● Em espera', 'badge-idoso'],
    'chamada' => ['● Em chamada', 'badge-normal'],
    'concluida' => ['✓ Concluída', 'badge-concluido'],
    'cancelada' => ['✗ Cancelada', 'badge-cancelado'],
];

// Tipos de atendimento para rechamada
$db = Database::ligar();
$tipos = $db->query(
    "SELECT id, nome FROM tipos_atendimento
     WHERE activo = 1 ORDER BY nome"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisar Pacientes —
        <?= APP_NOME ?>
    </title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <!-- Estilos controlados de forma centralizada pelo Tailwind (ver header.php) -->
</head>

<body class="text-on-surface bg-[#f3f4f6]">

<?php $paginaActual = 'pesquisar'; ?>
<?php include __DIR__ . '/../comum/sidebar.php'; ?>

<?php $tituloPagina = 'Pesquisar Pacientes'; ?>
<?php include __DIR__ . '/../comum/header.php'; ?>

<div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
<main class="w-full">

    <!-- Hero Header Section -->
    <section class="mb-4 mt-2 flex items-center justify-between">
        <div>
            <h2 class="font-extrabold text-black tracking-tight mb-1 text-3xl">Base de Dados de Pacientes</h2>
            <p class="text-on-surface-variant font-semibold text-sm">Consulte o arquivo ou faça a admissão rápida.</p>
        </div>
        <a href="dashboard.php" class="bg-surface-container-low text-black px-5 py-2.5 rounded-full font-bold text-xs hover:bg-surface-container transition-colors no-underline">← Visão geral</a>
    </section>

    <?php if ($mensagem): ?>
        <div class="bg-green-50 text-green-700 px-5 py-4 rounded-2xl text-sm font-bold shadow-sm mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="bg-error-container text-error px-5 py-4 rounded-2xl text-sm font-bold shadow-sm mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined">warning</span>
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <!-- Sticky Search Section -->
    <section class="sticky z-40 top-[84px] pt-4 pb-4 -mt-4 mb-6 relative">
        <!-- Smooth gradient fade background blending to cover scrolling table content properly -->
        <div class="absolute inset-0 bg-[#f3f4f6] z-[-1]"></div>
        <div class="absolute inset-x-0 -bottom-4 h-4 bg-gradient-to-b from-[#f3f4f6] to-transparent z-[-1] pointer-events-none"></div>

        <form method="GET" class="relative bg-white rounded-[1.5rem] flex flex-col md:flex-row items-center gap-4 p-3 floating-card border border-white/50 shadow-sm hover:shadow-md transition-shadow">
            <div class="relative flex-1 w-full">
                <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-on-surface-variant text-xl">search</span>
                <input name="q" value="<?= htmlspecialchars($termo) ?>" minlength="2" class="w-full rounded-xl bg-surface-container-low border-none font-semibold placeholder:text-on-surface-variant/50 font-['Manrope'] pl-12 pr-6 py-3 text-sm focus:ring-2 focus:ring-black/10 transition-all outline-none" placeholder="Pesquise por Nome do Paciente (mín. 2 letras) ..." type="text" autofocus autocomplete="off" />
            </div>
            <button type="submit" class="bg-black text-white rounded-xl font-black flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98] transition-all shadow-lg h-[46px] px-6 text-sm shrink-0">
                <span class="material-symbols-outlined text-xl">search</span>
                Procurar
            </button>
        </form>
    </section>

    <!-- RESULTADOS DA PESQUISA -->
    <?php if ($termo && empty($resultados)): ?>
        <!-- Empty State (No matches) -->
        <section class="flex flex-col items-center justify-center text-center py-12">
            <div class="relative w-32 h-32 mb-6 flex items-center justify-center">
                <div class="absolute inset-0 bg-surface-container-high/40 rounded-full blur-3xl opacity-50"></div>
                <span class="material-symbols-outlined text-[80px] text-on-surface-variant/30" style="font-variation-settings: 'wght' 200;">search_off</span>
            </div>
            <h3 class="text-xl font-black text-black mb-2">Nenhum resultado encontrado</h3>
            <p class="max-w-md text-on-surface-variant font-semibold text-xs leading-relaxed">Não encontrámos nenhum paciente na base de dados para "<strong><?= htmlspecialchars($termo) ?></strong>". Recomendamos efetuar o registo através da janela <a href="registar.php" class="text-black font-bold underline underline-offset-2 hover:text-blue-600 transition-colors">Novo Paciente</a>.</p>
        </section>

    <?php elseif (!empty($resultados)): ?>
        <section class="mb-12">
            <div class="bg-white rounded-[2rem] floating-card overflow-hidden border border-white">
                <div class="px-8 py-5 border-b border-surface-container-low/50 flex justify-between items-center bg-surface-bright">
                    <span class="font-black text-xs uppercase tracking-widest text-black"><?= count($resultados) ?> resultado(s) de pesquisa</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-surface-container-low/30 border-b border-surface-container-low">
                                <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Nome Completo</th>
                                <th class="px-6 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em] whitespace-nowrap">Idade</th>
                                <th class="px-6 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Morada</th>
                                <th class="px-6 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em] hidden md:table-cell">Senhas</th>
                                <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em] text-right">Acções</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low/50">
                            <?php foreach ($resultados as $r): ?>
                            <tr class="hover:bg-surface-container-low/20 transition-colors group">
                                <td class="px-8 py-6">
                                    <p class="font-bold text-black text-sm"><?= htmlspecialchars($r['nome']) ?></p>
                                </td>
                                <td class="px-6 py-6">
                                    <p class="text-on-surface-variant text-sm font-semibold"><?= $r['idade'] ?> anos</p>
                                </td>
                                <td class="px-6 py-6">
                                    <p class="text-on-surface-variant text-sm font-semibold truncate max-w-[200px]" title="<?= htmlspecialchars($r['morada']) ?>"><?= htmlspecialchars($r['morada']) ?></p>
                                </td>
                                <td class="px-6 py-6 hidden md:table-cell">
                                    <span class="bg-surface-container-high/50 text-on-surface border border-surface-container-highest px-3 py-1.5 rounded-lg text-[10px] font-black"><?= $r['total_senhas'] ?> visitas</span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="pesquisar.php?ver=<?= $r['id'] ?>&q=<?= urlencode($termo) ?>#paciente-painel" class="flex items-center gap-2 border border-black/10 px-5 py-2.5 rounded-full font-black text-[11px] uppercase tracking-wider hover:bg-black hover:text-white transition-all active:scale-95 shadow-sm whitespace-nowrap">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                            Explorar Perfil
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    <?php elseif (!$paciente): ?>
        <!-- Default Empty State Before Exploring -->
        <section class="flex flex-col items-center justify-center text-center py-16">
            <div class="relative w-32 h-32 mb-6 flex items-center justify-center opacity-70">
                <div class="absolute inset-0 bg-surface-container-high/40 rounded-full blur-3xl opacity-50"></div>
                <span class="material-symbols-outlined text-[80px] text-on-surface-variant/20" style="font-variation-settings: 'wght' 200;">assignment_ind</span>
            </div>
            <h3 class="text-xl font-black text-black mb-2">Procurar Histórico de Pacientes</h3>
            <p class="max-w-md text-on-surface-variant font-semibold text-xs leading-relaxed">Utilize o campo de busca acima para encontrar rapidamente históricos clínicos ou preencher senhas de admissão para pacientes recorrentes.</p>
        </section>
    <?php endif; ?>

    <!-- HISTÓRICO E DETALHES DO PACIENTE SELECIONADO -->
    <?php if ($paciente): ?>
        <!-- Overlay Modal Effect -->
        <div id="paciente-modal" class="fixed inset-0 z-[100] flex justify-center items-center px-4 py-8 sm:p-8 bg-black/40 backdrop-blur-sm overflow-hidden opacity-0 transition-opacity duration-500 pointer-events-none">
            
            <div id="paciente-modal-content" class="w-full max-w-6xl grid grid-cols-1 xl:grid-cols-3 gap-6 items-start my-auto translate-y-8 transition-transform duration-500 relative opacity-0">
                
                <!-- Close Modal Button -->
                <a href="pesquisar.php?q=<?= urlencode($termo) ?>" class="absolute -top-14 right-0 bg-white/20 hover:bg-white/40 text-white hover:text-white rounded-full p-2 flex items-center justify-center transition-all backdrop-blur-md shadow-lg border border-white/30 hidden sm:flex">
                    <span class="material-symbols-outlined">close</span>
                </a>
                
                <div class="xl:col-span-2 mt-12 sm:mt-0 flex flex-col sm:max-h-[85vh]">
                    
                    <a href="pesquisar.php?q=<?= urlencode($termo) ?>" class="bg-white/20 hover:bg-white/40 text-white hover:text-white rounded-full px-4 py-2 inline-flex items-center gap-2 transition-all backdrop-blur-md shadow-lg border border-white/30 sm:hidden mb-4 font-bold text-xs w-fit">
                        <span class="material-symbols-outlined">arrow_back</span> Voltar
                    </a>
                <!-- Visão Geral Clínica Panel -->
                <section class="bg-white rounded-[2rem] p-8 floating-card border border-white flex flex-col flex-1 overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 pb-8 border-b border-surface-container-low gap-4">
                        <div>
                            <h3 class="text-2xl font-extrabold text-black tracking-tight mb-3"><?= htmlspecialchars($paciente['nome']) ?></h3>
                            <div class="flex items-center gap-3 text-xs font-semibold text-on-surface-variant flex-wrap">
                                <span class="bg-surface-container-low px-3 py-1.5 rounded-lg text-black font-bold"><?= $paciente['idade'] ?> anos</span>
                                <?php if ($paciente['peso']): ?>
                                    <span class="bg-surface-container-low px-3 py-1.5 rounded-lg font-bold"><?= $paciente['peso'] ?> kg</span>
                                <?php endif; ?>
                                <span class="bg-surface-container-low px-3 py-1.5 rounded-lg flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">location_on</span> <?= htmlspecialchars($paciente['morada']) ?></span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-widest mb-1">Paciente no Sistema desde:</p>
                            <p class="font-mono text-xs text-black font-bold"><?= date('d/m/Y \à\s H:i', strtotime($paciente['registado_em'])) ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-2xl bg-surface-container-low flex items-center justify-center">
                            <span class="material-symbols-outlined text-black">history</span>
                        </div>
                        <h4 class="text-lg font-black tracking-tight text-black">Histórico de Senhas <span class="text-on-surface-variant font-semibold text-sm ml-2">(<?= count($historico) ?>)</span></h4>
                    </div>
                    
                    <?php if (!empty($historico)): ?>
                        <div class="border border-surface-container-low rounded-3xl overflow-hidden overflow-y-auto flex-1 custom-scrollbar min-h-[200px]">
                            <table class="w-full text-left relative">
                                <thead class="bg-surface-container-low/95 backdrop-blur-md sticky top-0 z-10">
                                    <tr>
                                        <th class="px-6 py-5 font-black text-on-surface-variant text-[10px] uppercase tracking-[0.15em] border-b border-surface-container-low shadow-[0_1px_2px_rgba(0,0,0,0.02)]">Senha / Referência</th>
                                        <th class="px-6 py-5 font-black text-on-surface-variant text-[10px] uppercase tracking-[0.15em] border-b border-surface-container-low shadow-[0_1px_2px_rgba(0,0,0,0.02)]">Especialidade / Local</th>
                                        <th class="px-6 py-5 font-black text-on-surface-variant text-[10px] uppercase tracking-[0.15em] border-b border-surface-container-low shadow-[0_1px_2px_rgba(0,0,0,0.02)]">Estado</th>
                                        <th class="px-6 py-5 font-black text-on-surface-variant text-[10px] uppercase tracking-[0.15em] text-right border-b border-surface-container-low shadow-[0_1px_2px_rgba(0,0,0,0.02)]">Data Emissão</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-surface-container-low">
                                    <?php foreach ($historico as $h): 
                                        $est = $estadoLabel[$h['estado']] ?? ['—', ''];
                                        $isWaiting = ($h['estado'] === 'espera' || $h['estado'] === 'chamada');
                                        $isCanceled = ($h['estado'] === 'cancelada');
                                    ?>
                                        <tr class="hover:bg-surface-container-low/20 transition-colors">
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center gap-1.5 font-black font-mono text-black text-[13px] bg-white border border-surface-container-highest shadow-sm px-2.5 py-1 rounded-md">
                                                    <?= htmlspecialchars($h['codigo']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="font-bold text-black text-xs"><?= htmlspecialchars($h['tipo_atendimento']) ?></p>
                                                <?php if($h['medico_nome']): ?>
                                                    <p class="text-[10px] text-on-surface-variant flex items-center gap-1 mt-1 opacity-80"><span class="material-symbols-outlined text-[13px]">stethoscope</span> Dr/a <?= htmlspecialchars($h['medico_nome']) ?> &bull; <?= htmlspecialchars($h['consultorio']) ?></p>
                                                <?php endif ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($isWaiting): ?>
                                                    <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider shadow-sm"><span class="material-symbols-outlined text-[14px]">pending_actions</span> <?= $h['estado'] ?></span>
                                                <?php elseif ($h['estado'] === 'concluida'): ?>
                                                    <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 border border-green-200 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider shadow-sm"><span class="material-symbols-outlined text-[14px]">check_circle</span> Finalizado</span>
                                                <?php elseif ($isCanceled): ?>
                                                    <span class="inline-flex items-center gap-1.5 bg-error/5 text-error border border-error/20 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider shadow-sm"><span class="material-symbols-outlined text-[14px]">cancel</span> Cancelada</span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 bg-surface-container-high text-on-surface-variant border border-surface-container-highest px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider"><?= $h['estado'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="text-[11px] font-semibold text-on-surface-variant bg-surface-container-low px-2 py-1 rounded-md whitespace-nowrap">
                                                    <?= dataFormatoPT($h['criado_em'], 'curto') ?> • <?= date('H:i', strtotime($h['criado_em'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="bg-surface-container-low/50 rounded-[1.5rem] p-10 flex flex-col justify-center items-center text-center border border-dashed border-surface-container-highest">
                            <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-3">history_toggle_off</span>
                            <p class="text-sm font-bold text-black mb-1">Sem Histórico Encontrado</p>
                            <p class="text-xs font-semibold text-on-surface-variant">Este paciente ainda não passou por nenhum atendimento formal registado no HGB.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Admissão Expressa Lateral com AJAX -->
            <div class="xl:col-span-1 border-none relative flex flex-col justify-start">
                <div class="relative w-full h-auto">
                    
                    <!-- Painel Form: Admissão Rápida -->
                    <div id="quick-admission-panel" class="bg-white rounded-[2rem] p-6 floating-card border border-white transition-all duration-500 relative z-10 w-full opacity-100">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-on-surface-variant mb-4 flex items-center justify-center gap-2 text-center pb-4 border-b border-surface-container-low w-full">
                            <span class="material-symbols-outlined text-black" style="font-variation-settings: 'FILL' 1;">bolt</span> Admissão Rápida
                        </h4>
                        <p class="text-[11px] text-on-surface-variant font-medium leading-relaxed mb-6 text-center">Emita uma nova senha associada a este biótipo no sistema sem refazer o registo demográfico.</p>
                        
                        <form id="quick-admission-form" method="POST" action="<?= BASE_URL ?>app/controllers/pacientes.php" class="flex flex-col gap-5">
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="acao" value="rechamar">
                            <input type="hidden" name="paciente_id" value="<?= $paciente['id'] ?>">
                            <input type="hidden" name="ajax" value="1">
                            
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1">Para Onde Direcionar?</label>
                                <?php
                                $sel_id = 'cs-tipo-atd';
                                $sel_name = 'tipo_atendimento_id';
                                $sel_icon = 'local_hospital';
                                $sel_placeholder = 'Escolher setor especialista...';
                                $sel_value = '';
                                $sel_required = true;
                                $sel_size = 'sm';
                                $sel_options = [];
                                foreach ($tipos as $t) {
                                    $sel_options[(string)$t['id']] = ['label' => htmlspecialchars($t['nome']), 'icon' => 'medical_services', 'color' => 'text-blue-600'];
                                }
                                include __DIR__ . '/../comum/custom_select.php';
                                ?>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 ml-1">Nível de Prioridade</label>
                                <?php
                                $sel_id = 'cs-prioridade-pesq';
                                $sel_name = 'prioridade';
                                $sel_icon = 'check_circle';
                                $sel_placeholder = 'Fila Regular (Normal)';
                                $sel_value = '4';
                                $sel_required = true;
                                $sel_size = 'sm';
                                $sel_options = [
                                    '4' => ['label' => 'Fila Regular (Normal)', 'icon' => 'check_circle', 'color' => 'text-blue-600'],
                                    '1' => ['label' => 'Urgência Crítica', 'icon' => 'notification_important', 'color' => 'text-red-600'],
                                    '2' => ['label' => 'Idoso / Terceira Idade', 'icon' => 'elderly', 'color' => 'text-amber-500'],
                                    '3' => ['label' => 'Gestante / Maternidade', 'icon' => 'pregnant_woman', 'color' => 'text-purple-600'],
                                ];
                                include __DIR__ . '/../comum/custom_select.php';
                                ?>
                            </div>

                            <button type="submit" class="w-full mt-2 bg-black text-white rounded-2xl text-sm font-black tracking-tight py-4 hover:bg-zinc-800 active:scale-[0.98] transition-all flex flex-col justify-center items-center gap-1 shadow-lg group relative overflow-hidden">
                                <span class="btn-text flex flex-col items-center gap-1">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">print</span>
                                        <span>Emitir Nova Senha</span>
                                    </div>
                                    <span class="text-[9px] font-bold opacity-60 uppercase tracking-widest mt-1">Imprimir e Encaminhar</span>
                                </span>
                                <span class="btn-loader absolute inset-0 flex items-center justify-center opacity-0 transition-opacity bg-black pointer-events-none">
                                    <span class="material-symbols-outlined animate-spin text-white">autorenew</span>
                                </span>
                            </button>
                        </form>
                    </div>

                    <!-- Painel Sucesso (Oculto) -->
                    <div id="quick-success-panel" class="absolute top-0 left-0 w-full bg-white rounded-[2rem] p-8 floating-card border border-white opacity-0 pointer-events-none transition-all duration-500 translate-y-4 flex flex-col items-center justify-center text-center z-20 shadow-xl">
                        <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-green-500 text-3xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        </div>
                        <h4 class="text-xl font-black text-black mb-1">Senha Gerada</h4>
                        <p class="text-xs font-semibold text-on-surface-variant mb-5">Admissão registada no sistema.</p>
                        
                        <div class="w-full bg-surface-container-low rounded-2xl p-5 border border-surface-container-higher mb-6">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-on-surface-variant mb-2">Novo Ticket</p>
                            <p id="success-senha" class="text-3xl font-mono font-black text-black tracking-widest bg-white py-2 rounded-xl shadow-sm border border-black/5">---</p>
                        </div>
                        
                        <button type="button" onclick="resetQuickAdmission()" class="bg-surface-container-low hover:bg-surface-container text-black rounded-xl font-bold text-xs py-3.5 px-6 transition-colors w-full border border-surface-container mb-3 active:scale-[0.98]">
                            &larr; Nova Admissão
                        </button>
                        <a href="pesquisar.php?ver=<?= $paciente['id'] ?>&q=<?= urlencode($termo) ?>#paciente-painel" class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant hover:text-black transition-colors underline underline-offset-4">
                            Refresh ao Histórico
                        </a>
                    </div>
                    
                </div>
            </div>

            <!-- Scripts for AJAX Submission -->
            <script>
            // Modal Entry Animation
            document.addEventListener("DOMContentLoaded", () => {
                const modal = document.getElementById("paciente-modal");
                const content = document.getElementById("paciente-modal-content");
                if(modal && content) {
                    // Start Animation shortly after load
                    setTimeout(() => {
                        modal.classList.remove("opacity-0", "pointer-events-none");
                        modal.classList.add("opacity-100");
                        content.classList.remove("translate-y-8", "opacity-0");
                        content.classList.add("translate-y-0", "opacity-100");
                    }, 50);
                }
            });

            document.getElementById('quick-admission-form')?.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const form = this;
                const btnText = form.querySelector('.btn-text');
                const btnLoader = form.querySelector('.btn-loader');
                const submitBtn = form.querySelector('button[type="submit"]');

                submitBtn.disabled = true;
                btnText.classList.add('opacity-0');
                btnLoader.classList.remove('opacity-0');

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'sucesso') {
                        document.getElementById('success-senha').textContent = data.codigo;
                        
                        const panel = document.getElementById('quick-admission-panel');
                        const success = document.getElementById('quick-success-panel');
                        
                        panel.style.opacity = '0';
                        panel.style.pointerEvents = 'none';
                        panel.style.transform = 'scale(0.95)';
                        panel.style.position = 'absolute';
                        
                        success.style.position = 'relative';
                        success.style.opacity = '1';
                        success.style.pointerEvents = 'auto';
                        success.style.transform = 'translateY(0)';
                    } else {
                        alert(data.mensagem || 'Ocorreu um erro.');
                        resetLoadingState();
                    }
                })
                .catch(error => {
                    alert('Erro na comunicação com o servidor.');
                    resetLoadingState();
                });

                function resetLoadingState() {
                    submitBtn.disabled = false;
                    btnText.classList.remove('opacity-0');
                    btnLoader.classList.add('opacity-0');
                }
            });

            function resetQuickAdmission() {
                const panel = document.getElementById('quick-admission-panel');
                const success = document.getElementById('quick-success-panel');
                const btnText = panel.querySelector('.btn-text');
                const btnLoader = panel.querySelector('.btn-loader');
                const submitBtn = panel.querySelector('button[type="submit"]');
                
                success.style.opacity = '0';
                success.style.pointerEvents = 'none';
                success.style.transform = 'translateY(4px)';
                success.style.position = 'absolute';
                
                panel.style.position = 'relative';
                panel.style.opacity = '1';
                panel.style.pointerEvents = 'auto';
                panel.style.transform = 'scale(1)';
                
                submitBtn.disabled = false;
                btnText.classList.remove('opacity-0');
                btnLoader.classList.add('opacity-0');
                
                document.getElementById('quick-admission-form').reset();
            }
            </script>
        </div>
    <?php endif; ?>

</main>
</main>
</div>

</body>

</html>