<?php

declare(strict_types=1);

namespace App\Service;

use Monolog\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Serviço para envio de e-mails via SMTP usando PHPMailer.
 * Encapsula configurações, envio e logs de forma profissional.
 */
class MailService
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromEmail;
    private string $fromName;
    private int $timeout;

    public function __construct(private Logger $logger)
    {
        // Lê as configurações do .env
        $this->host = $_ENV['MAIL_HOST'] ?? '';
        $this->port = (int) ($_ENV['MAIL_PORT'] ?? 587);
        $this->username = $_ENV['MAIL_USERNAME'] ?? '';
        $this->password = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->encryption = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $this->fromEmail = $_ENV['MAIL_FROM'] ?? $this->username;
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Meu Carango';
        $this->timeout = (int) ($_ENV['MAIL_TIMEOUT'] ?? 30);

        // Valida se as configurações essenciais estão presentes
        if (empty($this->host) || empty($this->username) || empty($this->password)) {
            $this->logger->error('Configuração de e-mail incompleta no .env', [
                'host' => $this->host ? 'presente' : 'faltando',
                'username' => $this->username ? 'presente' : 'faltando',
                'password' => $this->password ? 'presente' : 'faltando',
            ]);
            throw new \RuntimeException('Configuração SMTP incompleta. Verifique o arquivo .env');
        }
    }

    /**
     * Envia um e-mail genérico.
     *
     * @param string $to      E-mail do destinatário
     * @param string $subject Assunto do e-mail
     * @param string $body    Corpo do e-mail (HTML ou texto)
     * @param bool   $isHtml  Se true, envia como HTML; se false, como texto puro
     * @return bool
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        // Valida o e-mail do destinatário
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->logger->error('E-mail do destinatário inválido', ['to' => $to]);
            return false;
        }

        try {
            $mail = $this->createMailer();

            // Configura remetente e destinatário
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);

            // Conteúdo
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML($isHtml);

            // Envia
            $mail->send();

            $this->logger->info('E-mail enviado com sucesso', [
                'to'      => $to,
                'subject' => $subject,
            ]);

            return true;

        } catch (PHPMailerException $e) {
            $this->logger->error('Falha ao enviar e-mail', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        } catch (\Throwable $e) {
            $this->logger->critical('Erro inesperado ao enviar e-mail', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envia um e-mail específico para o código de verificação em duas etapas (2FA).
     *
     * @param string $to            E-mail do administrador
     * @param string $code          Código de 6 dígitos
     * @param int    $expiryMinutes Quantos minutos o código é válido
     * @return bool
     */
    public function sendTwoFactorCode(string $to, string $code, int $expiryMinutes): bool
    {
        $subject = 'Código de verificação - Meu Carango';

        // Corpo do e-mail em HTML (CSS nativo, sem Bootstrap)
        $body = $this->buildTwoFactorBody($code, $expiryMinutes);

        return $this->send($to, $subject, $body, true);
    }

    /**
     * Constrói o corpo HTML do e-mail para o código 2FA.
     */
    private function buildTwoFactorBody(string $code, int $expiryMinutes): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .content {
            padding: 20px 0;
        }
        .content p {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }
        .code-box {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #2c3e50;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px dashed #ccc;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #888;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
            margin-top: 20px;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Código de Verificação</h1>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p>Você solicitou um código de verificação para acessar o painel administrativo do <strong>Meu Carango</strong>.</p>
            <p>Use o código abaixo para concluir sua autenticação em duas etapas:</p>
            <div class="code-box">{$code}</div>
            <p><strong>Este código é válido por {$expiryMinutes} minutos.</strong></p>
            <p>Se você não solicitou este código, ignore este e-mail. Nenhuma ação é necessária.</p>
        </div>
        <div class="footer">
            <p>Meu Carango - Sistema de Gerenciamento de Lojistas</p>
            <p>Este e-mail foi enviado automaticamente. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Cria e configura uma instância do PHPMailer com as definições SMTP.
     *
     * @return PHPMailer
     * @throws PHPMailerException
     */
    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        // Configuração SMTP
        $mail->isSMTP();
        $mail->Host       = $this->host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->username;
        $mail->Password   = $this->password;
        $mail->SMTPSecure = $this->encryption;
        $mail->Port       = $this->port;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = $this->timeout;

        // Desativa debug em produção (pode ser ativado via .env se necessário)
        $mail->SMTPDebug = 0;

        return $mail;
    }

    /**
     * Verifica se a configuração SMTP está completa e válida.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->host)
            && !empty($this->username)
            && !empty($this->password)
            && filter_var($this->fromEmail, FILTER_VALIDATE_EMAIL);
    }
}