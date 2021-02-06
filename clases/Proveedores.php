<?php 
include_once $URLCom.'/clases/ClaseTFModelo.php';

class Proveedores extends TFModelo {
	//~ private $idProveedor;
	//~ private $nombreComercial;
	//~ private $razonSocial;
	//~ private $nif;
	//~ private $direccion;
	//~ private $telefono;
	//~ private $fax;
	//~ private $movil;
	//~ private $email;
	//~ private $fecha_creado;
	//~ private $estado;
	
	//~ public function __construct($conexion){
		//~ $this->db = $conexion;
		//~ // Obtenemos el numero registros.
		//~ $sql = 'SELECT count(*) as num_reg FROM proveedores';
		//~ $respuesta = $this->consulta($sql);
		//~ $this->num_rows = $respuesta->fetch_object()->num_reg;
		//~ // Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	//~ }
	//~ public function consulta($sql){
		//~ $db = $this->db;
		//~ $smt = $db->query($sql);
		//~ if ($smt) {
			//~ return $smt;
		//~ } else {
			//~ $respuesta = array();
			//~ $respuesta['consulta'] = $sql;
			//~ $respuesta['error'] = $db->error;
			//~ return $respuesta;
		//~ }
	//~ }
	
	public function buscarProveedorId($idProveedor){
		$db = $this->db;
		$sql='SELECT * from proveedores where idProveedor='.$idProveedor;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			if ($result = $smt->fetch_assoc () ){
				$respuesta = $result;
			}
		}
		return $respuesta;
	}
	public function buscarProveedorNombre($nombre){
		$db = $this->db;
		$sql='SELECT * from proveedores where nombrecomercial like "%'.$nombre.'%"';
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
        $sql='SELECT idProveedor, nombrecomercial, nif, razonsocial FROM proveedores';
        $smt=$this->consulta($sql);
		if (isset($smt['datos'])){
				$proveedores = $smt['datos'];
		} else {
            $proveedores['error']=$smt['error'];
			$proveedores['consulta']=$smt['consulta'];
        }
        return $proveedores;
    }

    public function buscarProductosProveedor($idProveedor){
        $sql='SELECT * from articulosProveedores where idProveedor='.$idProveedor;
        $smt=$this->consulta($sql);
		if (gettype($smt) == 'object') {
            while ($fila = $smt->fetch_assoc()){
				$productos_provedor[] = $fila;
			}
		} else {
            $productos_provedor['error']=$smt['error'];
			$productos_provedor['consulta']=$smt['consulta'];
        }
        //~ error_log('En mod_productos/tareas-> buscarProductosProveedor:'.json_encode($productos_provedor));

        return $productos_provedor;
    }
	
}

?>
