<?php 
//@Objetivo:
		//añadir un pedido temporal, si existe se modifica y si no se inserta
		//A continuación se calculan los totales y desgloses 
		$idTemporal=$_POST['idTemporal'];
		$idUsuario=$_POST['idUsuario'];
		$idTienda=$_POST['idTienda'];
		$estado=$_POST['estado'];
		$fecha=$_POST['fecha'];
		$fecha = new DateTime($fecha);
		$fecha = $fecha->format('Y-m-d');
		$idReal=$_POST['idReal'];
		$idCliente=$_POST['idCliente'];
		$productos=json_decode($_POST['productos']);
		$existe=0;
		if ($idTemporal>0){
			$res=$CcliPed->ModificarPedidoTemp($idCliente, $idTemporal, $idTienda, $idUsuario, $estado, $idReal, $productos);
			if(isset($res['error'])){
				$respuesta['error']=$res['error'];
				$respuesta['consulta']=$res['consulta'];
			}
		}else{
			$res=$CcliPed->addPedidoTemp($idCliente,  $idTienda, $idUsuario, $estado, $idReal, $productos);
			if(isset($res['error'])){
				$respuesta['error']=$res['error'];
				$respuesta['consulta']=$res['consulta'];
			}else{
				$idTemporal=$res['id'];
			}
		}
		if ($idReal>0){
			$modNum=$CcliPed->ModIdReal($idTemporal, $idReal);
			if(isset($modNum['error'])){
				$respuesta['error']=$modNum['error'];
				$respuesta['consulta']=$modNum['consulta'];
			}
		}
		 if ($productos){
				$CalculoTotales = recalculoTotales($productos);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=round($CalculoTotales['total'],2);
				$respuesta['totales']=$CalculoTotales;
				$modTotal=$CcliPed->modTotales($idTemporal, $respuesta['total'], $CalculoTotales['subivas']);
				if(isset($modTotal['error'])){
					$respuesta['error']=$modTotal['error'];
					$respuesta['consulta']=$modTotal['consulta'];
				}
			
				$htmlTotales=htmlTotales($CalculoTotales);
				$respuesta['htmlTabla']=$htmlTotales['html'];
			}
			$respuesta['id']=$idTemporal;
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];


?>
