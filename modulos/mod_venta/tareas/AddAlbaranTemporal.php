<?php 
	//@Objetivo:
		//añadir albarán temporal, hace las comprobaciones necesarias.
			$idAlbaranTemp=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estadoAlbaran=$_POST['estado'];
			$fecha=$_POST['fecha'];
			$fecha = new DateTime($fecha);
			$fecha = $fecha->format('Y-m-d');
			if (isset($_POST['pedidos'])){
				$pedidos=$_POST['pedidos'];
			}else{
				$pedidos=array();
			}
			
			$productos=json_decode($_POST['productos']);
			$idCliente=$_POST['idCliente'];
			$idReal=$_POST['idReal'];
			$existe=0;
			$respuesta=array();
			//Si el albarán temporal existe lo modifica
			if ($idAlbaranTemp>0){
				$rest=$CalbAl->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $idAlbaranTemp, $productos);
				if (isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
				}else{
					$existe=1;
					$res=$rest['idTemporal'];
				}
			}else{
				//Si no lo inserta
				$rest=$CalbAl->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $productos, $idCliente);
				if (isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
				}else{
					$existe=0;
					$res=$rest['id'];
					$idAlbaranTemp=$res;
				}
			}
			if ($idReal>0){
				$modId=$CalbAl->addNumRealTemporal($idAlbaranTemp, $idReal);
				if (isset($modId['error'])){
					$respuesta['error']=$modId['error'];
					$respuesta['consulta']=$modId['consulta'];
				}
			}
			//recalcula los totales de los productos y modifica el total en albarán temporal
			if (isset($productos)){
				$CalculoTotales = recalculoTotales($productos);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=round($CalculoTotales['total'],2);
				$respuesta['totales']=$CalculoTotales;
				$modTotal=$CalbAl->modTotales($res, $respuesta['total'], $CalculoTotales['subivas']);
				if (isset($modTotal['error'])){
					$respuesta['error']=$modTotal['error'];
					$respuesta['consulta']=$modTotal['consulta'];	
				}
				$htmlTotales=htmlTotales($CalculoTotales);
				$respuesta['htmlTabla']=$htmlTotales['html'];
			}
			$respuesta['id']=$res;
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];
?>
