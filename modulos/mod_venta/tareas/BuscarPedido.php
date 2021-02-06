<?php 
	//@Objetivo:
		//BUscar los pedidos guardado de un cliente para el apartado albaranes, si el pedido que inserto existe guarda los datos de este
		//Si no muestra un modal con los pedidos guardados de ese cliente
	
			$busqueda=$_POST['busqueda'];
			$idCliente=$_POST['idCliente'];
			$res=$CcliPed->PedidosClienteGuardado($busqueda, $idCliente);
			if (isset($res['error'])){
				$respuesta['error']=$res['error'];
				$respuesta['consulta']=$res['consulta'];
			}else{
				$respuesta['res']=$res;
				if (isset($res['Nitem'])){
						$respuesta['datos']['Numpedcli']=$res['Numpedcli'];
						$respuesta['datos']['idPedCli']=$res['id'];
						$respuesta['datos']['idPedido']=$res['id'];
						$respuesta['datos']['fecha']=$res['FechaPedido'];
						$respuesta['datos']['total']=$res['total'];
						$respuesta['datos']['estado']="Activo";
						$respuesta['Nitems']=$res['Nitem'];
						$productosPedido=$CcliPed->ProductosPedidos($res['id']);
						$productosPedido=modificarArrayProductos($productosPedido);
						if (isset($productosPedido['error'])){
							$respuesta['error']=$productosPedido['error'];
							$respuesta['consulta']=$productosPedido['consulta'];
						}else{
							$respuesta['productos']=$productosPedido;
						}
					
				}else{
					$respuesta=$res;
					$modal=modalAdjunto($res['datos']);
					$respuesta['html']=$modal['html'];
					
				}
			}


?>
