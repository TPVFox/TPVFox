<?php 
class AlbaranesVentas{
	private $idalbcli;
	private $numalbcli;
	private $numtemp_albcli;
	private $fecha;
	private $idTienda;
	private $idUsuario;
	private $idCliente;
	private $estado;
	private $formaPago;
	private $entregado;
	private $total;
	private $idalbcclitemporal;
	private $fechaInicioTemporal;
	private $fechaFinalTemporal;
	private $totalTemporal;
	private $total_ivasTemporal;
	private $productos;
	private $idabcclilinea;
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
	private $idalbIva;
	private $ivaalbIva;
	private $importeIva;
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
	public function insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $productos, $idCliente){
		$db = $this->db;
		$UnicoCampoPedidos=json_encode($pedidos);
		$UnicoCampoProductos=json_encode($productos);
		$smt = $db->query ('INSERT INTO albcliltemporales ( idUsuario , idTienda , estadoAlbCli , fechaInicio, idClientes, Pedidos, Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoAlbaran.'" , "'.$fecha.'", '.$idCliente.' , '."'".$UnicoCampoPedidos."'".', '."'".$UnicoCampoProductos."'".')');
		$sql='INSERT INTO albcliltemporales ( idUsuario , idTienda , estadoAlbCli , fechaInicio, idClientes, Productos, Pedidos) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoAlbaran.'" , "'.$fecha.'", '.$idCliente.' , '."'".$UnicoCampoProductos."'".', '."'".$UnicoCampoPedidos."'".')';

		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['sql']=$sql;
		
		return $respuesta;
	}
	public function modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $idTemporal){
		$db = $this->db;
		$UnicoCampoPedidos=json_encode($pedidos);
		$UnicoCampoProductos=json_encode($productos);
		$smt=$db->query('UPDATE albcliltemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoAlbCli='.$estadoAlbaran.' , fechaInicio='.$fecha.' , Pedidos='."'".$UnicoCampoPedidos."'". ' ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$idTemporal);
		return $idTemporal;
	}
	public function addNumRealTemporal($idTemporal,  $numAlbaran){
		$db = $this->db;
		$UnicoCampoPedidos=json_encode($pedidos);
		$smt=$db->query('UPDATE albcliltemporales SET numalbcli ='.$numAlbaran.' WHERE id='.$idTemporal);
		return $idTemporal;
	}
	public function buscarDatosAlabaranTemporal($idAlbaranTemporal) {
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albcliltemporales WHERE id='.$idAlbaranTemporal);
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}

	
	
}

?>
