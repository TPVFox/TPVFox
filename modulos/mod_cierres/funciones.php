<?php 
function ticketsPorFechaUsuario($fechaInicio,$BDTpv,$fechaFinal){
	// Objetivo:
	// Obtener los ticket cobrados, que son los pendiente para hacer cierre.
	
	//Inicializo variables.
	$formasPago = array();
	$resultado = array();
	
	//Obtenemos los ticket Abiertos 
	
	//muestro datos del ticket donde fecha mayor fecha inicio y menor que nueva fecha (fecha+1)
	$sql ='SELECT * FROM `ticketst` WHERE DATE_FORMAT(`Fecha`,"%d-%m-%Y") BETWEEN "'.$fechaInicio.'"'
		.' AND "'.$fechaFinal.'" and `estado`="Cobrado"';
	
	$resp = $BDTpv->query($sql);
	
	
	if($resp->num_rows > 0){
		$i=0; 
		while ($fila = $resp->fetch_assoc()) {
			//~ $resultado['tickets'][] = $fila;
			$usuario= $fila['idUsuario'];
			//monto array Usuarios[idUsuario][tickets en cada usuario][numTickets]
			$resultado['usuarios'][$usuario]['ticket'][$i] = $fila;
			//if no existe total usuario, creamos total, sino se lo sumamos 
			//array usuarios[idusuario][total suma de los tickets]
			if (!isset($resultado['usuarios'][$usuario]['total'])){
				$resultado['usuarios'][$usuario]['total']=$fila['total'];
			} else{
				$resultado['usuarios'][$usuario]['total'] +=$fila['total'];
			}
			//si no existe numInicial lo creamos y el final siempre lo grabamos
			if (!isset($resultado['usuarios'][$usuario]['NumInicial'])){
				$resultado['usuarios'][$usuario]['NumInicial'] = $fila['Numticket'];
				$resultado['usuarios'][$usuario]['NumFinal'] = $fila['Numticket'];   
			} else {
				$resultado['usuarios'][$usuario]['NumFinal'] = $fila['Numticket'];   
			}
			$fPago = $fila['formaPago'];
			$formasPago[]=$fPago;
			//si no existe forma de pago cojo la primera, si existe voy sumando sobre ella.
			if (!isset($resultado['usuarios'][$usuario]['formasPago'][$fPago])){
				$resultado['usuarios'][$usuario]['formasPago'][$fPago]=$fila['total'];

			} else{
				$resultado['usuarios'][$usuario]['formasPago'][$fPago] +=$fila['total'];
				
			}
			
			//si no existe se crea sino el total de cada ticket de todos los usuarios lo voy sumando
			if (!isset($resultado['totalcaja'])){
				$resultado['totalcaja'] = $fila['total'];
			} else {
				$resultado['totalcaja'] += $fila['total'];
			}
			//cojo el id ticketst para luego relacionar con ticketstIva con idticketst
			$resultado['rangoTickets'][$i]= $fila['id']; 
			$i++;
		}
		
		
		
	}
	
	
	// === Devolvemos tambien los ticketsAbiertos.
	$resultado['tickets_abiertos'] = obtenerTicketAbiertos($BDTpv,$fechaInicio,$fechaFinal);
	
	
	
	
	
	$resultado['formasPago']=array_unique($formasPago);
	$resultado['sql'] = $sql;
	return $resultado;
}

//concretar con fecha.. 
function nombreUsuario($BDTpv,$idUsuario){
	//$sql='SELECT username,nombre FROM `usuarios` WHERE `id`='.$idUsuario;
	$sql=' SELECT u.username, u.nombre, t.Numticket FROM `usuarios` AS u '
		.'LEFT JOIN `ticketst` AS t  ON t.idUsuario = u.id WHERE t.idUsuario='.$idUsuario;
		
//		SELECT u.username, u.nombre, t.* FROM `usuarios` AS u LEFT JOIN `ticketst` AS t ON t.idUsuario = u.id WHERE t.idUsuario=12 AND t.Fecha>'2017-10-01' AND t.Fecha<='2017-10-02' 
	$resp = $BDTpv->query($sql);
	$resultado=array();
	if ($resp->num_rows > 0) {
		while($fila = $resp->fetch_assoc()) {
			$resultado['datos']=$fila;
			//~ $resultado['rangoTickets'][$i]= $fila['Numticket'];
			//~ $resultado['numTicket'] = $fila['Numticket'];
			//~ $i++;
		}
	}
	return $resultado;
}


