<?php 

include_once './../../inicial.php';
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_compras/tareas.php';
include_once $URLCom.'/modulos/mod_compras/funciones.php';
include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
include_once $URLCom.'/modulos/mod_compras/clases/facturasCompras.php';
include_once $URLCom.'/clases/Proveedores.php';
include_once $URLCom.'/clases/articulos.php';
include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
include_once $URLCom.'/controllers/parametros.php';
include_once $URLCom.'/controllers/Controladores.php';

$pulsado = $_POST['pulsado'];


$CIncidencia=new ClaseIncidencia($BDTpv);
$CProveedores=new Proveedores($BDTpv);
$CPed=new PedidosCompras($BDTpv);
$CAlb=new AlbaranesCompras($BDTpv);
$CFac= new FacturasCompras($BDTpv);
$CArticulos=new Articulos($BDTpv);
$respuesta=array();
switch ($pulsado) {
    case 'abririncidencia':
        //@OBJETIVO:
        //Mostrar el modal de incidencias con los datos de compras, según en el archivo en el que está 
        //situado envía los datos de este. El idReal es el id del albarán, pedido o factura guardado si no lo 
        //envía en 0
        //@Retornar: devuelve el html para insertar en el js del modal
        $dedonde=$_POST['dedonde'];
        $idReal=0;
        if(isset($_POST['idReal'])){
            $idReal=$_POST['idReal'];
        }
        $configuracion=$_POST['configuracion'];
        $numIncidencia=0;
        $tipo="mod_compras";
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

    case 'addPedidoTemporal';
        include_once  $URLCom.'/modulos/mod_compras/tareas/addPedidoTemporal.php';
    break;

    case 'addAlbaranTemporal':
        include_once  $URLCom.'/modulos/mod_compras/tareas/addAlbaranTemporal.php';
    break;
    
    case 'addFacturaTemporal':
        include_once  $URLCom.'/modulos/mod_compras/tareas/addFacturaTemporal.php';
    break;
    
    case 'addProveedorArticulo':
        include_once  $URLCom.'/modulos/mod_compras/tareas/addProveedorArticulo.php';
    break;
    
    case 'buscarAdjunto':
        include_once  $URLCom.'/modulos/mod_compras/tareas/buscarAdjunto.php';
    break;
    
    case 'buscarProveedor':
		include_once  $URLCom.'/modulos/mod_compras/tareas/buscarProveedor.php';
	break;
    
	case 'buscarProductos':
		include_once  $URLCom.'/modulos/mod_compras/tareas/buscarProducto.php';
	break;

    case 'cancelarTemporal':
        // @Objetivo: cancelar el archivo temporal , cuando cancelamos un temporal muestra de primeros una alert
        //donde aceptamos en caso de querrer eliminar, a  continuación dependiendo de del archivo donde estemos
        //situados ejecuta su función 
        // @Retorno: en principio devuelve un array vacio a no ser que se tenga un error en la función ejecutada
        $idTemporal=$_POST['idTemporal'];
        $dedonde=$_POST['dedonde'];
        $respuesta=array();
        switch($dedonde){
            case 'pedido':
                $cancelar=cancelarPedido( $idTemporal, $BDTpv);
                $respuesta=$cancelar;
            break;
            case 'albaran':
                $cancelar=cancelarAlbaran( $idTemporal, $BDTpv);
                $respuesta=$cancelar;
            break;
            case 'factura':
                $cancelar=cancelarFactura( $idTemporal, $BDTpv);
                $respuesta=$cancelar;
            break;
         }
        
    break;

    case 'cambiarEstadoVariosAlbaranes':
        $idsAlbaranes = $_POST['ids'];
        $estado = $_POST['estado'];
        if (is_array($idsAlbaranes) && $estado !== '0'){
            foreach ($idsAlbaranes as $id){
                $respuesta[] = $CAlb->modEstadoAlbaran($id, $estado);
            }
        }

    break;

    case 'comprobarAdjunto':
        include_once  $URLCom.'/modulos/mod_compras/tareas/comprobarAdjunto.php';
    break;

    case 'datosImprimir':
        //@Objetivo:
        //Imprimir un documento , dependiendo de donde venga se pone el nombre y envía todos los datos  
        //a la función montarHTMLimprimir que lo que realiza es simplemente montar el html una parte copn la cabecera y 
        //otra con el cuerpo del documento
        //debajo cargamos las clases de imprimir y la plantilla una vez generada y lista la plantilla devolvemos la ruta
        //para así desde javascript poder abrirla
        $id=$_POST['id'];
        $dedonde=$_POST['dedonde'];
        $idTienda=$_POST['idTienda'];
        $nombreTmp=$dedonde."compras.pdf";
        $htmlImprimir=montarHTMLimprimir($id, $BDTpv, $dedonde, $idTienda);
        $cabecera=$htmlImprimir['cabecera'];
        $margen_top_caja_texto= 56;
        $html=$htmlImprimir['html'];
        include_once $URLCom.'/controllers/planImprimir.php';
        $respuesta=$rutatmp.'/'.$nombreTmp;
    break;
    case 'obtenerFormularioEmail':
        $id=$_POST['id'];
        $dedonde=$_POST['dedonde'];
        $idTienda=$_POST['idTienda'];
        $destinatario=$_POST['destinatario'] ;
        // ===========    Configuracion =================== //
        $ClasesParametros = new ClaseParametros('parametros.xml');
        $parametros = $ClasesParametros->getRoot();
        $conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
        $conf_email = array( 'asunto' =>$conf_defecto['email']['0']->valor,
                             'body'   =>$conf_defecto['email']['1']->valor
                             );
        $conf_email['asunto'] = str_replace('%',$id,$conf_email['asunto']);
        // === FIN CARGA CONFIGURACION === //
	
        $htmlFormulario = array();
        
        $htmlFormulario['html'] = htmlFormularioEmail($destinatario,$conf_email,$id,$dedonde,$idTienda);
        $respuesta= $htmlFormulario;
        
    break; 

    case 'enviarXCorreo':
        include_once  $URLCom.'/modulos/mod_compras/tareas/enviarEmail.php';

    break;

    case 'eliminarTemporal':
        // @Objetivo:
        // Cuando pulsamos en icono eliminar pedido temporal en listado de pedidos,albaranes y facturas..
        $id=$_POST['id_temporal'];
        $dedonde=$_POST['dedonde'];
        if ($dedonde === 'ListadoPedidos'){
            $respuesta = $CPed->eliminarTemporal($id);
        } else {
            if ($dedonde === 'ListadoAlbaranes'){
                $respuesta = $CAlb->eliminarTemporal($id);
            } else {
                // Si no entro en ninguna de la opciones.
                $respuesta['error'] = 'No se ejecuto.';
            }

        }
    break;

    case 'htmlAgregarFilaAdjunto':
        //@ Objetivo:
        // Agregar la fila con los datos del albaran o pedido adjunto
        // en caja superior, donde se añade adjuntos.
        $res=lineaAdjunto($_POST['datos'], $_POST['dedonde']);
        $respuesta['html']=$res['html'];
    break;
    
    case 'htmlAgregarFilasProductos':
        //@ Objetivo:
        //Agrega las filas de productos , para insertar en vista productos.
        //@ Parametros:
        // $_POST[Productos] -> Es un strign ( json con array de los productos)
        $productos=json_decode($_POST['productos'],true);
        $dedonde=$_POST['dedonde']; 
        $respuesta=array('html'=>"");
        if ($_POST['cabecera'] !=='NO'){
            // Controlo si hay viene de un adjunto.
            // Mira que el adjunto puedo tener solo un producto, por ello no podemos controlar numero productos.
             //OBjetivo: agregar la fila con los datos del albaran o pedido adjunto
            $html= htmlDatosAdjuntoProductos($_POST['cabecera'],$dedonde);
            $respuesta['html'].=$html;
        }
        foreach($productos as $producto){
            // $producto puede ser un array con todos datos del producto o simplemente el primer campo de un producto.
            // por lo que si $producto no es array , quiere decir que $productos ( es solo un producto).
            if (!is_array($producto)){
                $res=htmlLineaProducto($productos, $dedonde);
                $respuesta['html'].=$res['html'];
                break;
            }else{
                $res=htmlLineaProducto($producto, $dedonde);
                $respuesta['html'].=$res['html'];
            }
        }
    break;

    case 'insertarImporte':
        include_once  $URLCom.'/modulos/mod_compras/tareas/insertarImporte.php';
    break;

    case 'modalAlbaranesCambioEstado':
        $respuesta['html'] = modalSelectEstadoAlbaranes();
    break;
    
    case 'modificarEstado':
        //@Objetivo: 
        //Modificar el estado adjunto dependiento de donde venga 
        //@Parámetros que recibe: 
        //id -> id que recibimos , puede ser id de pedido o id de albaran
        //dedonde->Para poder filtrar que función tenemos que ejecutar	
        $estado=$_POST['estado'];
        if ($_POST['dedonde']=="albaran"){
            $modEstado=$CPed->modEstadoPedido($_POST['id'], $estado);
            if (isset($mosEstado['error'])){
                    $respuesta['error']=$mosEstado['error'];
                    $respuesta['consulta']=$modEstado['consulta'];
            }
        }
        if ($_POST['dedonde']=="factura"){
            $modEstado=$CAlb->modEstadoAlbaran($_POST['id'], $estado);
            if (isset($mosEstado['error'])){
                    $respuesta['error']=$mosEstado['error'];
                    $respuesta['consulta']=$modEstado['consulta'];
            }
        }
    break;
    
    case 'nuevaIncidencia':
        //@ Objetivo:
        // Agregar una nueva incidencia, dirigimos los datos a la función addIncidencia
        //esta está situada en el modulo de incidencias e inserta una nueva fila a la tabla de módulo
        //de incidencias con los datos seleccionado en el modal.
        $datos= $_POST['datos'];
        $dedonde="mod_compras";
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
        if($mensaje){
            $nuevo=$CIncidencia->addIncidencia($dedonde, $datos, $mensaje, $estado, $numInicidencia);
            $respuesta=$nuevo;
        }
    break;

    default :
        // @ Objetivo:
        // marcar posible error.
        error_log( 'Tarea compras case no encontrado:'.$pulsado);
    
}
 echo json_encode($respuesta);
 return $respuesta;
?>
