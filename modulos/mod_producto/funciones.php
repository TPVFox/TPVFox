<?php 


function obtenerProductos($BDTpv,$filtro) {
	// Function para obtener productos y listarlos
	//tener en cuenta el  paginado con parametros: $LimitePagina ,$desde,$filtro
	$resultado = array();
	//inicio paginacion filtro
	//para evitar repetir codigo
	$Controler = new ControladorComun; 
	$campoBD = 'articulo_name';

	$consulta = "SELECT a.*,  a.`idArticulo`, p.`pvpCiva` FROM `articulos` AS a "
				."LEFT JOIN `articulosPrecios` AS p "
				."ON p.`idArticulo` = a.`idArticulo`  ".$filtro;//$filtroFinal.$rango; 
	if ($ResConsulta = $BDTpv->query($consulta)){			
		while ($fila = $ResConsulta->fetch_assoc()) {
			$resultado[] = $fila;
		}
	}
	//~ $resultado['sql'] = $consulta;
	return $resultado;
}

//ver seleccionado en check listado en vista producto
function verSelec($BDTpv,$idProducto,$tabla,$idTienda){
	// Obtener datos de un id de producto.
	//PARAMETROS:
	//idProducto , id seleccionado en listaProductos es idArticulo en bbdd
	//tabla es articulos,	articulosPrecios
	//devolvemos :
	//todos los datos de las 2 tablas: articulos y articulosPrecios
	//razonsocial del proveedor de ese producto, 
		//si no existe proveedor le indicamos que 'No existe'
	$sqlProveedor=array();
	$consulta = 'SELECT a.*, prec.* FROM '. $tabla.' as a '
			.'  LEFT JOIN articulosPrecios as prec ON a.idArticulo= prec.idArticulo '
			.'  WHERE a.idArticulo ='.$idProducto.' AND '
			.'  prec.idArticulo='.$idProducto.' AND prec.idTienda= '.$idTienda;
	$fila= array();
	$unaOpc = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)) {
		$fila['error'] = 'Error en la consultar'.$BDTpv->errno;
	} else {
		if ($unaOpc->num_rows > 0){
			$fila = $unaOpc->fetch_assoc();
			if ($fila['idProveedor'] == 0){
				$fila['razonsocial'] = 'No existe';	
			} else { //coger razonsocial proveedor
				$sqlProveedor = 'SELECT razonsocial FROM proveedores WHERE idProveedor='.$fila['idProveedor'];
				$res = $BDTpv->query($sqlProveedor);
				if (mysqli_error($BDTpv)) {
					$fila['error2'] = 'Error en la consulta nombre proveedor'.$BDTpv->errno;
				} else {
					if ($res->num_rows > 0){
						$fila['razonsocial'] = $res->fetch_assoc();
					}					
				}
			}
		} else {
			$fila['error']= ' No se a encontrado producto';
		}
	}
	$fila['Nrow']= $unaOpc->num_rows;
	$fila['sql']=$sqlProveedor;
	$fila['consulta'] = $consulta;
	return $fila ;
	
}


function referenciasTiendas($BDTpv,$idArticulo){
	//idArticulo
	//tablas a consultar
	// articulosTiendas, 	articulosPrecios,	tiendas
	//Objetivo:
		//devolver array con datos ['ref']
		//idTienda,		crefTienda,		idVirtuemart,	estado
		//razonsocial de la tienda
		//pvpCiva, 		y 	tipoTienda
	
	
	$resultado=array();
	//~ $consulta = 'SELECT ati.*, prec.pvpCiva,  t.tipoTienda FROM `articulosTiendas` as ati '
			//~ .' LEFT JOIN articulosPrecios as prec ON prec.idTienda = ati.idTienda '
			//~ .' LEFT JOIN tiendas as t ON t.idTienda = ati.idTienda '
			//~ .' WHERE  ati.idArticulo= '.$idArticulo.' GROUP BY prec.idTienda';
			
			$consulta = 'SELECT ati.*, prec.pvpCiva,  t.tipoTienda , t.dominio FROM `articulosTiendas` as ati '
			.' LEFT JOIN articulosPrecios as prec ON prec.idTienda = ati.idTienda '
			.' LEFT JOIN tiendas as t ON t.idTienda = ati.idTienda '
			.' WHERE  ati.idArticulo= '.$idArticulo.' GROUP BY prec.idTienda';
	
	
	//consulta de articulosTiendas, referencias
	$resp = $BDTpv->query($consulta);
	if ($resp->num_rows > 0) {
		$i= 0;
		while($filaReferencias = $resp->fetch_assoc()) {
			$resultado['ref'][$i]['idTienda']=$filaReferencias['idTienda'];
			$resultado['ref'][$i]['cref']=$filaReferencias['crefTienda'];
			$resultado['ref'][$i]['idVirtu']=$filaReferencias['idVirtuemart'];			
			$resultado['ref'][$i]['estado']=$filaReferencias['estado'];
		
			$nombretienda=NombreTienda($BDTpv,$filaReferencias['idTienda']);
			$resultado['ref'][$i]['nombreTienda']= $nombretienda['razonsocial'];
			$resultado['ref'][$i]['pvpCiva']=$filaReferencias['pvpCiva'];
			$resultado['ref'][$i]['tipoTienda']= $filaReferencias['tipoTienda'];
			$resultado['ref'][$i]['dominio']=$filaReferencias['dominio'];
			$i++;
		}
	}
	
	$resultado['consulta']=$consulta;
	return $resultado ;
}

