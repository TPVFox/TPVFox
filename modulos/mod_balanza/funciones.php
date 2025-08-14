<?php 
include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';
function htmlPanelDesplegable($num_desplegable, $titulo, $body, $idBalanza) {
    // @ Objetivo: Montar html de desplegable con mejor visualización (Bootstrap 3/4 compatible)
    $collapse = 'collapse' . $num_desplegable;
    // Mejorado: estructura más limpia, accesibilidad y flexibilidad
    $html = '<div class="panel panel-default" style="margin-bottom:20px;">'
        . '<div class="panel-heading" style="background:#f5f5f5;">'
        . '<h4 class="panel-title" style="font-size:16px;">'
        . '<a data-toggle="collapse" href="#' . $collapse . '" aria-expanded="true" aria-controls="' . $collapse . '" style="display:block;text-decoration:none;">'
        . '<span class="glyphicon glyphicon-menu-down" style="margin-right:8px;"></span>'
        . htmlspecialchars($titulo)
        . '</a>'
        . '</h4>'
        . '</div>'
        . '<div id="' . $collapse . '" class="panel-collapse collapse in">'
        . '<div class="panel-body" style="background:#fff;">'
        . '<div class="row" style="margin-bottom:10px;">'
        . '  <div class="col-xs-12 col-sm-6" style="padding-bottom:5px;">'
        . ($idBalanza != 0
        ? '    <button id="agregar" type="button" class="btn btn-success btn-xs" onclick="mostrarTablaPluAdd(' . $idBalanza . ')">'
        . '      <span class="glyphicon glyphicon-plus"></span> Añadir'
        . '    </button>'
        . '    <button id="mostrarTablaPlus" type="button" class="btn btn-default btn-xs" style="display:none;" onclick="toggleTablaPlus()">'
        . '      <span class="glyphicon glyphicon-eye-open"></span> Mostrar/Ocultar PLUs'
        . '    </button>'
        . '    <button id="agregarArtPeso" type="button" class="btn btn-info btn-xs" onclick="mostrarTablaArtPesoAdd(' . $idBalanza . ')">'
        . '      <span class="glyphicon glyphicon-plus"></span> Añadir Artículo Peso'
        . '    </button>'
        . '    <button id="mostrarTablaArtPeso" type="button" class="btn btn-default btn-xs" style="display:none;" onclick="toggleTablaArtPeso()">'
        . '      <span class="glyphicon glyphicon-eye-open"></span> Mostrar/Ocultar Artículos Peso'
        . '    </button>'
            : ''
        )
        . '  </div>'
        . '</div>'
        . '<div id="addPlu"></div>'
        . '<div id="addArticuloPeso"></div>'
        . $body
        . '</div>'
        . '</div>'
        . '</div>';
    return $html;
}

function htmlTablaPlus($plus, $id) {
    // Mejorar visualización tabla PLUs
    $CBalanza = new ClaseBalanza();
    $Secciones = $CBalanza->usaSecciones($id);
    $html = '<div class="table-responsive">'
        . '<table id="tPlus" class="table table-striped table-bordered table-hover tabla-filtrable" style="background:#fff;">'
        . '<thead class="thead-dark">'
        . '<tr>'
        . '<th>Plus</th>'
        . '<th>'
        . '<a id="btnModificarTodos" class="btn btn-primary btn-xs" onclick="toggleEditarTodos()">'
        . 'Modificar <span class="glyphicon glyphicon-pencil"></span>'
        . '</a>'
        . '<a id="btnGuardarTodos" class="btn btn-success btn-xs" style="display:none;" onclick="guardarTodos(' . $id . ')">'
        . 'Guardar <span class="glyphicon glyphicon-floppy-disk"></span>'
        . '</a>'
        . '</th>'
        . '</tr>'
        . '<tr style="background:#f5f5f5;">'
        . '<th>PLU</th>'
        . ($Secciones ? '<th>Sección</th>' : '')
        . '<th>idArticulo</th>'
        . '<th>Referencia</th>'
        . '<th>Nombre</th>'
        . '<th>PVP</th>'
        . '<th>Proveedor</th>'
        . '<th></th>'
        . '<th>Acciones '
        . '<button type="button" class="btn btn-default btn-xs toggle-filtros" style="margin-left:5px;">'
        . '<span class="glyphicon glyphicon-filter"></span> Filtros</button>'
        . '</th>'
        . '</tr>'
        // Fila de filtros (debe tener el mismo número de columnas)
        . '<tr class="filtros" style="display:none;">'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>';
    if ($Secciones) {
        $html .= '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>';
    }
    $html .=
        '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th></th>'
        . '<th></th>'
        . '</tr>'
        . '</thead>'
        . '<tbody>';
    if (count($plus) > 0) {
        foreach ($plus as $item => $valor) {
            $html .= htmlLineaPluEditable($valor, $id);
        }
    }
    $html .= '</tbody></table></div>';
    return $html;
}

