<?php 
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
include_once $URLCom . '/modulos/mod_balanza/funciones.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseProductos.php';
$CBalanza=new ClaseBalanza($BDTpv);
$CProducto=new ClaseProductos($BDTpv);
$pulsado = $_POST['pulsado'];
$respuesta=array();
switch ($pulsado) {
    case 'addBalanza':
        $datos=array(
            'nombreBalanza'=>$_POST['nombreBalanza'],
            'modeloBalanza'=>$_POST['modeloBalanza'],
            'teclas'        =>$_POST['teclas']
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
        $tecla=$_POST['teclas'];
        $idBalanza=$_POST['idBalanza'];
        $html=htmlAddPLU($tecla, $idBalanza);
        $respuesta['html']=$html;
    break;
    case 'buscarProducto':
        $campo=camposBuscar($_POST['campo'], $_POST['busqueda']);
        $result=$CBalanza->buscarArticuloCampo($campo);
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
        $idBalanza = $_POST['idBalanza'];
        $plu = $_POST['plu'];
        $crefTienda = $_POST['cref'];
        $articulo_name = $_POST['articulo_name'];
        if(isset($_POST['tecla'])){
            $tecla = $_POST['tecla'];
        }else{
            $tecla = "";
        }
        
        $idArticulo=$_POST['idArticulo'];
        $buscarPlu=$CBalanza->buscarPluEnBalanza($plu, $idBalanza);
        if(isset($buscarPlu['datos'])){
            $respuesta['error']="Ya existe ese mismo plu en la balanza";
        }else{
            $addPlu=$CBalanza->addPlu($plu, $idBalanza, $tecla, $idArticulo);
            $datos=array(
            'plu'=> $plu,
            'tecla'=> $tecla,
            'idArticulo'=>$idArticulo,
            'articulo_name' => $articulo_name,
            'crefTienda' => $crefTienda
            );
            //~ $datos = $CBalanza->buscarPluEnBalanza($plu, $idBalanza);
            $html=htmlLineaPlu($datos, $idBalanza);
            $respuesta['html']=$html;
        }
    break;
    case 'eliminarPlu':
        $eliminar=$CBalanza->eliminarplu($_POST['idBalanza'], $_POST['plu']);
    break;
    case 'mostrarDatosBalanza':
        $datosBalanza=$CBalanza->datosBalanza($_POST['idBalanza']);
        if(isset($datosBalanza['datos'])){
            $datosplu=$CBalanza->pluDeBalanza($_POST['idBalanza']);
            if(isset($datosplu['datos'])){
                $html=htmlDatosListadoPrincipal($datosBalanza['datos'][0], $datosplu['datos']);
                $respuesta['html']=$html['html'];
                $respuesta['htmlDatosBalanza']=$html['htmlBalanza'];
            }
        }
    break;
    case 'modificarBalanza':
        $modificarBalanza=$CBalanza->modificarBalanza($_POST['idBalanza'], $_POST['nombre'], $_POST['modelo'], $_POST['tecla']);
        $respuesta['modif']=$modificarBalanza;
    break;
}
echo json_encode($respuesta);
?>
