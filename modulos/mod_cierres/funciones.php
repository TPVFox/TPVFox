<?php 
function ticketsPorFechaUsuario($fechaInicio,$BDTpv,$nuevafecha){
	//creo array de formasPago
	$formasPago = array();
	$resultado = array();
	
	//muestro datos del ticket donde fecha mayor fecha inicio y menor que nueva fecha (fecha+1)
	$sql ='SELECT * FROM `ticketst` WHERE DATE_FORMAT(`Fecha`,"%d-%m-%Y") BETWEEN "'.$fechaInicio.'"'
		.' AND "'.$nuevafecha.'" and `estado`="'.Cobrado.'"';
	
	$resp = $BDTpv->query($sql);
	//SELECT count(`numticket`), `idUsuario`, `fechaInicio`, `fechaFinal` FROM `ticketstemporales` WHERE `estadoTicket`='Abierto' GROUP BY `idUsuario` 
	
	//consulta ticketsAbiertos en tablaTemporal
	//Obtenemos cuantos tickets tienen cada usuario.
	//DATE_FORMAT('fecha','dia-mes-anho'), le indicamos como es el formato de nuestras fechas.
	$sqlAbiertos = 'SELECT count(`numticket`) as suma, `idUsuario`, DATE_FORMAT(`fechaInicio`,"%d-%m-%Y") as fechaInicio '
				.' FROM `ticketstemporales` '
				.' WHERE  DATE_FORMAT(`fechaInicio`,"%d-%m-%Y") > "'.$fechaInicio.'" AND  '
				. 'DATE_FORMAT(`fechaFinal`,"%d-%m-%Y") < "'.$nuevafecha.'" and `estadoTicket`="'.Abierto.'" GROUP BY `idUsuario` ';
	
	$respAbiertos =$BDTpv->query($sqlAbiertos);
	if($respAbiertos->num_rows > 0){
		while ($row = $respAbiertos->fetch_assoc()){
			$resultado['abiertos'][]=$row;
		}
	}
	
	
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
			} else {
				$resultado['usuarios'][$usuario]['NumFinal'] = $fila['Numticket'];   
			}
			//si no existe el final siempre lo grabamos
			if (!isset($resultado['usuarios'][$usuario]['NumFinal'])){
				$resultado['usuarios'][$usuario]['NumFinal'] = $fila['Numticket'];
			} else {
				$resultado['usuarios'][$usuario]['NumFinal'] = $fila['Numticket'];   
			}
			$fPago = $fila['formaPago'];
			$formasPago[]=$fPago;
			//si no existe forma de pago cojo la primera, si existe voy sumando sobre ella.
			if (!isset($resultado['usuarios'][$usuario][$fPago])){
				$resultado['usuarios'][$usuario]['formasPago'][$fPago]=$fila['total'];
				//si no existe tarjeta o contado, cojo el primer resultado, si existe lo voy sumando. 
				if (!isset($formasPago)){
					$resultado['usuarios'][$usuario][$fPago] = $fila['total'];
				} else {
					$resultado['usuarios'][$usuario][$fPago] += $fila['total'];
				}
				
			} else{
				$resultado['usuarios'][$usuario]['formasPago'][$fPago] +=$fila['total'];
				
			}
			
			//si no existe se crea sino el total de cada ticket de todos los usuarios lo voy sumando
			if (!isset($resultado['totalcaja'])){
				$resultado['totalcaja'] = $fila['total'];
			} else {
				$resultado['totalcaja'] += $fila['total'];
			}
						
			//$resultado['rangoTickets'][$i]= $fila['Numticket']; //necesito array numTickets para obtener sumaBasesIvas
			$resultado['rangoTickets'][$i]= $fila['id']; //cojo el id ticketst para luego relacionar con ticketstIva con idticketst
			$i++;
		}
		
		
		
	}
	$resultado['formasPago']=array_unique($formasPago);
	$resultado['sql'] = $sql;
	$resultado['sqlAbiertos']= $sqlAbiertos;
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

function obtenerCierres($BDTpv,$LimitePagina ,$desde,$filtro) {
	// Function para obtener clientes y listarlos
	//tener en cuenta el  paginado con parametros: $LimitePagina ,$desde,$filtro

//para evitar repetir codigo
	$Controler = new ControladorComun; 
	$campoBD = 'fecha';
	$campo2BD = '';
	$rangoFiltro = $Controler->paginacionFiltroBuscar($BDTpv,$filtro,$LimitePagina,$desde,$campoBD,$campo2BD);
	$rango=$rangoFiltro['rango'];
	$filtroFinal=$rangoFiltro['filtro'];
//fin paginacion y filtro de busqueda 


	$resultado = array();
	$consulta = "Select c.*, u.nombre as nombreUsuario FROM cierres AS c "
				." LEFT JOIN usuarios AS u ON c.idUsuario=u.id ".$filtroFinal.$rango; 
	
	$Resql = $BDTpv->query($consulta);
	//$cierres['NItems'] = $Resql->num_rows;
	
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
			.' FROM `ticketst` WHERE `estado` = "'.Cobrado.'" ';
	$resp = $BDTpv->query($sql);
	while($fila = $resp->fetch_assoc()) {
		$respuesta=$fila;
	}
	
	$respuesta['sql']= $sql;
	return $respuesta;
}

