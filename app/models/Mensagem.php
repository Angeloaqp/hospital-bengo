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
             WHERE m.destinatario_id = :uid
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
             WHERE m.remetente_id = :uid
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
             WHERE destinatario_id = :uid AND lida = 0"
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
}
