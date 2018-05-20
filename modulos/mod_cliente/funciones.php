<?php 

include_once "../../clases/FormasPago.php";
$CFormasPago=new FormasPago($BDTpv);

function obtenerClientes($BDTpv,$filtro) {
	// Function para obtener clientes y listarlos

	$clientes = array();
	$consulta = "Select * from clientes ".$filtro;//.$filtroFinal.$rango; 
	//$clientes['NItems'] = $Resql->num_rows;
	$i = 0;
	if ($Resql = $BDTpv->query($consulta)){			
		while ($fila = $Resql->fetch_assoc()) {
			$clientes[] = $fila;
		}
	}

	//$clientes ['consulta'] = $consulta;
	return $clientes;
}

function verSelec($BDTpv,$idSelec,$tabla){
	//ver seleccionado en check listado	
	// Obtener datos de un id de usuario.
	$where = 'idClientes = '.$idSelec;
	$consulta = 'SELECT * FROM '. $tabla.' WHERE '.$where;
	
	$unaOpc = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)) {
		$fila['error'] = 'Error en la consulta '.$BDTpv->errno;
	} else {
		if ($unaOpc->num_rows > 0){
			$fila = $unaOpc->fetch_assoc();
		} else {
			$fila['error']= ' No se a encontrado cliente';
		}
	}
	$fila['Nrow']= $unaOpc->num_rows;
	$fila['sql'] = $consulta;
	return $fila ;
}

