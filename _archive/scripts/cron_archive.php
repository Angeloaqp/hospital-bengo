<?php
// ================================================
// Hospital Geral do Bengo
// Cronjob: Database Archiving (Resilience & Optimization)
// Arquiva senhas e prontuários mais antigos que 30 dias
// ================================================

require_once __DIR__ . '/../config/database.php';

echo "Iniciando processo de arquivamento de dados antigos...\n";

try {
    $db = Database::ligar();
    
    // 1. Criar tabelas de arquivo (o modificador LIKE copia a estrutura e índices, mas descarta chaves estrangeiras)
    $db->exec("CREATE TABLE IF NOT EXISTS senhas_arquivo LIKE senhas");
    $db->exec("CREATE TABLE IF NOT EXISTS prontuarios_arquivo LIKE prontuarios");

    $db->beginTransaction();

    // 2. Copiar senhas antigas (> 30 dias) e concluídas/canceladas para o arquivo
    $stmt = $db->query("
        INSERT IGNORE INTO senhas_arquivo 
        SELECT * FROM senhas 
        WHERE criado_em < DATE_SUB(NOW(), INTERVAL 30 DAY) 
          AND estado IN ('concluida', 'cancelada')
    ");
    $senhasArquivadas = $stmt->rowCount();

    // 3. Copiar prontuários correspondentes
    $stmt = $db->query("
        INSERT IGNORE INTO prontuarios_arquivo 
        SELECT p.* FROM prontuarios p
        JOIN senhas s ON p.senha_id = s.id
        WHERE s.criado_em < DATE_SUB(NOW(), INTERVAL 30 DAY) 
          AND s.estado IN ('concluida', 'cancelada')
    ");
    $prontuariosArquivados = $stmt->rowCount();

    // 4. Eliminar prontuários da tabela original
    // (Tem de ser antes das senhas devido ao ON DELETE RESTRICT/CASCADE)
    $stmt = $db->query("
        DELETE p FROM prontuarios p
        JOIN senhas s ON p.senha_id = s.id
        WHERE s.criado_em < DATE_SUB(NOW(), INTERVAL 30 DAY) 
          AND s.estado IN ('concluida', 'cancelada')
    ");
    $prontuariosApagados = $stmt->rowCount();

    // 5. Eliminar senhas da tabela original
    $stmt = $db->query("
        DELETE FROM senhas 
        WHERE criado_em < DATE_SUB(NOW(), INTERVAL 30 DAY) 
          AND estado IN ('concluida', 'cancelada')
    ");
    $senhasApagadas = $stmt->rowCount();

    $db->commit();
    
    echo "Arquivamento concluído com sucesso.\n";
    echo "Resumo da Operação:\n";
    echo "- Senhas movidas para arquivo: $senhasArquivadas\n";
    echo "- Prontuários movidos para arquivo: $prontuariosArquivados\n";
    echo "- Registos originais limpos: " . ($senhasApagadas + $prontuariosApagados) . "\n";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "ERRO CRÍTICO durante o arquivamento: " . $e->getMessage() . "\n";
}
