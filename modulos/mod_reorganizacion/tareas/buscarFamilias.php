<?php
//@Objetivo:
//Busca la familia según el dato insertado , si el dato viene de la caja idFamilia entonces busca por id
//Si no busca por nombre de la familia y muestra un modal con las coincidencias ,
//Si no recibe busqueda muestra un modal con todas las familias
// Contiene el control de errores de las funciones que llama a la clase familia


// Saber si el valor de busqueda esta vacio.
include_once $URLCom . '/configuracion.php';
include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
include_once $URLCom . '/modulos/mod_familia/funciones.php';
$Cfamilias = new ClaseFamilias($BDTpv);
$buscar = array();
$respuesta = array(
    'Nitems' => 0,
    'id' => 0
);

if (strlen(trim($_POST['busqueda'])) == 0) {
    // Buscamos los datos de todas las familias ya que no tiene valor busqueda.
    $buscar['datos'] = $Cfamilias->todasFamilias();
    $respuesta['Nitems'] = count($buscar['datos']);
} else {
    // Tiene valor para buscar familia o familias.
    if ($_POST['idcaja'] === "id_familia") {
        // Buscamos por id, pero el resultado siempre es uno..por lo que
        // sino se cambia el metodo, no podemos nunca buscar por id varios proveedores.
        $buscar = $Cfamilias->buscarFamiliaId($_POST['busqueda']);
    } else {
        // Buscamos por nombre, el resultado siempre es un array de arrays con uno mas proveedores.
        $buscar = $Cfamilias->buscarFamiliaNombre($_POST['busqueda']);
    }
    if (isset($buscar['error'])) {
        // Existe un error en la consulta
        $respuesta['advertencia'] = $buscar;
    } else {
        // NO hubo error, continuamos

        if (isset($buscar['idFamilia'])) {
            // Obtuvo un resultado.
            // Ahora compruebo que su estado es NO es inactivo
            if ($buscar['mostrar_tpv'] !== '1') {
                $respuesta['id'] = $buscar['idFamilia'];
                $respuesta['nombre'] = $buscar['familiaNombre'];
                $respuesta['Nitems'] = 1;
            } else {
                // Si estado INACTIVO no tiene un id, ya queremos que nos habrá popup.
                // convierto el resultado en un array de array
                $buscar['datos']['0'] = $buscar;
            }
        }
    }
    // Obtuvo varios resultados o ninguno
    if (!isset($buscar['datos'])) {
        // No obtuvo resultados.
        $respuesta['datos'] = '';
        $buscar['datos'] = array();
    } else {
        $respuesta['Nitems'] = count($buscar['datos']);
        $respuesta['datos'] = $buscar['datos'];
    }
}
if ($respuesta['id'] == 0) {
    $respuesta['html'] = htmlFamilias($_POST['busqueda'], $_POST['dedonde'], $_POST['idcaja'], $buscar['datos']);
}
echo json_encode($respuesta);
return $respuesta;
