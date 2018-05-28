<?php 


include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';


class ClaseCliente extends modelo{
	
	public function getCliente($id){
		$sql= 'SELECT * FROM clientes WHERE idClientes='.$id;
		return $this->consulta($sql);
	}
	public function getTicket($id){
		$sql='SELECT Numticket as num , Fecha as fecha , total ,id FROM ticketst WHERE idCliente= '.$id.' order by Numticket desc';
		return $this->consulta($sql);
	}
	public function getFacturas($id){
		$sql='SELECT Numfaccli as num, Fecha as fecha, total, id FROM facclit WHERE idCliente='.$id.' order by id desc';
		return $this->consulta($sql);
	}
	public function getAlbaranes($id){
		$sql='SELECT Numalbcli as  num, Fecha as fecha, total, id FROM albclit WHERE idCliente='.$id.' order by id desc';
		return $this->consulta($sql);
	}
	public function getPedidos($id){
		$sql='SELECT Numpedcli as num, FechaPedido as fecha, total, id FROM pedclit WHERE idCliente='.$id.' order by id desc';
		return $this->consulta($sql);
	}
	public function adjuntosCliente($id){
		$respuesta=array();
		$respuesta['tickets']=$this->getTicket($id);
		$respuesta['facturas']=$this->getFacturas($id);
		$respuesta['albaranes']=$this->getAlbaranes($id);
		$respuesta['pedidos']=$this->getPedidos($id);
		return $respuesta;
	}
	public function modificarDatosCliente($datos, $id){
		$sql='UPDATE `clientes` SET Nombre="'.$datos['nombre'].'" , razonsocial="'.$datos['razonsocial'].'" , 
		nif="'.$datos['nif'].'" , direccion="'.$datos['direccion'].'" , codpostal="'.$datos['codpostal'].'" , telefono="'.$datos['telefono']
		.'" , movil="'.$datos['movil'].'" , fax="'.$datos['fax'].'" , email="'.$datos['email'].'" , estado="'.$datos['estado'].'" ,
		fomasVenci='."'".$datos['formasVenci']."'".' WHERE idClientes='.$id;
		//~ $consulta=$this->consulta($sql);
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	public function addcliente($datos){
		$sql='INSERT INTO `clientes`( `Nombre`, `razonsocial`, 
		`nif`, `direccion`, `codpostal`, `telefono`, `movil`, `fax`, `email`, 
		`estado`, `fomasVenci`, `fecha_creado`) VALUES ("'.$datos['nombre'].'", "'.$datos['razonsocial'].'", 
		"'.$datos['nif'].'", "'.$datos['direccion'].'", "'.$datos['codpostal'].'", "'.$datos['telefono'].'",
		 "'.$datos['movil'].'", "'.$datos['fax'].'", "'.$datos['email'].'", "'.$datos['estado'].'", '."'".$datos['formasVenci']."'".', NOW())';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
}


?>
