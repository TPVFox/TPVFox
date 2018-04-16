<?php
/* Objetivo de esta clase
 *   - Crear un objeto que contenga productos con todos los datos de estos.
 *   - Tener los parametros cargados, para interactuar con los datos.
 *
 * [Informacion sobre los estados posibles.]
 * Campo estado de las tablas de articulos :
 * Sus posibles valores , los podemos ver el metodo: posiblesEstados($tabla), donde hay uno para todas las tablas
 * y algunas tablas tiene algunos mas.
 * 
 * [OTRAS NOTAS]
 * Propiedades privadas:
 * En la clase extendida tenemos propiedades privadas , esta no las puedo obtener directamente.
 * Ejemplo:
 *   idTienda existe en la clase extendida ,pero es privada por la que no la podemos utilizar directamente ($this->idTienda)
 * Por ello si declaramos la propiedad en la clase, podríamos tener dos valores distintos de la misma propiedad... pero una
 * la llamamos parent::  ... 
 * 
 * Propiedades publicas:
 * En la clase podemos obtener propiedades de la clase extendida directamente
 * Ejemplo:
 *   $this->estado no esta declarada en la clase pero si en la extendida tiene valor...
 * 
 * Metodos en UpperCamelCase
 * 
 * 
 * 
 * */


include ($RutaServidor.$HostNombre.'/clases/ClaseTablaArticulos.php');

class ClaseProductos extends ClaseTablaArticulos{
	
	public $idTienda ; // Obtenemos el idTienda de la clase extendida.
	
	
	public function __construct($conexion='')
	{
		// Solo realizamos asignamos 
		if (gettype($conexion) === 'object'){
			parent::__construct($conexion);
			$this->idTienda = parent::GetIdTienda();
		}
	}
	
	public function obtenerProductos($campo,$filtro=''){
		// @ Objetivo 
		// Obtener los campos idArticulo,articulo_name,ultimoCoste,beneficio,iva,pvpCiva,estado productos según con el filtro indicado.
		switch ($campo) {
			case 'articulo_name':
				// Buscamos por nombre de articulo..
				$consulta = "SELECT a.idArticulo,a.articulo_name as articulo_name"
				." ,a.ultimoCoste,a.beneficio,a.iva,p.pvpSiva,p.pvpCiva,a.estado"
				." FROM `articulos` AS a "
				."LEFT JOIN `articulosPrecios` AS p "
				."ON p.`idArticulo` = a.`idArticulo`  ".$filtro;
				break;
			
			case 'crefTienda':
				// Buscamos por Referencia de tienda.
				$consulta = "SELECT a.idArticulo,a.articulo_name as articulo_name"
				." ,atiendas.crefTienda as crefTienda,a.ultimoCoste,a.beneficio,a.iva,p.pvpSiva,p.pvpCiva,a.estado"
				." FROM `articulos` AS a "
				."LEFT JOIN `articulosPrecios` AS p "
				."ON p.`idArticulo` = a.`idArticulo` "
				."LEFT JOIN `articulosTiendas` AS atiendas ON (atiendas.idArticulo = a.idArticulo) AND "
				."(atiendas.idTienda =".$this->idTienda.") "
				.$filtro;
				break;
			
			case 'codBarras':
				// Buscamos por Codbarras.
				$consulta = "SELECT a.idArticulo,a.articulo_name as articulo_name"
				." ,aCodBarras.codBarras as codBarras,a.ultimoCoste,a.beneficio,a.iva,p.pvpSiva,p.pvpCiva,a.estado"
				." FROM `articulos` AS a "
				."LEFT JOIN `articulosPrecios` AS p "
				."ON p.`idArticulo` = a.`idArticulo` "
				."LEFT JOIN `articulosCodigoBarras` AS aCodBarras ON (aCodBarras.idArticulo = a.idArticulo)"
				.$filtro;
				
			
		}
		
		$respuesta = parent::Consulta($consulta);
		if (isset($respuesta['error'])){
			// Si existe error devolvemos todo el array
			return $respuesta;
		}
		
		return $respuesta['Items'];

	}
	public function GetProductosConCodbarras($codbarras){
		// Objetivo:
		// Obtener array con id de productos que tiene ese codbarras.
		$sql = 'SELECT idArticulo FROM `articulosCodigoBarras` WHERE `codBarras`="'.$codbarras.'"';
		$items = parent::Consulta($sql);
		return $items;
		
	}
		
	public function GetProducto($id= 0){
		// Objetivo:
		// Este metodo existe en padre, pero necesito que añada a ArrayPropiedades las comprobaciones hacemos aquí.
		parent::GetProducto($id);
		// Ahora hacemos nuestra comprobaciones.
		$producto = $this->ArrayPropiedades();
		// Reinicio comprobacionesEstado.
		$this->comprobaciones = array();
		$this->comprobacionesEstado($producto);
		return $this->ArrayPropiedades();
		
	}
	
