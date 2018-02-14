<?php 

class FacturasCompras{
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM facprot';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function TodosTemporal(){
			$db = $this->db;
			$smt = $db->query ('SELECT * from facproltemporales');
			$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
		
	}
	public function TodosFactura(){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numfacpro , a.Fecha , b.nombrecomercial, a.total, a.estado FROM `facprot` as a LEFT JOIN proveedores as b on a.idProveedor=b.idProveedor ');
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function sumarIva($numFactura){
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from facproIva where Numfacpro ='.$numFactura);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function datosFactura($idFactura){
		$db=$this->db;
		$smt = $db->query ('SELECT * from facprot where id='.$idFactura);
			if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function ProductosFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * from  facprolinea where idfacpro='.$idFactura);
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function IvasFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * from  facproIva where idfacpro='.$idFactura);
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function albaranesFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * from  albprofac where idFactura='.$idFactura);
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function buscarFacturaTemporal($idFacturaTemporal){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM facproltemporales WHERE id='.$idFacturaTemporal);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function buscarFacturaNumero($numFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM facprot WHERE Numfacpro='.$numFactura);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
}

?>
