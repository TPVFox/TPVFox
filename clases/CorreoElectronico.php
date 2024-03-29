
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


    static public function leerConfiguracion(){
        include_once __DIR__.'/../modulos/mod_tienda/clases/ClaseTienda.php';
        $CTienda = new ClaseTienda();
       
        $res = $CTienda->tiendaPrincipal();
        $tiendaPrincipal=$res['datos'][0];
        $datosServidor = $CTienda->obtenerArrayDatosServidor($tiendaPrincipal['servidor_email']);
        
        return $datosServidor;
    }

    static public function enviar($destinatario, $mensaje, $asunto, $adjunto){
        // @ Objetivo:
        // Envia email desde el correo que indicamos en configuracion de tienda (a nivel Base datos)
        // Ademas de enviar email destinatario, se sube a la bandeja enviado del correo de la tienda.
        // @ Devolvemos:
        // respuesta = array( envio_destinatario => OK o KO,
        //                    subido_enviados => OK o KO ) si hay un KO mandamos error_envio y error_subida
        
        $respuesta = array();
        
        $configuracion = CorreoElectronico::leerConfiguracion();
        
        $mail =  new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;  //SMTP::DEBUG_SERVER;   Asi muestra respuesta.                  
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $configuracion['host'];                //Set the SMTP server to send through
            $mail->SMTPAuth   = $configuracion['SMTPAuth'];            //Enable SMTP authentication
            $mail->Username   = $configuracion['SMTPUsuario'];            //SMTP username
            $mail->Password   = $configuracion['SMTPPassword'];            //SMTP password
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption depende del servidor.
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $configuracion['SMTPPort'];                //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            
            //Recipients
            $mail->setFrom($configuracion['emailTienda'], $configuracion['nombreRemitente']);
            $mail->addAddress($destinatario);     //Add a recipient
                                                  //Name is optional
            
        
            //Attachments
            $mail->addAttachment($adjunto);         //Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
        
            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $asunto;               //'Here is the subject';
            $mensaje = $mensaje.'<br/><br/><br/>'.$configuracion['nombreRemitente'];
            $mail->Body    = $mensaje;              // 'This is the HTML message body <b>in bold!</b>';
        
            $result = $mail->send();

           
            if ($result) {
                  // Fue correcto el envio.
                  $respuesta['envio_destinatario'] = 'OK';
                  $mail_string = $mail->getSentMIMEMessage();
                  //Aqui hay que tener encuenta imap y smtp puede usar datos diferentes.
                  //mas info: https://gist.github.com/DavidRockin/b4867fd0b5bb687f5af1
                  $folder = "INBOX.Sent" ; // Location to save the email
                  $imapStream = imap_open("{" . $mail->Host . ":993/imap/ssl}".$folder, $mail->Username, $mail->Password);
                  $respuesta_imap = imap_append($imapStream, "{" . $mail->Host . ":993}" . $folder, $mail_string);
                  // En instruccion anterior con un parametro mas podemos controlar si esta LEIDO , IMPORTANTE O MAS..
                  // 2.3.2. Atributo de mensaje Banderas en  https://www.rfc-es.org/rfc/rfc2060-es.txt
                  imap_close($imapStream);
                  $respuesta['subido_enviados'] = 'OK';
                  if ($respuesta_imap !== true){
                        $respuesta['subido_enviados'] = 'KO';
                        $respuesta['error_subida'] = 'Error en $mail->send():'.json_encode(imap_errors());
                  } 
                  
            } else {
                  $respuesta['envio_destinatario'] = 'KO';
                  $respuesta['error_envio'] = 'Error en $mail->send():'.json_encode($result);
            }
           
        } catch (Exception $e) {
            $respuesta['envio_destinatario'] = 'KO';
            $respuesta['error_envio'] = 'Error en Mailer (Exception=Respuesta de $mail->send():'.$mail->ErrorInfo;
        }        
        return $respuesta;
    }
}