function codigosBarras($BDTpv,$idArticulo){
	//idArticulo
	//tablas a consultar
	//articulosCodigoBarras
	//por ultimo tambien devolvemos:
		//codBarras de ese articulo
	$resultado=array();
	$sqlCodBarras = 'SELECT * FROM articulosCodigoBarras WHERE idArticulo='.$idArticulo;
	$resBarras= $BDTpv->query($sqlCodBarras);
	if ($resBarras->num_rows>0){
		$x=0;
		while($fila =$resBarras->fetch_assoc()){
			$resultado['codigos'][$x]['codBarras'] = $fila['codBarras'];
			$x++;
		}
	} else{
		//no hay codigos de barras
		$resultado['codigos']='';
	}
	$resultado['sqlBarras']=$sqlCodBarras;
	return $resultado ;
}


function NombreTienda($BDTpv,$idTienda){
	//idTienda 
	//objetivo:
		//devolver nombre de la tienda del idTienda.
	$consulta = 'SELECT * FROM tiendas WHERE idTienda = '.$idTienda;
	$unaOpc = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)) {
		$fila['error'] = 'Error en la consultar'.$BDTpv->errno;
	} else {
		if ($unaOpc->num_rows > 0){
			$fila = $unaOpc->fetch_assoc();
		} else {
			$fila['error']= ' No se a encontrado nombre de tienda';
		}
	}
	$fila['Nrow']= $unaOpc->num_rows;
	$fila['consulta'] = $consulta;
	return $fila ;
}

function nombreFamilias($BDTpv,$idArticulo){
	//idArticulo  
	// tablas a consultar: familias y articulosFamilias
	//objetivo
		//conseguir el nombre de las familias de ese articulo.
	$consulta= 'SELECT f.*, artfam.* FROM `familias` as f '
			.' LEFT JOIN articulosFamilias as artfam ON f.idFamilia = artfam.idFamilia '
			.' WHERE artfam.idArticulo= '.$idArticulo;
			
	$unaOpc = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)) {
		$fila['error'] = 'Error en la consultar'.$BDTpv->errno;
	} else {
		if ($unaOpc->num_rows > 0){
			$i=0;
			while($res = $unaOpc->fetch_assoc()){
				$fila['familias'][$i]['nombreFam']=$res['familiaNombre'];
				$i++;
			}
		} else {
			$fila['error']= ' No se a encontrado nombre de familia';
		}
	}
	$fila['Nrow']= $unaOpc->num_rows;
	$fila['consulta'] = $consulta;
	return $fila ;
}

function htmlCodigoBarrasVacio($cont){
	//creo caja de codigo de barras vacia
	$cont=$cont+1;
	$nuevaFila = '<tr>';
	$nuevaFila .= '<td><input type="text" id="codBarras" name="codBarras_'.$cont.'" value=""></td>';
	$nuevaFila .= '<td><a id="eliminar" class="glyphicon glyphicon-trash" onclick="eliminarCodBarras(this)"></a></td>'; 		
	$nuevaFila .= '</tr>';
	
	return $nuevaFila;
}

// Función que selecciona los ivas que no son el iva principal
// Por un lado recibe el IVA que tiene por defecto un producto y busca los que no coincidan
function ivasNoPrincipal($BDTpv, $iva){
	$consulta = 'SELECT * FROM iva WHERE iva <> '.$iva;
	if ($ResConsulta = $BDTpv->query($consulta)){			
		while ($fila = $ResConsulta->fetch_assoc()) {
			$resultado[] = $fila;
		}
	}
	return $resultado;
}

