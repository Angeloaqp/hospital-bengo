<?php
// ================================================
// Hospital Geral do Bengo
// Editar Utilizador — Admin
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Estatistica.php';

exigirPerfil(['admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL .
        'app/views/admin/utilizadores.php');
    exit;
}

$u = Estatistica::obterUtilizador($id);
if (!$u) {
    $_SESSION['erro'] = 'Utilizador não encontrado.';
    header('Location: ' . BASE_URL .
        'app/views/admin/utilizadores.php');
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
    <title>Editar Utilizador —
        <?= APP_NOME ?>
    </title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--texto);
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--borda);
            border-radius: var(--radius-sm);
            font-size: 14px;
            color: var(--texto);
            background: #fff;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--azul);
            box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.12);
        }

        .form-group .hint {
            font-size: 11px;
            color: var(--texto-muted);
            margin-top: 4px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .form-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 8px;
        }

        .campo-medico {
            display: none;
        }

        .campo-medico.visivel {
            display: block;
        }

        .info-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--fundo);
            border: 1px solid var(--borda);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            color: var(--texto-muted);
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="text-on-surface">
<?php $paginaActual = 'utilizadores'; ?>
        <?php include __DIR__ . '/../comum/sidebar.php'; ?>

        <?php $tituloPagina = 'Editar Utilizador'; $subtituloPagina = 'ID #' . $u['id'] . ' — Criado em ' . date('d/m/Y', strtotime($u['criado_em'])); ?>
        <?php include __DIR__ . '/../comum/header.php'; ?>
<div class="ml-56 mt-28 p-8 flex justify-center">
<main class="w-full max-w-[1500px]">
<?php if ($erro): ?>
                <div class="alerta alerta-perigo">
                    ⚠
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form method="POST" id="form-editar" action="<?= BASE_URL ?>app/controllers/estatisticas.php">
                    <input type="hidden" name="acao" value="editar_utilizador">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="nome">Nome completo *</label>
                            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($u['nome']) ?>"
                                required minlength="3">
                        </div>

                        <div class="form-group">
                            <label for="nome_utilizador">
                                Nome de utilizador *
                            </label>
                            <input type="text" id="nome_utilizador" name="nome_utilizador"
                                value="<?= htmlspecialchars($u['nome_utilizador']) ?>" required minlength="3"
                                pattern="[a-zA-Z0-9_\.]+">
                            <div class="hint">
                                Apenas letras, números, _ e .
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="senha">Nova senha</label>
                            <input type="password" id="senha" name="senha" minlength="6"
                                placeholder="Deixar vazio para manter">
                            <div class="hint">
                                Preencher apenas se quiser alterar
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="text" id="telefone" name="telefone"
                                value="<?= htmlspecialchars($u['telefone'] ?? '') ?>"
                                placeholder="Ex: +244 923 456 789">
                        </div>

                        <div class="form-group">
                            <label for="perfil">Perfil de acesso *</label>
                            <select id="perfil" name="perfil" required>
                                <option value="recepcionista" <?= $u['perfil'] === 'recepcionista' ? 'selected' : '' ?>>
                                    Recepcionista
                                </option>
                                <option value="medico" <?= $u['perfil'] === 'medico' ? 'selected' : '' ?>>
                                    Médico
                                </option>
                                <option value="admin" <?= $u['perfil'] === 'admin' ? 'selected' : '' ?>>
                                    Administrador
                                </option>
                            </select>
                        </div>

                        <div class="form-group campo-medico" id="campo-especialidade">
                            <label for="especialidade_id">
                                Especialidade *
                            </label>
                            <select id="especialidade_id" name="especialidade_id">
                                <option value="0">— Seleccionar —</option>
                                <?php foreach ($especialidades as $e): ?>
                                    <option value="<?= $e['id'] ?>" <?= ($u['especialidade_id'] ?? 0) == $e['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($e['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group campo-medico" id="campo-consultorio">
                            <label for="consultorio_id">Consultório</label>
                            <select id="consultorio_id" name="consultorio_id">
                                <option value="0">— Seleccionar —</option>
                                <?php foreach ($consultorios as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($u['consultorio_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="form-actions">
                        <a href="utilizadores.php" class="btn">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primario">
                            Guardar Alterações
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <script>
        const perfil = document.getElementById('perfil');
        const camposMedico = document.querySelectorAll('.campo-medico');

        function toggleCamposMedico() {
            const show = perfil.value === 'medico';
            camposMedico.forEach(c => {
                c.classList.toggle('visivel', show);
            });
            if (!show) {
                document.getElementById('especialidade_id').value = '0';
                document.getElementById('consultorio_id').value = '0';
            }
        }

        perfil.addEventListener('change', toggleCamposMedico);
        toggleCamposMedico();
    </script>

</body>

</html>