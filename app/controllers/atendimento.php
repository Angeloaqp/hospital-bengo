<?php
// ================================================
// Hospital Geral do Bengo
// Controller: Atendimento (Ponto de Fila e Senhas)
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Senha.php';

// Apenas autenticados
exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'public/index.php');
    exit;
}

$acao = $_POST['acao'] ?? '';

// ------------------------------------------------
// ACÇÃO: Registar Paciente e Emitir Senha
// ------------------------------------------------
if ($acao === 'registar') {
    // Apenas recepcionista ou admin podem registar
    exigirPerfil(['recepcionista', 'admin']);

    $nome = trim($_POST['nome'] ?? '');
    $idade = (int) ($_POST['idade'] ?? 0);
    $peso = !empty($_POST['peso']) ? (float) $_POST['peso'] : null;
    $morada = trim($_POST['morada'] ?? '');

    $tipoAtendimentoId = (int) ($_POST['tipo_atendimento_id'] ?? 0);
    $prioridade = (int) ($_POST['prioridade'] ?? 4);
    $registadoPor = sessao('utilizador_id');

    // Validação básica
    if (!$nome || !$idade || !$morada || !$tipoAtendimentoId || !$prioridade) {
        die("<h1>Erro: Dados obrigatórios em falta.</h1><a href='javascript:history.back()'>Voltar</a>");
    }

    try {
        $db = Database::ligar();
        // Iniciar transacção (Paciente + Senha)
        $db->beginTransaction();

        // 1. Inserir Paciente
        $stmtP = $db->prepare(
            "INSERT INTO pacientes (nome, idade, morada, peso, registado_por) 
             VALUES (:nome, :idade, :morada, :peso, :registado_por)"
        );
        $stmtP->execute([
            ':nome' => $nome,
            ':idade' => $idade,
            ':morada' => $morada,
            ':peso' => $peso,
            ':registado_por' => $registadoPor
        ]);

        $pacienteId = $db->lastInsertId();

        // 2. Gerar Código da Senha de acordo a prioridade
        $codigoSenha = Senha::gerarCodigo($prioridade);

        // 3. Inserir Senha na fila de espera
        $stmtS = $db->prepare(
            "INSERT INTO senhas (codigo, paciente_id, tipo_atendimento_id, prioridade, registado_por, estado) 
             VALUES (:codigo, :paciente_id, :tipo_atendimento_id, :prioridade, :registado_por, 'espera')"
        );
        $stmtS->execute([
            ':codigo' => $codigoSenha,
            ':paciente_id' => $pacienteId,
            ':tipo_atendimento_id' => $tipoAtendimentoId,
            ':prioridade' => $prioridade,
            ':registado_por' => $registadoPor
        ]);

        // Guardar transação na bd
        $db->commit();

        // Guardar mensagem flash na sessão para o Dashboard exibir
        $_SESSION['mensagem'] = "Paciente registado com sucesso! A nova senha é: " . $codigoSenha;

        header('Location: ' . BASE_URL . 'app/views/recepcionista/dashboard.php');
        exit;

    } catch (PDOException $e) {
        $db->rollBack();
        die("<h1>Erro interno na base de dados</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>");
    }
}

// Fallback
header('Location: ' . BASE_URL . 'public/index.php');
exit;
