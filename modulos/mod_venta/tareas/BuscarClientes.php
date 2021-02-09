<?php 

	//@Objetivo
			//BUsqueda de clientes , si recibe de una caja id lo busca directamente si no crea el modal de clientes 
			$busqueda = $_POST['busqueda'];
			$dedonde = $_POST['dedonde'];
			$idcaja=$_POST['idcaja'];
			$respuesta=array();
			if ($idcaja=="id_cliente"){
				$res=$Ccliente->DatosClientePorId($busqueda);
				if (isset($res['error'])){
					$respuesta['error']=$res['error'];
					$respuesta['consulta']=$res['consulta'];
				}else if (isset($res['idClientes'])){
					$respuesta['res']=$res;
					$respuesta['id']=$res['idClientes'];
					$respuesta['nombre']=$res['Nombre'];
					$respuesta['Nitems']=1;
					$respuesta['formasVenci']=$res['formasVenci'];
				}else{
					$respuesta['Nitems']=2;
				}
				
			}else{
				$buscarTodo=$Ccliente->BuscarClientePorNombre($busqueda);
				if (isset($buscarTodo['error'])){
					$respuesta['error']=$buscarTodo['error'];
					$respuesta['consulta']=$buscarTodo['consulta'];
				}else{
					$respuesta['html'] = htmlClientes($busqueda,$dedonde, $idcaja, $buscarTodo['datos']);
					$respuesta['datos']=$buscarTodo['datos'];
				}
			}


?>
