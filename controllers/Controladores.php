
<?php
/*	 Es crear un controlador de consultas comunes para varios modulos.
 * */


class ControladorComun 
{
     private $BDTpv; // Conexion Base Datos
     
     
     function InfoTabla ($Bd,$tabla,$tipo_campo = 'si'){
		// Funcion que nos proporciona informacion de la tabla que le indicamos
		/* Nos proporciona informacion como nombre tabla, filas, cuando fue creada, ultima actualizacion .. y mas campos interesantes:
		 * Ejemplo de print_r de virtuemart_products 
		 * 		[Name] => virtuemart_products  // Normal ya que el prefijo ....
		 *    	[Rows] => Numero registros  // ESTE ES IMPORTANTE, el que analizamos inicialmente.
		 *    	[Create_time] => 2016-10-31 18:23:52 // Normal ya que nunca coincidira... se crearía fechas distintas.
		 *    	[Update_time] => 2016-10-31 20:46:35 // Lo recomendable que la hora Update ser superior en nuestra BD , pero no siempre será
		*/
		$fila = array();
		$consulta = 'SHOW TABLE STATUS WHERE `name`="'.$tabla.'"';
		$Queryinfo = $Bd->query($consulta);
		// Hay que tener en cuenta que no produce ningún error... 
		$Ntablas = $Bd->affected_rows   ;
		if ($Ntablas == 0) {
			$fila ['error'] = 'Error tabla no encontrada - '.$tabla;
		} else {
			$fila['info'] = $Queryinfo->fetch_assoc();
		}
		if (!isset($fila['error'])){
			$campos = array();
			$sqlShow = 'SHOW COLUMNS FROM '.$tabla;
			$fila['consulta_campos'] = $sqlShow;
			if ($res=$Bd->query($sqlShow)) {
				while ($dato_campo = $res->fetch_row()) {
					if ($tipo_campo ==='si'){
						// Obtenemos nombre campo y tipo de campo.
						$campos[] = $dato_campo[0].' '.$dato_campo[1];
					} else {
						$campos[] = $dato_campo[0];
					}
				}
				$fila['campos'] = $campos;
			} else{
				// Si NO existe o no sale mal enviamos un error.
				$fila['campos'] = $Bd->error;
			} 
		}
		$fila['consulta_info'] = $consulta;
			
		return $fila ;
		
	}
	
	
    
   
	function VerConexiones ($Conexiones){
		// Objetivo comprobar si las conexiones son correctas.
		$htmlError = '';
		foreach ($Conexiones as $conexion) {
				if ($conexion['conexion'] == 'Error'){
					$htmlError .= 	'Error de conexion en la BD '.$conexion['NombreBD'].'<br/>'
									.'¡¡Revisa configuracion! <br/>';
				}
		}
		return $htmlError ;
	}
	
	
	function EliminarTabla($nombretabla, $BD) 
	{
		//Objetivo tener una funcion para eliminar contenido de una tabla.
		$consulta = "Delete from " . $nombretabla;
		$Queryinfo = $BD->query($consulta);
		// Anotamos cuantos registros eliminamos. 
		$NRegiEliminados = $BD->affected_rows   ;
		return $NRegiEliminados;
	}

