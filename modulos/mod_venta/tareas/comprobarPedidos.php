<?php
//Objetivo:
		//Comprobar los pedidos en estado guardado que son de un cliente
			$idCliente=$_POST['idCliente'];
			$estado="Guardado";
			$respuesta=array();
			if ($idCliente>0){
				$comprobar=$CcliPed->ComprobarPedidos($idCliente, $estado);
				if(isset($comprobar['error'])){
					$respuesta['error']=$comprobar['error'];
					$respuesta['consulta']=$comprobar['consulta'];
				}else{
					if (isset ($comprobar['ped'])){
						if ($comprobar['ped']==1){
							$respuesta['ped']=1;
						
						}else{
							$respuesta['ped']=0;
						}
					}else{
						$respuesta['ped']=0;
					}
				
			}
		}
?>
