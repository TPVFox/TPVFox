<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */


$pulsado = $_POST['pulsado'];
include_once ("./../../inicial.php");
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_cierres/funciones.php';
include_once $URLCom.'/modulos/mod_cierres/clases/ClaseCierres.php';
$CCierres = new ClaseCierres;

 switch ($pulsado) {
     
    case 'insertarCierre':
		$datosCierre = $_POST['datos_cierre'];
		$respuesta =  array();
		$respuesta = InsertarProceso1Cierres($BDTpv, $datosCierre);
		
		
		echo json_encode($respuesta);
    break;
    
    
    
    /* **************************************************************	*
     * 			LLAMADAS FUNCIONES COMUNES MODULO CIERRES Y TPV			*
     * **************************************************************	* 	*/
     case 'buscarClientes':
		// Abrimos modal de clientes.
		$busqueda = $_POST['busqueda'];
		$tabla='clientes';
		$res = array( 'datos' => array());
		//funcion de buscar clientes
		//luego html mostrar modal 
		if ($busqueda != ''){
			//$res = BusquedaClientes($busqueda);
			$res = BusquedaClientes($busqueda,$BDTpv,$tabla);
		} 
		
		$respuesta = htmlClientes($busqueda,$dedonde,$res['datos']);
	
		echo json_encode($respuesta);
		break;


    case 'BorrarCierre':
        // Obtenemos el ultimos idCierre
        $idSeleccionado = $_POST['idSeleccionado'];
        $respuesta= $CCierres->borrarDatos_tablasCierres($idSeleccionado);
        echo json_encode($respuesta);
		break;
}



 
 
?>