function insertarCierre($BDTpv,$datosCierre){
	$resultado=array();
	$tabla = 'cierres';
	$fecha_dmYHora = '%d-%m-%Y %H:%i:%s';
	$fecha_dmY = '%d-%m-%Y';
	foreach ($datosCierre as $dato){
		$idTienda = $dato['tienda'];
		$idUsuario = $dato['idUsuarioLogin'];
		$FechaInicio = $dato['fechaInicio_tickets'];	//('d-m-Y H:i:s');
		$FechaFinal = $dato['fechaFinal_tickets'];	//('d-m-Y H:i:s');
		$total = $dato['totalFpago'];
		$fechaCierre = $dato['fechaCierre'];	//('d-m-Y ');
		$fechaCreacion =$dato['fechaCreacion']; //('d-m-Y H:i:s');
		$fInicSinHora = $dato['FinicioSINhora'];
		$fFinalSinHora = $dato['FfinalSINhora'];
		
	}
	//en mysql formato fecha es 'Y-m-d' y aqui trabajamos con 'd-m-Y'
	//convierto fecha a string para insertar en cierres, formateo fecha para insertar sql
	$formateoFechaInicio = ' STR_TO_DATE("'.$FechaInicio.'","'.$fecha_dmYHora.'") ';
	$formateoFechaFinal = ' STR_TO_DATE("'.$FechaFinal.'","'.$fecha_dmYHora.'")  '; 
	$formateoFechaCierre = ' STR_TO_DATE("'.$fechaCierre.'","'.$fecha_dmY.'") ';
	//UPDATE fechas sin hora
	
	$estadoCierre = 'Cerrado';
	$insertCierre = 'INSERT INTO '.$tabla.'( idTienda, idUsuario, FechaInicio, FechaFinal, Total, FechaCierre, FechaCreacion ) VALUES ("'
			.$idTienda.'" , "'.$idUsuario.'" ,  '.$formateoFechaInicio.' , '.$formateoFechaFinal.' , '
			.' "'.$total.'" , '.$formateoFechaCierre.' , "'.$fechaCreacion.'" )';
	
	//actualizar tickets estado = Cobrado a estado = Cerrado
	$updateEstado = 'UPDATE ticketst SET `estado`= "'.$estadoCierre.'" WHERE `estado` = "'.Cobrado.'"'
					.' AND DATE_FORMAT(`Fecha`,"%d-%m-%Y") BETWEEN "'.$fInicSinHora.'" AND "'.$fFinalSinHora.'"';
	//insertamos datos para cierre, si es correcto se Actualiza estado de tickets a 'Cerrado'
	if ($BDTpv->query($insertCierre) === true){
		$idCierre = $BDTpv->insert_id; //crea id en bbddd 
		$resultado['insertarCierre']='Correcto';
		$resultado['idCierre']=$idCierre;
		
		//tengo que actualizar el estado de esos tickets a cerrados.
		if ($BDTpv->query($updateEstado) === true){
			//actualizacion hecha
			$resultado['Nafectados_CambioEstado_tickets'] = $BDTpv->affected_rows;
			$resultado['update_estado']='Correcto';
			//insertamos en las demas tablas: cierres_ivas y cierres_usuarios_tickets
			$insertarIvas = insertarCierre_IVAS($BDTpv,$datosCierre,$idCierre);
			$insertarUsuarios = insertarUsuariosCierre($BDTpv,$datosCierre,$idCierre,$idTienda);
			
		}
		
		
	} else {
		// Quiere decir que hubo error en insertar en cierres
		$resultado['error'] = 'Error en Insert de CIERRES Numero error:'.$BDTpv->errno;
		$resultado['sql'] = $insertCierre;
	}
	
	
	
	//echo ' Funciones insertar ivas : '.$insertarIvas;
	$resultado['insertarUsuarios']=$insertarUsuarios;
	$resultado['insertarIvas'] = $insertarIvas;
	return $resultado;
}

