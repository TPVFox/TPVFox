<?php
include_once $URLCom.'/modulos/mod_venta/clases/ClaseVentas.php';
include_once $URLCom.'/modulos/mod_producto/clases/ClaseArticulosStocks.php';


class AlbaranesVentas extends ClaseVentas {

    public function __construct($conexion) {
        parent::__construct($conexion);
        // Obtenemos el numero registros.
        $sql = 'SELECT count(*) as num_reg FROM albclit';
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
    
    public function AddAlbaranGuardado($datos, $idAlbaran) {
        //@Objetivo:
        //Añadir nuevos registros de un albaran real 
        $respuesta = array();
        $errores = array();
        $db = $this->db;
        if ($idAlbaran > 0) {
            $sql = 'INSERT INTO albclit (id, Numalbcli, Fecha, idTienda , 
            idUsuario , idCliente , estado , total) VALUES (' . $idAlbaran . ' , ' . $idAlbaran
                    . ', "' . $datos['Fecha'] . '", ' . $datos['idTienda'] . ', ' . $datos['idUsuario'] . ', '
                    . $datos['idCliente'] . ', "' . $datos['estado'] . '", ' . $datos['total'] . ')';
            $smt = $this->consulta($sql);
            if (gettype($smt) === 'array') {
                error_log('en albaranesVentas AddGuardado(1):'.$smt['error']);
                $errores['0']['error'] = 'albaranesVentas AddGuardado(1):'.$smt['error'];
                $errores['0']['consulta'] = $smt['consulta'];
                return $respuesta;
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
                error_log('en albaranesVentas AddGuardado(2):'.$smt['error']);
                $errores['1']['error'] = 'albaranesVentas AddGuardado(2):'.$smt['error'];
                $errores['1']['consulta'] = $smt['consulta'];
                return $respuesta;
            } else {
                $id = $db->insert_id;
                $sql = 'UPDATE albclit SET Numalbcli  = ' . $id . ' WHERE id =' . $id;
                $smt = $this->consulta($sql);
                if (gettype($smt) === 'array') {
                    error_log('en albaranesVentas AddGuardado(3):'.$smt['error']);
                    $errores['2']['error'] = 'albaranesVentas AddGuardado(3):'.$smt['error'];
                    $errores['2']['consulta'] = $smt['consulta'];
                }
            }
        }
        $productos = json_decode($datos['productos'], true);
        $stock = new alArticulosStocks();
        $i = 1;
        foreach ($productos as $prod) {
            if ($prod['estadoLinea'] === 'Activo') {
                $codBarras = "";
                $numPed = 0;
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
                    error_log('en albaranesVentas AddGuardado(4):'.$smt['error']);
                    $errores['3']['error'] = 'albaranesVentas AddGuardado(4):'.$smt['error'];
                    $errores['3']['consulta'] = $smt['consulta'];
                    break;
                }
                $stock->actualizarStock($prod['idArticulo'], $datos['idTienda'], $prod['ncant'], K_STOCKARTICULO_RESTA);
                $i++;
            }
        }
        foreach ($datos['DatosTotales']['desglose'] as $iva => $basesYivas) {
            $sql = 'INSERT INTO albcliIva (idalbcli  ,  Numalbcli  , iva ,
                importeIva, totalbase) VALUES ("' . $id . '", "' . $id . '" , "' . $iva . '", "'
                    . $basesYivas['iva'] . '" , "' . $basesYivas['base'] . '")';
            $smt = $this->consulta($sql);
            if (gettype($smt) === 'array') {
                error_log('en albaranesVentas AddGuardado(5):'.$smt['error']);
                $errores['4']['error'] ='albaranesVentas AddGuardado(5):'.$smt['error'];
                $errores['4']['consulta'] = $smt['consulta'];
                break;
            }
        }
        $pedidos = json_decode($datos['pedidos'], true);
        foreach ($pedidos as $pedido) {
            if ($pedido['estado'] == "activo" || $pedido['estado'] == "Activo") {
                $sql = 'INSERT INTO pedcliAlb (idAlbaran  ,  numAlbaran  
                     , idPedido , numPedido) VALUES (' . $id . ', ' . $id . ' ,  ' . $pedido['id']
                        . ' , ' . $pedido['NumAdjunto'] . ')';
                $smt = $this->consulta($sql);
                if (gettype($smt) === 'array') {
                    error_log('en albaranesVentas AddGuardado(5):'.$smt['error']);
                    $errores['5']['error'] ='albaranesVentas AddGuardado(5):'.$smt['error'];
                    $errores['5']['consulta'] = $smt['consulta'];
                    break;
                }
            }
        }
        if (count($errores) >0 ){
            // Devolvemos los errores.
            $respuesta['errores'] = $errores;
        }
        return $respuesta;
    }

