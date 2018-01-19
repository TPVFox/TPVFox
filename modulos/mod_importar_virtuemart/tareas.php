
<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/


$pulsado = $_POST['pulsado'];

include_once ("./../../configuracion.php");

// Crealizamos conexion a la BD Datos
include_once ("./../mod_conexion/conexionBaseDatos.php");

// Incluimos funciones
include_once ("./funciones.php");
include ("./../../controllers/Controladores.php");
$mensaje_log= '';
if (isset($_POST['mensaje_log'])){
	// Si mandamos por post, mensaje_log lo grabamos.
	$mensaje_log= $_POST['mensaje_log'];
	// Cargamos la ruta log del modulo para poder guardar ...
	// Obtenemos nombre log mod_importar_virtuemart.
	$ruta =  $RutaServidor.$RutaDatos.'/log_tpvFox';
	$fichero_log = ComprobarExisteLogTpv($ruta,$mensaje_log);
	
}
// Cargamos el controlador.
$TControlador = new ControladorComun; 
 
 switch ($pulsado) {
	case 'EliminarRelacionBDtpv':
		$producto_web  =$_POST['producto_web'];
		$producto_tpv  =$_POST['producto_tpv'];
		$diferencias  =$_POST['diferencias'];
		$tienda = $_POST['tienda_actual'];
		$tienda_export = $_POST['tienda_export'];
		// OJO queda pendiente obtener la tienda actual de una forma correcta...
		//~ $respuesta = funcion que lo haga,, pero esto debería ser curl... :-) 
		$respuesta['error'] = ' Pendiente de crear funcion de eliminar relacion idVirtuemart en BDTvp';
		header('Content-Type: application/json');
		echo json_encode($respuesta,true);
		break;
	
	case 'CrearProductoWeb':
		$producto_web  =$_POST['producto_web'];
		$producto_tpv  =$_POST['producto_tpv'];
		$diferencias  =$_POST['diferencias'];
		$tienda = $_POST['tienda_actual'];
		$tienda_export = $_POST['tienda_export'];
		// OJO queda pendiente obtener la tienda actual de una forma correcta...
		//~ $respuesta = funcion que lo haga,, pero esto debería ser curl... :-) 
		$respuesta['error'] = ' Pendiente de llamar funcion en servidor para Crear Producto en la web';
		header('Content-Type: application/json');
		echo json_encode($respuesta,true);
		break;
		
	case 'UpdateUnProductoTpv':
		// Objetivo es modificar los datos en BDTPV
		// Hay que tener en cuenta que este proceso se va modificar según el tipo de diferencia.
		$producto_web  =$_POST['producto_web'];
		$producto_tpv  =$_POST['producto_tpv'];
		$diferencias  =$_POST['diferencias'];
		$tienda = $_POST['tienda_actual'];
		$tienda_export = $_POST['tienda_export'];
		$respuesta = array();
		$DiferenciasComprobadas = ComprobarDiferencias($diferencias,$producto_web,$producto_tpv);
		// OJO queda pendiente obtener la tienda actual de una forma correcta y configuracion...
		if ($DiferenciasComprobadas['dedonde'] === 'web'){
			// Actualizamos datos web en tpv
			$respuesta = UpdateUnProductoTpv($BDTpv,$DiferenciasComprobadas,$tienda_export,$tienda);
		}
		//~ $respuesta = $DiferenciasComprobadas;
		header('Content-Type: application/json');
		echo json_encode($respuesta,true);
		break;
		
	
	case 'InsertUnProductoTpv':
		$productoNuevo  =$_POST['producto'];
		$tienda = $_POST['tienda_actual'];
		$tienda_export = $_POST['tienda_export'];
		// OJO queda pendiente obtener la tienda actual de una forma correcta...
		$respuesta = InsertUnProductoTpv($BDTpv,$productoNuevo,$tienda_export,$tienda);
		header('Content-Type: application/json');
		echo json_encode($respuesta,true);
		break;
		
	
	case 'AnhadirLog':
		// Añadimos a LogGrabamos la configuracion en log,
		$datos  =json_encode($_POST['datos']);
		$mensaje_log = "\n".$datos."\n";
		$ruta =  $RutaServidor.$RutaDatos.'/log_tpvFox';
		$fichero_log = ComprobarExisteLogTpv($ruta,$mensaje_log);
		$respuesta['log'] = $fichero_log;
		$respuesta['mensaje']= $mensaje_log;
		header('Content-Type: application/json');
		echo json_encode($respuesta,true);
		break;
	
	
	case 'Inicio Actualizar':
		// Grabamos la configuracion en log,
		$configuracion  =json_encode($_POST['configuracion'][0]);
		$mensaje_log = "Configuracion:\n".$configuracion."\n";
		$ruta =  $RutaServidor.$RutaDatos.'/log_tpvFox';
		$fichero_log = ComprobarExisteLogTpv($ruta,$mensaje_log);
		$respuesta['log'] = $fichero_log;
		$respuesta['mensaje']= $mensaje_log;
        header('Content-Type: application/json');
		echo json_encode($respuesta,true);
		break; 
 
    
    case 'Inicio Importar':
		// Grabamos la configuracion en log,
		$configuracion  =json_encode($_POST['configuracion'][0]);
		$mensaje_log = "Configuracion:\n".$configuracion."\n";
		$ruta =  $RutaServidor.$RutaDatos.'/log_tpvFox';
		$fichero_log = ComprobarExisteLogTpv($ruta,$mensaje_log);
		$respuesta['log'] = $fichero_log;
		$respuesta['mensaje']= $mensaje_log;
		// Grabamos configuracion en BDTpv 
		 header('Content-Type: application/json');
		echo json_encode($respuesta,true);
		break;
	
    case 'Preparar insert':
		$Arraytablas = $_POST['tablasImpor'];
		$respuesta= prepararInsertTablasBDTpv($BDVirtuemart,$Arraytablas);
		header('Content-Type: application/json');
		echo json_encode($respuesta,true);
		break;
	
    case 'Vaciar tablas':
		$tablas = $_POST['tablas'];
		// Llamamos fuciones de vaciar.
		$respuesta = EliminarArticulosTpv($BDTpv,$tablas,$TControlador);
		echo json_encode($respuesta,true); 
		break;	
	
    case 'Realizar insert':
		$inserts = $_POST['InsertRealizar'];
		// Llamamos fuciones de vaciar.
		$respuesta= RealizarInsert($inserts,$BDTpv);
		echo json_encode($respuesta,true);
		break;	
	
	case 'Crear Tabla Temporal':
		$tablaTemporal = $_POST['TablaTemporal'];
		// Llamamos fuciones de vaciar.
		$CrearTablaTemporal = CrearTablasTemporales($BDVirtuemart,$tablaTemporal);
		// Comprobamos si existe funcion Before de tabla
		$func = 'BeforeTabla_'.$tablaTemporal['nombre_tabla_temporal'];

		if (function_exists($func)){
			// llamamos a funcion:
			$CrearTablaTemporal['Funcion_before']=$func($BDVirtuemart);
		}
		echo json_encode($CrearTablaTemporal,true);
		break;	
	
	case 'Comprobaciones' :
		$funcion = 	$_POST['funcion'];
		//@ Objetivo:
		// Ejecutar la funcion que le recibimos para comprobar.
		//@ Parametros: 
		// 		funcion -> comprobacions ( array) que tenemos Importar_virtuemart
		//@ Respuesta:
		// [ resultado ] = El resultado de funcion, tenmos que tenerlo clasificado por subproceso si los hubiera.
		// Ejemplo:
		// ComprobarTablaTempArticulosCompleta , donde hay dos subproceso.
		// resultado ->
		// 			[subproceso]
		//				[estado] = true /false
		//				[error] = Indicando error
		//				... Si necesitamos mas datos...
		$respuesta = array();
		switch ($funcion['nom_funcion']) {
			case 'ComprobarTablaTempArticulosCompleta':
				$resultado = ComprobarTablaTempArticulosCompleta ($BDVirtuemart);
				$respuesta = $resultado;
				break;

			case 'ComprobarTablaTempClientes':
				$resultado = ComprobarTablaTempClientes($BDVirtuemart);
				$respuesta = $resultado;
				break;
			
			default:
				// Creamos array respuesta error
				$proceso = $funcion['nom_funcion'];
				$subprocesos = $funcion['subprocesos'];
				foreach ($subprocesos as $subproceso){
					$respuesta[$subproceso]['estado']= false;
					$respuesta[$subproceso]['error'] = ' No se encontro proceso a ejecutar ' .$proceso;
				}
		}
		
		echo json_encode($respuesta,true);
		break;	
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDTpv);

 
 
?>
