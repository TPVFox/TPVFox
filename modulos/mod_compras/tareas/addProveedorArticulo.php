<?php 
//@Objetivo: comprobar si ya existe un registro de proveedores articulos si es así modificarlo y si nno crearlo
			$fechaActualizacion=date('Y-m-d');
			$estado="activo";
			$respuesta=array();
			$datos=array(
				'idArticulo'=>$_POST['idArticulo'],
				'refProveedor'=>$_POST['refProveedor'],
				'idProveedor'=>$_POST['idProveedor'],
				'coste'=>$_POST['coste'],
				'fecha'=>$fechaActualizacion,
				'estado'=>$estado
			);
			$datosArticulo=$CArticulos->buscarReferencia($_POST['idArticulo'], $_POST['idProveedor']);
			if (isset($datosArticulo['error'])){
					$respuesta['error']=$datosArticulo['error'];
					$respuesta['consulta']=$datosArticulo['consulta'];
			}else{
				if (isset($datosArticulo['idArticulo'])){
					$modArt=$CArticulos->modificarProveedorArticulo($datos);
					if (isset($modArt['error'])){
						$respuesta['error']=$modArt['error'];
						$respuesta['consulta']=$modArt['consulta'];
					}
				}else{
					$addNuevo=$CArticulos->addArticulosProveedores($datos);	
					if (isset($addNuevo['error'])){
						$respuesta['error']=$addNuevo['error'];
						$respuesta['consulta']=$addNuevo['consulta'];
					}
				}
			}
?>
