<?php 


include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';


class ClaseCliente extends modelo{
	
	public function getCliente($id){
		$sql= 'SELECT * FROM clientes WHERE idClientes='.$id;
		return $this->consulta($sql);
	}
	
	public function getTicket($id){
		
	}
	public function getFacturas($id){
		
	}
	public function getAlbaranes($id){
		
	}
	public function getPedidos($id){
		
	}
}


?>
