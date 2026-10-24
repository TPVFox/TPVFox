<?php
/* Objetivo de esta clase
 *   - Crear un objeto que contenga cierres con todos los datos de estos.
 *   - Tener los parametros cargados, para interactuar con los datos.
 *
 *
 * */


include_once $URLCom . '/clases/ClaseConexion.php';
require_once __DIR__ . '/../../../clases/DB.php';

class ClaseCierres extends ClaseConexion
{
    public $view; //string ruta de la vista que estamos
    public $BDTpv; // Objeto de conexion

    public function __construct()
    {
        parent::__construct();
        $this->BDTpv    = parent::getConexion();
        $this->view = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['PHP_SELF']);
    }


    public function obtenerCierres($filtro = '', $limite = '')
    {
        // Function para obtener cierres y listarlos
        //tablas usadas: - cierres
        //	 - usuarios
        $BDTpv = $this->BDTpv;
        $resultado = array();
        if (trim($filtro) != '') {
            $filtro = ' where ' . $filtro;
        }
        $consulta = "Select c.*, u.nombre as nombreUsuario FROM cierres AS c "
            . " LEFT JOIN usuarios AS u ON c.idUsuario=u.id " . $filtro . $limite;

        // TODO: revisar - $filtro y $limite son fragmentos SQL crudos (WHERE/LIMIT) que llegan como argumentos y no son ligables con ? aquí; la parte parametrizable de esta consulta no concatena valores.
        $Resql = (new DB($BDTpv))->pquery($consulta);
        if ($Resql) {
            while ($datos = $Resql->fetch_assoc()) {
                $resultado[] = $datos;
            }
        } else {
            $resultado['consulta'] = $consulta;
            $resultado['error'] = $BDTpv->error;
        }
        //$resultado ['sql'] = $consulta;
        return $resultado;
    }

    public function borrarDatos_tablasCierres($idCierre)
    {
        // Si el idCierre que recibe es el ultimos entonces
        // Eliminamos un registro de cierre en las tablas
        //      -cierres
        //      -cierres_ivas
        //      -cierres_usuarios_tickets
        //      -cierres_usuariosFormasPago
        // A parte cambia el estado a la tabla de ticketst
        $respuesta = array();
        $respuesta['estado'] = 'KO';
        $BDTpv = $this->BDTpv;

        // -- Comprobamos que idCierre es el ultimo --- //
        if ($this->UltimoIdCierre() === "$idCierre") {
            // -------        CREAMOS LOS SQL QUE VAMOS EJECTUAR      --------  //
            // -- Obtenemos los tickets de los usuarios de ese cierre -- //
            $capa = new DB($BDTpv);
            $sql = 'SELECT * FROM `cierres_usuarios_tickets` WHERE idCierre = ?';
            $resultado = $capa->pquery($sql, array($idCierre));
            $sentencias = array(); // pares [sql, params] a ejecutar de forma parametrizada
            while ($datos = $resultado->fetch_assoc()) {
                // Ahora debemos montar las consultas para cambiar estado de tickets de cada usuario
                $sqlUpdate = 'UPDATE `ticketst` SET `estado`="Cobrado"'
                    . '  WHERE `idTienda`=?'
                    . ' and `idUsuario`=?'
                    . ' and (Numticket>=?'
                    . ' and Numticket <=?)';
                $sentencias[] = array($sqlUpdate, array($datos['idTienda'], $datos['idUsuario'], $datos['Num_ticket_inicial'], $datos['Num_ticket_final']));
                $respuesta['sql'][] = $sqlUpdate;
            }
            // -- Eliminamos el registros
            $tablas = array(
                'cierres_usuarios_tickets',
                'cierres_usuariosFormasPago',
                'cierres_ivas',
                'cierres'
            );
            foreach ($tablas as $tabla) {
                // $tabla es un identificador de una lista blanca fija (nombres de tabla), no un valor
                $sql = 'DELETE FROM ' . $tabla . ' WHERE idCierre = ?';
                $sentencias[] = array($sql, array($idCierre));
                $respuesta['sql'][] = $sql;
            }

            // Ahora volvemos obtener el ultimo registro y le sumamos uno para poner autoincremento.
            // pero solo modificamos el auto_increment de la tabla cierres.
            $sql = 'ALTER TABLE cierres AUTO_INCREMENT =1'; // Ya coje el ultimo que tenga...
            $sentencias[] = array($sql, array());
            $respuesta['sql'][] = $sql;
            // Ahora ejecutamos la consultas.
            foreach ($sentencias as $sentencia) {
                $respuesta['resultado'] = $capa->execute($sentencia[0], $sentencia[1]);
            }
            // -- Cambiamos AUTO_INCREMENT de las tabla cierre -- //
            $respuesta['estado'] = 'Ok';
        }



        return $respuesta;
    }

    public function UltimoIdCierre()
    {
        // @Objetivo
        // Obtener el id del ultimos registro de cierre.
        // @ Devuelve
        // id-> (int) Ultimos registro de cierre.
        $BDTpv = $this->BDTpv;
        $consulta = 'SELECT idCierre from cierres order by idCierre desc  limit 1';
        $resultado = (new DB($BDTpv))->pquery($consulta);
        $id = $resultado->fetch_row();
        return $id[0];
    }

    public function obtenerRangoTicketsUsuarioCierre($idUsuario, $idCierre, $idTienda)
    {
        // @ Objetivo :
        // Obtener el ticke inicial y final para un cierre de un usuario
        $BDTpv = $this->BDTpv;
        $resultado = array();
        $sqlUsuarioTickets = 'SELECT Num_ticket_inicial,Num_ticket_final FROM `cierres_usuarios_tickets` WHERE idCierre = ?'
            . ' AND `idUsuario`= ? AND idTienda = ?';
        $rangoTickets = (new DB($BDTpv))->pquery($sqlUsuarioTickets, array($idCierre, $idUsuario, $idTienda));

        if ($BDTpv->error !== true) {
            if ($rangoTickets->num_rows === 1) {
                // Solo podemos obtener una fila.
                $rango = $rangoTickets->fetch_assoc();
                $resultado = $rango;
            } else {
                $resultado['error'] = ' No hay registros o hay mas de un registro';
                $resultado['consulta'] = $sqlUsuarioTickets;
            }
        } else {
            // Quiere decir que hubo un error.
            $resultado['error'] = 'La consulta o conexion dio un error';
            $resultado['consulta'] = $sqlUsuarioTickets;
        }
        return $resultado;
    }


    public function obtenerTicketsUsuariosCierre($idUsuario, $idCierre, $idTienda, $filtro = '')
    {
        // @ Objetivo :
        // Obtener listado de ticket cerrados de un usuario de un cierre
        $BDTpv = $this->BDTpv;
        $resultado = array();
        // Obtenemos rango tickets para un cierre de un usuario
        $rango = $this->obtenerRangoTicketsUsuarioCierre($idUsuario, $idCierre, $idTienda);
        if (!isset($rango['error'])) {
            $sqlTickets = 'SELECT t.*,c.Nombre,c.razonsocial FROM `ticketst` AS t LEFT JOIN clientes AS c ON c.idClientes = t.idCliente WHERE (t.`Numticket` between ? AND ? AND t.`idTienda`=? AND t.`idUsuario`=?)';
            $paramsTickets = array($rango['Num_ticket_inicial'], $rango['Num_ticket_final'], $idTienda, $idUsuario);
            if ($filtro !== '') {
                // Ahora comprobamos si nos viene un filtro, si es así debemos quitarle WHERE, ya que nuestra consulta ya tiene WHERE
                // lo y la sustituimos por AND
                $filtro =  str_replace('WHERE', 'AND', $filtro);
                // TODO: revisar - $filtro es un fragmento SQL crudo que llega como argumento; no es ligable con ? aquí.
                $sqlTickets .= ' ' . $filtro;
            }
            // Obtenemos los ticket para ese usuario y ese cierre.
            $tickets = (new DB($BDTpv))->pquery($sqlTickets, $paramsTickets);
            if ($BDTpv->error !== true) {
                //~ error_log($sqlTickets);
                while ($ticket = $tickets->fetch_assoc()) {
                    $resultado['datos'][] = $ticket;
                }
            } else {
                $resultado['error'] = ' No hay tickets para ese usuario y ese cierre';
            }
        }
        $resultado['rango'] = $rango; // 'La consulta o conexion dio un error';
        $resultado['consulta2'] = $sqlTickets;
        return $resultado;
    }
}
