<?php 
	//@Objetivo:
		//Añadir factura temporal hace exactamente lo mismo que el añadir albarán temporal pero esta vez con facturas
			$idFacturaTemp=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estadoFactura=$_POST['estado'];
			$numFactura=$_POST['idReal'];
			$fecha=$_POST['fecha'];
            $fecha = new DateTime($fecha);
			$fecha = $fecha->format('Y-m-d');
			$productos=json_decode($_POST['productos']);
			$idCliente=$_POST['idCliente'];
			if(isset($_POST['albaranes'])){
				$albaranes=$_POST['albaranes'];
			}else{
				$albaranes=array();
			}
			$respuesta=array();
			$existe=0;
			$res=$idFacturaTemp;
			if ($idFacturaTemp>0){
				$rest=$CFac->modificarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $idFacturaTemp, $productos);
				if(isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
				}else{
					$existe=1;	
					$pro=$rest['productos'];
				}
			}else{
				$rest=$CFac->insertarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $productos, $idCliente);
				if(isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
				}else{
					$existe=0;
					$pro=$rest['productos'];
					$res=$rest['id'];
					$idFacturaTemp=$res;
				}
				
			}
			$respuesta['numFactura']=$numFactura;
			if ($numFactura>0){
				$modId=$CFac->addNumRealTemporal($idFacturaTemp, $numFactura);
				if(isset($modId['error'])){
					$respuesta['error']=$modId['error'];
					$respuesta['consulta']=$modId['consulta'];
				}
			}
			if (isset($productos)){
				$CalculoTotales = recalculoTotales($productos);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=round($CalculoTotales['total'],2);
				$respuesta['totales']=$CalculoTotales;
				$modTotal=$CFac->modTotales($res, $respuesta['total'], $CalculoTotales['subivas']);
				if(isset($modTotal['error'])){
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