// Nueva función para permitir edición de PLU y Tecla
function htmlLineaPluEditable($plu, $idBalanza) {
    $CBalanza = new ClaseBalanza();
    $Secciones = $CBalanza->usaSecciones($idBalanza);
    $imagen = '';
    if ($plu['tipo'] === 'peso') {
        $imagen = '<img src="../../css/img/balanza.png" title="Peso" alt="Peso">';
    }
    $nuevaFila = '<tr id="plu_' . $plu['idArticulo'] . '">'
        . '<td><input type="text" id="editPlu_' . $plu['idArticulo'] . '" value="' . $plu['plu'] . '" class="form-control input-sm" readonly style="width:70px;"></td>'
        . ($Secciones ? '<td><input type="text" id="editTecla_' . $plu['idArticulo'] . '" value="' . htmlspecialchars($plu['seccion']) . '" class="form-control input-sm" readonly style="width:70px;"></td>' : '')
        . '<td>' . $plu['idArticulo'] . '</td>'
        . '<td>' . htmlspecialchars($plu['crefTienda']) . '</td>'
        . '<td>' . htmlspecialchars($plu['articulo_name']) . '</td>'
        . '<td style="text-align:right;">' . number_format($plu['pvpCiva'], 2) . '</td>'
        . '<td>' . htmlspecialchars($plu['nombrecomercial']) . '</td>'
        . '<td style="text-align:center;">' . $imagen . '</td>'
        . '<td style="white-space:nowrap;">'
        . '<a id="modificar_' . $plu['idArticulo'] . '" class="glyphicon glyphicon-pencil" title="Modificar" style="margin-right:8px;cursor:pointer;" onclick="modificarPlu(' . $plu['idArticulo'] . ', ' . $idBalanza . ')"></a>'
        . '<a id="guardar_' . $plu['idArticulo'] . '" class="glyphicon glyphicon-floppy-disk" title="Guardar" style="display:none;margin-right:8px;cursor:pointer;" onclick="guardarPlu(' . $plu['idArticulo'] . ', ' . $idBalanza . ')"></a>'
        . '<a id="eliminar_' . $plu['idArticulo'] . '" class="glyphicon glyphicon-trash" style="color:#d9534f;cursor:pointer;" onclick="eliminarPlu(\'' . $plu['plu'] . '\', ' . $idBalanza . ')"></a>'
        . '</td>'
        . '</tr>';
    return $nuevaFila;
}

function htmlArticulosPeso($articulos, $idBalanza) {
    $html = '<div class="table-responsive">'
        . '<table id="tArticulosPeso" class="table table-striped table-bordered table-hover tabla-filtrable" style="background:#fff;">'
        . '<thead class="thead-dark">'
        // Fila de encabezados
        . '<tr style="background:#f5f5f5;">'
        . '<th style="text-align:center; vertical-align:middle;">ID Artículo</th>'
        . '<th style="text-align:center; vertical-align:middle;">Nombre</th>'
        . '<th style="text-align:center; vertical-align:middle;">Referencia</th>'
        . '<th style="text-align:center; vertical-align:middle;">Cod. Barras</th>'
        . '<th style="text-align:center; vertical-align:middle;">PVP C/Iva</th>'
        . '<th style="text-align:center; vertical-align:middle;">Proveedor</th>'
        . '<th style="text-align:center; vertical-align:middle;">Acciones '
        . '<button type="button" class="btn btn-default btn-xs toggle-filtros" style="margin-left:5px;">'
        . '<span class="glyphicon glyphicon-filter"></span> Filtros</button>'
        . '</th>'
        . '</tr>'
        // Fila de filtros (debe tener el mismo número de columnas)
        . '<tr class="filtros" style="display:none;">'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th></th>'
        . '</tr>'
        . '</thead>'
        . '<tbody>';
    if (!empty($articulos)) {
        foreach ($articulos as $valor) {
            $html .= htmlAñadirArticulo($valor, $idBalanza) . "\n";
        }
    } else {
        $html .= '<tr><td colspan="7" class="text-center text-muted" style="background:#fcfcfc;">No hay artículos de peso disponibles.</td></tr>' . "\n";
    }
    $html .= '</tbody></table></div>';
    return $html;
}

