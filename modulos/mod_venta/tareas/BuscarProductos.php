<?php 
//@Objetivo: Buscar productos y diferenciar si tenemos que mostrar modal pintar la linea directamente
			$busqueda = $_POST['valorCampo'];
			$campoAbuscar = $_POST['campo'];
			$id_input = $_POST['cajaInput'];
			$idcaja=$_POST['idcaja'];
			$dedonde=$_POST['dedonde'];
			$idCliente=$_POST['idCliente'];
   			$res = BuscarProductos($idcaja,$campoAbuscar, $busqueda,$BDTpv, $idCliente);

	if ($res['Nitems']===1 && $idcaja<>"cajaBusqueda"){
				$respuesta=$res;
				$respuesta['Nitems']=$res['Nitems'];
			}else{
				// Cambio estado para devolver que es listado.
				$respuesta['listado']= htmlListadoProductos($res['datos'],$id_input,$campoAbuscar,$busqueda, $dedonde, $BDTpv, $idCliente);
				$respuesta['Estado'] = 'Listado';
				$respuesta['datos']=$res['datos'];
			}


?>
