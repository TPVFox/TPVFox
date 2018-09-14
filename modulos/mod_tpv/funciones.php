<?php

/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones en php para modulo TPV
 * */
include_once './../../inicial.php';
include_once $URLCom.'/modulos/mod_producto/clases/ClaseArticulosStocks.php';

function BuscarProductos($id_input, $campoAbuscar, $busqueda, $BDTpv) {
    // @ Objetivo:
    // 	Es buscar por Referencia / Codbarras / Descripcion nombre.
    //  tanto buscamos identicos como Likes 
    // @ Parametros:
    //		campoAbuscar-> indicamos que campo estamos buscando.
    //		busqueda -- string a buscar, puede contener varias palabras
    //		BDTpv-> conexion a la base datos.
    //		vuelta = 1, para buscar algo identico, si viene con 2 busca con %like% segunda llamada
    $resultado = array();
    $palabras = array();
    $products = array();

    if ($busqueda === '') {
        $resultado['Estado'] = 'NoSeBusco';
        return $resultado; // No continuamos ya que no tiene sentido.
    }

    // Limpio busqueda para evitar rotura en la consulta.
    $buscar = array(',', ';', '(', ')', '"', "'");
    $sustituir = array(' , ', ' ; ', ' ( ', ' ) ', ' ', ' ');
    $string = str_replace($buscar, $sustituir, trim($busqueda));
    $palabras = explode(' ', $string); //array de varias palabras, si las hay..

    $likes = array();
    $whereIdentico = array();

    foreach ($palabras as $key => $palabra) {
        if (trim($palabra) !== '') {
            $likes[] = $campoAbuscar . ' LIKE "%' . $palabra . '%" ';
            $whereIdentico[] = $campoAbuscar . ' = "' . $palabra . '"';
        } else {
            unset($palabras[$key]);
        }
    }
    $resultado['palabras'] = $palabras;

    //si vuelta es distinto de 1 es que entra por 2da vez busca %likes%	
    $busquedas = array();

    if (count($palabras > 0)) {
        $busquedas[] = implode(' and ', $whereIdentico);

        $busquedas[] = implode(' and ', $likes);
    }
    $i = 0;
    foreach ($busquedas as $buscar) {
        /* Buscamos identico primero y luego likes */
        $sql = 'SELECT a.`idArticulo` , a.`articulo_name` , ac.`codBarras` , ap.pvpCiva, at.crefTienda , a.`iva` '
                . ' FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac '
                . ' ON a.idArticulo = ac.idArticulo LEFT JOIN `articulosPrecios` AS ap '
                . ' ON a.idArticulo = ap.idArticulo AND ap.idTienda =1 LEFT JOIN `articulosTiendas` '
                . ' AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 WHERE ' . $buscar . ' LIMIT 0 , 30 ';
        $resultado['sql'] = $sql;
        $res = $BDTpv->query($sql);

        $resultado['Nitems'] = $res->num_rows;
        // Al ser identicos, es correcto, eso en la primera busqueda
        if ($i === 0) {
            if ($res->num_rows > 0) {
                $resultado['Estado'] = 'Correcto';
                // No volvemos a buscar posibles (LIKE)
                break;
            }
        }
        //compruebo error en consulta
        if (mysqli_error($BDTpv)) {
            $resultado['consulta'] = $sql;
            $resultado['error'] = $BDTpv->error_list;
            error_log('Error_buscar_producto:' . json_encode($resultado['error']) . '  Consulta:' . $sql);
            return $resultado;
        }
        $i++;
    }

    if (isset($res->num_rows)) {
        // Si existe resultado entramos.
        if ($res->num_rows > 0) {
            if (!isset($resultado['Estado'])) {
                // Quiere decir que no encontro uno igual, sino que encontro LIKE
                // es posible el resultado busqueda sea uno solo, pero lo hizo con LIKE
                // mostramos listado (popup) igualmente.
                $resultado['Estado'] = 'Listado';
            }
        } else {
            if ($res->num_rows === 0) {
                // Cuando se busco pero no se encontro nada.
                $resultado['Estado'] = 'NoExiste';
            }
        }
        while ($fila = $res->fetch_assoc()) {
            $products[] = $fila;
            $resultado['datos'] = $products;
        }
    }

    return $resultado;
}

function htmlProductos($productos, $id_input, $campoAbuscar, $busqueda) {
    // @ Objetivo 
    // Obtener listado de produtos despues de busqueda.
    $resultado = array();

    $resultado['encontrados'] = count($productos);
    $resultado['html'] = "<script type='text/javascript'>
					// Ahora debemos añadir parametro campo a objeto de cajaBusquedaProductos" .
            "cajaBusquedaproductos.parametros.campo.__defineSetter__ =" . "'" . $campoAbuscar . "';
						idN.parametros.campo.__defineSetter__ =" . "'" . $campoAbuscar . "';
						</script>";
    $resultado['html'] .= '<label>Busqueda por ' . $id_input . '</label>';
    // Utilizo el metodo onkeydown ya que encuentro que onKeyup no funciona en igual con todas las teclas.

    $resultado['html'] .= '<input id="cajaBusqueda" name="' . $id_input . '" placeholder="Buscar" data-obj="cajaBusquedaproductos" size="13" value="' . $busqueda . '" onkeydown="controlEventos(event)" type="text">';
    if (count($productos) > 10) {
        $resultado['html'] .= '<span>10 productos de ' . count($productos) . '</span>';
    }
    if ($resultado['encontrados'] === 0) {
        // Hay que tener en cuenta tambien si la caja tiene datos ya que sino no es lo mismo.
        if (strlen($busqueda) === 0) {
            // Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
            $resultado['html'] .= '<div class="alert alert-info">';
            $resultado['html'] .= ' <strong>Buscar!</strong> Pon las palabras para buscar productos que consideres.</div>';
        } else {
            // Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
            $resultado['html'] .= '<div class="alert alert-warning">';
            $resultado['html'] .= ' <strong>Error!</strong> No se encontrado nada con esa busqueda.</div>';
        }
    } else {

        $resultado['html'] .= '<table class="table table-striped"><thead>';
        $resultado['html'] .= ' <th></th>';
        $resultado['html'] .= '</thead><tbody>';

        $contad = 0;
        foreach ($productos as $producto) {
            $datos = "'" . $id_input . "'," .
                    "'" . addslashes(htmlspecialchars($producto['crefTienda'], ENT_COMPAT)) . "','"
                    . addslashes(htmlentities($producto['articulo_name'], ENT_COMPAT)) . "','"
                    . number_format($producto['iva'], 2) . "','" . $producto['codBarras'] . "',"
                    . number_format($producto['pvpCiva'], 2) . "," . $producto['idArticulo'];
            $Fila_N = 'Fila_' . $contad;
            $resultado['html'] .= '<tr class="FilaModal" id="' . $Fila_N . '"  onclick="escribirProductoSeleccionado('
                    . $datos . ');">';

            $resultado['html'] .= '<td id="C' . $contad . '_Lin">'
                    . '<input id="N_' . $contad . '" name="filaproducto"  data-obj="idN"  onkeydown="controlEventos(event)" type="image" alt=""><span class="glyphicon glyphicon-plus-sign agregar"></span></td>';
            $resultado['html'] .= '<td>' . htmlspecialchars($producto['crefTienda'], ENT_QUOTES) . '</td>';
            $resultado['html'] .= '<td>' . htmlspecialchars($producto['articulo_name'], ENT_QUOTES) . '</td>';
            $resultado['html'] .= '<td>' . number_format($producto['pvpCiva'], 2) . '</td>';

            $resultado['html'] .= '</tr>';
            $contad = $contad + 1;
            if ($contad === 10) {
                break;
            }
        }
        $resultado['html'] .= '</tbody></table>';
    }
    $resultado['campo'] = $campoAbuscar;

    return $resultado;
}