function baseIva($BDTpv,$idticketst){
	//@ tabla : ticketstIva
	//@ campo : idticketst
	//@ Objetivo:
	//Agrupamos por iva, para obtener sumIva, sumBase
	
	//se le pasa idtickets, e iva, para recoger sum(importeIva) y suma(totalbase)
	//seria idtickets de ticketstIva es la relacion de id de ticketst, porque 2 usuarios pueden tener mismo NumTicket.
	
	
	$sql ='SELECT SUM(`importeIva`) AS importeIva, SUM(`totalbase`) AS importeBase, iva '
		.' FROM `ticketstIva` '
		.' WHERE `idticketst` IN ('.$idticketst.') GROUP BY `iva`';
	$resp = $BDTpv->query($sql);
	$resultado = array();
	if ($resp->num_rows > 0) {
		$i=0;
		while($fila = $resp->fetch_assoc()) {		
			$resultado['items'][$i]=$fila;
			$i++;
			
		}
		$resultado['sql'] = $sql;

	} else {
		$resultado=0;
	}
	
	

	return $resultado;
}

function obtenerCierres($BDTpv ,$filtro) {
	// Function para obtener cierres y listarlos
	//tablas usadas: - cierres
				//	 - usuarios
	$resultado = array();
	$consulta = "Select c.*, u.nombre as nombreUsuario FROM cierres AS c "
				." LEFT JOIN usuarios AS u ON c.idUsuario=u.id ".$filtro; 
	
	$Resql = $BDTpv->query($consulta);	
	while ($datos = $Resql->fetch_assoc()) {
			$resultado[]=$datos;
	}
	//$resultado ['sql'] = $consulta;
	return $resultado;
}

function fechaMaxMinTickets($BDTpv){
	//Objetivo:
	//conseguir fecha min y max del primer y ultimo ticket cobrado
	// 1447431666 devuelve el valor del argumento como segundos desde '1970-01-01 00:00:00'UTC. UNIX TIMESTAMP c/hora
	$respuesta=array();
	$sql = 'SELECT UNIX_TIMESTAMP(min(`Fecha`)) as fechaMin, UNIX_TIMESTAMP(max(`Fecha`)) as fechaMax '
			.' FROM `ticketst` WHERE `estado` = "Cobrado" ';
	$resp = $BDTpv->query($sql);
	while($fila = $resp->fetch_assoc()) {
		$respuesta['fechas']=$fila;
	}
	
	$respuesta['sql']= $sql;
	return $respuesta;
}

