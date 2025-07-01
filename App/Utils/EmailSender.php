<?php

namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender
{
    private PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        // Настройки SMTP
        $this->mail->isSMTP();
        $this->mail->Host = 'mail.webhat.by';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'tass@webhat.by';
        $this->mail->Password = 'I^3+]nwdQdV?';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $this->mail->Port = 465;

        // Основные настройки письма
        $this->mail->CharSet = 'UTF-8';
        $this->mail->setFrom('tass@webhat.by', 'Webhat Project');
    }

    /**
     * Отправить email
     */
    public function send(string $to, string $subject, string $body): bool
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;

            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