function htmlCobrar($total, $configuracion) {
    // @ Objetivo:
    // Crear el html de ventana de cobrar, la cual mostramos en modo modal.
    $resultado = array();
    $resultado['entregado'] = 0;
    $resultado['modoPago'] = 0;
    $resultado['imprimir'] = 0;
    $resultado['html'] = '<div style="margin:0 auto; display:table; text-align:right;">'
            . '<h1>' . number_format($total, 2) . '<span class="small"> €</span></h1>'
            . '<h4> Entrega &nbsp <input pattern="[-+]?[0-9]*[.]?[0-9]+" id="entrega" name="entrega" class="text-right" value="' . number_format($total, 2) . '" data-obj="entrega" size="8" onkeydown="controlEventos(event)" ></input></h4>'
            . '<h4> Cambio &nbsp<input class="text-right" disabled id="cambio" size="8" type="text" name="cambio" value="0"></input></h4>'
            . '<div class="checkbox" style="text-align:center">';
    if ($configuracion['impresion_ticket'] === 'Si') {
        $chek = 'checked';
    } else {
        $chek = '';
    }
    $resultado['html'] .= '<label><input name="checkimprimir" type="checkbox" ' . $chek . '> Imprimir</label>'
            . '</div>'
            . '<div>'
            . '<select name="modoPago" id="modoPago">'
            . '<option value="contado">Contado</option>'
            . '<option value="tarjeta">Tarjeta</option>'
            . '</select>'
            . ' <button id="CobrarAceptar" type="button" data-obj="CobrarAceptar" onkeydown="controlEventos(event)" class="btn btn-primary" onclick="controlEventos(event)" >Aceptar</button>'
            . '</div>'
            . '</div>';

    return $resultado;
}

function grabarTicketsTemporales($BDTpv, $productos, $cabecera, $total) {
    // @ Objetivo: 	Guardar datos en tabla temporal de tickets.
    // @ Parametros:
    // 	$BDTpv -> Conexion a base de datos.
    // 	$productos -> Array de productos añadidos a ticket
    // 	$cabecera _> Array con datos de la cabecera.	
    $resultado = array();
    // Tomamos el valor de la fecha actual.
    $fecha = date("Y-m-d H:i:s");
    // Ponemods datos de variables cabecera.
    $idTienda = $cabecera['idTienda'];
    $idCliente = $cabecera['idCliente'];
    $idUsuario = $cabecera['idUsuario'];
    // Sabemos comprobamos estado ticket para saber si obtenemos numero.
    if ($cabecera['estadoTicket'] === 'Nuevo') {
        // Tenemos que obtener en que numero ticket temporal de tabla indices.
        $campo = "tempticket";
        $numTicket = ObtenerNumIndices($BDTpv, $campo, $idUsuario, $idTienda, true);
    } else {
        // Sino es nuevo , será abierto, por lo que ya exite numero.
        $numTicket = $cabecera['numTicket'];
    }
    /*  ================ Montamos el json para guardar productos en un solo campo. ==== */
    $productos_json = array();
    foreach ($productos as $product) {
        $productos_json[] = json_encode($product);
    }
    $UnicoCampoProductos = json_encode($productos_json);
    $PrepProductos = $BDTpv->real_escape_string($UnicoCampoProductos); //  Escapa los caracteres especiales de una cadena para usarla en una sentencia SQL, tomando en cuenta el conjunto de caracteres actual de la conexión
    /*  ================ Montamos instrucción, según estado. ==== */

    if ($cabecera['estadoTicket'] === 'Nuevo') {
        // Variables cambiadas.
        $resultado['estadoTicket'] = 'Actual';
        $resultado['fechaInicial'] = $fecha;

        // Insertamos el nuevo tickettemporal
        $SQL = 'INSERT INTO `ticketstemporales`(`numticket`,`estadoTicket`, `idTienda`, `idUsuario`, `fechaInicio`, `idClientes`, `total`, `Productos`) VALUES (' . $numTicket . ',"' . $resultado['estadoTicket'] . '",' . $idTienda . ',' . $idUsuario . ',"' . $fecha . '",' . $idCliente . ',' . $total . ',"' . $PrepProductos . '")';
        $BDTpv->query($SQL);
        if (mysqli_error($BDTpv)) {
            $resultado['consulta'] = $SQL;
            $resultado['error'] = $BDTpv->error_list;
        }
    } else {
        // Si NO es Nuevo entonces se hace UPDATE
        $SQL = 'UPDATE `ticketstemporales` SET `idClientes`=' . $idCliente . ',`fechaFinal`="' . $fecha . '",`total`=' . $total . ',`Productos`=' . "'" . $PrepProductos . "'" . ' WHERE `idTienda`=' . $idTienda . ' and `idUsuario`=' . $idUsuario . ' and numticket =' . $numTicket;
        $BDTpv->query($SQL);
        if ($cabecera['estadoTicket'] != 'Abierto') {
            // Quiere decir que no es el actual...
            // aun no las tengo todas conmigo.. para decir esto.
        }

        $resultado['estadoTicket'] = 'Actual';
        $resultado['fechaFinal'] = $fecha;
        if (mysqli_error($BDTpv)) {
            $resultado['consulta3'] = $SQL;
            $resultado['error3'] = $BDTpv->error_list;
        }
    }
    $resultado['NumeroTicket'] = $numTicket;
    //~ $resultado['productos'] = $productos_json;	
    $resultado['productos'] = $PrepProductos;
    return $resultado;
}

