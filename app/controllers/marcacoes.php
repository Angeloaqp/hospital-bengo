<?php
// ================================================
// Hospital Geral do Bengo — Controller: Marcações
// Criar, Check-in, Remarcar, Cancelar, Falta
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Marcacao.php';
require_once __DIR__ . '/../../app/models/Triagem.php';
require_once __DIR__ . '/../../app/models/Senha.php';
require_once __DIR__ . '/../../app/models/Notificacao.php';
require_once __DIR__ . '/../../app/models/Auditoria.php';
require_once __DIR__ . '/../../app/models/PacienteContacto.php';

exigirPerfil(['recepcionista', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'app/views/recepcionista/agenda.php');
    exit;
}

validarTokenCsrf();

$acao = trim($_POST['acao'] ?? '');
$utilizadorId = (int) sessao('utilizador_id');
$destino = BASE_URL . 'app/views/recepcionista/agenda.php';

// ------------------------------------------------
// CRIAR MARCAÇÃO
// ------------------------------------------------
if ($acao === 'criar') {
    $erros = [];

    $pacienteId = (int) ($_POST['paciente_id'] ?? 0);
    $especialidadeId = (int) ($_POST['especialidade_id'] ?? 0);
    
    // Auto-resolver tipo_atendimento_id (pois foi removido da UI)
    $tipoAtendimentoId = 1; // Default to Consulta Geral
    if ($especialidadeId > 0) {
        $db = Database::ligar();
        $stmt = $db->prepare("SELECT id FROM tipos_atendimento WHERE especialidade_id = :esp LIMIT 1");
        $stmt->execute([':esp' => $especialidadeId]);
        $tipo = $stmt->fetchColumn();
        if ($tipo) $tipoAtendimentoId = (int) $tipo;
    }

    $consultorioId = !empty($_POST['consultorio_id']) ? (int) $_POST['consultorio_id'] : null;
    $medicoId = (int) ($_POST['medico_seleccao'] ?? 0);
    if ($medicoId <= 0 && !empty($_POST['medico_id'])) {
        $medicoId = (int) $_POST['medico_id'];
    }
    
    $dataConsulta = trim($_POST['data_consulta'] ?? '');
    $turno = trim($_POST['turno'] ?? '');
    $hora_marcacao = trim($_POST['hora_marcacao'] ?? '');
    $origem = trim($_POST['origem'] ?? 'marcacao');
    $prioridade = (int) ($_POST['prioridade'] ?? 4);
    $observacoes = trim($_POST['observacoes'] ?? '');

    // Validações
    if ($pacienteId <= 0) $erros[] = 'Paciente é obrigatório.';
    if ($especialidadeId <= 0) $erros[] = 'Especialidade é obrigatória.';
    if ($medicoId <= 0) $erros[] = 'Médico é obrigatório.';
    if (empty($dataConsulta)) $erros[] = 'Data é obrigatória.';
    if (!in_array($turno, ['manha', 'tarde'])) $erros[] = 'Turno inválido.';
    if (!in_array($prioridade, [1, 2, 3, 4])) $prioridade = 4;
    if (!in_array($origem, ['marcacao', 'mesmo_dia'])) $origem = 'marcacao';

    // Mesmo dia = data de hoje
    if ($origem === 'mesmo_dia') {
        $dataConsulta = date('Y-m-d');
    }

    // Validar data não é passada
    if (!empty($dataConsulta) && $dataConsulta < date('Y-m-d')) {
        $erros[] = 'Não é possível marcar para uma data passada.';
    }

    // Verificar disponibilidade/capacidade
    if (empty($erros)) {
        if (!Marcacao::verificarDisponibilidade($medicoId, $consultorioId, $dataConsulta, $turno)) {
            $erros[] = 'Sem disponibilidade — capacidade lotada ou agenda bloqueada para esta data/turno.';
        }
    }

    if (!empty($erros)) {
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'erros' => $erros]);
            exit;
        }
        $_SESSION['erros_form'] = $erros;
        $_SESSION['dados_form'] = $_POST;
        header('Location: ' . BASE_URL . 'app/views/recepcionista/marcacao.php');
        exit;
    }

    $db = Database::ligar();
    $db->beginTransaction();

    try {
        $marcacaoId = Marcacao::criar([
            'paciente_id'        => $pacienteId,
            'especialidade_id'   => $especialidadeId,
            'tipo_atendimento_id'=> $tipoAtendimentoId,
            'consultorio_id'     => $consultorioId,
            'medico_id'          => $medicoId,
            'data_consulta'      => $dataConsulta,
            'hora_marcacao'      => $hora_marcacao ?: null,
            'turno'              => $turno,
            'origem'             => $origem,
            'prioridade'         => $prioridade,
            'observacoes'        => $observacoes ?: null,
            'criada_por'         => $utilizadorId,
        ]);

        // Guardar contactos se fornecidos
        if (!empty($_POST['contactos']) && is_array($_POST['contactos'])) {
            PacienteContacto::guardarContactos($pacienteId, $_POST['contactos']);
        }

        // Criar lembretes automáticos
        Notificacao::criarLembretesParaMarcacao($marcacaoId);

        // ── Gerar senha automaticamente para TODAS as marcações (válida no dia) ──
        $origemSenha = ($origem === 'mesmo_dia') ? 'mesmo_dia' : 'marcacao';
        $senhaCodigo = Senha::gerarCodigo($prioridade, $dataConsulta);

        $stmtSenha = $db->prepare(
            "INSERT INTO senhas
                (marcacao_id, origem, codigo, paciente_id,
                 tipo_atendimento_id, consultorio_id,
                 prioridade, estado, registado_por)
             VALUES
                (:mid, :orig, :cod, :pid,
                 :tipo, :cons,
                 :prio, 'espera', :reg)"
        );
        $stmtSenha->execute([
            ':mid'  => $marcacaoId,
            ':orig' => $origemSenha,
            ':cod'  => $senhaCodigo,
            ':pid'  => $pacienteId,
            ':tipo' => $tipoAtendimentoId,
            ':cons' => $consultorioId ?: null,
            ':prio' => $prioridade,
            ':reg'  => $utilizadorId,
        ]);

        if ($origem === 'mesmo_dia' || $dataConsulta === date('Y-m-d')) {
            Marcacao::confirmarCheckin($marcacaoId, $prioridade, $utilizadorId);
            $msgAuditoria = "Marcação #{$marcacaoId} para paciente #{$pacienteId} em {$dataConsulta} ({$turno}) — Senha: {$senhaCodigo}";
            $msgSucesso = "Marcação #{$marcacaoId} criada — Senha: {$senhaCodigo}";
        } else {
            $msgAuditoria = "Marcação #{$marcacaoId} para paciente #{$pacienteId} agendada para {$dataConsulta} ({$turno}) — Senha: {$senhaCodigo}";
            $msgSucesso = "Marcação agendada para " . date('d/m/Y', strtotime($dataConsulta)) . " — Senha gerada: {$senhaCodigo}.";
        }

        $db->commit();

        Auditoria::registar(
            $utilizadorId,
            'criar_marcacao',
            $msgAuditoria
        );

        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'       => 'success',
                'marcacao_id'  => $marcacaoId,
                'senha_codigo' => $senhaCodigo,
                'mensagem'     => $msgSucesso,
            ]);
            exit;
        }

        $_SESSION['mensagem'] = $msgSucesso;
        if ($senhaCodigo) {
            $_SESSION['ultima_senha'] = $senhaCodigo;
        }

        header('Location: ' . $destino);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'erros' => [$e->getMessage()]]);
            exit;
        }
        $_SESSION['erro'] = 'Erro ao criar marcação: ' . $e->getMessage();
        $_SESSION['dados_form'] = $_POST;
        header('Location: ' . BASE_URL . 'app/views/recepcionista/marcacao.php');
        exit;
    }
}

