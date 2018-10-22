<?php 

$pulsado = $_POST['pulsado'];

include_once ("./../../../configuracion.php");

// Crealizamos conexion a la BD Datos
include_once ($RutaServidor.$HostNombre. "/clases/ClaseSession.php");
include_once $RutaServidor.$HostNombre.'/modulos/mod_producto/clases/ClaseProductos.php';


	// Solo creamos objeto si no existe.
	$thisTpv = new ClaseSession();
	$BDTpv = $thisTpv->getConexion();
    $CTArticulos = new ClaseProductos($BDTpv);
    include_once ($RutaServidor.$HostNombre."/plugins/mod_producto/virtuemart/ClaseVirtuemart.php");
    $ObjViruemart = new PluginClaseVirtuemart();

	switch ($pulsado) {
        case 'modificarDatosWeb':
            // @ Objetivo:
            // Modificar o añadir un producto en la web.
            // ¡¡¡ OJO !!! Hago break para salir del case cuando dectetoun posible error.
            $datos = $_POST['datos'];
            
			$respuesta = array();
            $respuesta['datos']=$datos; // No devolvemos datos para debug.
            $datosComprobaciones=json_decode($datos, true);
            if(strlen($datosComprobaciones['nombre'])>180){
                $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                            <strong>Danger!</strong> No se puede modificar el producto 
                                            por que el nombre es superior a 180 caracteres.
                                        </div>';
                // No continuamos, ya que esta mal...
                break;
            }
            // -------            CONTROL DE IVA                      -------- //
            // Obtenemos los datos del producto para obtener el iva que tiene.
            $control_iva = 'OK';
            $datosProductoTpv = $CTArticulos->GetProducto($datosComprobaciones['idProducto']);
            
            // Obtenemos los datos del producto de la web si los ivas de la web:
            $d=$ObjViruemart->ObtenerDatosDeProducto($datosComprobaciones['id']);
            $ivas_web = array();
            if (isset( $d['Datos']['ivasWeb']['items'])){
                // Obtenemos todos los ivas de la web si tiene ivas...
                $ivas_web = $d['Datos']['ivasWeb']['items'];
                foreach ($ivas_web as $ivaWeb){
                    if ( $ivaWeb['virtuemart_calc_id'] ==$datosComprobaciones['iva']){
                        $iva_web = number_format($ivaWeb['calc_value'],2);
                    }
                }
                // Comparamos si el iva del producto tpv es el mismo que se va poner en la web
                if (number_format($datosProductoTpv['iva'],2) !== $iva_web){
                    $control_iva = 'KO';
                }
            }
            if ($control_iva == 'KO') {
                 $respuesta['error']=' Error Iva no coinciden.';
                 $respuesta['htmlAlerta']='<div class="alert alert-danger">
                           <strong>Danger!</strong> Error el iva que intentas subir no coincide con el del producto.
                                            </div>';
                // No continuamos... no se graba nada...
                break;
            }
            // -------             FIN DE CONTROL IVA                 -------- //


            // Ahora comprobamos que exista el iva del producto en la web
            
            // Ahora ponemos valor variable estado -> string
            if($datosComprobaciones['estado']==1){
               $estado="Sin Publicar";
            }else{
               $estado="Publicado";
            }
            if($datosComprobaciones['id']>0){
                // ----   MODIFICAMOS PRODUCTO EN LA WEB  ---- //
                // Estamos modificando, modificamos los datos..
                $modificarProducto = $ObjViruemart->modificarProducto($datos);
                if ( isset($modificarProducto['Datos']['error'])>0){
                    // Comprobamos si existe erro y no continuamos.
                    if(strlen($modificarProducto['Datos']['error']) <> 0){
                        $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                    <strong>Danger!</strong> Error de sql : '.$modificarProducto['Datos']['error'].' consulta: '.$modificarProducto['Datos']['consulta'].'
                                    </div>';
                        // No continuamos, ya que esta mal...
                        break;
                    }
                }
                
                
                $modificarArticulosTienda=$CTArticulos->modificarEstadoWeb($datosComprobaciones['idProducto'], $datosComprobaciones['idTiendaWeb'], $estado);
                $respuesta['resul']= $modificarProducto;
                $respuesta['htmlAlerta']='<div class="alert alert-success">
                                         <strong>Success!</strong> Has modificados los datos del producto.
                                         </div>';
                
            }else{
                // ---    AÑADIMOS PRODUCTO EN LA WEB   --- //
                //
                // Montamos los datos que son fijos.
                // Algunos de los datos que ponemos no tendrían que ser fijo, deberían ser configuracion o obtenerlos
                // de virtuemart.
                $datosComprobaciones['usuario']=365;
                $datosComprobaciones['peso']='KG';
                $datosComprobaciones['parametros']='min_order_level=""|max_order_level=""|step_order_level=""|product_box=""|';
                $datosComprobaciones['s_desc']="";
                $datosComprobaciones['desc']="";
                $datosComprobaciones['metadesc']="";
                $datosComprobaciones['metakey']="";
                $datosComprobaciones['title']="";
                $datosComprobaciones['vendor']=1;
                $datosComprobaciones['override']=0;
                $datosComprobaciones['product_override_price']="0.00000";
                $datosComprobaciones['product_discount_id']=0;
                $datosComprobaciones['product_currency']=47;
                // Buscamos la relacion de la familia web de las familias del producto.
                $familiasProducto=$CTArticulos->buscarFamiliasProducto($datosComprobaciones['idProducto'],$datosComprobaciones['idTiendaWeb']);
                if (isset($familiasProducto['error'])){
                    $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                <strong>Danger!</strong> Hubo un error al obtener la relacion de la familia en la web de este producto.</div>';
                    // No continuamos, ya que esta mal...
                    break;
                }
                $respuesta['familiaProducto']=$familiasProducto;
                $datosComprobaciones['familias']=$familiasProducto;
                $datos=json_encode($datosComprobaciones);
                $addProducto = $ObjViruemart->addProducto($datos);
                if($addProducto['Datos']['idArticulo']>0){
                    $addRegistro=$CTArticulos->addTiendaProducto( $datosComprobaciones['idProducto'], $datosComprobaciones['idTiendaWeb'], $addProducto['Datos']['idArticulo'], $estado);
                   
                    $respuesta['registro']=$addRegistro;
                    $respuesta['htmlAlerta']='<div class="alert alert-success">
                                                <strong>Success!</strong> Has añadido el producto a la web 
                                                </div>';
                                                
                }else{
                    $respuesta['error']=$addProducto['Datos']['error'];
                    $respuesta['htmlAlerta']='<div class="alert alert-danger">
                                                <strong>Danger!</strong> Error al añadir el producto a la web. '.$addProducto['Datos']['error'].' Consulta: '.$addProducto['Datos']['consulta'].'
                                            </div>';
                }
                $respuesta['resul']= $addProducto;
            }
           
           
        break;
        case 'mostrarModalNotificacion':
        //@Objetivo: montar el modal de la notificación de clientes
            $datos=$_POST['datos'];
            $html='<div class="col-md-12">'
                .'<h4>Enviar correo a :'.$datos['nombreUsuario'].'</h4>
                <div class="col-md-12">
                    Id del producto: <p id="idProducto">'.$datos['id'].'</p>
                    <input type="text" id="idNotificacion" value="'.$datos['idNotificacion'].'" style="display:none">'
                 .   '<input type="text" id="numLinea"  style="display:none" value="'.$datos['numLinea'].'">
                </div>
                '
                .'<div class="col-md-12">
                    <label>Email</label>'
                .'<input type="text" id="email" name="email" value="'.$datos['correo'].'" size="60">'
                .'</div></div>
                
                <div class="col-md-12">'
                .'<div class="col-md-12">
                    <label>Asunto</label>'
                .'<input type="text" id="asunto" name="asuno" size="60" value="Contestacion a pregunta sobre '.$datos['nombreProducto'].'">'
                .'</div>'
                .'</div>'
                .'<div class="col-md-12">'
                .'<div class="col-md-10">'
                .'<label>Mensaje:</label>'
                .'<textarea id="mensaje" cols="60" name="mensaje"></textarea>'
                .'</div>'
                .'</div>
                <button type="button" class="btn btn-success" onclick="enviarCorreoNotificacion()">Enviar Correo</button>';
            $respuesta['html']=$html;
            break;
            
        case 'enviarCorreoNotificacion':
            //Objetivo: enviar el correo de notificación
            //Primero cargamos la libreria
            $respuesta = array();
            $datos=$_POST['datos'];
            $enviarCorreo = $ObjViruemart->enviarCorreo($_POST['datos']);
            if($enviarCorreo['Datos']['mailer']==true){
                $respuesta['mail']= 2;
                $modificarEstadoNotificacion = $ObjViruemart->modificarNotificacion($datos['idProducto'], $datos['email']);
                if(isset ($modificarEstadoNotificacion['Datos']['error'])){
                    $respuesta['errorModificacion']=$modificarEstadoNotificacion['Datos']['error'];
                }
                $respuesta['modificacion']=$modificarEstadoNotificacion;
                $respuesta['numLinea']=$datos['numLinea'];
            }else{
                $respuesta['mail']= 1;
                $respuesta['error']=$enviarCorreo['Datos']['mensaje'];
            }
            $respuesta['correo']= $enviarCorreo;
            break;
            
        case 'subirProductosWeb':
            //@ Objetivo:
            // Subir los productos seleccinados a la web 
            $productosSeleccionados=$_SESSION['productos_seleccionados'];
            $tiendaWeb=$_POST['idTiendaWeb'];
            $respuesta = array();
            $productoEnWeb=array();
            $productosError=array();
            $contadorProductos=0;
            // Obtenemos los ivas de la web , si no hay mostramos error
            $datosProductoVirtual=$ObjViruemart->ObtenerDatosDeProducto(0);
            $ivas_web = array();
            
            if (isset( $datosProductoVirtual['Datos']['ivasWeb']['items'])){
                $ivas_web = $datosProductoVirtual['Datos']['ivasWeb']['items'];
            }
            $error = $ObjViruemart->error_string($datosProductoVirtual);
            if ($error <>''){
                // Hubo error al obtener los ivas.
                $respuesta['errores'] = array('tipo'    => 'danger',
                                            'mensaje'   => 'No se pudo obtener ivas, o fallo conexion',
                                            'dato'      => $error
                                        );
                // podemos continuar
                break;
            }
            foreach ($productosSeleccionados as $producto){
                $idVirtuemart=0;
                // Obtenemos los dato del un producto seleccionado.
                $datosProducto = $CTArticulos->GetProducto($producto);
  
                if(count($datosProducto['ref_tiendas'])>0){
                    // Si tiene relaciones de tienda  tenemos que comproba que no exista
                    // relacion con la tiendaWeb.
                    foreach ($datosProducto['ref_tiendas'] as $refTienda){
                        // Ahora comprobamos si tiene datos de la tienda Web producto.
                        if ($refTienda['idTienda'] == $tiendaWeb){
                            $idVirtuemart = $refTienda['idVirtuemart'];
                        }
                    }
                    if($idVirtuemart==0){
                        
                        // Este producto no tiene relacion con tienda Web , por lo que es nuevo para Web
                        $estado = 'Publicado' ; // Al ser nuevo le pongo ese estado.
                        $stockMin=number_format($datosProducto['stocks']['stockMin'], 0, '.', '');
                        $stockReal=number_format($datosProducto['stocks']['stockOn'], 0, '.', '');
                        $stockWeb=$stockReal-$stockMin;
                        // Ahora comprobamos que el iva que tiene el producto existe en la web.
                        // Comprobamos si existen los ivas de los productos en la web.
                        $error_iva = 'KO';
                        
                        foreach ($ivas_web as $ivaWeb){
                            if (number_format($ivaWeb['calc_value'],2) == number_format($datosProducto['iva'],2)){
                                //~ error_log('iva web = '.number_format($ivaWeb['calc_value'],2). ' tipo:'.gettype(number_format($ivaWeb['calc_value'],2)));
                                //~ error_log('iva web = '.number_format($datosProducto['iva'],2). ' tipo:'.gettype(number_format($datosProducto['iva'],2)));
                                // Existe el iva del producto en la web.
                                $error_iva = 'OK';
                            }
                        }
                        if ($error_iva == 'KO'){
                               // Si iva del producto no existe en la web no continuamos.
                                $respuesta['errores'][] = array('tipo'    => 'danger',
                                                    'mensaje'   => 'No existe el iva ('.$datosProducto['iva'].') del producto ('. $datosProducto['idArticulo'].')No se pudo obtener ivas, o fallo conexion',
                                                    'dato'      => $datosProducto
                                                );
                                array_push($productosError, $datosProducto['idArticulo']);
                        }
                        if ($error_iva == 'OK'){
                    
                            // Ahora debería buscar los idFamilias de la Web de las familias de este producto.
                            
                            $familiasProducto=$CTArticulos->buscarFamiliasProducto($datosProducto['idArticulo'], $tiendaWeb);
                            if (isset($familiasProducto['error'])){
                                // Si hay error entonces, tenemos que ir al siguiente producto.
                                // Tambien debemos indicar que ese producto no se subio.
                                
                                $respuesta['error']='Hubo un error al obtener la relacion de la familia en la web de este producto';
                                $respuesta['errores'][] = array('tipo'    => 'warning',
                                                    'mensaje'   => 'Hubo error al obtener la relacion de la familia web con este producto('. $datosProducto['idArticulo'].')',
                                                    'dato'      => $datosProducto
                                                );
                                array_push($productosError, $datosProducto['idArticulo']);
                            } else {
                                // No hubo error continuamos ..
                                $respuesta['familiaProducto']=$familiasProducto;
                                $datos=array(
                                    'estado'=> 1,
                                    'referencia'=> $datosProducto['cref_tienda_principal'],
                                    'nombre'=> $datosProducto['articulo_name'],
                                    'codBarras'=> "",
                                    'precioSiva'=>number_format($datosProducto['pvpSiva'],2, '.', ''),
                                    'iva'=> $datosProducto['iva'],
                                    'id'=> $idVirtuemart,
                                    'alias'=>"",
                                    'idProducto'=>$datosProducto['idArticulo'],
                                    'idTienda'=>$tiendaWeb,
                                    'usuario'=>365,
                                    'peso'=>'KG',
                                    'stock'=>$stockWeb,
                                    'parametros'=>'min_order_level=""|max_order_level=""|step_order_level=""|product_box=""|',
                                    's_desc'=>"",
                                    'desc'=>"",
                                    'metadesc'=>"",
                                    'metakey'=>"",
                                    'title'=>"",
                                    'vendor'=>1,
                                    'override'=>0,
                                    'product_override_price'=>"0.00000",
                                    'product_discount_id'=>0,
                                    'product_currency'=>47,
                                    'familias' => $familiasProducto
                                );
                                $datos=json_encode($datos);
                                $addProducto = $ObjViruemart->addProducto($datos);


                                if( isset ($addProducto['Datos']['idArticulo'])){
                                    // Hubo respuesta.
                                    if($addProducto['Datos']['idArticulo']>0){
                                    // Inserto correctamente.
                                        $addRegistro=$CTArticulos->addTiendaProducto( $producto, $tiendaWeb, $addProducto['Datos']['idArticulo'],$estado);
                                        
                                        if ($addRegistro['NAfectados'] == 1){
                                            // Cambio correctamente en articulosTienda la referencia
                                            $respuesta['registro']=$addRegistro;
                                            $contadorProductos=$contadorProductos+$addRegistro['NAfectados'];
                                        } else {
                                            $respuesta['error']='Hubo un error inserta la referencia en articulosTienda';
                                            $respuesta['errores'][] = array('tipo'    => 'warning',
                                                    'mensaje'   => 'Hubo error al obtener la relacion de la familia web con este producto('. $datosProducto['idArticulo'].')',
                                                    'dato'      => $addRegistro
                                                );
                                        }
                                    }
                                }

                                if( isset($addProducto['Datos']['error'])){
                                    // Hubo un error en la consulta o conexion al insertar
                                    $respuesta['errores'][]= array( 'tipo' => 'warning',
                                                                    'mensaje' =>' Error al insertar producto ('
                                                                    .$datosProducto[idArticulo].')',
                                                                    'dato' =>$addProducto['Datos']['consulta']
                                                                    );
                                    array_push($productosError, $datosProducto['idArticulo']);
                                }
                            }
                        
                        }else{
                            // Existe ya relacion de este producto en la Web.
                            // Añadimos a array de productoEnWeb
                            $datos=array(
                                'id'=>$datosProducto['idArticulo'],
                                'nombre'=>$datosProducto['articulo_name']
                            );
                            array_push($productoEnWeb, $datos);
                        }
                    }
                }
           
            } // Fin de foreach
            $respuesta['contadorProductos']=$contadorProductos;
            $respuesta['productosError']=$productosError;
            $respuesta['productoEnWeb']=$productoEnWeb;
            $respuesta['productos']=$productosSeleccionados;
        break;
        
        case 'contarProductosWeb':
            $respuesta=$ObjViruemart->contarProductos();
        break;
        
        case 'actualizarProductosWeb':
            $inicio=$_POST['inicio'];
            $final=$_POST['final'];
            
            $productos=$ObjViruemart->productosInicioFinal($inicio, $final);
            $respuesta['productos']=$productos;
            
        break;
       
        case 'RestarStock':
            $productos=$ObjViruemart->modificarStock($_POST['productos']);
            $respuesta['productos']=$productos;
        break;
    
    
    }
    echo json_encode($respuesta);
?>
