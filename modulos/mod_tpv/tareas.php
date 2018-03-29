
<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/


$pulsado = $_POST['pulsado'];
use Mike42\Escpos\Printer;

include_once ("./../../configuracion.php");
include_once ("../mod_incidencias/popup_incidencias.php");

// Crealizamos conexion a la BD Datos
include_once ("./../mod_conexion/conexionBaseDatos.php");

// Incluimos funciones
include_once ("./funciones.php");

// Incluimos controlador.
include ("./../../controllers/Controladores.php");
$Controler = new ControladorComun; 
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);


switch ($pulsado) {
    
    case 'buscarProductos':
		$busqueda = $_POST['valorCampo'];
		$campoAbuscar = $_POST['campo'];
		$id_input = $_POST['cajaInput'];
		$deDonde = $_POST['dedonde']; // Obtenemos de donde viene
		
		if ($id_input === "Codbarras") {
			// Si la busqueda es por codbarras y comprobamos el codbarras es propio, 
			// es decir que empiece por 21 o 20
			// YA que entonces tendremos que buscar por referencia.
			include ("./../../controllers/codbarras.php");
			$Ccodbarras = new ClaseCodbarras ; 
			$codigo_correcto = $Ccodbarras->ComprobarCodbarras($busqueda);
			error_log('Es correcot codigoBarras'.strlen($busqueda));
			if ($codigo_correcto === 'OK'){
				// Se comprobo código barras y es correcto.
				$codBarrasPropio= $Ccodbarras->DesgloseCodbarra($busqueda);
				if (count($codBarrasPropio)>0){
					// Obtenemos el campo a buscar de parametros de referencia, porque lo necesitamos
					// Cargamos los fichero parametros.
					include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
					$ClasesParametros = new ClaseParametros('parametros.xml');
					//~ $parametros = $ClasesParametros->getRoot();
					$xml_campo_cref = $ClasesParametros->Xpath('cajas_input//caja_input[nombre="cajaReferencia"]//parametros//parametro[@nombre="campo"]');
					$campoAbuscar =(string)$xml_campo_cref[0];
					$id_input='Referencia';
					$codBarrasPropio['codbarras_leido'] = $busqueda; // Guardamos en array el codbarras leido
					$busqueda= $codBarrasPropio['referencia'];
				}
			}
		}

		$respuesta = BuscarProductos($id_input,$campoAbuscar,$busqueda,$BDTpv);
		if ($respuesta['Estado'] !='Correcto' ){
			// Al ser incorrecta entramos aquí.
			// Mostramos popUp tanto si encontro varios como si no encontro ninguno.

			if (!isset($respuesta['datos'])){
				// Para evitar error envio, lo generamos vacio..
				$respuesta['datos']= array();
			}
			$respuesta['listado']= htmlProductos($respuesta['datos'],$id_input,$campoAbuscar,$busqueda);
		}
		if ($respuesta['Estado'] === 'Correcto' && $deDonde === 'popup'){
			// Cambio estado para devolver que es listado.
			$respuesta['listado']= htmlProductos($respuesta['datos'],$id_input,$campoAbuscar,$busqueda);
			$respuesta['Estado'] = 'Listado';
		}
		
		if ( isset($codBarrasPropio)){
			if (count($codBarrasPropio)>0){
				// Si hay datos , nos enviamos referencia y (precio o peso) obtenidos.
				$respuesta['codBarrasPropio'] = $codBarrasPropio;
				if (count($respuesta['datos']=== 1)){
					// Solo permito cambiar datos si hay un solo resultado.
					$respuesta['datos'][0]['codBarras'] = $codBarrasPropio['codbarras_leido'];
					$respuesta['datos'][0]['crefTienda'] = $codBarrasPropio['referencia'];
					if (isset($codBarrasPropio['peso'])){
						// [OJO] aquí cambiaría si tuvieramos activado campo de cantidad/peso, ya que es donde lo podríamos.
						$respuesta['datos'][0]['unidad'] = $codBarrasPropio['peso'];
					}
					if (isset($codBarrasPropio['precio'])){
						$respuesta['datos'][0]['pvpCiva'] = $codBarrasPropio['precio'];
					}
				// Ahora cambiamos $respuesta['datos'] , el peso o precio para referencia
				
				}
			}
		}
		
		echo json_encode($respuesta);  
		break;
	
	case 'cobrar':
		//~ echo 'cobrar';
		$totalJS = $_POST['total'];
		$productos = $_POST['productos'];
		$configuracion = $_POST['configuracion'];
		// Convertimos productos que debería ser un objeto a array
		$productos = json_decode( json_encode( $_POST['productos'] ));

		// Recalcular totales.
		$totales = recalculoTotales($productos);
		
		
		$respuesta = htmlCobrar($totalJS,$configuracion);
		$respuesta['recalculo'] = $totales;
		echo json_encode($respuesta);		
		
		break;
	
	case 'grabarTickes';
		// @ Objetivo :
		// Grabar tickets temporales.
		$respuesta = array();
		$cabecera = array(); // Array que rellenamos de con POST
		$productos 					=$_POST['productos'];
		$cabecera['idTienda']		=$_POST['idTienda'];
		$cabecera['idCliente']		=$_POST['idCliente'];
		$cabecera['idUsuario'] 		=$_POST['idUsuario'];
		$cabecera['estadoTicket'] 	=$_POST['estadoTicket'];
		$cabecera['numTicket'] 		=$_POST['numTicket'];
		
		// Ahora recalculamos nuevamente
		$productos_para_recalculo = json_decode( json_encode( $_POST['productos'] ));
		$CalculoTotales = recalculoTotales($productos_para_recalculo);
		
		$nuevoArray = array(
						'desglose'=> $CalculoTotales['desglose'],
						'total' => $CalculoTotales['total']
							);
		
		//~ $CalculoTotales = gettype($productos);

		$res 	= grabarTicketsTemporales($BDTpv,$productos,$cabecera,$CalculoTotales['total']);
		$respuesta=$res;
		
		$respuesta = array_merge($respuesta,$nuevoArray);
		echo json_encode($respuesta);
		break;
		
	case 'HtmlLineaTicket';
		$respuesta = array();
		$product 					=$_POST['producto'];
		$num_item					=$_POST['num_item'];
		$CONF_campoPeso		=$_POST['CONF_campoPeso'];
		$res 	= htmlLineaTicket($product,$num_item,$CONF_campoPeso);
		$respuesta['html'] =$res;
		$respuesta['conf_peso'] =$CONF_campoPeso;
		echo json_encode($respuesta);
		break;
	case 'CerrarTicket';
		$URLCom = $RutaServidor . $HostNombre;
		$respuesta = array();
		$cabecera = array(); // Array que rellenamos de con POST
		$cabecera['total']				=$_POST['total'];
		$cabecera['entregado']			=$_POST['entregado'];
		$cabecera['formaPago']			=$_POST['formaPago'];
		$cabecera['idTienda']			=$_POST['idTienda'];
		$cabecera['idCliente']			=$_POST['idCliente'];
		$cabecera['idUsuario'] 			=$_POST['idUsuario'];
		$cabecera['estadoTicket'] 		=$_POST['estadoTicket'];
		$cabecera['numTickTemporal'] 	=$_POST['numTickTemporal'];
		$checkimprimir 					=$_POST['checkimprimir'];
		$ruta_impresora					=$_POST['ruta_impresora'];
		// Obtenemos ticket
		$ticket 	= ObtenerUnTicketTemporal($BDTpv,$cabecera['idTienda'],$cabecera['idUsuario'] ,$cabecera['numTickTemporal']);
		// Comprobamos que el resultado es correcto y recalculamos totales
		if (isset($ticket['error'])) { 
			$respuesta['error-ticket']['mensaje'] ='Error en al Obtener ticket';
			$respuesta['error-ticket']['datos'] = $ticket['error'];
			echo json_encode($respuesta); // Convierto a JSON.
			return $respuesta; // No continuamos,.
		}
		// Obtenermos los productos como array que con un JSOn por cada producto y este JSON contiene los campos de cada producto
		if (isset($ticket['productos']) && ($ticket['estadoTicket']!='Cobrado')){
			
			$productos = json_decode( json_encode( $ticket['productos'] ));
			$Datostotales = recalculoTotales($ticket['productos']);	
			if (number_format($Datostotales['total'],2) != number_format($cabecera['total'],2)){
				$respuesta['error-ticket']['mensaje']  = ' No coincidente TOTAL:'.$cabecera['total'].' con el Total recalculado';
				$respuesta['error-ticket']['datos'] = $Datostotales;
			}
			// grabamos ticket.
			$grabar = grabarTicketCobrado($BDTpv,$productos,$cabecera,$Datostotales['desglose']);
		//si numTickets = -1 , no existe indice usuario. = -2 es no se puede grabar en indices
			//echo $grabar['Numtickets'];

		
		}
		if (!isset($respuesta['error']) ){
			//si esta marcado el check de imprimir al cobrar
			if ($checkimprimir === 'true'){
				// Obtenemos y organizamos datos antes imprimir
				$cabecera['fecha'] = $grabar['fecha'] ; // Fecha con la que grabamos el ticket.
				
				$cabecera['NumTicket'] = $grabar['Numtickets']; // El numero con el grabamos el ticket.
				$cabecera['Serie'] = $cabecera['idTienda'].'-'.$cabecera['idUsuario'];
				$DatosTienda = DatosTiendaID($BDTpv,$cabecera['idTienda']);
				$datosImpresion = ImprimirTicket($productos,$cabecera,$Datostotales['desglose'],$DatosTienda);
				// Incluimos fichero para imprimir ticket, con los datosImpresion.
				// Comprobamos si existe impresora.
				if (shell_exec('ls '.$ruta_impresora)){;
					include 'impresoraTicket.php';
				} else {
					$respuesta['error_impresora'] = ' no existe la impresora asignada';
				}
				
			}
		} else {
			// Si llega aquí es que $resultado['error'] existe por lo que pudo haber un error en:
			// $grabar = grabarTicketCobrado($BDTpv,$productos,$cabecera,$Datostotales['desglose']); 
			// ya que el oro 
			error_log ("Error en tareas, en if !isset($respuesta[error]");
			exit();
		}
		$respuesta['grabar'] =$grabar;
		//~ $respuesta['ticket'] = $datosImpresion;
		echo json_encode($respuesta);
		break;
		
	case 'guardarCierreCaja':
			echo 'tareas guardar cierre';
		break;
		
		
	case 'ObtenerRefTiendaWeb';
		$respuesta = array();
		$productos =$_POST['productos'];
		$idweb	 = $_POST['web'];
		//Ahora obtenemos datos tienda web.
		$tienda = BuscarTienda($BDTpv,$idweb);
		$respuesta = ObtenerRefWebProductos($BDTpv,$productos,$idweb);
		$respuesta['tienda'] = $tienda;
		echo json_encode($respuesta);
		break;
		
	case 'RegistrarRestaStock':
		$respuesta = array();
		$id_ticketst =$_POST['id_ticketst'];
		$respuesta_servidor = $_POST['respuesta_servidor'];
		$respuesta = RegistrarRestaStock($BDTpv,$id_ticketst,$respuesta_servidor);
		
	
		echo json_encode($respuesta);
		break;
	/* **************************************************************	*
     * 			LLAMADAS FUNCIONES COMUNES MODULO CIERRES Y TPV			*
     * **************************************************************	* 	*/
	case 'buscarClientes':
		// Abrimos modal de clientes
		$busqueda = $_POST['busqueda'];
		$dedonde = $_POST['dedonde'];
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
		
	case 'Grabar_configuracion':
		// Grabamos configuracion nueva configuracion
		$configuracion = $_POST['configuracion'];
		// Ahora obtenemos nombre_modulo y usuario , lo ponermos en variable y quitamos array configuracion.
		$nombre_modulo = $configuracion['nombre_modulo'];
		$idUsuario = $configuracion['idUsuario'];
		unset($configuracion['nombre_modulo'],$configuracion['idUsuario']);
		
		$respuesta = $Controler->GrabarConfiguracionModulo($nombre_modulo,$idUsuario,$configuracion);		
		$respuesta['configuracion'] = $configuracion ; 
		
		echo json_encode($respuesta);
		break;
		
		case 'abririncidencia':
		$dedonde=$_POST['dedonde'];
		$usuario=$_POST['usuario'];
		$idReal=$_POST['idReal'];
		$tipo="mod_compras";
		$fecha=date('Y-m-d');
		$datos=array(
		'dedonde'=>$dedonde,
		'idReal'=>$idReal
		);
		$datos=json_encode($datos);
		
		$estado="No resuelto";
		$html=modalIncidencia($usuario, $datos, $fecha, $tipo, $estado);
		$respuesta['html']=$html;
		$respuesta['datos']=$datos;
		echo json_encode($respuesta);
		break;
		
		
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDTpv);

 
 
?>
