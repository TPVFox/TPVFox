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
	
	public function GetProducto($id= 0){
		// Objetivo:
		// Este metodo existe en padre, pero necesito que añada a ArrayPropiedades las comprobaciones hacemos aquí.
		parent::GetProducto($id);
		// Ahora hacemos nuestra comprobaciones.
		$producto = $this->ArrayPropiedades();
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
	
	
	
	
	// Fin de clase.
}



?>
