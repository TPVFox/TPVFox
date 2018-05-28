<?php 


include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';


class ClaseCliente extends modelo{
	
	public function getCliente($id){
		$sql= 'SELECT * FROM clientes WHERE idClientes='.$id;
		return $this->consulta($sql);
	}
	
	public function getTicket($id){
		$sql='SELECT Numticket  , Fecha , total ,id FROM ticketst WHERE idCliente= '.$id.' order by Numticket desc';
		return $this->consulta($sql);
	}
	public function getFacturas($id){
		$sql='SELECT Numfaccli, Fecha, total, id FROM facclit WHERE idCliente='.$id.' order by id desc';
		return $this->consulta($sql);
	}
	public function getAlbaranes($id){
		
	}
	public function getPedidos($id){
		
	}
}


?>
