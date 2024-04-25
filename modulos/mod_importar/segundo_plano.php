<?php
include_once './../../inicial.php';
include_once './clases/ClaseImportarDbf.php';
$ruta_segura = $thisTpv->getRutaSegura();
$importarDbf = new ImportarDbf($ruta_segura);
// Obtenemos los datos del ultimo registro de Tabla importacion, para saber nombre fichero y registros originales.
$dregistro = $importarDbf->ultimoRegistro();
$datos_registro =$dregistro['datos'][0];
// Montamos nombre del fichero subir.
$dir_subida = $thisTpv->getRutaUpload();
$fichero = $dir_subida.'/'.$datos_registro['name'];

// Montamos nombre fichero_registro.

$fichero_registro = $importarDbf->nombreFicheroRegistro($ruta_segura);

//En filtros sin valor tanto en importar como fusionar no filtra nada.
$configImportar = $importarDbf->configImportar;
$filtros = $configImportar->filtros;
$campo_principal = $configImportar->campo_principal;

// Campos que no añadimos a SQL si tiene valor indicado.
$campos_sindatos = $configImportar->campos_sindatos;

// Obtener configuracion de registro de error_log
$reg_log = $configImportar->reg_log;


//  =======   Inicio  importacion    ================= //
// Compruebo si el estado del ultimo registro esta Creado , sino no hacemos nada...
error_log('fichero de registro'.$fichero_registro);
error_log('====== Empezo a importar '.date("Y-m-d H:i:s").' ======= '." \n\r",3,$fichero_registro);
error_log('Estado actual:'.$datos_registro['estado']."\n\r",3,$fichero_registro);


if ($datos_registro['estado'] === 'Creado'){
    // En produccion se envia registro inicio 0 al ultimo, pero puede
    // interesar fraccionarlo si es muy grande, para evitar una sobrecarga de memoria o
    // DESARROLLO te puede interesar cambiar $registro_inicial para no estar tanto tiempo esperando.
    $registro_inicial = 0;
    $registro_final = $datos_registro['Registros_originales'];
    $instruccion = 'python '.$URLCom.'/lib/py/leerDbf1.py 2>&1 -f '.$fichero.' -i '.$registro_inicial.' -e '.$registro_final;
    error_log('Registro Inicial de importacion:'.$registro_inicial."\n\r",3,$fichero_registro);


    exec($instruccion, $output,$entero);
    // Recuerda que $output es un array de todas las lineas obtenidad en .py
    // tambien recuerda que si el $entero es distinto de 0 , es que hubo un error en la respuesta de  .py
    if ($entero === 0) {
        $nulos = 0;
        $errores = 0;
        foreach ($output as $i=>$linea) {
            $l= json_decode($linea,true);
            $l['LINEA'] = $i+$registro_inicial;
            // Analizamos los campos que puede convertir la linea en NULA
            $delete = 'KO';
            foreach ($filtros['importar'] as $filtro){
                $campo=$filtro['nombre_campo'];
                if ($l[$campo] === $filtro['valor']){
                    $delete='OK';
                    break; // Ya que no tiene sentido continuar comprobando linea, ya es nulo
                }
            }
            if ($delete == 'KO'){
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
                // --- Control de DEBUG ---- //
                // Ejemplo de control de insert, cuando falla
                //~ if ($l['LINEA'] > 12289){
                    //~ $solo_sql = $importarDbf->insertarDbf('modulo_importar_ARTICULO',$l,true);
                    //~ error_log('Linea:'.$l['LINEA'].':'.$solo_sql,3,$fichero_registro);
                //~ }
                // --- Fin de debug  --- //
                $r = $importarDbf->insertarDbf('modulo_importar_ARTICULO',$l);
                if (gettype($r) === 'boolean'){
                    $errores++;
                    if ($reg_log['importar']['error'] === 'Si'){
                        error_log('Error linea:'.$i.' Campo Principal:'.$l[$campo_principal]." \n\r",3,$fichero_registro);
                        if ($reg_log['importar']['sql'] === 'Si'){
                            $e=$importarDbf->getFallo();
                            // Volvemos ejecutar insertar para obtener el sql.
                            $sql = $e['consulta'];
                            error_log('Error:'.$e['descripcion'],3,$fichero_registro);
                            error_log('SQL:'.$sql,3,$fichero_registro);
                            error_log('Contiene la linea:'.$linea,3,$fichero_registro);
                        }
                    }
                }
            } else {
                $nulos++;
                if ($reg_log['importar']['nulo'] ==='Si'){
                    error_log('Nulo linea:'.$i.' Campo Principal:'.$l[$campo_principal]." \n\r",3,$fichero_registro);
                }
            }
            $i++;
        }
    } else {
        error_log('============= Error  1.0 al obtener datos - PARAMOS PROCESO segundo_plano ==============='." \n\r",3,$fichero_registro);
        error_log('Instruccion:'.$instruccion." \n\r",3,$fichero_registro);
        error_log('Output:'.$output." \n\r",3,$fichero_registro);
        exit();
    }
    error_log('Registro Final de importacion:'.$registro_final."\n\r",3,$fichero_registro);

    // Ahora registramos nulos y errores
    $e = $importarDbf->anhadirNulosErrores($datos_registro['id'],$nulos,$errores);
    if ($e === false ){
        // Hubo un error al cambiar el estado, por lo que no podemos continuar.
        error_log('Hubo un error al registrar los nulos y errores:'.json_encode($importarDbf->getFallo()),3,$fichero_registro);
        error_log('===========  Error 1.1 al registrar nulos y errores - PARAMOS PROCESO segundo_plano ============'." \n\r",3,$fichero_registro);
        exit();
    }
    error_log('Registramos '.$nulos.' nulos y '.$errores.' errores',3,$fichero_registro);
    // Ahora cambiamos estado de registro a importado, para empezar con la fussion.
    $e = $importarDbf->cambioEstado($datos_registro['id'],'Importado');
    if ($e === false ){
        // Hubo un error al cambiar el estado, por lo que no podemos continuar.
        error_log('Hubo un error:'.json_encode($importarDbf->getFallo()),3,$fichero_registro);
        error_log('ERROR 1.2 EN segundo_plano  al cambiar estado a Importado :'.json_encode($importarDbf->getFallo())." \n\r",3,$fichero_registro);
        exit();
    } else {
        // Cambiamos estado variable para continue fusionando, sino no entra.
        $datos_registro['estado'] ='Importado';

    }
}
//  =======   Fin  importacion    ================= //



