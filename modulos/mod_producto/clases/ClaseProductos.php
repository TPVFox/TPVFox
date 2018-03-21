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

class ClaseProductos extends ClaseTablaArticulos{
	
	public $idTienda ; // Obtenemos el idTienda de la clase extendida.
	public $productos; // Array de id, de productos...
	
	
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