function InsertarProceso1Cierres($BDTpv,$datosCierre){
	// Objetivos :
	// 	Proceso 1 
	// 		1.1- Insert las tabla cierres
	// 		1.2- Update de ticket cambiando el estado Cerrado
	//		1.3- Obtener el num_cierre para poder hacer los demas inserts.
	//  Proceso 2
	// 		2.1- Insert en tabla Ivas
	// 	Proceso 3
	// 		3.1- Insert en tabla usuarios
	$resultado=array();
	$tabla = 'cierres';
	$fecha_dmYHora = '%d-%m-%Y %H:%i:%s';
	$fecha_dmY = '%d-%m-%Y';
	$idTienda = $datosCierre['tienda'];
	$idUsuario = $datosCierre['idUsuarioLogin'];
	$FechaInicio = $datosCierre['fechaInicio_tickets'];	//('d-m-Y H:i:s');
	$FechaFinal = $datosCierre['fechaFinal_tickets'];	//('d-m-Y H:i:s');
	$total = $datosCierre['totalcaja'];
	$fechaCierre = $datosCierre['fechaCierre'];	//('d-m-Y ');
	$fechaCreacion =$datosCierre['fechaCreacion']; //('d-m-Y H:i:s');
	$fInicSinHora = $datosCierre['FinicioSINhora'];
	$fFinalSinHora = $datosCierre['FfinalSINhora'];
	$rangoTickets = '('.implode(',',$datosCierre['rangoTickets']).')'; // Montamos string con rango tickets	
	//en mysql formato fecha es 'Y-m-d' y aqui trabajamos con 'd-m-Y'
	//convierto fecha a string para insertar en cierres, formateo fecha para insertar sql
	$formateoFechaInicio = ' STR_TO_DATE("'.$FechaInicio.'","'.$fecha_dmYHora.'") ';
	$formateoFechaFinal = ' STR_TO_DATE("'.$FechaFinal.'","'.$fecha_dmYHora.'")  '; 
	$formateoFechaCierre = ' STR_TO_DATE("'.$fechaCierre.'","'.$fecha_dmY.'") ';//fecha sin hora
	$formateoFechaCreacion = ' STR_TO_DATE("'.$fechaCreacion.'","'.$fecha_dmYHora.'") ';//fecha sin hora

	$estadoCierre = 'Cerrado';
	$insertCierre = 'INSERT INTO '.$tabla.' (idTienda, idUsuario, FechaInicio, FechaFinal, Total, FechaCierre, FechaCreacion) VALUES ("'
			.$idTienda.'" , "'.$idUsuario.'" ,  '.$formateoFechaInicio.' , '.$formateoFechaFinal.' , '
			.' "'.$total.'" , '.$formateoFechaCierre.' , '.$formateoFechaCreacion.' )';
	
	//actualizar tickets estado = Cobrado a estado = Cerrado
	$updateEstado = 'UPDATE ticketst SET `estado`= "'.$estadoCierre.'" WHERE `estado` = "Cobrado"'
					.' AND DATE_FORMAT(`Fecha`,"%d-%m-%Y") BETWEEN "'.$fInicSinHora.'"'
					.' AND "'.$fFinalSinHora.'" AND id IN '.$rangoTickets;
	//insertamos datos para cierre, si es correcto se Actualiza estado de tickets a 'Cerrado'
	if ($BDTpv->query($insertCierre) === true){
		$idCierre = $BDTpv->insert_id; //crea id en bbddd 
		$resultado['insertarCierre']='Correcto';
		$resultado['idCierre']=$idCierre;
	} else {
		// Quiere decir que hubo error en insertar en cierres
		$resultado['error'] = 'Error en Insert de CIERRES Numero error:'.$BDTpv->errno;
	}

	// Realizamo UPDATE de ticketst
	if ($BDTpv->query($updateEstado) === true){
		//actualizacion hecha
		$resultado['Nafectados_CambioEstado_tickets'] = $BDTpv->affected_rows;
		$resultado['update_estado']='Correcto';
	} else {
		// Quiere decir que hubo error en insertar en cierres
		$resultado['error'] = 'Error en Update de ticketst-> Numero error:'.$BDTpv->errno;
	}
	$resultado['insertCierres'] = $insertCierre;
	$resultado['updateTicketst'] = $updateEstado;
	$respuesta = array() ; // Lo que vamos a devolver...
	$respuesta['Proceso1'] = $resultado; 
	
	//Insertamos en la tabla cierres_ivas 
	$insertarIvas = insertarCierre_IVAS($BDTpv,$datosCierre,$idCierre);
	$respuesta['Proceso2']=$insertarIvas;
	
	//Insertamos en la tabla cierres_usuarios_tickets
	$insertarUsuarios = insertarUsuariosCierre($BDTpv,$datosCierre,$idCierre);
	$respuesta['Proceso3']=$insertarUsuarios;

	//debug
	//~ $resultado['datos'] = $datosCierre;
	return $respuesta;
}