//  =======     Inicio Fusion     ================= //
if ($datos_registro['estado'] === 'Importado'){
    error_log('==========================   Empezamos la fusion ====================='." \n\r",3,$fichero_registro);
    $codigos_principales = $importarDbf->leerTodos_mod_articulo();
    if ($codigos_principales === false){
        // Hubo un error al obtener los codigo de la tabla para fusionar.
        error_log('Hubo un error:'.json_encode($importarDbf->getFallo())." \n\r",3,$fichero_registro);
        error_log('===========  NO CONTINUAMOS POR ERROR 1.3 EN segundo_plano ============'.json_encode($importarDbf->getFallo())." \n\r",3,$fichero_registro);
        exit();
    }
    // Contamos cuantos productos vamos analizar.
    error_log('Añalizamos '.count($codigos_principales).' productos, si son nuevos,actualizado... ==='." \n\r",3,$fichero_registro);
    
    foreach ($codigos_principales as $producto){
        $estado =''; // Estados posibles de registros modulo_importar_ARTICULO son:(error,nuevo,actualizado,igual,filtrado,null)
        // Comprobamos si tenemos filtrar este registro.
        $estado = $importarDbf->ComprobarFiltroRegistro($producto,'ambos');
        // Actualizamos o nuevo, dejamos igual.
        if ($estado === ''){
            $estado = $importarDbf->ControllerNewUpdate($producto);
        }
        if ($reg_log['fusionar']['Codigo_y_estado'] === 'Si'){
            error_log('codigo:'.$producto['CODIGO'].' estado:'.$estado." \n\r",3,$fichero_registro);
        }
        $cambioEstadoImport = $importarDbf->cambioEstadoImportado($producto['CODIGO'],$estado);
        if ($cambioEstadoImport === false){
            // Hubo un error al cambiar el estado en tabla importada, no bloqueo el continuar ya que no tiene sentido.
            $sql = $importarDbf->getFallo();
            error_log(' ERROR 1.4 EN segundo_plano en codigo:'.$producto['CODIGO'].'consulta:'.$sql['consulta']." \n\r",3,$fichero_registro);
        }
        
    }
    $e = $importarDbf->CambioEstado($datos_registro['id'],'Fusionado');
    if ($e === false ){
        // Hubo un error al cambiar el estado, por lo que no podemos continuar.
        error_log('Hubo un error:'.json_encode($importarDbf->getFallo())." \n\r",3,$fichero_registro);
        error_log('ERROR 1.5 EN segundo_plano  al cambiar estado a fusionado :'.json_encode($importarDbf->getFallo())." \n\r",3,$fichero_registro);
        exit();
    }
}
    if (file_exists($fichero)) {
        error_log('Eliminadmos fichero subido:'.$fichero.'\n'." \n\r",3,$fichero_registro);
        unlink($fichero);
    }
    error_log('===============   Terminamos '.date("Y-m-d H:i:s").' ======= '." \n\r",3,$fichero_registro);

    

    ?>
    
