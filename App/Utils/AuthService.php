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

    $this->mail->isSMTP();
    $this->mail->Host       = $smtpConfig['smtp']['host'];
    $this->mail->SMTPAuth   = true;
    $this->mail->Username   = $smtpConfig['smtp']['username'];
    $this->mail->Password   = $smtpConfig['smtp']['password'];
    $this->mail->SMTPSecure = $smtpConfig['smtp']['secure']; // PHPMailer::ENCRYPTION_SMTPS
    $this->mail->Port       = $smtpConfig['smtp']['port'];

    $this->mail->CharSet    = $smtpConfig['charset'];
    $this->mail->setFrom($smtpConfig['smtp']['from'], $smtpConfig['smtp']['from_name']);

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