function insertarCierre_IVAS($BDTpv,$datosCierre,$idCierre){
	//Objetivo:
	//insertar por cada iva, importe_base, importe_iva, idTienda, idCierre
	$resultado= array();
	$idTienda = $datosCierre['tienda'];
	$idUsuario = $datosCierre['idUsuarioLogin'];
	$sumasIvasBases = $datosCierre['sumasIvas'];

	$i = 0;
	foreach($sumasIvasBases as $key=>$sumaBaseIva){
		$iva[$key]= $sumaBaseIva['iva'];
		$sumaBase[$key] = $sumaBaseIva['importeBase'];
		$sumaIva[$key] = $sumaBaseIva['importeIva'];
		
		//~ //inserto por cada iva, su sumaBase y su sumaIva
		$sql[$i] = 'INSERT INTO `cierres_ivas`(`idCierre`, `idTienda`, `tipo_iva`, `importe_base`, `importe_iva`) '
			.' VALUES ('.$idCierre.' , '.$idTienda.' , '.$iva[$key].' , '.$sumaBase[$key].' , '.$sumaIva[$key].')';
		if ($BDTpv->query($sql[$i]) === true){
			$resultado['insertar_ivas_cierre'] = 'Correcto';
		} else {
			// Quiere decir que hubo error en insertar en cierresIvas
			$resultado['error'] = 'Error en Insert de cierres IVAS->'.$i.' Numero error:'.$BDTpv->errno;
		}
		$resultado['InsertsIvas'][$i] = $sql;
		$i++;
		
	}
	return $resultado;
}

function insertarUsuariosCierre($BDTpv,$datosCierre,$idCierre){
	//necesitamos: idCierre, idUsuario, idTienda, Importe, Num_ticket_inicial, Num_ticket_final 
	//Insertamos datos por cada usuario
	$resultado= array();
	$idTienda = $datosCierre['tienda'];
	$usuarios = $datosCierre['usuarios'];
	$modoPago = $datosCierre['modoPago'];
	
	$x = 0;
	foreach ($usuarios as $idUsuario =>$usuario){ 
		$num_ticket_inicial =	$usuario['NumInicial']; 
		$num_ticket_final = $usuario['NumFinal'];
		$usuarioFpago= $modoPago[$idUsuario];
		$formasPago= $usuarioFpago['formasPago']; //array 
	
		//******** Inicio insertar por usuario FormasPago ***********//
		//si es correcto insertamos formas de pago en otra tabla
		$resultado['ArrayFormasPago'] = $formasPago;

		foreach ($formasPago as $nombre=>$importe){
			//~ $importe= $formasPago[$nombre]['importe']; 
			
			//inserto en FormasPago
			//x es contador por cada usuario, nombre= nombreFpago
			$sqlFpagoCierres[$x][$nombre]='INSERT INTO `cierres_usuariosFormasPago` '
				.' ( `idCierre`, `idTienda`, `idUsuario`, `FormasPago`, `importe`) '
				.' VALUES ('.$idCierre.','.$idTienda.','.$idUsuario.',"'.$nombre.'",'.$importe.')';
				
			$resultado['sqlFormaPago'][$x] = $sqlFpagoCierres[$x][$nombre];

			if ($BDTpv->query($sqlFpagoCierres[$x][$nombre]) === true){
				$resultado['insertar_FpagoCierres']='Correcto';
			} else {
				// Quiere decir que hubo error en insertar en cierresIvas
				$resultado['error'] = 'Error en Insert de Formas pago error:'.$BDTpv->errno;
			
			}
			//cogemos sumaImportes por usuario para insertar en cierres_usuarios_tickets
			if (!isset($sumaImporte[$idUsuario])){
				$sumaImporte[$idUsuario]=$importe;
			} else{				
				$sumaImporte[$idUsuario]+=$importe;
			}
		}
		
		//$resultado['pr']=$sumaImporte[$idUsuario];
		$sqlUsuariosCierre[$x] = 'INSERT INTO `cierres_usuarios_tickets` '
			.' ( `idCierre`, `idUsuario`, `idTienda`, `Importe`, `Num_ticket_inicial`, `Num_ticket_final`) '
			.' VALUES ('.$idCierre.' ,  '.$idUsuario.', '.$idTienda.' , '.$sumaImporte[$idUsuario].' ,'.$num_ticket_inicial.','.$num_ticket_final.')';
		$resultado['sqlUsuarios'][$x] = $sqlUsuariosCierre[$x];
		if ($BDTpv->query($sqlUsuariosCierre[$x]) === true){
				$resultado['insertarTickets_usuarios']='Correcto';
		} else {
			// Quiere decir que hubo error en insertar en cierresIvas
			$resultado['error'] = 'Error en Insert de cierres USUARIOS tickets Numero error:'.$BDTpv->errno;
		}

		//******** fin insertar por usuario FormasPago ***********//
		$x++;
	}
		
	return $resultado;
}