	public function cambiarTienda($id){
		// @Objetivo
		// Cambiar el id de la tienda , por si queremos buscar en otras tiendas simplemente.
		// Ten en cuenta que solo la cambia en esta clase no en la extendida, por lo que las consultas realizadas e
		// en la clase extendida, seguira haciendolo en la tienda asignada en la clase extendida.
		$this->idTienda= $id;
	}
	
	public function posiblesEstados($tabla){
		// @Objetivo
		// Obtener los estados posibles para la tabla que indicamos en parametro.
		// Posibles estado generales:
		$posibles_estados = array(  '1'=> array(
											'estado'      =>'Activo',
											'Descripcion' =>'Estado normal.'
												),
									'2' =>  array(
											'estado'      =>'Nuevo',
											'Descripcion' =>'Estado por defecto cuando se creo hace menos de 30 días.'
											),
									
									'3' =>  array(
											'estado'      =>'Temporal',
											'Descripcion' =>'Un producto que solo se comprar de forma temporal, en una epoca. En el proceso compra, debería advertilo y saber porque se compra.'
											),
									'4' =>  array(
											'estado'      =>'Oferta',
											'Descripcion' =>'Indica que el producto esta oferta, deberíamos ver que ofertas y hasta cuando.'
											),
									'5' =>  array(
											'estado'      =>'Baja',
											'Descripcion' =>'Indica que es un producto que se puede vender hasta fin existencias. Debería advertir a encargados compra que no se puede comprar.'
											),
									'6' =>  array(
											'estado'      =>'importado',
											'Descripcion' =>'Producto importado, de alguna tienda. Se creo forma automatica. Se cambia el estado, cuando ya lo compremos o cuando lo modifiquemos en ficha de producto'
											)
									);
		// Añado en todas la tablas menos en la articulos ya que son los por defecto.
		switch ($tabla) {
			case 'articulosTiendas':
				$array = array( '7' => array(
									'estado' =>'NoPublicado',
									'Descripcion'=>'Que existe en la tienda web pero no está publicado para la venta.'
									),
								'8' => array(
									'estado' =>'Publicado',
									'Descripcion'=>'Si esta creado y la venta en la tienda web'
									)
								);
				$posibles_estados= $posibles_estados +$array;
				break;
			case 'articulosProveedores':
				$array = array( '9' => array(
									'estado' =>'SinStock',
									'Descripcion'=>'El proveedor en estos momento no tiene Stock de producto.'
									),
								'10' => array(
									'estado' =>'Tarifa',
									'Descripcion'=>'Precio propuesto por el proveedor pero aun no se compro.'
									),
									
								);
				$posibles_estados= $posibles_estados +$array;
				break;
  
		}
		return $posibles_estados;
		
	}
	
	function comprobacionesEstado($producto){
		// @ Objetivo 
		// Comprobar que el estado que enviamos sea correcta.
		// @ Parametros:
		// 		$producto-> (array) Con los datos del producto
		// @ Responde :
		// 		Si hubiera un error crea una comprobación y le añade a la propiedad comprobaciones y tb la devuelve.
		$estado = $producto['estado'];
		switch ($estado) {
			case 'Nuevo':
				// Debemos saber si la fecha_actualizacion ultima es superior a un mes.
				$datetime1 = date_create($producto['fecha_creado']);
				$datetime2 = date_create();
				$interval = date_diff($datetime1, $datetime2);
				if ($interval->days >20){
					// Creamos la advertencia.
					$error = array ( 'tipo'=>'warning',
								 'dato' => '',
								 'mensaje' => 'Ojo !! Este producto tiene el estado [Nuevo], lleva '.$interval->days.' días, recomiendo ponerlo como Activo.'
								 );
					parent::SetComprobaciones($error);
					
				}
				break;
			case 'importado':
				// Debemos saber si la fecha_actualizacion ultima es superior a un mes.
				$datetime1 = date_create($producto['fecha_creado']);
				$datetime2 = date_create();
				$interval = date_diff($datetime1, $datetime2);
				if ($interval->days >20){
					// Creamos la advertencia.
					$error = array ( 'tipo'=>'warning',
								 'dato' => '',
								 'mensaje' => 'Ojo !! Este producto tiene el estado [importado], lleva '.$interval->days.' días, recomiendo cambiarlo.'
								 );
					parent::SetComprobaciones($error);
					
				}
				break;
				
			
		}
	}
	

