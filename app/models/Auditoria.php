<?php
// ================================================
// Hospital Geral do Bengo — Model: Auditoria
// Registo e consulta de logs de acções
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Auditoria
{

    /**
     * Regista uma acção no log
     */
    public static function registar(
        int $userId,
        string $accao,
        ?string $detalhes = null
    ): void {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "INSERT INTO logs_auditoria
                (utilizador_id, accao, detalhes, ip)
             VALUES (:uid, :accao, :det, :ip)"
        );
        $stmt->execute([
            ':uid' => $userId,
            ':accao' => $accao,
            ':det' => $detalhes,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    /**
     * Lista logs com filtros opcionais
     */
    public static function listar(
        int $limite = 50,
        ?string $filtroAccao = null,
        ?int $filtroUser = null,
        ?string $dataInicio = null,
        ?string $dataFim = null
    ): array {
        $db = Database::ligar();
        $sql = "SELECT l.id, l.accao, l.detalhes,
                       l.ip, l.criado_em,
                       u.nome AS utilizador_nome,
                       u.perfil
                FROM logs_auditoria l
                JOIN utilizadores u
                     ON l.utilizador_id = u.id
                WHERE 1=1";
        $params = [];

        if ($filtroAccao) {
            $sql .= " AND l.accao LIKE :accao";
            $params[':accao'] = '%' . $filtroAccao . '%';
        }
        if ($filtroUser) {
            $sql .= " AND l.utilizador_id = :uid";
            $params[':uid'] = $filtroUser;
        }
        if ($dataInicio) {
            $sql .= " AND DATE(l.criado_em) >= :di";
            $params[':di'] = $dataInicio;
        }
        if ($dataFim) {
            $sql .= " AND DATE(l.criado_em) <= :df";
            $params[':df'] = $dataFim;
        }

        $sql .= " ORDER BY l.criado_em DESC LIMIT :lim";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Conta total de logs hoje
     */
    public static function totalHoje(): int
    {
        $db = Database::ligar();
        return (int) $db->query(
            "SELECT COUNT(*) FROM logs_auditoria
             WHERE DATE(criado_em) = CURDATE()"
        )->fetchColumn();
    }

    /**
     * Lista utilizadores para filtro
     */
    public static function utilizadoresParaFiltro(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT DISTINCT u.id, u.nome, u.perfil
             FROM logs_auditoria l
             JOIN utilizadores u ON l.utilizador_id = u.id
             ORDER BY u.nome"
        )->fetchAll();
    }
}
