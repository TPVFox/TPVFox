<?php
/* Objetivo de esta clase
 *   - Crear un objeto que contenga 
 * 				tickets abiertos
 * 				datos_ticket en cuestion.
 * 				
 *   - Tener los parametros cargados, para interactuar con los datos.
 *
 * [Informacion sobre los estados posibles.]
 * Campo estado de las tablas de tickets :
 * 
 * [OTRAS NOTAS]
 * 
 * 
 * */
$rutaCompleta = $RutaServidor.$HostNombre;
include_once($rutaCompleta.'/clases/ClaseSession.php');

class ClaseTickets extends ClaseSession {
	
	public $Tienda ; 				// (array) Array con los datos de TiendaPrincipal
	public $numTicket; 				// (int) Numero ticket que estamos ahora.
	public $estado; 				// (string) El estado del ticket
	public $ticketsAbiertos ; 		// (array) con los datos del tickets abiertos.
	public $datos_get ; 			// (array) con los datos de get que tengamos.
	public $datos_post; 			// (array) con los datos de post que tengamos.	

	
	public function __construct()
	{
		parent::__construct();
		$this->Tienda = $_SESSION['tiendaTpv'];
	}
	
	public function GetTienda(){
		
		return $this->Tienda;
	}
	public function Consulta($sql){
		// @ Objetivo:
		// Realizar una consulta y devolver numero respuesta... o error..
		// [NOTA]
		// Solo valido para SELECT
		// No debería se funcion publica.
		// Habría que hacer algo como :
		// http://php.net/manual/es/mysqli-stmt.bind-param.php
		$respuesta = array();
		$db = $this->BDTpv;
		$smt = $db->query($sql);
		if ($db->query($sql)) {
			$respuesta['NItems'] = $smt->num_rows;
			// Hubo resultados
			while ($fila = $smt->fetch_assoc()){
				$respuesta['Items'][] = $fila;
			}
		} else {
			// Quiere decir que hubo error en la consulta.
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
		}
		
		return $respuesta;
	}
    public function consultaInsert($sql) {
        // Realizamos la consulta.
        // Esta consulta no tiene sentido teniendo la del padre...

        $db = $this->BDTpv;
        $smt = $db->query($sql);
        if ($smt) {
            return $smt;
        } else {
            $respuesta = array();
            $respuesta['consulta'] = $sql;
            $respuesta['error'] = $db->error;
            return $respuesta;
        }
    }
	
