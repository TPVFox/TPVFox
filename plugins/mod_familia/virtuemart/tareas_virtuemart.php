<?php 

$pulsado = $_POST['pulsado'];

include_once ("./../../../configuracion.php");
include_once ($RutaServidor.$HostNombre. "/clases/ClaseSession.php");
include_once $RutaServidor.$HostNombre.'/modulos/mod_familia/clases/ClaseFamilias.php';
$thisTpv = new ClaseSession();
$BDTpv = $thisTpv->getConexion();
$CFamilia = new ClaseFamilias($BDTpv);
include_once ($RutaServidor.$HostNombre."/plugins/mod_familia/virtuemart/ClaseVirtuemartFamilia.php");
$ObjViruemart = new PluginClaseVirtuemartFamilia();
switch ($pulsado) {
    case 'modificarFamiliaWeb':
        $datos = $_POST['datos'];
        $respuesta = array();
        $datosComprobaciones=json_decode($datos, true);
        if($datosComprobaciones['idFamiliaPadre']=="0"){
            $comprobarPadre['datos']['idFamilia_tienda']=0;
            $padre=0;
        }else{
            $comprobarPadre=$CFamilia->comprobarPadreWeb($datosComprobaciones['idTienda'], $datosComprobaciones['idFamiliaPadre']);
            $padre=$comprobarPadre['datos'][0]['idFamilia_tienda'];
            
        }
        $respuesta['padre']=$padre;
        if(!is_null($padre)){
     
        if($datosComprobaciones['idFamiliaWeb']>0){
             $respuesta['caracteres']=strlen($datosComprobaciones['nombreFamilia']);
              if(strlen($datosComprobaciones['nombreFamilia'])>180){
                    $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                <strong>Danger!</strong> No se puede modificar el producto 
                                                por que el nombre es superior a 180 caracteres.
                                            </div>';
                }else{
                    $datosMod=array(
                        'nombre'=>$datosComprobaciones['nombreFamilia'],
                        'idPadre'=>$padre,
                        'id'=>$datosComprobaciones['idFamiliaWeb']
                    );
                     $datosMod=json_encode($datosMod);
                    $modificar=$ObjViruemart->modificarFamiliaWeb($datosMod);
                    $respuesta['mod']=$modificar;
                    if(strlen($modificar['Datos']['error']) == 0){
                        $respuesta['htmlAlerta']='<div class="alert alert-success">
                                                    <strong>Success!</strong> Has modificados los datos del producto.
                                                </div>';
                             }else{
                        $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                    <strong>Danger!</strong> Error de sql : '.$modificarProducto['Datos']['error'].' consulta: '.$modificarProducto['Datos']['consulta'].'
                                                </div>';
                    }
                }
        }else{
            $datosComprobaciones['vendor']=1;
            $datosComprobaciones['limit']=0;
            $datosComprobaciones['hits']=0;
            $datosComprobaciones['parametros']='show_store_desc=""|showcategory_desc=""|showcategory=""|categories_per_row=""|showproducts=""|omitLoaded=""|showsearch=""|productsublayout=""|featured=""|featured_rows=""|omitLoaded_featured=""|discontinued=""|discontinued_rows=""|omitLoaded_discontinued=""|latest=""|latest_rows=""|omitLoaded_latest=""|topten=""|topten_rows=""|omitLoaded_topten=""|recent=""|recent_rows=""|omitLoaded_recent=""|';
            $datosComprobaciones['publicado']=1;
            $datosComprobaciones['fecha']=date("Y-m-d H:i:s");
            $datosComprobaciones['usuario']='911';
            $datosComprobaciones['locked_by']=0;
            $datosComprobaciones['alias']=str_replace(' ', '-', $datosComprobaciones['nombreFamilia']);
            $datosComprobaciones['padre']=$padre;
            
            
            $datos=json_encode($datosComprobaciones);
            $respuesta['datos']=$datosComprobaciones;
            $addFamilia = $ObjViruemart->addFamilia($datos);
            $respuesta['addFamilia']=$addFamilia;
            if($addFamilia['Datos']['idFamilia']>0){
                    $addRegistro=$CFamilia->addFamiliaTiendaWeb($datosComprobaciones['idTienda'], $datosComprobaciones['idFamiliaTpv'], $addFamilia['Datos']['idFamilia']);
                    if(isset($addFamilia['Datos']['error'])){
                         $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                    <strong>Danger!</strong> Error al añadir la familia a la web. '.$addFamilia['Datos']['error'].' Consulta: '.$addProducto['Datos']['consulta'].'
                                                </div>';
                    }else{
                         $respuesta['htmlAlerta']='<div class="alert alert-success">
                                                    <strong>Exito!</strong> Subida Familia a la web correctamente
                                                </div>';
                    }
            }else{
                $respuesta['error']=$addFamilia['Datos']['error'];
                $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                    <strong>Danger!</strong> Error al añadir la familia a la web. '.$addFamilia['Datos']['error'].' Consulta: '.$addProducto['Datos']['consulta'].'
                                                </div>';
            }
              
        }
    }else{
        $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                    <strong>Danger!</strong> NO puedes añadir/modificar esta familia ya que el padre seleccionado no esta subido
                                                </div>';
    }
    
    break;
}
 echo json_encode($respuesta);
?>
