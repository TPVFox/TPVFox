
<?php
/*	 Es crear un controlador de consultas comunes para varios modulos.
 * */


class ControladorComun 
{
     function InfoTabla ($Bd,$tabla){
		// Funcion que nos proporciona informacion de la tabla que le indicamos
		/* Nos proporciona informacion como nombre tabla, filas, cuando fue creada, ultima actualizacion .. y mas campos interesantes:
		 * Ejemplo de print_r de virtuemart_products 
		 * 		[Name] => virtuemart_products  // Normal ya que el prefijo ....
		 *    	[Rows] => Numero registros  // ESTE ES IMPORTANTE, el que analizamos inicialmente.
		 *    	[Create_time] => 2016-10-31 18:23:52 // Normal ya que nunca coincidira... se crearía fechas distintas.
		 *    	[Update_time] => 2016-10-31 20:46:35 // Lo recomendable que la hora Update ser superior en nuestra BD , pero no siempre será
		*/
		$fila = array();
		if ($tabla != ''){
			// Quiere decir que queremos consultar informa todas las tablas.
				$tablas = 'WHERE `name`="'.$tabla.'"';
		} else {
			// Quiere decir que queremos consultar informa un tabla
				$tablas = '';
		}
		$consulta = 'SHOW TABLE STATUS '. $tablas;
		$Queryinfo = $Bd->query($consulta);
		// Hay que tener en cuenta que no produce ningún error... 
		$Ntablas = $Bd->affected_rows   ;
		if ($Ntablas == 0) {
			$fila ['error'] = 'Error tabla no encontrada - '.$tablas;
		} else {
			$fila = $Queryinfo->fetch_assoc();
		}
		$fila['consulta'] = $consulta;
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
	

	
	
	
}
	
?>
