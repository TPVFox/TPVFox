<?php
//Objetivo:
		//Comprobar los albaranes con estado guardado que son del cliente seleccionado
		$idCliente  = $_POST['idCliente'];
        $dedonde    = $_POST['dedonde'];
		$estado="Guardado";
		$respuesta=array();
			if ($idCliente>0){
                if ($dedonde == 'factura') {
                    $comprobar=$CalbAl->ComprobarAlbaranes($idCliente, $estado);
                }
                if ($dedonde == 'albaran') {
                    $comprobar=$Cpedido->ComprobarPedidos($idCliente, $estado);
                }
				if (isset($comprobar['error'])){
					$respuesta['error']=$comprobar['error'];
					$respuesta['consulta']=$comprobar['consulta'];
				}else{
                    $respuesta = $comprobar;
				}
			}
?>
