
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once $URLCom.'/lib/PHPMailer/src/PHPMailer.php';
require_once $URLCom.'/lib/PHPMailer/src/Exception.php';
require_once $URLCom.'/lib/PHPMailer/src/SMTP.php';

//~ class Mailer extends PHPMailer {

    /**
     * Save email to a folder (via IMAP)
     *
     * This function will open an IMAP stream using the email
     * credentials previously specified, and will save the email
     * to a specified folder. Parameter is the folder name (ie, Sent)
     * if nothing was specified it will be saved in the inbox.
     *
     * @author David Tkachuk <http://davidrockin.com/>
     * 
     * mas info: https://gist.github.com/DavidRockin/b4867fd0b5bb687f5af1
     * https://www.rfc-es.org/rfc/rfc2060-es.txt
     */
    //~ public function copyToFolder($folderPath = null) {
        // $message = $this->MIMEHeader . $this->MIMEBody;
        //~ $message = $this->getSentMIMEMessage();
        //~ $path = "INBOX" . (isset($folderPath) && !is_null($folderPath) ? ".".$folderPath : ""); // Location to save the email
        //~ $imapStream = imap_open("{" . $this->Host . "}" . $path , $this->Username, $this->Password);
        //~ imap_append($imapStream, "{" . $this->Host . "}" . $path, $message);
        //~ imap_close($imapStream);
    //~ }

//~ }
class CorreoElectronico {


    // static public function leerConfiguracion(){
    //     include_once $URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php';
    //     $CTienda = new ClaseTienda();
       
    //     $res = $CTienda->tiendaPrincipal();
    //     $tiendaPrincipal=$res['datos'][0];
    //     $datosServidor = $CTienda->obtenerArrayDatosServidor($tiendaPrincipal['servidor_email']);
    //     $origen = array( 'email'    => $datosServidor['emailTienda'],
    //                      'nombre'   => $datosServidor['nombreEmail']
    //                      );

    //     return $origen;
    // }

    static public function enviar($destinatario, $mensaje, $asunto, $adjunto,$origen){
        
        include __DIR__.'/../configuracion.php'; // Para cargar configuraciond de $PHPMAILER_CONF

//        $configuracion = CorreoElectronico::leerConfiguracion();
        
        $mail =  new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $PHPMAILER_CONF['host'];                //Set the SMTP server to send through
            $mail->SMTPAuth   = $PHPMAILER_CONF['SMTPAuth'];            //Enable SMTP authentication
            $mail->Username   = $PHPMAILER_CONF['Username'];            //SMTP username
            $mail->Password   = $PHPMAILER_CONF['Password'];            //SMTP password
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption depende del servidor.
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $PHPMAILER_CONF['Port'];                //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            
            //Recipients
            $mail->setFrom($origen['email'], $origen['nombre']);
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
        
            //~ $mail->send();
            error_log($origen['email'].','.$origen['nombre'].','.$destinatario);
            $result = $mail->send();
            error_log(json_encode($result ));

           
            //~ if ($result) {
              //~ $mail_string = $mail->getSentMIMEMessage();
              //~ //Aqui hay que tener encuenta imap y smtp puede usar datos diferentes.
              //~ //mas info: https://gist.github.com/DavidRockin/b4867fd0b5bb687f5af1
              //~ // https://www.rfc-es.org/rfc/rfc2060-es.txt
              //~ $path = "INBOX.Sent" ; // Location to save the email
              //~ $imapStream = imap_open("{" . $mail->Host . "}" . $path , $mail->Username, $mail->Password);
              //~ imap_append($ImapStream, $folder, $mail_string, "\\Seen");
              //~ imap_close($imapStream);
            //~ }
            //~ $mail->copyToFolder("Sent");
            $respuesta = 'Message has been sent';
        } catch (Exception $e) {
            $respuesta = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }        
        return $respuesta;
    }
}
