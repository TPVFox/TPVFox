<?php
include_once ($RutaServidor.$HostNombre.'/clases/ClaseTFModelo.php');
Class ImportarDbf extends TFModelo {

   
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
        return $id;
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
                            $tipo = 'varchar('.$campo->longitud.')';
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
            $this->campos =$respuesta['datos'];
            $strSql = implode(",",$strCampos);
            $sql = 'CREATE TABLE modulo_importar_'.$nombreTabla.' ('.$strSql.')';
            $resultado = parent::consultaDML($sql);
        }
        return $resultado;
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

    public function getAvisosHtml($id,$tipo,$parametro=array()){
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
                        12 => 'Se creo tabla SQL y registro correctamente el inicio del proceso, con el id:'.$parametro[0],
                        13 => 'No se pudo crear la tabla ,por nos dio el siguiente error:'.$parametro[0]
                   
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
        $id = parent::insert($tabla, $datos,$soloSQL);
        return $id;
    }

}
