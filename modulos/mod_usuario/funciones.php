<?php 


// Funciones para vista Recambio unico.
function obtenerUsuarios($BDTpv) {
	// Function para obtener usuarios y listarlos

	$usuarios = array();
	$consulta = "Select * from usuarios";
	$ResUsuarios = $BDTpv->query($consulta);
	$usuarios['NItems'] = $ResUsuarios->num_rows;
	$i = 0;
	
	while ($usuario = $ResUsuarios->fetch_assoc()) {
			$usuarios['items'][$i]['id'] = $usuario['id'];
			$usuarios['items'][$i]['username'] = $usuario['username'];
			$usuarios['items'][$i]['nombre'] = $usuario['nombre'];
			$usuarios['items'][$i]['fecha'] = $usuario['fecha'];
			$usuarios['items'][$i]['group_id'] = $usuario['groupid'];
			$usuarios['items'][$i]['block'] = $usuario['estado'];
	$i = $i+1;
	}

	$usuarios ['consulta'] = $consulta;
	return $usuarios;
}

?>
