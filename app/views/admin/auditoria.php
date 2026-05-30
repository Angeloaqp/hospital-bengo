<?php
// ================================================
// Hospital Geral do Bengo
// Logs de Auditoria — Admin (Tactile Editorial)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Auditoria.php';

exigirPerfil(['admin']);

// Filtros
$filtroAccao = trim($_GET['accao'] ?? '');
$filtroUser = (int) ($_GET['user'] ?? 0);
$dataInicio = trim($_GET['di'] ?? '');
$dataFim = trim($_GET['df'] ?? '');

$logs = Auditoria::listar(
    100,
    $filtroAccao ?: null,
    $filtroUser ?: null,
    $dataInicio ?: null,
    $dataFim ?: null
);

$utilizadores = Auditoria::utilizadoresParaFiltro();
$totalHoje = Auditoria::totalHoje();

// Ícones por tipo de acção (Material Symbols)
$iconeAccao = [
    'login' => 'login',
    'logout' => 'logout',
    'chamar_paciente' => 'campaign',
    'concluir_atendimento' => 'check_circle',
    'cancelar_paciente' => 'cancel',
    'desfazer_chamada' => 'undo',
    'registar_paciente' => 'person_add',
    'rechamar_paciente' => 'replay',
    'criar_utilizador' => 'manage_accounts',
    'editar_utilizador' => 'edit',
    'toggle_utilizador' => 'sync_alt',
];
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--cor-scrollbar-light); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--cor-scrollbar-light-hover); }

        @keyframes glideIn {
            0% { opacity: 0; transform: translateY(20px); filter: blur(4px); }
            100% { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .glide-in { animation: glideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .stagger-1 { animation-delay: 0.05s; }
        .stagger-2 { animation-delay: 0.1s; }
        
        .table-row { transition: all 0.2s ease; border-bottom: 1px solid rgba(0,0,0,0.03); }
        .table-row:hover { background-color: var(--cor-input-hover); }

        /* Modern Select & Input for filters */
        .filter-input {
            background: var(--cor-input-bg); border: 2px solid transparent; border-radius: 1rem;
            padding: 0.8rem 1rem; font-size: 0.85rem; font-weight: 600; color: var(--cor-on-surface);
            outline: none; transition: all 0.3s ease;
        }
        .filter-input:focus { background: var(--cor-surface-container-lowest); border-color: var(--cor-on-surface); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        /* Buttons */
        .btn-action { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); }
        .btn-action:active { transform: scale(0.98); }
    </style>
</head>

<body class="text-on-surface bg-background">
    <?php $paginaActual = 'auditoria'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>
    
    <?php
    $tituloPagina = 'Auditoria';
    ob_start(); ?>
    <div class="px-4 py-2 bg-white rounded-full flex items-center gap-2 border border-primary/5 shadow-sm">
        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">monitoring</span>
        <span class="text-xs font-bold text-on-surface"><?= $totalHoje ?> acções hoje</span>
    </div>
    <?php $accoesPagina = ob_get_clean(); ?>

    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8 ">
<main class="w-full">
        <div class="pb-24">
            
            <div class="mb-10 flex justify-between items-end glide-in">
                <div>
                    <h2 class="text-3xl font-headline font-extrabold text-on-surface tracking-tight">Logs de Sistema</h2>
                    <p class="text-sm font-semibold text-on-surface-variant mt-1 max-w-xl">Monitorização e rastreio de segurança de todas as atividades realizadas na plataforma.</p>
                </div>
            </div>

            <!-- Formulário de Filtros -->
            <div class="bg-white rounded-[2rem] p-6 mb-8 border border-white/50 shadow-sm glide-in stagger-1">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest pl-2">Acção</label>
                        <?php
                        $sel_id = 'cs-accao';
                        $sel_name = 'accao';
                        $sel_icon = 'history';
                        $sel_placeholder = 'Todas as acções';
                        $sel_value = $filtroAccao;
                        $sel_size = 'sm';
                        $sel_class = 'w-48';
                        $sel_options = ['' => ['label' => 'Todas as acções', 'icon' => 'filter_list', 'color' => 'text-on-surface-variant']];
                        foreach ($iconeAccao as $k => $ic) {
                            $sel_options[$k] = ['label' => ucfirst(str_replace('_', ' ', $k)), 'icon' => $ic, 'color' => 'text-on-surface'];
                        }
                        include __DIR__ . '/../comum/custom_select.php';
                        ?>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest pl-2">Utilizador</label>
                        <?php
                        $sel_id = 'cs-user';
                        $sel_name = 'user';
                        $sel_icon = 'person';
                        $sel_placeholder = 'Todos os utilizadores';
                        $sel_value = (string)$filtroUser;
                        $sel_size = 'sm';
                        $sel_class = 'w-48';
                        $sel_options = ['0' => ['label' => 'Todos os utilizadores', 'icon' => 'groups', 'color' => 'text-on-surface-variant']];
                        foreach ($utilizadores as $u) {
                            $sel_options[(string)$u['id']] = ['label' => htmlspecialchars($u['nome']), 'icon' => 'person', 'color' => 'text-blue-600'];
                        }
                        include __DIR__ . '/../comum/custom_select.php';
                        ?>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest pl-2">Data Inicial</label>
                        <input type="date" name="di" value="<?= htmlspecialchars($dataInicio) ?>" class="filter-input">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest pl-2">Data Final</label>
                        <input type="date" name="df" value="<?= htmlspecialchars($dataFim) ?>" class="filter-input">
                    </div>

                    <div class="flex items-center gap-3 ml-auto">
                        <?php if ($filtroAccao || $filtroUser || $dataInicio || $dataFim): ?>
                            <a href="auditoria.php" class="px-5 py-3 text-sm font-bold text-gray-500 hover:text-black transition-colors rounded-xl hover:bg-gray-50">
                                Limpar
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="bg-primary text-white px-7 py-3 rounded-xl font-extrabold text-sm flex items-center gap-2 btn-action shadow-lg shadow-black/10">
                            <span class="material-symbols-outlined text-[18px]">filter_list</span>
                            Aplicar Filtros
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabela de Logs -->
            <div class="bg-white rounded-[2rem] border border-white/50 shadow-sm overflow-hidden glide-in stagger-2">
                <?php if (empty($logs)): ?>
                    <div class="flex flex-col items-center justify-center p-20 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-[32px] text-gray-300">security_update_warning</span>
                        </div>
                        <p class="text-sm font-bold text-gray-400">Nenhuma acção registada <?= ($filtroAccao || $filtroUser) ? 'com estes filtros.' : 'ainda.' ?></p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse min-w-[900px]">
                            <thead>
                                <tr>
                                    <th class="py-4 px-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Registo de Acção</th>
                                    <th class="py-4 px-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Responsável</th>
                                    <th class="py-4 px-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50 max-w-[300px]">Detalhes Técnicos</th>
                                    <th class="py-4 px-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50">Endereço IP</th>
                                    <th class="py-4 px-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/50 text-right">Data / Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $l):
                                    $ic = $iconeAccao[$l['accao']] ?? 'rule';
                                ?>
                                <tr class="table-row">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                                <span class="material-symbols-outlined text-[16px] text-on-surface"><?= $ic ?></span>
                                            </div>
                                            <span class="text-sm font-extrabold text-on-surface">
                                                <?= ucfirst(str_replace('_', ' ', $l['accao'])) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-800"><?= htmlspecialchars($l['utilizador_nome']) ?></span>
                                            <span class="text-[10px] font-extrabold text-blue-500 uppercase mt-0.5"><?= ucfirst($l['perfil']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 max-w-[300px]">
                                        <?php if ($l['detalhes']): ?>
                                            <span class="text-xs font-semibold text-gray-500 truncate block w-full bg-gray-50 px-2 py-1 rounded border border-primary/5" title="<?= htmlspecialchars($l['detalhes']) ?>">
                                                <?= htmlspecialchars($l['detalhes']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-300">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-xs font-mono font-bold text-gray-400 bg-gray-50/50 px-2 py-1 rounded border border-gray-100">
                                            <?= htmlspecialchars($l['ip'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-sm font-bold text-gray-800"><?= dataFormatoPT($l['criado_em'], 'curto') ?></span>
                                            <span class="text-[11px] font-bold text-gray-400"><?= date('H:i:s', strtotime($l['criado_em'])) ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </main>
</div>
</body>
</html>