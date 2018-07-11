<?php 

$pulsado = $_POST['pulsado'];

include_once ("./../../../configuracion.php");

// Crealizamos conexion a la BD Datos
//~ include_once ("./../mod_conexion/conexionBaseDatos.php");
include_once ($RutaServidor.$HostNombre. "/clases/ClaseSession.php");


	// Solo creamos objeto si no existe.
	$thisTpv = new ClaseSession();
	$BDTpv = $thisTpv->getConexion();
    include ($RutaServidor.$HostNombre."/plugins/mod_producto/virtuemart/ClaseVirtuemart.php");
    $ObjViruemart = new PluginClaseVirtuemart();

	switch ($pulsado) {
        case 'modificarDatosWeb':
        //@Objetivo: modificar los datos del producto
            $datos = $_POST['datos'];
            
			$respuesta = array();
			$modificarProducto = $ObjViruemart->modificarProducto($datos);
            $respuesta['datos']=$datos;
            
			$respuesta['resul']= $modificarProducto;
            if(strlen($modificarProducto['Datos']['error']) == 0){
                $respuesta['htmlAlerta']='<div class="alert alert-success">
                                            <strong>Success!</strong> Has modificados los datos del producto.
                                        </div>';
            }else{
                $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                            <strong>Danger!</strong> Error de sql : '.$modificarProducto['Datos']['consulta'].'
                                        </div>';
            }
           
        break;
        case 'mostrarModalNotificacion':
        //@Objetivo: montar el modal de la notificación de clientes
            $datos=$_POST['datos'];
            $html='<div class="col-md-12">'
                .'<h4>Enviar correo a :'.$datos['nombreUsuario'].'</h4>
                <div class="col-md-12">
                    Id del producto: <p id="idProducto">'.$datos['id'].'</p>
                    <input type="text" id="idNotificacion" value="'.$datos['idNotificacion'].'" style="display:none">'
                 //~ .   '<input type="text" id="emailW"  style="display:none" value="'.$datos['emailEnvio'].'">
                    //~ <input type="text" id="hostW"  style="display:none" value="'.$datos['hostEnvio'].'">
                    //~ <input type="text" id="passwordW"  style="display:none" value="'.$datos['passwordEnvio'].'">
                    //~ <input type="text" id="puertoW"  style="display:none" value="'.$datos['puertoEnvio'].'">'
                 .   '<input type="text" id="numLinea"  style="display:none" value="'.$datos['numLinea'].'">
                </div>
                '
                .'<div class="col-md-12">
                    <label>Email</label>'
                .'<input type="text" id="email" name="email" value="'.$datos['correo'].'" size="60">'
                .'</div></div>
                
                <div class="col-md-12">'
                .'<div class="col-md-12">
                    <label>Asunto</label>'
                .'<input type="text" id="asunto" name="asuno" size="60" value="Contestacion a pregunta sobre '.$datos['nombreProducto'].'">'
                .'</div>'
                .'</div>'
                .'<div class="col-md-12">'
                .'<div class="col-md-10">'
                .'<label>Mensaje:</label>'
                .'<textarea id="mensaje" cols="60" name="mensaje"></textarea>'
                .'</div>'
                .'</div>
                <button type="button" class="btn btn-success" onclick="enviarCorreoNotificacion()">Enviar Correo</button>';
            $respuesta['html']=$html;
            
        break;
        case 'enviarCorreoNotificacion':
        //Objetivo: enviar el correo de notificación
        //Primero cargamos la libreria
        $respuesta = array();
        $datos=$_POST['datos'];
        $enviarCorreo = $ObjViruemart->enviarCorreo($_POST['datos']);
        if($enviarCorreo['Datos']['mailer']==true){
            $respuesta['mail']= 2;
            $modificarEstadoNotificacion = $ObjViruemart->modificarNotificacion($datos['idNotificacion']);
            $respuesta['modificacion']=$modificarEstadoNotificacion;
            $respuesta['numLinea']=$datos['numLinea'];
        }else{
            $respuesta['mail']= 1;
            $respuesta['error']=$enviarCorreo['Datos']['mensaje'];
        }
        $respuesta['correo']= $enviarCorreo;
     
        //~ include_once ($RutaServidor.$HostNombre. "/lib/PHPMailer/src/PHPMailer.php");
        //~ include_once ($RutaServidor.$HostNombre. "/lib/PHPMailer/src/Exception.php");
        //~ include_once ($RutaServidor.$HostNombre. "/lib/PHPMailer/src/SMTP.php");
        
            //~ $mail=new PHPMailer\PHPMailer\PHPMailer(true);
            //~ $datos=$_POST['datos'];     //recibimos las datos
             //~ $mail->isSMTP();
           
            //~ $mail->SMTPDebug = 0;//no mostramos el mensaje de salida
            
          
            //~ $mail->Host=$datos['hostEnvio'];//host del servidor que lo obtenemos de joomla
           
            //~ $mail->Port=$datos['puertoEnvio'];//puerto del servidor smtp 
            //~ $mail->SMTPAuth = true;//Utilizamos la autentificación de smtp
            //~ $mail->SMTPSecure = 'ssl';//Conexión segura
            //~ $mail->Username =$datos['emailEnvio'];//Nombre de usuario
          
            //~ $mail->Password=$datos['passwordEnvio'];//Contraseña del servidor
          
           
            //~ $mail->setFrom($datos['emailEnvio'], $datos['emailEnvio']);//Cuenta de la que vamos a enviar el correo
            //~ $mail->addAddress($datos['email'], '');//A quien le vamos a enviar el correo
            //~ $mail->Subject = $datos['asunto'];//Asunto del correo
            //~ $mail->Body = $datos['mensaje'];//Mensaje del correo
            //~ $mail->smtpClose();//Cerramos la conexion
           //~ //Si al enviar obtenemos un error envia un error 
            //~ if (!$mail->send()) {
                //~ $respuesta['mail']= 1;
                //~ $respuesta['error']=$mail->ErrorInfo;
                  
                   
            //~ } else {
                //~ //Si no modificamos el registro de la notificación
                    //~ $respuesta['mail']= 2;
                    //~ $modificarEstadoNotificacion = $ObjViruemart->modificarNotificacion($datos['idNotificacion']);
                    //~ $respuesta['modificacion']=$modificarEstadoNotificacion;
                    //~ $respuesta['numLinea']=$datos['numLinea'];
                   
            //~ }
        break;
    
    
    }
    echo json_encode($respuesta);
?>
