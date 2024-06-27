<?php 
include_once $URLCom.'/modulos/mod_venta/clases/ClaseVentas.php';

class FacturasVentas extends ClaseVentas{
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM facclit';
		$respuesta = $this->consulta($sql);
		// Controlamos el resultado.
		if (gettype($respuesta)==='object'){
			$this->num_rows = $respuesta->fetch_object()->num_reg;
		} else {
			// Es un array porque hubo un fallo
			echo '<pre>';
			print_r($respuesta);
			echo '</pre>';
		}
	}
    public function AddFacturaGuardado($datos, $idFactura){
		//@Objetivo:
		//Añadir todos los registros de las diferentes tablas de una factura real
		$respuesta=array();
        $errores = array();
		$db = $this->db;
		if ($idFactura>0){
			$sql='INSERT INTO facclit (id, Numfaccli, Fecha, idTienda 
			, idUsuario , idCliente , estado , total, fechaCreacion, 
			fechaVencimiento,  fechaModificacion) VALUES ('.$idFactura.' , '.$idFactura
			.' , "'.$datos['Fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '
			.$datos['idCliente'].', "'.$datos['estado'].'", "'.$datos['total'].'", "'
			.$datos['fechaCreacion'].'", "'.$datos['fechaVencimiento']
			.'", "'.$datos['fechaModificacion'].'")';
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
                error_log('en facturasVentas AddGuardado(0):'.$smt['error']);
                $errores['0']['error'] = 'facturaVentas AddGuardado(0):'.$smt['error'];
                $errores['0']['consulta'] = $smt['consulta'];
				return $respuesta;
			}else{
				$id=$idFactura;
			}
		}else{
			$sql='INSERT INTO facclit (Numtemp_faccli , Fecha, 
			idTienda , idUsuario , idCliente , estado , total, fechaCreacion,
			fechaVencimiento, fechaModificacion) VALUES ('
			 .$datos['Numtemp_faccli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']
			 . ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado']
			 .'", "'.$datos['total'].'", "'.$datos['fechaCreacion'].'", "'.$datos['fechaVencimiento']
             .'" ,  "'.$datos['fechaModificacion'].'")';
			 $smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				error_log('en facturasVentas AddGuardado(1):'.$smt['error'].' consulta:'.$smt['consulta']);
                $errores['1']['error'] = 'facturaVentas AddGuardado(1):'.$smt['error'];
                $errores['1']['consulta'] = $smt['consulta'];
				return $respuesta;
			}else{
				$id=$db->insert_id;
				$sql='UPDATE facclit SET Numfaccli  = '.$id.' WHERE id ='.$id;
				$smt=$this->consulta($sql);
					if (gettype($smt)==='array'){
                        error_log('en facturasVentas AddGuardado(2):'.$smt['error']);
                        $errores['2']['error'] = 'facturaVentas AddGuardado(2):'.$smt['error'];
                        $errores['2']['consulta'] = $smt['consulta'];
						
					}
			}
		}
		$productos = json_decode($datos['productos'], true); 
		$i=1;
		foreach ( $productos as $prod){
			if ($prod['estadoLinea']=="Activo"){
				$codBarras="";
				$numAl=0;
				if (isset($prod['ccodbar'])){
					$codBarras=$prod['ccodbar'];
				}
				if (isset($prod['NumalbCli'])){
					$numAl=$prod['NumalbCli'];
				}
				$sql='INSERT INTO facclilinea (idfaccli  , Numfaccli ,
				 idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, 
				 iva, nfila, estadoLinea, NumalbCli, pvpSiva ) VALUES ('.$id.', '.$id.' , '
				 .$prod['idArticulo'].', '."'".$prod['cref']."'".', "'.$codBarras.'", "'
				 .$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades'].', '
				 .$prod['precioCiva'].' , '.$prod['iva'].', '.$i.', "'. $prod['estadoLinea']
				 .'" , '.$numAl.', '.$prod['pvpSiva'].')' ;
                $smt=$this->consulta($sql);
                if (gettype($smt)==='array'){
                    error_log('en facturasVentas AddGuardado(3):'.$smt['error']);
                    $errores['3']['error'] = 'facturaVentas AddGuardado(3):'.$smt['error'];
                    $errores['3']['consulta'] = $smt['consulta'];
                    break;
                }
                $i++;
            }
        }
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			$sql='INSERT INTO faccliIva (idfaccli  ,  Numfaccli  , iva , 
			importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '
			.$basesYivas['iva'].' , '.$basesYivas['base'].')';
			$smt=$this->consulta($sql);
            if (gettype($smt)==='array'){
                error_log('en facturasVentas AddGuardado(4):'.$smt['error']);
                $errores['4']['error'] = 'facturaVentas AddGuardado(4):'.$smt['error'];
                $errores['4']['consulta'] = $smt['consulta'];
                break;
            }
		}
		$albaranes = json_decode($datos['albaranes'], true); 
		if (isset($albaranes)){
            foreach ($albaranes as $albaran){
                if ($albaran['estado']=="activo" || $albaran['estado']=="Activo"){
                    $sql='INSERT INTO albclifac (idFactura  ,  numFactura  
                     , idAlbaran , numAlbaran) VALUES ('.$id.', '.$id.' ,  '
                     .$albaran['NumAdjunto'].' , '.$albaran['NumAdjunto'].')';
                    $smt=$this->consulta($sql);
                    if (gettype($smt)==='array'){
                        error_log('en facturasVentas AddGuardado(5):'.$smt['error']);
                        $errores['5']['error'] = 'facturaVentas AddGuardado(5):'.$smt['error'];
                        $errores['5']['consulta'] = $smt['consulta'];
                        break;
                    }
                }
            }
		}
        if (count($errores) >0 ){
            // Devolvemos los errores.
            $respuesta['errores'] = $errores;
        }
		return $respuesta;
	}

    public function AlbaranesFactura($idFactura){
		//@Objetivo:
		//Mostrar los albaranes que estan ligados a una determinada factura.
		$tabla='albclifac';
		$where='idFactura= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}

    public function EliminarRegistroTemporal($idTemporal, $idFactura){
		//@Objetivo:
		//Eliminar el resgistro de un temporal indicado
		$db=$this->db;
		if ($idFactura>0){
			$sql='DELETE FROM faccliltemporales WHERE Numfaccli ='.$idFactura;
		}else{
			$sql='DELETE FROM faccliltemporales WHERE id='.$idTemporal;
		}
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
      
	public function IvasFactura($idFactura){
		//@Objetivo:
		//Buscar los ivas de una factura real
		$tabla='faccliIva';
		$where='idfaccli= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}

    public function ProductosFactura($idFactura){
		//@Objetivo:
		//Buscar los productos de un número de factura
		$tabla='facclilinea';
		$where='idfaccli= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}
    
	public function TodosFacturaFiltro($filtro){
		//@Objetivo:
		//Mostrar los datos principales de todas las facturas con el filtro de paginacion 
		$sql = 'SELECT a.id , a.Numfaccli , a.Fecha , b.Nombre, a.total, a.estado 
		FROM `facclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes  '.$filtro;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$facturaPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($facturaPrincipal,$result);
			}
			$respuesta = array();
			$respuesta['Items'] = $facturaPrincipal;
			$respuesta['consulta'] = $sql;
			return $respuesta;
		}
	}

	public function TodosTemporal($idFactura = 0){
		//@Objetivo:
		//Mostrar los datos principales de una factura temnporal
        $respuesta = array();
        $sql='SELECT tem.Numfaccli, tem.id , tem.idCliente,
         tem.total, b.Nombre from faccliltemporales as tem left JOIN 
         clientes as b on tem.idCliente=b.idClientes';
        if ($idFactura > 0){
            // buscamos solos temporales para ese albaran.
            // [OJO] El campo que tenemos en temporal es Numfaccli pero debe se idfaccli
            // ya el día de mañana que pongamos en funcionamiento el poder distinto numero que id
            // dejaría funciona.
            $sql .= ' where tem.Numfaccli='.$idFactura;
        }
        $smt=$this->consulta($sql);
        if (gettype($smt)==='array'){
            $respuesta['error']=$smt['error'];
            $respuesta['consulta']=$smt['consulta'];
        }else{
            while ( $result = $smt->fetch_assoc () ) {
                array_push($respuesta,$result);
            }
        }
        return $respuesta;

	}
	
	public function addNumRealTemporal($idTemporal,  $numFactura){
		//@Objetivo:
		//Añadir a una factura temporal el número real de la factura en el caso de que exista 
		$db = $this->db;
		$sql='UPDATE faccliltemporales SET Numfaccli ='.$numFactura.' WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}

    public function buscarDatosTemporal($idFacturaTemporal) {
		//@Objetivo:
		//Buscar los datos de una factura temporal
		$tabla='faccliltemporales';
		$where='id='.$idFacturaTemporal;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}


    public function buscarIdFactura($numFactura){
		//@Objetivo:
		//Buscar el id de una factura real 
		$db=$this->db;
		$smt=$db->query('SELECT id FROM facclit WHERE Numfaccli= '.$numFactura );
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}

    public function comprobarTemporalesIdFac($idFactura,$numFacturaTemp = 0){
        // @Objetivo:
        // Compruebo que solo hay un factura temporal para ese idFactura y ademas si envio temporal compruebo que sea al mismo 
        // @Devuelvo:
        //  Array con o sin errores.
        $repuesta = array();
        if ($idFactura > 0){
            $posible_duplicado = $this->TodosTemporal($idFactura);
            if (!isset($posible_duplicado['error'])){
                $OK ='OK';
                if (count($posible_duplicado)>1){
                     $OK = 'Hay mas de un temporal con el mismo numero factura.';
                } else {
                    // Hay uno solo.
                    if ($numFacturaTemp > 0) {
                        if (isset($posible_duplicado[0]['id']) && $posible_duplicado[0]['id'] !== $numFacturaTemp){
                            $OK = 'Hay un temporal y no coincide el idtemporal.';
                        }
                    } else {
                        if (isset( $posible_duplicado[0]['id']) && $posible_duplicado[0]['id'] >0 ){
                            // Solo devuelvo idTemporal si id > 0    
                            $respuesta['idTemporal'] = $posible_duplicado[0]['id'];
                        }
                    }
                }
                if ($OK !== 'OK' ){
                    // Existe un registro o el que existe es distinto al actual.
                    array_push($respuesta['error'],$this->montarAdvertencia('danger',
                                         '<strong>Ojo posible duplicidad en factura temporal !! </strong>  <br> '.$OK
                                        )
                            );
                }
            }
        }
        return $respuesta;
    }
    
    public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		if ($smt) {
			return $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	}
    
    public function datosFactura($idFactura){
		//@Objetivo:
		//Mostrar los datos de una factura real según el id
		$tabla='facclit';
		$where='id='.$idFactura;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}
	
	public function eliminarFacturasTablas($idFactura){
		//@Objetivo:
		//Eliminar todos los registros de un id de factura real
		$respuesta=array();
		$db=$this->db;
		$sql[]='DELETE FROM  facclilinea where idfaccli ='.$idFactura ;
		$sql[]='DELETE FROM faccliIva where idfaccli ='.$idFactura ;
		$sql[]='DELETE FROM albclifac where idFactura  ='.$idFactura ;
        $sql[]='DELETE FROM  facclit where id='.$idFactura ;
	
       
		foreach($sql as $consulta){
			$smt=$this->consulta($consulta);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				break;
			}
		}
		return $respuesta;
	}

    public function getEstado($idFactura){
        // @ Objetivo
        // Obtener el estado de una factura
        $tabla='facclit';
        $where='id='.$idFactura ;
        $factura = parent::SelectUnResult($tabla, $where);
        $estado = $factura['estado'];
        return $estado;
    
    }

    public function insertarDatosTemporal($idUsuario, $idTienda, $fecha , $albaranes, $productos, $idCliente){
		//@Objetivo:
		//Insertar nuevo registro de factura 
		$db = $this->db;
		$respuesta=array();
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$PrepAlbaranes = $db->real_escape_string($UnicoCampoAlbaranes);
		$sql='INSERT INTO faccliltemporales ( idUsuario , idTienda ,
		 fecha, fechaInicio,idCliente, Albaranes, Productos ) VALUES ('
		 .$idUsuario.' , '.$idTienda.' , "'.$fecha.'",NOW(), '
		 .$idCliente.' , '."'".$PrepAlbaranes."', '".$PrepProductos."'".')';
		 $smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
                error_log('facturaVentas en insertarDatosTemporal de Factura:'.implode(' ',$smt));
				$respuesta['consulta']=$smt['consulta'];
		}else{
			$id=$db->insert_id;
			$respuesta['id']=$id;
			$respuesta['productos']=$productos;
		}
		return $respuesta;
	}

    public function modTotales($idTemporal, $total, $totalivas){
		//@Objetivo:
		//Modificar el total de una factura temporal
		$db=$this->db;
		$sql='UPDATE faccliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}

    public function modificarDatosTemporal($idUsuario, $idTienda, $fecha , $albaranes, $idTemporal, $productos){
		//@Objetivo:
		//Modificar los datos de una factura temporal
		$db = $this->db;
		$respuesta=array();
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$PrepAlbaranes = $db->real_escape_string($UnicoCampoAlbaranes);
		$sql='UPDATE faccliltemporales SET idUsuario='.$idUsuario
		.' , idTienda='.$idTienda.' , Fecha="'
		.$fecha.'" , Albaranes ="'.$PrepAlbaranes.'" ,Productos="'.$PrepProductos
		.' " WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
                error_log('Error mod_ventas/clases/facturasVentas en modificarDatosTemporal:'.$smt['error'].$smt['consulta']);
				return $respuesta;
		}else{
			$respuesta['idTemporal']=$idTemporal;
			$respuesta['productos']=$UnicoCampoProductos;
		}
	
		return $respuesta;
	}

    public function modificarEstado($idFactura, $estado){
		//@Objetivo:
		//Modificar el estado de una factura real
		$db=$this->db;
		$sql='UPDATE facclit set estado="'.$estado .'" where id='.$idFactura;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}

	public function modificarFechaFactura($idFactura, $Fecha, $formaPago, $fechaVenci){
		$respuesta=array();
		$db=$this->db;
		$sql='UPDATE  facclit set Fecha="'.$Fecha.'" , formaPago="'.$formaPago.'" ,  fechaVencimiento="'.$fechaVenci.'" where id='.$idFactura ;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta=array();
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				
		}
		$respuesta['sql']=$sql;
		return $respuesta;
	}

    public function obtenerAlbaranesFactura($idFactura){
        //@Objetivo:
        // Obtener los albaranes de una factura con sus datos.
        $respuesta = array();
        $sql = 'SELECT a.idAlbaran as id ,b.Numalbcli as NumalbCli, b.fecha as Fecha, b.total, b.estado	FROM albclifac as a LEFT JOIN albclit as b on a.idAlbaran=b.id WHERE a.idFactura='.$idFactura;
        $smt=$this->consulta($sql);
        if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$respuesta['Items'] = array();
			while ( $result = $smt->fetch_assoc () ) {
                array_push($respuesta['Items'],$result);
			}
            $respuesta['consulta'] = $sql;

			return $respuesta;
		}
    }

    public function posiblesEstados(){
        // @ Objetivo:
        // Devolver los posibles estados para la tabla de pedido. pedclit
        $posibles_estados = array(  '1'=> array(
											'estado'      =>'Guardado',
											'Descripcion' =>'Estado factura guardado cuando no se esta editando.'
												),
									'2' =>  array(
											'estado'      =>'Sin Guardar',
											'Descripcion' =>'Estado factura que se hay temporal , se esta editando.'
											),
									
									'3' =>  array(
											'estado'      =>'Procesado',
											'Descripcion' =>'Un factura que ya fue procesado. Hay recibo generado.'
											)
                                );
        return $posibles_estados;
    }


    public function sumarIva($numFactura){
		//@Objetivo:
		//Selecciona el importe iva y total base de una factura real
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as
		  totalbase from faccliIva where  Numfaccli  ='.$numFactura);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
}
	


?>
