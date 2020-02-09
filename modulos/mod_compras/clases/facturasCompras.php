<?php 
include_once ('./clases/ClaseCompras.php');
class FacturasCompras extends ClaseCompras{
    public $errores = array(); // (array) con los errores de comprobaciones.
    
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM facprot';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function TodosTemporal($idFactura = 0){
		//@Objetivo:
		//Obtener todos las facturas temporales o las de una sola factura
        $respuesta = array();
        $sql = 'SELECT tem.numfacpro, tem.id , tem.idProveedor, 
		tem.total, b.nombrecomercial from facproltemporales as tem left 
		JOIN proveedores as b on tem.idProveedor=b.idProveedor ';
         if ($idFactura > 0){
            // buscamos solos temporales para ese albaran.
            // [OJO] El campo que tenemos en temporal es numfacpro pero debe se idfacpro
            // ya el día de mañana que pongamos en funcionamiento el poder distinto numero que id
            // dejaría funciona.
            $sql .= ' where tem.numfacpro='.$idFactura;
        }
		$smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
            // Hubo error devolvemos array (error,consulta)
            $respuesta = $smt;       
        } else {
            while ($result = $smt->fetch_assoc()) {
                array_push($respuesta, $result);
            }
        }
		return $respuesta;	
	}
	
	public function TodosFactura(){
		//@Objetivo:
		//Mostrar solo los datos principales de todas las facturas
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numfacpro , a.Fecha , b.nombrecomercial, 
		a.total, a.estado FROM `facprot` as a LEFT JOIN proveedores as b on
		 a.idProveedor=b.idProveedor ');
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	
	public function TodosFacturaLimite($limite){
		//@Objetivo:
		//Mostrar los datos principales de una factura con un límite de registros
		$db=$this->db;
		$sql= 'SELECT a.id , a.Numfacpro , a.Fecha , b.nombrecomercial, 
		a.total, a.estado FROM `facprot` as a LEFT JOIN proveedores as b on 
		a.idProveedor=b.idProveedor '.$limite;
		$smt=$db->query($sql);
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		$resultado = array();
		$resultado['Items'] = $pedidosPrincipal;
		$resultado['consulta'] = $sql;
		return $resultado;
	}
	
	public function sumarIva($numFactura){
		//@Objetivo:
		//Sumar los resultado de importe iva y total base de una factura determinada
		$from_where= 'from facproIva where Numfacpro ='.$numFactura;
		$factura = parent::sumarIvaBases($from_where);
		
		return $factura;
	}
	
	public function datosFactura($idFactura){
		//@Objetivo:
		//Mostrar los datos de una factura determinada buscada por id
		$tabla='facprot';
		$where='id='.$idFactura;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}
	
	public function ProductosFactura($idFactura){
		//@Objetivo:
		//Buscar los productos de una factura determinada
		$tabla='facprolinea';
		$where='idfacpro= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}

    public function ProductosFacturaFormulario($idFactura) {
        //@ Objetivo:
        // Es igual que el metodo ProductosFactura pero cambiando nombre campos para funciones correctamente.
        $respuesta = [];
        $where = 'idfacpro= ' . $idFactura;
        $sql =  'SELECT `id`, `idfacpro`, `Numfacpro`, `idArticulo`, `cref`, `ccodbar`, `cdetalle`, `ncant`, `nunidades`, `costeSiva` as ultimoCoste, `iva`, `nfila`, `estadoLinea` as estado, `ref_prov`, `idalbpro` FROM `facprolinea` WHERE '.$where;
        $smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
            $respuesta = $smt; 
        } else {
            while ($result = $smt->fetch_assoc()) {
                $respuesta[] = $result;
			}
        }
        return $respuesta;
    }

	
	public function IvasFactura($idFactura){
		//@Objetivo:
		//Buscar los ivas de una factura determinada
		$tabla='facproIva';
		$where='idfacpro= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}
	
	public function albaranesFactura($idFactura){
		//@Objetivo:
		//Buscar todos los albaranes de un id de factura determinado 
		$tabla='albprofac';
		$where='idFactura= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}
	
	public function buscarFacturaTemporal($idFacturaTemporal){
		//@Objetivo:
		//Buscar los datos de una factura temporal
        $sql = 'SELECT * FROM facproltemporales WHERE id=' . $idFacturaTemporal;
        $smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
           $respuesta = $smt;
        } else {
            if ($this->affected_rows > 0){
                // Hubo resultados
                if ($result = $smt->fetch_assoc()) {
                    $respuesta = $result;
                }
            } else {
                // No hubo resultado.
                $respuesta['error'] = 'No se encontro temporal. affect_rows:'.$this->affected_rows;
                $respuesta['consulta'] = $sql;
            }
        }
        return $respuesta;
	}
	
	public function buscarFacturaNumero($numFactura){
		//@Objetivo:
		//Buscar los datos de un número de factura
		$tabla='facprot';
		$where='Numfacpro='.$numFactura;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}
	
	public function modificarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idFacturaTemp, $productos, $albaranes, $suNumero){
		//@Objetivo:
		//MOdficar los datos de una factura temporal
		$db = $this->db;
		$productos_json=json_encode($productos);
		$UnicoCampoProductos 	=$productos_json;
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$PreAlbaran = $db->real_escape_string($UnicoCampoAlbaranes);
		$sql='UPDATE facproltemporales SET idUsuario ='.$idUsuario.' , 
		idTienda='.$idTienda.' , estadoFacPro="'.$estado.'" , fechaInicio="'.$fecha.'"  
		,Productos="'.$PrepProductos.'", Albaranes="'.$PreAlbaran.'" 
		, Su_num_factura="'.$suNumero.'" WHERE id='.$idFacturaTemp;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
		}else{
			$respuesta['idTemporal']=$idFacturaTemp;
			$respuesta['productos']=$UnicoCampoProductos;
			$respuesta['pedidos']=$UnicoCampoAlbaranes;
		}
		return $respuesta;
	}
	
	public function insertarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $albaranes, $suNumero){
		//@Objetivo:
		//Insertar los datos de una factura temporal nueva
		$productos_json=json_encode($productos);
		$UnicoCampoProductos 	=$productos_json;
		$PrepProductos = $this->db->real_escape_string($UnicoCampoProductos);
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$PreAlbaran = $this->db->real_escape_string($UnicoCampoAlbaranes);
		$sql='INSERT INTO facproltemporales ( idUsuario , idTienda , 
		estadoFacPro , fechaInicio, idProveedor,  Productos, Albaranes , 
		Su_num_factura) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estado.'" , "'
		.$fecha.'", '.$idProveedor.' , "'.$PrepProductos.'" , "'
		.$PreAlbaran.'", "'.$suNumero.'")';
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
		}else{
			$id=$this->insert_id;
			$respuesta['id']=$id;
			$respuesta['productos']=$productos;
		}
		return $respuesta;
	}
	
	public function addNumRealTemporal($idTemporal, $idReal){
		//@Objetivo:
		//Añadir a una factura temporal el número de la factura real en el caso de que exista factura real
		$db=$this->db;
		$sql='UPDATE facproltemporales set numfacpro ='.$idReal .'  where id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	
	public function modEstadoFactura($idFactura, $estado){
		//@Objetivo:
		//Modificar el estado de una factura
		$db=$this->db;
		$sql='UPDATE facprot set estado="'.$estado .'"  where id='.$idFactura;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	
	public function modTotales($res, $total, $totalivas){
		//@Objetivo:
		//Modificar el total de una factura temporal, lo hacemos cada vez que añadimos un producto nuevo
		$db=$this->db;
		$sql='UPDATE facproltemporales set total='.$total .' , total_ivas='
		.$totalivas .' where id='.$res;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	
	public function eliminarFacturasTablas($idFactura, $tabla=''){
        //@ Objetivo:
        //Eliminamos todos los registros de una factura determinado.
        $respuesta = array();
        $tablas = array( 'facprot'=>'id','facprolinea'=>'idfacpro','facproIva'=>'idfacpro','albprofac'=>'idFactura');
        $OK = 'KO';
        if ($tabla !==''){
            // Controlamos que la tabla indicada exista en array
            foreach ($tablas as $key=>$t){
                if ($key === $tabla) {
                    $OK ='OK';
                } else {
                    // ELimino de array los nombres tablas que no son .
                    unset($tablas[$key]);
                }
            }
        } else {
            // Pongo en OK porque queremos eliminar las 3 tablas.
            $OK = 'OK';
        }
        if ($idFactura > 0){
            // Solo ejecuto si hay un idPedido y esta OK
            if ($OK === 'OK'){
                foreach($tablas as $tabla =>$campo){
                    $where = 'where '.$campo.' = '.$idFactura;
                    $respuesta[$tabla] = parent::deleteRegistrosTabla($tabla,$where);
                }
            }
        }
        return $respuesta;
	}
	
	public function AddFacturaGuardado($datos, $idFactura){
		//@Objetivo:
		//Añadir todos los datos de una factura nueva en las diferentes tablas
		$respuesta=array();
        if ($idFactura > 0) {
            $sql = 'UPDATE facprot SET Numfacpro ="'. $idFactura. '"'
                    .', Fecha ="'. $datos['fecha']. '"'
                    .', modify_by ="'.$datos['idUsuario'].'"'
                    .', estado ="'. $datos['estado'] . '"'
                    .', total ="'. $datos['total']. '"'
                    .', Su_num_factura ="'. $datos['suNumero'] . '"'
                    .', formaPago ="'. $datos['formaPago'] . '"'
                    .', FechaVencimiento ="'. $datos['fechaVenci'] . '"'
                    .', fechaModificacion = NOW() WHERE id="'. $idFactura. '"';
            $smt = parent::consulta($sql);
            if (gettype($smt)==='array'){
				 $respuesta = $smt;
			}else{
				$id=$idFactura;
				$respuesta['id']=$id;
			}
		}else{
			$sql='INSERT INTO facprot ( Fecha, idTienda , idUsuario , idProveedor , estado , total, Su_num_factura ) VALUES ("'
                    .$datos['fecha'].'", '.$datos['idTienda']
                    . ', '.$datos['idUsuario'].', '.$datos['idProveedor'].' , "'.$datos['estado']
                    .'", '.$datos['total'].', "'.$datos['suNumero'].'")';
            $smt = parent::consulta($sql);
			if (gettype($smt)==='array'){
                $respuesta = $smt;
                error_log('Error en facturasCompras en AddFacturaGuardado:'.$smt['error']);
			}else{
				$id=$this->insert_id;
				$respuesta['id']=$id;
                if (isset($id)) {
                    $sql='UPDATE facprot SET Numfacpro  = '.$id.' WHERE id ='.$id;
                    $smt = parent::consulta($sql);
                    if (gettype($smt)==='array') {
                       $respuesta = $smt;
                    } 
                } else {
                    $respuesta['error'] = "No existe id";
                    $respuesta['consulta'] = "El realiza el insert";
                }
			}
		}
		if (!isset($respuesta['error'])){
			$productos = json_decode($datos['productos'], true);
			$i=1;
            $numFactura = $id;
            $values = array();
            $sql = 'INSERT INTO facprolinea (idfacpro  , Numfacpro  , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov , idalbpro )';
			foreach ( $productos as $prod){
				if ($prod['estado']=='Activo' || $prod['estado']=='activo'){
                    $codBarras = (isset($prod['ccodbar'])) ? $prod['ccodbar']: null;
                    $idAlb = (isset($prod['idalbpro']))? $prod['idalbpro'] : 0;
                    $refProveedor =(isset($prod['crefProveedor']))?  $prod['crefProveedor'] : " ";
                    $values[] ='('. $id . ', ' . $numFactura . ' , ' . $prod['idArticulo'] . ', ' . "'" . $prod['cref']
                            . "'" . ', "' . $codBarras . '", "' . $prod['cdetalle'] . '", "' . $prod['ncant'] . '" , "'
                            . $prod['nunidades'] . '", "' . $prod['ultimoCoste'] . '" , ' . $prod['iva'] . ', '
                            . $i . ', "' . $prod['estado'] . '" , ' . "'" . $refProveedor . "'" . ', ' . $idAlb . ')';
                            
					$i++;
				}
			}
            // Ahora insertamos todos los productos a la vez.
            $valores =' VALUES '.implode(',',$values);
            $sql .= $valores;
            $smt = parent::consulta($sql);
            if (gettype($smt)==='array') {
               $respuesta = $smt;
               // Si hay un error grave, lo registramos en log, ya que hay arreglarlo a mano.
               error_log('Error a la hora insertar productos en factura '.$idFactura.' el error:'.json_encode($smt));
            }
            if (!isset($respuesta['error'])){
                foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
                    $sql='INSERT INTO facproIva (idfacpro  ,  Numfacpro  , iva , 
                    importeIva, totalbase) VALUES ('.$id.', '.$id
                    .' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')';
                    $smt = parent::consulta($sql);
                    if (gettype($smt)==='array'){
                        $respuesta = $smt;
                        break;
                    }
                }
                if (isset($datos['albaranes'])){
                    $albaranes = json_decode($datos['albaranes'], true); 
                    foreach ($albaranes as $albaran){
                        if ($albaran['estado']=='activo'){
                            $sql='INSERT INTO albprofac (idFactura  ,  numFactura   ,
                             idAlbaran , numAlbaran) VALUES ('.$id.', '.$id
                             .' ,  '.$albaran['idAdjunto'].' , '.$albaran['NumAdjunto'].')';
                            $smt = parent::consulta($sql);
                            if (gettype($smt)==='array'){
                                $respuesta = $smt;
                                break;
                            }
                        }
                    }
                }
            }
		}
		return $respuesta;
	}
	
	public function EliminarRegistroTemporal($idTemporal, $idFactura){
		//@Objetivo:
		//CAda vez que guardamos una factura nueva o ya existente eliminamos su temporal
		$db=$this->db;
		
		if ($idFactura>0){
			$sql='DELETE FROM facproltemporales WHERE numfacpro ='.$idFactura;
		}else{
			$sql='DELETE FROM facproltemporales  WHERE id='.$idTemporal;
		}
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function importesFactura($idFactura){
		$db=$this->db;
		$sql='SELECT * FROM facProCobros where idFactura='.$idFactura ;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			$importesPrincipal=array();
			while ($result = $smt->fetch_assoc () ){
				array_push($importesPrincipal,$result);
			}
			return $importesPrincipal;
		}
	}
	public function eliminarRealImportes($idFactura){
		$db=$this->db;
		$sql='DELETE FROM  facProCobros where idFactura='.$idFactura ;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function modificarImportesTemporal($idTemporal, $importes){
		$db=$this->db;
		$sql='UPDATE facproltemporales SET FacCobros='."'".$importes."'".' WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function importesTemporal($idTemporal){
		$db=$this->db;
		$sql='SELECT FacCobros FROM facproltemporales where id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$factura=$result;
			}
			return $factura;
		}
	}
	public function modFechaNumero($id, $fecha, $suNumero){
		$db=$this->db;
		$sql='UPDATE facprot set Su_num_factura ="'.$suNumero.'" , Fecha="'.$fecha.'" where id='.$id;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
    public function AddFacturaAlbaran($idFactura, $idAlbaran){
        $db=$this->db;
        $sql='SELECT * FROM albprofac where idFactura='.$idFactura.' and idAlbaran='.$idAlbaran;
        $smt=$this->consulta($sql);
        if ($result = $smt->fetch_assoc () ){
				$factura=$result;
        }else{
            $sql='INSERT INTO `albprofac`( `idFactura`, `numFactura`, `idAlbaran`, `numAlbaran`) 
            VALUES ('.$idFactura.', '.$idFactura.', '.$idAlbaran.', '.$idAlbaran.')';
            $smt=$this->consulta($sql);
            if (gettype($smt)==='array'){
                $respuesta['error']=$smt['error'];
                $respuesta['consulta']=$smt['consulta'];
                return $respuesta;
            }
        }
    }

    public function comprobarTemporalIdFacpro($idFactura,$numFacturaTemp = 0){
        // @Objetivo:
        // Compruebo que solo hay un factura temporal para ese idPedpro 
        // @Devuelvo:
        //  Array con o sin errores.
        $errores = array();
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
                            $errores['idTemporal'] = $posible_duplicado[0]['id'];
                        }
                    }
                }
                if ($OK !== 'OK' ){
                    // Existe un registro o el que existe es distinto al actual.
                    array_push($errores,$this->montarAdvertencia('danger',
                                         '<strong>Ojo posible duplicidad en factura temporal !! </strong>  <br> '.$OK
                                        )
                            );
                }
            }
        }
        return $errores;
    }

       public function GetFactura($id){
        $datos = $this->datosFactura($id);
        if (isset($datos['error'])){
            array_push($this->errores,$this->montarAdvertencia(
                                        'danger',
                                        'Error 1 en base datos.Consulta:'.json_encode($datos['consulta'])
                                )
                        );
        }
        $productos =$this->ProductosFacturaFormulario($id);
        if (isset($productos['error'])){
            array_push($this->errores,$this->montarAdvertencia(
                                        'danger',
                                        'Error 2 en base datos.Consulta:'.json_encode($productos['consulta'])
                                )
                        );
        } 
        $ivas=$this->IvasFactura($id);
        if (isset($ivas['error'])){
            array_push($this->errores,$this->montarAdvertencia(
                                        'danger',
                                        'Error 3 en base datos.Consulta:'.json_encode($ivas['consulta'])
                                )
                        );
        }
        $albaranes=$this->albaranesFactura($id);
		if (isset($albaranes['error'])){
			array_push($this->errores,$this->montarAdvertencia(
                                        'danger',
                                        'Error 4 en base datos.Consulta:'.json_encode($albaranes['consulta'])
                                )
                        );
		}
        if (count($this->errores)===0 ){
            // Si no hubo errores añadimos datos y formateamos datos fecha.
            $datos['Productos']=$productos;
            $datos['Albaranes'] = $albaranes;
        }
        return $datos;
    }


    public function guardarFactura(){
        $errores=array();
        $Tienda = $_SESSION['tiendaTpv'];
        $Usuario = $_SESSION['usuarioTpv'];
        if (!isset($Tienda['idTienda']) || !isset($Usuario['id'])){
             array_push($errores,$this->montarAdvertencia('danger',
                                    'ERROR NO HAY DATOS DE SESIÓN!'
                                    )
                        );
        }
        if (isset($_GET['tActual'])){
                $_POST['estado']='Sin guardar';
        }
        $idFactura=0;
        // Comprobamos que exista temporal, ya que si no existe no continuamos
        if (isset($_GET['tActual']) && $_GET['tActual']>0){
            $idFacturaTemporal=$_GET['tActual'];
            // Ahora buscamos datos de temporal
            $datosFactura=$this->buscarFacturaTemporal($idFacturaTemporal);
            if (isset($datosFactura['error'])){
                array_push($errores,$this->montarAdvertencia('danger',
                                'Error de SQL en buscarFacturaTemporal: '.$datosFactura['consulta']
                                )
                    );
            }
        }else{
            array_push($errores,$this->montarAdvertencia('warning',
                            'El temporal ya no existe  !'
                            )
                );
        }
        
        // Obtenemos fecha y formateamos , si no es correcta damos error.
        if (isset($_POST['fecha'])){
            if ($_POST['fecha']==""){
                array_push($errores,$this->montarAdvertencia('warning',
                        'Has dejado el campo fecha sin cubrir !'
                        )
                );
            }else{
                $fecha=$_POST['fecha'];
                $fecha =date_format(date_create($_POST['fecha']), 'Y-m-d');
            }
        }
        if (count($errores) === 0){
            // Continuamos que no hubo error
            // Obtenemos su numero
            $suNumero   = (isset($_POST['suNumero'])) ? $_POST['suNumero']: '';
            $formaPago  = (isset($_POST['formaVenci'])) ? $_POST['formaVenci'] : '';
            $fechaVenci = (isset($_POST['fechaVenci'])) ? $_POST['fechaVenci'] : '0000-00-00';

            // ======            Montamos productos y hacemos recalculo de totales         ======= //
            if (isset($datosFactura['Productos'])){
                $productos_para_recalculo = json_decode( $datosFactura['Productos'] );
                $CalculoTotales = recalculoTotales($productos_para_recalculo);
                $total=round($CalculoTotales['total'],2);
            }else{
                array_push($errores,$this->montarAdvertencia('danger',
                            'Error no tienes productos !'
                            )
                );
            }
            // ======               Montamos array para insertar        ======= //
            $datos=array(
                        'fecha'=>$fecha,
                        'idTienda'=>$Tienda['idTienda'],
                        'idUsuario'=>$Usuario['id'],
                        'idProveedor'=>$datosFactura['idProveedor'],
                        'estado'=>"Guardado",
                        'total'=>$total,
                        'DatosTotales'=>$CalculoTotales,
                        'productos'=>$datosFactura['Productos'],
                        'albaranes'=>$datosFactura['Albaranes'],
                        'suNumero'=>$suNumero,
                        'formaPago'=>$formaPago,
                        'fechaVenci'=>$fechaVenci
                    );
                
            if (count($errores) === 0){
                if (isset ($datosFactura['numfacpro']) && $datosFactura['numfacpro']>0) {
                    $idFactura=$datosFactura['numfacpro'];
                    $tablas = array('facprolinea','facproIva','albprofac');
                    foreach ($tablas as $tabla){
                        $eliminarTablasPrincipal=$this->eliminarFacturasTablas($datosFactura['numfacpro'],$tabla);
                    }
                    if (isset($eliminarTablasPrincipal['error'])){
                        // Hubo un error a la hora eliminar tablas principales.
                        array_push($errores,$this->montarAdvertencia('danger',
                                            'Error al eliminar las tablas principales!<br/>'
                                            .$eliminarTablasPrincipal['consulta']
                                            )
                                );
                    } 
                }
                $addNuevo=$this->AddFacturaGuardado($datos, $idFactura);
                if (isset($addNuevo['error'])){
                    array_push($errores,$this->montarAdvertencia('danger',
                                'Error de SQL en AddFacturaGuardado:'.$addNuevo['consulta']
                                )
                        );
                }else{
                    if (isset($addNuevo['id'])){
                        $eliminarTemporal=$this->EliminarRegistroTemporal($idFacturaTemporal,  $idFactura);
                        if (isset($eliminarTemporal['error'])){
                            array_push($errores,$this->montarAdvertencia('danger',
                                'Error de SQL en EliminarRegistroTemporal:'.$eliminarTemporal['consulta']
                                )
                            );
                        }
                    }else{
                        array_push($errores,$this->montarAdvertencia('danger',
                                'Error no hizo el inset de nuevo albarán correctamente'
                                )
                            );
                      
                    }
                }
            }
        }
        return $errores;
    }

    
}

?>
