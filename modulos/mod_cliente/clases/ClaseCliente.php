<?php 


include_once $URLCom.'/modulos/claseModelo.php';


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
		$sql='SELECT Numticket as num , Fecha as fecha , total ,id, idCliente , estado FROM ticketst WHERE idCliente= '.$id.' order by Numticket desc';
		return $this->consulta($sql);
	}
	public function getFacturas($id){
		//@Objetivo:
		//Cargar todas las facturas de un cliente detrerminado en orden descendente
		//@Parametros:
		//id -> id del cliente
		$sql='SELECT Numfaccli as num, Fecha as fecha, total, id , idCliente , estado FROM facclit WHERE idCliente='.$id.' order by id desc';
		return $this->consulta($sql);
	}
	public function getAlbaranes($id){
		//@objetivo:
		//Cargar todos los albaranes de clientes de un cliente determinado en ordes descendente
		//@Parametros:
		//id->id del cliente
		$sql='SELECT Numalbcli as  num, Fecha as fecha, total, id , idCliente, estado FROM albclit WHERE idCliente='.$id.' order by id desc';
		return $this->consulta($sql);
	}
	public function getPedidos($id){
		//@Objetivo:
		//Cargar todos los pedidos de clientes de un cliente determinado en orden descendente
		//@Parametros:
		//id-> id del cliente
		$sql='SELECT Numpedcli as num, FechaPedido as fecha, total, id , idCliente , estado FROM pedclit WHERE idCliente='.$id.' order by id desc';
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
		//Objetivo:
		//Comprobar cuando guardamos que le nif del cliente no es el mismo que otro cliente
		//Parametros:
		//Los datos del cliente
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
		//@Objetivo:
		//MOstrar los datos para el resumen tanto si tienen fechas como si selecciona todos
		//@Parametros:
		//idCliente: id del cliente
		//fechaInicio: fecha de inicio del resumen
		//fechaFin: fecha de fin de resumen
		//COnsultas:
		//1º Busca el numero de tickets e id de tickets de un cliente
		//2º Busca los productos sumando la cantidad y el importe (los productos que tienen precio de venta distinto los
		// cuenta como productos individuales).
		//3º Suma todas las bases y todos los ivas , los agrupa por iva
		//4º Muestra el número del ticket con el total de bases y el total de ivas de cada ticket
		$respuesta=array();
		$productos=array();
		$resumenBases=array();
		if($fechaIni=="" & $fechaFin==""){
			$sql='SELECT `Numticket`, id FROM `ticketst` WHERE `idCliente`='.$idCliente;
		}else{
			$sql='SELECT `Numticket`, id FROM `ticketst` WHERE `idCliente`='.$idCliente.' and `Fecha` BETWEEN 
		"'.$fechaIni.'" and  "'.$fechaFin.' 23:00:00"';
		}
		//~ error_log($sql);
		$tickets=$this->consulta($sql);
		if(isset($tickets['error'])){
			$respuesta=$tickets;
		}else{
			$ids=implode(', ', array_column($tickets['datos'], 'id'));
            if($ids==0){
                $respuesta['error']=1;
                $respuesta['consulta']='No existen ids entre estas fechas';
            }else{
			$sql='SELECT	*,	SUM(nunidades) as totalUnidades	FROM	`ticketslinea`	WHERE`idticketst` IN('.$ids.') and 
			`estadoLinea` <> "Eliminado" GROUP BY idArticulo + `precioCiva`';
			$productos=$this->consulta($sql);
			if(isset($tickets['error'])){
				$respuesta=$productos;
			}else{
				$respuesta['productos']=$productos['datos'];
			}
			$sql='SELECT i.* , t.idTienda, t.idUsuario, sum(i.totalbase) as sumabase , sum(i.importeIva) 
			as sumarIva, t.Fecha as fecha   from ticketstIva as i  
			left JOIN ticketst as t on t.id=i.idticketst  where idticketst 
			in ('.$ids.')  GROUP BY idticketst;';
			$resumenBases=$this->consulta($sql);
			if(isset($resumenBases['error'])){
				$respuesta=$resumenBases;
			}else{
				$respuesta['resumenBases']=$resumenBases['datos'];
			}
			$sql='SELECT *, sum(importeIva) as sumiva , sum(totalbase) as sumBase from ticketstIva where idticketst 
			in ('.$ids.')  GROUP BY iva;';
			$desglose=$this->consulta($sql);
			if(isset($desglose['error'])){
				$respuesta=$desglose;
			}else{
				$respuesta['desglose']=$desglose['datos'];
			}
        }
		}
		return $respuesta;
	}
	
	
}


?>
