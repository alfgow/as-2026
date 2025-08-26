<?php
namespace App\Helpers;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
require_once __DIR__ . '/../config/config.php';
use App\Core\Config;

class MailHelper
{
    private static function client(): SesClient
    {
        return new SesClient([
            'version'     => '2010-12-01',
            'region'      => AWS_SES_REGION,
            'credentials' => [
                'key'    => AWS_KEY,
                'secret' => AWS_SECRET,
            ],
        ]);
    }

    private static function send(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        try {
            $client = self::client();
            $client->sendEmail([
                'Source' => AWS_SES_SENDER,
                'Destination' => ['ToAddresses' => [$to]],
                'ReplyToAddresses' => ['polizas@arrendamientoseguro.app'],
                'Message' => [
                    'Subject' => ['Data' => $subject, 'Charset' => 'UTF-8'],
                    'Body' => [
                        'Html' => ['Data' => $htmlBody, 'Charset' => 'UTF-8'],
                        'Text' => ['Data' => $textBody, 'Charset' => 'UTF-8']
                    ]
                ],
            ]);
            return true;
        } catch (AwsException $e) {
            error_log("SES ERROR: " . $e->getAwsErrorMessage());
            return false;
        }
    }

    /** Enviar correo con el Magic Link */
    public static function sendMagicLinkEmail(string $to, string $nombre, string $magicLink, string $expiresAt): bool
    {
        $subject = "Tu acceso temporal - SecureLink";
        $htmlBody = self::baseTemplate($nombre, "
            <p>Hemos generado un <strong>SecureLink</strong> para tu actualización de datos:</p>
            <p style='text-align:center; margin:20px 0;'>
                <a href='{$magicLink}' style='padding:12px 20px; background:#de6868; color:#fff; text-decoration:none; border-radius:6px;'>Acceder ahora</a>
            </p>
            <p>En un segundo correo recibirás el código <strong>OTP</strong> para que puedas acceder.</p>
            <p>Este enlace expira el <strong>{$expiresAt}</strong>.</p>
        ");
        $textBody = "Hola {$nombre},\n\nAccede a tu ficha con el siguiente enlace: {$magicLink}\n\nEl enlace expira el {$expiresAt}.";

        return self::send($to, $subject, $htmlBody, $textBody);
    }

    /** Enviar correo con el OTP */
    public static function sendOtpEmail(string $to, string $nombre, string $otp, string $expiresAt): bool
    {
        $subject = "Tu acceso temporal - Código OTP";
        $htmlBody = self::baseTemplate($nombre, "
            <p>Tu <strong>código OTP</strong> para acceder es:</p>
            <p style='text-align:center; font-size:28px; font-weight:bold; color:#de6868; margin:20px 0;'>{$otp}</p>
            <p>Este código expira el <strong>{$expiresAt}</strong>.</p>
        ");
        $textBody = "Hola {$nombre},\n\nTu código OTP es: {$otp}\n\nEl código expira el {$expiresAt}.";

        return self::send($to, $subject, $htmlBody, $textBody);
    }

    /** Plantilla base para todos los correos */
    private static function baseTemplate(string $nombre, string $contenido): string
    {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
        <meta charset='UTF-8'>
        <title>Arrendamiento Seguro</title>
        <style>
            body { margin:0; padding:20px; background:#fde8e8ca; font-family:'Segoe UI',Arial,sans-serif; color:#4b1d1d; }
            .container { max-width:600px; margin:auto; background:#fff; border-radius:20px; box-shadow:0 8px 24px rgba(0,0,0,0.12); overflow:hidden; }
            .header { text-align:center; padding:30px 20px 10px; }
            .header img.logo { width:120px; margin-bottom:10px; }
            .content { padding:20px 30px; text-align:center; }
            .content h2 { margin:10px 0; font-size:22px; color:#4b1d1d; }
            .content p { margin:8px 0; font-size:15px; line-height:1.5; }
            .footer { text-align:center; padding:20px; font-size:12px; color:#777; background:#fdf2f2; }
        </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://alfgow.s3.mx-central-1.amazonaws.com/Logo+Circular.png' alt='Arrendamiento Seguro' class='logo'>
                </div>
                <div class='content'>
                    <h2>¡Hola {$nombre}!</h2>
                    {$contenido}
                </div>
                <div class='footer'>
                    © ".date('Y')." Arrendamiento Seguro · Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>";
    }
}
