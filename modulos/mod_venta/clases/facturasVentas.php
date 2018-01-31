<?php 
class FacturasVentas{
	private $id;
	private $Numfaccli;
	private $Numtemp_faccli;
	private $fecha;
	private $idTienda;
	private $idUsuario;
	private $idCliente;
	private $estado;
	private $formaPago;
	private $entregado;
	private $total;
	private $idfacclilinea;
	private $idArticulo;
	private $cref;
	private $ccodbar;
	private $cdetalle;
	private $ncant;
	private $nunidades;
	private $precioCiva;
	private $iva;
	private $nfila;
	private $estadoLinea;
	private $idfaccliiva;
	private $importeiva;
	private $totalbase;
	
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM pedclit';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	
	public function TodosTemporal(){
			$db = $this->db;
			$smt = $db->query ('SELECT * from faccliltemporales');
			$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
		
	}
	
	
	public function TodosAlbaranes(){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numfaccli , a.Fecha , b.Nombre, a.total, a.estado FROM `facclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes ');
		$albaranesPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranesPrincipal,$result);
		}
		return $albaranesPrincipal;
	}
	
	public function datosFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM facclit WHERE id= '.$idFactura );
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function ProductosFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM facclilinea WHERE idfaccli= '.$idFactura );
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function IvasFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM faccliiva WHERE idalbcli= '.$idFactura );
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function AlbaranesFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albFacCli WHERE idFactura= '.$idFactura );
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function buscarDatosFacturasTemporal($idFacturaTemporal) {
		$db=$this->db;
		$smt=$db->query('SELECT * FROM faccliltemporales WHERE id='.$idFacturaTemporal);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function EliminarRegistroTemporal($idTemporal, $idFactura){
		$db=$this->db;
		if ($idAlbaran>0){
			$smt=$db->query('DELETE FROM faccliltemporales WHERE numfaccli ='.$idFactura);
			$sql='DELETE FROM faccliltemporales WHERE numfaccli ='.$idFactura;
		}else{
			$smt=$db->query('DELETE FROM faccliltemporales WHERE id='.$idTemporal);
		}
		return $sql;
	}
}


?>