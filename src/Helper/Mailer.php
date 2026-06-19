<?php
namespace Logbuch\Helper;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Mailer — dünner Wrapper um PHPMailer für den SMTP-Versand.
 *
 * Verwendung:
 *   $mailer = new Mailer($config['mail']);
 *   $mailer->send($empfaengerEmail, $empfaengerName, $betreff, $htmlBody);
 *
 * Wirft eine RuntimeException, wenn der Versand fehlschlägt — Aufrufer
 * sollten das fangen und dem Nutzer keine internen Details zeigen.
 */
class Mailer
{
    public function __construct(private array $config)
    {
    }

    /**
     * Sendet eine HTML-E-Mail (mit automatischem Klartext-Fallback).
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        if (!class_exists(PHPMailer::class)) {
            throw new \RuntimeException(
                'PHPMailer ist nicht installiert. Bitte "composer require phpmailer/phpmailer" ausführen.'
            );
        }

        $mail = new PHPMailer(true);

        try {
            // ---- SMTP-Verbindung ----
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->Port       = (int) $this->config['port'];
            $mail->SMTPAuth   = $this->config['username'] !== '';
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->CharSet    = 'UTF-8';

            $encryption = strtolower((string) ($this->config['encryption'] ?? ''));
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }

            // ---- Absender / Empfänger ----
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($toEmail, $toName);

            // ---- Inhalt ----
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody)));

            $mail->send();
        } catch (PHPMailerException $e) {
            throw new \RuntimeException('E-Mail-Versand fehlgeschlagen: ' . $mail->ErrorInfo, 0, $e);
        }
    }
}
