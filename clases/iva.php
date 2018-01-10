<?php 

class iva{
	private $id = 0;
	private $descripcion = '';
	private $iva= 0;
	private $recargo= 0;
	private $db;
	private $num_rows;
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM iva';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function ArrayDatos($datos){
		$this->id 			= $datos['idIva'];
		$this->descripcion 	= $datos['descripcionIva'];
		$this->iva 			= $datos['iva'];
		$this->recargo 		= $datos['recargo'];
		//~ $respuesta = $this->arrayDatos();
		$respuesta = array (	'id' => $this->id,
								'descripcion' => $this->descripcion,
								'iva'=> $this->iva,
								'recargo' => $this->recargo
							);
		
		return $respuesta;
	}
	
	
	
	//~ public function arrayDatos(){
		//~ $respuesta = array (	'id' => $this->id,
								//~ 'descripcion' => $this->descripcion,
								//~ 'iva'=> $this->iva,
								//~ 'recargo' => $this->recargo
							//~ );
		//~ return $respuesta ;
	//~ }
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
			$iva = $this->arrayDatos($result);
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
			$iva = $this->arrayDatos($result);
			array_push($ivasPrincipal,$iva);
		}
		return $ivasPrincipal;
		}catch ( PDOException $e ) {
			echo 'Error: ' . $e->getMessage ();
		}
	}
	
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}

}
?>