// Modificar un producto, recoge los datos del formulario post y los va actualizando segun las tablas que corresponda
function modificarProducto($BDTpv, $datos, $tabla){
	$resultado = array();
	$id=$datos['idProducto'];
	$nombre=$datos['nombre'];
	$coste=$datos['coste'];
	$beneficio=$datos['beneficio'];
	$iva=$datos['iva'];
	$pvpCiva=$datos['pvpCiva'];
	$pvpSiva=$datos['pvpSiva'];
	$referencia=$datos['referencia'];
	$tienda=$datos['idTienda'];
	// Montar un array con los las claves del array datos
	$keys=array_keys($datos);
	$codBarras = [];
	// Se va recorriendo  
	foreach($keys as $key){
		// Los que coincidan con el campo cod quiere decir que es un codigo de barras y se añaden al array codBarras[]
		$nombre1="cod";
		if (strpos($key, $nombre1)>-1){
			if ($datos[$key]<>""){
				$codBarras[] = '('.$id.',"'.$datos[$key].'")';
			}
		}
	}
	$stringCodbarras = implode(',',$codBarras);
	//Fecha y hora del sistema
	$fechaMod=date("Y-m-d H:i:s");
	$sql='UPDATE '.$tabla.' SET articulo_name="'.$nombre.'", costepromedio='.$coste.', beneficio='.$beneficio.' , iva ='.$iva.', fecha_modificado="'.$fechaMod.'" WHERE idArticulo='.$id;
	$sql2='UPDATE articulosPrecios SET pvpCiva='.$pvpCiva.', pvpSiva='.$pvpSiva.' WHERE idArticulo='.$id  ;
	$sql3='DELETE FROM articulosCodigoBarras where idArticulo='.$id;
	$sql5='UPDATE articulosTiendas set crefTienda ="'.$referencia.'" WHERE  idArticulo='.$id.' and idTienda='.$tienda;
	$sql4='INSERT INTO articulosCodigoBarras (idArticulo, codBarras) VALUES '.$stringCodbarras;
	$consulta = $BDTpv->query($sql);
	$consulta = $BDTpv->query($sql2);
	$consulta = $BDTpv->query($sql3);
	$consulta = $BDTpv->query($sql4);
	$consulta = $BDTpv->query($sql5);
	$resultado['sql'] =$sql;
	$resultado['sql2'] =$sql2;
	$resultado['sql3'] =$sql3;
	$resultado['sql4'] =$sql4;
	$resultado['sql6'] =$sql5;
	$resultado['sql5']=$keys;
	return $resultado;	
}
/*Función para añadir un producto nuevo*/
function añadirProducto($BDTpv, $datos, $tabla){
	$nombre=$datos['nombre'];
	$coste=$datos['coste'];
	$beneficio=$datos['beneficio'];
	$iva=$datos['iva'];
	$pvpCiva=$datos['pvpCiva'];
	$pvpSiva=$datos['pvpSiva'];
	$idProovedor=$datos['idProveedor'];
	$estado=$datos['estado'];
	$pvpCiva=$datos['pvpCiva'];
	$pvpSiva=$datos['pvpSiva'];
	$idTienda=$datos['idTienda'];
	$referencia=$datos['referencia'];
	
	//Fecha y hora del sistema 
	$fechaAdd=date("Y-m-d H:i:s");
	$keys=array_keys($datos);
	$codBarras = [];
	$sql='INSERT INTO '.$tabla.' (iva, idProveedor , articulo_name, beneficio, costepromedio, estado, fecha_creado) VALUES ("'.$iva.'" , "'.$idProovedor.'" , "'.$nombre.'", "'.$beneficio.'", "'.$coste.'", "'. $estado .'", "'.$fechaAdd.'")';
	$consulta = $BDTpv->query($sql);
	//Id del inster anterior 
	$idGenerado=$BDTpv->insert_id;
	foreach($keys as $key){
		// Los que coincidan con el campo cod quiere decir que es un codigo de barras y se añaden al array codBarras[]
		$nombre1="cod";
		if (strpos($key, $nombre1)>-1){
			if ($datos[$key]<>""){
				$codBarras[] = '('.$idGenerado.',"'.$datos[$key].'")';
			}
		}
	}
	$stringCodbarras = implode(',',$codBarras);
	$sql2='INSERT INTO articulosPrecios (idArticulo, pvpCiva , pvpSiva, idTienda ) VALUES ('.$idGenerado.', '.$pvpCiva.', '.$pvpSiva.' , '.$idTienda.')';
	if ($referencia == 0){
		$referencia="Sin ref";
	}
	$sql4='INSERT INTO articulosTiendas (idArticulo, idTienda, crefTienda) VALUES ('.$idGenerado.', '.$idTienda.', "'.$referencia.'")';
	$sql3='INSERT INTO articulosCodigoBarras (idArticulo, codBarras) VALUES '.$stringCodbarras;
	$consulta = $BDTpv->query($sql2);
	$consulta = $BDTpv->query($sql3);
		$consulta = $BDTpv->query($sql4);
	$resultado['sql'] =$sql;
		$resultado['sql1'] =$sql2;
			$resultado['sql2'] =$sql3;
			$resultado['sql4'] =$sql4;
	return $resultado;
}
/*Función que cuenta cuantos codigos de barras tiene un articulo*/
function ContarCodBarras($BDTpv, $idArticulo){
	$sql='SELECT count(*) from articulosCodigoBarras where idArticulo='.$idArticulo;
	$res = $BDTpv->query($sql);
	$items=$res->fetch_row();
	$resultado=$items[0];
	return $resultado;
}
/*Seleccionar los codigos de barras de un producto*/
function codBarrasProducto($BDTpv, $idArticulo){
	$sql='SELECT * from  articulosCodigoBarras where idArticulo='.$idArticulo;
	if ($ResConsulta = $BDTpv->query($sql)){			
		while ($fila = $ResConsulta->fetch_assoc()) {
			$resultado[] = $fila;
		}
	}
	return $resultado;
}
?>