function obtenerCierreUnico($BDTpv, $idCierre){
	// consulta sql join para obtener los datos de un cierre concreto
	// montar bien array para mostrarlos luego en html
	$resultado = array();
	
	//consultamos cierres_usuarios_tickets y usuarios para poder mostrar nombre del usuario
	$sqlUsuarioTickets = 'SELECT usuarioTicket.*, nombreUsu.username '
				.' FROM cierres_usuarios_tickets as usuarioTicket '
				.' LEFT JOIN usuarios as nombreUsu ON nombreUsu.id = usuarioTicket.idUsuario '
				.'WHERE usuarioTicket.idCierre = "'.$idCierre.'"';
				
				
	//consultamos cierres_usuariosFormasPago
	$consultaFpago=' SELECT fpago.*, u.username FROM cierres_usuariosFormasPago as fpago '
				.' LEFT JOIN usuarios AS u ON fpago.idUsuario = u.id '
				.' WHERE fpago.idCierre = "'.$idCierre.'"';

	//ataco cierres y cierres_ivas
	$consulta =	' SELECT cierre.*, ivas.* '
				.' FROM `cierres` as cierre '
				.' INNER JOIN `cierres_ivas` as ivas ON cierre.idCierre = ivas.idCierre '
				.' WHERE cierre.idCierre =  "'.$idCierre.'"';
	//montaje de cierres segun el idCierre y los ivas segun idCierre y tipos de ivas
	$Resql = $BDTpv->query($consulta);
	$i = 0;
	while ($datos = $Resql->fetch_assoc()) {
		$resultado['cierres'][$idCierre]['FechaCierre']= $datos['FechaCierre'];
		$resultado['cierres'][$idCierre]['idTienda']= $datos['idTienda'];
		$resultado['cierres'][$idCierre]['idUsuario']= $datos['idUsuario'];		
		$resultado['cierres'][$idCierre]['FechaInicio']= $datos['FechaInicio'];			
		$resultado['cierres'][$idCierre]['FechaFinal']= $datos['FechaFinal'];			
		$resultado['cierres'][$idCierre]['FechaCreacion']= $datos['FechaCreacion'];			
		$resultado['cierres'][$idCierre]['Total']= $datos['Total'];	
	
		$resultado['ivas'][$idCierre][$i]['tipo_iva'] = $datos['tipo_iva'];
		$resultado['ivas'][$idCierre][$i]['importe_base'] = $datos['importe_base'];		
		$resultado['ivas'][$idCierre][$i]['importe_iva'] = $datos['importe_iva'];
		
		$i++;		
	}
	
	//montaje de array fpago, por usuarios
	$sqlFpago = $BDTpv->query($consultaFpago);
	
	while ($fpago = $sqlFpago->fetch_assoc()) {
		 $idUsuario= $fpago['idUsuario'];		
		$resultado['fpago'][$idUsuario]['nombre']=$fpago['username'];
		$resultado['fpago'][$idUsuario]['idUsuario']=$fpago['idUsuario'];
		$formaPago = $fpago['FormasPago'];
		$formasPago[]=$formaPago;
		$resultado['fpago'][$idUsuario]['formas'][$formaPago] = $fpago['importe'];
		
	}
	
	//montaje de array de usuarios tickets cierres
	$sqlUsuario = $BDTpv->query($sqlUsuarioTickets);
	$z=0;
	while ($usuarios =$sqlUsuario->fetch_assoc()){
		$resultado['usuario'][$z]['nombreUsuario']= $usuarios['username'];
		$resultado['usuario'][$z]['idUsuario']=$usuarios['idUsuario'];
		$resultado['usuario'][$z]['Importe']=$usuarios['Importe'];
		$resultado['usuario'][$z]['Num_ticket_inicial']=$usuarios['Num_ticket_inicial'];
		$resultado['usuario'][$z]['Num_ticket_final']=$usuarios['Num_ticket_final'];
		$z++;
	}
	
	//$resultado ['sql'] = $sqlUsuarioTickets;
	return $resultado;
	
}


