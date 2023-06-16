<?php 
include_once $URLCom.'/clases/ClaseTFModelo.php';

class Proveedores extends TFModelo {
	
	public function buscarProveedorId($idProveedor){
		$sql='SELECT * from proveedores where idProveedor='.$idProveedor;
		$smt=$this->consulta($sql);
		if (isset($smt['error'])){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
            $respuesta = $smt['datos'][0];
		}
		return $respuesta;
	}
	public function buscarProveedorNombre($nombre){
        // Buscar por Nombre Comercial o razon social.
        // y por palabras
        $palabras = explode(' ', $nombre);
        $likes = array();
        foreach ($palabras as $key => $palabra) {
            // Montamos consulta por palabras de varias palabras, en nombre o razon social
            $likes[] =  'nombrecomercial LIKE "%' . $palabra . '%" ';
        } 
        $sql = 'SELECT * FROM proveedores WHERE ';
        $whereNombre = '';
        if (count($likes) >0){
            // Si no hay palabras ya no buscamos por nombre
            $whereNombre= '('.implode(' and ', $likes).')';
            $sql.= $whereNombre.' OR ';
            // Ahora hacemos lo mismo, pero con el campo razon social, por esos sutituimos Nombre por razonsocial
            $sql.= str_replace('nombrecomercial','razonsocial',$whereNombre);
        } 
        //~ error_log($sql);
		$smt=$this->consulta($sql);
		if (isset($smt['error'])){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			$respuesta=$smt;
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
        $sql='SELECT idProveedor, nombrecomercial, nif, razonsocial, estado FROM proveedores';
        $smt=$this->consulta($sql);
		if (isset($smt['datos'])){
				$proveedores = $smt['datos'];
		} else {
			if (count($proveedores) >0 ) {
				$proveedores['error']=$smt['error'];
				$proveedores['consulta']=$smt['consulta'];
			} else {
				$proveedores['error']=' No hay proveedores';
				$proveedores['consulta']=$smt['consulta'];
			}
        }
        return $proveedores;
    }

    public function buscarProductosProveedor($idProveedor){
        $sql='SELECT * from articulosProveedores where idProveedor='.$idProveedor;
        $smt=$this->consulta($sql);
		if (isset($smt['datos'])){
				$productos_provedor =$smt['datos'];
		} else {
            $productos_provedor['error']=$smt['error'];
			$productos_provedor['consulta']=$smt['consulta'];
        }
        return $productos_provedor;
    }
	
}

?>