function recalculoTotales($productos) {
    // @ Objetivo recalcular los totales y desglose del ticket
    // @ Parametro:
    // 	$productos (array) no objeto.
    $respuesta = array();
    $desglose = array();
    $ivas = array();
    $subtotal = 0;
    // Creamos array de tipos de ivas hay en productos.
    //~ $ivas = array_unique(array_column($productos,'ctipoiva'));
    //~ sort($ivas); // Ordenamos el array obtenido, ya que los indices seguramente no son correlativos.
    foreach ($productos as $product) {
        // Si la linea esta eliminada, no se pone.
        if ($product->estado === 'Activo') {
            $totalLinea = $product->unidad * $product->pvpconiva;
            //~ $respuesta['lineatotal'][$product->nfila] = number_format($totalLinea,2);
            $subtotal = $subtotal + $totalLinea; // Subtotal sumamos importes de lineas.
            // Ahora calculmos bases por ivas
            $desglose[$product->ctipoiva]['BaseYiva'] = (!isset($desglose[$product->ctipoiva]['BaseYiva']) ? $totalLinea : $desglose[$product->ctipoiva]['BaseYiva'] + $totalLinea);
            // Ahora calculamos base y iva 
            $operador = (100 + $product->ctipoiva) / 100;
            $desglose[$product->ctipoiva]['base'] = number_format(($desglose[$product->ctipoiva]['BaseYiva'] / $operador), 2);
            $desglose[$product->ctipoiva]['iva'] = number_format($desglose[$product->ctipoiva]['BaseYiva'] - $desglose[$product->ctipoiva]['base'], 2);
            //~ $desglose[$product->ctipoiva]['tipoIva'] =$iva;
        }
    }
    //~ $respuesta['ivas'] = $ivas;
    $respuesta['desglose'] = $desglose;
    $respuesta['total'] = number_format($subtotal, 2);
    return $respuesta;
}

function ControlEstadoTicketsAbierto($BDTpv, $idUsuario, $idTienda) {
    // @ Objetivo:
    // Es poner el estado Abierto todos los tickets temporales de ese usuario y tienda que tenga estado Actual.
    // Se entiende que al entrar en ticket tpv , vamos hacer uno nuevo y abandonamos el que estuvieramos haciendo.
    // por lo cual lo pasamos a abierto.
    $respuesta = array();
    // Montamos consulta
    $sql = 'UPDATE `ticketstemporales` SET `estadoTicket` = "Abierto" WHERE `idTienda` =' . $idTienda . ' AND `idUsuario` =' . $idUsuario . ' AND estadoTicket ="Actual"';
    $BDTpv->query($sql);
    if (mysqli_error($BDTpv)) {
        $resultado['consulta'] = $sql;
        $resultado['error'] = $BDTpv->error_list;
    }
    // Si fue correcto comprobamos a cuantos afectos, que sería los tickets abiertos.
    $respuesta['num_afectados'] = $BDTpv->affected_rows;
    //~ $respuesta['consulta'] = $sql;
    return $respuesta;
}

function ObtenerUnTicketTemporal($BDTpv, $idTienda, $idUsuario, $numero_ticket) {
    // @ Objetivo
    // Obtener los datos de un ticket ( ticketsTemporal ), con sus productos en un array.
    // Hay que tener en cuenta que todos los productos del tickets esta en un campo unico, en un array JSON
    $respuesta = array();
    $productos = array();
    $Sql = 'SELECT t.`id` , t.`numticket` , t.`estadoTicket` , t.`idTienda` , t.`idUsuario` , t.`fechaInicio` , t.`fechaFinal` , t.`idClientes` , t.`total` , t.`total_ivas` , c.`Nombre` , c.`razonsocial`,t.`Productos` FROM `ticketstemporales` AS t LEFT JOIN `clientes` AS c ON c.`idClientes` = t.`idClientes` WHERE `idTienda` =' . $idTienda . ' AND `idUsuario` =' . $idUsuario . ' AND `numticket` =' . $numero_ticket;


    if ($resp = $BDTpv->query($Sql)) {
        // Quiere decir que hay resultados.
        $respuesta['Numero_rows'] = $resp->num_rows;
        if ($respuesta['Numero_rows'] === 1) {
            $row = $resp->fetch_assoc();
            // Enviamos datos cabecera tambien.
            $respuesta['numticket'] = $row['numticket'];
            $respuesta['idClientes'] = $row['idClientes'];
            $respuesta['fechaInicio'] = $row['fechaInicio'];
            $respuesta['fechaFinal'] = $row['fechaFinal'];
            $respuesta['Nombre'] = $row['Nombre'];
            $respuesta['razonsocial'] = $row['razonsocial'];
            $respuesta['estadoTicket'] = $row['estadoTicket'];

            // Obtenemos array de productos con campo unico que es un Json con los campos
            $productos_json = json_decode($row['Productos']);
            foreach ($productos_json as $product) {
                $temp = json_decode($product);
                $productos[$temp->nfila - 1] = $temp;
                //~ $productos[] = json_decode($product);// Obtenemos campos del producto.
            }
        } else {
            // Quiere decir que algo salio mal, ya que obtuvo mas o ninguno registro.
            $respuesta['error'] = ' Numeros tickets obtenidos: ' . $respuesta['Numero_rows'];
            $respuesta['consulta'] = $Sql;
            return $respuesta; // No continuamos.
        }
    } elseif (mysqli_error($BDTpv)) {
        $respuesta['consulta'] = $Sql;
        $respuesta['error'] = $BDTpv->error_list;
        return $respuesta; // No continuamos si hay error en la consulta.
    }
    /* liberar el conjunto de resultados */
    $resp->free();
    $respuesta['productos'] = $productos;
    return $respuesta;
}

function anhadirLineasTicket($productos, $CONF_campoPeso) {
    //@ Objetivo:
    // Obtener html de todas las lineas de productos.
    $htmlLineas = array();
    foreach ($productos as $product) {
        $num_item = $product->nfila - 1;
        $unaLinea = htmlLineaTicket($product, $num_item, $CONF_campoPeso);
        $htmlLineas[$num_item] = $unaLinea;
    }
    return $htmlLineas;
}