    public function AlbaranClienteGuardado($busqueda, $idCliente) {
        $respuesta = array();
        $respuesta['busqueda'] = $busqueda;
        if ($busqueda > 0) {
            $sql = 'select  Numalbcli as NumalbCli, id , Fecha  , total from albclit where
             Numalbcli =' . $busqueda . ' and  idCliente=' . $idCliente.' and estado="Guardado"';
            $smt = $this->consulta($sql);
            if (gettype($smt) === 'array') {
                $respuesta['error'] = $smt['error'];
                $respuesta['consulta'] = $smt['consulta'];
            } else {
                $respuesta['Nitems']=0; // Puede que no haya resultados
                if ($result = $smt->fetch_assoc () ){
                    $respuesta['datos']['0']=$result;
                    $respuesta['Nitems']=1;
                }
            }
        } else {
            $sql = 'SELECT  Numalbcli as NumalbCli ,  id , Fecha  , total  from albclit 
            where idCliente=' . $idCliente . ' and estado="Guardado"';
            $smt = $this->consulta($sql);
            if (gettype($smt) === 'array') {
                $respuesta['error'] = $smt['error'];
                $respuesta['consulta'] = $smt['consulta'];
            } else {
                $albaranes = array();
                while ($result = $smt->fetch_assoc()) {
                    array_push($albaranes, $result);
                }
                $respuesta['datos'] = $albaranes;
                $respuesta['Nitems'] = count($albaranes);
            }
        }
        return $respuesta;
    }

