<?php 

include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';

class ClaseProveedor extends modelo{
	
	public function getProveedor($id){
		$sql='SELECT * from proveedores where idProveedor='.$id;
		return $this->consulta($sql);
	}
	
	
	
}

?>
