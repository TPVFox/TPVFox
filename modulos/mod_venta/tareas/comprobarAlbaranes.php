<?php
//Objetivo:
		//Comprobar los albaranes con estado guardado que son del cliente seleccionado
		$idCliente=$_POST['idCliente'];
		$estado="Guardado";
		$respuesta=array();
			if ($idCliente>0){
				$comprobar=$CalbAl->ComprobarAlbaranes($idCliente, $estado);
				if (isset($comprobar['error'])){
					$respuesta['error']=$comprobar['error'];
					$respuesta['consulta']=$comprobar['consulta'];
				}else{
					if (isset ($comprobar['alb'])){
						if ($comprobar['alb']==1){
							$respuesta['alb']=1;
							
						}else{
							$respuesta['alb']=0;
							
						}	
					}else{
						$respuesta['alb']=0;
						$respuesta['sql']=$comprobar['sql'];
					}
				}
			}
?>
