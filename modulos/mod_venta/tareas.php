<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/
$pulsado = $_POST['pulsado'];
include_once ("./../../inicial.php");
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_venta/funciones.php';
include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
include_once $URLCom.'/modulos/mod_venta/clases/pedidosVentas.php';
include_once $URLCom.'/modulos/mod_venta/clases/albaranesVentas.php';
include_once $URLCom.'/modulos/mod_venta/clases/facturasVentas.php';
include_once $URLCom.'/clases/cliente.php';

$CIncidencia=new ClaseIncidencia($BDTpv);
$Cpedido=new PedidosVentas($BDTpv);
$CalbAl=new AlbaranesVentas($BDTpv);
$Ccliente=new Cliente($BDTpv);
$CFac=new FacturasVentas($BDTpv);
switch ($pulsado) {
        case 'abririncidencia':
			$dedonde=$_POST['dedonde'];
			$usuario=$_POST['usuario'];
			$idReal=0;
			if(isset($_POST['idReal'])){
				$idReal=$_POST['idReal'];
			}
			$configuracion=$_POST['configuracion'];
			$numInicidencia=0;
			$tipo="mod_ventas";
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

        case 'abrirIncidenciasAdjuntas':
			$idReal=$_POST['id'];
			$modulo=$_POST['modulo'];
			$dedonde=$_POST['dedonde'];
			$datosIncidencia=$CIncidencia->incidenciasAdjuntas($idReal, $modulo,  $dedonde);
			if(isset($datosIncidencia['error'])){
				$respuesta['error']=$datosIncidencia['error'];
				$respuesta['consulta']=$datosIncidencia['consulta'];
			}else{
                $html=modalIncidenciasAdjuntas($datosIncidencia);
                $respuesta['html']=$html;
			}
		break;
        
        case 'anhadirPedidoTemp':
            include_once $URLCom.'/modulos/mod_venta/tareas/AddPedidoTemporal.php';
		break;
        
		case 'anhadirTemporal':
            include_once $URLCom.'/modulos/mod_venta/tareas/AddTemporal.php';
		break;
        
        case 'buscarProductos':
            include_once $URLCom.'/modulos/mod_venta/tareas/BuscarProductos.php';
		break;
		
	    case 'buscarClientes':
            include_once $URLCom.'/modulos/mod_venta/tareas/BuscarClientes.php';
		break;	
		
		case 'buscarPedido':
            include_once $URLCom.'/modulos/mod_venta/tareas/BuscarPedido.php';
		break;
		
		case 'buscarAdjunto':
            include_once $URLCom.'/modulos/mod_venta/tareas/BuscarAdjunto.php';
		break;
        
        case 'cancelarTemporal':
			$idTemporal=$_POST['idTemporal'];
			$dedonde=$_POST['dedonde'];
			$respuesta=array();
			switch($dedonde){
				case 'pedido':
					$cancelar=cancelarPedido( $idTemporal, $BDTpv);
				break;
				case 'albaran':
					$cancelar=cancelarAlbaran( $idTemporal, $BDTpv);
				break;
				case 'factura':
					$cancelar=cancelarFactura( $idTemporal, $BDTpv);
				break;
			 }
             $respuesta=$cancelar;
		break;
        
		case 'comprobarAlbaran':
            include_once $URLCom.'/modulos/mod_venta/tareas/comprobarAdjunto.php';
		break;

        case 'htmlAgregarFilaAdjunto':
            // @ Objetivo:
            // Devuelve el html de la fila del pedido y permitimos borrar fila,
            // ya que si permitimos añadir, tambien permitimos eliminar.
			$html=htmlLineaAdjunto($_POST['datos'], $_POST['dedonde'],'editar');
			$respuesta['html']=$html;
		break;
		
		case 'htmlAgregarFilasProductos':
            // @ Objetivo:
            //HTML mostrar las lineas de productos
            $productos=$_POST['productos']; // (array) Un array de varios productos, o un array de un producto..
            $dedonde=$_POST['dedonde'];
            $respuesta =array('html'=>'');
            foreach($productos as $producto){
                if (!is_array($producto)){
                    // Si no es un array, es un producto, por lo que se hace linea productos ( que es uno solo )
                    // por lo que permitimos editarlo, ya que desde tarea, solo es cuando estamos editando.
                    $res=htmlLineaProductos($productos, $dedonde,'editar');
                    $respuesta['html']=$res;
                    break;
                }else{
                    //Como es un array de productos ejecutamos foreach
                    $res=htmlLineaProductos($producto, $dedonde);
                    $respuesta['html'].=$res;
                }
                 $respuesta['productos']=$productos;
             }
		break;

        case 'modificarEstadoDocumento':
            // @ Objetivo:
            //Modificar el estado de un pedido, albaran o factura.
            $dedonde        = $_POST['dedonde'];
			$idDocumento    = $_POST['idModificar'];
			$estado         = $_POST['estado'];
			$respuesta      = array();
            if ( $dedonde = 'pedido' ){
                $modEstado=$Cpedido->ModificarEstadoPedido($idDocumento, $estado);
            }
            if ( $dedonde = 'factura' ){
                $modEstado=$CFac->modificarEstado($idDocumento, $estado);
            }
            if ( $dedonde = 'albaran' ){
                $modEstado=$CalbAl->ModificarEstadoAlbaran($idDocumento, $estado);
            }
			if(isset($modEstado['error'])){
				$respuesta['error']=$modEstado['error'];
				$respuesta['consulta']=$modEstado['consulta'];
			}
		break;
        
		case 'datosImprimir':
			//@Objetivo:
		//enviar los datos para imprimir el pdf
			$id=$_POST['id'];
			$dedonde=$_POST['dedonde'];
			$tienda=$_POST['tienda'];
			$nombreTmp=$dedonde."ventas.pdf";
			$htmlImprimir=montarHTMLimprimir($id, $BDTpv, $dedonde, $tienda);
			$cabecera=$htmlImprimir['cabecera'];
			$html=$htmlImprimir['html'];
            //~ include_once $URLCom.'/lib/tcpdf/tcpdf.php';
            $margen_top_caja_texto= 56;
            include_once $URLCom.'/clases/imprimir.php';
			include_once $URLCom.'/controllers/planImprimir.php';
			$respuesta=$rutatmp.'/'.$nombreTmp;
		break;
        
		case 'nuevaIncidencia':
			$usuario= $_POST['usuario'];
			$fecha= $_POST['fecha'];
			$datos= $_POST['datos'];
			$dedonde="mod_ventas";
			$estado= $_POST['estado'];
			$mensaje= $_POST['mensaje'];
			$usuarioSelect=0;
			if(isset($_POST['usuarioSelec'])){
			$usuarioSelect=$_POST['usuarioSelec'];
			}
			if($usuarioSelect>0){
				$datos=json_decode($datos);
				$datos->usuarioSelec=$usuarioSelect;
				$datos=json_encode($datos);
			}
			$numInicidencia=0;
			if(isset($mensaje)){
				$nuevo=$CIncidencia->addIncidencia($dedonde, $datos, $mensaje, $estado, $numInicidencia);
				$respuesta=$nuevo;
			}
		break;
        
}
echo json_encode($respuesta);
return $respuesta;
