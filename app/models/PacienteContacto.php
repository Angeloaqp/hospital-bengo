<?php
// ================================================
// Hospital Geral do Bengo — Model: PacienteContacto
// Gestão de contactos dos pacientes para lembretes
// ================================================

require_once __DIR__ . '/../../config/database.php';

class PacienteContacto
{
    /**
     * Listar todos os contactos de um paciente
     */
    public static function listarPorPaciente(int $pacienteId): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT * FROM paciente_contactos
             WHERE paciente_id = :pid AND activo = 1
             ORDER BY principal DESC, tipo ASC"
        );
        $stmt->execute([':pid' => $pacienteId]);
        return $stmt->fetchAll();
    }

    /**
     * Guardar contactos de um paciente (substituir todos)
     * Recebe array de contactos:
     * [['tipo'=>'telefone','valor'=>'9xx','consentimento'=>1,'principal'=>1], ...]
     */
    public static function guardarContactos(int $pacienteId, array $contactos): void
    {
        $db = Database::ligar();
        $inTrans = $db->inTransaction();
        if (!$inTrans) {
            $db->beginTransaction();
        }

        try {
            // Desactivar os antigos
            $stmt = $db->prepare(
                "UPDATE paciente_contactos SET activo = 0
                 WHERE paciente_id = :pid"
            );
            $stmt->execute([':pid' => $pacienteId]);

            // Inserir novos
            $stmtInsert = $db->prepare(
                "INSERT INTO paciente_contactos
                    (paciente_id, tipo, valor, nome_contacto,
                     principal, consentimento, activo)
                 VALUES
                    (:pid, :tipo, :valor, :nome, :princ, :cons, 1)"
            );

            foreach ($contactos as $c) {
                if (empty($c['valor'])) continue;
                $stmtInsert->execute([
                    ':pid'   => $pacienteId,
                    ':tipo'  => $c['tipo'],
                    ':valor' => trim($c['valor']),
                    ':nome'  => $c['nome_contacto'] ?? null,
                    ':princ' => (int) ($c['principal'] ?? 0),
                    ':cons'  => (int) ($c['consentimento'] ?? 0),
                ]);
            }

            if (!$inTrans) {
                $db->commit();
            }
        } catch (Exception $e) {
            if (!$inTrans) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Contactos com consentimento de envio
     * Devolve apenas os que autorizaram lembretes
     */
    public static function contactosComConsentimento(int $pacienteId): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT * FROM paciente_contactos
             WHERE paciente_id = :pid
             AND activo = 1
             AND consentimento = 1
             AND tipo IN ('telefone','whatsapp','email')
             ORDER BY principal DESC"
        );
        $stmt->execute([':pid' => $pacienteId]);
        return $stmt->fetchAll();
    }
}
