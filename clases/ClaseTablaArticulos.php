<?php
include ($RutaServidor.$HostNombre.'/clases/ClaseTablaIva.php');
include ($RutaServidor.$HostNombre.'/clases/ClaseTablaFamilias.php');

class ClaseTablaArticulos{
	
	private $db; // (Objeto) Conexion
	private $idTienda; // (int) Id de la tienda , por defecto es la principal, pero se podrá cambiar.
	// Propiedades particulares de tabla articulos.
	private $num_rows; // (int) Numero de registros.
	private $idArticulo;
	private $iva= 0;
	private $articulo_name = '';
	private $beneficio =  25; // Beneficio por defecto
	private $costepromedio = 0; // Sino se compro , 0
	private $costeultimo = 0; // Es el ultimo coste compra, si no se compro es el ultimo conocido, que pusimos.
	private $estado ='Activo'; // Estado del producto por defecto en tienda principal: Activo, Eliminado
	private $fecha_creado;
	private $fecha_modificado;
	private $codBarras; // Array de codbarras para ese producto.
	private $ref_tiendas; // Array de referencias de las tiendas.
	private $proveedores; // Array de referencias de proveedores.
	private $familias; // Array de familias de ese producto
	private $proveedor_principal; // Array con datos del proveedor principal
	
	
	public function __construct($conexion='')
	{
		// Solo realizamos asignamos 
		if (gettype($conexion) === 'object'){
			$this->db = $conexion;
			// Obtenemos el numero registros.
			$sql = 'SELECT count(*) as num_reg FROM articulos';
			$respuesta = $this->db->query($sql);
			$this->num_rows = $respuesta->fetch_object()->num_reg;
			// Obtenemos la tienda principal
			$this->ObtenerTiendaPrincipal();
		}
	}
	
	private function consulta($sql){
		$respuesta = array();
		$db = $this->db;
		$smt = $db->query($sql);
		if ($db->query($sql)) {
			$respuesta['NItems'] = $smt->num_rows;
			// Hubo resultados
			while ($fila = $smt->fetch_assoc()){
				$respuesta['Items'][] = $fila;
			}
		} else {
			// Quiere decir que hubo error en la consulta.
			$respuesta['consulta'] = $sql;
			$array['error'] = $db->error;
		}
		
		return $respuesta;
	}
	
	
	public function getProducto($id){
		// @ Objetivo :
		// Obtener los datos de un articulo ( producto).
		// @ Parametro -> (int) id de articulo..
		// Todos los datos posibles según las propiedades que tengamos. (idTienda)
		// tenemos la propiedad de idTienda
		$respuesta = array();
		// El campo ultimoCoste, tendría que llamarse coste_ultimo
		// El campo costepromedio -> coste_promedio ...
		
		$Sql = 'SELECT a.*, prec.* FROM articulos as a '
			.'  LEFT JOIN articulosPrecios as prec ON a.idArticulo= prec.idArticulo '
			.'  WHERE a.idArticulo ='.$id.' AND '
			.'  prec.idArticulo='.$id.' AND prec.idTienda= '.$this->idTienda;
		$consulta = $this->consulta($Sql);
		if ($consulta['NItems'] === 1){
			// Si hay uno solo continuamos.
			$respuesta = $consulta['Items'][0];
			// Obtenemos datos del proveedor prederterminado.
			$this->ObtenerDatosProvPredeter($respuesta['idProveedor']);
			unset($respuesta['idProveedor']); // Elimino de respuesta idProveedor
			$respuesta['Proveedor_principal'] = $this->proveedor_principal;
			// Obtenemos referencias y datos de las otras tiendas para ese producto
			$this->ObtenerReferenciasTiendas($id); 
			$respuesta['Referencias_tiendas'] = $this->ref_tiendas;
			// Obtenemos familias a las que pertenece ese producto
			$this->ObtenerFamiliasProducto($id);
			$respuesta['familias'] = $this->familias;
			// Obtenemos Codbarras a las que pertenece ese producto
			$this->ObtenerCodbarrasProducto($id);
			$respuesta['codbarras'] = $this->codbarras;
		}
		
		
		return $respuesta;
	}
	
	// ----- FUNCION PARA OBTENER PROPIEDADES --- //	
	public function getNumRows(){
		return $this->num_rows;
	}
	public function getIdTienda(){
		return $this->idTienda;
	}
	
	
	// -----  OTROS FUNCIONES NECESARIAS ------ //
	
	public function getTodosIvas(){
		$CTivas = new ClaseTablaIva($this->db);
		$ivas = $CTivas->todoIvas();
		return $ivas;
	}
	
	public function ObtenerTiendaPrincipal(){
		// Objetivo:
		// Obtener la tienda principal y guardarla en propiedad tienda.
		// [NOTA] -> Asi no hace falta mandar siempre idTienda
		$Sql = "SELECT idTienda FROM `tiendas` WHERE `tipoTienda`='Principal'";
		$respuesta = $this->consulta($Sql);
		if ($respuesta['NItems'] === 1){
			// Quiere decir que obtuvo un dato solo..
			$this->idTienda = $respuesta['Items'][0]['idTienda'];
		}
	}
	
	public function ObtenerDatosProvPredeter($id){
		// Objetivo:
		// Obtener los datos del proveedor del que indiquemos
		$Sql = "SELECT * FROM `proveedores` WHERE `idProveedor`=".$id;
		$respuesta = $this->consulta($Sql);
		if ($respuesta['NItems'] === 1){
			// Quiere decir que obtuvo un dato solo..
			$this->proveedor_principal = $respuesta['Items'][0];
		}
		
	}
	
	public function ObtenerReferenciasTiendas($id){
		// Objetivo:
		// Obtener los referencias de todas tiendas de ese producto y los precios con iva y sin iva de esas tiendas.
		// @Parametro
		// $id -> (int) Id del producto.
		$Sql = 'SELECT ati.*, prec.pvpCiva, prec.pvpSiva, t.tipoTienda , t.dominio FROM `articulosTiendas` as ati '
			.' LEFT JOIN articulosPrecios as prec ON prec.idTienda = ati.idTienda '
			.' LEFT JOIN tiendas as t ON t.idTienda = ati.idTienda '
			.' WHERE  ati.idArticulo= '.$id.' GROUP BY prec.idTienda';
		$consulta = $this->consulta($Sql);
		// Aqui podemos obtener varios registros.
		$this->ref_tiendas = $consulta['Items'];
		
	}
	
	public function ObtenerFamiliasProducto($id){
		// Objetivo:
		// Obtener idFamilias y nombre de familia de ese producto
		// @Parametro
		// $id -> (int) Id del producto.
		$Sql = 'SELECT f.*, artfam.* FROM `familias` as f '
			.' LEFT JOIN articulosFamilias as artfam ON f.idFamilia = artfam.idFamilia '
			.' WHERE artfam.idArticulo= '.$id;
		$consulta = $this->consulta($Sql);
		// Aqui podemos obtener varios registros.
		$this->familias = $consulta['Items'];
		
	}
	
	public function ObtenerCodbarrasProducto($id){
		// Objetivo:
		// Obtener codbarras para ese producto.
		// @Parametro
		// $id -> (int) Id del producto.
		$Sql = 'SELECT * FROM articulosCodigoBarras WHERE idArticulo='.$id;
		$consulta = $this->consulta($Sql);
		// Aqui podemos obtener varios registros.
		$this->codbarras = $consulta['Items'];
		
	}
	
}

?>