function htmlLineaTicket($producto, $num_item, $CONF_campoPeso) {
    //@ Objetivo:
    // Obtener html de una linea de productos.
    //@ Parametros:
    // $product -> Debería ser un objeto, pero por javascritp viene como un array por lo comprobamos y convertimos.
    // Variables que vamos utilizar:
    $classtr = ''; // para clase en tr
    $estadoInput = ''; // estado input cantidad.

    if (!is_object($producto)) {
        // Comprobamos si product no es objeto lo convertimos.
        $product = (object) $producto;
    } else {
        $product = $producto;
    }
    // Creamos importe --> 
    $importe = $product->pvpconiva * $product->unidad;
    $importe = number_format($importe, 2);
    // Si estado es eliminado tenemos añadir class y disabled input
    if ($product->estado !== 'Activo') {
        $classtr = ' class="tachado" ';
        $estadoInput = 'disabled';
        $funcOnclick = ' retornarFila(' . $num_item . ');';
        $btnELiminar_Retornar = '<td class="eliminar"><a onclick="' . $funcOnclick . '"><span class="glyphicon glyphicon-export"></span></a></td>';
    } else {
        $funcOnclick = ' eliminarFila(' . $num_item . ');';
        $btnELiminar_Retornar = '<td class="eliminar"><a onclick="' . $funcOnclick . '"><span class="glyphicon glyphicon-trash"></span></a></td>';
    }
    $nuevaFila = '<tr id="Row' . ($product->nfila) . '" ' . $classtr . '>'
            . '<td class="linea">' . $product->nfila . '</td>' //num linea
            . '<td class="codbarras">' . $product->ccodebar . '</td>'
            . '<td class="referencia">' . $product->cref . '</td>'
            . '<td class="detalle">' . $product->cdetalle . '</td>'
            . '<td><input pattern="[-+]?[0-9]*[.]?[0-9]+" id="Unidad_Fila_' . $product->nfila
            . '" type="text" data-obj="Unidad_Fila" pattern="[.0-9]+" name="unidad" placeholder="unidad" size="4"  value="'
            . $product->unidad . '"  ' . $estadoInput . ' onkeydown="controlEventos(event,'
            . "'Unidad_Fila_" . $product->nfila . "'" . ')" onBlur="controlEventos(event)"></td>';
    //si en config peso=si, mostramos columna peso
    if ($CONF_campoPeso === 'si') {
        $nuevaFila .= '<td><input pattern="[-+]?[0-9]*[.]?[0-9]+" id="C' . $product->nfila . '_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>'; //cant/kilo
    } else {
        $nuevaFila .= '<td style="display:none"><input id="C' . $product->nfila . '_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>';
    }
    $nuevaFila .= '<td class="pvp"><input pattern="[-+]?[0-9]*[.]?[0-9]+" id="precioCIva_' . $product->nfila
            . '" name="precioCIva_' . $product->nfila . '" value="' . $product->pvpconiva . '" size="3" data-obj="cajaPrecioCIva"'
            . ' onkeydown="controlEventos(event)"  readonly>'
            . '<a onclick="ActivarPrecioCIva(event,' . $product->nfila . ')">'
            . '<span class="glyphicon glyphicon-cog"></span>'
            . '</a></td>'
            . '<td class="tipoiva">' . $product->ctipoiva . '%</td>'
            . '<td id="N' . $product->nfila . '_Importe" class="importe" >' . $importe . '</td>' //importe 
            . $btnELiminar_Retornar // Mostramos btn eliminar o retornar
            . '</tr>';
    return $nuevaFila;
}

function MaquetarFecha($fecha, $tipo = 'dmy') {
    // @ Objetivo formatear una una fecha y obtener al tipo indicado
    // @ Parametros
    // 	$fecha : Dato de fecha
    //	$tipo : Pueder ser 
    //				HM -> Hora Minuto
    //				dmy -> Dia Mes Año
    // Creamos array de fecha
    $fechaArray = date_parse($fecha);
    $horaMinuto = sprintf("%'.02d", $fechaArray['hour']) . ':' . sprintf("%'.02d", $fechaArray['minute']);
    $DiaMesAnho = sprintf("%'.02d", $fechaArray['day']) . '/' . sprintf("%'.02d", $fechaArray['month']) . '/' . $fechaArray['year'];
    if ($tipo === 'HM') {
        $respuesta = $horaMinuto;
    } else {
        $respuesta = $DiaMesAnho;
    }
    return $respuesta;
}

function ObtenerNumIndices($BDTpv, $campo, $idUsuario, $idTienda, $incrementar = false) {
    // @ Objetivo 
    // Obtener el numero tickets a utilizar en las tablas tickets y si lo indicamos a la funcion podemos incrementarlo.
    // @ Parametros
    // 	 $campo: (String) `Numtempticket`,`Numticket` , segun se la tabla que utilicemos.
    // 	 $idUsuario ->(int); 
    // 	 $idTienda 	->(int);
    // 	 $incrementar ---> booleano ( lo utilizamos para indicar a la funcion que incremente el numeros de ticket en el registro y campo indicado.
    // Hay que tener en cuenta que tenemos un registro por Usuario y Tienda para llevar un control numeros ticket. 
    $sql = 'SELECT ' . $campo . ' FROM `indices` WHERE `idTienda` =' . $idTienda . ' AND `idUsuario` =' . $idUsuario;
    $resp = $BDTpv->query($sql);

    $row = $resp->fetch_array(MYSQLI_NUM);
    if (count($row) === 1) {
        $numTicket = $row[0];
    } else {
        error_log('NO existe Indice de usuario, Algo salio mal en mod_tpv/funciones.php en funcion grabarTicketTemporal');
        //devolvemos
        $numTicket = -1;
        exit;
    }
    if ($incrementar === true) {
        // Si trae parametro $incrementar , se añade uno al valor actual del campo indicado.
        $numTicket = $numTicket + 1;
        $sql = "UPDATE `indices` SET " . $campo . " =" . $numTicket . " WHERE `idTienda` =" . $idTienda . " AND `idUsuario` =" . $idUsuario;
        $BDTpv->query($sql);
        if (mysqli_error($BDTpv)) {
            $numTicket = -2;
            error_log('No se pudo grabar en indices, algo salio mal en mod_tpv/funciones.php en funcion grabarTicketTemporal');
        }
    }

    return $numTicket;
}

