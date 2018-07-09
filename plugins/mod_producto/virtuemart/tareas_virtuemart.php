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
            $datos = $_POST['datos'];
            
			$respuesta = array();
			$modificarProducto = $ObjViruemart->modificarProducto($datos);
            $respuesta['datos']=$datos;
			$respuesta['resul']= $modificarProducto;
        break;
        case 'mostrarModalNotificacion':
            $datos=$_POST['datos'];
            $html='<div class="col-md-12">'
                .'<h4>Enviar correo a :'.$datos['nombreUsuario'].'</h4>
                <div class="col-md-12">
                    Id del producto: <p id="idProducto">'.$datos['id'].'</p>
                    <input type="text" id="idNotificacion" value="'.$datos['idNotificacion'].'" style="display:none">
                    <input type="text" id="emailW"  style="display:none" value="'.$datos['emailEnvio'].'">
                    <input type="text" id="hostW"  style="display:none" value="'.$datos['hostEnvio'].'">
                    <input type="text" id="passwordW"  style="display:none" value="'.$datos['passwordEnvio'].'">
                    <input type="text" id="puertoW"  style="display:none" value="'.$datos['puertoEnvio'].'">
                </div>
                '
                .'<div class="col-md-12">
                    <label>Email</label>'
                .'<input type="text" id="email" name="email" value="'.$datos['correo'].'" size="60">'
                .'</div></div>
                
                <div class="col-md-12">'
                .'<div class="col-md-12">
                    <label>Asunto</label>'
                .'<input type="text" id="asunto" name="asuno" size="60" value="'.$datos['nombreProducto'].'">'
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
        include_once ($RutaServidor.$HostNombre. "/lib/PHPMailer/src/PHPMailer.php");
       include_once ($RutaServidor.$HostNombre. "/lib/PHPMailer/src/Exception.php");
        include_once ($RutaServidor.$HostNombre. "/lib/PHPMailer/src/SMTP.php");

            $mail=new PHPMailer\PHPMailer\PHPMailer(true);
            $datos=$_POST['datos'];
             $mail->isSMTP();
           
            $mail->SMTPDebug = 3;
            
            //~ $mail->Host ='hl309.hosteurope.es';
            $mail->Host=$datos['hostEnvio'];
            //~ $mail->Port = 465;
            $mail->Port=$datos['puertoEnvio'];
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'ssl';
            $mail->Username ='web@multipiezas.es';
            //~ $mail->Password ='0fFaqiERXdLn';
            $mail->Password=$datos['passwordEnvio'];
          
            //Poner direccion de multifrenos
            $mail->setFrom($datos['emailEnvio'], $datos['emailEnvio']);
            $mail->addAddress($datos['email'], 'Nombre prueba');
            $mail->Subject = $datos['asunto'];
            $mail->Body = $datos['mensaje'];
            $mail->smtpClose();
            if (!$mail->send()) {
                   $respuesta['mail']= 1;
                   //~ $respuesta['error']=$mail->ErrorInfo;
                  
                   
            } else {
                
                   $respuesta['mail']= 2;
                   $modificarEstadoNotificacion = $ObjViruemart->modificarNotificacion($datos['idNotificacion']);
                   $respuesta['modificacion']=$modificarEstadoNotificacion;
                   
            }
        break;
    
    
    }
    echo json_encode($respuesta);
?>