	function contarRegistro($BD,$nombretabla,$whereC='') {
		/* Esta funcio esta repetida en Consultas de modulo de importar
		 * por lo que deberíamos eliminarla de consultas
		 * y implementar está funcion, textear que funciona el modulo importar..... 
		 * 
		 * */
		// Funcion para contar registros de una tabla.
		$array = array();
		$consulta = "SELECT * FROM ". $nombretabla.' '.$whereC;
		$consultaContador = $BD->query($consulta);
		if ($BD->query($consulta)) {
			$array['NItems'] = $consultaContador->num_rows;
		} else {
			// Quiere decir que hubo error en la consulta.
			$array['consulta'] = $consulta;
			$array['error'] = $BD->error;
		}
		$array['sql']=$consulta;
		return $array['NItems'];
		//~ return $array;
	}
	
	
	function paginacionFiltroBuscar($filtro,$LimitePagina,$desde,$campoBD,$campo2BD='',$campo3BD=''){
		//HARIA funcion para no repetir codigo de paginacion y campo busqueeda
		//le pasaria $campoBD para likes de palabras
		//aqui se monta el limite de paginas y el explode de palabras 
		//PARAMETROS:
			//filtro == palabras a buscar
			//limitePagina = 40 ejemplo
			//desde = 0 inicialmente
			//campoBD = campos de bbdd , ej. articulo_name
			//campo2BD = segundo campo de bbdd a buscar
		//DEVUELVO: 
			//un array['rango']=limit 40  
					//['filtro'] =Where likes
		$resultado = array();
		$buscar='';
		$rango= '';
		$filtroFinal='';
		//si existe filtro, palabras a buscar
		//con implode creo un array de palabras para buscarlas por like
		if ($filtro !== ''){
			$palabras=array();
		
			$palabras = explode(' ',$filtro); // array de varias palabras, si las hay..
			//para buscar por palabras separadas
			foreach($palabras as $palabra){
				if ($campo2BD !== ''){
					$likes[] =  '`'.$campoBD.'` LIKE "%'.$palabra.'%" or `'.$campo2BD.'` LIKE "%'.$palabra.'%" ';
				} else {
					$likes[] =  '`'.$campoBD.'` LIKE "%'.$palabra.'%" ';
				}
				if ($campo3BD !== '') {
					$likes[] =  '`'.$campoBD.'` LIKE "%'.$palabra.'%" or `'.$campo2BD.'` LIKE "%'.$palabra.'%" or `'.$campo3BD.'` LIKE "%'.$palabra.'%" ';
					
				}
			}
			$buscar = implode(' and ',$likes).')';
			$filtroFinal = ' WHERE ('.$buscar;
			
		}	
		if ($LimitePagina > 0 ){
			$rango .= " LIMIT ".$LimitePagina." OFFSET ".$desde;
		}
		// datos a devolver serian string $rango, string $filtroFinal
		//fin de paginacion parametros necesarios y de campo de busqueda
		$resultado['rango']=$rango;
		$resultado['filtro']=$filtroFinal;
		
		return $resultado;
	}
	
	function ConstructorLimitOffset($inicio,$final){
		// @Objetivo: 
		// Obtener String de limit inicio offset final..
		$respuesta ='';
		if ($inicio >0){
			$respuesta = " LIMIT ".$inicio." OFFSET ".$final;
		}
		return $respuesta;
	}
	
	
	
	
	function ConstructorLike($campos,$a_buscar,$operador='AND'){
	// @ Objetivo:
	// Construir un where con like de palabras y el campo indicado
	// Si contiene simbolos extranos les ponemos espacios para buscar palabras sin ellos.
	// @ Parametros:
	//  $campos -> (array) Campos los que buscar..
	//  $a_buscar-> (String) Que puede contener varias palabras.
	// 	$operador -> (String) puede ser OR o AND.. no mas...
	
	$buscar = array(',',';','(',')','-','"');
	$sustituir = array(' , ',' ; ',' ( ',' ) ',' - ',' ');
	$string  = str_replace($buscar, $sustituir, trim($a_buscar));
	$palabras = explode(' ',$string);
	$likes = array();
	// La palabras queremos descartar , la ponemos en mayusculas
	foreach($palabras as $palabra){
		if (trim($palabra) !== '' && strlen(trim($palabra))){
			// Entra si la palabra tiene mas 3 caracteres.
			// Aplicamos filtro de palabras descartadas
			
				foreach ($campos as $campo){
					$likes[] =  $campo.' LIKE "%'.$palabra.'%" ';
				}
				
			
		}
	}
	// Montamos busqueda con el operador indicado o el por defecto
	$operador = ' '.$operador.' ';
	$busqueda = implode($operador,$likes);
	return $busqueda;
}
	
	
	function consultaRegistro($BD,$nombretabla,$whereC='') {
		/* Objetivo:
		 * Crear una consulta que obtenga todos los campos de la tabla filtrado.
		 * */
		// Funcion para contar registros de una tabla.
		$array = array();
		$consulta = "SELECT * FROM ". $nombretabla.' '.$whereC;
		$resultadoConsulta = $BD->query($consulta);
		if ($BD->query($consulta)) {
			$array['NItems'] = $resultadoConsulta->num_rows;
		} else {
			// Quiere decir que hubo error en la consulta.
			$array['consulta'] = $consulta;
			$array['error'] = $BD->error;
		}
		if ($array['NItems'] > 0){
			// Hubo resultados
			while ($fila = $resultadoConsulta->fetch_assoc()){
				$array['Items'][] = $fila;
			}
		}
		//~ $array['sql']=$consulta;
		return $array;
	}
	
	
	
	
	
