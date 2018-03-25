<?php 

class ClaseTablaIva{
	private $id = 0;
	private $descripcion = '';
	private $iva= 0;
	private $recargo= 0;
	private $db;
	private $num_rows;
	
	public function __construct($conexion =''){
		// Solo realizamos asignamos 
		if (gettype($conexion) === 'object'){
			$this->db = $conexion;
			// Obtenemos el numero registros.
			$sql = 'SELECT count(*) as num_reg FROM iva';
			$respuesta = $this->consulta($sql);
			$this->num_rows = $respuesta->fetch_object()->num_reg;
			// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
		}
	}
	
	public function conexion($conexion){
		$this->__construct($conexion);
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
	public function getIva($id=''){
		// Objetivo:
		// Obtener el iva con id o sin el..
		if ($id === ''){
			// Quiere decir que devolvemos el valor propiedad
			return $this->iva;
		} else {
			// Queremos obtener le iva del id indicado.
			$ivas = $this->todoIvas();
			foreach ($ivas as $item){
				if ($item['idIva']===$id){
				 return $item['iva'];
				}
			}
		}
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
		$db = $this->db;
		$smt = $db->query('SELECT * FROM iva');
		$ivas = array();
		while ( $result = $smt->fetch_assoc () ) {
			$ivas[] = $result;
		}
		return $ivas;
	
	}
	 public function ivasNoPrincipal($ivaPrincipal){
		$db = $this->db;
		$smt = $db->query ( 'SELECT * FROM iva where iva <>'.$ivaPrincipal );
		$ivas = array();
		while ( $result = $smt->fetch_assoc () ) {
			$ivas[] = $result;
		}
		return $ivas;
		
	}
	
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}

}
?>
