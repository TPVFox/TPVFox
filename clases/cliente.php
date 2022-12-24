<?php 

class Cliente{
	private $idCliente;
	private $nombre;
	private $razonSocial;
	private $nif;
	private $direccion;
	private $codPostal;
	private $telefono;
	private $movil;
	private $fax;
	private $email;
	private $estado;
	
		public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM clientes';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		if ($smt) {
			return $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	}
	public function arrrayDatos($datos){
		$this->id= $datos['idClientes'];
		$this->nombre=$datos['Nombre'];
		$this->razonSocial=$datos['razonsocial'];
		$this->nif=$datos['nif'];
		$this->direccion=$datos['direccion'];
		$this->codPostal=$datos['codPostal'];
		$this->telefono=$datos['telefono'];
		$this->movil=$datos['movil'];
		$this->fax=$datos['fax'];
		$this->email=$datos['email'];
		$this->estado=$datos['estado'];
	}
	public function DatosClientePorId($idCliente){
		$db = $this->db;
		$sql='SELECT * from clientes WHERE idClientes='.$idCliente;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$cliente=$result;
			}
			return $cliente;
		}
	}
	
	public function BuscarClientePorNombre($nombre){
        // Buscar por Nombre Comercial o razon social.
        // y por palabras
        $palabras = explode(' ', $nombre);
        $likes = array();
        foreach ($palabras as $key => $palabra) {
            // Montamos consulta por palabras de varias palabras, en nombre o razon social
            $likes[] =  'Nombre LIKE "%' . $palabra . '%" ';
        } 
        $sql = 'SELECT * FROM clientes WHERE ';
        $whereNombre = '';
        if (count($likes) >0){
            // Si no hay palabras ya no buscamos por nombre
            $whereNombre= '('.implode(' and ', $likes).')';
            $sql.= $whereNombre.' OR ';
            // Ahora hacemos lo mismo, pero con el campo razon social, por esos sutituimos Nombre por razonsocial
            $sql.= str_replace('Nombre','razonsocial',$whereNombre);
        } 
        //~ error_log($sql);  
		$db = $this->db;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			$clientePrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
					array_push($clientePrincipal, $result);
			}
			$respuesta['sql']=$sql;
			$respuesta['datos']= $clientePrincipal;
			return $respuesta;
		}
	}
	
	
	
	
}
?>
