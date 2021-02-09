<?php 
/*	Clase para trabajar con tablas de familias.
 * Tablas de familias:
 *  familias
 *  familiasTiendas
 * 
 * */
class ClaseTablaFamilias{
	private $db; // (Objeto) Conexion
	private $num_rows; // (int) Numero de registros.
	// Propiedades particulares de tabla familia.
	private $idFamilia ;
	private $familiaNombre = '';
	private $familiaPadre= 0;
	
	public function __construct($conexion=''){
		if (gettype($conexion) === 'object'){
			$this->db = $conexion;
			// Obtenemos el numero registros.
			$sql = 'SELECT count(*) as num_reg FROM familias';
			$respuesta = $this->consulta($sql);
			$this->num_rows = $respuesta->fetch_object()->num_reg;
			// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
		}
	}
	public function ArrayDatos($datos){
		$resultado = array();
		return $resultado;
	}
	
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}

}
?>
