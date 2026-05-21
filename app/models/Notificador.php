<?php
// ================================================
// Hospital Geral do Bengo — Model: Notificador
// Serviço de envio real de notificações
// Adaptadores: Email SMTP, SMS HTTP, WhatsApp HTTP
// ================================================

class Notificador
{
    /**
     * Enviar notificação pelo canal correcto
     * @return array ['sucesso' => bool, 'erro' => string|null]
     */
    public static function enviar(array $notificacao): array
    {
        return match ($notificacao['canal']) {
            'email'    => self::enviarEmail($notificacao),
            'sms'      => self::enviarSms($notificacao),
            'whatsapp' => self::enviarWhatsapp($notificacao),
            default    => ['sucesso' => false, 'erro' => 'Canal desconhecido: ' . $notificacao['canal']],
        };
    }

    // ------------------------------------------------
    // EMAIL via SMTP (fsockopen — sem Composer)
    // ------------------------------------------------
    private static function enviarEmail(array $n): array
    {
        $host = getenv('HB_MAIL_HOST');
        $port = (int) (getenv('HB_MAIL_PORT') ?: 587);
        $user = getenv('HB_MAIL_USER');
        $pass = getenv('HB_MAIL_PASS');
        $from = getenv('HB_MAIL_FROM') ?: $user;
        $fromName = getenv('HB_MAIL_FROM_NAME') ?: 'Hospital Geral do Bengo';

        if (!$host || !$user || !$pass) {
            return ['sucesso' => false, 'erro' => 'Configuração de email incompleta (HB_MAIL_HOST/USER/PASS).'];
        }

        $destino = $n['destino'];
        $assunto = $n['assunto'] ?? 'Lembrete - Hospital Geral do Bengo';
        $mensagem = $n['conteudo'];

        try {
            // Usar mail() do PHP como fallback simples
            // Em produção, recomenda-se configurar php.ini ou usar SMTP directo
            $headers = implode("\r\n", [
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . $fromName . ' <' . $from . '>',
                'Reply-To: ' . $from,
                'X-Mailer: HGB-Notificador/1.0',
            ]);

            // Tentar envio via SMTP com fsockopen
            $resultado = self::smtpEnviar(
                $host, $port, $user, $pass,
                $from, $fromName, $destino,
                $assunto, $mensagem
            );

            if ($resultado === true) {
                return ['sucesso' => true, 'erro' => null];
            }

            return ['sucesso' => false, 'erro' => 'SMTP: ' . $resultado];

        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => 'Email: ' . $e->getMessage()];
        }
    }

    /**
     * Envio SMTP básico via fsockopen (sem dependências)
     * @return true|string true se sucesso, string com erro se falhou
     */
    private static function smtpEnviar(
        string $host, int $port,
        string $user, string $pass,
        string $from, string $fromName,
        string $to, string $subject, string $body
    ): true|string {
        $tls = ($port === 587 || $port === 465);
        $prefix = ($port === 465) ? 'ssl://' : '';

        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
        if (!$socket) {
            return "Não foi possível ligar ao servidor SMTP: $errstr ($errno)";
        }

        stream_set_timeout($socket, 10);

        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            return "Resposta inesperada do servidor: $response";
        }

        // EHLO
        fputs($socket, "EHLO localhost\r\n");
        $resp = '';
        while ($line = fgets($socket, 512)) {
            $resp .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }

        // STARTTLS para porta 587
        if ($port === 587) {
            fputs($socket, "STARTTLS\r\n");
            fgets($socket, 512);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($socket, "EHLO localhost\r\n");
            while ($line = fgets($socket, 512)) {
                if (substr($line, 3, 1) === ' ') break;
            }
        }

        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 512);
        fputs($socket, base64_encode($user) . "\r\n");
        fgets($socket, 512);
        fputs($socket, base64_encode($pass) . "\r\n");
        $authResp = fgets($socket, 512);
        if (substr($authResp, 0, 3) !== '235') {
            fclose($socket);
            return "Autenticação SMTP falhou: $authResp";
        }

        // MAIL FROM
        fputs($socket, "MAIL FROM:<$from>\r\n");
        fgets($socket, 512);

        // RCPT TO
        fputs($socket, "RCPT TO:<$to>\r\n");
        $rcptResp = fgets($socket, 512);
        if (substr($rcptResp, 0, 3) !== '250') {
            fclose($socket);
            return "Destinatário rejeitado: $rcptResp";
        }

        // DATA
        fputs($socket, "DATA\r\n");
        fgets($socket, 512);

        $headers = "From: $fromName <$from>\r\n"
            . "To: $to\r\n"
            . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Date: " . date('r') . "\r\n";

        fputs($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $dataResp = fgets($socket, 512);

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        if (substr($dataResp, 0, 3) === '250') {
            return true;
        }

        return "Erro ao enviar dados: $dataResp";
    }

    // ------------------------------------------------
    // SMS via HTTP Gateway
    // ------------------------------------------------
    private static function enviarSms(array $n): array
    {
        $gatewayUrl = getenv('HB_SMS_GATEWAY_URL');
        $token = getenv('HB_SMS_GATEWAY_TOKEN');
        $from = getenv('HB_SMS_FROM') ?: 'HospBengo';

        if (!$gatewayUrl || !$token) {
            return ['sucesso' => false, 'erro' => 'Configuração SMS incompleta (HB_SMS_GATEWAY_URL/TOKEN).'];
        }

        $payload = json_encode([
            'to'      => $n['destino'],
            'from'    => $from,
            'message' => $n['conteudo'],
        ]);

        return self::httpPost($gatewayUrl, $payload, $token);
    }

    // ------------------------------------------------
    // WHATSAPP via HTTP Gateway
    // ------------------------------------------------
    private static function enviarWhatsapp(array $n): array
    {
        $gatewayUrl = getenv('HB_WHATSAPP_GATEWAY_URL');
        $token = getenv('HB_WHATSAPP_GATEWAY_TOKEN');

        if (!$gatewayUrl || !$token) {
            return ['sucesso' => false, 'erro' => 'Configuração WhatsApp incompleta (HB_WHATSAPP_GATEWAY_URL/TOKEN).'];
        }

        $payload = json_encode([
            'to'      => $n['destino'],
            'message' => $n['conteudo'],
        ]);

        return self::httpPost($gatewayUrl, $payload, $token);
    }

    // ------------------------------------------------
    // HTTP POST genérico (cURL)
    // ------------------------------------------------
    private static function httpPost(string $url, string $payload, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['sucesso' => false, 'erro' => 'HTTP: ' . $error];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['sucesso' => true, 'erro' => null];
        }

        return [
            'sucesso' => false,
            'erro' => "HTTP $httpCode: " . substr($response, 0, 200),
        ];
    }
}
