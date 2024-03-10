<?php 

$pulsado = $_POST['pulsado'];

include_once ("./../../../configuracion.php");
include_once ($RutaServidor.$HostNombre. "/clases/ClaseSession.php");
include_once $RutaServidor.$HostNombre.'/modulos/mod_familia/clases/ClaseFamilias.php';
$thisTpv = new ClaseSession();
$BDTpv = $thisTpv->getConexion();
$CFamilia = new ClaseFamilias($BDTpv);
include_once ($RutaServidor.$HostNombre."/plugins/mod_familia/virtuemart/ClaseVirtuemartFamilia.php");
$ObjVirtuemart = new PluginClaseVirtuemartFamilia();
// ---------------   CONFIGURACION DE VIRTUEMART ----------------------------------  //
// Esta configuracion deberíamos obtenerla de virtuemart, ahora la ponemos nosotros a mano.
$parametros = 'show_store_desc=""|showcategory_desc=""|showcategory=""|categories_per_row=""|showproducts=""|omitLoaded=""|showsearch=""|productsublayout=""|featured=""|featured_rows=""|omitLoaded_featured=""|discontinued=""|discontinued_rows=""|omitLoaded_discontinued=""|latest=""|latest_rows=""|omitLoaded_latest=""|topten=""|topten_rows=""|omitLoaded_topten=""|recent=""|recent_rows=""|omitLoaded_recent=""|';

$conf_virtuemart = array(   'parametros'=> $parametros,
                            'vendor'    => 1,
                            'limit'     => 0,
                            'hits'      => 0,
                            'publicado' => 1,
                            'fecha'     => date("Y-m-d H:i:s"),
                            'usuario'   => '911',
                            'locked_by' => 0
                        );

switch ($pulsado) {

    case 'modificarFamiliaWeb':
         // Objetivo es modificar o añadir familia
        $respuesta = array();
        $datos=json_decode($_POST['datos'], true);
        if($datos['idFamiliaPadreWeb']=="0"){
            $comprobarPadre['datos']['idFamilia_tienda']=0;
            $padre=0;
        }else{
            $comprobarPadre=$CFamilia->obtenerRelacionFamilia_tienda($datos['idTienda'], $datos['idFamiliaPadreWeb']);
            $padre=$comprobarPadre['datos'][0]['idFamilia_tienda'];
        }
        $respuesta['padre']=$padre;
        if(!is_null($padre)){
            
            // Si existe padre continuamos.
            if($datos['idFamiliaWeb']>0){
                // Modificamos ya que no es nuevo.
                 $respuesta['caracteres']=strlen($datos['nombreFamilia']);
                  if(strlen($datos['nombreFamilia'])>180){
                      $respuesta['htmlAlerta'] = $CFamilia->montarAdvertencia('danger',
                                                    'No se puede modificar familia por que el nombre es superior a 180 caracteres.'
                                                    ,'OK');
                    }else{
                        $datosMod = array (
                            'nombre'=>$datos['nombreFamilia'],
                            'idPadre'=>$padre,
                            'id'=>$datos['idFamiliaWeb']
                        );
                        $datosMod=json_encode($datosMod);
                        $modificar=$ObjVirtuemart->modificarFamiliaWeb($datosMod);
                        $respuesta['mod']=$modificar;
                        if(isset($modificar['Datos']['error'])){
                             $respuesta['htmlAlerta'] = $CFamilia->montarAdvertencia('danger',
                                                            'Error de sql : '.$modificar['Datos']['error']
                                                            .' consulta: '.$modificar['Datos']['consulta']
                                                            ,'OK');
                           
                        }else{
                            $respuesta['htmlAlerta'] = $CFamilia->montarAdvertencia('success',
                                                            'Has modificados los datos del producto'
                                                            ,'OK');
                        }
                    }
            }else{
                $datos += $conf_virtuemart;
                $datos['alias']=str_replace(' ', '-', $datos['nombreFamilia']);
                $datos['padre']=$padre;        
                
                $datosAdd=json_encode($datos);
                $respuesta['datos']=$datos;
                $addFamilia = $ObjVirtuemart->addFamilia($datosAdd);
                $respuesta['addFamilia']=$addFamilia;
                if(isset ($addFamilia['Datos']['idFamilia'])){
                    // Hubo datos por lo que la conexion es correcta.
                    $addRegistro=$CFamilia->addFamiliaTiendaWeb($datos['idTienda'],
                                                                $datos['idFamiliaTpv'],
                                                                $addFamilia['Datos']['idFamilia']);
                    if(isset($addFamilia['Datos']['error'])){
                        $respuesta['htmlAlerta'] = $CFamilia->montarAdvertencia('danger',
                                                        'Error 1 al añadir la familia a la web. '
                                                        .$addFamilia['Datos']['error'].' Consulta: '
                                                        .$addFamilia['Datos']['consulta']
                                                        ,'OK');
                    }else{
                        $respuesta['htmlAlerta'] = $CFamilia->montarAdvertencia('success',
                                                            'Subida Familia a la web correctamente'
                                                            ,'OK');
                    }
                }else{
                    if (isset ($addFamilia['error_conexion'])){
                        $respuesta['error']=$addFamilia['info'];
                        $respuesta['htmlAlerta'] = $CFamilia->montarAdvertencia('danger',
                                                        'Error 2 al añadir la familia a la web.<br/>'
                                                        .$addFamilia['error_conexion'].'<br/>Url: '
                                                        .$addFamilia['info']['url']
                                                        ,'OK');
                    } else {
                        // Pienso que no llega nunca sin error conexion
                        // por si acaso lo marco.
                        error_log ( 'Error en tareas_virtuemart lines 113 , hubo un error sin identificar ');
                    }
                }
                  
            }
        }else{
            $respuesta['htmlAlerta'] = $CFamilia->montarAdvertencia('danger',
                                                'NO puedes añadir/modificar esta familia ya que el padre seleccionado no esta subido'
                                                ,'OK');
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
            $addFamilia = $ObjVirtuemart->addFamilia($datos);
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
