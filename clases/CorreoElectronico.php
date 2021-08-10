
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '/var/www/tpvfox/lib/PHPMailer/src/PHPMailer.php';
require_once '/var/www/tpvfox/lib/PHPMailer/src/Exception.php';
require_once '/var/www/tpvfox/lib/PHPMailer/src/SMTP.php';

class CorreoElectronico {



    static public function enviar($destinatario, $mensaje, $asunto, $adjunto){
        include_once '/var/www/tpvfox/configuracion.php';

        $mail =  new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $PHPMAILER_CONF['host'];                //Set the SMTP server to send through
            $mail->SMTPAuth   = $PHPMAILER_CONF['SMTPAuth'];            //Enable SMTP authentication
            $mail->Username   = $PHPMAILER_CONF['Username'];            //SMTP username
            $mail->Password   = $PHPMAILER_CONF['Password'];            //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = $PHPMAILER_CONF['Port'];                //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        
            //Recipients
            $mail->setFrom($email_direccion_origen, $email_usuario_origen);
            $mail->addAddress($destinatario);     //Add a recipient
                                                  //Name is optional
            // $mail->addReplyTo('info@example.com', 'Information');
            //$mail->addCC('cc@example.com');
            //$mail->addBCC('bcc@example.com');
        
            //Attachments
            $mail->addAttachment($adjunto);         //Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
        
            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $asunto;               //'Here is the subject';
            $mail->Body    = $mensaje;              // 'This is the HTML message body <b>in bold!</b>';
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        
            $mail->send();
            $respuesta = 'Message has been sent';
        } catch (Exception $e) {
            $respuesta = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }        
        return $respuesta;
    }
}