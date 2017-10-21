<?php 
function ticketsPorFechaUsuario($fechaInicio,$BDTpv,$nuevafecha){
	//creo array de formasPago
	$formasPago = array();
	$resultado = array();
	
	//muestro datos del ticket donde fecha mayor fecha inicio y menor que nueva fecha (fecha+1)
	$sql ='SELECT * FROM `ticketst` WHERE DATE_FORMAT(`Fecha`,"%d-%m-%Y") BETWEEN "'.$fechaInicio.'" AND "'.$nuevafecha.'"';
	
	$resp = $BDTpv->query($sql);
	//SELECT count(`numticket`), `idUsuario`, `fechaInicio`, `fechaFinal` FROM `ticketstemporales` WHERE `estadoTicket`='Abierto' GROUP BY `idUsuario` 
	
	//consulta ticketsAbiertos en tablaTemporal
	//Obtenemos cuantos tickets tienen cada usuario.
	//$sqlAbiertos = 'SELECT COUNT(`numticket`) AS suma, `idUsuario` FROM `ticketstemporales` WHERE `fechaInicio` > "'.$fechaInicio.'" AND `fechaInicio` < "'.$nuevafecha.'" AND `estadoTicket`= "'.Abierto.'" GROUP BY `idUsuario` ';
	$sqlAbiertos = 'SELECT count(`numticket`) as suma, `idUsuario`, DATE_FORMAT(`fechaInicio`,"%d-%m-%Y") as fechaInicio  FROM `ticketstemporales` WHERE `estadoTicket`="'.Abierto.'" GROUP BY `idUsuario` ';
	
	//$sqlAbiertos = 'SELECT count(`numticket`) as suma, `idUsuario`, DATE_FORMAT(`fechaInicio`,"%d-%m-%Y") as fechaInicio  FROM `ticketstemporales` '
		//		.'WHERE  DATE_FORMAT(`fechaInicio`,"%d-%m-%Y") > "'.$fechaInicio.'" AND  DATE_FORMAT(`fechaFinal`,"%d-%m-%Y") > "'.$nuevafecha.'" and `estadoTicket`="'.Abierto.'" GROUP BY `idUsuario` ';
	
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
						
			$resultado['rangoTickets'][$i]= $fila['Numticket']; //necesito array numTickets para obtener sumaBasesIvas
			//~ $resultado['numTicket'] = $fila['Numticket'];
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


function baseIva($BDTpv,$numTicket){
	//se le pasa numTicket, e iva, para recoger sum(importeIva) y suma(totalbase)
	
	
	
	$sql ='SELECT SUM(`importeIva`) AS importeIva, SUM(`totalbase`) AS importeBase, iva FROM `ticketstIva` '
		.'WHERE `Numticket` IN ('.$numTicket.') GROUP BY `iva`';
	$resp = $BDTpv->query($sql);
	$resultado = array();
	if ($resp->num_rows > 0) {
		$i=0;
		while($fila = $resp->fetch_assoc()) {		
			$resultado['items'][$i]=$fila;
			//~ $resultado['sumaiva']=$fila['importeIva'];			
			//~ $resultado['base']=$fila['importeBase'];
			//~ $resultado['iva']=$fila['iva'];
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


	$clientes = array();
	$consulta = "Select * from cierres ".$filtroFinal.$rango; 
	$Resql = $BDTpv->query($consulta);
	$clientes['NItems'] = $Resql->num_rows;
	$i = 0;
//~ echo '<pre>';
//~ echo $consulta;
//~ echo '</pre>';	
	while ($cliente = $Resql->fetch_assoc()) {			
			$clientes['items'][$i]['idCierre'] = $cliente['idCierre'];
			$clientes['items'][$i]['nombreUsuario'] = $cliente['nombreUsuario'];
			$clientes['items'][$i]['fechaFinal'] = $cliente['fechaFinal'];
			$clientes['items'][$i]['total'] = $cliente['total'];
	$i = $i+1;
	}

	$clientes ['consulta'] = $consulta;
	return $clientes;
}

function fechaMaxMinTickets($BDTpv){
	$respuesta=array();
	$sql = 'SELECT UNIX_TIMESTAMP(min(`Fecha`)) as fechaMin, UNIX_TIMESTAMP(max(`Fecha`)) as fechaMax FROM `ticketst` WHERE 1 ';
	$resp = $BDTpv->query($sql);
	while($fila = $resp->fetch_assoc()) {
		$respuesta=$fila;
	}
	
	
	return $respuesta;
}




?>
