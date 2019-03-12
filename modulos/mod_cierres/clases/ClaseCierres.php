<?php
/* Objetivo de esta clase
 *   - Crear un objeto que contenga cierres con todos los datos de estos.
 *   - Tener los parametros cargados, para interactuar con los datos.
 *
 * 
 * */


include_once $URLCom.'/clases/ClaseConexion.php';

class ClaseCierres extends ClaseConexion{
	public $view ; //string ruta de la vista que estamos
    public $BDTpv ; // Objeto de conexion

    public function __construct()
	{
		parent::__construct();
		$this->BDTpv	= parent::getConexion();
		$this->view = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['PHP_SELF']);
	}


    public function obtenerCierres($filtro='',$limite='') {
	// Function para obtener cierres y listarlos
	//tablas usadas: - cierres
				//	 - usuarios
    $BDTpv = $this->BDTpv;
    $resultado = array();
	if (trim($filtro) !=''){
		$filtro = ' '.$filtro;
	}
	$consulta = "Select c.*, u.nombre as nombreUsuario FROM cierres AS c "
				." LEFT JOIN usuarios AS u ON c.idUsuario=u.id ".$filtro.$limite; 
	
	$Resql = $BDTpv->query($consulta);	
	if ($Resql){
		while ($datos = $Resql->fetch_assoc()) {
			$resultado[]=$datos;
		}
	} else  {
		$resultado['consulta'] = $consulta;
		$resultado['error'] = $BDTpv->error;
	}
	//$resultado ['sql'] = $consulta;
	return $resultado;
}



}
