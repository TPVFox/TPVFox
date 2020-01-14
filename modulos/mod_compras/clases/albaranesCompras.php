<?php

include_once('../mod_compras/clases/ClaseCompras.php');
include_once '../mod_producto/clases/ClaseArticulosStocks.php';
include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
class AlbaranesCompras extends ClaseCompras {

    public $db; //(object) -> Conexion mysqli.
    public $errores = array(); // (array) con los errores de comprobaciones.
    
    public function __construct($conexion) {
        $this->db = $conexion;
        // Obtenemos el numero registros.
        $sql = 'SELECT count(*) as num_reg FROM albprot';
        $respuesta = parent::consulta($sql);
        if (gettype($respuesta) === 'object') {
            $this->num_rows = $respuesta->fetch_object()->num_reg;
        } else {
            // Es un array porque hubo un fallo
            echo '<pre>';
            print_r($respuesta);
            echo '</pre>';
        }
        // Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
    }

    public function modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoPedido, $fecha, $idAlbaranTemporal, $productos, $pedidos, $suNumero) {
        //@Objetivo;
        //Modificamos los datos del pedido temporal, cada vez que hacemos cualquier modificación en el albarán,
        // modificamos el temporal

        $productos_json = json_encode($productos);
        $UnicoCampoProductos = $productos_json;
        $PrepProductos = $this->db->real_escape_string($UnicoCampoProductos);
        $UnicoCampoPedidos = json_encode($pedidos);

        $PrepPedidos = $this->db->real_escape_string($UnicoCampoPedidos);
        $sql = 'UPDATE albproltemporales SET idUsuario =' . $idUsuario . ' , idTienda='
                . $idTienda . ' , estadoAlbPro="' . $estadoPedido . '" , Fecha="' . $fecha . '"  ,Productos="'
                . $PrepProductos . '", Pedidos="' . $PrepPedidos . '" , Su_numero="'
                . $suNumero . '" WHERE id=' . $idAlbaranTemporal;
        $smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
            $respuesta = $smt;
        } else {
            $respuesta['idTemporal'] = $idAlbaranTemporal;
            $respuesta['productos'] = $UnicoCampoProductos;
            $respuesta['pedidos'] = $UnicoCampoPedidos;
        }
        return $respuesta;
    }

    public function insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoPedido, $fecha, $productos, $idProveedor, $pedidos, $suNumero) {
        //Objetivo:
        //insertar un nuevo albaran temporal
        $productos_json = json_encode($productos);
        $UnicoCampoProductos = $productos_json;
        $PrepProductos = $this->db->real_escape_string($UnicoCampoProductos);
        $UnicoCampoPedidos = json_encode($pedidos);
        $PrepPedidos = $this->db->real_escape_string($UnicoCampoPedidos);
        $sql = 'INSERT INTO albproltemporales ( idUsuario , idTienda , estadoAlbPro , Fecha, 
		idProveedor,  Productos, Pedidos , Su_numero) VALUES 
		(' . $idUsuario . ' , ' . $idTienda . ' , "' . $estadoPedido . '" , "' . $fecha . '", ' . $idProveedor . ' , "'
                . $PrepProductos . '" , "' . $PrepPedidos . '", "' . $suNumero . '")';
        $smt= parent::consulta($sql);
        if (gettype($smt)!=='array') {
            $respuesta['id'] = $this->insert_id;
            $respuesta['sql'] = $sql;
        } else {
            // Hubo un error
            $respuesta = $smt;
        }
        return $respuesta;
    }

    public function addNumRealTemporal($idTemporal, $idReal) {
        //Objetivo:
        //Modificar el albarán tempoal en el caso de que tengamos un numeroReal
        $sql = 'UPDATE albproltemporales set Numalbpro =' . $idReal . '  where id=' . $idTemporal;
        $smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
           return $smt;
        }
    }

    public function modEstadoAlbaran($idAlbaran, $estado) {
        // @Objetivo:
        //Modificar el estado del albarán
        $sql = 'UPDATE albprot set estado="' . $estado . '"  where id=' . $idAlbaran;
        $smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
           return $smt;
        }
    }

    public function modTotales($res, $total, $totalivas) {
        //@ Objetivo:
        // Modificar los totales del albarán temporal
        $sql = 'UPDATE albproltemporales set total=' . $total . ' , total_ivas=' . $totalivas . ' where id=' . $res;
        $smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
           return $smt;
        }
    }

    public function buscarAlbaranTemporal($idAlbaranTemporal) {
        //@Objetivo:
        //Buscar los datos del un albarán temporal
        $sql = 'SELECT * FROM albproltemporales WHERE id=' . $idAlbaranTemporal;
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

    public function buscarAlbaranNumero($numAlbaran) {
        //@Objetivo:
        //Buscamos los datos de un albarán real según el número del albarán.
        $tabla = 'albprot';
        $where = 'Numalbpro=' . $numAlbaran;
        $albaran = parent::SelectUnResult($tabla, $where); // Funciona sin haber metido al padre db..
        return $albaran;
    }

    public function eliminarAlbaranTablas($idAlbaran,$tabla = '') {
        //@ Objetivo:
        //Eliminamos todos los registros de un albarán determinado.
        //Tambien descontamos el stock 
        $albaran = $this->datosAlbaran($idAlbaran);
        $lineasAlbaran = $this->ProductosAlbaran($idAlbaran);
        $respuesta = array();
        $tablas = array( 'albprot'=>'id','albprolinea'=>'idalbpro','albproIva'=>'idalbpro','pedproAlb'=>'idAlbaran');
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
        if ($idAlbaran > 0){
            // Solo ejecuto si hay un idPedido y esta OK
            if ($OK === 'OK'){
                foreach($tablas as $tabla =>$campo){
                    $where = 'where '.$campo.' = '.$idAlbaran;
                    $respuesta[$tabla] = parent::deleteRegistrosTabla($tabla,$where);
                }
            }
            // Ahora elimino stock de las lineas eliminadas.
            if (count($respuesta) === 0 ){
                if($albaran && $lineasAlbaran ){
                    $stock = new alArticulosStocks();
                    foreach($lineasAlbaran as $linea){
                        $idArticulo = $linea['idArticulo'];
                        $idTienda = $albaran['idTienda'];
                        $cantidad = $linea['ncant'];
                        $stock->actualizarStock($idArticulo, $idTienda, $cantidad, K_STOCKARTICULO_RESTA);
                    }
                }
            }
        }
        return $respuesta;
    }

    public function AddAlbaranGuardado($datos, $idAlbaran) {
        //@Objetivo:
        //Añadimos los registro de un albarán.
        //Si $idAlbaran es mayor 0, entonces no es nuevo, solo se modifica albprot, donde los campos:
        // FechaCreacion y idUsuario no modifican, ya que se pone fechaModificacion y modify_by
        //El resto tablas se insertan, ya que con anterioridad se borraron los registros para es idAlbaran.
        $respuesta = array();
        $db = $this->db;
        // Aquí tenemos que validar las fechas son correctas
        $datos['fechaVenci'] = $this->ComprobarFecha($datos['fechaVenci']);
        if ($idAlbaran > 0) {
            $sql = 'UPDATE albprot SET Numalbpro ="'. $idAlbaran. '"'
                    .', Fecha ="'. $datos['fecha']. '"'
                    .', modify_by ="'.$datos['idUsuario'].'"'
                    .', estado ="'. $datos['estado'] . '"'
                    .', total ="'. $datos['total']. '"'
                    .', Su_numero ="'. $datos['suNumero'] . '"'
                    .', formaPago ="'. $datos['formaPago'] . '"'
                    .', FechaVencimiento ="'. $datos['fechaVenci'] . '"'
                    .', fechaModificacion = NOW() WHERE id="'. $idAlbaran. '"';
            $smt = parent::consulta($sql);
            if (gettype($smt)==='array') {
                $respuesta = $smt;
            } else {
                $id = $idAlbaran;
                $respuesta['id'] = $id;
            }
        } else {
            $sql = 'INSERT INTO  albprot  (Numtemp_albpro, Fecha, idTienda , idUsuario , idProveedor , estado , 
			total, Su_numero, formaPago, FechaVencimiento) VALUES ('
                    . $datos['Numtemp_albpro'] . ' , "' . $datos['fecha'] . '", ' . $datos['idTienda'] . ', '
                    . $datos['idUsuario'] . ', ' . $datos['idProveedor'] . ' , "' . $datos['estado'] . '", "' . $datos['total']
                    . '", "' . $datos['suNumero'] . '", "' . $datos['formaPago'] . '", "' . $datos['fechaVenci'] . '")';
            $smt = parent::consulta($sql);
            if (gettype($smt)==='array') {
                $respuesta = $smt;
            } else {
                $id = $this->insert_id;
                $respuesta['id'] = $id;
                if (isset($id)) {
                    $sql = 'UPDATE albprot SET Numalbpro  = ' . $id . ' WHERE id =' . $id;
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
        if (!isset($respuesta['error'])) {
            $productos = json_decode($datos['productos'], true);
            $i = 1;
            $numAlbaran = $id;
            $stock = new alArticulosStocks();
            $values = array();
            $sql = 'INSERT INTO albprolinea (idalbpro  , Numalbpro  , idArticulo , cref, ccodbar, 
					cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov , idpedpro )';
            foreach ($productos as $prod) {
                if ($prod['estado'] == 'Activo' || $prod['estado'] == 'activo') {
                    $codBarras = null;
                    $idPed = 0;
                    $refProveedor = " ";
                    if (isset($prod['ccodbar'])) {
                        $codBarras = $prod['ccodbar'];
                    }
                    if (isset($prod['idpedpro'])) {
                        $idPed = $prod['idpedpro'];
                    }
                    if (isset($prod['crefProveedor'])) {
                        $refProveedor = $prod['crefProveedor'];
                    }
                    $values[] ='('. $id . ', ' . $numAlbaran . ' , ' . $prod['idArticulo'] . ', ' . "'" . $prod['cref'] . "'" . ', "'
                            . $codBarras . '", "' . $prod['cdetalle'] . '", "' . $prod['ncant'] . '" , "' . $prod['nunidades'] . '", "'
                            . $prod['ultimoCoste'] . '" , ' . $prod['iva'] . ', ' . $i . ', "' . $prod['estado'] . '" , ' . "'"
                            . $refProveedor . "'" . ', ' . $idPed . ')';
                            
                    
                    // ¿Donde se guarda el error si no actualiza stock? ????
                    $stock->actualizarStock($prod['idArticulo'], $datos['idTienda'], $prod['nunidades'], K_STOCKARTICULO_SUMA);
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
               error_log('Error a la hora insertar productos en albaran '.$idalbpro.' el error:'.json_encode($smt));
            }
            if (!isset($respuesta['error'])){
                foreach ($datos['DatosTotales']['desglose'] as $iva => $basesYivas) {
                    $sql = 'INSERT INTO albproIva'
                           .' (idalbpro  ,  Numalbpro  , iva , importeIva, totalbase) VALUES ('
                           . $id . ', ' . $numAlbaran . ' , ' . $iva . ', '
                           . $basesYivas['iva'] . ' , ' . $basesYivas['base'] . ')';
                    $smt = parent::consulta($sql);
                    if (gettype($smt)==='array') {
                       $respuesta = $smt;
                       break;
                    } 
                }
                $pedidos = json_decode($datos['pedidos'], true);
                if (count($pedidos) > 0) {
                    foreach ($pedidos as $pedido) {
                        if ($pedido['estado'] == 'activo') {
                            $sql = 'INSERT INTO pedproAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) 
                            VALUES (' . $id . ', ' . $numAlbaran . ' ,  ' . $pedido['idAdjunto'] . ' , '
                                    . $pedido['NumAdjunto'] . ')';
                            $smt = parent::consulta($sql);
                            if (gettype($smt)==='array') {
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

    public function EliminarRegistroTemporal($idTemporal, $idAlbaran) {
        //@Objetivo:
        //Cadas vez que añadimos un albarán como guardado tenemos que eliminar el registro temporal
        if ($idAlbaran > 0) {
            $sql = 'DELETE FROM albproltemporales WHERE Numalbpro =' . $idAlbaran;
        } else {
            $sql = 'DELETE FROM albproltemporales WHERE id=' . $idTemporal;
        }
        $smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
            $respuesta = $smt;
            return $respuesta;
        }
    }

    public function TodosTemporal() {
        //@Objetivo:
        //Mostramos todos los albaranes temporales
        $sql = 'SELECT tem.Numalbpro, tem.id , tem.idProveedor, tem.total, 
			b.nombrecomercial from albproltemporales as tem left JOIN proveedores 
			as b on tem.idProveedor=b.idProveedor';
        $smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
            $respuesta = $smt;        
        } else {
            $albaranPrincipal = array();
            while ($result = $smt->fetch_assoc()) {
                array_push($albaranPrincipal, $result);
            }
            $respuesta=$albaranPrincipal;
        }
       return $respuesta;

    }

    public function TodosAlbaranesLimite($limite) {
        //@Objetivo:
        //Obtenemos todos los datos principales de los albaranes de la tabla principal pero con un límite para la paginación
        $respuesta = array();
        $sql = 'SELECT a.id , a.Numalbpro , a.Fecha , b.nombrecomercial, a.total, 
		a.estado  from `albprot` as a LEFT JOIN proveedores as b on 
		a.idProveedor =b.idProveedor  ' . $limite;
        $smt = parent::consulta($sql);
        if (gettype($smt)==='array') {
            $respuesta = $smt; 
        } else {
            $pedidosPrincipal = array();
            while ($result = $smt->fetch_assoc()) {
                array_push($pedidosPrincipal, $result);
            }
            $respuesta['Items'] = $pedidosPrincipal;
            $respuesta['consulta'] = $sql;
            $respuesta['limite'] = $limite;
        }
        return $respuesta;
    }

    public function sumarIva($numAlbaran) {
        //@Objetivo:
        //Sumamos los importes iva y el total de la base de un número de albarán
        $from_where = 'from albproIva where  Numalbpro  =' . $numAlbaran;
        $albaran = parent::sumarIvaBases($from_where);
        return $albaran;
    }

    public function GetAlbaran($id){
        $datos = $this->datosAlbaran($id);
        if (isset($datos['error'])){
            array_push($this->errores,$this->montarAdvertencia(
                                        'danger',
                                        'Error 1 en base datos.Consulta:'.json_encode($datos['consulta'])
                                )
                        );
        }
        $productos =$this->ProductosAlbaranFormulario($id);
        if (isset($productos['error'])){
            array_push($this->errores,$this->montarAdvertencia(
                                        'danger',
                                        'Error 2 en base datos.Consulta:'.json_encode($productos['consulta'])
                                )
                        );
        } 
        $ivas=$this->IvasAlbaran($id);
        if (isset($ivas['error'])){
            array_push($this->errores,$this->montarAdvertencia(
                                        'danger',
                                        'Error 3 en base datos.Consulta:'.json_encode($ivas['consulta'])
                                )
                        );
        }
        $pedidos=$this->PedidosAlbaranes($id);
		if (isset($pedidos['error'])){
			array_push($this->errores,$this->montarAdvertencia(
                                        'danger',
                                        'Error 4 en base datos.Consulta:'.json_encode($ivas['consulta'])
                                )
                        );
		}
        if (count($this->errores)===0 ){
            // Si no hubo errores añadimos datos y formateamos datos fecha.
            $datos['Productos']=$productos;
            $datos['Pedidos'] = $pedidos;
        }
        return $datos;
    }

    public function datosAlbaran($idAlbaran) {
        //@Objetivo:
        //MOstramos los datos de un albarán buscando por ID
        $tabla = 'albprot';
        $where = 'id=' . $idAlbaran;
        $albaran = parent::SelectUnResult($tabla, $where);
        return $albaran;
    }

    public function ProductosAlbaran($idAlbaran) {
        //@Objetivo:
        //BUscamos los productos de un determinado id de albarán
        $tabla = 'albprolinea';
        $where = 'idalbpro= ' . $idAlbaran;
        $albaran = parent::SelectVariosResult($tabla, $where);
        return $albaran;
    }

    public function ProductosAlbaranFormulario($idAlbaran) {
        //@ Objetivo:
        // Es igual que el metodo ProductosAlbaran pero cambiando nombre campos para funciones correctamente.
        $respuesta = [];
        $where = 'idalbpro= ' . $idAlbaran;
        $sql =  'SELECT `id`, `idalbpro`, `Numalbpro`, `idArticulo`, `cref`, `ccodbar`, `cdetalle`, `ncant`, `nunidades`, `costeSiva` as ultimoCoste, `iva`, `nfila`, `estadoLinea` as estado, `ref_prov`, `idpedpro` FROM `albprolinea` WHERE '.$where;
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

    public function IvasAlbaran($idAlbaran) {
        //@Objetivo:
        //Mostramos los registros de iva de un determinado albarán
        $tabla = 'albproIva';
        $where = 'idalbpro= ' . $idAlbaran;
        $albaran = parent::SelectVariosResult($tabla, $where);
        return $albaran;
    }

    public function PedidosAlbaranes($idAlbaran,$completo ='KO') {
        // @ Objetivo:
        //Obtenemos los pedidos que estan añadidos en albaran.
        // @ Parametros
        // $idAlbaran -> (int) Id de albaran
        // $completo -> (string) KO, no hace nada mas . OK -> Obtiene los datos del pedido (fecha y totales)
        // @ Devuelve:
        // Sin ser completo:
        // Los datos que obtenemos tabla pedproAlb ( id,idAlbaran,numAlbaran,idPedido,numPedido)
        // Completo:
        // Devuelve todos los datos pedido:
        //   [id],[Numpedpro],[Numtemp_pedpro],[FechaPedido]
        //  ,[idTienda],[idUsuario],[idProveedor],[estado],[formaPago],[entregado],[total]
        //  ,[fechaCreacion],[fechaModificacion]
        $tabla = 'pedproAlb';
        $where = 'idAlbaran= ' . $idAlbaran;
        $pedidos = parent::SelectVariosResult($tabla, $where);
        if ($completo === 'OK'){
            // Si tiene datos y no trae error.
            if (!isset($pedidos['error']) && count($pedidos) >0){
                // Pide completo y tiene datos.
                $Cped = new PedidosCompras($this->db);
                foreach ($pedidos as $key=>$pedido){   
                    $d = $Cped->DatosPedido($pedido['idPedido']);
                    $pedidos[$key]['Numpedpro'] = $d ['Numpedpro'];
                    $pedidos[$key]['estado'] = $d['estado'];
                    $pedidos[$key]['total']  = $d['total'];
                    $pedidos[$key]['fecha'] = $d['FechaPedido'];               
                }
            }
        }
        return $pedidos;
    }

    public function albaranesProveedorGuardado($idProveedor, $estado) {
        //@Objetivo:
        //Muestra los albaranes de un proveedor determinado con el estado indicado. 
        //Principalmente la utilizamos para saber los
        //albaranes de guardados de un proveedor para poder incluirlo en facturas
        $tabla = 'albprot';
        $where = 'idProveedor= ' . $idProveedor . ' and estado=' . "'" . $estado . "'";
        $albaran = parent::SelectVariosResult($tabla, $where);
        return $albaran;
    }

    public function buscarAlbaranProveedorGuardado($idProveedor, $numAlbaran, $estado) {
        //@Objetivo:
        //Buscar datos principal de un albarán de proveedor y estado guardado
        if ($numAlbaran > 0) {
            $albaran=array();
            $sql = 'SELECT a.Su_numero, a.Numalbpro , a.Fecha , a.total, a.id , a.FechaVencimiento ,
			  a.formaPago , sum(b.totalbase) as totalSiva FROM albprot as a 
			  INNER JOIN albproIva as b on a.id=b.idalbpro where a.idProveedor=' . $idProveedor . ' 
			  and a.estado="' . $estado . '" and a.Numalbpro=' . $numAlbaran . ' GROUP by a.id ';
            $smt = parent::consulta($sql);
            if (gettype($smt)==='array') {
                $albaran = $smt; 
            } else {
                $albaranesPrincipal = array();
                if ($result = $smt->fetch_assoc()) {
                    $albaran = $result;
                    $albaran['Nitem'] = 1;
                }
            }
        } else {
            $sql = 'SELECT a.Su_numero , a.Numalbpro , a.Fecha , a.total, a.id , a.FechaVencimiento , 
			a.formaPago , sum(b.totalbase) as totalSiva FROM albprot as a  INNER JOIN albproIva as b 
			on a.`id`=b.idalbpro where  a.idProveedor=' . $idProveedor . ' and a.estado="' . $estado . '" GROUP by a.id';
            $smt = parent::consulta($sql);
            if (gettype($smt)==='array') {
                $albaran = $smt; 
            } else {
                $albaranesPrincipal = array();
                while ($result = $smt->fetch_assoc()) {
                    array_push($albaranesPrincipal, $result);
                }
                $albaran['datos'] = $albaranesPrincipal;
            }
        }
        return $albaran;
    }

    public function modFechaNumero($id, $suNumero, $fecha, $formaPago, $fechaVencimiento) {
        $respuesta = arrya();
        $sql = 'UPDATE albprot set Su_numero="' . $suNumero . '" , Fecha="' . $fecha . '", formaPago="' . $formaPago . '", FechaVencimiento="' . $fechaVencimiento . '" where id=' . $id;
        $smt = parent::consulta($sql);
         if (gettype($smt)==='array') {
            $respuesta = $smt; 
        }
        return $respuesta;
    }
    
    public function NumfacturaDeAlbaran($numAlbaran){	
		$tabla='albprofac';
		$where='`numAlbaran`='.$numAlbaran;
		$albaran = parent::SelectUnResult($tabla, $where);
		return $albaran;
	}

    public function ComprobarFecha($fecha){
        // @Objetivo:
        // Comprobar si la fecha (string) es correcta, si no es devuelve una fecha 0000-00-00
        // @Devolvemos string
        if (strlen(trim($fecha)) === 0){
            $fecha="0000-00-00";
        }
        return $fecha;
    }

    public function guardarAlbaran(){
        //@ Objetivo:
        // Se comprueba si no hay errroes, se Guardar un albarán, eliminar el temporal y comprobar cambio de precios 
        // para insertarlos en el historico
        //@ Parámetros:
        //  No recibe ya que no necesita, ya lo tiene todo de sistema. (POST,GET ...)
        //[NOTA]
        // - Si es nuevo, es decir no existe idAlbaran, se inserta.
        // - Si se esta modificando entonces , se modifica tabla albprot y resto de tablas se elimina los registros de ese
        // albaran y se vuelven insertar.
       
        $errores=array();
        $Tienda = $_SESSION['tiendaTpv'];
        $Usuario = $_SESSION['usuarioTpv'];
        if (!isset($Tienda['idTienda']) || !isset($Usuario['id'])){
             array_push($errores,$this->montarAdvertencia('danger',
                                    'ERROR NO HAY DATOS DE SESIÓN!'
                                    )
                        );
        }
        // Inicializo variables.
        if (isset($_POST['idTemporal']) ){    
            // Compruebo que tengamos temporal, y obtenemos datos del temporal.
            $idAlbaranTemporal=$_POST['idTemporal'];
            $datosAlbaran=$this->buscarAlbaranTemporal($idAlbaranTemporal);
            if (isset($datosAlbaran['error'])){
                    array_push($errores,$this->montarAdvertencia(
                                    'danger',
                                    'Error 1.1 en base datos.Consulta:'.json_encode($datosAlbaran['consulta'])
                            )
                    );
            }

        } else {
             array_push($errores,$this->montarAdvertencia('warning',
                                            'No enviarte que albaran temporal es no puede modificarlo.'
                                            )
                                );
        }
        if (count($errores) === 0){
            // Continuamos que no hubo error
            $idAlbaran = 0; // valor por defecto.
            if (isset($_GET['id']) && $_POST['estado'] === 'Sin Guardar'){
                $idAlbaran  = $_GET['id'];
            }
            $suNumero   = (isset($_POST['suNumero'])) ? $_POST['suNumero']: '';
            $formaPago  = (isset($_POST['formaVenci'])) ? $_POST['formaVenci'] : '';
            $fechaVenci = (isset($_POST['fechaVenci'])) ? $_POST['fechaVenci'] : '';
            
            if (isset($_POST['hora']) && $_POST['hora'] !=''){
                $f=$_POST['fecha'].' '.$_POST['hora'].':00';
                $fecha=date_format(date_create($f), 'Y-m-d H:i:s');
            } else {
                $fecha =date_format(date_create($_POST['fecha']), 'Y-m-d');
            }               
            // ======            Montamos productos y hacemos recalculo de totales         ======= //
            if (isset ($datosAlbaran['Productos'])){
                $productos=$datosAlbaran['Productos'];
                $productos_para_recalculo = json_decode( $productos );
                if(count($productos_para_recalculo)>0){
                    $CalculoTotales = $this->recalculoTotales($productos_para_recalculo);
                    $total=round($CalculoTotales['total'],2);
                } else {
                    // Hay $datosAlbaran['Productos'], pero no tiene productos.
                    array_push($errores,$this->montarAdvertencia('warning',
                                        'Se obtuvo $datoAlbaran[productos] pero tiene datos !!'
                                        )
                            );

                }
            }else{
                    // No obtuvo $datosAlbaran['Productos'], algo esta mal.
                    array_push($errores,$this->montarAdvertencia('warning',
                                        'No tienes productos  ! !!'
                                        )
                            );
            }
            // ======               Montamos array para insertar        ======= //
            $datos=array(
                'Numtemp_albpro'=>$idAlbaranTemporal,
                'fecha'=>$fecha,
                'idTienda'=>$Tienda['idTienda'],
                'idUsuario'=>$Usuario['id'],
                'idProveedor'=>$datosAlbaran['idProveedor'],
                'estado'=>"Guardado",
                'total'=>$total,
                'DatosTotales'=>$CalculoTotales,
                'productos'=>$productos,
                'pedidos'=>$datosAlbaran['Pedidos'],
                'suNumero'=>$suNumero,
                'formaPago'=>$formaPago,
                'fechaVenci'=>$fechaVenci
            );
            
            if (isset($datosAlbaran['Numalbpro']) && $datosAlbaran['Numalbpro']>0){
                $idAlbaran = $datosAlbaran['Numalbpro'];
                // Solo elimino tablas para volver inserta despues.
                $tablas = array('albprolinea','albproIva','pedproAlb');
                foreach ($tablas as $tabla){
                    $eliminarTablasPrincipal=$this->eliminarAlbaranTablas($datosAlbaran['Numalbpro'],$tabla);
                }
                if (isset($eliminarTablasPrincipal['error'])){
                    // Hubo un error a la hora eliminar tablas principales.
                    array_push($errores,$this->montarAdvertencia('danger',
                                        'Error al eliminar las tablas principales!<br/>'
                                        .$eliminarTablasPrincipal['consulta']
                                        )
                            );
                } else {
                    // No hubo errores al eliminar tabla

                } 
            }
            
            $addNuevo=$this->AddAlbaranGuardado($datos, $idAlbaran);
            if (isset($addNuevo['error'])){
                // Hubo un error a la hora eliminar tablas principales.
                    array_push($errores,$this->montarAdvertencia('danger',
                                        'Error añadir un nuevo albarán !!<br/>'
                                        .'Error:'.$addNuevo['error'].' consulta:'.$addNuevo['consulta']
                                        )
                            );
            }else{
                if(isset($addNuevo['id'])){
                    $dedonde="albaran";
                    $historico=parent::comprobarHistoricoCoste($productos,
                                                $dedonde, $addNuevo['id'],
                                                $datosAlbaran['idProveedor'],
                                                $fecha, $Usuario['id']
                                            );
                    if (isset($historico['error'])){
                        array_push($errores,$this->montarAdvertencia('warning',
                                        'Error en al modificar los coste de los productos !!<br/>'
                                        .$historico['consulta']
                                        )
                            );
                    }
                    $eliminarTemporal=$this->EliminarRegistroTemporal($idAlbaranTemporal, $idAlbaran);
                    if (isset($eliminarTemporal['error'])){
                        array_push($errores,$this->montarAdvertencia('dander',
                                        'Error al eliminar las tablas temporales !!<br/>'
                                        .$eliminarTemporal['consulta']
                                        )
                            );
                    }
                }else{
                    array_push($errores,$this->montarAdvertencia('dander',
                                        'Error al generar id nuevo de la función AddAlbaranGuardado!'
                                        )
                            );
                }
            }
        }
        return $errores;
    }

    

    


}