function htmlAñadirArticulo($articulo, $idBalanza) {
    $nuevaFila = '<tr>'
        . '<td style="text-align:center; vertical-align:middle;">' . $articulo['idArticulo'] . '</td>'
        . '<td style="vertical-align:middle;">' . htmlspecialchars($articulo['articulo_name'], ENT_QUOTES) . '</td>'
        . '<td style="vertical-align:middle;">' . htmlspecialchars($articulo['crefTienda'], ENT_QUOTES) . '</td>'
        . '<td style="vertical-align:middle;">' . htmlspecialchars($articulo['codBarras'], ENT_QUOTES) . '</td>'
        . '<td style="text-align:right; vertical-align:middle;">' . number_format($articulo['pvpCiva'], 2) . '</td>'
        . '<td style="vertical-align:middle;">' . htmlspecialchars($articulo['nombrecomercial'], ENT_QUOTES) . '</td>'
        . '<td style="text-align:center; vertical-align:middle;">'
        . '<a class="btn btn-success btn-xs" title="Añadir a balanza" onclick="addArticuloPeso(' . $articulo['idArticulo'] . ', ' . $idBalanza . ')">'
        . '<span class="glyphicon glyphicon-plus"></span> Añadir</a></td>'
        . '</tr>' . "\n";
    return $nuevaFila;
}
function htmlLineaPlu( $plu, $idBalanza){
    //@OBjetivo: imprimir las lineas de plus de una balanza con los datos de un articulo
    $imagen= '';
    if ($plu['tipo'] === 'peso'){
        $imagen='<img src="../../css/img/balanza.png" title="Peso" alt="Peso">';
    }
   $nuevaFila = '<tr id="plu_'.$plu['plu'].'">'
				. '<td><input type="hidden" id="idPlu_'.$plu['plu']
				.'" name="idPlu'.$plu['plu'].'" value="'.$plu['plu'].'">'
				.$plu['plu'].'</td>'
				
                .'<td>'.$plu['seccion'].'</td>'
                .'<td>'.$plu['idArticulo'].'</td>'
                .'<td>'.$plu['crefTienda'].'</td>'
                .'<td>'.$plu['articulo_name'].'</td>'
                .'<td>'.number_format($plu['pvpCiva'],2).'</td>'
                .'<td>'.$plu['nombrecomercial'].'</td>'
                .'<td>'.$imagen.'</td>'
                .'<td><a id="eliminar_'.$plu['plu']
				.'" class="glyphicon glyphicon-trash" onclick="eliminarPlu('."'".$plu['plu']."'".', '.$idBalanza.')"></a>'
				.'</td>'.'</tr>';
                
	return $nuevaFila;
}
function htmlAddPLU($seccion, $idBalanza){
    //@OBjetivo_: devolver html con los datos para poder añadir plu
    $style_none = ' ';
    if($seccion=='no'){
        $style_none = 'style="display:none"';
    }

    $html='<th colspan="5">'
            .'<div class="col-md-12">'
                .'<label>Plu:</label>'
				.'<input type="text" name="plu" id="plu" value="" >'
            .'</div>'
            .'<div class="col-md-12" '.$style_none.'>'
               .'<label>Tecla:</label>'
               .'<input type="text" name="seccionPlu" id="seccionPlu" value="" >';
    
    $html.='</div>'
            .'<div>'
                .'<label>Opciones de busqueda de los productos:</label>'
                .'<div class="col-md-1">'
                    .'<label>Id:</label>'
                    .'<input type="text" name="idArticulo" id="idArticulo" data-obj="cajaidArticulo" onkeydown="controlEventos(event)" value="" size="3">'
                .'</div>'
                .'<div class="col-md-5">'
                    .'<label>Nombre:</label>'
                    .'<input type="text" name="nombreProducto" id="nombreProducto" data-obj="cajanombreProducto" onkeydown="controlEventos(event)" value="" size="30">'
                .'</div>'
                .'<div class="col-md-2">'
                    .'<label>Referencia:</label>'
                    .'<input type="text" name="referencia" id="referencia" data-obj="cajareferencia" onkeydown="controlEventos(event)" value="" size="8">'
                .'</div>'
                .'<div class="col-md-2">'
                    .'<label>Cod Barras:</label>'
                    .'<input type="text" name="codBarras" id="codBarras" data-obj="cajacodBarras" onkeydown="controlEventos(event)" value="" size="8">'
                .'</div>'
                .'<div class="col-md-2">'
                    .'<label>Precio C/Iva:</label>'
                    .'<input type="text" name="precioConiva" id="precioConIva" value="" size="8">'
                .'</div>'
            .'</div>'
        .'<div>'
            .'<div class="col-md-4"><label></label>'
            .'<a class="btn btn-success" onclick="addPlu('.$idBalanza.')">Añadir</a>'
            .'</div>'
        .'</div>';
    return $html;
}
function camposBuscar($campo, $busqueda){
    //@ Objetivo:
    // devolver el string con el campo y busqueda preparado para el sql
    if($campo=='a.idArticulo'){
        $busqueda='a.idArticulo='.$busqueda;
    }else{

         // Limpio busqueda para evitar rotura en la consulta.
        $buscar = array(',', ';', '(', ')', '"', "'");
        $sustituir = array(' , ', ' ; ', ' ( ', ' ) ', ' ', ' ');
        $string = str_replace($buscar, $sustituir, trim($busqueda));
        $palabras = explode(' ', $string); //array de varias palabras, si las hay..

        $likes = array();

        foreach ($palabras as $key => $palabra) {
            if (trim($palabra) !== '') {
                $likes[] = $campo . ' LIKE "%' . $palabra . '%" ';
            } else {
                unset($palabras[$key]);
            }
        }
        $resultado['palabras'] = $palabras;

        //si vuelta es distinto de 1 es que entra por 2da vez busca %likes%	
        $busqueda = implode(' and ', $likes);;     
    }
    return $busqueda;
}

