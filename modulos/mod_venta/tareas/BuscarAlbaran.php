<?php 
//Objetivo:
		//Busca el albarán indicado, si recibe resultado guarda el albaran y muestra los productos de este 
		//Si no muestra un albarán
			$busqueda=$_POST['busqueda'];
			$idCliente=$_POST['idCliente'];
			$res=$CalbAl->AlbaranClienteGuardado($busqueda, $idCliente);
			if (isset($res['error'])){
				$respuesta['error']=$res['error'];
				$respuesta['consulta']=$res['consulta'];
			}else{
				if (isset($res['Nitem'])){
						$respuesta['temporales']=1;
						$respuesta['datos']['Numalbcli']=$res['Numalbcli'];
						$respuesta['datos']['idalbcli']=$res['id'];
						$respuesta['datos']['fecha']=$res['Fecha'];
						$respuesta['datos']['total']=$res['total'];
						$respuesta['datos']['idAlbaran']=$res['id'];
						$respuesta['datos']['estado']="Activo";
						$respuesta['Nitems']=$res['Nitem'];
						$productosAlbaran=$CalbAl->ProductosAlbaran($res['id']);
						if(isset($productosAlbaran['error'])){
							$respuesta['error']=$productosAlbaran['error'];
							$respuesta['consulta']=$productosAlbaran['consulta'];
						}
						$respuesta['productos']=$productosAlbaran;
					
				}else{
					$respuesta=$res;
					$modal=modalAdjunto($res['datos']);
					$respuesta['html']=$modal['html'];
					
				}
			}

?>
