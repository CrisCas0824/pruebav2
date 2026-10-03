<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

class MailHelper {
    public static function sendEmail($to, $subject, $body, $isHTML = true) {
        $mail = new PHPMailer(true);
        try {
            $smtpUsername = getenv('SMTP_USERNAME');
            $smtpPassword = getenv('SMTP_PASSWORD');
            if (!$smtpUsername || !$smtpPassword) {
                error_log('SMTP_USERNAME and SMTP_PASSWORD must be configured to send email.');
                return false;
            }

            // Configuración del servidor SMTP (Gmail)
            $mail->SMTPDebug = 0;                      // 0 = apagado (para producción)
            $mail->isSMTP();                           // Usar SMTP
            $mail->Host       = 'smtp.gmail.com';      // Servidor SMTP de Google
            $mail->SMTPAuth   = true;                  // Habilitar autenticación SMTP
            $mail->Username   = $smtpUsername;
            $mail->Password   = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Habilitar cifrado TLS
            $mail->Port       = 587;                   // Puerto TCP para TLS

            // Remitente y destinatario
            $mail->setFrom($smtpUsername, 'Tienda La Caserita');
            $mail->addAddress($to);

            // Contenido del correo
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            // Si el body es HTML, hacemos una versión texto plano básica
            if ($isHTML) {
                $mail->AltBody = strip_tags($body);
            }

            // Para solucionar problemas de caracteres especiales (tildes, eñes)
            $mail->CharSet = 'UTF-8';

            return $mail->send();
        } catch (Exception $e) {
            error_log("Error enviando correo con PHPMailer: {$mail->ErrorInfo}");
            return false;
        }
    }
}
