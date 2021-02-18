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

$rutaCompleta = $RutaServidor.$HostNombre;
include_once($rutaCompleta.'/clases/ClaseSession.php');

$CSession =  new ClaseSession();

// Incluimos controlador.
include_once $URLCom.'/controllers/Controladores.php';
$Controler = new ControladorComun; 
// Incluimos funciones
include_once $URLCom.'/modulos/mod_producto/funciones.php';
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);
// Nueva clase 
include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
$NCArticulo = new ClaseProductos($BDTpv);
include_once $URLCom.'/clases/articulos.php';
$CArticulo=new Articulos($BDTpv);
include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
$CAlbaran=new AlbaranesCompras($BDTpv);

include_once $URLCom.'/modulos/mod_familia/clases/ClaseFamilias.php';
$CFamilia=new ClaseFamilias($BDTpv);
include_once $URLCom.'/clases/Proveedores.php';
$CProveedor=new Proveedores($BDTpv);
$respuesta = array();

switch ($pulsado) {

    case 'buscarNombreFammilia':
        $idFamilia=$_POST['idfamilia'];
        $idProducto=$_POST['idProducto'];
        if($idProducto==0){
            $productosEnFamilia=array();
            $productos=$_SESSION['productos_seleccionados'];
            $respuesta['productos']=$productos;
            $contadorProductos=0;
            foreach ($productos as $idProducto){
                 $comprobar=$CFamilia->comprobarRegistro($idProducto, $idFamilia);
                 $respuesta['datos']=$comprobar['datos'];
                if(isset($comprobar['datos'])){
                   array_push($productosEnFamilia, $idProducto);
                }else{
                   $addFamilia=$CFamilia->guardarProductoFamilia($idProducto, $idFamilia);
                   if($addFamilia['error']){
                        $respuesta['error']=$addFamilia;
                   }else{
                       $contadorProductos=$contadorProductos+1;
                   }
                }
            }
                $respuesta['contadorProductos']=$contadorProductos;
                $respuesta['productosEnFamilia']=$productosEnFamilia;
        }else{
            $comprobar=$CFamilia->comprobarRegistro($idProducto, $idFamilia);
            $respuesta['comprobar']=$comprobar;
            if(isset($comprobar['datos'])){
                $respuesta['error']=1;
            }else{
                $nombreFamilia=$CFamilia->buscarPorId($idFamilia);
                $nuevaFila = '<tr>'
                        . '<td><input type="hidden" id="idFamilias_'.$idFamilia
                        .'" name="idFamilias_'.$idFamilia.'" value="'.$idFamilia.'">'
                        .$idFamilia.'</td>'
                        .'<td>'.$nombreFamilia['datos'][0]['familiaNombre'].'</td>'
                        .'<td><a id="eliminar_'.$idFamilia
                        .'" class="glyphicon glyphicon-trash" onclick="eliminarFamiliaProducto(this)"></a>'
                        .'</td>'.'</tr>';
                $respuesta['html']=$nuevaFila;
                $respuesta['nombre']=$nombreFamilia;
            }
        }
    break;

    case 'buscarProductosDeFamilia':
        if($_POST['idfamilia']=="0"){
             $productos=$CFamilia->buscarProductosSinFamilias();
        }else{
            $productos=$CFamilia->buscarProductosFamilias($_POST['idfamilia']);
        }
        $idsProductos=array();
        foreach ($productos['datos'] as $producto){
            array_push($idsProductos, $producto['idArticulo']);
        }
        $respuesta['Productos']=$idsProductos;
    break;

    case 'buscarProductosProveedor':
        $productos=$CProveedor->buscarProductosProveedor($_POST['idProveedor']);
        $idsProductos=array();
        foreach ($productos as $producto){
            
            array_push($idsProductos, $producto['idArticulo']);
        }
        $respuesta['Productos']=$idsProductos;
    break;

    case 'cambiarEstadoProductos':
        $productos=$_POST['productos'];
        $estado=$_POST['estado'];
        $modEstado=$NCArticulo->modificarVariosEstados($estado, $productos);
        $respuesta['consulta']=$modEstado;
        $respuesta['productos']=$productos;
        $respuesta['estado']=$estado;
    break;
    
    case 'comprobarReferencia':
		$referencia=$_POST['referencia'];
		$comprobacion=$NCArticulo->buscarReferenciaProductoTienda( $referencia);
		$respuesta=$comprobacion;
	break;
	
	case 'ComprobarSiExisteCodbarras':
		$respuesta = $NCArticulo->GetProductosConCodbarras($_POST['codBarras']);
	break;

    case 'datosRegularizar':
        include_once $URLCom.'/modulos/mod_producto/tareas/htmlModalRegularizacionStock.php';
    break;

    case 'eliminarCoste':
		$respuesta = array();
		$idArticulo=$_POST['idArticulo'];
		$dedonde=$_POST['dedonde'];
		$id=$_POST['id'];
		$tipo=$_POST['tipo'];
		$mod=$CArticulo->modEstadoArticuloHistorico($idArticulo, $id, $dedonde, $tipo,'Sin Cambios');
		$respuesta['sql']=$mod;
    break;

    case 'eliminarSeleccion':
		$deseleccionar=eliminarSeleccion();
        $respuesta['deseleccionado'] = ' Se deselecciono '.$deseleccionar;
	break;
    
    case 'eliminarHistorico':
        $idHistorico=$_POST['idHistorico'];
        $eliminar=$NCArticulo->EliminarHistorico($idHistorico);
        $respuesta=$eliminar;
    break;

    case 'eliminarReferenciaTienda':
        $idCruce=$_POST['idCruce'];
        $eliminar_cruce_tienda=$NCArticulo->EliminarCruceTienda($idCruce);
        $respuesta=$eliminar_cruce_tienda;
    break;

    case 'eliminarRefProveedor':
        $idProveedor=$_POST['idProveedor'];
        $idArticulo=$_POST['idArticulo'];
        $eliminar_ref_proveedor=$NCArticulo->EliminarRefProveedor($idArticulo,$idProveedor);
        $respuesta=$eliminar_ref_proveedor;
    break;

    case 'eliminarProductos':
        $tiendaWeb=$_POST['idTiendaWeb'];
        $productos=$_SESSION['productos_seleccionados'];
        $productosNoEliminados=array();
        $productosEliminados=array();
        foreach ($productos as $idProducto){
            $carga=$NCArticulo->GetProducto($idProducto);
            $datosProducto=$NCArticulo->ArrayPropiedades();
            $datos=[
                'nombre'=>$datosProducto['articulo_name'],
                'id'=>$idProducto,
                'comprobaciones'=> '',
            ];

            if($datosProducto['estado']=="Baja"){
                $comprobacionesEliminar=$NCArticulo->ComprobarEliminar($idProducto, $tiendaWeb);
                if($comprobacionesEliminar['bandera']==1){
                    $datos['comprobaciones'] = $comprobacionesEliminar['resultado'];
                    $productosNoEliminados[] = $datos;
                    // $respuesta[] = [
                    //     'consulta_con_datos' => $comprobacionesEliminar['resultado']['consulta'],
                    //     'haydatos' => $comprobacionesEliminar['resultado']['haydatos'],
                    // ];
                }else{                    
                    $productosEliminados[] = $datos;
                    productosSesion($idProducto,'NoSeleccionar');
                }

                if(isset($comprobacionesEliminar['resultado']['error'])){
                    $respuesta['errores']=['datos'=>$datos,
                    'consulta'=> $comprobacionesEliminar['consulta'],
                    ];
                }
                
            }else{
                $datos['comprobaciones'] = array(0=>['mensaje' =>'Revisa estado producto, tiene que estar: baja']);
                array_push( $productosNoEliminados, $datos);
            }
        }
        $respuesta['NoEliminados']= $productosNoEliminados;        
        $respuesta['Eliminados']=$productosEliminados;

        $respuesta['html'] = construirHTMLEliminarProductos($productosEliminados, $productosNoEliminados);
    break;

    case 'grabarRegularizacion':
        $idArticulo = $_POST['idarticulo'];
        $stockReal = $_POST['stockReal'];
        $Producto = $NCArticulo->GetProducto($idArticulo);
        $stocksumar =  floatval($stockReal) - $Producto['stocks']['stockOn'];
        $idTienda = isset($_SESSION['tiendaTpv']) ? $_SESSION['tiendaTpv']['idTienda'] : 1;
        $idUsuario = isset($_SESSION['usuarioTpv']) ? $_SESSION['usuarioTpv']['id'] : 0;
        // Ahora cambiamos el stock
        $respuesta['cambioStock'] = alArticulosStocks::regularizaStock($idArticulo, $idTienda, $stocksumar, K_STOCKARTICULO_SUMA);
        // Montamos array para grabar en tabla stocksRegularizacion
        $datos = array ('idArticulo' => $idArticulo,
                        'idTienda' => $idTienda,
                        'stockActual' => $Producto['stocks']['stockOn'],
                        'stockModif' => $stocksumar,
                        'stockFinal' => $stockReal,
                        'stockOperacion' => K_STOCKARTICULO_SUMA,
                        'idUsuario' => $idUsuario
                        );
        $respuesta['registroRegularizacionStock'] = alArticulosStocks::grabarRegularizacion($datos);
        
    break;

    case 'Grabar_configuracion':
		// Grabamos configuracion
		$configuracion = $_POST['configuracion'];
		// Ahora obtenemos nombre_modulo y usuario , lo ponermos en variable y quitamos array configuracion.
		$nombre_modulo = $configuracion['nombre_modulo'];
		$idUsuario = $configuracion['idUsuario'];
		unset($configuracion['nombre_modulo'],$configuracion['idUsuario']);
		
		$respuesta = $Controler->GrabarConfiguracionModulo($nombre_modulo,$idUsuario,$configuracion);		
		$respuesta['configuracion'] = $configuracion ; 
	break;

	case 'HtmlCajaBuscarProveedor':
        include_once $URLCom.'/modulos/mod_producto/tareas/htmlCajaBuscarProveedor.php';
	break;

    case 'HtmlLineaCodigoBarras';
		$respuesta['html']	= HtmlLineaCodigoBarras($_POST['fila']);
    break;

    case 'imprimir':
		// De momento no puedo pasar a tareas ya devuelve un fichero ... 
		$id=$_POST['id'];
		$dedonde="Recalculo";
		$nombreTmp=$dedonde."recalculo.pdf";
        if ($_POST['bandera']==1){
			$htmlImprimir=montarHTMLimprimir($id, $BDTpv, $dedonde, $CArticulo, $CAlbaran, $CProveedor);
		}else{
			$dedonde="albaran";
			$htmlImprimir=montarHTMLimprimirSinGuardar($id, $BDTpv, $dedonde, $CArticulo, $CAlbaran, $CProveedor);
			
		}
		$cabecera=$htmlImprimir['cabecera'];
		$html=$htmlImprimir['html'];
		include_once $URLCom.'/clases/imprimir.php';
        include_once $URLCom.'/controllers/planImprimirRe.php';
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$respuesta['fichero']=$ficheroCompleto;
        //~ $respuesta['html']=$html;
	break;
    
	case 'imprimirEtiquetas':
        include_once $URLCom.'/modulos/mod_producto/tareas/imprimirEtiquetas.php';
	break;
    
	case 'obtenerCostesProveedor':
        include_once $URLCom.'/modulos/mod_producto/tareas/obtenerCostesProveedor.php';
	break;
    
	case 'productosSesion':
		$respuesta=productosSesion($_POST['id'], $_POST['seleccionar']);
	break;

    case 'obtenerEstadoProductoWeb';
        // Objetivo es obtener el estado de los productos que enviemos a la web.
        // @ Parametros:
        //      ids_productos = (array) ids de la los productos de tpv.
        //      id_web = (int) con el id de la tienda web.
        $ids_productos  = $_POST['ids_productos'];
        $id_tiendaWeb   = $_POST['id_tiendaWeb'];
        // @ Devolvemos:
        // array con los ids_productos y si estado
		$ObjVirtuemart = $NCArticulo->SetPlugin('ClaseVirtuemart');         // Creo el objeto de plugin Virtuemart.
        // Lo  ideal sería mandar solo una petición ya que así no saturamos la red...
        // pero de momento lo dejo..
        foreach ($ids_productos as $key=>$idProducto){
            $producto=$NCArticulo->GetProducto($idProducto);
            $idVirtuemart = 0;
            foreach ($producto['ref_tiendas'] as $ref){
                // Debemos comprobar que es la referencia de la tienda web.. FALTA
                if ($ref['idVirtuemart'] >0) {
                    $idVirtuemart = $ref['idVirtuemart'];
                }
            }
            if ($idVirtuemart > 0) {
                $datosWebCompletos=$ObjVirtuemart->datosCompletosTiendaWeb($idVirtuemart,$producto['iva'],$producto['idArticulo'],$id_tiendaWeb);
            } else {
                    $datosWebCompletos = array ( 'datosWeb' => array('estado' =>'NoExiste') );
            }
            $respuesta[$key]= array(
                    'estado'=>$datosWebCompletos['datosWeb']['estado'],
                    'idArticulo' => $idProducto
                    );
        }
    break;

    case 'retornarCoste':
		$idArticulo=$_POST['idArticulo'];
		$dedonde=$_POST['dedonde'];
		$id=$_POST['id'];
		$tipo=$_POST['tipo'];
		$mod=$CArticulo->modEstadoArticuloHistorico($idArticulo, $id, $dedonde, $tipo, 'Pendiente');
		$respuesta['sql']=$mod;
	break;
    
    case 'modalFamiliaProducto':
        if($_POST['idProducto']==""){
            $idProducto=0;
        }else{
             $idProducto=$_POST['idProducto'];
        }
        $familias=$CFamilia->todoslosPadres();
        $modal=modalAutocompleteFamilias($familias['datos'], $idProducto);
        $respuesta['familias']=$familias;
        $respuesta['html']=$modal;
    break;

    case 'modalEstadoProductos':
        $productos=$_SESSION['productos_seleccionados'];
        $modal=modalAutocompleteEstadoProductos($productos);
        $respuesta['html']=$modal;
    break;


}
echo json_encode($respuesta);
?>
