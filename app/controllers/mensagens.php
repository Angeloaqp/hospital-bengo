<?php
// ================================================
// Hospital Geral do Bengo
// Controller: Mensagens Internas
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Mensagem.php';

// Apenas autenticados podem enviar e receber mensagens
exigirPerfil(['admin', 'medico', 'recepcionista']);

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
$meuId = (int) sessao('utilizador_id');
$meuPerfil = sessao('perfil');

// Define redirecionamento baseado no perfil (para voltar à caixa correctamente)
$redirCaixa = BASE_URL . 'app/views/comum/mensagens.php';

// ------------------------------------------------
// ACÇÃO: Enviar mensagem
// ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'enviar') {
    $destinatarios = $_POST['destinatarios'] ?? []; // Pode ser array se escolhermos múltiplos
    $assunto = $_POST['assunto'] ?? '';
    $conteudo = $_POST['conteudo'] ?? '';

    // Se for enviado apenas um (como drop-down), transforma em array
    if (!is_array($destinatarios) && !empty($destinatarios)) {
        $destinatarios = [$destinatarios];
    }

    $erros = [];
    if (empty($destinatarios)) {
        $erros[] = "Selecione pelo menos um destinatário.";
    }
    if (empty(trim($assunto))) {
        $erros[] = "O assunto não pode estar vazio.";
    }
    if (empty(trim($conteudo))) {
        $erros[] = "A mensagem não pode estar vazia.";
    }

    if (!empty($erros)) {
        $_SESSION['erro'] = implode(" ", $erros);
        $_SESSION['form_msg_assunto'] = $assunto;
        $_SESSION['form_msg_conteudo'] = $conteudo;
        header("Location: {$redirCaixa}?tab=escrever");
        exit;
    }

    $enviados = 0;
    foreach ($destinatarios as $dest_id) {
        $dest_id = (int) $dest_id;
        if ($dest_id > 0) {
            if (Mensagem::enviar($meuId, $dest_id, $assunto, $conteudo)) {
                $enviados++;
            }
        }
    }

    if ($enviados > 0) {
        $_SESSION['mensagem'] = "Mensagem enviada com sucesso para {$enviados} pessoa(s).";
    } else {
        $_SESSION['erro'] = "Nenhuma mensagem pôde ser enviada.";
    }
    header("Location: {$redirCaixa}");
    exit;
}

// ------------------------------------------------
// ACÇÃO: Marcar como lida via GET
// ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $acao === 'ler') {
    $msgId = (int) ($_GET['id'] ?? 0);
    if ($msgId > 0) {
        Mensagem::marcarComoLida($msgId, $meuId);
    }
    header("Location: {$redirCaixa}?tab=ler&id={$msgId}");
    exit;
}

// Fallback
header("Location: {$redirCaixa}");
exit;