	function paginacionFiltro($Array_campos,$filtro,$prefijo,$sufijo){
		/* @ Objetivo:
		 * Montar le where para hacer consultas y utilizar paginacion.
		 * Este where puede montarse con likes o con = , depende de los que necesitemos
		 * aparte tambien devolvemos el rango si fuera necesarios.
		 * @ Parametros:
		 * 		$Array_campos : Arrays de Array
		 * 					[0] (
		 * 						'nombre_campo' 		=> nombre del campo...
		 * 						'tipo_comparador' 	=> '=' Valor ( puede ser 'LIKE' o '=' 
		 * 					)
		 * 					[1] (
		 * 						'nombre_campo'=> 
		 * 						'tipo_comparador' => 
		 * 						)
		 * 		$filtro  : String , que puede contener varias palabras separadas por espacio.
		 * 		$prefijo : String que ponemos en justo despues de where.
		 * 		$sufijo  : String que ponermos al final del string que devolvemos.
 		 * NOTA IMPORTANTES: Es obligatorio todos los parametros, si no quiere enviar nada envia vacio '', array().
		 * @ Devolvemos:
		 * Devolvemos string.
		 * 
		 * */
		// INICIALIZO VARIABLES QUE VOY UTILIZAR. 
		$resultado = '';
		$buscar='';
		$wheres = array();
		$palabras=array();

		if ($filtro !==''){
			//Quiere decir que tenemos palabras a buscar.
			$palabras = explode(' ',$filtro); // Crear array de una o varias palabras.
		}
		foreach ($Array_campos as $key =>$campo){
			foreach ($palabras as $palabra){
				$wheres[$key] = '('.$campo['nombre_campo'].' '.$campo['tipo_comparador'].' ';
				if ($campo['tipo_comparador'] === 'LIKE'){
					$palabra = '%'.$palabra.'%';
				}
				$wheres[$key] .= '"'.$palabra.'")';
			}
		}
		if ($prefijo !='' || count($wheres)>0 ) {
			// Si hay prefijo o campos , para evitar devolver un WHERE solo...
			$resultado ='WHERE '.$prefijo;
			if (count($wheres)> 0) {
				$resultado .=' ('.implode(' OR ',$wheres).')'; 
			}
		}
			$resultado .= ' '.$sufijo;
		return $resultado;
	}
	