function insertarCliente($datos,$BDTpv,$tabla){
	$resultado = array();
	$nombre = $datos['nombre'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	$razonsocial =$datos['razonsocial'];
	$nif = $datos['nif'];
	$direccion = $datos['direccion']; 
	
	$telefono=$datos['telefono'];
	$movil = $datos['movil'];
	$fax = $datos['fax'];
	$email =$datos['email'];
	$estado =$datos['estado'];
	
	//comprobar que razonsocial NO EXISTE al crear un nuevo usuario
	$buscarCliente = 'SELECT * FROM clientes WHERE razonsocial= "'.$razonsocial.'"';
	$res = $BDTpv->query($buscarCliente);
	$numClientes = mysqli_num_rows($res); //num usuarios que existen con ese nombre
	if ($numClientes === 1){
		// Si entro es porque existe ya el usuario o no mando nombre usuario
		$resultado['error'] = 'error';
		//razon social YA EXISTE	
	} else {
		$consulta = 'INSERT INTO '.$tabla.'( Nombre, razonsocial, nif, direccion, telefono, movil, fax, email, estado ) VALUES ("'
			.$nombre.'" , "'.$razonsocial.'" , "'.$nif.'" , "'.$direccion.'" , "'.$telefono.'" , "'.$movil.'" , "'.$fax.'" , "'.$email.'" , "'.$estado.'")';
		if ($BDTpv->query($consulta) === true){
			$resultado['id'] = $BDTpv->insert_id;	//crea id en bbddd 
		} else {
			// Quiere decir que hubo error en insertar en clientes
			$resultado['error'] = 'Error en Insert de cliente Numero error:'.$BDTpv->errno;
			$resultado['sql'] = $consulta;

		}
		
	}
	//~ $resultado['consulta'] =$InsertSlq;
	return $resultado;
}


//parametros: 
//datos array de post ,importante NAME de los inputs
//BDTpv conexion bbdd tpv
//tabla en la que trabajar usuarios
//idSelecc , usuario concreto a modificar , check seleccionado en listaUsuarios
function modificarCliente($datos,$BDTpv,$tabla){
	
	//~ echo 'modificar usuario';
	$resultado = array();	
	$nombre = $datos['nombre'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	$razonsocial =$datos['razonsocial'];
	$nif = $datos['nif'];
	$direccion = $datos['direccion']; 
	
	$idCliente =$datos['idCliente'];
	$telefono=$datos['telefono'];
	$movil = $datos['movil'];
	$fax = $datos['fax'];
	$email =$datos['email'];
	$estado =$datos['estado'];
	
	$sql ='UPDATE '.$tabla.' SET Nombre ="'.$nombre.'" , razonsocial ="'.$razonsocial.'" , nif ="'
			.$nif.'" , direccion = "'.$direccion.'" , telefono ="'.$telefono.'" , movil ="'.$fax.'" , email ="'.$email.'" , estado = "'
		.$estado.'" WHERE idClientes='.$idCliente;
	
	
	$consulta = $BDTpv->query($sql);
	
	//$resultado['consulta'] =$sql;
	$resultado['sql'] =$sql;

	return $resultado;
}


function htmlProductos($total_productos,$productos,$busqueda_por,$campoAbuscar,$busqueda){
	// @ Objetivo 
	// Obtener listado de produtos despues de busqueda.
	// @ Parametros 
	// 		$total_productos -> (int) Cantidad total de registros de la consulta.
	//								Si enviamos -1 quiere decir que no se conto los posibles registros.
	
	$resultado = array();
	if ($campoAbuscar === 'idArticulo'){
				$campo_mostrar = 'crefTienda';
	}
	if ($campoAbuscar === 'Referencia'){
		$campo_mostrar = 'cref_tienda_principal';
	}
	if ($campoAbuscar === 'Descripcion'){
		$campo_mostrar = ''; // Este campo realmente no mostramos
	}
	if ($campoAbuscar === 'Codbarras'){
		$campo_mostrar = 'codBarras'; // Este campo realmente no mostramos
	}
	$html = '<label>Busqueda por '.$busqueda_por.'</label>'
			.'<input id="cajaBusqueda" name="'
			.$campoAbuscar.'" placeholder="Buscar" data-obj="cajaBusquedaproductos" size="13" value="'
			.$busqueda.'" onkeydown="controlEventos(event)" type="text">';
	if (count($productos)>10){
		if ($total_producto ===-1){
			// Quiere decir que no se sabe realmente cuantos pueden ser la busqeuda completa.
			$tproductos = '* ';
		} else {
			$tproductos = $total_productos;
		}
		$html .= '<span>10 productos de '.$tproductos.'</span>';
	}
	if ($total_productos === 0){
			// Hay que tener en cuenta tambien si la caja tiene datos ya que sino no es lo mismo.
			if (strlen($busqueda) === 0 ) {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$html .= '<div class="alert alert-info">'
						.' <strong>Buscar!</strong> Pon las palabras para buscar productos que consideres.</div>';
			} else {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$html .= '<div class="alert alert-warning">'
						.' <strong>Error!</strong> No se encontrado nada con esa busqueda.</div>';
			}
	} else {
	
		$html.= '<table class="table table-striped"><thead>'
				.'<th></th>'
				.'</thead><tbody>';
		
		$contad = 0;
		foreach ($productos as $producto){
				$datos = 	"'".addslashes(htmlentities($producto['articulo_name'],ENT_COMPAT))."','"
						.number_format($producto['iva'],2)."','".$producto['pvpSiva']."','"
						.number_format($producto['pvpCiva'],2)."',".$producto['idArticulo'];
			$Fila_N = 'Fila_'.$contad;
			$html .= '<tr class="FilaModal" id="'.$Fila_N.'"  onclick="escribirProductoSeleccionado('
					.$datos.');">'
					.' <td id="C'.$contad.'_Lin">'
					.'  <input id="N_'.$contad.'" name="filaproducto"  data-obj="idN"  onkeydown="controlEventos(event)" type="image" alt=""><span class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			$c_m = '';
			if ($campo_mostrar !==''){
				$c_m = htmlspecialchars($producto[$campo_mostrar], ENT_QUOTES);
			}
			$html .=' <td>'.$c_m.'</td>'
					. '<td>'.htmlspecialchars($producto['articulo_name'], ENT_QUOTES).'</td>'
					.' <td>'.number_format($producto['pvpCiva'],2).'</td>'
					.' <td>'.number_format($producto['pvpSiva'],2).'</td>'
					.'</tr>';
			$contad = $contad +1;
			if ($contad === 10){
				break;
			}
			
		}
		$html .='</tbody></table>';
	}
	$resultado['html'] = $html;
	$resultado['encontrados'] =$total_productos;
	$resultado['campo'] = $campoAbuscar;
	
	return $resultado;
// Funcion para obtener html de busqueda de producto. ( Lo ideal seria hacer fuera un plugin  )
// Para un correcto funcionamiento de la caja busqueda tenemos que tener creado cajaBusquedaproductos en xml 
// Ejemplo de configuracion input en xml 
// 		<caja_input>
//			<nombre id_input="cajaBusqueda">cajaBusquedaproductos</nombre>
//			<teclas>
//				<action tecla="13">buscarProducto</action>
//			</teclas>
//			<parametros>
//				<parametro nombre="dedonde">popup</parametro>
//				<parametro nombre="campo"></parametro>  
//			</parametros> 
//			<before>
//				<estado>Si</estado>
//			</before>
//		</caja_input>
// Tambien las clases de N_ son necesarias...


}

?>
