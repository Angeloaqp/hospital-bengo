<?php
// Script de testes manuais para validação de conectividade e controladores.

$urlBase = "http://localhost/hospital-bengo/public/index.php";
$testesPassados = 0;
$testesFalhados = 0;

function assertTest($nome, $condicao, $mensagemErro = "Falhou") {
    global $testesPassados, $testesFalhados;
    if ($condicao) {
        echo "[ PASS ] $nome\n";
        $testesPassados++;
    } else {
        echo "[ FAIL ] $nome - $mensagemErro\n";
        $testesFalhados++;
    }
}

echo "=== A INICIAR TESTES LOCAIS (HOSPITAL GERAL DO BENGO) ===\n\n";

// 1. Teste de Ficheiros de Configuração
$configDbExists = file_exists(__DIR__ . '/config/database.php');
assertTest("Ficheiro de configuração da BD existe", $configDbExists);

// 2. Teste de Conexão à Base de Dados
if ($configDbExists) {
    require_once __DIR__ . '/config/database.php';
    try {
        $db = Database::ligar();
        assertTest("Ligação à Base de Dados efetuada com sucesso", $db instanceof PDO);
    } catch (Exception $e) {
        assertTest("Ligação à Base de Dados efetuada com sucesso", false, $e->getMessage());
    }
}

// 3. Teste de HTTP (Requer que o Apache esteja a correr via XAMPP)
$ch = curl_init($urlBase);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // Head request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

assertTest("O servidor Web está a responder (Página de Login)", $httpCode === 200 || $httpCode === 302, "Código HTTP recebido: $httpCode");

// Resumo
echo "\n=== RESUMO ===\n";
echo "Passaram: $testesPassados\n";
echo "Falharam: $testesFalhados\n";

if ($testesFalhados === 0) {
    echo "ESTADO: SUCESSO (O sistema base está operacional)\n";
} else {
    echo "ESTADO: FALHOU (Verifique os erros acima)\n";
}
