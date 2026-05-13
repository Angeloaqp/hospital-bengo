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
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        .fade-in-delay-1 { animation: fadeIn 0.5s ease-out 0.1s forwards; opacity: 0; }
        .floating-card { box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05), 0 2px 10px -2px rgba(0,0,0,0.03); }
        .tactile-input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            font-size: 14px;
            color: #111827;
            background: #fff;
            transition: all 0.2s;
        }
        .tactile-input:focus {
            outline: none;
            border-color: #000;
            box-shadow: 0 0 0 4px rgba(0,0,0,0.05);
        }
        .tactile-label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            color: #4b5563;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>

<body class="text-on-surface h-screen overflow-hidden bg-[#f3f4f6]">

    <?php $paginaActual = 'utilizadores'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php ob_start(); ?>
        <a href="<?= BASE_URL ?>app/views/admin/ver_utilizador.php?id=<?= $u['id'] ?>" class="text-xs px-4 py-2 bg-surface-container-low text-on-surface-variant hover:bg-surface-container hover:text-black rounded-full font-bold transition-all border border-black/5 flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar ao Perfil
        </a>
    <?php $accoesPagina = ob_get_clean(); ?>

    <?php $tituloPagina = 'Editar Conta: ' . htmlspecialchars(explode(' ', $u['nome'])[0]); ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-56 mt-28 h-[calc(100vh-7rem)] flex flex-col">
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <main class="w-full max-w-[800px] mx-auto px-8 pb-24 pt-8">

                <div class="mb-8 fade-in">
                    <h2 class="text-4xl font-headline font-extrabold tracking-tighter mb-2 text-black">Editar Utilizador</h2>
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
                            <select id="perfil" name="perfil" class="tactile-input appearance-none bg-no-repeat bg-[right_1rem_center]" style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'black\'><path d=\'M7 10l5 5 5-5z\'/></svg>'); background-size: 1.5rem;" required>
                                <option value="recepcionista" <?= $u['perfil'] === 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                                <option value="medico" <?= $u['perfil'] === 'medico' ? 'selected' : '' ?>>Médico</option>
                                <option value="admin" <?= $u['perfil'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>

                        <!-- Especialidade (Médico) -->
                        <div class="campo-medico">
                            <label for="especialidade_id" class="tactile-label">Especialidade *</label>
                            <select id="especialidade_id" name="especialidade_id" class="tactile-input appearance-none bg-no-repeat bg-[right_1rem_center]" style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'black\'><path d=\'M7 10l5 5 5-5z\'/></svg>'); background-size: 1.5rem;">
                                <option value="0">— Seleccionar —</option>
                                <?php foreach ($especialidades as $e): ?>
                                    <option value="<?= $e['id'] ?>" <?= ($u['especialidade_id'] ?? 0) == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Consultório (Médico) -->
                        <div class="campo-medico">
                            <label for="consultorio_id" class="tactile-label">Consultório Vinculado</label>
                            <select id="consultorio_id" name="consultorio_id" class="tactile-input appearance-none bg-no-repeat bg-[right_1rem_center]" style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'black\'><path d=\'M7 10l5 5 5-5z\'/></svg>'); background-size: 1.5rem;">
                                <option value="0">— Seleccionar —</option>
                                <?php foreach ($consultorios as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($u['consultorio_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 mt-6 border-t border-surface-container-low">
                        <a href="<?= BASE_URL ?>app/views/admin/ver_utilizador.php?id=<?= $u['id'] ?>" class="px-6 py-3 rounded-full text-sm font-bold text-on-surface-variant hover:bg-surface-container-low transition-all">Cancelar</a>
                        <button type="submit" class="px-8 py-3 bg-black text-white rounded-full font-bold text-sm shadow hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">save</span> Guardar Alterações
                        </button>
                    </div>
                </form>

            </main>
        </div>
    </div>

    <script>
        const perfilSelect = document.getElementById('perfil');
        const camposMedico = document.querySelectorAll('.campo-medico');

        function toggleCamposMedico() {
            const show = perfilSelect.value === 'medico';
            camposMedico.forEach(c => {
                c.style.display = show ? 'block' : 'none';
            });
            if (!show) {
                document.getElementById('especialidade_id').value = '0';
                document.getElementById('consultorio_id').value = '0';
            }
        }

        perfilSelect.addEventListener('change', toggleCamposMedico);
        toggleCamposMedico();
    </script>
</body>
</html>