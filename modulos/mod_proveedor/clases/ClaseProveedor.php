<?php 

include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';

class ClaseProveedor extends modelo{
	
	public function getProveedor($id){
		$sql='SELECT * from proveedores where idProveedor='.$id;
		return $this->consulta($sql);
	}
	
	public function getFacturas($id){
		$sql='SELECT Numfacpro  as num, Fecha as fecha, total, id FROM facprot WHERE idProveedor='.$id;
		return $this->consulta($sql);
	}
	
	public function getAlbaranes($id){
		$sql='SELECT Numalbpro as num, Fecha as fecha, total , id FROM albprot WHERE idProveedor='.$id;
		return $this->consulta($sql);
	}
	
	public function getPedidos($id){
		$sql='SELECT Numpedpro as num, FechaPedido as fecha , total, id FROM pedprot WHERE idProveedor='.$id;
		return $this->consulta($sql);
	}
	public function adjuntosProveedor($id){
		$respuesta=array();
		$respuesta['facturas']=$this->getFacturas($id);
		$respuesta['albaranes']=$this->getAlbaranes($id);
		$respuesta['pedidos']=$this->getPedidos($id);
		return $respuesta;
	}
	public function modificarDatosProveedor($datos){
		$sql='UPDATE `proveedores` SET `nombrecomercial`="'.$datos['nombrecomercial'].'",
		`razonsocial`="'.$datos['razonsocial'].'",`nif`="'.$datos['nif'].'",`direccion`="'.$datos['direccion'].'",
		`telefono`="'.$datos['telefono'].'",`fax`="'.$datos['fax'].'",`movil`="'.$datos['movil'].'",
		`email`="'.$datos['email'].'",`estado`="'.$datos['estado'].'" WHERE idProveedor='.$datos['idProveedor'];
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	public function addProveedorNuevo($datos){
		$sql='INSERT INTO `proveedores`( `nombrecomercial`, `razonsocial`, 
		`nif`, `direccion`, `telefono`, `fax`, `movil`, `email`, `fecha_creado`, 
		`estado`) VALUES ("'.$datos['nombrecomercial'].'","'.$datos['razonsocial'].'",
		"'.$datos['nif'].'","'.$datos['direccion'].'","'.$datos['telefono'].'","'.$datos['fax'].'",
		"'.$datos['movil'].'","'.$datos['email'].'",NOW() , "'.$datos['estado'].'" )';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
}

?>