	public function ObtenerTicketsAbiertos($numTicket=0){
		// @ Objetivo es obtener las cabeceras de los ticketAbiertos.
		// @ Parametro
		// 	$numticket -> Si recibimos uno, ese no lo devolvemos, para evitar mostrarlo, ya que no tiene sentido mostralo
		// 				si lo estamos editando.
		$BDTpv= $this->BDTpv;
		$idTienda = $this->Tienda['idTienda'];
		$respuesta = array();
		// Montamos consulta
		$sql = 'SELECT t.idUsuario, u.nombre as usuario, t.`numticket`,t.`idClientes`,t.`fechaInicio`,t.`fechaFinal`,t.`total`,t.`total_ivas`,c.Nombre, c.razonsocial FROM `ticketstemporales` as t LEFT JOIN clientes as c ON t.idClientes=c.idClientes LEFT JOIN usuarios as u ON t.idUsuario= u.id WHERE t.idTienda ='.$idTienda.' AND estadoTicket="Abierto"';
		if ($res = $BDTpv->query($sql)) {
			/* obtener un array asociativo */
				$i= 0;
				while ( $fila = $res->fetch_assoc()){
					if ($numTicket != $fila['numticket']){
					// Añadimos fila a items si el numero ticket no es igual al que recibimos...
					// Si es mismo no lo añadimos, ya que estamos modificando o viendolo;
						$respuesta['items'][$i]= $fila;
					$i++;
					}
				}
			/* liberar el conjunto de resultados */
			$res->free();
		} elseif (mysqli_error($BDTpv)){
			
			$respuesta['error'] = $BDTpv->error_list;
		} 
		
		$respuesta['consulta'] = $sql;
		return $respuesta;
	
	}
		
		
	public function obtenerUnTicket($idTicketst){
		// @ Objetivo obtener un ticket.
		// @ Parametro
		// 		$idTicketst -> id del ticketst , que no es el numero ticket.... :-)
		// 		$idTienda -> id de la tienda que estas.
		$resultado 	= array();
		$idTienda 	= $this->Tienda['idTienda'];
		// Realizamos la consulta de ticket dos consultas.
		
		// Primera consulta para obtener cabecera y totales.
		$consulta = ' SELECT t.*, c.`idClientes`, u.`username`, c.`razonsocial`, c.`Nombre` ' 
					.'FROM ticketst AS t '
					.'LEFT JOIN `clientes` AS c '
					.'ON c.`idClientes` = t.`idCliente` '
					.'LEFT JOIN `usuarios` AS u '
					.'ON u.`id` = t.`idUsuario` '
					.'WHERE `idTienda` ='.$idTienda.' AND t.`id` = '.$idTicketst;

		$query = $this->Consulta($consulta);
		if (!isset($query['error'])){
			if ($query['NItems'] === 1){
				// Obtuvimos un registro de un ticket , por lo que montamos cabecera de ese ticket.
				$registro = $query['Items'][0];
				$cabecera = $registro;
				// Eliminamos los elemento array innecesario.
				
				//~ $resultado['totales'] = array (
											//~ 'formaPago' => $cabecera['formaPago'],
											//~ 'entregado' => $cabecera['entregado'],
											//~ 'total'		=> $cabecera['total']
										//~ );
				//~ unset($cabecera['formaPago'],$cabecera['entregado'],$cabecera['total']);

				$resultado['cabecera'] = $cabecera;
						
			} else {
				// Quiere decir que se encontro mas o ningún ticket.
				$resultado['error'][] = array( 'tipo'		=> 'danger',
											 'mensaje'	=> 'Error '.$query['NItems'].' registros encontrado para el idticketst '.$idTicketst,
											 'dato'		=> $query
											);
			}
			
		} else {
			$resultado['error'][]		=  array( 'tipo'		=> 'danger',
											 'mensaje'	=> 'Error en la consulta:'.$consulta,
											 'dato'		=> $query
											);
		}
		// Solo ejecutamos si no hubo errores.
		if (!isset($resultado['error'])){
			//  Obtenemos las bases y ivas del ticket
			$datosIvas = $this->obtenerBaseIvaTicket($idTicketst);
			if (isset($datosIvas['items'])){
				$resultado['basesYivas'] = $datosIvas['items'];
			} else {
				// Hubo un error
				$resultado['error'][] = $datosIvas;
			}
			// Obtenemos las lineas del ticket
			$lineas =  $this->obtenerLineasTicket($idTicketst);
			if (isset($lineas['items'])){
				$resultado['lineas'] = $lineas['items'];
			} else {
				// Hubo un error
				$resultado['error'][] = $datosIvas;
			}
		
		}
		
		return $resultado ;	
	}
	
	
	
	public function obtenerBaseIvaTicket($idticketst){
		//@ Objetivo:
		//Obtener los registros del iva y bases de ese ticket
		$resultado = array();
		$sql ='SELECT SUM(`importeIva`) AS importeIva, SUM(`totalbase`) AS importeBase, iva '
			.' FROM `ticketstIva` '
			.' WHERE `idticketst` IN ('.$idticketst.') GROUP BY `iva`';
		$resp = $this->Consulta($sql);
	
		if ($resp['NItems'] > 0) {
			$resultado['items'] = $resp['Items'];
			$resultado['sql'] = $sql;

		} else {
			$resultado['error'] = array( 'tipo'		=> 'warning',
										'mensaje'	=> 'Error a la hora obtner bases y ivas, registros: '.$query['NItems'],
										 'dato'		=> $query
										);
		}
		
	return $resultado;
	}
	
	public function obtenerLineasTicket($idTicketst){
		// Objetivo:
		//	Obtener las lineas de ese ticket
		$resultado = array();
		$consulta = 'SELECT * FROM `ticketslinea` WHERE idticketst='.$idTicketst;
		$query = $this->Consulta($consulta);
		if (!isset($query['error'])){
			$resultado['items'] = $query['Items'];
		} else {
			$resultado['error'] = array( 'tipo'		=> 'warning',
										'mensaje'	=> 'Error a la hora obtener lineas del ticket, registros: '.$query['NItems'],
										 'dato'		=> $query
										);
		}
		
		return $resultado;
		
	}
	