function modalProductos($busqueda, $productos, $campoAbuscar){
    //@OBjetivo: devolver html con los datos del modal
    $resultado = array();
	$resultado['encontrados'] = count($productos);
    $resultado['html'] =  "<script type='text/javascript'>
			 ".
			 "cajaBusquedaProducto.parametros.campo="."'".$campoAbuscar."';
			idN.parametros.campo.__defineSetter__ ="."'".$campoAbuscar."';
			</script>";
	$resultado['html'] .= '<label>Busqueda Producto </label>';
	$resultado['html'] .= '<input id="cajaBusquedaProducto" name="valorProducto" placeholder="Buscar"'.
				'size="13" data-obj="cajaBusquedaProducto" value="'.$busqueda.'"
				 onkeydown="controlEventos(event)" type="text">';
  
    if (isset($productos)){
		$resultado['html'] .= '<span>10 productos de '.count($productos).'</span>';
	
        $resultado['html'] .= '<table class="table table-striped"><thead>'
        . ' <th></th> <th>id</th><th>Nombre</th><th>Referencia</th></thead><tbody>';
        if (count($productos)>0){
            $contad = 0;
            foreach ($productos as $producto){  
                $resultado['html'] .= '<tr id="Fila_'.$contad.'" class="FilaModal" onclick="seleccionProductoModal('.
                $producto['idArticulo'].",'".$producto['articulo_name']."','".$producto['crefTienda']."','".$producto['codBarras']."','".$producto['pvpCiva']."'".');" >';
            
                $resultado['html'] .= '<td id="C'.$contad.'_Lin" >';
                $resultado['html'] .= '<input id="N_'.$contad.'" name="filaProducto" data-obj="idN" onkeydown="controlEventos(event)" type="image"  alt="">'
                . '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
                .'<td>'.$producto['idArticulo'].'</td>'
                . '<td>'.htmlspecialchars($producto['articulo_name'],ENT_QUOTES).'</td>'
                . '<td>'.htmlentities($producto['crefTienda'],ENT_QUOTES).'</td>'
                .'</tr>';
                $contad = $contad +1;
                if ($contad === 10){
                    break;
                }
			
            }
        }
        $resultado['html'] .='</tbody></table>';
    } else {
        // No encontro resultado por lo que mostramos advertencia.
        $resultado['html'] .='<div class="alert alert-info">No hay resultado para esta busqueda, prueba otra.</div> ';

    }
	
	return $resultado;
}