function grabarTicketCobrado($BDTpv, $productos, $cabecera, $desglose) {
    // @ Objetivo:
    // Grabar el ticketCerrado (Cobrado) y cambiar el estado ticketTemporal.
    // @ Parametros:
    // 		$cabecera : Array que trae ->
    // 				$cabecera['total']
    //				$cabecera['entregado']
    //				$cabecera['formaPago']
    //				$cabecera['idTienda']
    // 				$cabecera['idCliente']
    //				$cabecera['idUsuario']
    //				$cabecera['estadoTicket']
    //				$cabecera['numTickTemporal'] 
    //				$cabecera['cambio'] 
    // 		$productos . Array de Objetos que trae ->
    //				[0] Indice producto.
    // 					producto.id;
    //					productos.cref;
    //					productos.cdetalle;
    //					productos.pvpconiva;
    //					productos.ccodebar
    //					productos.ctipoiva 
    //					productos.unidad 
    // 					productos.estado;
    //					productos.nfila
    // 		$desglose -> Arrayque trae ->
    //				["10"]["BaseYiva"] -> Subtotal
    //					  ["base"] -> Base
    //					  ["iva"]: -> Importe Iva
    //				["4"]...  El orden puede ser cualquiera de los ivas y no tiene que porquetodos.. 
    //				["21"] ...
    // Recuerda que tenemos que obtener el numticket en el que va el usuario.
    // por logica solo podrá utilizar la aplicación un usuario en una sola tienda a la vez.:-)
    // La fecha y hora ( timedate) la del momento de cobrar.
    // Campo de tabla ticketst 
    // id 	, Numticket , Numtempticket ,Fecha 	datetime, idUsuario, idCliente, estado, formaPago, entregado
    $SqlTickets = array(); // Creamos array para SQL
    $Impresion = array(); // Creamos array para luego enviar imprimir
    $fecha = date("Y-m-d H:i:s");
    $estado = 'Cobrado';
    // Solo falta obtener el numticket que tiene en indice es usuario para esa tienda.
    $campo = 'numticket';
    // Obtenemos el numero ticket para grabar y ya cambiado en indice... por si somos muy rápidos.. :-)
    $numticket = ObtenerNumIndices($BDTpv, $campo, $cabecera['idUsuario'], $cabecera['idTienda'], true); // Lo incrementamos 
    // Creamos la consulta para graba en
    // Preparamos SQl para Consulta en tickest
    $SqlTicket = 'INSERT INTO `ticketst`(`Numticket`, `Numtempticket`, `Fecha`'
            . ', `idUsuario`, `idTienda`, `idCliente`, `estado`, `formaPago`'
            . ', `entregado`, `total`) VALUES (' . $numticket . ',' . $cabecera['numTickTemporal']
            . ',"' . $fecha . '",' . $cabecera['idUsuario'] . ',' . $cabecera['idTienda'] . ',' . $cabecera['idCliente'] . ',"' . $estado . '","' . $cabecera['formaPago'] . '","' . $cabecera['entregado'] . '","' . $cabecera['total'] . '")';
    // Ejecutamos consulta para obtener el id ( autoincremental) que el que va enlazar los tickets
    $BDTpv->query($SqlTicket);
    $numIdTicketT = $BDTpv->insert_id;
    if (mysqli_error($BDTpv)) {
        $resultado['error'][]['tipo'] = 'danger';
        $resultado['error'][]['mensaje'] = 'Consulta:' . $SqlTicket;
        $resultado['error'][]['datos'] = json_encode($BDTpv->error_list);
        // Registro igualmente en log, de momento...
        error_log(' Rotura en funcion grabarTicketCobrado()->Error al grabar en ticketst');
        error_log(json_encode($BDTpv->error_list));
    }

    // Preparamos SQl para Consulta para ticketLinea
    // Aquí va ser insert de varios registros , la cantidad productos que tenga el ticket
    $valor = array();
    $articulosStock = [];
    foreach ($productos as $producto) {
        $cantidad = (float) $producto->unidad;
        // De momento esto lo dejamos igual pero lo deberíamos controlar con $CONF_campoPeso
        $unidad = $cantidad; // En el momento que se gestione hay que cambiar la tabla.
        $valor[] = '(' . $numIdTicketT . ',' . $numticket . ',' . $producto->id . ',"' . $producto->cref . '","' . $producto->ccodebar . '","'
                . $producto->cdetalle . '",' . $cantidad . ',' . $unidad . ','
                . $producto->pvpconiva . ',' . $producto->ctipoiva . ',' . $producto->nfila . ',"' . $producto->estado . '")';
        if ($producto->estado == 'Activo') {
            $articulosStock[] = ['idArticulo' => $producto->id, 'idTienda' => $cabecera['idTienda'], 'nunidades' => $cantidad];
        }
    }
    $valores = implode(',', $valor);
    $SqlTickets[] = 'INSERT INTO `ticketslinea`(`idticketst`,`Numticket`, `idArticulo`'
            . ', `cref`, `ccodbar`, `cdetalle`, `ncant`, `nunidades`, `precioCiva`'
            . ', `iva`,nfila,estadoLinea) VALUES ' . $valores;


    // Preparamos SQl para Consulta para ticketstiva	
    if (count($desglose) > 0) {
        // En tickets con valor 0 , no hay datos desglose..
        $iva = array();
        foreach ($desglose as $index => $valor) {
            $iva [] = '(' . $numIdTicketT . ',' . $numticket . ',"' . $index . '","' . $valor['iva'] . '","' . $valor['base'] . '")';
            // $valor['iva'] -> Es el importe del iva.
        }
        $ivas = implode(',', $iva);
        if ($ivas != '') {
            $SqlTickets[] = 'INSERT INTO `ticketstIva`(`idticketst`,`Numticket`, `iva`, `importeIva`,`totalbase`) VALUES ' . $ivas;
        }
    }
    // Preparamos SQL para cambiar estado de ticket temporal.
    $SqlTickets[] = 'UPDATE `ticketstemporales` SET `fechaFinal`="' . $fecha . '",`estadoTicket`=' . "'" . $estado . "'" . ' WHERE `idTienda`=' . $cabecera['idTienda'] . ' and `idUsuario`=' . $cabecera['idUsuario'] . ' and numticket =' . $cabecera['numTickTemporal'];

    // Ejecutamos las cuatro consultas.
    foreach ($SqlTickets as $key => $sql) {
        $BDTpv->query($sql);
        if (mysqli_error($BDTpv)) {
            $resultado['error'][$key]['tipo'] = 'danger';
            $resultado['error'][$key]['mensaje'] = 'Consulta:' . $sql;
            $resultado['error'][$key]['datos'] = json_encode($BDTpv->error_list);
            // Registro igualmente en log, de momento...
            error_log(' Rotura en funcion grabarTicketCobrado()-> Consulta:' . $sql);
            error_log(json_encode($BDTpv->error_list));
        } else {
            // Enviamos datos que cuantos registros fueron añadidos o modificados por cada consulta..
            // aunque no lo utilizamos.
            $resultado['num_filas_consulta'][$key] = $BDTpv->affected_rows;
        }
    }
    foreach ($articulosStock as $articuloStock) {
        alArticulosStocks::actualizarStock($articuloStock['idArticulo'], $articuloStock['idTienda']
                , $articuloStock['nunidades'], K_STOCKARTICULO_RESTA);
    }
    // Devolvemos los numeros ticket , tanto temporal como real.
    $resultado['sql_ivas'] = gettype($desglose);
    $resultado['Numtickets'] = $numticket;
    $resultado['fecha'] = $fecha;
    return $resultado;
}

