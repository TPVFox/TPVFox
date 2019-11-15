<?php
include_once  $URLCom.'/clases/ClaseTFModelo.php';

class FormasPago extends TFModelo{

	public function todas(){
		$sql = 'SELECT * from formasPago';
        $formasPagoPrincipal = $this->consulta($sql);
		return $formasPagoPrincipal['datos'];
	}
	public function formadePagoSinPrincipal($id){
        $sql = 'SELECT * from formasPago where id<>'.$idforma;
        $formasPagoSinPrincipal = $this->consulta($sql);
        return $formasSinPagoPrincipal['datos'];
	}
	public function datosPrincipal($idPrincipal){
        $sql = 'SELECT * from formasPago where id='.$idPrincipal;
        $resultado = $this->consulta($sql);
        return $resultado['datos'][0];
	}
}

?>
