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
    
    
    }
?>
