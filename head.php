<?php
include_once 'inicial.php';
?>
<meta name="language" content="es">
<meta charset="UTF-8">
<link rel="stylesheet" href="<?php echo $HostNombre;?>/jquery/jquery-ui.min.css" type="text/css">
<link rel="stylesheet" href="<?php echo $HostNombre;?>/css/bootstrap.min.css" type="text/css">
<link rel="stylesheet" href="<?php echo $HostNombre;?>/css/template.css" type="text/css">

<script src="<?php echo $HostNombre;?>/jquery/jquery-2.2.5-pre.min.js"></script>
<script src="<?php echo $HostNombre;?>/css/bootstrap.min.js"></script>
<?php
	
	if ($_SESSION['estadoTpv'] != "Correcto"){
		// Controlamos si no hay session correcta mostramos formulario de acceso.
		// Asi evitamos el inicio de procesos si no hay usuario.
		// con ello evito errores innecesarios.
		include_once ($URLCom."/plugins/controlUser/modalUsuario.php");
		echo '</head>';
		echo '</body>';
		echo '</html>';
		exit;	
	}
?>
