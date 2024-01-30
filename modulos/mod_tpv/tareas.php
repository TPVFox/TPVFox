
<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/


$pulsado = $_POST['pulsado'];
//~ use Mike42\Escpos\Printer;
include_once './../../inicial.php';
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_tpv/funciones.php';
include_once $URLCom.'/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';

// Incluimos controlador.
$Controler = new ControladorComun; 
$Controler->loadDbtpv($BDTpv); // Añado la conexion a controlador.

// Creamos clases de parametros 
include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
$ClasesParametros = new ClaseParametros('parametros.xml');
$parametros = $ClasesParametros->getRoot();
// Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_tpv',$Usuario['id']);
include_once $URLCom.'/modulos/mod_tpv/clases/ClaseTickets.php';
// Creamos clase de ticket

$CTickets = new ClaseTickets();
switch ($pulsado) {
    
    case 'buscarProductos':
        include_once $URLCom.'/modulos/mod_tpv/tareas/buscarProducto.php';
    break;  
    
    case 'cobrar':
        $totalJS = $_POST['total'];
        $productos = json_decode($_POST['productos']);
        $configuracion = $_POST['configuracion'];
        // Recalcular totales.
        $totales = recalculoTotales($productos);
        $respuesta = htmlCobrar($totalJS,$configuracion);
        $respuesta['recalculo'] = $totales;
        break;
    
    case 'grabarTickes';
        // @ Objetivo :
        // Grabar tickets temporales.
        include ('tareas/grabarTicketTemporal.php');
    break;
        
    case 'HtmlLineaTicket';
        $respuesta = array();
        $product                    =$_POST['producto'];
        $num_item                   =$_POST['num_item'];
        $CONF_campoPeso     =$_POST['CONF_campoPeso'];
        $res    = htmlLineaTicket($product,$num_item,$CONF_campoPeso);
        $respuesta['html'] =$res;
        $respuesta['conf_peso'] =$CONF_campoPeso;
    break;

    case 'CerrarTicket';
        include_once $URLCom.'/modulos/mod_tpv/tareas/CerrarTicket.php';
        
    break;

    case 'ImprimirTicketCerrados';
        // Ahora debería imprimir el ticket cerrado.
        $id                 =$_POST['idTicketst'];
        $ticket = $CTickets->obtenerUnTicket($id);
        
        $datosImpresion = $CTickets->prepararParaImprimirTicket($ticket);
        $ruta_impresora = $configuracion['impresora_ticket'];
        if (ComprobarImpresoraTickets($ruta_impresora) === true){;
            include 'tareas/impresoraTicket.php';
        } else {
            $respuesta['error_impresora'] = ' no existe la impresora asignada, hay un error';
        }
        // Pendiente de realizar.
        $respuesta['idTicketST'] = $id;
        $respuesta['datosImpresion'] = $datosImpresion;
    break;

    case 'ObtenerRefTiendaWeb';
        include ('tareas/PrepararEnviarStockWeb.php');
    break;
    
    case 'RegistrarRestaStock':
        $respuesta = array();
        $id_ticketst    = $_POST['id_ticketst'];
        $estado         = $_POST['estado'];
        $datos = $_POST['datos'];
        $respuesta = RegistrarRestaStock($BDTpv,$id_ticketst, $estado,$datos);
    break;

    case 'buscarClientes':
        // Abrimos modal de clientes
        $busqueda = $_POST['busqueda'];
        $dedonde = $_POST['dedonde'];
        $tabla='clientes';
        //funcion de buscar clientes
        //luego html mostrar modal 
        if ($busqueda != ''){
            $res = BusquedaClientes($busqueda,$BDTpv,$tabla,$dedonde);
        } 
        if (!isset($res['datos'])){
            $res = array( 'datos' => array());
        }
        $respuesta['Nitems'] = count($res['datos']);
            
        if ($respuesta['Nitems']==1 and $res['datos']['0']['estado']=='Activo') {
            $respuesta['id'] =$res['datos']['0']['idClientes'];
            $respuesta['nombre'] =$res['datos']['0']['nombre'].'-'.$res['datos']['0']['razonsocial'];
        } else {
            $respuesta['html'] = htmlClientes($busqueda,$dedonde,$res['datos']);
        }
        
        $respuesta['datos'] = $res;
        $respuesta['donde'] = $dedonde;
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
    break;
    
    case 'abririncidencia':
        include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
        $CIncidencia=new ClaseIncidencia($BDTpv);
        $dedonde=$_POST['dedonde'];
        $configuracion=$_POST['configuracion'];
        $idReal=0;
        if(isset($_POST['idReal'])){
            $idReal=$_POST['idReal'];
        }
        $tipo="mod_tpv";
        $numInicidencia=0;
        $datos=array(
        'vista'=>$dedonde,
        'idReal'=>$idReal
        );
        $datos=json_encode($datos);
        
        $estado="No resuelto";
        $html=$CIncidencia->htmlModalIncidencia($datos, $dedonde, $configuracion, $estado, $numIncidencia);
        $respuesta['html']=$html;
        $respuesta['datos']=$datos;
    break;
    
    case 'nuevaIncidencia':
        include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
        $CIncidencia=new ClaseIncidencia($BDTpv);
        $fecha= $_POST['fecha'];
        $datos= $_POST['datos'];
        $estado= $_POST['estado'];
        $mensaje= $_POST['mensaje'];
        $numInicidencia=0;
        $usuarioSelect=0;
        $dedonde="mod_tpv";
        if(isset($_POST['usuarioSelec'])){
        $usuarioSelect=$_POST['usuarioSelec'];
        }
        if($usuarioSelect>0){
            $datos=json_decode($datos);
            $datos->usuarioSelec=$usuarioSelect;
            $datos=json_encode($datos);
        }
        if($mensaje){
            $nuevo=$CIncidencia->addIncidencia($dedonde, $datos, $mensaje, $estado, $numInicidencia);
            $respuesta=$nuevo;
        }
    break;
    case 'cambiarClienteTicketGuardado':
        $modCliente=$CTickets->modificarClienteTicket($_POST['id_ticketst'], $_POST['idCliente']);
        if(isset($modCliente['error'])){
            $mensaje="Error al modificar el cliente";
        }else{
            $mensaje="Cliente Modificado con Éxito";
        }
        $respuesta['mensaje']=$mensaje;
    break;

    case 'cambiarFormaPagoTicketGuardado' :
        $cambiarFormaPago = $CTickets->cambiarFormaPagoTicket($_POST['id_ticketst'], $_POST['formaPago']);
        if(isset($cambiarFormaPago['error'])){
            $mensaje="Error al modificar la forma pago";
        }else{
            $mensaje="Forma pago modificada con éxito";
        }
        $respuesta['mensaje']=$mensaje;
    break;

    case 'htmlFechaNueva':
        $htmlFechaNueva = htmlFechaNueva($_POST['Tickets']);
        $respuesta =$htmlFechaNueva;
    break;

    case 'cambiarFechaTicketsSeleccionados':
        $cambiarFechaTickets = $CTickets->cambiarFechaTickets($_POST['Tickets'], $_POST['FechaNueva']);
        $c=0; // Contador de correctos
        foreach ($cambiarFechaTickets as $res){
            if ($res === true){
                $c++;
            }
        }
        if (count($_POST['Tickets']) !== $c){
            $respuesta['error'] == 'Se envio '.count($_POST['Tickets']). 'ticket(s) , pero solo hubo '.$c.' cambios correctos.';
        } else {
            $respuesta['mensaje'] = 'Ok, todo';
        }
    break;

    case 'listadoFamilia':
        $productos = array();
        $familias = array();
        $idFamilia = $_POST['idFamilia'];
        $CTArticulos = new ClaseProductos($BDTpv);
        $CFamilias = new ClaseFamilias($BDTpv);
        $fam = $CFamilias->buscarFamilisMostrarTpv($idFamilia);
        $pro = $CFamilias->buscarProductosFamilias($idFamilia,20);
        if (isset($pro['datos'])){
            foreach ($pro['datos'] as $p){
                $productos[] = $CTArticulos->GetProducto($p['idArticulo']);
            }
        }
        $respuesta['sql'] =json_encode($pro);
        if (isset($fam['datos'])){
            $familias = $fam['datos'];
        }
        $htmlModal = htmlModalListadoPorFamilias($familias,$productos,$configuracion['input_pordefecto']);
        $respuesta['familias']= $familias;
        $respuesta['productos'] = $productos;
        $respuesta['html'] =$htmlModal;
    break;

}
echo json_encode($respuesta);
/* ===============  CERRAMOS CONEXIONES  ===============*/
mysqli_close($BDTpv);

 
 
?>
