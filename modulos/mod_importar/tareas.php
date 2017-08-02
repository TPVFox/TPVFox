
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

 
 switch ($pulsado) {
    case 'Inicio':
		$nombreTabla = $_POST['Fichero'];
		$fichero = $RutaServidor.$CopiaDBF.'/'.$nombreTabla;
		$respuesta = LeerEstructuraDbf($fichero);
		echo json_encode($respuesta) ;
		break;
	case 'Comprobar-tabla':
		$nombreTabla = $_POST['Fichero'];
		$campos = $_POST['campos'];
	//	$vaciar = $_POST['vaciar'];  recojo si esta checkado tabla para vaciar datos
		// $Conexiones se obtiene en modulo de conexion.
		$conexion = $Conexiones[1]['tablas'];
		$respuesta = ComprobarTabla($nombreTabla,$conexion,$BDImportDbf,$campos);
		//~ $respuesta['Vaciar'] = array("uno","dos");
		echo json_encode($respuesta);
		break;
    case 'obtenerDbf':
		$numInicial = $_POST['lineaI'];
		$numFinal = $_POST['lineaF'];
		$campos = $_POST['campos']; 
		$nombreTabla = $_POST['Fichero'];	//quitar dbf subsrt($cadena,0,-3); 
		$nombreTablaSin = substr($nombreTabla,0,-4);  //al fichero le tengo que quitar .dbf 
      
		$fichero = $RutaServidor.$CopiaDBF.'/'.$nombreTabla;	//añadir dbf
		
        $datosDbf = LeerDbf($fichero,$numFinal,$numInicial,$campos);
      
        
        $respuesta = InsertarDatos($campos,$nombreTablaSin,$datosDbf,$BDImportDbf);
        //~ $respuesta['sin dbf'] = $nombreTablaSin;
        //ejecutar func para conectar/volcar con mysql bbdd 
		//$respuesta = $datosDbf;
        echo json_encode($respuesta) ;
        break;
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportDbf);

 
 
?>
