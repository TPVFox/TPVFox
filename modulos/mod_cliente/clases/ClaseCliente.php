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
}


?>
