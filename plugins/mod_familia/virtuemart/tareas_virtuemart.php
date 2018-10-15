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
// ---------------   CONFIGURACION DE VIRTUEMART ----------------------------------  //
// Esta configuracion deberíamos obtenerla de virtuemart, ahora la ponemos nosotros a mano.
$conf_virtuemart = array();
$conf_virtuemart['parametros'] =
'show_store_desc=""|showcategory_desc=""|showcategory=""|categories_per_row=""|showproducts=""|omitLoaded=""|showsearch=""|productsublayout=""|featured=""|featured_rows=""|omitLoaded_featured=""|discontinued=""|discontinued_rows=""|omitLoaded_discontinued=""|latest=""|latest_rows=""|omitLoaded_latest=""|topten=""|topten_rows=""|omitLoaded_topten=""|recent=""|recent_rows=""|omitLoaded_recent=""|';

$conf_virtuemart['vendor'] = 1;
$conf_virtuemart['limit'] = 0;
$conf_virtuemart['hits']=0;
$conf_virtuemart['publicado']=1;
$conf_virtuemart['fecha']=date("Y-m-d H:i:s");
$conf_virtuemart['usuario']='911';
$conf_virtuemart['locked_by']=0;




switch ($pulsado) {
    case 'modificarFamiliaWeb':
        $datos = $_POST['datos'];
        $respuesta = array();
        $datosComprobaciones=json_decode($datos, true);
        if($datosComprobaciones['idFamiliaPadre']=="0"){
            $comprobarPadre['datos']['idFamilia_tienda']=0;
            $padre=0;
        }else{
            $comprobarPadre=$CFamilia->obtenerRelacionFamilia($datosComprobaciones['idTienda'], $datosComprobaciones['idFamiliaPadre']);
            $padre=$comprobarPadre['datos'][0]['idFamilia_tienda'];
            
        }
        $respuesta['padre']=$padre;
        if(!is_null($padre)){
            // Si existe padre continuamos.
            if($datosComprobaciones['idFamiliaWeb']>0){
                // Modificamos ya que no es nuevo.
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
                $datosComprobaciones['parametros']=$conf_virtuemart['parametros'];
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
                if(isset ($addFamilia['Datos']['idFamilia'])){
                    // Hubo datos por lo que la conexion es correcta.
                    $addRegistro=$CFamilia->addFamiliaTiendaWeb($datosComprobaciones['idTienda'], $datosComprobaciones['idFamiliaTpv'], $addFamilia['Datos']['idFamilia']);
                    if(isset($addFamilia['Datos']['error'])){
                         $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                    <strong>Danger!</strong> Error 1 al añadir la familia a la web. '.$addFamilia['Datos']['error'].' Consulta: '.$addProducto['Datos']['consulta'].'
                                                </div>';
                    }else{
                         $respuesta['htmlAlerta']='<div class="alert alert-success">
                                                    <strong>Exito!</strong> Subida Familia a la web correctamente
                                                </div>';
                    }
                }else{
                    if (isset ($addFamilia['error_conexion'])){
                        $respuesta['error']=$addFamilia['info'];
                        $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                             <strong>Danger!</strong> Error 2 al añadir la familia a la web.<br/>'
                                             .$addFamilia['error_conexion'].'<br/>Url: '
                                             .$addFamilia['info']['url']
                                             .'</div>';
                    } else {
                        // Pienso que no llega nunca sin error conexion
                        // por si acaso lo marco.
                        error_log ( 'Error en tareas_virtuemart lines 113 , hubo un error sin identificar ');
                    }
                }
                  
            }
    }else{
        $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                    <strong>Danger!</strong> NO puedes añadir/modificar esta familia ya que el padre seleccionado no esta subido
                                                </div>';
    }
    
    break;
    case 'subirHijosWeb':
        $idPadre=$_POST['idPadre'];
        $idTienda=$_POST['idTienda'];
        // Obtenemos los hijos del padre.
        $descendientes=$CFamilia->descendientes($idPadre);
        $padreWeb=$CFamilia->obtenerRelacionFamilia($idTienda, $idPadre);
        $respuesta['descendientes']=$descendientes;
        $respuesta['padre']=$padreWeb;
        $idPadreWeb=$padreWeb['datos'][0]['idFamilia_tienda'];
        error_log('En tarea_virtuemar linea 132:'.$idPadreWeb);
        error_log('En tarea_virtuemar linea 133:'.$idPadre);
        $idsDescendientes=$descendientes['datos'];
        $datosFamilias=array();
        $familiasNoSubidas=array();
        $familiasSubidas=array();
        foreach ($idsDescendientes as $des){
            // Inicializo variable con los datos configuracion por defecto.
            $datosComprobaciones=$conf_virtuemart;
            // Ya ponemos el dato del padre que lo sabemos.
            $datosComprobaciones['padre']=$idPadreWeb;
            // Busco el nombre
            $nombreFamilia=$CFamilia->buscarPorId($des['idFamilia']);
            $datosComprobaciones['nombreFamilia']=$nombreFamilia['datos'][0]['familiaNombre'];
            $datosComprobaciones['alias']=str_replace(' ', '-', $nombreFamilia['datos'][0]['familiaNombre']);
            // Comprobamos que no exista ya relacion para esa familia.
            $comprobarSiExiste=$CFamilia->obtenerRelacionFamilia($idTienda,$des['idFamilia']);
            $respuesta['comprobacionSiExiste'] = $comprobarSiExiste;
            $datos=json_encode($datosComprobaciones);
            $addFamilia = $ObjViruemart->addFamilia($datos);
            if($addFamilia['Datos']['idFamilia']>0){
                $addRegistro=$CFamilia->addFamiliaTiendaWeb($idTienda, $des['idFamilia'], $addFamilia['Datos']['idFamilia']);
                if(!isset($addFamilia['Datos']['error'])){
                    array_push($familiasSubidas, $nombreFamilia['datos'][0]['familiaNombre']); 
                }
            }else{
                array_push($familiasNoSubidas, $nombreFamilia['datos'][0]['familiaNombre']);
            }
            
        }
        $respuesta['familiasSubidas']=$familiasSubidas;
        $respuesta['familiasNoSubidas']=$familiasNoSubidas;
        
    
    break;
}
 echo json_encode($respuesta);
?>
