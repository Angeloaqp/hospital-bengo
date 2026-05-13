<?php
// ================================================
// Hospital Geral do Bengo
// Controller: Atendimento (descontinuado)
// A lógica de atendimento foi migrada para
// app/controllers/senhas.php e prontuario.php.
// Este ficheiro mantém-se como redirect de segurança.
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/sessao.php';

header('Location: ' . BASE_URL . 'public/index.php');
exit;
