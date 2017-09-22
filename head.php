<?php
	// Recuerda que este fichero se include en muchos ficheros, por lo que
	// la ruta getcwd() no es la misma siempre.
	if (isset($DirectorioInicio)) {
		$Ruta = './';
	} else {
		$Ruta = './../../' ; // Porque estoy en modulo...
		// Esto tiene porque ser así se podría asignar antes, desde el fichero que include.
	}

	include_once ($Ruta.'configuracion.php');
   	$URLCom = $RutaServidor . $HostNombre;
	include_once ($URLCom."/modulos/mod_conexion/conexionBaseDatos.php");
	//incluyo ruta del controlador de sesion (funcion php)
    include_once ($URLCom. "/plugins/controlUser/funcion.php");

	// Solo creamos objeto si no existe.
	$thisTpv = new ComprobarSession;
	$TPVsession= $thisTpv->comprobarEstado($BDTpv, $URLCom);
//coment
?>

<meta name="language" content="es">
<meta charset="UTF-8">
<link rel="stylesheet" href="<?php echo $HostNombre;?>/css/bootstrap.min.css" type="text/css">
<link rel="stylesheet" href="<?php echo $HostNombre;?>/css/template.css" type="text/css">

<script src="<?php echo $HostNombre;?>/jquery/jquery-2.2.5-pre.min.js"></script>
<script src="<?php echo $HostNombre;?>/css/bootstrap.min.js"></script>
