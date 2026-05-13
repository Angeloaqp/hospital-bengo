<?php
// ================================================
// Hospital Geral do Bengo — Model: Mensagem
// Sistema de Comunicação Interna
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Mensagem
{
    /**
     * Envia uma nova mensagem
     */
    public static function enviar(
        int $remetente,
        int $destinatario,
        string $assunto,
        string $conteudo
    ): bool {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "INSERT INTO mensagens 
                (remetente_id, destinatario_id, assunto, conteudo)
             VALUES 
                (:rem, :dest, :ass, :cont)"
        );
        return $stmt->execute([
            ':rem' => $remetente,
            ':dest' => $destinatario,
            ':ass' => trim($assunto),
            ':cont' => trim($conteudo)
        ]);
    }

    /**
     * Caixa de entrada (mensagens recebidas pelo utilizador)
     */
    public static function caixaDeEntrada(int $userId): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT m.id, m.assunto, m.conteudo, m.lida, m.criado_em,
                    u.nome AS remetente_nome, u.perfil AS remetente_perfil
             FROM mensagens m
             JOIN utilizadores u ON m.remetente_id = u.id
             WHERE m.destinatario_id = :uid AND m.apagada_destinatario = 0
             ORDER BY m.criado_em DESC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Mensagens enviadas pelo utilizador
     */
    public static function caixaDeSaida(int $userId): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT m.id, m.assunto, m.conteudo, m.lida, m.criado_em,
                    u.nome AS destinatario_nome, u.perfil AS destinatario_perfil
             FROM mensagens m
             JOIN utilizadores u ON m.destinatario_id = u.id
             WHERE m.remetente_id = :uid AND m.apagada_remetente = 0
             ORDER BY m.criado_em DESC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Conta mensagens não lidas
     */
    public static function contarNaoLidas(int $userId): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM mensagens 
             WHERE destinatario_id = :uid AND lida = 0 AND apagada_destinatario = 0"
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca uma mensagem como lida
     */
    public static function marcarComoLida(int $msgId, int $userId): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE mensagens SET lida = 1 
             WHERE id = :id AND destinatario_id = :uid"
        );
        $stmt->execute([
            ':id' => $msgId,
            ':uid' => $userId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Apaga uma mensagem (soft delete) para o utilizador especificado
     */
    public static function apagar(int $msgId, int $userId): bool
    {
        $db = Database::ligar();
        // Verificar se é remetente ou destinatário
        $stmt = $db->prepare("SELECT remetente_id, destinatario_id FROM mensagens WHERE id = :id");
        $stmt->execute([':id' => $msgId]);
        $msg = $stmt->fetch();

        if (!$msg) return false;

        if ($msg['remetente_id'] === $userId) {
            $upd = $db->prepare("UPDATE mensagens SET apagada_remetente = 1 WHERE id = :id");
            $upd->execute([':id' => $msgId]);
        }
        if ($msg['destinatario_id'] === $userId) {
            $upd = $db->prepare("UPDATE mensagens SET apagada_destinatario = 1 WHERE id = :id");
            $upd->execute([':id' => $msgId]);
        }

        // Se ambos apagaram, podemos eliminar o registo definitivo para poupar espaço
        $del = $db->prepare("DELETE FROM mensagens WHERE id = :id AND apagada_remetente = 1 AND apagada_destinatario = 1");
        $del->execute([':id' => $msgId]);

        return true;
    }

    /**
     * Obtém detalhes de uma mensagem (verificando se o utilizador tem permissão)
     */
    public static function obter(int $msgId, int $userId): ?array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT m.*, 
                    rem.nome AS remetente_nome, rem.perfil AS remetente_perfil,
                    dest.nome AS destinatario_nome, dest.perfil AS destinatario_perfil
             FROM mensagens m
             JOIN utilizadores rem ON m.remetente_id = rem.id
             JOIN utilizadores dest ON m.destinatario_id = dest.id
             WHERE m.id = :id AND (m.remetente_id = :uid1 OR m.destinatario_id = :uid2)
             LIMIT 1"
        );
        $stmt->execute([
            ':id' => $msgId,
            ':uid1' => $userId,
            ':uid2' => $userId
        ]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /**
     * Lista utilizadores activos para enviar mensagem (exclui o próprio utilizador logado)
     */
    public static function destinatarios(int $excluirId): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT id, nome, perfil 
             FROM utilizadores 
             WHERE estado = 1 AND id != :id
             ORDER BY perfil ASC, nome ASC"
        );
        $stmt->execute([':id' => $excluirId]);
        return $stmt->fetchAll();
    }

    /**
     * Lixo: mensagens apagadas pelo utilizador (soft-deleted)
     */
    public static function lixo(int $userId): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT m.id, m.assunto, m.conteudo, m.lida, m.criado_em,
                    m.remetente_id, m.destinatario_id,
                    rem.nome AS remetente_nome,
                    dest.nome AS destinatario_nome,
                    CASE 
                        WHEN m.remetente_id = :uid1 THEN 'enviada'
                        ELSE 'recebida'
                    END AS tipo
             FROM mensagens m
             JOIN utilizadores rem ON m.remetente_id = rem.id
             JOIN utilizadores dest ON m.destinatario_id = dest.id
             WHERE (m.remetente_id = :uid2 AND m.apagada_remetente = 1)
                OR (m.destinatario_id = :uid3 AND m.apagada_destinatario = 1)
             ORDER BY m.criado_em DESC"
        );
        $stmt->execute([':uid1' => $userId, ':uid2' => $userId, ':uid3' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Restaurar uma mensagem do lixo (reverter soft delete)
     */
    public static function restaurar(int $msgId, int $userId): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare("SELECT remetente_id, destinatario_id FROM mensagens WHERE id = :id");
        $stmt->execute([':id' => $msgId]);
        $msg = $stmt->fetch();

        if (!$msg) return false;

        if ($msg['remetente_id'] === $userId) {
            $upd = $db->prepare("UPDATE mensagens SET apagada_remetente = 0 WHERE id = :id");
            $upd->execute([':id' => $msgId]);
            return true;
        }
        if ($msg['destinatario_id'] === $userId) {
            $upd = $db->prepare("UPDATE mensagens SET apagada_destinatario = 0 WHERE id = :id");
            $upd->execute([':id' => $msgId]);
            return true;
        }

        return false;
    }

    /**
     * Conta mensagens no lixo do utilizador
     */
    public static function contarLixo(int $userId): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM mensagens
             WHERE (remetente_id = :uid1 AND apagada_remetente = 1)
                OR (destinatario_id = :uid2 AND apagada_destinatario = 1)"
        );
        $stmt->execute([':uid1' => $userId, ':uid2' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