function ComprobarImpresoraTickets($ruta_impresora) {
    // @ Objetivo :
    // Comprobar si la ruta de la impresora es correcto.
    // @ Parametro:
    //   ruta_impresora-> (string) Ruta de la impresora.
    // @ Devuelve:
    //   boreano-> true (correcto) , false (no la encuentra)
    $respuesta = false;
    if (shell_exec('ls ' . $ruta_impresora)) {
        $respuesta = true;
    }
    return $respuesta;
}

function ImprimirTicket($productos, $cabecera, $desglose, $tienda) {
    // @ Objetivo es montar un array con las distintas partes del ticket para luego mandar imprimir.
    // Recuerda que € no imprime directamente hay que utilizar la code Page 1252, por ello en 
    // body NO podemos €
    $respuesta = array();
    // Obtenemos hora y fecha en el formato deseado a imprimir:
    $hora = MaquetarFecha($cabecera['fecha'], 'HM');
    $fecha = MaquetarFecha($cabecera['fecha']);
    // Preparamos la <<< cabecera1 del ticket  LETRA GRANDE  >>> 
    $respuesta['cabecera1'] = $tienda['NombreComercial'] . "\n"; // Este dato realmente lo deberíamos cojer de tabla tiendas.
    $respuesta['cabecera1-datos'] = $tienda['direccion'];
    // Preparamos la <<< cabecera2 del ticket  GRANDE  >>> 
    $respuesta['cabecera2'] = "\nTeléfono:" . $tienda['telefono'] . "\n";
    $respuesta['cabecera2'] .= str_repeat("=", 24) . "\n";
    $respuesta['cabecera2'] .= "FACTURA  SIMPLIFICADA\n";
    $respuesta['cabecera2'] .= str_repeat("=", 24) . "\n";
    $respuesta['cabecera2-datos'] = 'Fecha:' . $fecha . ' Hora: ' . $hora . "\n";
    $respuesta['cabecera2-datos'] .= ' Serie:' . $cabecera['Serie'] . ' Numero:' . $cabecera['NumTicket'] . "\n";
    $respuesta['cabecera2-datos'] .= str_repeat("-", 42) . "\n";
    // Preparamos el <<<  body   >>>  del ticket
    $lineas = array();
    $i = 0;
    foreach ($productos as $product) {
        // Solo montamos lineas para imprimir aquellos que estado es 'Activo';
        if ($product->estado === 'Activo') {
            // No mostramos referencia, mostramos id producto
            $lineas[$i]['1'] = ' (id:' . $product->id . ') ' . substr($product->cdetalle, 0, 36); //.substr($product->cref,0,10);
            $importe = $product->unidad * $product->pvpconiva;
            // Creamos un array con valores numericos para poder formatear correctamente los datos
            $Numeros = array(
                0 => array(
                    'float' => $product->unidad,
                    'decimales' => 0
                ),
                1 => array(
                    'float' => $product->pvpconiva,
                    'decimales' => 2
                ),
                2 => array(
                    'float' => $importe,
                    'decimales' => 2
                )
            );
            foreach ($Numeros as $indice => $strNumero) {
                $stringvalor = strval(number_format($strNumero['float'], $strNumero['decimales']));
                $Numeros[$indice]['string'] = ( strlen($stringvalor) < 10 ? str_repeat(" ", 10 - strlen($stringvalor)) . $stringvalor : $stringvalor );
            }

            $lineas[$i]['2'] = $Numeros[0]['string'] . ' X ' . $Numeros[1]['string'] . ' = ' . $Numeros[2]['string'] . ' (' . sprintf("%' 2d", $product->ctipoiva) . ')';
            $i++;
        }
    }
    $body = '';
    foreach ($lineas as $linea) {
        $body .= $linea['1'] . "\n";
        $body .= $linea['2'] . "\n";
    }
    $respuesta['body'] = $body;
    // Fin del <<<  body   >>>  del ticket
    // Preparamos el <<<  pie   >>>  del ticket
    $respuesta['pie-datos'] = str_repeat("-", 42) . "\n";
    foreach ($desglose as $index => $valor) {
        $respuesta['pie-datos'] .= $valor['base'] . '  -> ' . $index . '%' . '  -> ' . $valor['iva'] . "\n";
    }
    $respuesta['pie-datos'] .= str_repeat("-", 42) . "\n";
    $respuesta['pie-total'] = number_format($cabecera['total'], 2);
    $respuesta['pie-formaPago'] = $cabecera['formaPago'];
    $respuesta['pie-entregado'] = number_format($cabecera['entregado'], 2);
    $respuesta['pie-cambio'] = number_format($cabecera['cambio'], 2);

    $respuesta['pie-datos2'] = "\n" . $tienda['razonsocial'] . " - CIF: " . $tienda['nif'] . "\n";



    return $respuesta;
}

