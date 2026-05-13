<?php
// ================================================
// Hospital Geral do Bengo - Modelo: Prontuario
// ================================================

class Prontuario
{
    /**
     * Obter o prontuário associado a uma senha
     */
    public static function obterPorSenha(int $senhaId): ?array
    {
        $db = Database::ligar();
        $stmt = $db->prepare("SELECT * FROM prontuarios WHERE senha_id = :senha_id LIMIT 1");
        $stmt->bindValue(':senha_id', $senhaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Obter o ID do paciente de uma determinada senha
     */
    public static function pacienteDaSenha(int $senhaId): ?int
    {
        $db = Database::ligar();
        $stmt = $db->prepare("SELECT paciente_id FROM senhas WHERE id = :senha_id LIMIT 1");
        $stmt->bindValue(':senha_id', $senhaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? (int)$result['paciente_id'] : null;
    }

    /**
     * Histórico de consultas de um paciente
     */
    public static function historicoPaciente(int $pacienteId, int $limite = 10): array
    {
        $db = Database::ligar();
        $sql = "SELECT p.*, s.codigo, t.nome as tipo_atendimento, u.nome as medico_nome
                FROM prontuarios p
                JOIN senhas s ON p.senha_id = s.id
                LEFT JOIN tipos_atendimento t ON s.tipo_atendimento_id = t.id
                LEFT JOIN utilizadores u ON p.medico_id = u.id
                WHERE p.paciente_id = :pac_id
                ORDER BY p.criado_em DESC
                LIMIT :limite";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':pac_id', $pacienteId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Dados demográficos do paciente
     */
    public static function dadosPaciente(int $pacienteId): ?array
    {
        $db = Database::ligar();
        $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $pacienteId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Atualizar um prontuário existente
     */
    public static function atualizar(int $prontuarioId, string $notas, string $prescricao, string $diagnostico): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare("UPDATE prontuarios 
                              SET notas_clinicas = :notas, 
                                  prescricao = :pres, 
                                  diagnostico = :diag 
                              WHERE id = :id");
        return $stmt->execute([
            ':notas' => $notas,
            ':pres'  => $prescricao,
            ':diag'  => $diagnostico,
            ':id'    => $prontuarioId
        ]);
    }

    /**
     * Criar um novo prontuário
     */
    public static function criar(int $senhaId, int $pacienteId, int $medicoId, string $notas, string $prescricao, string $diagnostico): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare("INSERT INTO prontuarios 
                              (senha_id, paciente_id, medico_id, notas_clinicas, prescricao, diagnostico) 
                              VALUES (:senha_id, :paciente_id, :medico_id, :notas, :pres, :diag)");
        $stmt->execute([
            ':senha_id'    => $senhaId,
            ':paciente_id' => $pacienteId,
            ':medico_id'   => $medicoId,
            ':notas'       => $notas,
            ':pres'        => $prescricao,
            ':diag'        => $diagnostico
        ]);
        return (int) $db->lastInsertId();
    }
}
