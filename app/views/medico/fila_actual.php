<?php
// ================================================
// Hospital Geral do Bengo
// Médico — Fila Actual (Tactile)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Senha.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';
require_once __DIR__ . '/../../../app/models/Prontuario.php';

exigirPerfil(['medico', 'admin']);

$medicoId = (int) sessao('utilizador_id');
$meuPerfilObject = Utilizador::obter($medicoId);
$especialidade = Senha::especialidadeDoMedico($medicoId);
$proxima = Senha::proximaDoMedico($medicoId);
$emAtend = Senha::emAtendimento($medicoId);
$filaEspera = Senha::filaDoMedico($medicoId);
$consultorio = Senha::consultorioDoMedicoV2($medicoId);
$urgentes = Senha::contarUrgentes();
$emEspera = Senha::contarEsperaDoMedico($medicoId);

// Prontuário Clínico — carregar dados se em atendimento
$prontuarioAtual = null;
$historicoPaciente = [];
$dadosPaciente = null;
if ($emAtend) {
    $prontuarioAtual = Prontuario::obterPorSenha($emAtend['id']);
    $pacId = Prontuario::pacienteDaSenha($emAtend['id']);
    if ($pacId) {
        $historicoPaciente = Prontuario::historicoPaciente($pacId, 10);
        $dadosPaciente = Prontuario::dadosPaciente($pacId);
    }
}

// Verifica janela de desfazer (15 segundos)
$podeDesfazer = false;
$ultimaChamada = $_SESSION['ultima_chamada'] ?? 0;
$chamadaTs = $_SESSION['chamada_ts'] ?? 0;
$restoUndo = 0;
if ($ultimaChamada && $chamadaTs) {
    $segundos = time() - $chamadaTs;
    $podeDesfazer = $segundos <= 15;
    $restoUndo = max(0, 15 - $segundos);
}