function htmlDatosListadoPrincipal($datosBalanza, $datosplu, $opcionSelect){
    //Objetivo: devolver html con los datos de una balanza y plus para el listado principal
    $resultado=array();
    $html="";
    $htmlBalanza="";
    $htmlBalanza.='<p><b>Nombre de balanza: </b>'.$datosBalanza['nombreBalanza'].'</p>
    <p><b>Modelo de Balanza: </b>'.$datosBalanza['modelo'].'</p>
    <p><label>Filtrar por: </label><select id="filtroBalanza" >';
    if($opcionSelect=='a.plu'){
        $htmlBalanza.='<option value="a.plu" onclick="mostrarDatosBalanza('.$datosBalanza['idBalanza'].')">PLU</option>
            <option value="a.seccion" onclick="mostrarDatosBalanza('.$datosBalanza['idBalanza'].')">TECLA</option>
        </select></p>';
    }else{
         $htmlBalanza.='<option value="a.seccion" onclick="mostrarDatosBalanza('.$datosBalanza['idBalanza'].')">TECLA</option>
        <option value="a.plu" onclick="mostrarDatosBalanza('.$datosBalanza['idBalanza'].')">PLU</option>
        </select></p>';
    }
    // Encabezados de la tabla principal (filtrable)
    $html .= '<div class="table-responsive">'
        . '<table id="tablaListadoPrincipal" class="table table-striped table-bordered table-hover tabla-filtrable" style="background:#fff;">'
        . '<thead class="thead-dark">'
        . '<tr style="background:#f5f5f5;">'
        . '<th><b>PLU</b></th>'
        . '<th><b>Tecla</b></th>'
        . '<th><b>idArticulo</b></th>'
        . '<th><b>Descripción</b></th>'
        . '<th><b>Referencia</b></th>'
        . '<th><b>PVP</b></th>'
        . '<th><b>Tipo</b> '
        . '<button type="button" class="btn btn-default btn-xs toggle-filtros" style="margin-left:5px;">'
        . '<span class="glyphicon glyphicon-filter"></span> Filtros</button>'
        . '</th>'
        . '</tr>'
        // Fila de filtros (debe tener el mismo número de columnas)
        . '<tr class="filtros" style="display:none;">'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th><input type="text" class="form-control input-sm filtro-col" placeholder="Filtrar"></th>'
        . '<th></th>'
        . '</tr>'
        . '</thead>'
        . '<tbody>';
        $indice=0;
    foreach ($datosplu as $plu){
        $espacio="";
        
        $imagen= '';
        $class = '';
        if ($plu['tipo'] === 'peso'){
            $imagen='<img src="../../css/img/balanza.png" title="Peso" alt="Peso">';
        }
        if (isset($plu['duplicado'])){
            $class='class="alert alert-danger" title="Producto duplicado en esta balanza"';
        }
        $html.='<tr '.$class.'>
            <td>'.$plu['plu'].'</td>
            <td>'.$plu['seccion'].'</td>
            <td>'.$plu['idArticulo'].'</td>
            <td>'.$plu['articulo_name'].'</td>
            <td>'.$plu['crefTienda'].'</td>
            <td>'.number_format($plu['pvpCiva'],2).'</td>
            <td>'.$imagen.'</td>
        </tr>';
        // Comprobamos que va correlatio plu, pero esto es valido plu, pero para seccion ???
        $sigIndice=$indice+1;
        if(isset($datosplu[$sigIndice])){
            // Si hay plu no utilizados mostramos advertencia.
            $resta=$datosplu[$sigIndice]['plu']-$datosplu[$indice]['plu'];
            if($resta>1){
                 $html.='<tr><td COLSPAN="4" class="warning">Faltan números entre el anterior y el siguiente</td></tr>';
            }
        }
        $indice++;
       
    }
    $resultado['html']=$html;
    $resultado['htmlBalanza']= $htmlBalanza;
    return $resultado;
}
function htmlTecla($seccion){
    //@Objetivo: html con las opciones de la seccion
    if($seccion=="si"){
        $html ='<option value="si" selected="selected">Si</option>';
  
    }else{
        $html ='<option value="si">Si</option>';
    }
    if($seccion=="no"){
         $html .='<option value="no" selected="selected">No</option>';
    } else {
         $html .='<option value="no">No</option>';

    }
    return $html;
}
?>
