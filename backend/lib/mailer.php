<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!class_exists(PHPMailer::class)) {
    // Autoload (au cas où init.php n’a pas encore chargé composer)
    $root = dirname(__DIR__, 2);
    $autoload = $root . '/vendor/autoload.php';
    if (is_file($autoload)) require $autoload;
}

function sendEmail(string $to, string $subject, string $html, ?string $textAlt = null): bool {
    $mail = new PHPMailer(true);
    try {
        $host   = getenv('SMTP_HOST') ?: '';
        $port   = (int)(getenv('SMTP_PORT') ?: 587);
        $user   = getenv('SMTP_USER') ?: '';
        $pass   = getenv('SMTP_PASS') ?: '';
        $secure = strtolower((string)(getenv('SMTP_SECURE') ?: 'tls')); // tls|ssl|none

        $from     = getenv('MAIL_FROM') ?: ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromName = getenv('MAIL_FROM_NAME') ?: 'EcoRide';

        if ($host && $user) {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } // else: no encryption
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
        } else {
            // Fallback: mail() (pas fiable en dev)
            $mail->isMail();
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $html;
        $mail->AltBody = $textAlt ?: strip_tags(str_replace('<br>', "\n", $html));

        return $mail->send();
    } catch (Exception $e) {
        error_log('[Mailer] ' . $e->getMessage());
        return false;
    }
}