// Prioridades
$prioridades = [
    1 => ['label' => 'Urgente', 'color' => 'bg-error text-white', 'text' => 'text-error', 'bg' => 'bg-error/10', 'border' => 'border-error'],
    2 => ['label' => 'Idoso', 'color' => 'bg-orange-500 text-white', 'text' => 'text-orange-700', 'bg' => 'bg-orange-100', 'border' => 'border-orange-500'],
    3 => ['label' => 'Grávida', 'color' => 'bg-purple-600 text-white', 'text' => 'text-purple-700', 'bg' => 'bg-purple-100', 'border' => 'border-purple-600'],
    4 => ['label' => 'Normal', 'color' => 'bg-blue-600 text-white', 'text' => 'text-blue-600', 'bg' => 'bg-blue-50', 'border' => 'border-blue-500'],
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fila Actual — Médico — <?= APP_NOME ?></title>
    <meta http-equiv="refresh" content="20">
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
        .tactile-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-weight: 800; letter-spacing: -0.05em; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        .fade-in-delay-1 { animation: fadeIn 0.5s ease-out 0.08s forwards; opacity: 0; }
        .fade-in-delay-2 { animation: fadeIn 0.5s ease-out 0.16s forwards; opacity: 0; }
        .fade-in-delay-3 { animation: fadeIn 0.5s ease-out 0.24s forwards; opacity: 0; }
        .success-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        .btn-press:active { transform: scale(0.98) translateY(1px); }
        .crossfade-out { opacity: 0; transition: opacity 0.35s ease-out; }
        .crossfade-in { opacity: 0; animation: fadeIn 0.5s ease-out 0.1s forwards; }
    </style>
</head>
<body class="text-on-surface h-screen overflow-hidden bg-[#f3f4f6]">

    <?php $paginaActual = 'fila_actual'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <!-- Especialidade e Consultório no Header -->
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

    <?php $tituloPagina = 'Dashboard — Fila Actual'; ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-56 mt-28 h-[calc(100vh-7rem)] flex flex-col">
        <!-- Scrollable Content Area -->
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <main class="w-full max-w-[1500px] mx-auto px-8 pb-24 pt-4">
                
                <!-- Page Title Section -->
                <header class="mb-8 flex items-end justify-between fade-in">
                    <div>
                        <h2 class="text-4xl font-headline font-extrabold tracking-tighter mb-2">Fila de Atendimento</h2>
                        <div class="flex items-center gap-3 text-on-surface-variant text-sm font-semibold">
                            <span>Dr. <?= htmlspecialchars($_primeiroNome ?? explode(' ', $meuPerfilObject['nome'])[0]) ?></span>
                            <span class="w-1 h-1 bg-surface-container-highest rounded-full"></span>
                            <span><?= $especialidade ? htmlspecialchars($especialidade['nome']) : 'Clínica Geral' ?></span>
                            <span class="w-1 h-1 bg-surface-container-highest rounded-full"></span>
                            <span class="text-primary font-bold"><?= $emEspera ?> pacientes em espera</span>
                        </div>
                    </div>
                </header>

                <!-- Urgency Alert Banner -->
                <?php if ($urgentes > 0): ?>
                    <div class="mb-8 p-4 bg-error/5 rounded-2xl flex items-center gap-4 border border-error/10">
                        <div class="bg-error w-10 h-10 rounded-full flex items-center justify-center text-white shrink-0 shadow-lg shadow-error/20">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">bolt</span>
                        </div>
                        <div>
                            <p class="font-headline font-bold text-error tracking-tight">Tem <?= $urgentes ?> urgência(s) activa(s) na sua fila.</p>
                            <p class="text-xs text-error/80 font-medium">Priorize o atendimento de segurança.</p>
                        </div>
                    </div>
                <?php endif; ?>



                <!-- Bento Grid for Attendance -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12 fade-in-delay-1">
                    
                    <!-- MODO CONSULTA — Prontuário Clínico -->
                    <?php if ($emAtend): ?>
                        <section class="bg-white rounded-[2rem] floating-card border border-white relative overflow-hidden min-h-[420px] transition-all duration-500 flex flex-col lg:col-span-2" id="atendimento-card">
                            <div class="h-full flex flex-col lg:flex-row" id="atendimento-content">
                                <!-- Painel Principal: Dados + Formulário -->
                                <div class="flex-1 p-8 flex flex-col">
                                    <!-- Header do paciente -->
                                    <div class="flex justify-between items-start mb-6">
                                        <div>
                                            <span class="px-3 py-1 bg-green-50 text-green-700 rounded-full text-[10px] font-black uppercase tracking-widest border border-green-100 inline-flex items-center gap-1.5">
                                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                                Modo Consulta
                                            </span>
                                            <div class="flex items-end gap-4 mt-4">
                                                <h3 class="text-5xl tactile-mono text-primary"><?= htmlspecialchars($emAtend['codigo']) ?></h3>
                                                <div class="mb-1">
                                                    <p class="text-2xl font-headline font-extrabold tracking-tight"><?= htmlspecialchars($emAtend['paciente_nome']) ?></p>
                                                    <p class="text-on-surface-variant font-semibold text-sm">
                                                        <?= htmlspecialchars($emAtend['tipo_atendimento']) ?>
                                                        <?= $emAtend['consultorio'] ? ' — ' . htmlspecialchars($emAtend['consultorio']) : '' ?>
                                                        <?php if ($dadosPaciente && isset($dadosPaciente['idade'])): ?>
                                                            — <?= (int)$dadosPaciente['idade'] ?> anos
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Formulário de Prontuário -->
                                    <form method="POST" action="<?= BASE_URL ?>app/controllers/prontuario.php" class="flex-1 flex flex-col gap-4" id="form-prontuario">
                                        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                        <input type="hidden" name="senha_id" value="<?= $emAtend['id'] ?>">
                                        <input type="hidden" name="prontuario_id" value="<?= $prontuarioAtual['id'] ?? 0 ?>">

                                        <!-- Diagnóstico -->
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1.5 block">Diagnóstico</label>
                                            <input type="text" name="diagnostico" placeholder="Ex: Malária, Hipertensão..." value="<?= htmlspecialchars($prontuarioAtual['diagnostico'] ?? '') ?>" class="w-full px-4 py-3 bg-surface-container-low rounded-xl border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/10 text-sm font-semibold transition-all outline-none placeholder:text-gray-300">
                                        </div>

                                        <!-- Notas Clínicas -->
                                        <div class="flex-1 flex flex-col">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1.5 block">Notas Clínicas</label>
                                            <textarea name="notas_clinicas" rows="4" placeholder="Observações da consulta, exame físico, queixas..." class="flex-1 w-full px-4 py-3 bg-surface-container-low rounded-xl border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/10 text-sm font-medium transition-all outline-none resize-none placeholder:text-gray-300"><?= htmlspecialchars($prontuarioAtual['notas_clinicas'] ?? '') ?></textarea>
                                        </div>

                                        <!-- Prescrição -->
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-1.5 block">Prescrição / Receita</label>
                                            <textarea name="prescricao" rows="3" placeholder="Medicamentos, dosagem, instruções..." class="w-full px-4 py-3 bg-surface-container-low rounded-xl border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/10 text-sm font-medium transition-all outline-none resize-none placeholder:text-gray-300"><?= htmlspecialchars($prontuarioAtual['prescricao'] ?? '') ?></textarea>
                                        </div>

                                        <!-- Botões de Acção -->
                                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                            <button type="submit" name="acao" value="guardar_prontuario" class="bg-surface-container-low text-on-surface-variant py-4 px-6 rounded-2xl font-black text-sm btn-press shadow hover:bg-surface-container-high transition-all flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined text-[18px]">save</span>
                                                Guardar Rascunho
                                            </button>
                                            <button type="submit" name="acao" value="concluir_com_prontuario" class="flex-1 bg-black text-white py-4 px-6 rounded-2xl font-black text-sm btn-press shadow hover:bg-neutral-800 transition-all flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined text-[18px]">task_alt</span>
                                                Concluir Consulta
                                            </button>
                                            <button type="button" onclick="handleCancelar(this, <?= $emAtend['id'] ?>, '<?= addslashes(htmlspecialchars($emAtend['paciente_nome'])) ?>')" class="bg-error/5 text-error py-4 px-6 rounded-2xl font-black text-sm btn-press hover:bg-error/10 transition-all flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined text-[18px]">person_off</span>
                                                Ausente
                                            </button>
                                        </div>
                                    </form>

                                    <?php if ($podeDesfazer): ?>
                                        <div class="mt-4 pt-4 border-t border-surface-container-low flex justify-between items-center gap-4 transition-all duration-500" id="undo-inline">
                                            <div class="flex flex-col flex-1 w-full relative z-10">
                                                <span class="text-xs font-bold text-on-surface-variant flex items-center gap-1.5 mb-2">
                                                    <span class="material-symbols-outlined text-[16px] text-green-500">check_circle</span>
                                                    Paciente chamado com sucesso
                                                </span>
                                                <div class="h-1 bg-surface-container-highest rounded-full overflow-hidden w-full max-w-[220px]">
                                                    <div class="h-full bg-blue-500 rounded-full transition-all duration-500 ease-linear" id="undo-fill-inline" style="width:<?= ($restoUndo / 15) * 100 ?>%"></div>
                                                </div>
                                            </div>
                                            <button onclick="handleDesfazer(<?= $ultimaChamada ?>)" class="text-blue-600 bg-blue-50 hover:bg-blue-100 px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all active:scale-95 border border-blue-100 whitespace-nowrap relative z-10">
                                                <span class="material-symbols-outlined text-[16px]">undo</span> Desfazer
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Timeline Lateral: Histórico do Paciente -->
                                <div class="w-full lg:w-80 bg-surface-container-low/30 border-t lg:border-t-0 lg:border-l border-surface-container-low p-6 overflow-y-auto custom-scrollbar" style="max-height:600px;">
                                    <div class="flex items-center gap-2 mb-5">
                                        <span class="material-symbols-outlined text-[18px] text-on-surface-variant">history</span>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Histórico (<?= count($historicoPaciente) ?>)</span>
                                    </div>

                                    <?php if (empty($historicoPaciente)): ?>
                                        <div class="text-center py-8">
                                            <span class="material-symbols-outlined text-3xl text-surface-container-highest mb-2 block">note_stack</span>
                                            <p class="text-xs text-on-surface-variant font-semibold">Primeira consulta deste paciente.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="space-y-4">
                                            <?php foreach ($historicoPaciente as $h): ?>
                                                <div class="bg-white rounded-xl p-4 border border-white shadow-sm">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant"><?= date('d/m/Y', strtotime($h['criado_em'])) ?></span>
                                                        <span class="text-[9px] font-bold text-primary"><?= htmlspecialchars($h['medico_nome']) ?></span>
                                                    </div>
                                                    <?php if ($h['diagnostico']): ?>
                                                        <p class="text-xs font-extrabold text-black mb-1"><?= htmlspecialchars($h['diagnostico']) ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($h['notas_clinicas']): ?>
                                                        <p class="text-[11px] text-on-surface-variant font-medium line-clamp-3"><?= nl2br(htmlspecialchars(mb_substr($h['notas_clinicas'], 0, 150))) ?><?= mb_strlen($h['notas_clinicas']) > 150 ? '…' : '' ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($h['prescricao']): ?>
                                                        <div class="mt-2 pt-2 border-t border-surface-container-low">
                                                            <span class="text-[9px] font-black uppercase tracking-widest text-blue-600">Rx</span>
                                                            <p class="text-[11px] text-on-surface-variant font-medium line-clamp-2"><?= nl2br(htmlspecialchars(mb_substr($h['prescricao'], 0, 100))) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="absolute -bottom-12 -right-12 w-48 h-48 bg-surface-container-low rounded-full opacity-30 pointer-events-none"></div>
                        </section>
                    <?php else: ?>
                        <section class="bg-white rounded-[2rem] p-8 floating-card border border-white relative overflow-hidden group min-h-[420px] flex items-center justify-center transition-all duration-700" id="atendimento-card">
                            <div class="text-center" id="atendimento-content">
                                <span class="material-symbols-outlined text-6xl text-surface-container-highest mb-4 block scale-100 transition-transform">check_circle</span>
                                <p class="text-on-surface-variant font-bold text-lg">Sem atendimento em progresso.</p>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Próximo Paciente Card -->
                    <?php if ($proxima): 
                        $pP = $prioridades[$proxima['prioridade']] ?? $prioridades[4];
                    ?>
                        <section class="bg-surface-container-low/50 rounded-[2rem] p-8 flex flex-col justify-between border-2 border-dashed <?= $pP['border'] ?> relative overflow-hidden group min-h-[420px] transition-all duration-500" id="proximo-card">
                            <div class="h-full flex flex-col justify-between" id="proximo-content">
                                <div>
                                    <span class="px-3 py-1 bg-white rounded-full text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Próximo Paciente</span>
                                    <div class="mt-8 flex items-end gap-6">
                                        <span class="text-5xl tactile-mono text-zinc-400"><?= htmlspecialchars($proxima['codigo']) ?></span>
                                        <div class="mb-1">
                                            <p class="text-2xl font-headline font-extrabold tracking-tight"><?= htmlspecialchars($proxima['paciente_nome']) ?></p>
                                            <p class="text-sm <?= $pP['text'] ?> font-bold uppercase tracking-wider"><?= $pP['label'] ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!$emAtend): ?>
                                    <button class="w-full bg-white text-black py-6 px-6 rounded-2xl font-black text-xl flex items-center justify-center gap-3 border border-surface-container-highest btn-press shadow hover:bg-black hover:text-white transition-all group relative overflow-hidden" onclick="handleChamar(this, <?= $proxima['id'] ?>, '<?= addslashes(htmlspecialchars($proxima['paciente_nome'])) ?>', '<?= htmlspecialchars($proxima['codigo']) ?>')">
                                        <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform" id="chamar-icon">volume_up</span>
                                        <span id="chamar-text">Chamar Próximo</span>
                                    </button>
                                <?php else: ?>
                                    <button disabled class="w-full bg-surface-container-low text-on-surface-variant py-6 px-6 rounded-2xl font-black text-lg flex items-center justify-center gap-3 border border-surface-container-highest cursor-not-allowed opacity-75 relative overflow-hidden active:scale-100">
                                        <span class="material-symbols-outlined text-3xl">hourglass_empty</span>
                                        <span>Conclua atendimento actual</span>
                                    </button>
                                <?php endif; ?>
                                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                                    <span class="material-symbols-outlined text-8xl" style="font-variation-settings: 'wght' 200;">local_activity</span>
                                </div>
                            </div>
                        </section>
                    <?php else: ?>
                        <section class="bg-surface-container-low/50 rounded-[2rem] p-8 flex items-center justify-center border-2 border-dashed border-surface-container-highest min-h-[420px]">
                            <div class="text-center">
                                <span class="material-symbols-outlined text-6xl text-surface-container-highest mb-4">notifications_paused</span>
                                <p class="text-on-surface-variant font-bold text-lg">A fila está vazia.</p>
                            </div>
                        </section>
                    <?php endif; ?>

                </div>

                <!-- Queue Table Section -->
                <?php
                // Remover o próximo paciente da lista restante
                $filaRestante = array_filter($filaEspera, fn($s) => $s['id'] !== ($proxima['id'] ?? 0));
                $filaRestante = array_values($filaRestante);
                ?>
                <?php if (!empty($filaRestante)): ?>
                    <section class="bg-white rounded-[2rem] overflow-hidden floating-card border border-white fade-in-delay-2">
                        <div class="p-8 pb-4 flex items-center justify-between">
                            <h4 class="text-xl font-headline font-extrabold tracking-tight text-black">Restantes pacientes na fila (<?= count($filaRestante) ?>)</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-surface-container-low/30 border-b border-surface-container-low">
                                        <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Senha</th>
                                        <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Nome</th>
                                        <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Prioridade</th>
                                        <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.15em] text-right">Tempo de Espera</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-surface-container-low/50">
                                    <?php foreach (array_slice($filaRestante, 0, 10) as $s): 
                                        $p = $prioridades[$s['prioridade']] ?? $prioridades[4];
                                        $minutosEspera = max(0, floor((time() - strtotime($s['criado_em'])) / 60));
                                    ?>
                                        <tr class="hover:bg-surface-container-low/20 transition-colors group cursor-pointer">
                                            <td class="px-8 py-6">
                                                <span class="tactile-mono text-lg px-2.5 py-1 bg-surface-container-high rounded text-black group-hover:bg-black group-hover:text-white transition-all"><?= htmlspecialchars($s['codigo']) ?></span>
                                            </td>
                                            <td class="px-8 py-6 font-bold text-black text-sm"><?= htmlspecialchars($s['paciente_nome']) ?></td>
                                            <td class="px-8 py-6">
                                                <span class="px-3 py-1 <?= $p['bg'] ?> <?= $p['text'] ?> rounded-full text-[10px] font-black uppercase tracking-wider"><?= $p['label'] ?></span>
                                            </td>
                                            <td class="px-8 py-6 text-right font-black text-on-surface-variant text-xs uppercase tracking-tight">Há <?= $minutosEspera ?> min</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script>
    // Utils for AJAX Form requests
    function req(data, callback) {
        data.csrf_token = "<?= gerarTokenCsrf() ?>";
        fetch("<?= BASE_URL ?>app/controllers/senhas.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: new URLSearchParams(data)
        }).then(res => callback(res)).catch(e => console.error(e));
    }

    function handleDesfazer(senhaId) {
        req({acao: "desfazer", senha_id: senhaId}, () => window.location.reload());
    }

    function handleConcluir(btn, senhaId, nome) {
        const card = document.getElementById('atendimento-card');
        const content = document.getElementById('atendimento-content');
        
        // Bloquear altura para evitar saltos
        card.style.minHeight = card.offsetHeight + 'px';
        content.classList.add('crossfade-out');
        
        req({acao: "concluir", senha_id: senhaId}, () => {
            setTimeout(() => {
                card.classList.add('items-center', 'justify-center');
                card.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full crossfade-in">
                        <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mb-6 shadow-xl shadow-green-200">
                            <span class="material-symbols-outlined text-white text-6xl">check</span>
                        </div>
                        <h3 class="text-3xl font-headline font-extrabold tracking-tight mb-2 text-center">Atendimento Concluído</h3>
                        <p class="text-on-surface-variant font-semibold text-center max-w-[280px]">O paciente <b class="text-black">${nome}</b> foi finalizado com sucesso.</p>
                    </div>
                `;
                setTimeout(() => window.location.reload(), 2000);
            }, 400);
        });
    }

    function handleCancelar(btn, senhaId, nome) {
        const card = document.getElementById('atendimento-card');
        const content = document.getElementById('atendimento-content');
        
        // Bloquear altura para evitar saltos
        card.style.minHeight = card.offsetHeight + 'px';
        content.classList.add('crossfade-out');
        
        req({acao: "cancelar", senha_id: senhaId}, () => {
            setTimeout(() => {
                card.classList.add('items-center', 'justify-center');
                card.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full crossfade-in">
                        <div class="w-24 h-24 bg-surface-container-highest rounded-full flex items-center justify-center mb-6 shadow-lg shadow-black/5">
                            <span class="material-symbols-outlined text-on-surface-variant text-6xl">person_off</span>
                        </div>
                        <h3 class="text-3xl font-headline font-extrabold tracking-tight mb-2 text-center text-on-surface-variant">Paciente Ausente</h3>
                        <p class="text-on-surface-variant/60 font-semibold text-center max-w-[280px]">O registo de <b class="text-black">${nome}</b> foi marcado como ausente.</p>
                    </div>
                `;
                setTimeout(() => window.location.reload(), 2000);
            }, 400);
        });
    }

    function handleChamar(btn, senhaId, nome, codigo) {
        const rightCard = document.getElementById('proximo-card');
        const leftSection = document.getElementById('atendimento-card');
        const leftContent = document.getElementById('atendimento-content');
        
        const icon = document.getElementById('chamar-icon');
        const text = document.getElementById('chamar-text');
        
        btn.disabled = true;
        icon.innerHTML = 'sync';
        icon.classList.add('animate-spin');
        text.innerText = 'Processando...';
        
        req({acao: "chamar", senha_id: senhaId}, () => {
            // 1. Bloquear a altura actual do cartão esquerdo para evitar saltos
            leftSection.style.minHeight = leftSection.offsetHeight + 'px';
            
            // 2. Crossfade suave: desvanecer o conteúdo antigo (ambos os cartões)
            if(leftContent) leftContent.classList.add('crossfade-out');
            if(rightCard) {
                rightCard.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                rightCard.style.opacity = '0';
                rightCard.style.transform = 'scale(0.95)';
            }
            
            // 3. Após o fade-out, injectar o estado final completo de uma só vez
            setTimeout(() => {
                leftSection.classList.remove('items-center', 'justify-center');
                leftSection.classList.add('flex-col');
                
                leftSection.innerHTML = `
                    <div class="h-full flex flex-col crossfade-in w-full relative z-10" id="atendimento-content">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="px-3 py-1 bg-surface-container-high rounded-full text-[10px] font-black uppercase tracking-widest text-on-surface-variant">A Atender Agora</span>
                                <h3 class="text-7xl tactile-mono mt-4 text-primary">${codigo}</h3>
                            </div>
                            <div class="flex items-center gap-2 bg-green-50 text-green-700 px-4 py-2 rounded-full font-black text-[10px] uppercase tracking-widest border border-green-100">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                Em Consulta
                            </div>
                        </div>
                        
                        <div class="mb-8 flex-1">
                            <p class="text-3xl font-headline font-extrabold tracking-tight mb-2 text-black">${nome}</p>
                            <p class="text-on-surface-variant font-semibold text-sm">Paciente encaminhado para consulta.</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 relative z-10">
                            <button class="flex-1 bg-black text-white py-5 px-6 rounded-2xl font-black text-lg shadow text-center cursor-default opacity-60">
                                Concluir Atendimento
                            </button>
                            <button class="bg-surface-container-low text-on-surface-variant py-5 px-8 rounded-2xl font-black text-sm shadow cursor-default opacity-60">
                                Ausente / Cancelar
                            </button>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-surface-container-low flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div class="flex flex-col flex-1 w-full">
                                <span class="text-xs font-bold text-on-surface-variant flex items-center gap-1.5 mb-2">
                                    <span class="material-symbols-outlined text-[16px] text-green-500">check_circle</span>
                                    Paciente chamado com sucesso
                                </span>
                                <div class="h-1 bg-surface-container-highest rounded-full overflow-hidden w-full max-w-[220px]">
                                    <div class="h-full bg-blue-500 rounded-full transition-all duration-500 ease-linear" id="undo-fill-dynamic" style="width:100%"></div>
                                </div>
                            </div>
                            <button onclick="handleDesfazer(${senhaId})" class="text-blue-600 bg-blue-50 hover:bg-blue-100 px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all active:scale-95 border border-blue-100 whitespace-nowrap">
                                <span class="material-symbols-outlined text-[16px]">undo</span> Desfazer
                            </button>
                        </div>
                    </div>
                    <div class="absolute -bottom-12 -right-12 w-48 h-48 bg-surface-container-low rounded-full opacity-30 pointer-events-none"></div>
                `;
                
                // 4. Iniciar countdown do botão Desfazer dinâmico
                const dynFill = document.getElementById('undo-fill-dynamic');
                if (dynFill) {
                    let pct = 100;
                    const iv = setInterval(() => {
                        pct -= (100 / 15) * 0.5;
                        if (pct <= 0) { clearInterval(iv); }
                        dynFill.style.width = Math.max(0, pct) + '%';
                    }, 500);
                }
                
                // 5. Reload suave para sincronizar com a base de dados
                setTimeout(() => window.location.reload(), 2200);
            }, 400);
        });
    }

    // Countdown da barra de desfazer flutuante
    (function () {
        const fill = document.getElementById('undo-fill-inline');
        if (!fill) return;
        const resto = <?= $restoUndo ?? 0 ?>;
        let pct = (resto / 15) * 100;
        const iv = setInterval(() => {
            pct -= (100 / 15) * 0.5;
            if (pct <= 0) {
                clearInterval(iv);
                const toast = document.getElementById('undo-inline');
                if (toast) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(10px)';
                    toast.style.transition = 'all 0.5s ease';
                    setTimeout(() => toast.remove(), 500);
                }
                return;
            }
        fill.style.width = pct + '%';
        }, 500);
    })();

    // Etapa 4: Clinical Feedback (Auditory cues for Urgent Patients)
    (function() {
        let knownUrgentes = <?= $urgentes ?>;
        
        function playUrgentPing() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime); // A5
                osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.3);
                
                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.3, ctx.currentTime + 0.05);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.start();
                osc.stop(ctx.currentTime + 0.5);
            } catch(e) { console.log('Audio API blocked or not supported'); }
        }

        setInterval(async () => {
            try {
                const res = await fetch('<?= BASE_URL ?>app/api/notificacoes_clinicas.php');
                if (!res.ok) return;
                const data = await res.json();
                
                if (data.urgentes_count > knownUrgentes) {
                    playUrgentPing();
                    if (typeof window.showToast === 'function') {
                        window.showToast("Novo paciente URGENTE na fila!", "error");
                    }
                    knownUrgentes = data.urgentes_count;
                    // Opcional: atualizar contador visual se existir
                    const badge = document.querySelector('.urgent-badge-count');
                    if(badge) badge.innerText = knownUrgentes;
                } else if (data.urgentes_count < knownUrgentes) {
                    knownUrgentes = data.urgentes_count;
                }
            } catch(e) { }
        }, 15000); // 15 seconds
    })();
    </script>
</body>
</html>
