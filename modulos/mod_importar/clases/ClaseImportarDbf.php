<?php
include_once $URLCom.'/modulos/mod_importar/clases/ClaseImportar.php';

Class ImportarDbf extends Importar {
    
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
            if ($respuesta === false){
                $respuesta = parent::getFallo();
            } else {
                // Su fue correcto obtenemos array (insert_id = 0 ,affected_rows = 0), ya que se creo una tabla.
                $respuesta = 0;

            }
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
                            // Comparamos array de producto con articulo, pero hay que tene en cuenta los campos que indicamos en configuracion.
                            if ( count(array_diff($articulo,$p)) === 0 ){
                                $estado = 'igual';
                            } else {
                                if ($reg_log_dif === 'Si'){
                                    // Registramos error_log la comparacion.
                                    error_log('Producto:'.json_encode($p));
                                    error_log('Articulo tpvfox:'.json_encode($articulo));
                                    $d = array_diff($articulo,$p);
                                    error_log('Differencia.'.json_encode($d));
                                }
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


    public function htmlBtnImportarDBF($texto,$tipo){
        return '<a href="importarDBF.php" class="btn btn-'.$tipo.'">'.$texto.'</a>';

    }

}