// ------------------------------------------------
// CHECK-IN (Triagem + Senha)
// ------------------------------------------------
if ($acao === 'checkin') {
    $marcacaoId = (int) ($_POST['marcacao_id'] ?? 0);

    if ($marcacaoId <= 0) {
        $_SESSION['erro'] = 'Marcação inválida.';
        header('Location: ' . $destino);
        exit;
    }

    $marcacao = Marcacao::obter($marcacaoId);
    if (!$marcacao || $marcacao['estado'] !== 'marcada') {
        $_SESSION['erro'] = 'Esta marcação não está em estado válido para check-in.';
        header('Location: ' . $destino);
        exit;
    }

    $prioridadeClinica = (int) ($_POST['prioridade_clinica'] ?? $marcacao['prioridade']);

    $db = Database::ligar();
    $db->beginTransaction();

    try {
        // 1. Salvar triagem
        Triagem::criarOuAtualizar($marcacaoId, [
            'paciente_id'        => (int) $marcacao['paciente_id'],
            'sintomas'           => trim($_POST['sintomas'] ?? ''),
            'temperatura'        => $_POST['temperatura'] ?? null,
            'pressao_arterial'   => trim($_POST['pressao_arterial'] ?? ''),
            'peso'               => $_POST['peso'] ?? null,
            'frequencia_cardiaca'=> $_POST['frequencia_cardiaca'] ?? null,
            'observacoes'        => trim($_POST['observacoes_triagem'] ?? ''),
            'prioridade_clinica' => $prioridadeClinica,
            'registado_por'      => $utilizadorId,
        ]);

        // 2. Confirmar check-in na marcação
        Marcacao::confirmarCheckin($marcacaoId, $prioridadeClinica, $utilizadorId);

        // 3. Atualizar prioridade da senha existente
        $stmtSenha = $db->prepare(
            "UPDATE senhas SET prioridade = :prio WHERE marcacao_id = :mid"
        );
        $stmtSenha->execute([
            ':prio' => $prioridadeClinica,
            ':mid'  => $marcacaoId
        ]);
        
        $stmt = $db->prepare("SELECT codigo FROM senhas WHERE marcacao_id = :mid LIMIT 1");
        $stmt->execute([':mid' => $marcacaoId]);
        $codigo = $stmt->fetchColumn() ?: 'Sem senha';

        $db->commit();

        Auditoria::registar(
            $utilizadorId,
            'checkin_marcacao',
            "Check-in marcação #{$marcacaoId}, senha {$codigo}"
        );

        $_SESSION['mensagem'] = "Check-in realizado — Senha: {$codigo}";
        $_SESSION['ultima_senha'] = $codigo;

        header('Location: ' . $destino);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['erro'] = 'Erro no check-in: ' . $e->getMessage();
        header('Location: ' . $destino);
        exit;
    }
}