	function AnhadirProductoNuevo($datos){
		// @ Objetivo
		// Crear un producto nuevo con los datos que tengamos.
		// @ Parametros:
		// 		$datos-> (array) con los datos para crear producto.
		// @ Devuelve:
		// 		(array) -> id (int) el numero id creado
		// 				-> errores (array) con tipo,mensaje,dato.
		$fecha_ahora= date("Y-m-d H:i:s");   // Obtenemos la fecha sistema 
		$campos_obligatorios = array('articulo_name','estado','iva','pvpSiva','pvpCiva','coste','beneficio');
		$comprobaciones = array(); // Lo utilizo para guardar resultado de comprobaciones o errores.
		// ---- 	Comprobamos que existe campo y tiene dato correcto.		--------- //
		foreach ($campos_obligatorios as $key){
			$existe = 'NO';
			if (isset($datos[$key])){
				$existe ='Si'; // Ya que existe
				if ($key === 'iva'){
					// Comprobamos si el iva que vamos añadir es correcto, si no ponemos el por defecto.
					$comprobarIva = parent::ComprobarIva($datos['iva']);
					if (gettype($comprobarIva['error'])==='array'){
						// Hubo un error el tipo de iva 
						$datos['iva'] = $comprobarIva['iva'];
						$comprobaciones['iva'] = $comprobarIva['error']; // Podrías utilizar perfectamente $comprobarIva
					}
					
				}				
			}
			if ($existe === 'NO'){
				$error = array ( 'tipo'=>'danger',
								 'mensaje' =>'Error no existe o no es correcto '.$key,
								 'dato' => $key);
				$comprobaciones['campos'] = $error;
				
				return $comprobaciones;
			}
			
		}
		// ---- 		Insertamos un producto nuevo en tabla articulos 		----- //
		$sqlArticulo = 'INSERT INTO `articulos`(`iva`, `articulo_name`, `estado`,ultimoCoste, `fecha_creado`,beneficio) VALUES ("'.$datos['iva'].'","'.$datos['articulo_name'].'","'.$datos['estado'].'","'.$datos['coste'].'","'.$fecha_ahora.'","'.$datos['beneficio'].'")';
		// De momento inserto ultimoCoste, pero no deberíamos... :-) ya que no se compro.	
		$respuesta = array();
		$DB = parent::GetDb();
				$smt = $DB->query($sqlArticulo);
				if ($smt) {
					$respuesta['idInsert'] = $DB->insert_id;
					// Hubo resultados
				} else {
					// Quiere decir que hubo error en la consulta.
					$respuesta['error'] = $DB->connect_errno;
					
				}
				$respuesta['consulta'] = $sqlArticulo;
		if (isset($respuesta['error'])){
			// Entonces hubo error no podemos continuar.
			$error = array ( 'tipo'=>'danger',
								 'mensaje' =>'Error al insertar en tabla Articulos '.json_encode($respuesta['error']),
								 'dato' => $sqlArticulo
							);
			$comprobaciones['insert_articulos'] = $error;
			return $comprobaciones;
		}
		$comprobaciones['insert_articulos'] = array( 'id_producto_nuevo' => $respuesta['idInsert'],
													 'consulta'=> $respuesta['consulta'] = $sqlArticulo
													);
		
		// ---- 		Insertamos un producto precios del producto nuevo en tabla articulosprecios 		----- //
		$datos['id'] = $respuesta['idInsert'];
		// Hay que tene en cuenta que si el precio es 0 lo va añadir igualmente, ya que asi se podrá modificar , no insertar.
		$comprobaciones['insert_articulos_precios']  = parent::InsertarPreciosVentas($datos);
		
		// ----         Insertamos codbarras  del producto nuevo 											----- //
		$comprobaciones['codbarras']=$this->ComprobarCodbarrasUnProducto($datos['id'],$datos['codBarras']);
		
		return $comprobaciones;
		
	}
		
	function AnhadirCodbarras($id,$codbarras = array()){
			// @ Objetivo 
			// Una funcion para añadir uno o mas codBarras al producto que enviamos.
			// @ parametros:
			// 		id-> (int) ID del producto
			// 		codbarras -> (array) -> (strings) Codbarras queremos añadir.
			$respuesta = array();
			$values = array();
			if ($id > 0){
				if (count($codbarras)>0){
					// Entonces eliminamos solo el codbarras que indicamos.
					foreach ($codbarras as $key=>$cd){
						$values[]= '('.$id.',"'.$cd.'")';
					}
				}
				$stringValues = implode(',',$values);
				$sql = 'INSERT INTO `articulosCodigoBarras`(`idArticulo`, `codBarras`) VALUES '.$stringValues;
				$DB = parent::GetDb();
				$smt = $DB->query($sql);
				if ($smt) {
					$respuesta['NAnhadidos'] = $DB->affected_rows;
					// Hubo resultados
				} else {
					// Quiere decir que hubo error en la consulta.
					$respuesta['consulta'] = $sql;
					$respuesta['error'] = $DB->connect_errno;
				}
				$respuesta['consulta'] = $sql;
				
			}
			$respuesta['consulta'] = $sql;
			return $respuesta;
			
	}	
	
	
	
