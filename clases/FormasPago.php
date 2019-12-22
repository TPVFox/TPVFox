<?php
include_once  $URLCom.'/clases/ClaseTFModelo.php';

class FormasPago extends TFModelo{

	public function todas(){
		$sql = 'SELECT * from formasPago';
        $formasPagoPrincipal = $this->consulta($sql);
		return $formasPagoPrincipal['datos'];
	}
	public function formadePagoSinPrincipal($id){
        $respuesta = 0;
        $sql = 'SELECT * from formasPago where id<>'.$id;
        $r = $this->consulta($sql);
        if (isset($r['datos'])){
            $respuesta = $r['datos'];
        }
        return $respuesta;
	}
	public function datosPrincipal($idPrincipal){
        $respuesta = 0;
        $sql = 'SELECT * from formasPago where id='.$idPrincipal;
        $r = $this->consulta($sql);
        if (isset($r['datos'])){
            $respuesta = $r['datos'][0];
        }
        return $respuesta;
	}
}

?>
