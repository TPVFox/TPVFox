<?php
/* Objetivo de esta clase
 *   - Crear un objeto que contenga cierres con todos los datos de estos.
 *   - Tener los parametros cargados, para interactuar con los datos.
 *
 * 
 * */


include_once $URLCom.'/clases/ClaseConexion.php';

class ClaseCierres extends ClaseConexion{
	public $view ; //string ruta de la vista que estamos
    public $BDTpv ; // Objeto de conexion

    public function __construct()
	{
		parent::__construct();
		$this->BDTpv	= parent::getConexion();
		$this->view = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['PHP_SELF']);
	}


    public function obtenerCierres($filtro='',$limite='') {
        // Function para obtener cierres y listarlos
        //tablas usadas: - cierres
                    //	 - usuarios
        $BDTpv = $this->BDTpv;
        $resultado = array();
        if (trim($filtro) !=''){
            $filtro = ' '.$filtro;
        }
        $consulta = "Select c.*, u.nombre as nombreUsuario FROM cierres AS c "
                    ." LEFT JOIN usuarios AS u ON c.idUsuario=u.id ".$filtro.$limite; 
        
        $Resql = $BDTpv->query($consulta);	
        if ($Resql){
            while ($datos = $Resql->fetch_assoc()) {
                $resultado[]=$datos;
            }
        } else  {
            $resultado['consulta'] = $consulta;
            $resultado['error'] = $BDTpv->error;
        }
        //$resultado ['sql'] = $consulta;
        return $resultado;
    }

    public function borrarDatos_tablasCierres($idCierre){
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
        if ($this->UltimoIdCierre() === "$idCierre"){
            // -------        CREAMOS LOS SQL QUE VAMOS EJECTUAR      --------  //
            // -- Obtenemos los tickets de los usuarios de ese cierre -- //
            $sql = 'SELECT * FROM `cierres_usuarios_tickets` WHERE idCierre = '.$idCierre;
            $resultado = $BDTpv->query($sql);	
            while ($datos = $resultado->fetch_assoc()) {
                // Ahora debemos montar las consultas para cambiar estado de tickets de cada usuario
                $respuesta['sql'][]='UPDATE `ticketst` SET `estado`="Cobrado"'
                                .'  WHERE `idTienda`='.$datos['idTienda']
                                .' and `idUsuario`='.$datos['idUsuario']
                                .' and (Numticket>='.$datos['Num_ticket_inicial']
                                .' and Numticket <='.$datos['Num_ticket_final'].')';
            }
            // -- Eliminamos el registros
            $tablas = array('cierres',
                            'cierres_ivas',
                            'cierres_usuariosFormasPago',
                            'cierres_usuarios_tickets'
                            );
            foreach ($tablas as $tabla){
                $sql = 'DELETE FROM '.$tabla.' WHERE idCierre='.$idCierre;
                $respuesta['sql'][] =$sql;
            }
            
            // Ahora volvemos obtener el ultimo registro y le sumamos uno para poner autoincremento.
            // pero solo modificamos el auto_increment de la tabla cierres.
            $sql = 'ALTER TABLE cierres AUTO_INCREMENT =1'; // Ya coje el ultimo que tenga... 
            $respuesta['sql'][] =$sql;
            // Ahora ejecutamos la consultas.
            foreach ($respuesta['sql'] as $sql){
                if ($BDTpv->query($sql)){
                    $respuesta['resultado'] = $BDTpv->affected_rows ;
                } 
            }
            // -- Cambiamos AUTO_INCREMENT de las tabla cierre -- //
            $respuesta['estado'] = 'Ok';
        }
        

        
        return $respuesta;
    
    }

    public function UltimoIdCierre(){
        // @Objetivo
        // Obtener el id del ultimos registro de cierre.
        // @ Devuelve
        // id-> (int) Ultimos registro de cierre.
        $BDTpv = $this->BDTpv;
        $consulta = 'SELECT idCierre from cierres order by idCierre desc  limit 1';
        $resultado= $BDTpv->query($consulta);
        $id= $resultado->fetch_row();
        return $id[0];


    }

}