// ------------------------------------------------
// TRIAGEM (gravar sinais vitais — sem gerar senha)
// ------------------------------------------------
if ($acao === 'triagem') {
    $marcacaoId = (int) ($_POST['marcacao_id'] ?? 0);

    if ($marcacaoId <= 0) {
        $_SESSION['erro'] = 'Marcação inválida.';
        header('Location: ' . $destino);
        exit;
    }

    $marcacao = Marcacao::obter($marcacaoId);
    if (!$marcacao || !in_array($marcacao['estado'], ['confirmada', 'marcada'])) {
        $_SESSION['erro'] = 'Esta marcação não está em estado válido para triagem.';
        header('Location: ' . $destino);
        exit;
    }

    $prioridadeClinica = (int) ($_POST['prioridade_clinica'] ?? $marcacao['prioridade']);

    try {
        // 1. Salvar triagem
        Triagem::criarOuAtualizar($marcacaoId, [
            'paciente_id'        => (int) $marcacao['paciente_id'],
            'sintomas'           => trim($_POST['sintomas'] ?? ''),
            'temperatura'        => $_POST['temperatura'] ?? null,
            'pressao_arterial'   => trim($_POST['pressao_arterial'] ?? ''),
            'peso'               => $_POST['peso'] ?? null,
            'frequencia_cardiaca'=> $_POST['frequencia_cardiaca'] ?? null,
            'observacoes'        => trim($_POST['observacoes_triagem'] ?? ''),
            'prioridade_clinica' => $prioridadeClinica,
            'registado_por'      => $utilizadorId,
        ]);

        // 2. Atualizar prioridade na marcação e na senha se diferente
        $db = Database::ligar();
        $stmtPrio = $db->prepare(
            "UPDATE marcacoes SET prioridade = :prio, atualizado_em = NOW() WHERE id = :id"
        );
        $stmtPrio->execute([':prio' => $prioridadeClinica, ':id' => $marcacaoId]);

        $stmtSenhaPrio = $db->prepare(
            "UPDATE senhas SET prioridade = :prio WHERE marcacao_id = :mid"
        );
        $stmtSenhaPrio->execute([':prio' => $prioridadeClinica, ':mid' => $marcacaoId]);

        Auditoria::registar(
            $utilizadorId,
            'triagem_marcacao',
            "Triagem registada para marcação #{$marcacaoId}"
        );

        $_SESSION['mensagem'] = 'Triagem registada com sucesso.';
        
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'mensagem' => $_SESSION['mensagem']]);
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['erro'] = 'Erro na triagem: ' . $e->getMessage();
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'erro' => $_SESSION['erro']]);
            exit;
        }
    }

    header('Location: ' . $destino);
    exit;
}