function insertarCierre_IVAS($BDTpv,$datosCierre,$idCierre){
	//Objetivo:
	//insertar por cada iva, importe_base, importe_iva, idTienda, idCierre
	$resultado= array();
	foreach ($datosCierre as $dato){
		$idTienda = $dato['tienda'];
		$idUsuario = $dato['idUsuarioLogin'];
		$sumasIvasBases = $dato['sumasIvas'];

	}
	$i = 0;
	foreach($sumasIvasBases as $key=>$sumaBaseIva){
		$iva[$key]= $sumaBaseIva['iva'];
		$sumaBase[$key] = $sumaBaseIva['importeBase'];
		$sumaIva[$key] = $sumaBaseIva['importeIva'];
		
		//~ //inserto por cada iva, su sumaBase y su sumaIva
		$sql[$i] = 'INSERT INTO `cierres_ivas`(`idCierre`, `idTienda`, `tipo_iva`, `importe_base`, `importe_iva`) '
			.' VALUES ('.$idCierre.' , '.$idTienda.' , '.$iva[$key].' , '.$sumaBase[$key].' , '.$sumaIva[$key].')';
		if ($BDTpv->query($sql[$i]) === true){
			$resultado['idCierreIvas'] = $BDTpv->insert_id; //crea id en bbddd
			$resultado['insertar_ivas_cierre'] = 'Correcto';
		} else {
			// Quiere decir que hubo error en insertar en cierresIvas
			$resultado['error'] = 'Error en Insert de cierres IVAS Numero error:'.$BDTpv->errno;
			$resultado['sql'] = $sql[$i];
		}
		$i++;
	}
	
	
	return $resultado;
}

function insertarUsuariosCierre($BDTpv,$datosCierre,$idCierre,$idTienda){
	//necesitamos: idCierre, idUsuario, idTienda, Importe, Num_ticket_inicial, Num_ticket_final 
	//Insertamos datos por cada usuario
	$resultado= array();
	foreach ($datosCierre as $dato){
		$usuarios = $dato['usuarios'];
		$modoPago =$dato['modoPago'];
	}
	
	
	$x = 0;
	foreach ($usuarios as $idUsuario =>$usuario){ 
		$num_ticket_inicial =	$usuario['NumInicial']; 
		$num_ticket_final = $usuario['NumFinal'];
		$usuarioFpago= $modoPago[$idUsuario];
		$formasPago= $usuarioFpago['formasPago']; //array 
		
		
		$insert_cabecera_usuarios_tickets = 'INSERT INTO `cierres_usuarios_tickets` '
			.' ( `idCierre`, `idUsuario`, `idTienda`, `Importe`, `Num_ticket_inicial`, `Num_ticket_final`) '
			.' VALUES ';
	
		
		//******** Inicio insertar por usuario FormasPago ***********//
		//si es correcto insertamos formas de pago en otra tabla
		
		foreach ($formasPago as $nombre=>$importe){
			$importe= $formasPago[$nombre]['importe']; 
			
			
			//inserto en FormasPago
			//x es contador por cada usuario, nombre= nombreFpago
			$sqlFpagoCierres[$x][$nombre]='INSERT INTO `cierres_usuariosFormasPago` '
				.' ( `idCierre`, `idTienda`, `idUsuario`, `FormasPago`, `importe`) '
				.' VALUES ('.$idCierre.','.$idTienda.','.$idUsuario.',"'.$nombre.'",'.$importe.')';
				
			if ($BDTpv->query($sqlFpagoCierres[$x][$nombre]) === true){
				$resultado['idCierreFormasPago'] = $BDTpv->insert_id; //crea id en bbddd
				$resultado['insertar_FpagoCierres']='Correcto';
			} else {
				// Quiere decir que hubo error en insertar en cierresIvas
					$resultado['sql'] = $sqlFpagoCierres[$x][$nombre];
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
		$insert_datos_usuarios_tickets[$x]= '('.$idCierre.' ,  '.$idUsuario.', '.$idTienda.' , '.$sumaImporte[$idUsuario].' ,'.$num_ticket_inicial.','.$num_ticket_final.')';
		
		$sqlUsuariosCierre[$x]=$insert_cabecera_usuarios_tickets.$insert_datos_usuarios_tickets[$x];
		if ($BDTpv->query($sqlUsuariosCierre[$x]) === true){
				$idCierreUsuarios = $BDTpv->insert_id; //crea id en bbddd
				$resultado['insertarTickets_usuarios']='Correcto';
		} else {
			// Quiere decir que hubo error en insertar en cierresIvas
			$resultado['error'] = 'Error en Insert de cierres USUARIOS tickets Numero error:'.$BDTpv->errno;
			$resultado['sql'] = $sqlUsuariosCierre[$x];
		}
				
				
				
		//******** fin insertar por usuario FormasPago ***********//
		$x++;
	}
	
	
	
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
