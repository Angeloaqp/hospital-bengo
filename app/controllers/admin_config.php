<?php
// ================================================
// Hospital Geral do Bengo — Controller: Admin Config
// Gestão de consultórios, especialidades, tipos,
// disponibilidade médica e bloqueios de agenda
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Disponibilidade.php';
require_once __DIR__ . '/../../app/models/Auditoria.php';

exigirPerfil(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'app/views/admin/configuracao.php');
    exit;
}

validarTokenCsrf();

$acao = trim($_POST['acao'] ?? '');
$utilizadorId = (int) sessao('utilizador_id');
$destino = BASE_URL . 'app/views/admin/configuracao.php';

// ------------------------------------------------
// CONSULTÓRIOS
// ------------------------------------------------
if ($acao === 'criar_consultorio') {
    $nome = trim($_POST['nome'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '') ?: null;

    if (empty($nome)) {
        $_SESSION['erro'] = 'Nome do consultório é obrigatório.';
        header('Location: ' . $destino . '?tab=consultorios');
        exit;
    }

    Disponibilidade::criarConsultorio($nome, $responsavel);
    Auditoria::registar($utilizadorId, 'criar_consultorio', "Consultório: {$nome}");
    $_SESSION['mensagem'] = 'Consultório criado.';
    header('Location: ' . $destino . '?tab=consultorios');
    exit;
}

if ($acao === 'editar_consultorio') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '') ?: null;
    $activo = (int) ($_POST['activo'] ?? 1);

    if ($id <= 0 || empty($nome)) {
        $_SESSION['erro'] = 'Dados inválidos.';
        header('Location: ' . $destino . '?tab=consultorios');
        exit;
    }

    Disponibilidade::editarConsultorio($id, $nome, $responsavel, $activo);
    Auditoria::registar($utilizadorId, 'editar_consultorio', "Consultório #{$id}: {$nome}");
    $_SESSION['mensagem'] = 'Consultório actualizado.';
    header('Location: ' . $destino . '?tab=consultorios');
    exit;
}

// ------------------------------------------------
// ESPECIALIDADES
// ------------------------------------------------
if ($acao === 'criar_especialidade') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '') ?: null;

    if (empty($nome)) {
        $_SESSION['erro'] = 'Nome da especialidade é obrigatório.';
        header('Location: ' . $destino . '?tab=especialidades');
        exit;
    }

    Disponibilidade::criarEspecialidade($nome, $descricao);
    Auditoria::registar($utilizadorId, 'criar_especialidade', "Especialidade: {$nome}");
    $_SESSION['mensagem'] = 'Especialidade criada.';
    header('Location: ' . $destino . '?tab=especialidades');
    exit;
}

if ($acao === 'editar_especialidade') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '') ?: null;
    $activo = (int) ($_POST['activo'] ?? 1);

    Disponibilidade::editarEspecialidade($id, $nome, $descricao, $activo);
    Auditoria::registar($utilizadorId, 'editar_especialidade', "Especialidade #{$id}: {$nome}");
    $_SESSION['mensagem'] = 'Especialidade actualizada.';
    header('Location: ' . $destino . '?tab=especialidades');
    exit;
}

// ------------------------------------------------
// TIPOS DE ATENDIMENTO
// ------------------------------------------------
if ($acao === 'criar_tipo') {
    $nome = trim($_POST['nome'] ?? '');
    $prefixo = trim($_POST['prefixo'] ?? 'N');
    $espId = !empty($_POST['especialidade_id']) ? (int) $_POST['especialidade_id'] : null;

    if (empty($nome)) {
        $_SESSION['erro'] = 'Nome do tipo é obrigatório.';
        header('Location: ' . $destino . '?tab=tipos');
        exit;
    }

    Disponibilidade::criarTipoAtendimento($nome, $prefixo, $espId);
    Auditoria::registar($utilizadorId, 'criar_tipo_atendimento', "Tipo: {$nome}");
    $_SESSION['mensagem'] = 'Tipo de atendimento criado.';
    header('Location: ' . $destino . '?tab=tipos');
    exit;
}

