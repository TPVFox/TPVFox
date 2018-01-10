<?php 

class iva{
	private $id;
	private $descripcion;
	private $iva;
	private $recargo;
	private $db;
	private $num_rows;
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$tabla = $conexion->query('DESCRIBE iva');
		$this->num_rows = $tabla->num_rows;
	}
	public function datos($datos){
		$this->id 			= $datos['idIva'];
		$this->descripcion 	= $datos['descripcionIva'];
		$this->iva 			= $datos['iva'];
		$this->recargo 		= $datos['recargo'];
		$respuesta = $this->arrayDatos();
		
		return $respuesta;
	}
	
	public function arrayDatos(){
		$respuesta = array (	'id' => $this->id,
								'descripcion' => $this->descripcion,
								'iva'=> $this->iva,
								'recargo' => $this->recargo
							);
		return $respuesta ;
	}
	public function getNumRows(){
		return $this->num_rows;
	}
	public function getId(){
		return $this->id;
	}
	public function getDescripcion(){
		return $this->descripcion;
	}
	public function getIva(){
		return $this->iva;
	}
	public function getRecargo(){
		return $this->recargo;
	}
	public function setId($id){
		$this->id=$id;
	}
	public function setDescripcion($descripcion){
		$this->descripcion=$descripcion;
	}
	public function setIva($iva){
		$this->iva=$iva;
	}
	public function setRecargo($recargo){
		$this->recargo=$recargo;
	}
	
	public function todoIvas(){
		//~ try{
		//~ $db = BD::conectar ();
		$db = $this->db;
		$smt = $db->query('SELECT * FROM iva');
		$ivasPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			$iva = $this->datos($result);
			array_push($ivasPrincipal,$result);
		}
		//~ array_push($ivasPrincipal,$smt );
		return $ivasPrincipal;
	//~ } catch ( PDOException $e ) {
			//~ echo 'Error: ' . $e->getMessage ();
		//~ }
	}
	static public function ivasNoPrincipal($ivaPrincipal){
		try{
			$smt = $db->query ( 'SELECT * FROM iva where iva <>'.$ivaPrincipal );
			$ivasPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			$iva = $this->datos($result);
			array_push($ivasPrincipal,$iva);
		}
		return $ivasPrincipal;
		}catch ( PDOException $e ) {
			echo 'Error: ' . $e->getMessage ();
		}
	}
	
}

?>
