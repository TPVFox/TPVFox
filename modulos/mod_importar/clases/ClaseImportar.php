<?php
include_once $URLCom.'/clases/ClaseTFModelo.php';
include_once $URLCom.'/modulos/mod_importar/clases/ClaseConfigImportar.php';

Class Importar extends TFModelo {
    public function __construct() {
        $this->configImportar = new ConfigImportar();
    }
   
    public function registroImportar($datos){
        // @objetivo
        // Registrar fichero y sesion que lo realiza.
        // @Parametros
        // $datos -> array con 
        //
        // @Devolvemos
        // id creado para que puede el usuario verlo y anotarlo por si cierra session.
        $tabla = 'modulo_importar_registro';
        parent::setTabla($tabla);
        $id = parent::insert($datos);
        if ($id === false){
            // Algo fallo
            $respuesta = $this->getFallo();
            error_log('Tipo respuesta'.gettype($respuesta).'valor:'.json_encode($respuesta));
        } else {
            $respuesta = $id;
        }
        return $respuesta;
    }
    
    public function ultimoRegistro(){
        $sql = 'SELECT * FROM `modulo_importar_registro` order by id desc limit 1';
        $respuesta = parent::consulta($sql);
        return $respuesta;
    }


    public function getAvisosHtml($id,$tipo,$parametro=''){
        //@ Objetivo
        // Obtener los mensajes maquetados bootstrap.
        //@ Parametros
        // $id          -> (int) Indice mensaje.
        // $tipo        -> (string) Donde indicamos si es: info, danger o warning
        // $parametros  -> (array) Podemos mandar los parametros que necesite el mensaje.

        // Array de mensajes
        $mensaje = array(
                        0  =>'Su fichero es válido y se subió con éxito.',
                        1  =>'Error a la hora mover el fichero.',
                        2  =>'El fichero ya existe, por lo que si no lo subiste tú , cambiale el nombre.',
                        3  =>'El directorio upload no es correcto',
                        4  =>'No subiste ficheros',
                        // Errores $_FILES ver manual php (en vez empezar 1 empezamos 5)
                        5  =>'El fichero subido excede la directiva upload_max_filesize de php.ini.',
                        6  =>'El fichero subido excede la directiva MAX_FILE_SIZE especificada en el formulario HTM',
                        7  =>'El fichero fue sólo parcialmente subido.',
                        8  =>'No se subió ningún fichero',
                        9  => 'Falta la carpeta temporal',
                        10 => 'No se pudo escribir el fichero en el disco',
                        11 => 'PHP detuvo la subida de ficheros',
                        12 => 'Se creo tabla SQL y registro correctamente el inicio del proceso, con el id:'.$parametro,
                        13 => 'No se pudo crear la tabla ,por nos dio el siguiente error:'.$parametro,
                        14 => 'Error al registrar el fichero.Error:'.$parametro,
                        15 => 'NO SE ESTA EJECUTANDO y el ultimos registro de tabla mod_importar_registro su estado es '.$parametro,
                        16 => 'Se esta ejecutando SEGUNDO PLANO, POR LO NO TERMINO PROCESAR, su estado es '.$parametro,
                        17 => 'La Tabla modulos_importar_registro no tienes registros',
                        18 => 'El tipo fichero subido, no DBF',
                        19 => 'Error al consultar tabla registro.ERROR:'.$parametro,
                        20 => 'Existe tabla modulo_importar_ARTICULOS'
                    );
        
        $html = '<div class="alert alert-'.$tipo.'">'
                .$mensaje[$id]
                .'</div>';
        return $html;
    }


    public function cambioEstado($id,$estado){
        // @ Objetivo :
        // Cambiar el estado de la tabla de importar registro
        // Los posibles estado : 'Creado','Importado' y 'Fusionado'
        parent::setTabla('modulo_importar_registro');
        $estado = 'estado ="'.$estado.'"';
        $condicion = 'id ="'.$id.'"';
        $respuesta = parent::update($estado, $condicion) ;
        return $respuesta;
    }

    public function cambioEstadoImportado($codigo_principal,$estado){
        parent::setTabla('modulo_importar_ARTICULO');
        $estado = 'estado_tpvfox="'.$estado.'"';
        $condicion = 'CODIGO ="'.$codigo_principal.'"';
        $respuesta = parent::update($estado, $condicion);
        return $respuesta;
    }

    public function actualizarArticuloTpvfox($producto,$id){
        // @Objetivo:
        // Modificamos datos de articulos de las tablas:
        //          articulos
        //          articulosPrecios
        //          articulosStocks
        // @Parametros:
        // $producto -> Los datos productos nuevos de dbf , los que vamos poner en tpvfox.
        // $id -> El id de Articulo a cambiar.
        
        parent::setTabla('articulos');
        $campos = array('articulo_name' => $producto['articulo_name']
                        ,'fecha_modificado' => date("Y-m-d H:i:s")
                        ,'estado' => 'actualizado'
                        );
        $condicion = 'idArticulo ="'.$id.'"';
        $respuesta = parent::update($campos, $condicion) ;

        if ($respuesta !== false) {
            // Fue correcta modificacion tabla anterior
            parent::setTabla('articulosPrecios');
            $campos = array('pvpCiva' => $producto['pvpCiva'],'pvpSiva' => $producto['pvpSiva']);
            $condicion = 'idArticulo ="'.$id.'" and idTienda=1';
            $respuesta = parent::update($campos, $condicion) ;
            if ($respuesta !==false){
                // Fue correcta modificacion tabla anterior
                parent::setTabla('articulosStocks');
                $campos ='stockOn = '.$producto['stockOn'];
                $condicion = 'idArticulo ="'.$id.'" and idTienda=1';
                $respuesta = parent::update($campos, $condicion) ;

            }
        }

        if ($respuesta === false){
            error_log( 'Hubo un error en metodo actualizarArticuloTpvfox');
        }
        return $respuesta;
    }

    public function anhadirNulosErrores($id,$nulos,$errores){
        // @ Objetivo:
        // Registrar en modulo_importar_registros cuantos registros fueron errores y nulos. Estos ultimos es una configuracion que se puede cambiar.
        parent::setTabla('modulo_importar_registro');
        $datos =array(  'nulos'     =>$nulos,
                        'errores'   =>$errores
                    );
        $condicion = 'id ="'.$id.'"';
        $respuesta = parent::update($datos, $condicion) ;
        return $respuesta;
    }



    public function consultaExiste($codigo){
        // @ Objetivo:
        // Obtener registros de un articulo en tpvFox con un CODIGO
        $sql =  'SELECT `idArticulo` FROM `articulosTiendas` WHERE `crefTienda`="'.$codigo.'" AND `idTienda`=1';
        $smt=parent::consulta($sql);
        return $smt;
    }

    public function insertarNuevo($producto){
        /* @ Objetivo :
         *   Añadir un producto nuevo a tpvfox con los datos que importamos.
         *   el estado del producto 'importado'
         *   El insert se tiene hacer en varias tablas (articulos,articulosTiendas,articulosCodigoBarras,articulosPrecios,articulosStocks)
         * @ Devolvemos:
         *   $respuesta = array ( idArticulo = El id articulo que acabamos de crear.
         *                        tablas_afectadas = (int) Cantidad de tablas que se añadio correctamente.
         *                        error = (string) o null , con el error si se produce.
         *                      )
         * */
        $respuesta = array();
        // Insertamos primero tabla articulos y obtenemos idArticulo Nuevo.
        // tengo que montar $datos con los campos queremos para esa tabla.
        $datos = array ( 'articulo_name'=> $producto['articulo_name'],
                         'iva'          => $producto['iva'],
                         'ultimoCoste'  => $producto['ultimoCoste'],
                         'costepromedio'=> $producto['costepromedio'],
                         'fecha_creado' => date("Y-m-d H:i:s"),
                         'tipo'         => 'unidad',
                         'beneficio'    => $producto['beneficio'],
                         'estado'       => 'importado'
                        );
        parent::setTabla('articulos');
        $id = parent::insert($datos);
        if ($id === 'false'){
            // Hubo un error al añadir el producto a articulos.
            $respuesta['error']='Al añadir codigo:'.$producto['CODIGO'].' en tabla articulos:---> ERROR:'.json_encode(parent::getFallo());
        } else  {
            $respuesta['tablas_afectadas'] = 1;
            $respuesta['idArticulo'] = $id;
            // Ahora añadimos tabla articulosCodigoBarras, pero antes montamos datos.
            $datos = array ( 'idArticulo'   => $respuesta['idArticulo'],
                             'codBarras'    => $producto['codBarras']
                            );
            parent::setTabla('articulosCodigoBarras');
            $id = parent::insert($datos);
            if ($id === false){
                // Hubo un error al añadir el producto a articulosCodigoBarras.
                $respuesta['error']='Al añadir codigo:'.$producto['CODIGO'].' en tabla articulosCodigoBarras:---> ERROR:'.json_encode(parent::getFallo());
            } else  {
                $respuesta['tablas_afectadas'] = 2;
                // Ahora añadimos tabla articulosPrecios, pero antes montamos datos.
                $datos = array ( 'idArticulo'   => $respuesta['idArticulo'],
                                 'pvpCiva'    => $producto['pvpCiva'],
                                 'pvpSiva'    => $producto['pvpSiva'],
                                 'idTienda'    => 1
                                );
                parent::setTabla('articulosPrecios');
                $id = parent::insert($datos);
                if ($id === false){
                    // Hubo un error al añadir el producto a articulos.
                    $respuesta['error']='Al añadir codigo:'.$producto['CODIGO'].' en tabla articulosPrecios:---> ERROR:'.parent::getFallo();
                } else  {
                    $respuesta['tablas_afectadas'] = 3;
                    // Ahora añadimos tabla articulosTiendas, pero antes montamos datos.
                    $datos = array ( 'idArticulo'   => $respuesta['idArticulo'],
                                     'crefTienda'    => $producto['crefTienda'],
                                     'estado'    => 'importado',
                                     'idTienda'    => 1
                                    );
                    parent::setTabla('articulosTiendas');
                    $id = parent::insert($datos);
                    if ($id === false){
                        // Hubo un error al añadir el producto a articulos.
                        $respuesta['error']='Al añadir codigo:'.$producto['CODIGO'].' en tabla articulosTiendas:---> ERROR:'.json_encode(parent::getFallo());
                    } else  {
                        $respuesta['tablas_afectadas'] = 4;
                        // Ahora añadimos tabla articulosStocks, pero antes montamos datos.
                        $datos = array ( 'idArticulo'       => $respuesta['idArticulo'],
                                         'stockOn'          => $producto['stockOn'],
                                         'idTienda'         => 1,
                                         'fecha_modificado' => date("Y-m-d H:i:s")
                                        );
                        parent::setTabla('articulosStocks');
                        //SELECT `CODIGO`,`NULO`,`estado_tpvfox`,`STOCK`,CODE_BAR FROM `modulo_importar_ARTICULO` WHERE `estado_tpvfox`!='igual' and `estado_tpvfox`!='actualizado' 
                       
                        $id = parent::insert($datos);
                        if ($id === false){
                            // Hubo un error al añadir el producto a articulos.
                            $respuesta['error']='Al añadir Stock a idArticulo:'.$respuesta['idArticulo'].' del crefTienda:'.$producto['crefTienda'].' ---> ERROR:'.json_encode(parent::getFallo());
                        } else  {
                            $respuesta['tablas_afectadas'] = 5;
                        }
                    }
                }
            }
        }
        return $respuesta;      
    }

    public function ComprobarFiltroRegistro($registro,$accion){
        // @ Objetivo:
        // Comprobar si ese regitros se tiene filtrar o no.
        // @ Respuesta:
        //  $respuesta = (string) con 'filtrado' o vacio '';
        $conf = $this->configImportar;
        $respuesta = '';
        $filtros = $conf->filtros;
        $valor = $filtros['fusionar'][$accion]['valor'];
        if ( $valor !==''){
		$campo = $filtros['fusionar'][$accion]['nombre_campo'];
			if ($registro[$campo]=== $valor){
				$respuesta = 'filtrado'; // Si entra aqui, este registro ya no vamos comprobar nada.
			}
		}
        return $respuesta;
    }

    public function contarRegistrosPorEstado(){
        // @ Objetivo:
        // Contar la cantida de registros en modulo_importar_tabla de cada estado.
        // @ Respuesta :
        //    Array con los estados que hay y cuantos.
        parent::setTabla('modulo_importar_ARTICULO');
        $estados = array( 'error'       => 0,
                          'nuevo'       => 0,
                          'actualizado' => 0,
                          'igual'       => 0,
                          'filtrado'    => 0 
                        );
        foreach ( $estados as $estado => $valor){
            $sql =  'SELECT count(*) as sum FROM `modulo_importar_ARTICULO` WHERE `estado_tpvfox`="'.$estado.'"';
            $smt=parent::consulta($sql);
            $estados[$estado] = $smt['datos'][0]['sum'];
        }
        // Ahora buscamos nulos o que no se ninguno de los los anteriores.
        $sql =  'SELECT count(*) as sum FROM `modulo_importar_ARTICULO` WHERE `estado_tpvfox` is NULL';
        $smt=parent::consulta($sql);
        $estados['NULL'] = $smt['datos'][0]['sum'];

        return $estados;

    }

    public function EliminarTabla(){

        $sql = 'DROP TABLE modulo_importar_ARTICULO';
        $respuesta = parent::consultaDML($sql);
        if ($respuesta === false){
            $respuesta = parent::getFallo();
        } else {
            // Su fue correcto obtenemos array (insert_id = 0 ,affected_rows = 0), ya que se creo una tabla.
            $respuesta = 0;

        }
        return $respuesta;
    }


    public function comprobarSiEjecutaSegundoplano(){
        // @ Objetivo:
        // Compruebo si se esta ejecutando segundo_plano.php
        // @ Repuesta:
        // Numero de procesos que hay abiertos con ese nombre.
        
        $command = 'ps aux | grep "[p]hp.*segundo_plano"';
        exec($command ,$ejecutando); 
        return count($ejecutando);

    }

    public function comprobarSiExisteFichero($fichero){
        // @ Objetivo:
        // Compruebo si se esta ejecutando segundo_plano.php
        // @ Repuesta:
        // Numero de procesos que hay abiertos con ese nombre.
        

    }

    public function EliminarRegistroTabla($id){
        $sql = 'DELETE FROM `modulo_importar_registro` WHERE `id`='.$id;
        $respuesta = parent::consultaDML($sql);
        if ($respuesta === false){
            $respuesta = parent::getFallo();
        } else {
            // Su fue correcto obtenemos array (insert_id = 0 ,affected_rows = 0), ya que se creo una tabla.
            $respuesta = $repuesta['affected_rows'];

        }
        return $respuesta;

    }

    public function nombreFicheroRegistro($ruta_segura){
        // @ Objetivo:
        // Crear fichero con la ruta completa para registrar log.
        $dregistro = $this->ultimoRegistro();
        $datos_registro =$dregistro['datos'][0];
        $c = strlen($datos_registro['name'])-4;
        $e = array(":", "-", " ");
        $dtime = str_replace($e,"",$datos_registro['fecha_inicio']);
        $fichero_registro = $ruta_segura.'/'.mb_strcut($datos_registro['name'],0,$c).'_'.$dtime.'.log';
        return $fichero_registro;
    }

    public function htmlBtnEliminarUltimoRegistro($id){
        $mClick = "metodoClick('EliminarUltimoRegistro','".$id."')";
        $btn_eliminar_registro ='<button class="btn btn-danger" onclick="'.$mClick.'">Borrar ultimo registro</button>';
        return $btn_eliminar_registro;
    }

}
