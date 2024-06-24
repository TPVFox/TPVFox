<?php 
include_once $URLCom.'/modulos/mod_venta/clases/ClaseVentas.php';
class PedidosVentas extends ClaseVentas{

    public function __construct($conexion){
		parent::__construct($conexion);
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM pedclit';
		$respuesta = parent::consulta($sql);
		if (gettype($respuesta)==='object'){
			$this->num_rows = $respuesta->fetch_object()->num_reg;
		} else {
			// Es un array porque hubo un fallo
			echo '<pre>';
			print_r($respuesta);
			echo '</pre>';
		}
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}


    public function AddPedidoGuardado($datos, $idPedido){
		//Objetivo:
		//Añade un registro de un pedido ya guardado en pedidos . Si el numero del pedido es mayor  de 0 o sea que hay un registro en pedidos 
		//lo añade a la tabla temporal si no añade un registro normal a la tabla pedido
		$respuesta=array();
        $errores = array();
		$db = $this->db;
		if ($idPedido>0){
			$sql='INSERT INTO pedclit (id, Numpedcli , Numtemp_pedcli, 
			Fecha, idTienda, idUsuario, idCliente, estado, total)
			 VALUES ('.$idPedido.' , '.$idPedido.' , '.$datos['Numtemp_pedcli'].' , "'
			 .$datos['Fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '
			 .$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].')';
        $smt = parent::consulta($sql);
			if (gettype($smt)==='array'){
				$errores['0']['error']=$smt['error'];
				$errores['0']['consulta']=$smt['consulta'];
			}else{
				$id=$idPedido;
			}
		}else{
			$sql='INSERT INTO pedclit (Numtemp_pedcli, Fecha,
			 idTienda, idUsuario, idCliente, estado, total) VALUES ('
			 .$datos['Numtemp_pedcli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']
			 . ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado']
			 .'", '.$datos['total'].')';
        $smt = parent::consulta($sql);
			if (gettype($smt)==='array'){
					$errores['1']['error']=$smt['error'];
					$errores['1']['consulta']=$smt['consulta'];
			}else{
				$id=$db->insert_id;
				$sql='UPDATE pedclit SET Numpedcli  = '.$id.' WHERE id ='.$id;
				$smt=$this->consulta($sql);
				if (gettype($smt)==='array'){
					$errores['2']['error']=$smt['error'];
					$errores['2']['consulta']=$smt['consulta'];
				}
			}
		}
		$productos = json_decode($datos['productos'], true); 
		$i=1;
		foreach ( $productos as $prod){
			if($prod['estadoLinea']=='Activo'){
				$codBarras="";
				if ($prod['ccodbar']){
					$codBarras=$prod['ccodbar'];
				}
				$sql='INSERT INTO pedclilinea (idpedcli , Numpedcli, idArticulo,
				 cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea ,pvpSiva) 
				 VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', "'
				 .$codBarras.'", "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades']
				 .', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$i.', "'. $prod['estadoLinea'].'", '.$prod['pvpSiva'].' )' ;
        $smt = parent::consulta($sql);
				if (gettype($smt)==='array'){
						$errores['3']['error']=$smt['error'];
						$errores['3']['consulta']=$smt['consulta'];
						break;
				}
			$i++;
			}
		}
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			$sql='INSERT INTO pedcliIva (idpedcli ,  Numpedcli , iva , 
			importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '.$basesYivas['iva']
			.' , '.$basesYivas['base'].')';
        $smt = parent::consulta($sql);
				if (gettype($smt)==='array'){
					$errores['4']['error']=$smt['error'];
					$errores['4']['consulta']=$smt['consulta'];
					break;
			}
			
		}
        if (count($errores) >0 ){
            // Devolvemos los errores.
            $respuesta['errores'] = $errores;
        }
		 return $respuesta;
	}

    public function ComprobarPedidos($idCliente, $estado){
		//@Objetivo:
		//Comprobar los pedidos de un cliente determinado con el estado guardado
        $respuesta = array( 'NItems'=>0);
        $db=$this->db;
		$sql='SELECT  id from pedclit where idCliente='
		.$idCliente .' and estado="'.$estado.'"';
        $smt = parent::consulta($sql);
		if (gettype($smt)==='array'){
				$resultado['error']=$smt['error'];
				$resultado['consulta']=$smt['consulta'];
				return $resultado;
		}else {
            $respuesta['NItems'] = $smt->num_rows;
        }
        return $respuesta;
	}

    public function EliminarRegistroTemporal($idTemporal, $idPedido){
		//@Objetivo:
		// Cuando un pedido pasa de temporal a pedidos se borran los registros temporales
		$db=$this->db;
		if ($idPedido>0){
			$sql='DELETE FROM pedcliltemporales WHERE Numpedcli='.$idPedido;
		}else{
			$sql='DELETE FROM pedcliltemporales WHERE id='.$idTemporal;
		}
        $smt = parent::consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
    
    public function IvasPedidos($idPedido){
		//@Objetivo:
		//Buscar de la tabla pedcliIva todos los registros de un pedido
		$tabla='pedcliIva';
		$where='idpedcli= '.$idPedido;
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}

    public function ModificarEstadoPedido($idPedido, $estado){
		//@Objetivo:
		//MOdificar el estado de un pedido real indicado
		$db=$this->db;
		$sql='UPDATE pedclit SET estado="'.$estado.'" 
		WHERE id='.$idPedido;
        $smt = parent::consulta($sql);
		if (gettype($smt)==='array'){
				$resultado['error']=$smt['error'];
				$resultado['consulta']=$smt['consulta'];
				return $resultado;
		}
	}

    public function NumAlbaranDePedido($idPedido){
		$tabla='pedcliAlb';
		$where='idPedido='.$idPedido;
		$relacion_alb_pedido = parent::SelectUnResult($tabla, $where);
		return $relacion_alb_pedido;
    }


    public function PedidosClienteGuardado($busqueda, $idCliente){
		//@Objetivo:
		//Buscar algunos datos de un pedido guardado
        $respuesta = array();
        $db=$this->db;
		$respuesta['busqueda']=$busqueda;
		if ($busqueda>0){
		$sql='select  Numpedcli, id , Fecha , total from 
		pedclit where Numpedcli='.$busqueda.' and  idCliente='. $idCliente.' and estado="Guardado"';
		$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				
			}else{
                $respuesta['Nitems']=0; // Puede que no haya resultados
				if ($result = $smt->fetch_assoc () ){
					$respuesta['datos']['0']=$result;
    				$respuesta['Nitems']=1;
				}
			}
		}else{
			$sql='SELECT  Numpedcli, Fecha , total , 
			id from pedclit where idCliente='.$idCliente .' and estado="Guardado"';
        $smt = parent::consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				
			}else{
				$pedidos=array();
				while ( $result = $smt->fetch_assoc () ) {
					array_push($pedidos,$result);	
				}
				$respuesta['datos']=$pedidos;
                $respuesta['Nitems'] = count($pedidos);

			}
		}
		return $respuesta;
	}

    public function ProductosPedido($idPedido){
		//@Objetivo:
		//Buscar los articulos de un pedido
		$tabla='pedclilinea';
		$where='idpedcli= '.$idPedido;
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}
    
    public function TodosPedidosFiltro($filtro){
		//@Objetivo: Todos los pedidos guardados pero ultilizando el filtro
		$db=$this->db;
		$sql= 'SELECT a.id , a.Numpedcli, a.Fecha, b.Nombre, a.total, a.estado 
		FROM `pedclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes '.$filtro;
        $smt = parent::consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$pedidosPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);
			}
			$respuesta = array();
			$respuesta['Items'] = $pedidosPrincipal;
			$respuesta['consulta'] = $sql;
			return $respuesta;
		}
	}	
	
	public function TodosTemporal($idPedido = 0){
		//@Objetivo:
        //Mostrar todos los datos temporales
        $respuesta = array();
		$sql='SELECT tem.Numpedcli, tem.id , tem.idCliente,
        tem.total, b.Nombre from pedcliltemporales as tem left JOIN clientes
         as b on tem.idCliente=b.idClientes';
        if ($idPedido > 0){
            // buscamos solos temporales para ese albaran.
            // [OJO] El campo que tenemos en temporal es Numfaccli pero debe se idfaccli
            // ya el día de mañana que pongamos en funcionamiento el poder distinto numero que id
            // dejaría funciona.
            $sql .= ' where tem.Numpedcli='.$idPedido;
        }
        $smt = parent::consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        } else {
            $pedidotemporales = array();
            while ($result = $smt->fetch_assoc()) {
                array_push($pedidotemporales, $result);
            }
            return $pedidotemporales;
        }
    }
    
    public function addNumRealTemporal($idTemporal, $idPedido) {
        //@Objetivo:
        //SI tenemos un número de albarán real lo metemos en el albarán temporal
        $db = $this->db;
        $sql='UPDATE pedcliltemporales set Numpedcli ='.$idPedido
		.' WHERE id='.$idTemporal;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }
        

    public function buscarDatosTemporal($idTemporal) {
		//@Objetivo:
		//Buscar los datos de una pedido temporal
		$tabla='pedcliltemporales';
		$where='id='.$idTemporal;
		$pedido = parent::SelectUnResult($tabla, $where);
		return $pedido;
	} 

	public function datosPedido($idPedido){
		//@Objetivo:
		//Mostrar todos los datos de un pedido
		$tabla='pedclit';
		$where='id= '.$idPedido;
		$pedido = parent::SelectUnResult($tabla, $where);
		return $pedido;
	}
	
	public function eliminarPedidoTablas($idPedido){
		//@Objetivo:
		//Eliminar los registros de in id de pedido real
		$db=$this->db;
		$respuesta=array();
        $sql[0]='DELETE FROM pedclit where id='.$idPedido ;
		$sql[1]='DELETE FROM pedclilinea where idpedcli='.$idPedido ;
		$sql[2]='DELETE FROM pedcliIva where idpedcli='.$idPedido ;
        

		foreach ($sql as $consulta){
        $smt = parent::consulta($consulta);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				break;
			}
		}
		return $respuesta;
		
	}

     public function getEstado($idPedido){
        // @ Objetivo
        // Obtener el estado de una factura
        $tabla='pedclit';
        $where='id='.$idPedido ;
        $pedido = parent::SelectUnResult($tabla, $where);
        $estado = $pedido['estado'];
        return $estado;
    
    }

    public function insertarDatosTemporal($idUsuario, $idTienda, $fecha, $pedidos, $productos, $idCliente) {
        //@Objetivo:
        //Insertar un nuevo registro de albaranes temporales
        $respuesta = array();
        $db = $this->db;
        //$UnicoCampoPedidos = json_encode($pedidos);
        $UnicoCampoProductos = json_encode($productos);
        $PrepProductos = $db->real_escape_string($UnicoCampoProductos);
        //$PrepPedidos = $db->real_escape_string($UnicoCampoPedidos);
        $sql = 'INSERT INTO pedcliltemporales ( idUsuario , idTienda , Fecha, fechaInicio,
                idCliente,  Productos ) VALUES (' . $idUsuario . ' , '
                . $idTienda . '  , "' . $fecha . '",NOW(), ' . $idCliente . ' , "' . $PrepProductos . '")';
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            error_log('pedidosVentas en insertarDatosTemporal:'.implode(' ',$smt));
            $respuesta['consulta'] = $smt['error'].'consulta'.$smt['consulta'];
        } else {
            $id = $db->insert_id;
            $respuesta['id'] = $id;
            $respuesta['productos'] = $productos;
        }
        return $respuesta;
}
	
	public function modTotales($res, $total, $totalivas){
		//@Objetivo:
		//Modificar el total de un albarán temporal
		$db=$this->db;
		$sql='UPDATE pedcliltemporales set total='.$total 
		.' , total_ivas='.$totalivas .' where id='.$res;
        $smt = parent::consulta($sql);
		if (gettype($smt)==='array'){
				$resultado['error']=$smt['error'];
				$resultado['consulta']=$smt['consulta'];
				return $resultado;
		}
	}

    public function modificarDatosTemporal($idUsuario, $idTienda, $fecha , $adjuntos, $idTemporal, $productos){
		$db=$this->db;
		$UnicoCampoProductos = json_encode($productos);
        $PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$sql='UPDATE pedcliltemporales set idUsuario='.$idUsuario.', Fecha="'.$fecha.'", idTienda='.$idTienda.', Productos="'.$PrepProductos 
		.'" WHERE id='.$idTemporal;		
		$smt=parent::consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
			}
	}
    
	public function modificarFecha($idPedido, $fecha){
		$db=$this->db;
		$sql='UPDATE pedclit SET Fecha="'.$fecha.'" where id='.$idPedido;
        $smt = parent::consulta($sql);
		if (gettype($smt)==='array'){
				$resultado=array();
				$resultado['error']=$smt['error'];
				$resultado['consulta']=$smt['consulta'];
				return $resultado;
		}
	}

    public function posiblesEstados(){
        // @ Objetivo:
        // Devolver los posibles estados para la tabla de pedido. pedclit
        $posibles_estados = array(  '1'=> array(
											'estado'      =>'Guardado',
											'Descripcion' =>'Estado pedido guardado cuando no se esta editando.'
												),
									'2' =>  array(
											'estado'      =>'Sin Guardar',
											'Descripcion' =>'Estado pedido que se hay temporal , se esta editando.'
											),
									
									'3' =>  array(
											'estado'      =>'Procesado',
											'Descripcion' =>'Un pedido que ya fue procesado, se creo el albaran, no se permite modificar, ni eliminar.'
											)
                                );
        return $posibles_estados;
    }

    public function sumarIva($numPedido){
		//@Objetivo:
		//Suma importe iva y totoal base de todos los registro de un pedido determinado
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) 
		as  totalbase from pedcliIva where Numpedcli ='.$numPedido);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}

	

    
}

?>