// ------------------------------------------------
// REMARCAR
// ------------------------------------------------
if ($acao === 'remarcar') {
    $marcacaoId = (int) ($_POST['marcacao_id'] ?? 0);
    $novaData = trim($_POST['nova_data'] ?? '');
    $novoTurno = trim($_POST['novo_turno'] ?? '');

    if ($marcacaoId <= 0 || empty($novaData) || !in_array($novoTurno, ['manha', 'tarde'])) {
        $_SESSION['erro'] = 'Dados inválidos para remarcação.';
        header('Location: ' . $destino);
        exit;
    }

    try {
        $novoId = Marcacao::remarcar($marcacaoId, $novaData, $novoTurno, $utilizadorId);

        // Cancelar notificações da marcação antiga
        Notificacao::cancelarPorMarcacao($marcacaoId);
        // Criar novas notificações
        Notificacao::criarLembretesParaMarcacao($novoId);

        Auditoria::registar(
            $utilizadorId,
            'remarcar_marcacao',
            "Marcação #{$marcacaoId} → #{$novoId} para {$novaData} ({$novoTurno})"
        );

        $_SESSION['mensagem'] = "Marcação remarcada com sucesso — Nova: #{$novoId}.";

    } catch (RuntimeException $e) {
        $_SESSION['erro'] = $e->getMessage();
    }

    header('Location: ' . $destino);
    exit;
}

// ------------------------------------------------
// CANCELAR
// ------------------------------------------------
if ($acao === 'cancelar') {
    $marcacaoId = (int) ($_POST['marcacao_id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? 'Cancelamento administrativo');

    if ($marcacaoId <= 0) {
        $_SESSION['erro'] = 'Marcação inválida.';
        header('Location: ' . $destino);
        exit;
    }

    if (Marcacao::cancelar($marcacaoId, $motivo, $utilizadorId)) {
        Notificacao::cancelarPorMarcacao($marcacaoId);
        Auditoria::registar(
            $utilizadorId,
            'cancelar_marcacao',
            "Marcação #{$marcacaoId}: {$motivo}"
        );
        $_SESSION['mensagem'] = 'Marcação cancelada.';
    } else {
        $_SESSION['erro'] = 'Não foi possível cancelar esta marcação.';
    }

    header('Location: ' . $destino);
    exit;
}

// ------------------------------------------------
// FALTA
// ------------------------------------------------
if ($acao === 'falta') {
    $marcacaoId = (int) ($_POST['marcacao_id'] ?? 0);

    if ($marcacaoId <= 0) {
        $_SESSION['erro'] = 'Marcação inválida.';
        header('Location: ' . $destino);
        exit;
    }

    if (Marcacao::marcarFalta($marcacaoId, $utilizadorId)) {
        Notificacao::cancelarPorMarcacao($marcacaoId);
        Auditoria::registar(
            $utilizadorId,
            'falta_marcacao',
            "Marcação #{$marcacaoId}: paciente não compareceu"
        );
        $_SESSION['mensagem'] = 'Marcação registada como falta.';
    } else {
        $_SESSION['erro'] = 'Não foi possível registar a falta.';
    }

    header('Location: ' . $destino);
    exit;
}

// Fallback
header('Location: ' . $destino);
exit;
