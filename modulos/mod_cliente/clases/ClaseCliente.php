<?php 


include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';


class ClaseCliente extends modelo{
	
	public function getCliente($id){
		//@Objetivo:
		//Cargar todos los datos de un cliente
		//@Parametros: 
		//id => recibe el id del cliente que queremos buscar
		$sql= 'SELECT * FROM clientes WHERE idClientes='.$id;
		return $this->consulta($sql);
	}
	public function getTicket($id){
		//@Objetivo: Cargar todos los tickes de un cliente determinado en orden descendente
		//@Parametros:
		//id -> id del cliente
		$sql='SELECT Numticket as num , Fecha as fecha , total ,id FROM ticketst WHERE idCliente= '.$id.' order by Numticket desc';
		return $this->consulta($sql);
	}
	public function getFacturas($id){
		//@Objetivo:
		//Cargar todas las facturas de un cliente detrerminado en orden descendente
		//@Parametros:
		//id -> id del cliente
		$sql='SELECT Numfaccli as num, Fecha as fecha, total, id FROM facclit WHERE idCliente='.$id.' order by id desc';
		return $this->consulta($sql);
	}
	public function getAlbaranes($id){
		//@objetivo:
		//Cargar todos los albaranes de clientes de un cliente determinado en ordes descendente
		//@Parametros:
		//id->id del cliente
		$sql='SELECT Numalbcli as  num, Fecha as fecha, total, id FROM albclit WHERE idCliente='.$id.' order by id desc';
		return $this->consulta($sql);
	}
	public function getPedidos($id){
		//@Objetivo:
		//Cargar todos los pedidos de clientes de un cliente determinado en orden descendente
		//@Parametros:
		//id-> id del cliente
		$sql='SELECT Numpedcli as num, FechaPedido as fecha, total, id FROM pedclit WHERE idCliente='.$id.' order by id desc';
		return $this->consulta($sql);
	}
	public function adjuntosCliente($id){
		//@Objetivo: 
		//Cargar todos los adjunto de un cliente , tickets, facturas, albaranes y pedidos
		//@Parametros:
		//id-> id del cliente 
		$respuesta=array();
		$respuesta['tickets']=$this->getTicket($id);
		$respuesta['facturas']=$this->getFacturas($id);
		$respuesta['albaranes']=$this->getAlbaranes($id);
		$respuesta['pedidos']=$this->getPedidos($id);
		return $respuesta;
	}
	public function modificarDatosCliente($datos, $id){
		//@Objetivo:
		//Modificar los datos de un cliente determinado
		//@Parametros:
		//Datos-> array con todos los datos del cliente
		//id-> id del cliente que se va a modificar
		$sql='UPDATE `clientes` SET Nombre="'.$datos['nombre'].'" , razonsocial="'.$datos['razonsocial'].'" , 
		nif="'.$datos['nif'].'" , direccion="'.$datos['direccion'].'" , codpostal="'.$datos['codpostal'].'" , telefono="'.$datos['telefono']
		.'" , movil="'.$datos['movil'].'" , fax="'.$datos['fax'].'" , email="'.$datos['email'].'" , estado="'.$datos['estado'].'" ,
		fomasVenci='."'".$datos['formasVenci']."'".' WHERE idClientes='.$id;
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	public function addcliente($datos){
		//@Objetivo:
		//Añadir un cliente nuevo
		//@Parametros:
		//datos-> todos los datos que se recogen de la ficha de clientes 
		$sql='INSERT INTO `clientes`( `Nombre`, `razonsocial`, 
		`nif`, `direccion`, `codpostal`, `telefono`, `movil`, `fax`, `email`, 
		`estado`, `fomasVenci`, `fecha_creado`) VALUES ("'.$datos['nombre'].'", "'.$datos['razonsocial'].'", 
		"'.$datos['nif'].'", "'.$datos['direccion'].'", "'.$datos['codpostal'].'", "'.$datos['telefono'].'",
		 "'.$datos['movil'].'", "'.$datos['fax'].'", "'.$datos['email'].'", "'.$datos['estado'].'", '."'".$datos['formasVenci']."'".', NOW())';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	public function comprobarExistenDatos($datos){
		$respuesta=array();
		$sql='select nif , idClientes  FROM clientes where nif="'.$datos['nif'].'"';
		$consulta=$this->consulta($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}else{
			if($consulta['datos']>0){
				if($consulta['datos'][0]['idClientes'] != $datos['idCliente']){
				$respuesta['error']="Existe";
				$respuesta['consulta']="Ese nif ya existe";
				return $respuesta;
			}
			}
		}
	}
	public function ticketClienteFechas($idCliente, $fechaIni, $fechaFin){
		$sql='SELECT `Numticket`, id FROM `ticketst` WHERE `idCliente`='.$idCliente.' and `Fecha` BETWEEN 
		"'.$fechaIni.'" and  "'.$fechaFin.'"';
		return $this->consulta($sql);
	}
}


?>