	function EliminarCodbarras($id,$codbarras = array()){
			// @ Objetivo 
			// Una funcion para eliminar uno o todos los codBarras del producto que enviamos.
			// @ parametros:
			// 		id-> (int) ID del producto
			// 		codbarras -> (array) -> (strings) Codbarras queremos eliminar.
			$respuesta = array();
			if ($id > 0){
				if (count($codbarras)>0){
					// Entonces eliminamos solo el codbarras que indicamos.
					foreach ($codbarras as $key=>$cd){
						$codbarras[$key]= 'codbarras="'.$cd.'"';
					}
					$stringCodbarras = ' AND ('.implode(' OR ',$codbarras).')'; 
				}
				$sql = 'DELETE FROM `articulosCodigoBarras` WHERE `idArticulo`='.$id.$stringCodbarras;
				$DB = parent::GetDb();
				$smt = $DB->query($sql);
				if ($smt) {
					$respuesta['NEliminados'] = $DB->affected_rows;
					// Hubo resultados
				} else {
					// Quiere decir que hubo error en la consulta.
					$respuesta['consulta'] = $sql;
					$respuesta['error'] = $DB->connect_errno;
				}
				$respuesta['consulta'] = $sql;
				
			}
			$respuesta['consulta'] = $sql;
			return $respuesta;
			
	}
	
	
	function ComprobarCodbarrasUnProducto($id_pro,$Pro_Nuevo_codBarras){
		// @ Objetivo:
		// Que codigo de barras hay que añadir, modificar o eliminar.
		// @ Parametros: 
		//   $id -> (int) Id del producto que vamos añadir,modificar o elimnar codbarras.
		// 	 $Pro_Nuevo_codBarras-> (array) Los codbarras modificados, eliminado o nuevos.
		// @ Devuelve:
		//   comprobaciones : (array) con tipo,mensaje,dato para poder mostrar.
		$producto_sin_modificar = $this->getProducto($id_pro);
		$Pro_codBarras = $producto_sin_modificar['codBarras'];// (array) con los codbarras que tenía ante de modificar.
		
		$comprobaciones = array(); // Array que utilizamos para informar de lo que hicimos
		// ---       	    Ahora empezamos con CodBarras por partes   						--- //
		// Obtengo aquellos codbarras que no este el el Post, estos son los que tengo eliminar.
		$codbarras_eliminados = array_diff($Pro_codBarras,$Pro_Nuevo_codBarras);
		
		if (count($codbarras_eliminados)>0){
			// Quiere decir que SE BORRO alguno o todos los codbarras que existia.
			// Eliminamos el codbarras del producto.
			$Sqls['eliminados'] = $this->EliminarCodbarras($id_pro,$codbarras_eliminados);
			if ($Sqls['eliminados']['NEliminados']===count($codbarras_eliminados)){
				$comprobaciones[0]['tipo']= 'warning';
				$comprobaciones[0]['mensaje']= 'Eliminamos los siguiente codbarras para este producto:'.implode(',',$codbarras_eliminados);
			} else {
				$comprobaciones[0]['tipo']= 'dargen';
				$comprobaciones[0]['mensaje']= 'Error no coincide el numero eliminado de codbarras: '.implode(',',$codbarras_eliminados);
			}
			$comprobaciones[0]['dato']= json_encode($Sqls['eliminados']);
		}
		// Ahora vemos los que tenemos que añadir.
		// En array de los codbarras recibidos ($DatosProducto['codBarras']) eliminamos aquellos que vamos añadir.
		$codbarras_nuevos = array_diff($Pro_Nuevo_codBarras,$Pro_codBarras);
		if (count($codbarras_nuevos)>0){
			$Sqls['anhadidos'] = $this->AnhadirCodbarras($id_pro,$codbarras_nuevos);
			if ($Sqls['anhadidos']['NAnhadidos']===count($codbarras_nuevos)){
				$comprobaciones[1]['tipo']= 'success';
				$comprobaciones[1]['mensaje']= 'Añadimos los siguiente codbarras: '.implode(',',$codbarras_nuevos);
			} else {
				$comprobaciones[1]['tipo']= 'dargen';
				$comprobaciones[1]['mensaje']= 'Error no coincide el numero insertado con los codbarras que iba añadir codbarras: '.implode(',',$codbarras_nuevos);
			}
			$comprobaciones[1]['dato'] = json_encode($Sqls['anhadidos']);
		}	
		
		return $comprobaciones;
	}
	
	
	
	
	
	// Fin de clase.
}



?>
