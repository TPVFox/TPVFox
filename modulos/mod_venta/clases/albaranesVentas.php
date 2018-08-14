<?php
//~ include_once ("./../../../inicial.php");
include_once $URLCom.'/modulos/mod_venta/clases/ClaseVentas.php';
include_once $URLCom.'/modulos/mod_producto/clases/ClaseArticulosStocks.php';


class AlbaranesVentas extends ClaseVentas {

    public function __construct($conexion) {
        $this->db = $conexion;
        // Obtenemos el numero registros.
        $sql = 'SELECT count(*) as num_reg FROM albclit';
        $respuesta = $this->consulta($sql);
        $this->num_rows = $respuesta->fetch_object()->num_reg;
        // Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
    }

    public function consulta($sql) {
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

    public function insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha, $pedidos, $productos, $idCliente) {
        //@Objetivo:
        //Insertar un nuevo registro de albaranes temporales
        $respuesta = array();
        $db = $this->db;
        $UnicoCampoPedidos = json_encode($pedidos);
        $UnicoCampoProductos = json_encode($productos);
        $PrepProductos = $db->real_escape_string($UnicoCampoProductos);
        $PrepPedidos = $db->real_escape_string($UnicoCampoPedidos);
        $sql = 'INSERT INTO albcliltemporales ( idUsuario , idTienda , estadoAlbCli
		 , fechaInicio, idClientes, Pedidos, Productos ) VALUES (' . $idUsuario . ' , '
                . $idTienda . ' , "' . $estadoAlbaran . '" , "' . $fecha . '", ' . $idCliente . ' , "'
                . $PrepPedidos . '", "' . $PrepProductos . '")';
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
        } else {
            $id = $db->insert_id;
            $respuesta['id'] = $id;
            $respuesta['productos'] = $productos;
        }
        return $respuesta;
    }

    public function modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha, $pedidos, $idTemporal, $productos) {
        //@Objetivo:
        //Modificar un registro de albaranes temporales
        $respuesta = array();
        $db = $this->db;
        $UnicoCampoPedidos = json_encode($pedidos);
        $UnicoCampoProductos = json_encode($productos);
        $PrepProductos = $db->real_escape_string($UnicoCampoProductos);
        $PrepPedidos = $db->real_escape_string($UnicoCampoPedidos);
        $sql = 'UPDATE albcliltemporales SET idUsuario=' . $idUsuario
                . ' , idTienda=' . $idTienda . ' , estadoAlbCli="' . $estadoAlbaran . '" , fechaInicio='
                . $fecha . ' , Pedidos="' . $PrepPedidos . '" ,Productos="' . $PrepProductos
                . '"  WHERE id=' . $idTemporal;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
        } else {
            $respuesta['idTemporal'] = $idTemporal;
            $respuesta['productos'] = $UnicoCampoProductos;
        }
        return $respuesta;
    }

    public function addNumRealTemporal($idTemporal, $numAlbaran) {
        //@Objetivo:
        //SI tenemos un número de albarán real lo metemos en el albarán temporal
        $db = $this->db;
        $sql = 'UPDATE albcliltemporales SET numalbcli =' . $numAlbaran
                . ' WHERE id=' . $idTemporal;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }

    public function buscarDatosAlabaranTemporal($idAlbaranTemporal) {
        //@Objetivo:
        //Buscar todos los datos de un albarán temporal
        $tabla = 'albcliltemporales';
        $where = 'id=' . $idAlbaranTemporal;
        $albaran = parent::SelectUnResult($tabla, $where);
        return $albaran;
    }

    public function buscarTemporalNumReal($idAlbaran) {
        //@Objetivo:
        //Buscar todos los datos de un albarán temporal por numero real de albarán cliente
        $tabla = 'albcliltemporales';
        $where = 'numalbcli=' . $idAlbaran;
        $albaran = parent::SelectUnResult($tabla, $where);
        return $albaran;
    }

    public function modTotales($res, $total, $totalivas) {
        //@Objetivo:
        //Modificar el total de un albarán temporal
        $db = $this->db;
        $sql = 'UPDATE albcliltemporales set total=' . $total . ' , total_ivas=' . $totalivas . ' where id=' . $res;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }

    public function eliminarAlbaranTablas($idAlbaran) {
        //@Objetivo:
        //Eliminar todas los registros de un id de albarán real 
        $respuesta = array();
        $db = $this->db;
        $albaran = $this->datosAlbaran($idAlbaran);
        $lineasAlbaran = $this->ProductosAlbaran($idAlbaran);
        $sql[0] = 'DELETE FROM albclit where id=' . $idAlbaran;
        $sql[1] = 'DELETE FROM albclilinea where idalbcli =' . $idAlbaran;
        $sql[2] = 'DELETE FROM albcliIva where idalbcli =' . $idAlbaran;
        $sql[3] = 'DELETE FROM pedcliAlb where idAlbaran =' . $idAlbaran;
        foreach ($sql as $consulta) {
            $smt = $this->consulta($consulta);
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
        //Añadir nuevos registros de un albaran real 
        $respuesta = array();
        $i = 1;
        $db = $this->db;
        //~ error_log('idAlbaran'.$idAlbaran);
        if ($idAlbaran > 0) {
            $sql = 'INSERT INTO albclit (id, Numalbcli, Fecha, idTienda , 
			idUsuario , idCliente , estado , total) VALUES (' . $idAlbaran . ' , ' . $idAlbaran
                    . ', "' . $datos['Fecha'] . '", ' . $datos['idTienda'] . ', ' . $datos['idUsuario'] . ', '
                    . $datos['idCliente'] . ', "' . $datos['estado'] . '", ' . $datos['total'] . ')';
            $smt = $this->consulta($sql);
            if (gettype($smt) === 'array') {
                $respuesta['error'] = $smt['error'];
                $respuesta['consulta'] = $smt['consulta'];
            } else {
                $id = $idAlbaran;
            }
        } else {
            $sql = 'INSERT INTO albclit (Numtemp_albcli, Fecha, idTienda ,
			 idUsuario , idCliente , estado , total) VALUES (' . $datos['Numtemp_albcli']
                    . ' , "' . $datos['Fecha'] . '", ' . $datos['idTienda'] . ', ' . $datos['idUsuario']
                    . ', ' . $datos['idCliente'] . ' , "' . $datos['estado'] . '", ' . $datos['total'] . ')';
            $smt = $this->consulta($sql);
            if (gettype($smt) === 'array') {
                $respuesta['error'] = $smt['error'];
                $respuesta['consulta'] = $smt['consulta'];
            } else {
                $id = $db->insert_id;
                $sql = 'UPDATE albclit SET Numalbcli  = ' . $id . ' WHERE id =' . $id;
                $smt = $this->consulta($sql);
                if (gettype($smt) === 'array') {
                    $respuesta['error'] = $smt['error'];
                    $respuesta['consulta'] = $smt['consulta'];
                }
            }
        }
        $productos = json_decode($datos['productos'], true);
        $stock = new alArticulosStocks();
        foreach ($productos as $prod) {
            if ($prod['estadoLinea'] === 'Activo') {
                $numPed = 0;
                $codBarras = "";
                if (isset($prod['ccodbar'])) {
                    $codBarras = $prod['ccodbar'];
                }
                if (isset($prod['Numpedcli'])) {
                    $numPed = $prod['Numpedcli'];
                }
                if (isset($prod['NumpedCli'])) {
                    $numPed = $prod['NumpedCli'];
                }
                $sql = 'INSERT INTO albclilinea (idalbcli  , Numalbcli , idArticulo ,
				 cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, 
				 estadoLinea, NumpedCli, pvpSiva ) VALUES (' . $id . ', ' . $id . ' , ' . $prod['idArticulo']
                        . ', "' . $prod['cref'] . '", "' . $codBarras . '", "' . $prod['cdetalle'] . '", '
                        . $prod['ncant'] . ' , ' . $prod['nunidades'] . ', ' . $prod['precioCiva'] . ' , '
                        . $prod['iva'] . ', ' . $i . ', "' . $prod['estadoLinea'] . '" , ' . $numPed . ', ' . $prod['pvpSiva'] . ')';
                $smt = $this->consulta($sql);
                if (gettype($smt) === 'array') {
                    $respuesta['error'] = $smt['error'];
                    $respuesta['consulta'] = $smt['consulta'];
                    break;
                }
                $stock->actualizarStock($prod['idArticulo'], $datos['idTienda'], $prod['ncant'], K_STOCKARTICULO_RESTA);
                $i++;
            }
        }
        foreach ($datos['DatosTotales']['desglose'] as $iva => $basesYivas) {
            $sql = 'INSERT INTO albcliIva (idalbcli  ,  Numalbcli  , iva ,
				importeIva, totalbase) VALUES (' . $id . ', ' . $id . ' , ' . $iva . ', '
                    . $basesYivas['iva'] . ' , ' . $basesYivas['base'] . ')';
            $smt = $this->consulta($sql);
            if (gettype($smt) === 'array') {
                $respuesta['error'] = $smt['error'];
                $respuesta['consulta'] = $smt['consulta'];
                break;
            }
        }
        $pedidos = json_decode($datos['pedidos'], true);
        foreach ($pedidos as $pedido) {
            if ($pedido['estado'] == "activo" || $pedido['estado'] == "Activo") {
                $sql = 'INSERT INTO pedcliAlb (idAlbaran  ,  numAlbaran  
					 , idPedido , numPedido) VALUES (' . $id . ', ' . $id . ' ,  ' . $pedido['idPedCli']
                        . ' , ' . $pedido['Numpedcli'] . ')';
                $smt = $this->consulta($sql);
                if (gettype($smt) === 'array') {
                    $respuesta['error'] = $smt['error'];
                    $respuesta['consulta'] = $smt['consulta'];
                    break;
                }
            }
        }
        return $respuesta;
    }

    public function EliminarRegistroTemporal($idTemporal, $idAlbaran) {
        //@Objetivo:
        //Eliminar el albarán temporal indicado
        $db = $this->db;
        if ($idAlbaran > 0) {
            $smt = $db->query('DELETE FROM albcliltemporales WHERE numalbcli =' . $idAlbaran);
        } else {
            $smt = $db->query('DELETE FROM albcliltemporales WHERE id=' . $idTemporal);
        }
    }

    public function TodosAlbaranesFiltro($filtro) {
        //@Objetivo:
        //Mostrar algunos datos de todos los albaranes reales con un filtro
        $db = $this->db;
        $sql = 'SELECT a.id , a.Numalbcli , a.Fecha , b.Nombre, a.total,
		 a.estado FROM `albclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes  ' . $filtro;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        } else {
            $albaranesPrincipal = array();
            while ($result = $smt->fetch_assoc()) {
                array_push($albaranesPrincipal, $result);
            }
            $respuesta = array();
            $respuesta['Items'] = $albaranesPrincipal;
            $respuesta['consulta'] = $sql;
            return $respuesta;
        }
    }

    public function sumarIva($numAlbaran) {
        //@Objetivo:
        //Mostrar la suma de los impirtes ivas y total base   de un albaran real
        $db = $this->db;
        $smt = $db->query('select sum(importeIva ) as importeIva , sum(totalbase)
		 as  totalbase from albcliIva where  Numalbcli  =' . $numAlbaran);
        if ($result = $smt->fetch_assoc()) {
            $albaran = $result;
        }
        return $albaran;
    }

    public function TodosTemporal() {
        //@Objetivo:
        //Mostrar todos los datos temporales
        $db = $this->db;
        $sql = 'SELECT tem.numalbcli, tem.id , tem.idClientes,
			 tem.total, b.Nombre from albcliltemporales as tem left JOIN clientes
			  as b on tem.idClientes=b.idClientes';
        $smt = $this->consulta($sql);
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

    public function datosAlbaran($idAlbaran) {
        //@Objetivo:
        //Datos de un albarán real según id
        $tabla = 'albclit';
        $where = 'id=' . $idAlbaran;
        $albaran = parent::SelectUnResult($tabla, $where);
        return $albaran;
    }

    public function datosAlbaranNum($numAlbaran) {
        //@Objetivo:
        //Datos de un albarán real según numero de cliente
        $tabla = 'albclit';
        $where = 'numalbcli=' . $numAlbaran;
        $albaran = parent::SelectUnResult($tabla, $where);
        return $albaran;
    }

    public function ProductosAlbaran($idAlbaran) {
        //@Objetivo:
        //Muestros los productos de un id de cliente real 
        $tabla = 'albclilinea';
        $where = 'idalbcli= ' . $idAlbaran;
        $albaran = parent::SelectVariosResult($tabla, $where);
        return $albaran;
    }

    public function IvasAlbaran($idAlbaran) {
        //@Objetivo:
        //BUsca en la tabla ivas cliente los datos de un albarán real
        $tabla = 'albcliIva';
        $where = 'idalbcli= ' . $idAlbaran;
        $albaran = parent::SelectVariosResult($tabla, $where);
        return $albaran;
    }

    public function PedidosAlbaranes($idAlbaran) {
        //@Objetivo:
        //Busca los pedidos de un albarán real
        $tabla = 'pedcliAlb';
        $where = 'idAlbaran= ' . $idAlbaran;
        $albaran = parent::SelectVariosResult($tabla, $where);
        return $albaran;
    }

    public function ModificarEstadoAlbaran($idAlbaran, $estado) {
        //@Objetivo:
        //Modificar estado de un albarán real
        $db = $this->db;
        $sql = 'UPDATE albclit SET estado="' . $estado . '" WHERE id=' . $idAlbaran;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }

    public function ComprobarAlbaranes($idCliente, $estado) {
        //@Objetivo:
        //Comprobar los albaranes de un determinado estado
        $db = $this->db;
        $estado = '"' . 'Guardado' . '"';
        $sql = 'SELECT  id from albclit where idCliente=' . $idCliente . ' and estado=' . $estado;
        $albaranes = array();
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        } else {
            while ($result = $smt->fetch_assoc()) {
                $albaranes['alb'] = 1;
            }
            return $albaranes;
        }
    }

    public function AlbaranClienteGuardado($busqueda, $idCliente) {
        $db = $this->db;
        $pedido['busqueda'] = $busqueda;
        if ($busqueda > 0) {
            $sql = 'select  Numalbcli , id , Fecha  , total from albclit where
			 Numalbcli =' . $busqueda . ' and  idCliente=' . $idCliente;
            $smt = $this->consulta($sql);
            if (gettype($smt) === 'array') {
                $pedido['error'] = $smt['error'];
                $pedido['consulta'] = $smt['consulta'];
            } else {
                if ($result = $smt->fetch_assoc()) {
                    $pedido = $result;
                }
                $pedido['Nitem'] = 1;
            }
        } else {
            $sql = 'SELECT  Numalbcli , Fecha  , total , id from albclit 
			where idCliente=' . $idCliente . ' and estado="Guardado"';
            $smt = $this->consulta($sql);
            if (gettype($smt) === 'array') {
                $pedido['error'] = $smt['error'];
                $pedido['consulta'] = $smt['consulta'];
            } else {
                $pedidosPrincipal = array();
                while ($result = $smt->fetch_assoc()) {
                    array_push($pedidosPrincipal, $result);
                }

                $pedido['datos'] = $pedidosPrincipal;
            }
        }
        return $pedido;
    }

    public function modificarFecha($idReal, $fecha) {
        $db = $this->db;
        $sql = 'UPDATE albclit SET Fecha="' . $fecha . '" WHERE id=' . $idReal;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $errores = array();
            $errores['error'] = $smt['error'];
            $errores['consulta'] = $smt['consulta'];
            return $errores;
        }
    }
	public function NumfacturaDeAlbaran($numAlbaran){	
		$db=$this->db;
		$tabla='albclifac';
		$where='`numAlbaran`='.$numAlbaran;
		$albaran = parent::SelectUnResult($tabla, $where);
		return $albaran;
	}

}


