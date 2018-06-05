<?php 
//@Objetivo:
			//Añade un albaran temporal es igual que la de pedidos pero esta vez en la tabla temporal de albaranes
			$idAlbaranTemporal=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idAlbaran=$_POST['idReal'];
			$fecha=$_POST['fecha'];
			$fecha = new DateTime($fecha);
			$fecha = $fecha->format('Y-m-d');
			$hora=$_POST['hora'];
			if($hora !=""){
				$fecha1=$fecha.' '.$hora.':00';
				$fecha=date_format(date_create($fecha1), 'Y-m-d H:i:s');
			}
			$productos=json_decode($_POST['productos']);
			if (isset($_POST['pedidos'])){
				$pedidos=$_POST['pedidos'];
			}else{
				$pedidos=array();
			}
			$suNumero=$_POST['suNumero'];
			$idProveedor=$_POST['idProveedor'];
			$existe=0;
		//Si existe el albaran  temporal se modifica , devuelve el control de errores
		//Si no tiene  errores devuelve el idTemporal y la bandera que se utiliza el el js de existe
			if ($idAlbaranTemporal>0){
				$rest=$CAlb->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idAlbaranTemporal, $productos, $pedidos, $suNumero);
					if (isset($rest['error'])){
						$respuesta['error']=$rest['error'];
						$respuesta['consulta']=$rest['consulta'];
						//~ break;
					}else{
						$existe=1;
						$res=$rest['idTemporal'];
						$respuesta['id']=$rest['idTemporal'];
					}
			}else{
				//Si no existe el temporal se crea , con control de errores 
				$rest=$CAlb->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $pedidos, $suNumero);
				if (isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
					$existe=0;
						//~ break;
					
				}else{
					$existe=0;
					$idAlbaranTemporal=$rest['id'];
					$respuesta['id']=$rest['id'];
					$respuesta['sqlTemporal']=$rest['sql'];
				}
			}
			//Si es un albarán que se está modificando se guarda en el Real el idTemporal
			//Y se cambia el estado a Sin guardar
			//Con control de errores las dos funciones
			if ($idAlbaran>0){
				$modId=$CAlb->addNumRealTemporal($idAlbaranTemporal, $idAlbaran);
				if (isset($modId['error'])){
						$respuesta['error']=$modId['error'];
						$respuesta['consulta']=$modId['consulta'];
						//~ break;
				}
				$estado="Sin Guardar";
				$modEstado=$CAlb->modEstadoAlbaran($idAlbaran, $estado);
				if (isset($modEstado['error'])){
						$respuesta['error']=$modEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
						//~ break;
				}
			}
			if ($productos){
				$CalculoTotales = recalculoTotales($productos);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=round($CalculoTotales['total'],2);
				$respuesta['totales']=$CalculoTotales;
				$modTotal=$CAlb->modTotales($idAlbaranTemporal, $respuesta['total'], $CalculoTotales['subivas']);
				if (isset($modTotal['error'])){
						$respuesta['error']=$modTotal['error'];
						$respuesta['consulta']=$modTotal['consulta'];
						//~ break;
				}
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$htmlTotales=htmlTotales($CalculoTotales);
				$respuesta['htmlTabla']=$htmlTotales['html'];
				
			}
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];

?>
