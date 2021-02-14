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
		$sql='SELECT * from proveedores where nombrecomercial like "%'.$nombre.'%"';
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