	public function prepararParaImprimirTicket($ticket){
		// @ Objetivo es montar un array con las distintas partes del ticket para luego mandar imprimir.
		// Recuerda que € no imprime directamente hay que utilizar la code Page 1252, por ello en 
		// body NO podemos €
		$respuesta = array();
		$tienda = $this->Tienda;
		$cabecera = $ticket['cabecera'];
		$cabecera['Serie'] = $cabecera['idTienda'].'-'.$cabecera['idUsuario'];
		$cabecera['cambio'] = $cabecera['entregado']- $cabecera['total'];
		$desglose = $ticket['basesYivas'];
		$productos = $ticket['lineas'];
		// Preparamos la <<< cabecera1 del ticket  LETRA GRANDE  >>> 
		$respuesta['cabecera1'] = $tienda['NombreComercial']."\n"; // Este dato realmente lo deberíamos cojer de tabla tiendas.
		$respuesta['cabecera1-datos'] = $tienda['direccion'];
		// Preparamos la <<< cabecera2 del ticket  GRANDE  >>> 
		$respuesta['cabecera2'] = "\nTeléfono:".$tienda['telefono']."\n";
		$respuesta['cabecera2'] .= str_repeat("=",24)."\n";
		$respuesta['cabecera2'] .="FACTURA  SIMPLIFICADA\n";
		$respuesta['cabecera2'] .= str_repeat("=",24)."\n";
		$respuesta['cabecera2-datos'] = 'Fecha:'.MaquetarFecha ($cabecera['Fecha'])
									.' Hora: '.MaquetarFecha ($cabecera['Fecha'],'HM')."\n"
									.' Serie:'.$cabecera['Serie'].' Numero:'.$cabecera['Numticket']. "\n"
									.str_repeat("-",42)."\n";
		// Preparamos el <<<  body   >>>  del ticket
		$lineas = array();
		$i = 0;
		foreach ($productos as $product) {
			// Solo montamos lineas para imprimir aquellos que estado es 'Activo';
			if ( $product['estadoLinea'] === 'Activo'){
				// No mostramos referencia, mostramos id producto
				$lineas[$i]['1'] = ' (id:'.$product['id'].') '.substr($product['cdetalle'], 0, 36);//.substr($product->cref,0,10);
				$importe = $product['ncant'] * $product['precioCiva'];
				// Creamos un array con valores numericos para poder formatear correctamente los datos
				$Numeros = array(
								0 => array(
									'float'		 => $product['ncant'],
									'decimales' => 3
									),
								1 => array(
									'float' 	=> $product['precioCiva'],
									'decimales' => 2
									),
								2 => array(
									'float'		=> $importe,
									'decimales' => 2
									)
							);
				foreach ( $Numeros as  $indice => $strNumero){
					$stringvalor = strval(number_format($strNumero['float'],$strNumero['decimales']));
					$Numeros[$indice]['string'] =( strlen($stringvalor)<10 ? str_repeat(" ", 10-strlen($stringvalor)).$stringvalor : $stringvalor );
				} 
				
				$lineas[$i]['2'] = $Numeros[0]['string'].' X '.$Numeros[1]['string'].' = '.$Numeros[2]['string'].' ('.sprintf("%' 2d", $product['iva']).')';
				$i++;
				}
			}
		$body = '';
		foreach ($lineas as $linea){
			$body .=$linea['1']."\n";
			$body .=$linea['2']."\n";
		}
		$respuesta['body'] = $body;
		// Fin del <<<  body   >>>  del ticket
		
		// Preparamos el <<<  pie   >>>  del ticket
		$respuesta['pie-datos'] =str_repeat("-",42)."\n";
		foreach ($desglose as $index=>$valor){
			$respuesta['pie-datos'] .= $valor['importeBase'].'  -> '.$valor['iva']. '%'.'  -> '.$valor['importeIva']."\n";
		}
		$respuesta['pie-datos'] .=str_repeat("-",42)."\n";
		$respuesta['pie-total'] =number_format($cabecera['total'],2);
		$respuesta['pie-formaPago'] =$cabecera['formaPago'];
		$respuesta['pie-entregado'] =number_format($cabecera['entregado'],2);
		$respuesta['pie-cambio'] =number_format($cabecera['cambio'],2);

		$respuesta['pie-datos2'] ="\n".$tienda['razonsocial']." - CIF: ".$tienda['nif']."\n";



		return $respuesta;	
			
			
		}
		
	function MaquetarFecha ($fecha,$tipo='dmy'){
		// @ Objetivo formatear una una fecha y obtener al tipo indicado
		// @ Parametros
		// 	$fecha : Dato de fecha
		//	$tipo : Pueder ser 
		//				HM -> Hora Minuto
		//				dmy -> Dia Mes Año
		// Creamos array de fecha
		$fechaArray = date_parse($fecha);
		$horaMinuto = sprintf("%'.02d", $fechaArray['hour']).':'.sprintf("%'.02d", $fechaArray['minute']);
		$DiaMesAnho = sprintf("%'.02d", $fechaArray['day']).'/'.sprintf("%'.02d", $fechaArray['month']).'/'.$fechaArray['year'];
		if ($tipo === 'HM'){
			$respuesta = $horaMinuto;
		} else{
			$respuesta = $DiaMesAnho;
		}
		return $respuesta;
	}
    
    
    public function modificarClienteTicket($idTicket, $idCliente){
       
		$consulta = 'UPDATE `ticketst` SET idCliente='.$idCliente.' where id='.$idTicket;
        $resp = $this->consultaInsert($consulta);
        return $resp;
    }
	// Fin de clase.
}



?>
