<?php 
//@Objetivo:
			//Añadir factura temporal 
			// [NOTA] Es igual que añadir pedido temporal con la diferencia de que cambia la tabla temporal de facturas
			$idFacturaTemp=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idFactura=$_POST['idReal'];
			$fecha=$_POST['fecha'];
			$fecha = new DateTime($fecha);
			$fecha = $fecha->format('Y-m-d');
			$respuesta=array();
			$productos=json_decode($_POST['productos']);
            $albaranes=array();
			if(isset ($_POST['albaranes'])){
				$albaranes=$_POST['albaranes'];
			}
			$idProveedor=$_POST['idProveedor'];
			$suNumero=$_POST['suNumero'];
			if ($idFacturaTemp>0){
				$rest=$CFac->modificarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idFacturaTemp, $productos, $albaranes, $suNumero);
				if(isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
				}else{
					$existe=1;
					$res=$rest['idTemporal'];
					$pro=$rest['productos'];
				}
			}else{
				$rest=$CFac->insertarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $albaranes, $suNumero);
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
			if ($idFactura>0){
				$modId=$CFac->addNumRealTemporal($idFacturaTemp, $idFactura);
				if (isset($modId['error'])){
					$respuesta['error']=$modId['error'];
					$respuesta['consulta']=$modId['consulta'];
				}else{
					$estado="Sin Guardar";
					$modEstado=$CFac->modEstadoFactura($idFactura, $estado);
					if (isset($modEstado['error'])){
						$respuesta['error']=$modEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
					}
				}
			}
			if ($productos){
				$CalculoTotales = recalculoTotales($productos);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=round($CalculoTotales['total'],2);
				$respuesta['totales']=$CalculoTotales;
				$modTotal=$CFac->modTotales($res, $respuesta['total'], $CalculoTotales['subivas']);
				if (isset($modTotal['error'])){
						$respuesta['error']=$modTotal['error'];
						$respuesta['consulta']=$modTotal['consulta'];
				}
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$htmlTotales=htmlTotales($CalculoTotales);
				$respuesta['htmlTabla']=$htmlTotales['html'];
				
			}
			$respuesta['id']=$res;
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];
?>
