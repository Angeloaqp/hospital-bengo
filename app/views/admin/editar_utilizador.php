<?php
// ================================================
// Hospital Geral do Bengo
// Editar Utilizador — Admin (Tactile Editorial)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Estatistica.php';

exigirPerfil(['admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . 'app/views/admin/utilizadores.php');
    exit;
}

$u = Estatistica::obterUtilizador($id);
if (!$u) {
    $_SESSION['erro'] = 'Utilizador não encontrado.';
    header('Location: ' . BASE_URL . 'app/views/admin/utilizadores.php');
    exit;
}

$especialidades = Estatistica::listarEspecialidades();
$consultorios = Estatistica::listarConsultorios();

$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Utilizador — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--cor-scrollbar-light); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--cor-scrollbar-light-hover); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        .fade-in-delay-1 { animation: fadeIn 0.5s ease-out 0.1s forwards; opacity: 0; }
        .floating-card { box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05), 0 2px 10px -2px rgba(0,0,0,0.03); }
        .tactile-input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--cor-scrollbar-light);
            border-radius: 0.75rem;
            font-size: 14px;
            color: var(--cor-chart-dark);
            background: var(--cor-surface-container-lowest);
            transition: all 0.2s;
        }
        .tactile-input:focus {
            outline: none;
            border-color: var(--cor-toast-bg);
            box-shadow: 0 0 0 4px rgba(0,0,0,0.05);
        }
        .tactile-label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            color: var(--cor-inactive-text);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>

