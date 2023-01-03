<?php
include_once  $URLCom.'/clases/ClaseTFModelo.php';
class TiposVencimientos extends TFModelo{
	private $id;
	private $descripcion;
	private $dias;
	
	public function todos(){
		$sql = 'SELECT * from tiposVencimiento';
        $tiposPrincipal = $this->consulta($sql);
		return $tiposPrincipal;
	}

    public function MenosPrincipal($idPrincipal){
		$sql = 'SELECT * from tiposVencimiento WHERE id<>'.$idPrincipal;
        $tiposPrincipal = $this->consulta($sql);
		return $tiposPrincipal;
	}

    public function datosPrincipal($idPrincipal){
        $resultado = array();
        $sql = 'SELECT * from tiposVencimiento where id='.$idPrincipal;
        $smt = $this->consulta($sql);
        if (isset($smt['datos'])){
            $resultado = $smt['datos'];
        } 
		return $resultado;
	}
}



?>
