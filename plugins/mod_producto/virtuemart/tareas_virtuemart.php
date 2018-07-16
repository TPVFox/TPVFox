<?php 

$pulsado = $_POST['pulsado'];

include_once ("./../../../configuracion.php");

// Crealizamos conexion a la BD Datos
include_once ($RutaServidor.$HostNombre. "/clases/ClaseSession.php");
include_once $RutaServidor.$HostNombre.'/modulos/mod_producto/clases/ClaseProductos.php';


	// Solo creamos objeto si no existe.
	$thisTpv = new ClaseSession();
	$BDTpv = $thisTpv->getConexion();
    $CTArticulos = new ClaseProductos($BDTpv);
    include_once ($RutaServidor.$HostNombre."/plugins/mod_producto/virtuemart/ClaseVirtuemart.php");
    $ObjViruemart = new PluginClaseVirtuemart();

	switch ($pulsado) {
        case 'modificarDatosWeb':
        //@Objetivo: modificar los datos del producto
            $datos = $_POST['datos'];
            
			$respuesta = array();
            $datosComprobaciones=json_decode($datos, true);
            if($datosComprobaciones['id']>0){
                 $respuesta['caracteres']=strlen($datosComprobaciones['nombre']);
                if(strlen($datosComprobaciones['nombre'])>180){
                    $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                <strong>Danger!</strong> No se puede modificar el producto 
                                                por que el nombre es superior a 180 caracteres.
                                            </div>';
                }else{
                    
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
                }
            }else{
                $datosComprobaciones['usuario']=365;
                $datosComprobaciones['peso']='KG';
                $datosComprobaciones['parametros']='min_order_level=""|max_order_level=""|step_order_level=""|product_box=""|';
                $datosComprobaciones['s_desc']="";
                $datosComprobaciones['desc']="";
                $datosComprobaciones['metadesc']="";
                $datosComprobaciones['metakey']="";
                $datosComprobaciones['title']="";
                $datosComprobaciones['vendor']=1;
                $datosComprobaciones['override']=0;
                $datosComprobaciones['product_override_price ']="0.00000";
                $datosComprobaciones['product_discount_id']=0;
                $datosComprobaciones['product_currency']=47;
                $datos=json_encode($datosComprobaciones);
                $addProducto = $ObjViruemart->addProducto($datos);
                if($addProducto['Datos']['error']==""){
                    if($addProducto['Datos']['id']>0){
                        $addRegistro=$CTArticulos->addTiendaProducto( $datosComprobaciones['idProducto'], $datosComprobaciones['idTienda'], $addProducto['Datos']['id']);
                        $respuesta['registro']=$addRegistro;
                         $respuesta['htmlAlerta']='<div class="alert alert-success">
                                                    <strong>Success!</strong> Has añadido el producto a la web 
                                                    </div>';
                                                    
                    }
                }else{
                    $respuesta['error']=$addProducto['Datos']['error'];
                     $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                    <strong>Danger!</strong> Error al añadir el producto a la web. '.$addProducto['Datos']['consulta'].'
                                                </div>';
                }
                
                $respuesta['resul']= $addProducto;
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
        break;
    
    
    }
    echo json_encode($respuesta);
?>
