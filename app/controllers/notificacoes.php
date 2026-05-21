<?php
// ================================================
// Hospital Geral do Bengo — Controller: Notificações
// Listar, reenviar, cancelar notificações
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Notificacao.php';
require_once __DIR__ . '/../../app/models/Notificador.php';
require_once __DIR__ . '/../../app/models/Auditoria.php';

exigirPerfil(['recepcionista', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'app/views/recepcionista/agenda.php');
    exit;
}

validarTokenCsrf();

$acao = trim($_POST['acao'] ?? '');
$utilizadorId = (int) sessao('utilizador_id');
$destino = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'app/views/recepcionista/agenda.php';

// ------------------------------------------------
// REENVIAR notificação manualmente
// ------------------------------------------------
if ($acao === 'reenviar') {
    $notifId = (int) ($_POST['notificacao_id'] ?? 0);

    if ($notifId <= 0) {
        $_SESSION['erro'] = 'Notificação inválida.';
        header('Location: ' . $destino);
        exit;
    }

    $db = Database::ligar();
    $stmt = $db->prepare("SELECT * FROM notificacoes WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $notifId]);
    $notif = $stmt->fetch();

    if (!$notif) {
        $_SESSION['erro'] = 'Notificação não encontrada.';
        header('Location: ' . $destino);
        exit;
    }

    // Resetar para pendente e tentar enviar
    $stmtReset = $db->prepare(
        "UPDATE notificacoes SET estado = 'pendente', tentativas = 0, agendada_para = NOW() WHERE id = :id"
    );
    $stmtReset->execute([':id' => $notifId]);

    $resultado = Notificador::enviar($notif);

    if ($resultado['sucesso']) {
        Notificacao::marcarEnviada($notifId);
        $_SESSION['mensagem'] = 'Notificação reenviada com sucesso.';
    } else {
        Notificacao::marcarFalhada($notifId, $resultado['erro']);
        $_SESSION['erro'] = 'Falha ao reenviar: ' . $resultado['erro'];
    }

    Auditoria::registar($utilizadorId, 'reenviar_notificacao', "Notificação #{$notifId}");
    header('Location: ' . $destino);
    exit;
}

// ------------------------------------------------
// CANCELAR notificação
// ------------------------------------------------
if ($acao === 'cancelar') {
    $notifId = (int) ($_POST['notificacao_id'] ?? 0);

    if ($notifId <= 0) {
        $_SESSION['erro'] = 'Notificação inválida.';
        header('Location: ' . $destino);
        exit;
    }

    $db = Database::ligar();
    $stmt = $db->prepare(
        "UPDATE notificacoes SET estado = 'cancelada' WHERE id = :id AND estado IN ('pendente','falhada')"
    );
    $stmt->execute([':id' => $notifId]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['mensagem'] = 'Notificação cancelada.';
        Auditoria::registar($utilizadorId, 'cancelar_notificacao', "Notificação #{$notifId}");
    } else {
        $_SESSION['erro'] = 'Não foi possível cancelar esta notificação.';
    }

    header('Location: ' . $destino);
    exit;
}

// Fallback
header('Location: ' . $destino);
exit;
