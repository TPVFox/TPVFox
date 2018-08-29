<?php

//~ include_once ('./clases/ClaseCompras.php');
include_once('../mod_compras/clases/ClaseCompras.php');
include_once '../mod_producto/clases/ClaseArticulosStocks.php';

class AlbaranesCompras extends ClaseCompras {

    public $db; //(object) -> Conexion mysqli.

    public function __construct($conexion) {
        $this->db = $conexion;
        // Obtenemos el numero registros.
        $sql = 'SELECT count(*) as num_reg FROM albprot';
        $respuesta = $this->consulta($sql);
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

    public function consultaAlbaran($sql) {
        // Realizamos la consulta.
        // Esta consulta no tiene sentido teniendo la del padre...

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

    public function modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoPedido, $fecha, $idAlbaranTemporal, $productos, $pedidos, $suNumero) {
        //@Objetivo;
        //Modificamos los datos del pedido temporal, cada vez que hacemos cualquier modificación en el albarán,
        // modificamos el temporal

        $db = $this->db;
        $productos_json = json_encode($productos);
        $UnicoCampoProductos = $productos_json;
        $PrepProductos = $db->real_escape_string($UnicoCampoProductos);
        $UnicoCampoPedidos = json_encode($pedidos);

        $PrepPedidos = $db->real_escape_string($UnicoCampoPedidos);
        $sql = 'UPDATE albproltemporales SET idUsuario =' . $idUsuario . ' , idTienda='
                . $idTienda . ' , estadoAlbPro="' . $estadoPedido . '" , fechaInicio="' . $fecha . '"  ,Productos="'
                . $PrepProductos . '", Pedidos="' . $PrepPedidos . '" , Su_numero="'
                . $suNumero . '" WHERE id=' . $idAlbaranTemporal;
        $smt = $this->consultaAlbaran($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
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
        $db = $this->db;
        $productos_json = json_encode($productos);
        $UnicoCampoProductos = $productos_json;
        $PrepProductos = $db->real_escape_string($UnicoCampoProductos);

        $UnicoCampoPedidos = json_encode($pedidos);
        $PrepPedidos = $db->real_escape_string($UnicoCampoPedidos);
        $sql = 'INSERT INTO albproltemporales ( idUsuario , idTienda , estadoAlbPro , fechaInicio, 
		idProveedor,  Productos, Pedidos , Su_numero) VALUES 
		(' . $idUsuario . ' , ' . $idTienda . ' , "' . $estadoPedido . '" , "' . $fecha . '", ' . $idProveedor . ' , "'
                . $PrepProductos . '" , "' . $PrepPedidos . '", "' . $suNumero . '")';
        $smt = $db->query($sql);
        if ($smt) {
            $respuesta['id'] = $db->insert_id;
            $respuesta['sql'] = $sql;
            return $respuesta;
        } else {
            $respuesta = array();
            $respuesta['consulta'] = $sql;
            $respuesta['error'] = $db->error;
            return $respuesta;
        }
    }

    public function addNumRealTemporal($idTemporal, $idReal) {
        //Objetivo:
        //Modificar el albarán tempoal en el caso de que tengamos un numeroReal
        $db = $this->db;
        $sql = 'UPDATE albproltemporales set numalbpro =' . $idReal . '  where id=' . $idTemporal;
        $smt = $this->consultaAlbaran($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }

    public function modEstadoAlbaran($idAlbaran, $estado) {
        // @Objetivo:
        //Modificar el estado del albarán
        $db = $this->db;
        $sql = 'UPDATE albprot set estado="' . $estado . '"  where id=' . $idAlbaran;
        $smt = $this->consultaAlbaran($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }

    public function modTotales($res, $total, $totalivas) {
        //@Objetivo:Modificar los totales del albarán temporal
        $db = $this->db;
        $sql = 'UPDATE albproltemporales set total=' . $total . ' , total_ivas=' . $totalivas . ' where id=' . $res;
        $smt = $this->consultaAlbaran($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }

    public function buscarAlbaranTemporal($idAlbaranTemporal) {
        //@Objetivo:
        //Buscar los datos del un albarán temporal
        $db = $this->db;
        $sql = 'SELECT * FROM albproltemporales WHERE id=' . $idAlbaranTemporal;
        $smt = $this->consultaAlbaran($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        } else {
            if ($result = $smt->fetch_assoc()) {
                $albaran = $result;
            }
            return $albaran;
        }
    }

    public function buscarAlbaranNumero($numAlbaran) {
        //@Objetivo:
        //Buscamos los datos de un albarán real según el número del albarán.
        $tabla = 'albprot';
        $where = 'Numalbpro=' . $numAlbaran;
        $albaran = parent::SelectUnResult($tabla, $where); // Funciona sin haber metido al padre db..
        return $albaran;
    }

    public function eliminarAlbaranTablas($idAlbaran) {
        //@Objetivo:
        //Eliminamos todos los registros de un albarán determinado. Lo hacemos cuando vamos a crear uno nuevo
        $db = $this->db;

        $albaran = $this->datosAlbaran($idAlbaran);
        $lineasAlbaran = $this->ProductosAlbaran($idAlbaran);

        $sql = array();
        $respuesta = array();
        $sql[0] = 'DELETE FROM albprot where id=' . $idAlbaran;
        $sql[1] = 'DELETE FROM albprolinea where idalbpro =' . $idAlbaran;
        $sql[2] = 'DELETE FROM albproIva where idalbpro =' . $idAlbaran;
        $sql[3] = 'DELETE FROM pedproAlb where idAlbaran =' . $idAlbaran;
        foreach ($sql as $consulta) {
            $smt = $this->consultaAlbaran($consulta);
            if (gettype($smt) === 'array') {
                $respuesta['error'] = $smt['error'];
                $respuesta['consulta'] = $smt['consulta'];
                break;
            }
        }
        if($albaran && $lineasAlbaran && (count($respuesta)==0)){
            $stock = new alArticulosStocks();
            foreach($lineasAlbaran as $linea){
                $idArticulo = $linea['idArticulo'];
                $idTienda = $albaran['idTienda'];
                $cantidad = $linea['ncant'];
                $stock->actualizarStock($idArticulo, $idTienda, $cantidad, K_STOCKARTICULO_RESTA);
            }
        }
        
        


        return $respuesta;
    }

    public function AddAlbaranGuardado($datos, $idAlbaran) {
        //@Objetivo:
        //Añadimos los registro de un albarán nuevo, cada uno en una respectiva tabla
        $respuesta = array();
        $db = $this->db;
        //~ error_log('fecha de clase'.$datos['fecha']);
        if ($idAlbaran > 0) {
            $sql = 'INSERT INTO albprot (id, Numalbpro, Fecha, idTienda , idUsuario , 
			idProveedor , estado , total, Su_numero, formaPago,FechaVencimiento) VALUES ('
                    . $idAlbaran . ' , ' . $idAlbaran . ', "' . $datos['fecha'] . '", ' . $datos['idTienda'] . ', '
                    . $datos['idUsuario'] . ', ' . $datos['idProveedor'] . ', "' . $datos['estado'] . '", ' . $datos['total']
                    . ', "' . $datos['suNumero'] . '", "' . $datos['formaPago'] . '", "' . $datos['fechaVenci'] . '")';
            $smt = $this->consultaAlbaran($sql);
            if (gettype($smt) === 'array') {
                $respuesta['error'] = $smt['error'];
                $respuesta['consulta'] = $smt['consulta'];
            } else {
                $id = $idAlbaran;
                $respuesta['id'] = $id;
            }
        } else {
            $sql = 'INSERT INTO  albprot  (Numtemp_albpro, Fecha, idTienda , idUsuario , idProveedor , estado , 
			total, Su_numero, formaPago, FechaVencimiento) VALUES ('
                    . $datos['Numtemp_albpro'] . ' , "' . $datos['fecha'] . '", ' . $datos['idTienda'] . ', '
                    . $datos['idUsuario'] . ', ' . $datos['idProveedor'] . ' , "' . $datos['estado'] . '", ' . $datos['total']
                    . ', "' . $datos['suNumero'] . '", "' . $datos['formaPago'] . '", "' . $datos['fechaVenci'] . '")';
            $smt = $this->consultaAlbaran($sql);
            if (gettype($smt) === 'array') {
                $respuesta['error'] = $smt['error'];
                $respuesta['consulta'] = $smt['consulta'];
            } else {
                $id = $db->insert_id;
                $respuesta['id'] = $id;
                if (isset($id)) {
                    $sql = 'UPDATE albprot SET Numalbpro  = ' . $id . ' WHERE id =' . $id;
                    $smt = $this->consultaAlbaran($sql);
                    if (gettype($smt) === 'array') {
                        $respuesta['error'] = $smt['error'];
                        $respuesta['consulta'] = $smt['consulta'];
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
            foreach ($productos as $prod) {
                if ($prod['estado'] == 'Activo' || $prod['estado'] == 'activo') {
                    $codBarras = null;
                    $numPed = 0;
                    $refProveedor = " ";
                    if (isset($prod['ccodbar'])) {
                        $codBarras = $prod['ccodbar'];
                    }
                    if (isset($prod['numPedido'])) {
                        $numPed = $prod['numPedido'];
                    }
                    if (isset($prod['crefProveedor'])) {
                        $refProveedor = $prod['crefProveedor'];
                    }
                    $sql = 'INSERT INTO albprolinea (idalbpro  , Numalbpro  , idArticulo , cref, ccodbar, 
					cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov , Numpedpro )
					 VALUES (' . $id . ', ' . $numAlbaran . ' , ' . $prod['idArticulo'] . ', ' . "'" . $prod['cref'] . "'" . ', "'
                            . $codBarras . '", "' . $prod['cdetalle'] . '", "' . $prod['ncant'] . '" , "' . $prod['nunidades'] . '", "'
                            . $prod['ultimoCoste'] . '" , ' . $prod['iva'] . ', ' . $i . ', "' . $prod['estado'] . '" , ' . "'"
                            . $refProveedor . "'" . ', ' . $numPed . ')';
                    $smt = $this->consultaAlbaran($sql);
                    if (gettype($smt) === 'array') {
                        $respuesta['error'] = $smt['error'];
                        $respuesta['consulta'] = $smt['consulta'];
                        break;
                        //~ exit;
                    }
                    // ¿Donde se guarda el error si no actualiza stock? ????
                    $stock->actualizarStock($prod['idArticulo'], $datos['idTienda'], $prod['nunidades'], K_STOCKARTICULO_SUMA);
                    $i++;
                }
                
            }
            foreach ($datos['DatosTotales']['desglose'] as $iva => $basesYivas) {
                $sql = 'INSERT INTO albproIva (idalbpro  ,  Numalbpro  , iva , importeIva, totalbase) VALUES ('
                        . $id . ', ' . $numAlbaran . ' , ' . $iva . ', ' . $basesYivas['iva'] . ' , ' . $basesYivas['base'] . ')';
                $smt = $this->consultaAlbaran($sql);
                if (gettype($smt) === 'array') {
                    $respuesta['error'] = $smt['error'];
                    $respuesta['consulta'] = $smt['consulta'];
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
                        $smt = $this->consultaAlbaran($sql);
                        if (gettype($smt) === 'array') {
                            $respuesta['error'] = $smt['error'];
                            $respuesta['consulta'] = $smt['consulta'];
                            break;
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
        $db = $this->db;
        if ($idAlbaran > 0) {
            $sql = 'DELETE FROM albproltemporales WHERE numalbpro =' . $idAlbaran;
        } else {
            $sql = 'DELETE FROM albproltemporales WHERE id=' . $idTemporal;
        }
        $smt = $this->consultaAlbaran($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }

    public function TodosTemporal() {
        //@Objetivo:
        //Mostramos todos los albaranes temporales
        $db = $this->db;
        $sql = 'SELECT tem.numalbpro, tem.id , tem.idProveedor, tem.total, 
			b.nombrecomercial from albproltemporales as tem left JOIN proveedores 
			as b on tem.idProveedor=b.idProveedor';
        $smt = $this->consultaAlbaran($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        } else {
            $albaranPrincipal = array();
            while ($result = $smt->fetch_assoc()) {
                array_push($albaranPrincipal, $result);
            }
            return $albaranPrincipal;
        }
    }

    public function TodosAlbaranesLimite($limite) {
        //@Objetivo:
        //MOstramos todos los datos principales de los albaranes de la tabla principal pero con un límite para la paginación
        $db = $this->db;
        $sql = 'SELECT a.id , a.Numalbpro , a.Fecha , b.nombrecomercial, a.total, 
		a.estado  from `albprot` as a LEFT JOIN proveedores as b on 
		a.idProveedor =b.idProveedor  ' . $limite;
        $smt = $this->consultaAlbaran($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        } else {
            $pedidosPrincipal = array();
            while ($result = $smt->fetch_assoc()) {
                array_push($pedidosPrincipal, $result);
            }
            $resultado = array();
            $resultado['Items'] = $pedidosPrincipal;
            $resultado['consulta'] = $sql;
            $resultado['limite'] = $limite;
            return $resultado;
        }
    }

    public function sumarIva($numAlbaran) {
        //@Objetivo:
        //Sumamos los importes iva y el total de la base de un número de albarán
        $from_where = 'from albproIva where  Numalbpro  =' . $numAlbaran;
        $albaran = parent::sumarIvaBases($from_where);

        return $albaran;
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

    public function IvasAlbaran($idAlbaran) {
        //@Objetivo:
        //Mostramos los registros de iva de un determinado albarán
        $tabla = 'albproIva';
        $where = 'idalbpro= ' . $idAlbaran;
        $albaran = parent::SelectVariosResult($tabla, $where);
        return $albaran;
    }

    public function PedidosAlbaranes($idAlbaran) {
        //@Objetivo:
        //MUestra los pedidos de un número de albarán
        $tabla = 'pedproAlb';
        $where = 'idAlbaran= ' . $idAlbaran;
        $albaran = parent::SelectVariosResult($tabla, $where);
        return $albaran;
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
        $db = $this->db;
        if ($numAlbaran > 0) {
          
            $albaran=array();
            //~ $sql='SELECT Numalbpro , Fecha , total, id , FechaVencimiento ,
            //~ formaPago FROM albprot WHERE idProveedor= '.$idProveedor.' and estado='."'"
            //~ .$estado."'".' and Numalbpro='.$numAlbaran;
            $sql = 'SELECT a.Su_numero, a.Numalbpro , a.Fecha , a.total, a.id , a.FechaVencimiento ,
			  a.formaPago , sum(b.totalbase) as totalSiva FROM albprot as a 
			  INNER JOIN albproIva as b on a.id=b.idalbpro where a.idProveedor=' . $idProveedor . ' 
			  and a.estado="' . $estado . '" and a.Numalbpro=' . $numAlbaran . ' GROUP by a.id ';
            $smt = $this->consultaAlbaran($sql);
            if (gettype($smt) === 'array') {
                $albaran['error'] = $smt['error'];
                $albaran['consulta'] = $smt['consulta'];
                return $respuesta;
            } else {
               
                $albaranesPrincipal = array();
                if ($result = $smt->fetch_assoc()) {
                    error_log("entre aqui");
                    $albaran = $result;
                    $albaran['Nitem'] = 1;
                    
                }
                
              
                
            }
        } else {
            //~ $sql='SELECT Numalbpro, Fecha, total, id , FechaVencimiento , 
            //~ formaPago  FROM albprot WHERE idProveedor= '.$idProveedor.'  and estado="'
            //~ .$estado.'"';
            $sql = 'SELECT a.Su_numero , a.Numalbpro , a.Fecha , a.total, a.id , a.FechaVencimiento , 
			a.formaPago , sum(b.totalbase) as totalSiva FROM albprot as a  INNER JOIN albproIva as b 
			on a.`id`=b.idalbpro where  a.idProveedor=' . $idProveedor . ' and a.estado="' . $estado . '" GROUP by a.id';
            $smt = $this->consultaAlbaran($sql);
            if (gettype($smt) === 'array') {
                $albaran['error'] = $smt['error'];
                $albaran['consulta'] = $smt['consulta'];
                return $respuesta;
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
        $db = $this->db;
        $sql = 'UPDATE albprot set Su_numero="' . $suNumero . '" , Fecha="' . $fecha . '", formaPago="' . $formaPago . '", FechaVencimiento="' . $fechaVencimiento . '" where id=' . $id;
        $smt = $this->consultaAlbaran($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }
    
    public function NumfacturaDeAlbaran($numAlbaran){	
		$db=$this->db;
		$tabla='albprofac';
		$where='`numAlbaran`='.$numAlbaran;
		$albaran = parent::SelectUnResult($tabla, $where);
		return $albaran;
	}


}
