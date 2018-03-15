<?php
/* Objetivo de esta clase
 *   - Crear un objeto que contenga productos con todos los datos de estos.
 *   - Tener los parametros cargados, para interactuar con los datos.
 * [NOTAS]
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
 * */


include ($RutaServidor.$HostNombre.'/clases/ClaseTablaArticulos.php');

class ClaseTablaProductos extends ClaseTablaArticulos{
	
	private $idTienda ; // Obtenemos el idTienda de la clase extendida.
	public $productos; // Array de id, de productos...
	
	
	public function __construct($conexion='')
	{
		// Solo realizamos asignamos 
		if (gettype($conexion) === 'object'){
			parent::__construct($conexion);
			$this->idTienda = parent::GetIdTienda();
		}
	}
	
	public function obtenerProductos($filtro=''){
		// @ Objetivo 
		// Obtener los campos idArticulo,articulo_name,ultimoCoste,beneficio,iva,pvpCiva,estado productos según con el filtro indicado.
		echo 'Estado'.$this->estado;
		echo 'Filtro:'.$filtro;
		$consulta = "SELECT a.idArticulo,a.articulo_name,a.ultimoCoste,a.beneficio,a.iva,p.pvpCiva,a.estado"
				." FROM `articulos` AS a "
				."LEFT JOIN `articulosPrecios` AS p "
				."ON p.`idArticulo` = a.`idArticulo`  ".$filtro;
		$respuesta = parent::Consulta($consulta);
		
		
		return $respuesta['Items'];
		
	}
	
	public function cambiarTienda($id){
		// @Objetivo
		// Cambiar el id de la tienda , por si queremos buscar en otras tiendas simplemente.
		// Ten en cuenta que solo la cambia en esta clase no en la extendida, por lo que las consultas realizadas e
		// en la clase extendida, seguira haciendolo en la tienda asignada en la clase extendida.
		$this->idTienda= $id;
	}
	
}

?>