function ObtenerTickets($BDTpv, $filtro) {
    //obtenemos tickets cobrados / cerrados
    // Function para obtener productos y listarlos
    //tener en cuenta el  paginado con parametros: $desde,$filtro
    //varias tablas:	ticketst
    //		clientes
    //		cierres_usuarios_tickets --> para conseguir el idCierre de cada ticket cerrado
    $resultado = array();
    $idCierre = 0; // Valor por defecto para evitar advertencia en log.
    $consulta = 'SELECT t.*, c.`Nombre`, c.`razonsocial` FROM `ticketst` AS t '
            . 'LEFT JOIN `clientes` AS c '
            . 'ON c.`idClientes` = t.`idCliente` ' . $filtro;


    $ResConsulta = $BDTpv->query($consulta);

    $resultado = array();
    $i = 0;
    while ($fila = $ResConsulta->fetch_assoc()) {
        $numTicket = $fila['Numticket'];
        $resultado['tickets'][] = $fila;
        // Ahora consultamos si esta o no enviado stock a la web.
        $sql_envio_stock = 'SELECT * FROM `importar_virtuemart_tickets` WHERE `idTicketst`=' . $fila['id'];
        $Consulta_envio_stock = $BDTpv->query($sql_envio_stock);
        if (mysqli_error($BDTpv)) {
            $resultado['consulta'] = $sql;
            $resultado['error'] = $BDTpv->error_list;
            error_log(' Rotura en funcion ObtenerTickets funcion.php de mod_tpv linea 720');
            error_log($BDTpv->error_list);
            // Rompemos programa..
            //exit();
        } else {
            // Quiere decir que la consulta fue correcta.
            // Ahora comprobamos cuantos registros, ya que solo debería haber uno.
            if ($Consulta_envio_stock->num_rows === 1) {
                while ($fila_envio_stock = $Consulta_envio_stock->fetch_assoc()) {
                    $resultado['tickets'][$i]['enviado_stock'] = $fila_envio_stock['estado'];
                    $resultado['tickets'][$i]['respuesta_envio'] = $fila_envio_stock['Fecha'] . '(' . $Consulta_envio_stock->num_rows . ')';
                }
            } else {
                // Quiere decir que hubo 0 a mas 1 resultado.
                $resultado['tickets'][$i]['enviado_stock'] = 'Erroneo';
                $resultado['tickets'][$i]['respuesta_envio'] = '(' . $Consulta_envio_stock->num_rows . ')';
                $resultado['tickets'][$i]['respuesta_envio_rows'] = $Consulta_envio_stock->num_rows;
            }
        }
        $i ++;
    }

    $resultado['sql1'] = $consulta;
    return $resultado;
}

function ivas($BDTpv) {
    //recojo array de ivas
    $sql = 'SELECT `iva` AS iva FROM `ticketstIva` GROUP by iva';
    $resp = $BDTpv->query($sql);
    $resultado = array();
    if ($resp->num_rows > 0) {
        $i = 0;
        while ($fila = $resp->fetch_assoc()) {
            $resultado[$i] = $fila['iva'];
            $i++;
        }
    } else {
        $resultado = 0;
    }
    //$resultado['sql'] = $sql;
    return $resultado;
}

function DatosTiendaID($BDTpv, $idTienda) {
    // @ Obtener datos de tienda
    // Esta funcion pienso que no debería ser necesaria, pero no encontre otra forma pasar los datos ahora.
    $resultado = array();
    $sql = 'SELECT idTienda,razonsocial,telefono,direccion,NombreComercial,nif,ano,estado '
            . ' FROM tiendas WHERE idTienda = ' . $idTienda;
    $res = $BDTpv->query($sql);
    //compruebo error en consulta
    if (mysqli_error($BDTpv)) {
        $resultado['consulta'] = $sql;
        $resultado['error'] = $BDTpv->error_list;
        return $resultado;
    }
    $datos = $res->fetch_assoc();
    $resultado = $datos;
    return $resultado;
}

function ObtenerRefWebProductos($BDTpv, $productos, $idWeb) {
    // @ Objetivo 
    // Obtener el idVirtuemart del producto que utilizamos en virtuemart
    // @ Parametros
    // 	 $productos-> Array de objetos.
    // Montamos where para buscar los idArticulo de los productos.
    $resultado = array();
    $wheres = array();
    foreach ($productos as $producto) {
        $wheres[] = $producto['idArticulo'];
    }
    $where = '(' . implode(',', $wheres) . ')';

    $consulta = 'SELECT idArticulo,idVirtuemart FROM articulosTiendas WHERE `idTienda` =' . $idWeb . ' AND idArticulo IN ' . $where;
    if ($query = $BDTpv->query($consulta)) {
        while ($dato = $query->fetch_assoc()) {
            $key_id_producto = $dato['idArticulo'];
            foreach ($productos as $key=>$producto) {
                if ($producto['idArticulo'] === $key_id_producto){
                    $productos[$key]['idVirtuemart'] = $dato['idVirtuemart'];
                }
            }
        }
    } else {
        $resultado['error'] = ' Error en la consulta';
        $resultado['consulta'] = $consulta;
    }
    // Montamos productos con idVirtuemart.
   
    $resultado['productos'] = $productos;
    return $resultado;
}

function ObtenerEnvioIdTickets($BDTpv, $idTicketst) {
    // @Objetivo :
    // Es obtener si se envio el stock de ese ticket
    $resultado = array();
    $sql_envio_stock = 'SELECT * FROM `importar_virtuemart_tickets` WHERE `idTicketst`=' . $idTicketst;
    $Consulta_envio_stock = $BDTpv->query($sql_envio_stock);
    if (mysqli_error($BDTpv)) {
        $resultado['consulta'] = $sql;
        $resultado['error'] = $BDTpv->error_list;
        error_log(' Rotura en funcion ObtenerTickets funcion.php de mod_tpv linea 720');
        error_log($BDTpv->error_list);
        // Rompemos programa..
        //exit();
    } else {
        // Quiere decir que la consulta fue correcta.
        // Ahora comprobamos cuantos registros, ya que solo debería haber uno.
        if ($Consulta_envio_stock->num_rows === 1) {
            while ($fila_envio_stock = $Consulta_envio_stock->fetch_assoc()) {
                $resultado['tickets']['enviado_stock'] = $fila_envio_stock['estado'];
                $resultado['tickets']['respuesta_envio'] = $fila_envio_stock['Fecha'] . '(' . $Consulta_envio_stock->num_rows . ')';
                $resultado['tickets']['respuesta_envio_rows'] = $Consulta_envio_stock->num_rows;
            }
        } else {
            // Quiere decir que hubo 0 a mas 1 resultado.
            $resultado['tickets']['enviado_stock'] = 'Erroneo';
            $resultado['tickets']['respuesta_envio'] = '(' . $Consulta_envio_stock->num_rows . ')';
            $resultado['tickets']['respuesta_envio_rows'] = $Consulta_envio_stock->num_rows;
        }
    }

    return $resultado;
}

/* * *****************************************************************************	
 *  			FUNCIONES REPETIDAS Y COMUNES EN OTROS MODULOS: CIERRES Y TPV	 		*
 * ****************************************************************************** */

function BuscarTienda($BDTpv, $idWeb) {
    $consulta = 'SELECT * FROM tiendas WHERE  idTienda =' . $idWeb;
    $unaOpc = $BDTpv->query($consulta);
    if (mysqli_error($BDTpv)) {
        $fila = $unaOpc;
    } else {
        $fila = $unaOpc->fetch_assoc();
    }
    //~ $fila['sql'] = $unaOpc;
    $fila['consulta'] = $consulta;
    return $fila;
}

