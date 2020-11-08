<?php
include './clases/ClaseImportarDbf.php';
$importarDbf = new ImportarDbf();
$datos_registro = $importarDbf->ultimoRegistro();
// ========= Configuracion particular =============== //
/* La configuracion deberíamos obtenerla y el usuario debría poder configurarla.*/
// Campo delete es array donde indicamos campo_nulo aquel que indica que esa linea no se añade y el valor que tienes tener para no  añadirlo
$campo_delete = array ( 'nombre_campo' => 'NULO',
                      'valor' => ''); // Valor que quieres que NO exporte a Mysql.
$campo_principal = 'CODIGO'; // El nombre del campo que debe ser unico y referencia de cruce
// Campos que no añadimos a SQL si tiene valor indicado.
$campos_sindatos = array ( 'VACIOS'    => 'None',
                        'FECHA_UL'  => 'None'
                    );
// Registrar en error_log
$registro_error = array('nulo'  => 'Si', // Los codigos nulos
                        'error' => 'Si', // Los codigos errores
                        'sql'   => 'No'  // Sql generamos que produce error
                        );
$fichero = './../ficheros_dbf/ARTICULO.dbf'; // Ruta del fichero dbf

//  ========   Fin de configuracion  ================= //
$resultado = array();
error_log('Empezo');
$instruccion = "python ./py/leerDbf1.py 2>&1 -f ".$fichero." -i 1 -e 16059";
exec($instruccion, $output,$entero);
// Recuerda que $output es un array de todas las lineas obtenidad en .py
	// tambien recuerad que si el $entero es distinto de 0 , es que hubo un error en la respuesta de  .py
	if ($entero === 0) {
		//$resultado['campos'] = $campos;
		$resultado['Estado'] = 'Correcto';
		// pasamos array asociativo.
        $i=1;// Lineas
        $nulos = 0;
        $errores = 0;
		foreach ($output as $linea) {
            $l= json_decode($linea,true);
            $l['LINEA'] = $i;
            // Analizamos los campos:
            $delete = $campo_delete['nombre_campo'];
            if ($l[$delete] !== $campo_delete['valor']){
                if ($l['NULO'] === 'True'){
                    $l['NULO'] = 1;
                } else {
                    $l['NULO'] = 0;
                }
                foreach ($campos_sindatos as $key=>$valor){
                    if ($l[$key] === $valor){
                        unset($l[$key]);
                    }
                }
                if ($l['ENVASES'] === 'True'){
                    $l['ENVASES'] = 1;
                } else {
                    $l['ENVASES'] = 0;
                }
                $r = $importarDbf->insertarDbf('ARTICULO',$l); // Obtenemos array con datos y campos.
                if (isset($r['Error'])){
                    $errores++;
                    if ($registro_error['error'] === 'Si'){
                        error_log('Error linea:'.$i.' Campo Principal:'.$l[$campo_principal]);
                    }
                    if ($registro_error['sql'] === 'Si'){
                        $sql= $importarDbf->insertarDbf('ARTICULO',$l,'Sql');
                        error_log('SQL:'.$sql);
                        error_log('Contiene la linea:'.$linea);
                    }
                }
            } else {
                $nulos++;
                if ($registro_error['nulo'] ==='Si'){
                    error_log('Nulo linea:'.$i.' Campo Principal:'.$l[$campo_principal]);
                }
            }
            $i++;
        }
        error_log('Errores:'.$errores);
        error_log('Nulos:'.$nulos);
	} else {
		$resultado['Estado'] = 'Error-obtener ';
		$resultado['Errores'] = $output;
        error_log('NO Entro entero');
		// Recuerda que esto lo mostramos gracias a que ponemos parametro 2>&1 en exec... 
		// No permitimos continuar.
		// nos imprime en pantalla (tabla) el error
	}
