<?php 
//@Objetivo: Buscar productos y diferenciar si tenemos que mostrar modal pintar la linea directamente
			$busqueda = $_POST['valorCampo'];
			$campoAbuscar = $_POST['campo'];
			$id_input = $_POST['cajaInput'];
			$idcaja=$_POST['idcaja'];
			$dedonde=$_POST['dedonde'];
			$res = BuscarProductos($id_input,$campoAbuscar, $idcaja, $busqueda,$BDTpv);
			if ($res['Nitems']===1){
				$respuesta=$res;
				$respuesta['Nitems']=$res['Nitems'];	
			}else{
				// Cambio estado para devolver que es listado.
				$respuesta['listado']= htmlProductos($res['datos'],$id_input,$campoAbuscar,$busqueda, $dedonde);
				$respuesta['Estado'] = 'Listado';
				$respuesta['datos']=$res['datos'];
			}


?>