    public function ComprobarAlbaranes($idCliente, $estado) {
        //@Objetivo:
        //Comprobar los albaranes de un determinado estado
        $respuesta = array( 'NItems'=>0);
        $db = $this->db;
        $sql = 'SELECT  id from albclit where idCliente=' . $idCliente . ' and estado="' . $estado.'"';
        $smt = $this->consulta($sql);
    
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            
        } else {
            $respuesta['NItems'] = $smt->num_rows;
        }
        return $respuesta;
    }

    public function EliminarRegistroTemporal($idTemporal, $idAlbaran) {
        //@Objetivo:
        //Eliminar  un temporal , pero si trae numero albaran , elimina todos los temporales
        //para es albaran.
        $db = $this->db;
        if ($idAlbaran > 0) {
            $sql = 'DELETE FROM albcliltemporales WHERE Numalbcli =' . $idAlbaran;
        } else {
            $sql = 'DELETE FROM albcliltemporales WHERE id=' . $idTemporal;
        }
        $smt=$this->consulta($sql);
        if (gettype($smt)==='array'){
            $respuesta['error']=$smt['error'];
            $respuesta['consulta']=$sql;
            return $respuesta;
        }
    }

    public function IvasAlbaran($idAlbaran) {
        //@Objetivo:
        //BUsca en la tabla ivas cliente los datos de un albarán real
        $tabla = 'albcliIva';
        $where = 'idalbcli= ' . $idAlbaran;
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
    
    public function NumfacturaDeAlbaran($numAlbaran){   
        $db=$this->db;
        $tabla='albclifac';
        $where='`numAlbaran`='.$numAlbaran;
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
    
    public function TodosTemporal($idAlbaran = 0) {
        //@Objetivo:
        //Mostrar todos los datos temporales
        $db = $this->db;
        $sql = 'SELECT tem.Numalbcli, tem.id , tem.idCliente,
             tem.total, b.Nombre from albcliltemporales as tem left JOIN clientes
              as b on tem.idCliente=b.idClientes';
        if ($idAlbaran > 0){
            // buscamos solos temporales para ese albaran.
            // [OJO] El campo que tenemos en temporal es Numfaccli pero debe se idfaccli
            // ya el día de mañana que pongamos en funcionamiento el poder distinto numero que id
            // dejaría funciona.
            $sql .= ' where tem.Numalbcli='.$idAlbaran;
        }
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        } else {
            $albaranTemporales = array();
            while ($result = $smt->fetch_assoc()) {
                array_push($albaranTemporales, $result);
            }
            return $albaranTemporales;
        }
    }

    public function addNumRealTemporal($idTemporal, $numAlbaran) {
        //@Objetivo:
        //SI tenemos un número de albarán real lo metemos en el albarán temporal
        $db = $this->db;
        $sql = 'UPDATE albcliltemporales SET Numalbcli =' . $numAlbaran
                . ' WHERE id=' . $idTemporal;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }

    
    public function buscarDatosTemporal($idAlbaranTemporal) {
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
        $where = 'Numalbcli=' . $idAlbaran;
        $albaran = parent::SelectUnResult($tabla, $where);
        return $albaran;
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
        $where = 'Numalbcli=' . $numAlbaran;
        $albaran = parent::SelectUnResult($tabla, $where);
        return $albaran;
    }

    public function eliminarAlbaranTablas($idAlbaran) {
        //@Objetivo:
        //Eliminar todas los registros de un id de albarán real 
        $respuesta = array();
        $db = $this->db;
        $albaran = $this->datosAlbaran($idAlbaran);
        $lineasAlbaran = $this->ProductosAlbaran($idAlbaran);
       
        $sql[] = 'DELETE FROM albclilinea where idalbcli =' . $idAlbaran;
        $sql[] = 'DELETE FROM albcliIva where idalbcli =' . $idAlbaran;
        $sql[] = 'DELETE FROM pedcliAlb where idAlbaran =' . $idAlbaran;
        $sql[] = 'DELETE FROM albclit where id=' . $idAlbaran;
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
                $stock->actualizarStock($idArticulo, $idTienda, $cantidad, K_STOCKARTICULO_SUMA);
            }
        }
        
        return $respuesta;
    }

    public function getEstado($idAlbaran){
        // @ Objetivo
        // Obtener el estado de una factura
        $tabla='albclit';
        $where='id='.$idAlbaran ;
        $factura = parent::SelectUnResult($tabla, $where);
        $estado = $factura['estado'];
        return $estado;
    
    }

    public function insertarDatosTemporal($idUsuario, $idTienda, $fecha, $pedidos, $productos, $idCliente) {
        //@Objetivo:
        //Insertar un nuevo registro de albaranes temporales
        $respuesta = array();
        $db = $this->db;
        $UnicoCampoPedidos = json_encode($pedidos);
        $UnicoCampoProductos = json_encode($productos);
        $PrepProductos = $db->real_escape_string($UnicoCampoProductos);
        $PrepPedidos = $db->real_escape_string($UnicoCampoPedidos);
        $sql = 'INSERT INTO albcliltemporales ( idUsuario , idTienda , Fecha, fechaInicio,
                idCliente, Pedidos, Productos ) VALUES (' . $idUsuario . ' , '
                . $idTienda . '  , "' . $fecha . '",NOW(), ' . $idCliente . ' , "'
                . $PrepPedidos . '", "' . $PrepProductos . '")';
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
        error_log('albaranesVentas en insertarDatosTemporal:'.implode(' ',$smt));
            $respuesta['consulta'] = $smt['error'].'consulta'.$smt['consulta'];
        } else {
            $id = $db->insert_id;
            $respuesta['id'] = $id;
            $respuesta['productos'] = $productos;
        }
        return $respuesta;
    }

    public function modTotales($res, $total, $totalivas) {
        //@Objetivo:
        //Modificar el total de un albarán temporal
        $db = $this->db;
        $sql = 'UPDATE albcliltemporales set total="' . $total . '" , total_ivas="' . $totalivas . '" where id=' . $res;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        }
    }

    public function modificarDatosTemporal($idUsuario, $idTienda, $fecha, $pedidos, $idTemporal, $productos) {
        //@Objetivo:
        //Modificar un registro de albaranes temporales
        $respuesta = array();
        $db = $this->db;
        $UnicoCampoPedidos = json_encode($pedidos);
        $UnicoCampoProductos = json_encode($productos);
        $PrepProductos = $db->real_escape_string($UnicoCampoProductos);
        $PrepPedidos = $db->real_escape_string($UnicoCampoPedidos);
        $sql = 'UPDATE albcliltemporales SET idUsuario=' . $idUsuario
                . ' , idTienda=' . $idTienda . ' , Fecha="'
                . $fecha . '" , Pedidos="' . $PrepPedidos . '" ,Productos="' . $PrepProductos
                . '"  WHERE id=' . $idTemporal;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            error_log('Error en clase albaranesVentas en modificarDatosTemporal'.$smt['error']);
            $respuesta['consulta'] = $smt['consulta'];
        } else {
            $respuesta['idTemporal'] = $idTemporal;
            $respuesta['productos'] = $UnicoCampoProductos;
        }
        return $respuesta;
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

    public function obtenerPedidosAlbaran($idAlbaran){
        //@Objetivo:
        // Obtener los albaranes de una factura con sus datos.
        $respuesta = array();
        $sql = 'SELECT a.idPedido as id ,b.Numpedcli as Numpedcli, b.fecha as Fecha, b.total, b.estado  FROM pedcliAlb as a LEFT JOIN pedclit as b on a.idPedido=b.id WHERE a.idAlbaran='.$idAlbaran;
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
        // Devolver los posibles estados para la tabla de albaran. albclit
        $posibles_estados = array(  '1'=> array(
                                            'estado'      =>'Guardado',
                                            'Descripcion' =>'Estado albaran guardado cuando no se esta editando.'
                                                ),
                                    '2' =>  array(
                                            'estado'      =>'Sin Guardar',
                                            'Descripcion' =>'Estado albaran que se hay temporal , se esta editando.'
                                            ),
                                    
                                    '3' =>  array(
                                            'estado'      =>'Procesado',
                                            'Descripcion' =>'Un albaran que ya fue procesado ya se añadió a una factura.No se permite modificar, ni cambiar.'
                                            )
                                );
        return $posibles_estados;
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

}


