<?php 
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
include_once $URLCom . '/modulos/mod_balanza/funciones.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseProductos.php';
include_once $URLCom . '/modulos/mod_balanza/clases/ClaseComunicacionBalanza.php';
$CBalanza=new ClaseBalanza($BDTpv);
$CProducto=new ClaseProductos($BDTpv);
$pulsado = $_POST['pulsado'];
$respuesta=array();
switch ($pulsado) {
    case 'addBalanza':
        //@Objetivo: Añadir una balanza nueva
        $datos=array(
            'nombreBalanza' => $_POST['nombreBalanza'],
            'modeloBalanza' => $_POST['modeloBalanza'],
            'secciones'     => $_POST['secciones'],
            'Grupo'         => $_POST['Grupo'],
            'Direccion'     => $_POST['Direccion'],
            'IP'            => $_POST['IP'],
            'soloPLUS'      => isset($_POST['soloPLUS']) ? 1 : 0
        );
        $html="";
        $addBalanza=$CBalanza->addBalanza($datos);
        if($addBalanza['error']<>"0"){
            $html='<div class="alert alert-danger">
                <strong>Danger!</strong> Error de sql: '.$addBalanza['consulta'].'.
                </div>';
        }else{
            $html='<div class="alert alert-success">
                  <strong>Success!</strong> Balanza registrada.
                </div>';
        }
        
        $respuesta['html']=$html;
        $respuesta['balanza']=$addBalanza;
    break;
    case 'htmlPlu':
    //OBjetivo: OBjetivo llamar a la función htmlAddPlu que devuelve el html para añadir un plu
        $seccion=$_POST['secciones'];
        $idBalanza=$_POST['idBalanza'];
        $html=htmlAddPLU($seccion, $idBalanza);
        $respuesta['html']=$html;
    break;
    case 'buscarProducto':
    //@Objetivo: buscar producto
    //Devuelve o los datos de un  producto o el html del modal
        $busqueda=camposBuscar($_POST['campo'], $_POST['busqueda']);
        $result=$CBalanza->buscarArticuloCampo($busqueda);
        if(count($result['datos'])==1){
            $datos=array(
            'idArticulo'=>$result['datos'][0]['idArticulo'],
            'nombre'=>$result['datos'][0]['articulo_name'],
            'referencia'=>$result['datos'][0]['crefTienda'],
            'codBarras'=>$result['datos'][0]['codBarras']
            );
            if($_POST['idcaja']=='codBarras'){
                if($result['datos'][0]['codBarras']<>$_POST['busqueda']){
                    $html=modalProductos($_POST['busqueda'], $result['datos'], $_POST['campo']);
                    $respuesta['html']=$html['html'];
                }else{
                    $respuesta['datos']=$datos;
                }
            }else{
                $respuesta['datos']=$datos;
            }
            
            
        }else{
            $html=modalProductos($_POST['busqueda'], $result['datos'], $_POST['campo']);
            $respuesta['html']=$html['html'];
        }
        $respuesta['buscar']=$result;
    break;
    case 'addPlu':
    //@Objetivo: añadir plu
        $idBalanza = $_POST['idBalanza'];
        $plu = $_POST['plu'];
        $crefTienda = $_POST['cref'];
        $articulo_name = $_POST['articulo_name'];
        $pvpCiva = $_POST['pvpCiva'];
        if(isset($_POST['seccion'])){
            $seccion = $_POST['seccion'];
        }else{
            $seccion = "";
        }
        
        $idArticulo=$_POST['idArticulo'];
        $buscarPlu=$CBalanza->buscarPluEnBalanza($plu, $idBalanza);
        if(isset($buscarPlu['datos'])){
            $respuesta['error']='Ya existe el producto con id:'.$buscarPlu['datos']['0']['idArticulo'].' ese mismo plu en la balanza';
            $respuesta['buscarPlu'] = json_encode($buscarPlu);
        }else{
            $addPlu=$CBalanza->addPlu($plu, $idBalanza, $seccion, $idArticulo);
            $datos=array(
            'plu'=> $plu,
            'seccion'=> $seccion,
            'idArticulo'=>$idArticulo,
            'articulo_name' => $articulo_name,
            'crefTienda' => $crefTienda,
            'pvpCiva' => $pvpCiva
            );
            $html=htmlLineaPlu($datos, $idBalanza);
            $respuesta['html']=$html;
           
        }
    break;
    case 'eliminarPlu':
    //@OBjetivo: eliminar plu
        $eliminar=$CBalanza->eliminarplu($_POST['idBalanza'], $_POST['plu']);
    break;
    case 'mostrarDatosBalanza':
    //@Objetivo: Mostrar los datos de una balanza con los plu
        $datosBalanza=$CBalanza->datosBalanza($_POST['idBalanza']);
        if(isset($datosBalanza['datos'])){
            $datosplu=$CBalanza->pluDeBalanza($_POST['idBalanza'], $_POST['filtro']);
            $respuesta['datosPlu'] = json_encode($datosplu);
            if(isset($datosplu['datos'])){
                $html=htmlDatosListadoPrincipal($datosBalanza['datos'][0], $datosplu['datos'], $_POST['filtro']);
                $respuesta['html']=$html['html'];
                $respuesta['htmlDatosBalanza']=$html['htmlBalanza'];
            }
        }
    break;
    case 'modificarBalanza':
    //@Objetivo: Modificar los datos de la balanza con el nuevo formato de datos
        $datos = array(
            'nombreBalanza' => $_POST['nombreBalanza'],
            'modeloBalanza' => $_POST['modeloBalanza'],
            'secciones'     => $_POST['secciones'],
            'Grupo'         => $_POST['Grupo'],
            'Direccion'     => $_POST['Direccion'],
            'IP'            => $_POST['IP'],
            'soloPLUS'      => isset($_POST['soloPLUS']) ? 1 : 0
        );
        $modificarBalanza = $CBalanza->modificarBalanza($_POST['idBalanza'], $datos);
        $respuesta['modif'] = $modificarBalanza;
    break;
    case 'crearDirectorioBalanza':
        // Objetivo: Crear el directorio de la balanza si no existe
        $CBalanzaComunicacion = new ClaseComunicacionBalanza($BDTpv);
        $datos = array(
            'nombreBalanza' => $_POST['nombreBalanza'],
            'idBalanza' => $_POST['idBalanza'],
            'ipBalanza' => $_POST['ipBalanza'],
            'grupoBalanza' => $_POST['grupoBalanza'],
            'direccionBalanza' => $_POST['direccionBalanza'],
            'ipPc' => $_POST['ipPc'],
            'serieH' => $_POST['serieH'],
            'serieTipo' => $_POST['serieTipo'],
            'modoDirectorio' => $_POST['modoDirectorio']
        );
        $nombreSinEspacios = str_replace(' ', '', $datos['nombreBalanza']);
        $rutaBalanza = $RutaServidor . $rutatmp . '/' . $nombreSinEspacios . $datos['idBalanza'];
        error_log("Ruta de la balanza: " . $rutaBalanza);
        $CBalanzaComunicacion->setRutaBalanza($rutaBalanza);
        $crearDirectorio = $CBalanzaComunicacion->crearDirectorioBalanza($datos);
        $respuesta['balanzaCom'] = $crearDirectorio;
    break;
    case 'guardarPlu':
        // Guardar edición de un solo PLU solo si hay cambios
        $idArticulo = intval($_POST['idArticulo']);
        $idBalanza = intval($_POST['idBalanza']);
        $plu = $_POST['plu'];
        $seccion = isset($_POST['seccion']) ? $_POST['seccion'] : 0;

        // Obtener el PLU actual usando el método de la clase
        $actual = $CBalanza->obtenerPluActual($idBalanza, $idArticulo);

        if ($actual && ($actual['plu'] == $plu && $actual['seccion'] == $seccion)) {
            // No hay cambios, no modificar nada
            $respuesta['resultado'] = ['success' => true, 'mensaje' => 'Sin cambios'];
        } else {
            $resultado = $CBalanza->updatePlu($idArticulo, $idBalanza, $plu, $seccion);
            $respuesta['resultado'] = $resultado;
        }
    break;
    case 'guardarTodosPlus':
        // Guardar edición masiva de PLUs solo si hay cambios
        $datos = isset($_POST['datos']) ? $_POST['datos'] : [];
        if (is_string($datos)) {
            $datos = json_decode($datos, true);
        }
        if (!is_array($datos)) {
            $datos = [];
        }
        $idBalanza = intval($_POST['idBalanza']);
        $errores = [];
        $CBalanza = new ClaseBalanza($BDTpv);

        foreach ($datos as $pluData) {
            $idArticulo = intval($pluData['idArticulo']);
            $pluNuevo = $pluData['plu'];
            $seccionNueva = $pluData['seccion'] ?? 0;

            // Obtener el PLU actual usando el método de la clase
            $actual = $CBalanza->obtenerPluActual($idBalanza, $idArticulo);

            // Solo actualizar si hay cambios
            if ($actual && ($actual['plu'] != $pluNuevo || $actual['seccion'] != $seccionNueva)) {
                $res = $CBalanza->updatePlu($idArticulo, $idBalanza, $pluNuevo, $seccionNueva);
                if (isset($res['error'])) {
                    $errores[] = $res['error'];
                }
            }
        }
        $respuesta['success'] = empty($errores);
        $respuesta['errores'] = $errores;
    break;
    case 'mostrarArticulosPeso':
        $idBalanza = intval($_POST['idBalanza']);
        $articulos = $CBalanza->getArticulosPesoSinPLU($idBalanza);
        $respuesta['html'] = htmlArticulosPeso($articulos, $idBalanza);
    break;
}
echo json_encode($respuesta);
?>