function baseIva($BDTpv, $idticketst) {
    //@ tabla : ticketstIva
    //@ campo : idticketst
    //@ Objetivo:
    //Agrupamos por iva, para obtener sumIva, sumBase
    //se le pasa idtickets, e iva, para recoger sum(importeIva) y suma(totalbase)
    //seria idtickets de ticketstIva es la relacion de id de ticketst, porque 2 usuarios pueden tener mismo NumTicket.


    $sql = 'SELECT SUM(`importeIva`) AS importeIva, SUM(`totalbase`) AS importeBase, iva '
            . ' FROM `ticketstIva` '
            . ' WHERE `idticketst` IN (' . $idticketst . ') GROUP BY `iva`';
    $resp = $BDTpv->query($sql);
    $resultado = array();
    if ($resp->num_rows > 0) {
        $i = 0;
        while ($fila = $resp->fetch_assoc()) {
            $resultado['items'][$i] = $fila;
            $i++;
        }
        $resultado['sql'] = $sql;
    } else {
        $resultado = 0;
    }

    return $resultado;
}

function BusquedaClientes($busqueda, $BDTpv, $tabla) {
    // @ Objetivo es buscar los clientes 
    // @ Parametros
    // 	$busqueda --> Lo que vamos a buscar
    // 	$BDTpv--> Conexion
    //	$tabla--> tabla donde buscar.
    // Buscamos en los tres campos... Nombre, razon social, nif
    $resultado = array();
    $buscar1 = 'Nombre';
    $buscar2 = 'razonsocial';
    $buscar3 = 'nif';
    $sql = 'SELECT idClientes, nombre, razonsocial, nif  FROM ' . $tabla . ' WHERE ' . $buscar1 . ' LIKE "%' . $busqueda . '%" OR '
            . $buscar2 . ' LIKE "%' . $busqueda . '%" OR ' . $buscar3 . ' LIKE "%' . $busqueda . '%"';
    $res = $BDTpv->query($sql);

    //compruebo error en consulta
    if (mysqli_error($BDTpv)) {
        $resultado['consulta'] = $sql;
        $resultado['error'] = $BDTpv->error_list;
        return $resultado;
    }

    $arr = array();
    $i = 0;
    //fetch_assoc es un boleano..
    while ($fila = $res->fetch_assoc()) {
        $arr[$i] = $fila;

        $resultado['datos'][0] = $fila;
        $resultado['datos'] = $arr;
        $i++;
    }
    return $resultado;
}

function htmlClientes($busqueda, $dedonde, $clientes = array()) {
    // @ Objetivo:
    // Montar el hmtl para mostrar con los clientes si los hubiera.
    // @ parametros:
    // 		$busqueda -> El valor a buscar,aunque puede venir vacio.. 
    //		$dedonde  -> Nos indica de donde viene. (tpv,cerrados,cobrados)
    $resultado = array();
    $n_dedonde = 0;
    $resultado['encontrados'] = count($clientes);

    $resultado['html'] = '<label>Busqueda Cliente en ' . $dedonde . '</label>'
            . '<input id="cajaBusquedacliente" name="valorCliente" placeholder="Buscar"'
            . 'size="13" data-obj="cajaBusquedacliente" value="' . $busqueda
            . '" onkeydown="controlEventos(event)" type="text">';

    if (count($clientes) > 10) {
        $resultado['html'] .= '<span>10 clientes de ' . count($clientes) . '</span>';
    }
    $resultado['html'] .= '<table class="table table-striped"><thead>'
            . ' <th></th>' //cabecera blanca para boton agregar
            . ' <th>Nombre</th>'
            . ' <th>Razon social</th>'
            . ' <th>NIF</th>'
            . '</thead><tbody>';
    if (count($clientes) > 0) {
        $contad = 0;
        foreach ($clientes as $cliente) {
            $razonsocial_nombre = $cliente['nombre'] . ' - ' . $cliente['razonsocial'];
            $datos = "'" . $cliente['idClientes'] . "','" . addslashes(htmlentities($razonsocial_nombre, ENT_COMPAT)) . "'";
            $resultado['html'] .= '<tr class="FilaModal" id="Fila_'
                    . $contad . '" onclick="escribirClienteSeleccionado(' . $datos . ",'" . $dedonde . "'" . ');">'
                    . '<td id="C' . $contad . '_Lin" >'
                    . '<input id="N_' . $contad . '" name="filacliente" data-obj="idN" onkeydown="controlEventos(event)" type="image"  alt="">'
                    . '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
                    . '<td>' . htmlspecialchars($cliente['nombre'], ENT_QUOTES) . '</td>'
                    . '<td>' . htmlentities($cliente['razonsocial'], ENT_QUOTES) . '</td>'
                    . '<td>' . $cliente['nif'] . '</td>'
                    . '</tr>';
            $contad = $contad + 1;
            if ($contad === 10) {
                break;
            }
        }
    }
    $resultado['html'] .= '</tbody></table>';
    // Ahora generamos objetos de filas.
    // Objetos queremos controlar.
    return $resultado;
}

function RegistrarRestaStock($BDTpv, $id) {
    // @ Objetivo:
    // Registrar aquellos tickets que hemos ya descontado stock en la web.
    $resultado = array();
    //~ if ($respuesta_servidor['estado'] === 'Correcto') {
        //~ $respuesta_servidor['registro_cambiados'] = 'Registros cambiados ' . $respuesta_servidor['registro_cambiados'];
    //~ }
    $sql = 'INSERT INTO `importar_virtuemart_tickets`(`idTicketst`, `Fecha`, `estado`, `respuesta`) VALUES (' . $id . ',now(),"' . $respuesta_servidor['estado'] . '","' . $respuesta_servidor['registro_cambiados'] . '")';

    $BDTpv->query($sql);
    if (mysqli_error($BDTpv)) {
        $resultado['consulta'] = $sql;
        $resultado['error'] = $BDTpv->error_list;
        error_log(' Rotura en funcion RegistrarRestaSoctk funcion.php de mod_tpv linea 1034');
        error_log($BDTpv->error_list);
        // Rompemos programa..
        //exit();
    } else {
        // Enviamos datos que cuantos registros fueron añadidos o modificados por cada consulta..
        // aunque no lo utilizamos.
        $resultado['estado'] = 'Correcto';
        $resultado['mensaje'] = 'Registrado correctamente en tabla importar_virtuemart_tickets';
    }
    return $resultado;
}

?>
