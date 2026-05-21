<?php
// ================================================
// Hospital Geral do Bengo — Model: Triagem
// Registo de triagem no check-in
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Triagem
{
    /**
     * Criar ou atualizar triagem para uma marcação
     */
    public static function criarOuAtualizar(int $marcacaoId, array $dados): int
    {
        $db = Database::ligar();

        // Verificar se já existe
        $stmt = $db->prepare(
            "SELECT id FROM triagens WHERE marcacao_id = :mid LIMIT 1"
        );
        $stmt->execute([':mid' => $marcacaoId]);
        $existente = $stmt->fetch();

        if ($existente) {
            // Atualizar
            $stmt2 = $db->prepare(
                "UPDATE triagens SET
                    sintomas = :sint,
                    temperatura = :temp,
                    pressao_arterial = :pa,
                    peso = :peso,
                    frequencia_cardiaca = :fc,
                    observacoes = :obs,
                    prioridade_clinica = :prio
                 WHERE id = :id"
            );
            $stmt2->execute([
                ':sint' => $dados['sintomas'] ?? null,
                ':temp' => !empty($dados['temperatura']) ? (float) $dados['temperatura'] : null,
                ':pa'   => $dados['pressao_arterial'] ?? null,
                ':peso' => !empty($dados['peso']) ? (float) $dados['peso'] : null,
                ':fc'   => !empty($dados['frequencia_cardiaca']) ? (int) $dados['frequencia_cardiaca'] : null,
                ':obs'  => $dados['observacoes'] ?? null,
                ':prio' => (int) ($dados['prioridade_clinica'] ?? 4),
                ':id'   => (int) $existente['id'],
            ]);
            return (int) $existente['id'];
        }

        // Criar nova
        $stmt3 = $db->prepare(
            "INSERT INTO triagens
                (marcacao_id, paciente_id, sintomas, temperatura,
                 pressao_arterial, peso, frequencia_cardiaca,
                 observacoes, prioridade_clinica, registado_por)
             VALUES
                (:mid, :pid, :sint, :temp,
                 :pa, :peso, :fc,
                 :obs, :prio, :reg)"
        );
        $stmt3->execute([
            ':mid'  => $marcacaoId,
            ':pid'  => (int) $dados['paciente_id'],
            ':sint' => $dados['sintomas'] ?? null,
            ':temp' => !empty($dados['temperatura']) ? (float) $dados['temperatura'] : null,
            ':pa'   => $dados['pressao_arterial'] ?? null,
            ':peso' => !empty($dados['peso']) ? (float) $dados['peso'] : null,
            ':fc'   => !empty($dados['frequencia_cardiaca']) ? (int) $dados['frequencia_cardiaca'] : null,
            ':obs'  => $dados['observacoes'] ?? null,
            ':prio' => (int) ($dados['prioridade_clinica'] ?? 4),
            ':reg'  => (int) $dados['registado_por'],
        ]);
        return (int) $db->lastInsertId();
    }

    /**
     * Obter triagem por marcação
     */
    public static function obterPorMarcacao(int $marcacaoId): ?array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT t.*, u.nome AS registado_por_nome
             FROM triagens t
             JOIN utilizadores u ON t.registado_por = u.id
             WHERE t.marcacao_id = :mid
             LIMIT 1"
        );
        $stmt->execute([':mid' => $marcacaoId]);
        $r = $stmt->fetch();
        return $r ?: null;
    }
}
