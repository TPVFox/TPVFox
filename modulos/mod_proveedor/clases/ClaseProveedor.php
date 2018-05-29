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
		$sql='SELECT Numalbpro as num. Fecha as fecha, total , id FROM albprot WHERE idProveedor='.$id;
		return $this->consulta($sql);
	}
	
	public function getPedidos($id){
		$sql='SELECT Numpedpro as num, FechaPedido as fecha , total, id FROM pedprot WHERE idProveedor='.$id;
		return $this->consulta($sql);
	}
	
}

?>
