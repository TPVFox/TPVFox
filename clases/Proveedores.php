<?php 

class Proveedores {
	private $idProveedor;
	private $nombreComercial;
	private $razonSocial;
	private $nif;
	private $direccion;
	private $telefono;
	private $fax;
	private $movil;
	private $email;
	private $fecha_creado;
	private $estado;
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM proveedores';
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
	
	public function buscarProveedorId($idProveedor){
		$db = $this->db;
		$sql='SELECT * from proveedores where idProveedor='.$idProveedor;
		//~ $smt=$db->query('SELECT * from proveedores where idProveedor='.$idProveedor);
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$proveedor=$result;
				return $proveedor;
			}
		}
		
	}
	public function buscarProveedorNombre($nombre){
		$db = $this->db;
		$sql='SELECT * from proveedores where nombrecomercial like "%'.$nombre.'%"';
		//~ $smt=$db->query('SELECT * from proveedores where nombrecomercial like "%'.$nombre.'%"');
		//~ $sql='SELECT * from proveedores where nombrecomercial="%'.$nombre.'%"';
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			$proveedorPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($proveedorPrincipal, $result);
			}
			$respuesta['datos']=$proveedorPrincipal;
			//~ $respuesta['sql']=$sql;
		}
			return $respuesta;
	}


    public function todosProveedores(){
        // @ Objetivo:
        // Buscar todos los proveedores y obtener los campos idProveedo y nombrecomercial
        // @ Devolvemos:
        // Siempre devolvemos un array ...
        // Podemos devolver proveedores o error.
        $proveedores = array();
        $sql='SELECT idProveedor, nombrecomercial FROM proveedores';
        $smt=$this->consulta($sql);
		if (gettype($smt) == 'object') {
            while ($fila = $smt->fetch_assoc()){
				$proveedores[] = $fila;
			}
		} else {
            $proveedores['error']=$smt['error'];
			$proveedores['consulta']=$smt['consulta'];
        }
        return $proveedores;
    }

    public function buscarProductosProveedor($idProveedor){
        $sql='SELECT * from articulosProveedores where idProveedor='.$idProveedor;
        $smt=$this->consulta($sql);
        if ($smt->fetch_assoc()) {
            while ($fila = $smt->fetch_assoc()){
				$productos_provedor[] = $fila;
			}
		} else {
            $productos_provedor['error']=$smt['error'];
			$productos_provedor['consulta']=$smt['consulta'];
        }
        return $productos_provedor;
    }
	
}

?>
