<?php 

include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';

class ClaseProveedor extends modelo{
	
	public function getProveedor($id){
		//@Objetivo: cargar todos los datos de un proveedor 
		//@Parametros: 
		//id: id del proveedor
		$sql='SELECT * from proveedores where idProveedor='.$id;
		return $this->consulta($sql);
	}
	
	public function getFacturas($id){
		//@Objetivo: cargar todas las facturas de compras de un proveedor determinado
		//@Parametros:
		//id: id del proveedor
		$sql='SELECT Numfacpro  as num, Fecha as fecha, total, id , idProveedor FROM facprot WHERE idProveedor='.$id;
		return $this->consulta($sql);
	}
	
	public function getAlbaranes($id){
		//@OBjetivo: cargar todos los albaranes de compras de un proveedor 
		//@Prametros: 
		//id : id del proveedor
		$sql='SELECT Numalbpro as num, Fecha as fecha, total , id , idProveedor  FROM albprot WHERE idProveedor='.$id;
		return $this->consulta($sql);
	}
	
	public function getPedidos($id){
		//@Objetivo: cargar todos los pedidos de compras de un proveedor
		//@Parametros:
		//id: id del proveedor
		$sql='SELECT Numpedpro as num, FechaPedido as fecha , total, id, idProveedor  FROM pedprot WHERE idProveedor='.$id;
		return $this->consulta($sql);
	}
	public function adjuntosProveedor($id){
		//@OBjetivo: cargar todos los pedidos, albaranes y facturas de una sola vez
		$respuesta=array();
		$respuesta['facturas']=$this->getFacturas($id);
		$respuesta['albaranes']=$this->getAlbaranes($id);
		$respuesta['pedidos']=$this->getPedidos($id);
		return $respuesta;
	}
	public function modificarDatosProveedor($datos){
		//@Objetivo: modificar los datos de un proveedor determinado
		//@Paramatros: 
		//datos: datos a modificar del provvedor 
		$sql='UPDATE `proveedores` SET `nombrecomercial`="'.$datos['nombrecomercial'].'",
		`razonsocial`="'.$datos['razonsocial'].'",`nif`="'.$datos['nif'].'",`direccion`="'.$datos['direccion'].'",
		`telefono`="'.$datos['telefono'].'",`fax`="'.$datos['fax'].'",`movil`="'.$datos['movil'].'",
		`email`="'.$datos['email'].'",`estado`="'.$datos['estado'].'" WHERE idProveedor='.$datos['idProveedor'];
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	public function addProveedorNuevo($datos){
		//@Objetivo:
		//Añadir un proveedor nuevo
		//Parametros: 
		//datos del proveedor 
		$sql='INSERT INTO `proveedores`( `nombrecomercial`, `razonsocial`, 
		`nif`, `direccion`, `telefono`, `fax`, `movil`, `email`, `fecha_creado`, 
		`estado`) VALUES ("'.$datos['nombrecomercial'].'","'.$datos['razonsocial'].'",
		"'.$datos['nif'].'","'.$datos['direccion'].'","'.$datos['telefono'].'","'.$datos['fax'].'",
		"'.$datos['movil'].'","'.$datos['email'].'",NOW() , "'.$datos['estado'].'" )';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	
	public function comprobarExistenDatos($datos){
		//@Objetivo:
		//comprobar que el nif que vamos a guardar en el proveedor no lo tiene otro proveedor
		$respuesta=array();
		$sql='select nif , idProveedor FROM proveedores where nif="'.$datos['nif'].'"';
		$consulta=$this->consulta($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}else{
			if($consulta['datos']>0){
				if($consulta['datos'][0]['idProveedor'] != $datos['idProveedor']){
					$respuesta['error']="Existe";
					$respuesta['consulta']="Ese nif ya existe";
					return $respuesta;
				}
				
			}
		}
	}
	function albaranesProveedoresFechas($idProveedor, $fechaIni, $fechaFin){
		$respuesta=array();
		$productos=array();
		$resumenBases=array();
		if($fechaIni=="" & $fechaFin==""){
			$sql='SELECT Numalbpro , id FROM albprot WHERE  idProveedor ='.$idProveedor;
		}else{
			$sql='SELECT Numalbpro , id FROM albprot WHERE idProveedor ='.$idProveedor.' and `Fecha` BETWEEN 
		 "'.$fechaIni.'" and  "'.$fechaFin.'"';
         
        //~ $sql='SELECT Numalbpro, id FROM albprot WHERE idProveedor='.$idProveedor.' and Fecha >= "'.$fechaIni.'" AND Fecha <= "'.$fechaFin.'"';
		}
		$albaranes=$this->consulta($sql);
		if(isset($albaranes['error'])){
			$respuesta=$albaranes;
		}else{
			$ids=implode(', ', array_column($albaranes['datos'], 'id'));
			if($ids==0){
                $respuesta['error']=1;
                $respuesta['consulta']='No hay resumen entre las fechas seleccionadas';
            }else{
                $sql='SELECT	*,	SUM(nunidades) as totalUnidades	FROM	`albprolinea`	WHERE idalbpro  IN('.$ids.') and 
                `estadoLinea` <> "Eliminado" GROUP BY idArticulo + costeSiva';
                
                $productos=$this->consulta($sql);
                if(isset($albaranes['error'])){
                    $respuesta=$productos;
                }else{
                    $respuesta['productos']=$productos['datos'];
                }
                $sql='SELECT i.* , t.idTienda, t.idUsuario, sum(i.totalbase) as sumabase , sum(i.importeIva) 
                as sumarIva, t.Fecha as fecha   from albproIva as i  
                left JOIN albprot as t on t.id=i.idalbpro   where idalbpro  
                in ('.$ids.')  GROUP BY idalbpro ;';
                $resumenBases=$this->consulta($sql);
                if(isset($resumenBases['error'])){
                    $respuesta=$resumenBases;
                }else{
                    $respuesta['resumenBases']=$resumenBases['datos'];
                }
                $sql='SELECT *, sum(importeIva) as sumiva , sum(totalbase) as sumBase from albproIva where idalbpro 
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