	function ObtenerCajasInputParametros($parametros){
		$VarJS = array();
		foreach ($parametros->cajas_input->caja_input as $caja_input){
			// Montamos acciones de teclas
			$JSTeclas = array();
			$JSParametros = array(); // Posibles parametros que necesitemos en caja.

			// Obtenemos teclas y acciones. Recuerda que para ello debes tener funcion controladoracciones
			if (isset($caja_input->teclas->action)){
				foreach ($caja_input->teclas->action as $accion){
					$JSTeclas[] =  (string) $accion['tecla'].':'."'".$accion."'";
				}
				$JSaccionesTeclas = ', acciones:{'.implode(',',$JSTeclas).'}';
			} else {
				$JSaccionesTeclas = '';
			}
			// Obtenemos parametros si existe.
			if (isset($caja_input->parametros->parametro)){
				foreach ($caja_input->parametros->parametro as $parametro){
					$JSParametros [] = (string) $parametro['nombre'].':'."'".$parametro."'";
				}
				$JSstringParametros = ', parametros:{'.implode(',',$JSParametros).'}';
			} else {
				$JSstringParametros = '';
			}
			// Obtenemos acciones despues de crear caja
			// De momento solo obtengo estado , ya este puede variar.
			if (isset($caja_input->before)){
				$JSstringBefore = ', before_constructor: '."'".(string)$caja_input->before->estado."'";
			} else {
				$JSstringBefore ='';
			}
			// Obtenemos acciones antes de crear caja
			// De momento solo obtengo estado , ya este puede variar.
			if (isset($caja_input->after)){
				$JSstringAfter = 'after_constructor: '."'".(string)$caja_input->after->estado."',";
			} else {
				$JSstringAfter ='';
			}
			//~ echo '<pre>';
			//~ echo $JSstringBefore;
			//~ echo '</pre>';
			$VarJS[] = 'var '.(string) $caja_input->nombre.' = {'.$JSstringAfter.
					"id_input :'".$caja_input->nombre['id_input']."'".$JSaccionesTeclas.$JSstringParametros.$JSstringBefore.'};
					';
		}
		$htmlVarJS = implode(' 
								',$VarJS);
		return $htmlVarJS;
	}
	
	function GrabarConfiguracionModulo($nombre_modulo,$idUsuario,$configuracion){
		// @ Objetivo:
		// Grabar la configuracion de modulo para un usuario.
		// @ Parametros:
		// $nombre_modulo -> (String) 
		// $idUsuario-> (String) Aunque es un numero... :-)
		// $configuracion -> Array que traer parametros y ademas trae 
		//			$configuracion['tipo_configuracion'] -> (String) que puede ser Usuario o Modulo
		// [NOTA] -> Tiene que existir para poder reescribir.
		$BDTpv = $this->BDTpv;
		// Ahora comprobamos el tipo de configuracion que es, ya que uno inserta y otro update
		$tipo_configuracion = $configuracion['tipo_configuracion'];
		unset($configuracion['tipo_configuracion']); // Elimino para no meterlo como parametro en campo
		if ( $tipo_configuracion === 'Usuario'){
			// Existe registro por lo que hacemos udate.
			$Set_conf= " SET configuracion=".
				"'".json_encode($configuracion)."',fecha= NOW() WHERE idusuario=".$idUsuario." AND nombre_modulo='".$nombre_modulo."'" ;
			$Sql= 'UPDATE `modulos_configuracion` '.$Set_conf;
		} else {
			// No existe registro por lo que creamos registro.
			$values = "VALUES ('".$idUsuario."','".$nombre_modulo."','".json_encode($configuracion)."',NOW())";
			$Sql= 'INSERT INTO `modulos_configuracion`(`idusuario`, `nombre_modulo`, `configuracion`, `fecha`) '.$values;
		}
		if ($BDTpv->query($Sql)){
			$respuesta['affectado'] = $BDTpv->affected_rows ;
		} 
		$respuesta['Sql']= $Sql;
	
		return $respuesta;
		
	}
	function obtenerConfiguracion($conf_defecto,$nombre_modulo,$idUsuario){
		//Objetivo:
		//Obtener la configuracion del modulo.
		//Tenienedo encuenta la configuracion por defecto del modulo y la configuracion que hay en la tabla modulo.
		//nos quedamos con la de la tabla
		//Aunque la compramos por si falta algun parametro configuracion por defecto, si falta deberíamos añadirlo.

		$respuesta = $conf_defecto;
		$res = $this->obtenerConfiguracionModuloTabla($nombre_modulo,$idUsuario);
		if ($res['NItems'] === 1){
			// Si obtiene configuracion, entonce NItems debe ser 1, no puede ser mayor , ni menor..
			$conf_tabla = json_decode($res['Items'][0]['configuracion']);
			foreach ($conf_tabla as $key=>$valor){
				$respuesta[$key] = $valor;
			}
			$respuesta['tipo_configuracion'] = 'Usuario';
		} else {
			// Si hay no hay resultado ponemos la configuracion de Modulo
			if ($res['NItems'] === 0){
				$respuesta['tipo_configuracion'] = 'Modulo';
			} 
		}
		// Añadimos $nombre_modulo, $idUsuario, asi podemos moverlo junto.
		$respuesta['nombre_modulo']= $nombre_modulo;
		$respuesta['idUsuario'] = $idUsuario;
		return $respuesta;
		
	}
	
	function obtenerConfiguracionModuloTabla($nombre_modulo,$idUsuario){
		// Objetivo :
		// Obtener la configuracion si la hay de un modulo en cuestion.
		$BDTpv = $this->BDTpv;
		$where_conf= ' WHERE idusuario='.$idUsuario.' AND nombre_modulo="'.$nombre_modulo.'"';
		$respuesta = $this->consultaRegistro($BDTpv,'modulos_configuracion',$where_conf);
		return $respuesta;
	}
	
	function loadDbtpv($BD){
		$this->BDTpv = $BD;
	}
    
    public static function insertTd($contenido=''){
        //@Objetivo:Cubrir los td sin tantas comprobaciones. Está función la ponemos llamar sin inicializar la clase
        //@Contenido: es el contenido que queremos poner en el td, lo inicializamos
        //por si no existe.
        
        $resultado='<td></td>';
        if(strlen($contenido)>0){
            $resultado='<td>'.$contenido.'</td>';
        }
        return $resultado;
    }
	
}
	
?>