function ArrayFechaUnix ($Unix,$nombre){
	// @ Objetivo 
	// Recibir una fecha Unix y montar array con los distintos formatos y maquetados.
	// @ Parametros
	// 		$Unix: Fecha en sistema Unix
	//		$nombre: Nombre asociativo del array
	// Devuelve un string
	
	// Obtenemos date_parse ( un array con los datos).
	$resultado = array();
	$fecha_dmYHora = '%d-%m-%Y %H:%M:%S';
	$fecha_dmY = '%d-%m-%Y';

	$resultado[$nombre]['Epoch-Unix']=$Unix;
	$resultado[$nombre]['String_d-m-y_hora'] = strftime($fecha_dmYHora,$Unix);
	$resultado[$nombre]['String_d-m-y'] = strftime($fecha_dmY,$Unix);

	$resultado[$nombre]['date'] = date_parse($resultado[$nombre]['String_d-m-y_hora']);

	return $resultado;
}


function obtenerTicketAbiertos($BDTpv,$fechaInicio,$fechaFinal) {
	// Objetivo:
	// Es obtener cantidad de tickets abiertos agrupados por usuarios en el intervalo de fecha que le enviamos,
	// @ Parametros 
	// 		$fechaInicio -> fecha formato d-m-y_hora
	// 		$fechaFinal -> fecha formato d-m-y_hora
	$resultado = array();
	$sqlAbiertos = 'SELECT count(t.`numticket`) as suma, t.`idUsuario`, u.username as username '
				.' FROM `ticketstemporales` as t '
				.' LEFT JOIN usuarios as u ON u.id=t.idUsuario'
				.' WHERE  DATE_FORMAT(t.`fechaInicio`,"%d-%m-%Y") >= "'.$fechaInicio.'" AND  '
				. 'DATE_FORMAT(t.`fechaFinal`,"%d-%m-%Y") <= "'.$fechaFinal.'" and t.`estadoTicket`="Abierto" GROUP BY `idUsuario` ';
	
	$respAbiertos =$BDTpv->query($sqlAbiertos);
	if($respAbiertos->num_rows > 0){
		while ($row = $respAbiertos->fetch_assoc()){
			$resultado[]=$row;
		}
	}
	//~ $resultado[] =$sqlAbiertos;
	return $resultado;
}




//~ function borrarDatos_tablasCierres(){
//~ //cambia estado tickets de cerrados a Cobrados, seria indicarle fecha campo=Fecha
//~ UPDATE ticketst SET `estado`= "Cobrado" WHERE `estado` = "Cerrado";

 //~ //Borramos datos de las 4 tablas de cierres, poniendo un rango de id
//~ DELETE FROM `cierres` WHERE `idCierre` BETWEEN "0" AND "150";
//~ DELETE FROM `cierres_ivas` WHERE `idCierre` BETWEEN "0" AND "150";
//~ DELETE FROM `cierres_usuarios_tickets` WHERE `idCierre` BETWEEN "0" AND "150";
//~ DELETE FROM `cierres_usuariosFormasPago` WHERE `idCierre` BETWEEN "0" AND "150";

//~ //inicializamos el id =1 . auto incremental
//~ ALTER TABLE `cierres_usuariosFormasPago` auto_increment = 1; 
//~ ALTER TABLE `cierres_usuarios_tickets` auto_increment = 1; 
//~ ALTER TABLE `cierres_ivas` auto_increment = 1; 
//~ ALTER TABLE `cierres` auto_increment = 1; 
//~ }
?>
