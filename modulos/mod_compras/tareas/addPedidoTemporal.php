<?php
//@Objetivo:
			//Añadir un pedido temporal, recibe los campos necesarios para añadir el pedido
			//Si ya existe modifica el registro si no lo crea, devuelve siempre el id del temporal
			$numPedidoTemp=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estadoPedido=$_POST['estado'];
			$idPedido=$_POST['idReal'];
			$fecha=$_POST['fecha'];
			$fecha = new DateTime($fecha);
			$fecha = $fecha->format('Y-m-d');
			$productos=json_decode($_POST['productos']);
			$idProveedor=$_POST['idProveedor'];
			$existe=0; // Variable para devolver y saber si modifico o insert.
			//Existe la utilizo como bandera para que el javascript solo me cree una vez la url del temporal
			if ($numPedidoTemp>0){
				//Si existe el número temporal se modifica el temporal
				$rest=$CPed->modificarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $numPedidoTemp, $productos);
				if (isset($rest['error'])){
						$respuesta['error']=$rest['error'];
						$respuesta['consulta']=$rest['consulta'];
						//~ echo json_encode($respuesta);
						//~ break;
				}else{
					$existe=1;
				}
			}else{
				//Si no existe crea un temporal nuevo
				$rest=$CPed->insertarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $productos, $idProveedor);
				if (isset($rest['error'])){// Control de errores
						$respuesta['error']=$rest['error'];
						$respuesta['consulta']=$rest['consulta'];
						//~ echo json_encode($respuesta);
						//~ break;
				}else{
					$existe=0;
					$numPedidoTemp=$rest['id'];
				}
			}
			$pro=$rest['productos'];
			 if ($idPedido>0){
				 //Si existe u pedido real se modifica el temporal para indicarle que tiene un numero temporal
				//Existe idPedido, estamos modificacion de un pedido,añadimos el número del pedido real al registro temporal
				//y modificamos el estado del pedido real a sin guardar.
				$modId=$CPed->addNumRealTemporal($numPedidoTemp, $idPedido);
				if (isset($modId['error'])){
						$respuesta['error']=$modId['error'];
						$respuesta['consulta']=$modId['consulta'];
						//~ break;
				}
				$estado="Sin Guardar";
				// Se modifica el estado del pedido real a sin guardar
				$modEstado=$CPed->modEstadoPedido($idPedido, $estado);
				if (isset($modId['error'])){
						$respuesta['error']=$modEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
						//~ break;
				}
			 }
			if ($productos){
				//Recalcula el valor de los productos
					$CalculoTotales = recalculoTotales($productos);
					$total=round($CalculoTotales['total'],2);
					$respuesta['total']=round($CalculoTotales['total'],2);
					$respuesta['totales']=$CalculoTotales;
					$modTotal=$CPed->modTotales($numPedidoTemp, $respuesta['total'], $CalculoTotales['subivas']);
					$respuesta['sqlmodtotal']=$modTotal['sql'];
					$htmlTotales=htmlTotales($CalculoTotales);
					$respuesta['htmlTabla']=$htmlTotales['html'];
				}
				$respuesta['id']=$numPedidoTemp;
				$respuesta['existe']=$existe;
				$respuesta['productos']=$_POST['productos']; 
?>
