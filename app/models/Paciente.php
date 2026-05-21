<?php
// ================================================
// Hospital Geral do Bengo — Model: Paciente
// ================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Senha.php';

class Paciente
{

    /**
     * Regista um novo paciente e emite a senha.
     * Devolve o código da senha gerada ou lança excepção.
     *
     * @param array $dados Dados validados do formulário
     * @param int   $registadoPor ID do utilizador logado
     * @return string Código da senha (ex: U-003)
     */
    public static function registarComSenha(
        array $dados,
        int $registadoPor
    ): string {
        $db = Database::ligar();

        // Inicia transacção — ambos os INSERTs 
        // devem ser bem-sucedidos
        $db->beginTransaction();

        try {
            // 1. Insere o paciente
            $stmt = $db->prepare(
                "INSERT INTO pacientes 
                    (nome, idade, morada, peso, registado_por)
                 VALUES 
                    (:nome, :idade, :morada, :peso, :reg_por)"
            );
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':idade' => (int) $dados['idade'],
                ':morada' => $dados['morada'],
                ':peso' => !empty($dados['peso'])
                    ? (float) $dados['peso']
                    : null,
                ':reg_por' => $registadoPor,
            ]);

            $pacienteId = (int) $db->lastInsertId();

            // 2. Gera o código da senha
            $prioridade = (int) $dados['prioridade'];
            $codigo = Senha::gerarCodigo($prioridade);

            // 3. Insere a senha
            $stmt2 = $db->prepare(
                "INSERT INTO senhas 
                    (codigo, paciente_id, tipo_atendimento_id,
                     prioridade, estado, registado_por)
                 VALUES 
                    (:codigo, :pac_id, :tipo_id,
                     :prioridade, 'espera', :reg_por)"
            );
            $stmt2->execute([
                ':codigo' => $codigo,
                ':pac_id' => $pacienteId,
                ':tipo_id' => (int) $dados['tipo_atendimento_id'],
                ':prioridade' => $prioridade,
                ':reg_por' => $registadoPor,
            ]);

            $db->commit();
            return $codigo;

        } catch (PDOException $e) {
            $db->rollBack();
            throw new RuntimeException(
                'Erro ao registar paciente: ' . $e->getMessage()
            );
        }
    }

    /**
     * Regista um novo paciente sem emitir senha.
     * Devolve o ID do paciente recém-criado.
     *
     * @param array $dados Dados validados do formulário
     * @param int   $registadoPor ID do utilizador logado
     * @return int ID do paciente
     */
    public static function registarApenas(
        array $dados,
        int $registadoPor
    ): int {
        $db = Database::ligar();

        try {
            $stmt = $db->prepare(
                "INSERT INTO pacientes 
                    (nome, bi_nif, idade, sexo, morada, peso, registado_por)
                 VALUES 
                    (:nome, :bi_nif, :idade, :sexo, :morada, :peso, :reg_por)"
            );
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':bi_nif' => !empty($dados['bi_nif']) ? $dados['bi_nif'] : null,
                ':idade' => (int) $dados['idade'],
                ':sexo' => !empty($dados['sexo']) ? $dados['sexo'] : null,
                ':morada' => $dados['morada'],
                ':peso' => !empty($dados['peso'])
                    ? (float) $dados['peso']
                    : null,
                ':reg_por' => $registadoPor,
            ]);

            return (int) $db->lastInsertId();

        } catch (PDOException $e) {
            throw new RuntimeException(
                'Erro ao registar paciente: ' . $e->getMessage()
            );
        }
    }

    /**
     * Devolve todos os tipos de atendimento activos
     */
    public static function tiposAtendimento(): array
    {
        $db = Database::ligar();
        return $db->query(
            "SELECT id, nome, prefixo 
             FROM tipos_atendimento 
             WHERE activo = 1 
             ORDER BY id"
        )->fetchAll();
    }
    /**
     * Atualiza os dados demográficos base do paciente
     */
    public static function atualizarApenas(
        int $pacienteId,
        array $dados
    ): bool {
        $db = Database::ligar();

        try {
            $stmt = $db->prepare(
                "UPDATE pacientes SET 
                    nome = :nome,
                    bi_nif = :bi_nif,
                    idade = :idade,
                    sexo = :sexo,
                    morada = :morada,
                    peso = :peso
                 WHERE id = :id"
            );
            return $stmt->execute([
                ':nome' => $dados['nome'],
                ':bi_nif' => !empty($dados['bi_nif']) ? $dados['bi_nif'] : null,
                ':idade' => (int) $dados['idade'],
                ':sexo' => !empty($dados['sexo']) ? $dados['sexo'] : null,
                ':morada' => $dados['morada'],
                ':peso' => !empty($dados['peso']) ? (float) $dados['peso'] : null,
                ':id' => $pacienteId
            ]);

        } catch (PDOException $e) {
            throw new RuntimeException(
                'Erro ao atualizar paciente: ' . $e->getMessage()
            );
        }
    }
}
