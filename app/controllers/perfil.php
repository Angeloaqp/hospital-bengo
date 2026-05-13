<?php
// ================================================
// Hospital Geral do Bengo
// Controller: Perfil (Alteração de Dados e Upload)
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Utilizador.php';
require_once __DIR__ . '/../../app/models/Auditoria.php';

// Protege (precisa ter sessão)
exigirPerfil(['admin', 'medico', 'recepcionista']);

$acao = $_POST['acao'] ?? '';
$meuId = (int) sessao('utilizador_id');
$meuPerfil = sessao('perfil');

// View do perfil
$urlVoltar = BASE_URL . 'app/views/perfil/editar.php';

// Limites de Upload
$maxSize = 2 * 1024 * 1024; // 2MB
$formatosPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

// ------------------------------------------------
// ACÇÃO: Actualizar Perfil e Foto
// ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'actualizar') {
    validarTokenCsrf();
    $nome = $_POST['nome'] ?? '';
    // Apenas Médicos e Recepcionistas usam telefone na listagem, mas podemos guardar generalizado
    $telefone = $_POST['telefone'] ?? null;

    if (empty(trim($nome))) {
        $_SESSION['erro'] = "O nome é obrigatório.";
        header('Location: ' . $urlVoltar);
        exit;
    }

    $fotoPathSql = null;

    // Tratamento de Upload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $img = $_FILES['foto'];

        // Verifica tamanho
        if ($img['size'] > $maxSize) {
            $_SESSION['erro'] = "A foto tem de ter no máximo 2MB.";
            header('Location: ' . $urlVoltar);
            exit;
        }

        // Verifica o tipo (MIME) via finfo (validação server-side)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($img['tmp_name']);
        if (!in_array($mimeReal, $formatosPermitidos)) {
            $_SESSION['erro'] = "São permitidas apenas fotos nos formatos JPG, PNG ou WEBP.";
            header('Location: ' . $urlVoltar);
            exit;
        }

        // Criar pasta se não existir (na root/public/uploads/fotos)
        $pastaDestino = __DIR__ . '/../../public/uploads/fotos/';
        if (!is_dir($pastaDestino)) {
            mkdir($pastaDestino, 0755, true);
        }

        // Gera um nome único -> id_utilizador_timestamp.ext
        $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
        $nomeArquivo = "perfil_{$meuId}_" . time() . "." . strtolower($ext);
        $caminhoFinal = $pastaDestino . $nomeArquivo;

        if (move_uploaded_file($img['tmp_name'], $caminhoFinal)) {
            // O path a gravar na BD: public/uploads/fotos/nome_ficheiro.ext
            $fotoPathSql = 'uploads/fotos/' . $nomeArquivo;

            // Delete a foto antiga, se existir (opicional mas boa prática)
            $perfilAntigo = Utilizador::obter($meuId);
            if ($perfilAntigo && $perfilAntigo['foto_path']) {
                $fotoAntigaReal = __DIR__ . '/../../public/' . $perfilAntigo['foto_path'];
                if (file_exists($fotoAntigaReal)) {
                    @unlink($fotoAntigaReal);
                }
            }
        } else {
            $_SESSION['erro'] = "Acorreu um erro a guardar a imagem de perfil.";
            header('Location: ' . $urlVoltar);
            exit;
        }
    }

    // Actualiza
    if (Utilizador::actualizarPerfil($meuId, $nome, $telefone, $fotoPathSql)) {
        // Renova o nome da sessão também
        $_SESSION['utilizador_nome'] = $nome;
        $_SESSION['mensagem'] = "O teu perfil foi actualizado!";
        Auditoria::registar($meuId, 'editar_perfil', 'Alterações guardadas no próprio perfil');
    } else {
        $_SESSION['erro'] = "Falha ao gravar no banco de dados.";
    }

    header('Location: ' . $urlVoltar);
    exit;
}

// ------------------------------------------------
// ACÇÃO: Alterar Senha Segura
// ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'senha') {
    validarTokenCsrf();
    $senha_antiga = $_POST['senha_antiga'] ?? '';
    $senha_nova = $_POST['senha_nova'] ?? '';
    $senha_conf = $_POST['senha_conf'] ?? '';

    if (empty($senha_antiga) || empty($senha_nova) || empty($senha_conf)) {
        $_SESSION['erro_senha'] = "Deves preencher todos os campos de senha.";
        header('Location: ' . $urlVoltar . '#card-senha');
        exit;
    }

    if ($senha_nova !== $senha_conf) {
        $_SESSION['erro_senha'] = "A nova senha e a confirmação não coincidem.";
        header('Location: ' . $urlVoltar . '#card-senha');
        exit;
    }

    if (strlen($senha_nova) < 6) {
        $_SESSION['erro_senha'] = "A nova senha deve ter pelo menos 6 caracteres.";
        header('Location: ' . $urlVoltar . '#card-senha');
        exit;
    }

    if (Utilizador::alterarSenha($meuId, $senha_antiga, $senha_nova)) {
        $_SESSION['mensagem_senha'] = "A tua senha foi alterada com segurança!";
        Auditoria::registar($meuId, 'mudar_senha', 'Alteração silenciosa da passe');
    } else {
        $_SESSION['erro_senha'] = "A senha actual que introduziste está errada.";
    }

    header('Location: ' . $urlVoltar . '#card-senha');
    exit;
}

// Não achou a acção
header('Location: ' . BASE_URL . "app/views/{$meuPerfil}/dashboard.php");
exit;
