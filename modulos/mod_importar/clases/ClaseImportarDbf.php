<?php
include_once $URLCom.'/clases/ClaseTFModelo.php';
include_once $URLCom.'/modulos/mod_importar/clases/ClaseConfigImportar.php';

Class ImportarDbf extends TFModelo {
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

    public function crearEstructura($fichero,$URLCom){
        // @ Objetivo
        // Es obtener numero registros, campos y crear estructura Sql de la tabla dbf
        $respuesta = $this->obtenerEstructuraDbf($fichero,$URLCom);
        $nombreTabla =basename($fichero, ".dbf");
        if ($respuesta['error']===0){
            // No hubo errores.
            $strCampos = array( 0 =>'LINEA varchar(8)'
                        );
            
            $i = 1;
            $resultado = array();
            // Ahora obtenemos los numero registros que tiene el fichero
            $this->Num_registros = $respuesta['datos'][0];
            foreach ($respuesta['datos'] as $campo){
                $campo = json_decode($campo);
                if (isset($campo->campo)){
                    $tipo = '';
                    switch ($campo->tipo){
                        case 'C':
                            $tipo = 'varchar('.$campo->longitud.') COLLATE utf8_general_ci';
                            break;
                        case 'N':
                            $tipo = 'decimal('.$campo->longitud.','.$campo->decimal.')';
                            break;
                        case 'D':
                            $tipo = 'date';
                            break;
                        case 'L':
                            $tipo = 'tinyint(1)';
                            break;
                    }
                    $strCampos[$i] = $campo->campo.' '.$tipo;
                    $i++;
                } 
            }
            // Ahora añadimos el campo id_Tpvfox para poder luego fusionarlos.
            $strCampos[] = 'id_tpvfox int(11)';
            $strCampos[] = 'estado_tpvfox varchar(11)';

            $this->campos =$respuesta['datos'];
            $strSql = implode(",",$strCampos);
            $sql = 'CREATE TABLE modulo_importar_'.$nombreTabla.' ('.$strSql.')';
            $respuesta = parent::consultaDML($sql);
        }
        return $respuesta;
    }

    public function obtenerEstructuraDbf($fichero,$URLCom){
        // @ Objetivo
        // Obtener campos que tiene el fichero dbf
        // @ Parametro
        // $fichero = Ruta y nombre del fichero dbf
        // @ Devuelve.
        // $error = 1 si es un error, 0 si fue correcto.
        // $datos 0 $errores
       	$instruccion = 'python '.$URLCom.'/lib/py/leerEstrucDbf2.py 2>&1 -f '.$fichero;
        $resultado = array();
        $output = array(); 


        // Recuerda que esto lo mostramos gracias a que ponemos parametro 2>&1 en exec... 
        // No permitimos continuar.
        exec($instruccion, $output, $entero);
        $resultado['error'] = $entero;
        if ($entero >0 ){
            $resultado['errores'] = $output;
        } else {
            $resultado['datos'] = $output;

        }
        return $resultado;

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
                        14 => 'Error al registrar el fichero.Error:'.$parametro
                    );
        
        $html = '<div class="alert alert-'.$tipo.'">'
                .$mensaje[$id]
                .'</div>';
        return $html;
    }

    public function LeerDbf($fichero,$URLCom) {
        // Parametros:
        // El objetivo es leer DBF
        // Metodo:
        // A traves exec , obtenemos array.
        // tratamos array $output para obtener los datos y los ponemos a nuestro gusto $resultado;
        $resultado = array();
        $output = array(); 
        $instruccion = 'python '.$URLCom.'/lib/py/leerDbf1.py 2>&1 -f '.$fichero;
        exec($instruccion, $output,$entero);
        // Recuerda que $output es un array de todas las lineas obtenidad en .py
        // tambien recuerad que si el $entero es distinto de 0 , es que hubo un error en la respuesta de  .py
        if ($entero === 0) {
            //$resultado['campos'] = $campos;
            $resultado['Estado'] = 'Correcto';
            // pasamos array asociativo.
            $i = 0;
            foreach ($output as $linea) {
                $resultado[$i] = json_decode($linea,true); // Obtenemos array con datos y campos.
                $i++;
            }
        } else {
            $resultado['Estado'] = 'Error-obtener ';
            $resultado['Errores'] = $output;
            // Recuerda que esto lo mostramos gracias a que ponemos parametro 2>&1 en exec... 
            // No permitimos continuar.
            // nos imprime en pantalla (tabla) el error
        }
        return $resultado;
    }

    public function insertarDbf($tabla,$datos,$soloSQL = false){
        parent::setTabla($tabla);
        $id = parent::insert($datos,$soloSQL);
        return $id;
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

    public function leerTodos_mod_articulo() {
        // @ Objetivo:
        // Obtener todo los registros con un limite y desde.
        $tabla ='modulo_importar_ARTICULO';
        // La primera condicion es filtrar los registros que no se procesaron, ya que este proceso puede ser recurrente.
        $condiciones ='estado_tpvfox IS NULL';
        $columnas = array('CODIGO','NOMBRE','STOCK','CODE_BAR','PCOSTE','PVENTA','PVP','BENEFICIO','IVA','NULO');
        // Creo defecto para variables que necesita metodo _leer
        $limit = 0;
        $offset = 0;
        $join = array();
        $columnasSql = count($columnas) > 0 ? implode(',', $columnas): '*';
        return parent::_leer($tabla, $condiciones, $columnas, 
                $join, $limit, $offset);
    }

    public function ControllerNewUpdate($producto){
        //@Objetivo:
        // Es el controlador para :
        // Consultamos si existe (consultaExiste) y
        //          - Si no existe, lo creamos.
        //          - Si existe, comprobamos si son los datos iguales o no.
        // Una vez se comprueba si Nuevo,Actualizado o esta igual, se crea o cambia en tablas tpvfox, luego se registra estado en tabla_modulo.
        //@Parametros:
        //  $producto : Array con los campos necesarios para crear o actualizar un articulo en tpvfox campos:
        //              'CODIGO','NOMBRE','STOCK','CODE_BAR','PCOSTE','PVENTA','PVP','BENEFICIO','IVA'
        $conf = $this->configImportar;
        $estado ='';
        // Los posibles estado del registro mod_articulo son (error,nuevo,actualizado,igual,filtrado,null)
        // Obtenemos idArticulo si existe con ese Codigo.
        $A = $this->consultaExiste($producto['CODIGO']);
        $p = array ('articulo_name' => trim($producto['NOMBRE']),
                    'pvpCiva'       => number_format($producto['PVP'],2),
                    'pvpSiva'       => number_format($producto['PVENTA'],2),// para comparar...
                    'stockOn'       => $producto['STOCK'],
                    'crefTienda'    => $producto['CODIGO'],
                    'codBarras'     => $producto['CODE_BAR'],
                    'iva'           => $producto['IVA'],
                    'beneficio'     => $producto['BENEFICIO'],
                    'ultimoCoste'   => $producto['PCOSTE'],
                    'costepromedio' => $producto['PCOSTE'],
                    'tipo'          => 'unidad'                    
                    );
        
        if (isset($A['datos'])){
            // Comprobamos si tenesmo que actualizar o filtramos solo.
            $filtrar = $this->ComprobarFiltroRegistro($producto,'actualizar');
            if ($filtrar === ''){
                // No se filtra... continuamos.
                if (count($A['datos']) === 1){
                    // Encontro solo un producto con ese CODIGO
                    $estado = 'error';
                    // Obtenemos idArticulo para poder comprobar si cambio algo.
                    $idArticulo = $A['datos'][0]['idArticulo'];
                    $p['idArticulo'] =  $idArticulo;
                    // Obtenemos datos de Articulo de tpvfox:
                    $Sql = 'SELECT'
                    .' a.idArticulo,a.articulo_name, prec.pvpCiva, pvpSiva,  s.stockOn '
                    .' FROM articulos as a '
                    .'  LEFT JOIN articulosPrecios as prec ON a.idArticulo= prec.idArticulo '
                    .' LEFT JOIN articulosStocks AS s ON a.idArticulo = s.idArticulo '
                    .'  WHERE a.idArticulo ='.$idArticulo.' AND '
                    .'  prec.idArticulo='.$idArticulo.' AND prec.idTienda=1 ';
                    $consulta = parent::consulta($Sql);
                    if (isset($consulta['datos'])){
                        // Fue correcta la consulta y montamos el array de Articulo
                        if ( count($consulta['datos']) === 1){
                            $estado = 'actualizado';
                            $articulo = $consulta['datos'][0];
                            // Ahora cambio formato de numero para coincida.
                            $articulo['pvpCiva'] = number_format($articulo['pvpCiva'],2);
                            $articulo['pvpSiva'] = number_format($articulo['pvpSiva'],2); // para comparar...
                            $articulo['stockOn'] = number_format($articulo['stockOn'],3);
                            $r = $conf->reg_log;
                            $reg_log_dif = $r['fusionar']['diferencia_array'];
                            if ($reg_log_dif === 'Si'){
                                // Registramos error_log la comparacion.
                                error_log('Producto:'.json_encode($p));
                                error_log('Articulo tpvfox:'.json_encode($articulo));
                            }
                            // Comparamos array de producto con articulo,
                            if ( count(array_diff($articulo,$p)) === 0 ){
                                $estado = 'igual';
                            } else {
                                // Hay que hacer update ya que no estan iguales: Actualizado
                                $p['pvpSiva'] = number_format($producto['PVENTA'],4); // Para meter costes en milesima centimos.
                                $respuesta = $this->actualizarArticuloTpvfox($p,$idArticulo);
                                if ($respuesta ===false){
                                    // Algo fallo a la hora update.
                                    error_log('Error en actualizacion:'.json_encode(parent::getFallo()));
                                    $estado = 'error'; // Ya que lo había cambiado antes.
                                }
                            }
                        } else {
                            error_log('============ Se obtuvo mas de un producto con ese codigo en tpvfox ');
                            // Aquí realmente no es un error de consulta por lo que metodo getFallo no funcionaria.. por lo auq e
                        }
                    } else {
                        error_log('=================Error en consulta :'.$consulta['datos']);
                    }
                } else {
                    // Quiere decir que hay mas de un producto o 0 con ese CODIGO,  marcamos error.
                    error_log( ' El CODIGO:'.$producto['CODIGO'].' se encontraron '.count($A['datos']).' no voy comprobar nada, lo marco estado ERROR');
                }
            } else {
                $estado = 'filtrado';
            }
        } else {
            // No existe articulos con ese CODIGO, por lo que es NUEVO.
            $filtrar = $this->ComprobarFiltroRegistro($producto,'crear');
            if ($filtrar === ''){
                $estado = 'nuevo';
                // Ahora añadimos a $p los campos que necesitamos para cuando es nuevo.
                $p['fecha_creado']  = date("Y-m-d H:i:s");
                $p['pvpSiva'] = number_format($producto['PVENTA'],4); // Para meter costes en milesima centimos.

                // Ahora comprobamos datos necesarios para permitir crear uno NUEVO.
                $error = array();
                if (trim($p['articulo_name']) ===''){
                    $error[] = 'No tiene datos en articulo_name';
                }
                if (abs($p['pvpSiva']) == 0){
                    $error[] = 'No tiene precio sin iva';
                }
                if (abs($p['pvpCiva']) == 0){
                    $error[] = 'No tiene precio con iva';
                }
                // Faltaría comprobar el iva... tambien....
                if (count($error) === 0){
                    $iN = $this->insertarNuevo($p);
                    if (isset($iN['error'])){
                        // Hubo un error al insertar.
                        error_log('Error al insertar:'.$p['crefTienda'].'--->'.json_encode(parent::getFallo()));
                    }
                } else {
                    // Hubo algun error, por lo que no creamos y ponemos como error.
                    $estado = 'error';
                    error_log('Hubo un error al comprobar un producto nuevo con el CODIGO: '.$p['crefTienda'].'       ERRORES:'. implode(',',$error));
                }
            } else {
                $estado = 'filtrado';
            }
        }
        return $estado;
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
        // @Objetivo:
        // Comprobar si ese regitros se tiene filtrar o no.
        // @Respuesta:
        //  $respuesta = (string) con 'filtrado' o vacio '';
        $conf = $this->configImportar;
        $respuesta = '';
        $filtros = $conf->filtros;
        if ($filtros['fusionar']['valor'] !==''){
            $f_fusionar = $filtros['fusionar'];
            if ($f_fusionar['accion'] === $accion){
                $campo = $f_fusionar['nombre_campo'];
                if ($registro[$campo]=== $f_fusionar['valor']){
                    $respuesta = 'filtrado'; // Si entra aqui, este registro ya no vamos comprobar nada.
                }
            }
        }
        return $respuesta;




    }
    

}