if ($acao === 'editar_tipo') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $prefixo = trim($_POST['prefixo'] ?? 'N');
    $espId = !empty($_POST['especialidade_id']) ? (int) $_POST['especialidade_id'] : null;
    $activo = (int) ($_POST['activo'] ?? 1);

    Disponibilidade::editarTipoAtendimento($id, $nome, $prefixo, $espId, $activo);
    Auditoria::registar($utilizadorId, 'editar_tipo_atendimento', "Tipo #{$id}: {$nome}");
    $_SESSION['mensagem'] = 'Tipo de atendimento actualizado.';
    header('Location: ' . $destino . '?tab=tipos');
    exit;
}

// ------------------------------------------------
// DISPONIBILIDADE MÉDICA
// ------------------------------------------------
if ($acao === 'guardar_disponibilidade') {
    $medicoId = (int) ($_POST['medico_id'] ?? 0);
    $espId = (int) ($_POST['especialidade_id'] ?? 0);
    $consId = !empty($_POST['consultorio_id']) ? (int) $_POST['consultorio_id'] : null;
    $diaSemana = (int) ($_POST['dia_semana'] ?? 0);
    $turno = trim($_POST['turno'] ?? '');
    $capacidade = (int) ($_POST['capacidade'] ?? 10);

    if ($medicoId <= 0 || $espId <= 0 || $diaSemana < 1 || $diaSemana > 7 || !in_array($turno, ['manha', 'tarde'])) {
        $_SESSION['erro'] = 'Dados inválidos para disponibilidade.';
        header('Location: ' . $destino . '?tab=disponibilidade');
        exit;
    }

    Disponibilidade::guardar([
        'medico_id'       => $medicoId,
        'especialidade_id'=> $espId,
        'consultorio_id'  => $consId,
        'dia_semana'      => $diaSemana,
        'turno'           => $turno,
        'capacidade'      => max(1, $capacidade),
    ]);

    Auditoria::registar($utilizadorId, 'guardar_disponibilidade', "Médico #{$medicoId}, dia {$diaSemana}, {$turno}");
    $_SESSION['mensagem'] = 'Disponibilidade guardada.';
    header('Location: ' . $destino . '?tab=disponibilidade');
    exit;
}

if ($acao === 'remover_disponibilidade') {
    $id = (int) ($_POST['id'] ?? 0);
    Disponibilidade::remover($id);
    Auditoria::registar($utilizadorId, 'remover_disponibilidade', "Disponibilidade #{$id}");
    $_SESSION['mensagem'] = 'Disponibilidade removida.';
    header('Location: ' . $destino . '?tab=disponibilidade');
    exit;
}

// ------------------------------------------------
// BLOQUEIOS DE AGENDA
// ------------------------------------------------
if ($acao === 'criar_bloqueio') {
    $medicoId = !empty($_POST['medico_id']) ? (int) $_POST['medico_id'] : null;
    $consId = !empty($_POST['consultorio_id']) ? (int) $_POST['consultorio_id'] : null;
    $dataBloqueio = trim($_POST['data_bloqueio'] ?? '');
    $turno = trim($_POST['turno'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');

    if (empty($dataBloqueio) || !in_array($turno, ['manha', 'tarde']) || empty($motivo)) {
        $_SESSION['erro'] = 'Preencha todos os campos do bloqueio.';
        header('Location: ' . $destino . '?tab=bloqueios');
        exit;
    }

    Disponibilidade::criarBloqueio([
        'medico_id'      => $medicoId,
        'consultorio_id' => $consId,
        'data_bloqueio'  => $dataBloqueio,
        'turno'          => $turno,
        'motivo'         => $motivo,
        'criado_por'     => $utilizadorId,
    ]);

    Auditoria::registar($utilizadorId, 'criar_bloqueio_agenda', "Data: {$dataBloqueio}, {$turno}");
    $_SESSION['mensagem'] = 'Bloqueio criado.';
    header('Location: ' . $destino . '?tab=bloqueios');
    exit;
}

if ($acao === 'remover_bloqueio') {
    $id = (int) ($_POST['id'] ?? 0);
    Disponibilidade::removerBloqueio($id);
    Auditoria::registar($utilizadorId, 'remover_bloqueio', "Bloqueio #{$id}");
    $_SESSION['mensagem'] = 'Bloqueio removido.';
    header('Location: ' . $destino . '?tab=bloqueios');
    exit;
}

// Fallback
header('Location: ' . $destino);
exit;
