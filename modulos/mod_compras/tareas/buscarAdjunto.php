<?php 
	//@objetivo:
		//buscar si el numero de adjunto (número de pedido o albarán )
		//carga los datos principales y sus productos
			$respuesta=array();
			$numAdjunto=$_POST['numReal'];
			$idProveedor=$_POST['idProveedor'];
			$estado="Guardado";
			$dedonde=$_POST['dedonde'];
			if ($dedonde=="albaran"){
				$datosAdjunto=$CPed->buscarPedidoProveedorGuardado($idProveedor, $numAdjunto, $estado);
				if (isset($datosAdjunto['error'])){
					$respuesta['error']=$datosAdjunto['error'];
					$respuesta['consola']=$datosAdjunto['consulta'];
				}
			}else{
				$datosAdjunto=$CAlb->buscarAlbaranProveedorGuardado($idProveedor, $numAdjunto, $estado);
				if (isset($datosAdjunto['error'])){
					$respuesta['error']=$datosAdjunto['error'];
					$respuesta['consola']=$datosAdjunto['consulta'];
				}else{
                    $respuesta['productosAlbaran']=$datosAdjunto;
                }
			}
			if (isset($datosAdjunto['Nitem'])){
				$respuesta['temporales']=1;
				if ($dedonde=="albaran"){
					$respuesta['datos']['NumAdjunto']=$datosAdjunto['Numpedpro'];
					$respuesta['datos']['idAdjunto']=$datosAdjunto['id'];
					$productosAdjunto=$CPed->ProductosPedidos($datosAdjunto['id']);
					if (isset($productosAdjunto['error'])){
						$respuesta['error']=$productosAdjunto['error'];
						$respuesta['consulta']=$productosAdjunto['consulta'];
					}else{
						$respuesta['productos']=$productosAdjunto;
					}
				}else{
					$respuesta['datos']['NumAdjunto']=$datosAdjunto['Numalbpro'];
					$respuesta['datos']['idAdjunto']=$datosAdjunto['id'];
					$respuesta['datos']['totalSiva']=$datosAdjunto['totalSiva'];
					$respuesta['datos']['Su_numero']=$datosAdjunto['Su_numero'];
					$productosAdjunto=$CAlb->ProductosAlbaran($datosAdjunto['id']);
					if (isset($productosAdjunto['error'])){
						$respuesta['error']=$productosAdjunto['error'];
						$respuesta['consulta']=$productosAdjunto['consulta'];
					}else{
						$respuesta['productos']=$productosAdjunto;
					}
				}
				$date = new DateTime($datosAdjunto['Fecha']);
				$respuesta['datos']['fecha']=date_format($date, 'Y-m-d');
				$respuesta['datos']['total']=$datosAdjunto['total'];
				$respuesta['datos']['estado']="activo";
				
				$respuesta['Nitems']=$datosAdjunto['Nitem'];
				
			}else{
				$respuesta['datos']=$datosAdjunto;
				$modal=modalAdjunto($datosAdjunto['datos'], $dedonde, $BDTpv);
				$respuesta['html']=$modal['html'];
			}

?>
