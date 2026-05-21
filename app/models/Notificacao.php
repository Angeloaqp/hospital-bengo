<?php
// ================================================
// Hospital Geral do Bengo — Model: Notificacao
// Gestão de lembretes e notificações
// ================================================

require_once __DIR__ . '/../../config/database.php';

class Notificacao
{
    /**
     * Criar lembretes para uma marcação
     * Gera: 24h antes + manhã da consulta
     * Para cada contacto com consentimento
     */
    public static function criarLembretesParaMarcacao(int $marcacaoId): int
    {
        require_once __DIR__ . '/PacienteContacto.php';
        require_once __DIR__ . '/Marcacao.php';

        $marcacao = Marcacao::obter($marcacaoId);
        if (!$marcacao) return 0;

        $contactos = PacienteContacto::contactosComConsentimento(
            (int) $marcacao['paciente_id']
        );
        if (empty($contactos)) return 0;

        $dataConsulta = $marcacao['data_consulta'];
        $turno = $marcacao['turno'];
        $turnoTexto = ($turno === 'manha') ? 'Manhã' : 'Tarde';

        // Mensagem genérica (sem dados clínicos)
        $mensagem = sprintf(
            "Lembrete: Tem uma consulta marcada no Hospital Geral do Bengo para %s (%s). Por favor, compareça na recepção para fazer o check-in.",
            date('d/m/Y', strtotime($dataConsulta)),
            $turnoTexto
        );

        // Calcular horários de envio
        $horariosEnvio = [];

        // 24h antes (dia anterior às 10h)
        $diaAnterior = date('Y-m-d', strtotime($dataConsulta . ' -1 day'));
        $horariosEnvio[] = $diaAnterior . ' 10:00:00';

        // Manhã da consulta (07h)
        $horariosEnvio[] = $dataConsulta . ' 07:00:00';

        $db = Database::ligar();
        $criados = 0;

        foreach ($contactos as $contacto) {
            $canal = self::mapearCanal($contacto['tipo']);
            if (!$canal) continue;

            $assunto = ($canal === 'email')
                ? 'Lembrete de Consulta — Hospital Geral do Bengo'
                : null;

            foreach ($horariosEnvio as $agendadaPara) {
                // Não criar lembrete se a data já passou
                if (strtotime($agendadaPara) <= time()) continue;

                $stmt = $db->prepare(
                    "INSERT INTO notificacoes
                        (marcacao_id, paciente_id, canal, destino,
                         assunto, conteudo, agendada_para)
                     VALUES
                        (:mid, :pid, :canal, :dest,
                         :assunto, :msg, :agendada)"
                );
                $stmt->execute([
                    ':mid'      => $marcacaoId,
                    ':pid'      => (int) $marcacao['paciente_id'],
                    ':canal'    => $canal,
                    ':dest'     => $contacto['valor'],
                    ':assunto'  => $assunto,
                    ':msg'      => $mensagem,
                    ':agendada' => $agendadaPara,
                ]);
                $criados++;
            }
        }

        return $criados;
    }

    /**
     * Listar notificações pendentes prontas para envio
     */
    public static function listarPendentes(int $limite = 50): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT n.*, p.nome AS paciente_nome
             FROM notificacoes n
             JOIN pacientes p ON n.paciente_id = p.id
             WHERE n.estado = 'pendente'
             AND n.agendada_para <= NOW()
             AND n.tentativas < 3
             ORDER BY n.agendada_para ASC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Marcar como enviada
     */
    public static function marcarEnviada(int $id): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE notificacoes
             SET estado = 'enviada',
                 enviada_em = NOW(),
                 tentativas = tentativas + 1
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Marcar como falhada e reagendar retry
     */
    public static function marcarFalhada(int $id, string $erro): bool
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE notificacoes
             SET estado = CASE WHEN tentativas >= 2 THEN 'falhada' ELSE 'pendente' END,
                 tentativas = tentativas + 1,
                 ultimo_erro = :erro,
                 agendada_para = CASE
                    WHEN tentativas < 2 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                    ELSE agendada_para
                 END
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id, ':erro' => $erro]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Cancelar notificações de uma marcação (quando remarcada/cancelada)
     */
    public static function cancelarPorMarcacao(int $marcacaoId): int
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "UPDATE notificacoes
             SET estado = 'cancelada'
             WHERE marcacao_id = :mid
             AND estado = 'pendente'"
        );
        $stmt->execute([':mid' => $marcacaoId]);
        return $stmt->rowCount();
    }

    /**
     * Listar falhas recentes (para painel da recepção)
     */
    public static function listarFalhasRecentes(int $limite = 20): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT n.*, p.nome AS paciente_nome,
                    m.data_consulta, m.turno
             FROM notificacoes n
             JOIN pacientes p ON n.paciente_id = p.id
             JOIN marcacoes m ON n.marcacao_id = m.id
             WHERE n.estado = 'falhada'
             ORDER BY n.criado_em DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Listar todas as notificações de uma marcação
     */
    public static function listarPorMarcacao(int $marcacaoId): array
    {
        $db = Database::ligar();
        $stmt = $db->prepare(
            "SELECT * FROM notificacoes
             WHERE marcacao_id = :mid
             ORDER BY agendada_para ASC"
        );
        $stmt->execute([':mid' => $marcacaoId]);
        return $stmt->fetchAll();
    }

    /**
     * Estatísticas de notificações
     */
    public static function estatisticas(): array
    {
        $db = Database::ligar();
        $stmt = $db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(estado = 'pendente') AS pendentes,
                SUM(estado = 'enviada') AS enviadas,
                SUM(estado = 'falhada') AS falhadas,
                SUM(estado = 'cancelada') AS canceladas
             FROM notificacoes"
        );
        return $stmt->fetch() ?: [];
    }

    /**
     * Mapear tipo de contacto para canal de notificação
     */
    private static function mapearCanal(string $tipo): ?string
    {
        return match ($tipo) {
            'telefone'  => 'sms',
            'whatsapp'  => 'whatsapp',
            'email'     => 'email',
            default     => null,
        };
    }
}