<body class="text-on-surface h-screen overflow-hidden bg-background">

    <?php $paginaActual = 'utilizadores'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php ob_start(); ?>
        <a href="<?= BASE_URL ?>app/views/admin/ver_utilizador.php?id=<?= $u['id'] ?>" class="text-xs px-4 py-2 bg-surface-container-low text-on-surface-variant hover:bg-surface-container hover:text-black rounded-full font-bold transition-all border border-primary/5 flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar ao Perfil
        </a>
    <?php $accoesPagina = ob_get_clean(); ?>

    <?php $tituloPagina = 'Editar Conta: ' . htmlspecialchars(explode(' ', $u['nome'])[0]); ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-56 mt-28 h-[calc(100vh-7rem)] flex flex-col">
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <main class="w-full max-w-[800px] mx-auto px-8 pb-24 pt-8">

                <div class="mb-8 fade-in">
                    <h2 class="text-4xl font-headline font-extrabold tracking-tighter mb-2 text-on-surface">Editar Utilizador</h2>
                    <p class="text-on-surface-variant font-medium">Modifique as credenciais, dados pessoais ou nível de acesso.</p>
                </div>

                <?php if ($erro): ?>
                    <div class="mb-6 p-4 bg-error/10 border border-error/20 rounded-2xl flex items-start gap-3 fade-in text-error">
                        <span class="material-symbols-outlined shrink-0">error</span>
                        <p class="font-bold text-sm mt-0.5"><?= htmlspecialchars($erro) ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>app/controllers/estatisticas.php" class="bg-white p-10 rounded-[2.5rem] floating-card border border-white fade-in-delay-1 space-y-8">
                    <input type="hidden" name="acao" value="editar_utilizador">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nome -->
                        <div class="md:col-span-2">
                            <label for="nome" class="tactile-label">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" class="tactile-input" value="<?= htmlspecialchars($u['nome']) ?>" required minlength="3">
                        </div>

                        <!-- Nome de Utilizador -->
                        <div>
                            <label for="nome_utilizador" class="tactile-label">Nome de Acesso *</label>
                            <input type="text" id="nome_utilizador" name="nome_utilizador" class="tactile-input" value="<?= htmlspecialchars($u['nome_utilizador']) ?>" required minlength="3" pattern="[a-zA-Z0-9_\.]+">
                            <p class="text-[10px] font-bold text-on-surface-variant mt-2 uppercase tracking-widest">Letras, números, _ e .</p>
                        </div>

                        <!-- Telefone -->
                        <div>
                            <label for="telefone" class="tactile-label">Telefone</label>
                            <input type="text" id="telefone" name="telefone" class="tactile-input" value="<?= htmlspecialchars($u['telefone'] ?? '') ?>" placeholder="Ex: 923 456 789">
                        </div>

                        <!-- Senha -->
                        <div class="md:col-span-2 p-6 bg-surface-container-low/50 rounded-2xl border border-surface-container-low">
                            <label for="senha" class="tactile-label">Nova Senha</label>
                            <input type="password" id="senha" name="senha" class="tactile-input bg-white" minlength="6" placeholder="Deixar em branco para manter a actual">
                            <p class="text-[10px] font-bold text-on-surface-variant mt-2 uppercase tracking-widest">Apenas preencha se desejar alterar a senha</p>
                        </div>

                        <!-- Perfil -->
                        <div class="md:col-span-2 mt-4 pt-6 border-t border-surface-container-low">
                            <label for="perfil" class="tactile-label">Nível de Acesso *</label>
                            <?php
                            $sel_id = 'perfil';
                            $sel_name = 'perfil';
                            $sel_icon = 'shield_person';
                            $sel_placeholder = 'Seleccionar perfil';
                            $sel_value = $u['perfil'];
                            $sel_required = true;
                            $sel_options = [
                                'recepcionista' => ['label' => 'Recepcionista', 'icon' => 'badge', 'color' => 'text-purple-600'],
                                'medico' => ['label' => 'Médico', 'icon' => 'stethoscope', 'color' => 'text-blue-600'],
                                'admin' => ['label' => 'Administrador', 'icon' => 'shield_person', 'color' => 'text-on-surface'],
                            ];
                            include __DIR__ . '/../comum/custom_select.php';
                            ?>
                        </div>

                        <!-- Especialidade (Médico) -->
                        <div class="campo-medico">
                            <label for="especialidade_id" class="tactile-label">Especialidade *</label>
                            <?php
                            $sel_id = 'especialidade_id';
                            $sel_name = 'especialidade_id';
                            $sel_icon = 'medical_services';
                            $sel_placeholder = '— Seleccionar —';
                            $sel_value = (string)($u['especialidade_id'] ?? 0);
                            $sel_options = ['0' => ['label' => '— Seleccionar —', 'icon' => 'filter_list', 'color' => 'text-on-surface-variant']];
                            foreach ($especialidades as $e) {
                                $sel_options[(string)$e['id']] = ['label' => htmlspecialchars($e['nome']), 'icon' => 'medical_services', 'color' => 'text-blue-600'];
                            }
                            include __DIR__ . '/../comum/custom_select.php';
                            ?>
                        </div>

                        <!-- Consultório (Médico) -->
                        <div class="campo-medico">
                            <label for="consultorio_id" class="tactile-label">Consultório Vinculado</label>
                            <?php
                            $sel_id = 'consultorio_id';
                            $sel_name = 'consultorio_id';
                            $sel_icon = 'meeting_room';
                            $sel_placeholder = '— Seleccionar —';
                            $sel_value = (string)($u['consultorio_id'] ?? 0);
                            $sel_options = ['0' => ['label' => '— Seleccionar —', 'icon' => 'filter_list', 'color' => 'text-on-surface-variant']];
                            foreach ($consultorios as $c) {
                                $sel_options[(string)$c['id']] = ['label' => htmlspecialchars($c['nome']), 'icon' => 'meeting_room', 'color' => 'text-green-600'];
                            }
                            include __DIR__ . '/../comum/custom_select.php';
                            ?>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 mt-6 border-t border-surface-container-low">
                        <a href="<?= BASE_URL ?>app/views/admin/ver_utilizador.php?id=<?= $u['id'] ?>" class="px-6 py-3 rounded-xl text-sm font-bold text-on-surface-variant hover:bg-surface-container-low transition-all">Cancelar</a>
                        <button type="submit" class="px-8 py-3 bg-primary text-white rounded-xl font-bold text-sm shadow hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">save</span> Guardar Alterações
                        </button>
                    </div>
                </form>

            </main>
        </div>
    </div>

    <script>
        const perfilSelect = document.getElementById('perfil-native');
        const camposMedico = document.querySelectorAll('.campo-medico');

        function toggleCamposMedico() {
            const show = perfilSelect.value === 'medico';
            camposMedico.forEach(c => {
                c.style.display = show ? 'block' : 'none';
            });
            if (!show) {
                document.getElementById('especialidade_id-native').value = '0';
                document.getElementById('consultorio_id-native').value = '0';
            }
        }

        perfilSelect.addEventListener('change', toggleCamposMedico);
        toggleCamposMedico();
    </script>
</body>
</html>