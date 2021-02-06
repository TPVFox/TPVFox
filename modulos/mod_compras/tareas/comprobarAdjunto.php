<?php 
//@Objetivo:
//comprobar que el proveedor tiene albaran o pedido en estado guardado
			$estado="Guardado";
			$idProveedor=$_POST['idProveedor'];
			$dedonde=$_POST['dedonde'];
			$respuesta=array();
			if ($dedonde=="factura"){
				$buscar=$CAlb->albaranesProveedorGuardado($idProveedor, $estado);
				if (isset($buscar['error'])){
						$respuesta['error']=$buscar['error'];
						$respuesta['consulta']=$buscar['consulta'];
				}
			}else{
				$buscar=$CPed->pedidosProveedorGuardado($idProveedor, $estado);
				if (isset($buscar['error'])){
						$respuesta['error']=$buscar['error'];
						$respuesta['consulta']=$buscar['consulta'];
				}
			}
			if (count($buscar)>0){
					$respuesta['bandera']=1;
			}else{
					$respuesta['bandera']=2;
			}
?>
