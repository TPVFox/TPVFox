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
	
	public $numTicket; // (int) Numero ticket que estamos ahora.
	public $ticketsAbiertos ; // (array) con los datos del tickets abiertos.
	public $datos_get ; // (array) con los datos de get que tengamos.
	public $datos_post; // (array) con los datos de post que tengamos.	

	
	public function __construct()
	{
		parent::__construct();
		
	}
	
	public function ObtenerTicketsAbiertos($numTicket=0){
		// @ Objetivo es obtener las cabeceras de los ticketAbiertos.
		// @ Parametro
		// 	$numticket -> Si recibimos uno, ese no lo devolvemos, para evitar mostrarlo, ya que no tiene sentido mostralo
		// 				si lo estamos editando.
		$BDTpv= $this->BDTpv;
		$session = parent::GetSession();
		$idTienda = $session['tiendaTpv']['idTienda'];
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
		
		
		

	
	
	
	
	// Fin de clase.
}



?>
