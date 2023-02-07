<?php 
include_once $URLCom.'/clases/FormasPago.php';
include_once $URLCom.'/clases/TiposVencimiento.php';
include_once $URLCom.'/clases/ClaseTFModelo.php';


class ClaseCliente extends TFModelo{
    protected $tabla = 'clientes';
    public $adjuntos = array('tickets'      => array('datos' =>array()),
                             'facturas'     => array('datos' =>array()),
                             'albaranes'    => array('datos' =>array()),
                             'pedidos'      => array('datos' =>array()),
                             'desc_tickets' => array('datos' =>array())
                             );  // Los ultimos 15 movimientos de ese cliente.
    public $arrayCliente = array (
                            'idClientes'    => '',
                            'Nombre'        => '',
                            'razonsocial'   => '',
                            'codpostal'     => '',
                            'direccion'     => '',
                            'nif'           => '',
                            'telefono'      => '',
                            'movil'         => '',
                            'fax'           => '',
                            'email'         => '',
                            'formasVenci'   => '{"vencimiento":"0","formapago":"0"}'
                        );
    public function obtenerClientes($filtro='') {
        // Function para obtener clientes y listarlos

        $sql = "Select * from clientes ".$filtro; //.$filtroFinal.$rango; 
        $clientes = $this->consulta($sql);
        if (!isset($clientes['datos'])){
            $clientes['datos']=array(); // mandamos array vacio.
        };
        //$clientes ['consulta'] = $consulta;
        return $clientes['datos'];
    }

    public function contarRegistros($filtro='') {
        $clientes = $this->obtenerClientes ($filtro);
        return count($clientes);
    }                    

    public function getCliente($id){
		//@Objetivo:
		//Obtener los datos de un cliente de la tabla clientes.
		//@Parametros: 
		//id => recibe el id del cliente que queremos buscar
		$sql= 'SELECT * FROM clientes WHERE idClientes='.$id;
		
		return $this->consulta($sql);
	}

    public function getClienteCompleto($id){
        // @ Objetivo:
        // Obtener los datos de un cliente completo:
        //   - Tabla Cliente.
        //   - Obtener de ese cliente los ultimos 15 movimientos, le llamo adjuntos
        //          -Tickets
        //          -Albaranes
        //          -Facturas
        //          -Pedidos.
        //          -Descuentos
        if ($id > 0) {
            $ClienteUnico =$this->getCliente($id);
            if (!isset($ClienteUnico['error'])){
                if (count($ClienteUnico['datos']) >1){
                    // Hay mas de uno por lo que hay error grabe.
                    $mensaje='Para este id:'.$id.' se encontrado '.count($ClienteUnico['datos']).' registros';
                    $ClienteUnico['error'] =$this->montarAdvertencia('danger',$mensaje);

                } else {
                    if (count($ClienteUnico['datos'])===1){
                        $ClienteUnico=$ClienteUnico['datos'][0];
                        $ClienteUnico['adjuntos']=$this->adjuntosCliente($id);
                    } else {
                        // No hay datos para ese id, por lo que debemos informar
                        error_log(json_encode($ClienteUnico));
                        $mensaje = 'Para este id:'.$id.' no hay datos';
                        $ClienteUnico['error'] =$this->montarAdvertencia('danger',$mensaje);

                    }
                }
            }
        } else {
            // Debe ser nuevo porque id es 0
            $ClienteUnico = $this->arrayCliente;
            // Tambien devolvemos los vacios de los adjuntos.
            $ClienteUnico['adjuntos']=$this->adjuntos;
        }
        return $ClienteUnico;

    }

    

   
	public function getTicket($id){
		//@Objetivo: Cargar los ultimos 15 tickets de un cliente en orden descendente
		//@Parametros:
		//id -> id del cliente
		$sql='SELECT Numticket as num , Fecha as fecha , total ,id, idCliente , estado FROM ticketst WHERE idCliente= '.$id.' order by Fecha desc limit 0,15';
		return $this->consulta($sql);
	}
	public function getFacturas($id){
		//@Objetivo:
		//Cargar todas las facturas de un cliente detrerminado en orden descendente
		//@Parametros:
		//id -> id del cliente
		$sql='SELECT Numfaccli as num, Fecha as fecha, total, id , idCliente , estado FROM facclit WHERE idCliente='.$id.' order by Fecha desc limit 0,15';
		return $this->consulta($sql);
	}
	public function getAlbaranes($id){
		//@objetivo:
		//Cargar todos los albaranes de clientes de un cliente determinado en ordes descendente
		//@Parametros:
		//id->id del cliente
		$sql='SELECT Numalbcli as  num, Fecha as fecha, total, id , idCliente, estado FROM albclit WHERE idCliente='.$id.' order by Fecha desc limit 0,15';
		return $this->consulta($sql);
	}
	public function getPedidos($id){
		//@Objetivo:
		//Cargar todos los pedidos de clientes de un cliente determinado en orden descendente
		//@Parametros:
		//id-> id del cliente
		$sql='SELECT Numpedcli as num, Fecha as fecha, total, id , idCliente , estado FROM pedclit WHERE idCliente='.$id.' order by Fecha desc limit 0,15';
		return $this->consulta($sql);
	}

    public function getDescuentosTickets($id){
        //@Objetivo:
        //Cargar todos los descuentos del cliente realizados.
        //@Parametros:
        //id -> id del clietne
        $sql='SELECT descuentoCliente as descuento, fechaInicio, fechaFin as fecha, numTickets as num,importeTickets,importeDescuento as total,idTicket as ticketPago, id , idCliente , estado FROM descuentos_tickets WHERE idCliente='.$id.' order by fechaFin desc limit 0,15';
		return $this->consulta($sql);

    }
	public function adjuntosCliente($id){
		//@Objetivo: 
		//Cargar todos los adjunto de un cliente , tickets, facturas, albaranes y pedidos
		//@Parametros:
		//id-> id del cliente

        // Obtenemos los adjuntos, si hay error devuelve array[error] ,si tene datos array['datos']

		$adjuntos=array( 'tickets'      => $this->getTicket($id),
                         'facturas'     => $this->getFacturas($id),
                         'albaranes'    => $this->getAlbaranes($id),
                         'pedidos'      => $this->getPedidos($id),
                         'desc_tickets' => $this->getDescuentosTickets($id)
                         );
        foreach ($adjuntos as $key=>$adjunto){
            if (isset($adjunto['error'])){
                // Hubo un error en la consulta.
                $adjuntos[$key]= $this->montarAdvertencia('danger',$adjunto['error']) ;
            } else {
                if (!isset($adjunto['datos'])){
                    // No obtubo datos
                    $adjuntos[$key]['datos'] = array();
                }
            }
        }
		return $adjuntos;
	}

	public function modificarDatosCliente($datos){
        //@Objetivo:
        //Modificar los datos de un cliente determinado
        //@Parametros:
        //Datos-> array con todos los datos del cliente
        //id-> id del cliente que se va a modificar
        $respuesta = array();
        // Eliminamos matriz vencimiento y formapago, ya que viene otra que es formasVenci => array("formapago":"1","vencimiento":"1"}
        unset($datos['vencimiento']);
        unset($datos['formapago']);
        $consulta = $this->update($datos, 'idClientes=' . $datos['idClientes']);
		if(isset($consulta['error'])){
			$respuesta['error']= $consulta;
        } else {
            // Fue bien , devolvemos la cantidad de filas modificadas.
            $respuesta = ModeloP::$db->affected_rows;
            // OJ0:
            // Aunque no de error, si hacer un update y tiene lo mismos datos que tenía, la respuesta es 0
        }
        return $respuesta;
    }
	public function addcliente($datos){
        //@Objetivo:
        //Añadir un cliente nuevo
        //@Parametros:
        //datos-> todos los datos que se recogen de la ficha de clientes
        $respuesta = array();

        $sql = 'INSERT INTO `clientes`( `Nombre`, `razonsocial`,
		`nif`, `direccion`, `codpostal`, `telefono`, `movil`, `fax`, `email`,
		`estado`, `formasVenci`, `fecha_creado`, `descuento_ticket`,`requiere_factura`,`recargo_equivalencia`) VALUES ("' . $datos['Nombre'] . '", "' . $datos['razonsocial'] . '",
		"' . $datos['nif'] . '", "' . $datos['direccion'] . '", "' . $datos['codpostal'] . '", "' . $datos['telefono'] . '",
		 "' . $datos['movil'] . '", "' . $datos['fax'] . '", "' . $datos['email'] . '", "' . $datos['estado'] . '", ' . "'" . $datos['formasVenci'] . "'" 
         . ', NOW(), "'.$datos['descuento_ticket'].'", "'.$datos['requiere_factura'].'", "'.$datos['recargo_equivalencia'].'" )';
        $consulta = $this->consultaDML($sql);

		if(isset($consulta['error'])){
            $respuesta['error'] = $consulta;
        } else {
            // Fue bien , devolvemos el id insertado.
            $respuesta = ModeloP::$db->insert_id;
        }
        return $respuesta;
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
        $sql='SELECT `Numticket`, id FROM `ticketst` WHERE `idCliente`='.$idCliente;
		if (!$fechaIni=="" & !$fechaFin==""){
            $sql .= ' and `Fecha` BETWEEN "' . $fechaIni . '" and  "' . $fechaFin . ' 23:59:59"';
        }
        //~ error_log($sql);
		$tickets=$this->consulta($sql);
		if(isset($tickets['error'])){
			$respuesta=$tickets;
		}else{
            $ids= 0;
            if (isset($tickets['datos']){
                $ids=implode(', ', array_column($tickets['datos'], 'id'));
            }
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

    public function albaranClienteFechas($idCliente, $fechaIni, $fechaFin){
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
        $sql='SELECT `Numalbcli`, id FROM `albclit` WHERE `idCliente`='.$idCliente;
		if (!$fechaIni=="" & !$fechaFin==""){
            $sql .= ' and `Fecha` BETWEEN "' . $fechaIni . '" and  "' . $fechaFin . ' 23:59:59"';
        }
        //~ error_log($sql);
		$albaranes=$this->consulta($sql);
		if(isset($albaranes['error'])){
			$respuesta=$albaranes;
		}else{
			$ids=implode(', ', array_column($albaranes['datos'], 'id'));
            if($ids==0){
                $respuesta['error']=1;
                $respuesta['consulta']='No existen ids entre estas fechas';
            }else{
                $sql='SELECT	*,	SUM(nunidades) as totalUnidades	FROM	`albclilinea`	WHERE`idalbcli` IN('.$ids.') GROUP BY idArticulo + `pvpSiva`';
                $productos=$this->consulta($sql);
                if(isset($tickets['error'])){
                    $respuesta=$productos;
                }else{
                    $respuesta['productos']=$productos['datos'];
                }
                $sql='SELECT i.* , t.idTienda, t.idUsuario, sum(i.totalbase) as sumabase , sum(i.importeIva) 
                as sumarIva, t.Fecha as fecha   from albcliIva as i  
                left JOIN albclit as t on t.id=i.idalbcli  where idalbcli 
                in ('.$ids.')  GROUP BY idalbcli;';
                $resumenBases=$this->consulta($sql);
                if(isset($resumenBases['error'])){
                    $respuesta=$resumenBases;
                }else{
                    $respuesta['resumenBases']=$resumenBases['datos'];
                }
                $sql='SELECT *, sum(importeIva) as sumiva , sum(totalbase) as sumBase from albcliIva where idalbcli 
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

    public function getVencimientos(){
        // @ Objetivo:
        // - Obtener todos los tipos vencimiento que tenemos.
        // @ Respuesta
        // Devolvemos los row completos de los vencimientos.
        // Si esta vacio devolvemos false.
        $respuesta = false;
        $CtiposVen=new TiposVencimientos();
        $t=$CtiposVen->todos();

        if (isset($t['datos'])){
            $respuesta = $t['datos'];
        }
        return $respuesta;

    }
    public function getVencimiento($id){
        // @ Objetivo:
        // - Obtener dias de un vencimiento
        // @ Respuesta
        // Devolvemos un int, 0 si no hay respuesta.
        $respuesta = 0;
        $CtiposVen=new TiposVencimientos();
        $datos=$CtiposVen->datosPrincipal($id);
    
        if (isset($datos['0']['dias'])){
            $respuesta = $datos['0']['dias'];
        }
        return $respuesta;

    }


    public function getFormasPago(){
        // @Objetivo:
        // Crear el objeto de vencimientos y poder:
        // - Obtener todos los tipos vencimiento que tenemos.
        // @Notas:
        // Creo que lo ideal sería que fuera una propiedad
        $CFormasPago=new FormasPago();
        $FormasPago=$CFormasPago->todas();
        return $FormasPago;

    }

 public function guardarCliente($datosPost){
        // @ Objetivo:
        // Añadir un cliente o modificar los datos.
        // Devolver las comprobaciones ( advertencias de la comprobaciones).
        // Devolver datos nuevos o antiguos si no fue modificado.
        // estado OK o KO si fue creado o modificado (grabado).
        // @ Parametros:
        //  Array post con los datos para añadir o modificar.
        // @ Devuelve:
        // Array con :
        //     ['datos']            ->Se devuelve datos modificados o grabados en caso OK, KO se devuelve los mismos que nos mando.
        //     ['comprobaciones']   -> Advertencias de la comprobaciones.
        //     ['estado']           -> KO o OK , si fue creado o modificado.
        //     ['errores']          -> Solo se devuelve en caso KO, y se devuelve array no string.
        $respuesta = array();
        $errores = array();
        // Eliminamos el campos de btn de ficha.
        $datosPost = $this->eliminarBtnFichaPost($datosPost);
        // Creamos array con todos los datos, ya que puede que no vengan todos.
        $datosNuevos = $this->arrayCliente;

        // Añadimos ahora obtenemos los datos forma pago y vencimiento y convertimos json.
        $datosForma=array(
                        'formapago'     =>$datosPost['formapago'],
                        'vencimiento'   =>$datosPost['vencimiento']
                        );
        $datosForma=json_encode($datosForma);
        $datosNuevos['formasVenci']= $datosForma;

        foreach ($datosPost as $key=>$datos){
            // Se hace por si No vienen todos para montar array completo.
            // ya que es necesario validar todos los campos.
            $datosNuevos[$key]=$datos;
        }
        // Ahora se debería validar los datos.
        $comprobar=$this->validarDatos($datosNuevos);
        $respuesta['datos'] = $datosPost; // Por si no guarda devolvemos los mismos datos que envio.
        $respuesta['estado'] = 'KO';
        if (!isset($comprobar['errores'])){    
             if($datosPost['idClientes']>0){
                // Cuando ya existe de modifica
                $modificar =$this->modificarDatosCliente($datosNuevos);
                if (isset($modificar['error'])){
                    // Hubo error grave.
                    $respuesta['error'] = $modificar['error'];
                } else {
                    if ($modificar != 1) {
                        // Si se modifico mas uno o 0 es un error grave. 
                        $respuesta['error'] = 'Se ha modificado los datos de '.$modificar.' clientes.[ERROR:clientes 1]';
                    } else {
                        $respuesta['datos'] = $this->getClienteCompleto( $datosPost['idClientes']);
                        $respuesta['estado'] = 'OK';
                    }
                }
            }else{
                $guardar= $this->addcliente($datosNuevos);
                if (isset($modificar['error'])){
                    // Hubo error grave.
                    $respuesta['error'] = $modificar['error'];
                } else {
                    $respuesta['datos'] = $this->getClienteCompleto($guardar);
                    $respuesta['estado'] = 'OK';

                }

            }
        } else {
            $respuesta['error'] = $comprobar['errores'];
        }
        $respuesta['comprobaciones']=$comprobar['comprobaciones'];

        return $respuesta;
    }
    public function validarDatos($datos){
        // @ Objetivo:
        // Hacer las validaciones de los campos correpondientes antes de guardar o modificar.
        // @ Parametros:
        //  $datos -> Array de campos a validar.
        // @ Devuelve:
        // Un array con:
        //    ['datos']         -> Siempre devuelve array con datos o vacio.
        //    ['errores']       -> No siempre lo tiene que devolver, solo si queremos indicar errores graves.
        //    ['comprobaciones']-> Siempre lo devolvemos , aunque se vacio, montamos warning
        $respuesta=array();
        $comprobaciones = array();
        // Elimino los campos que de momento no voy a validar
        //   campo formarVenci que es generado al cargar arrayCliente.
        //   campo formapago que trae el POST
        //   campo vencimiento que trae el POST
        unset($datos['formasVenci']);
        unset($datos['formapago']);
        unset($datos['vencimiento']);

        // Enviamos campos para comprobar si existe el mismo valor en otro registo.
        $generales = array('tabla' => 'clientes','campo_obtener' => 'idClientes','limite' => 15);
        $descarte = array ('idClientes' =>$datos['idClientes']);
        
        $comprobar = $this->comprobarExisteValorCampo($generales,$datos,$descarte);
        foreach ($comprobar as $campo =>$c){
            if (isset($c['error'])){
                // Hubo uno o varios errores en la consulta, es un error grave.
                $respuesta['errores'][] =$c['error'];
            } else{
                // Validamos aquí los campos que tienen valor y encontro registros iguales.
                if ($datos[$campo] != '' && count($c['datos']) > 0) {
                    // Validamos campos que no estan vacios y tiene datos.
                    if ($campo === 'nif'){
                        // Si existe nif otro no permito continuar grabando/modificarlo, se manda error.
                        $respuesta['errores'][] = $this-> montarAdvertencia('danger',$c['datos']);
                    }
                    if ($campo === 'telefono'){
                        // Si existe telefono envio un warning
                        $comprobaciones[] = $this-> montarAdvertencia('warning',$c['datos']);   
                    }

                }

            }

        }
        // Otras validaciones:
        if ($datos['Nombre']==='' && $datos['razonsocial']===''){
            // Damos error ya que no tiene sentido no cubrir cualquiera de estos campos.
            $respuesta['errores'][] = $this-> montarAdvertencia('danger','Debe poner Nombre o razon social');

        }
        $respuesta['comprobaciones'] = $comprobaciones;
        return $respuesta;

    }
    public function eliminarBtnFichaPost($datos){
        // Eliminamos el campos de btn de ficha.
        if (isset($datos['Guardar'])){
            unset($datos['Guardar']);
        }
        // Si existe tb eliminamos el campo Resumen que envia el post.
        if (isset($datos['Resumen'])){
            unset($datos['Resumen']);
        }
        return $datos;
    }

    // ------------------- METODOS COMUNES ----------------------  //
    // -  Al final de cada clase suelo poner aquellos metodos   -  //
    // - que considero que puede ser añadimos algun controlador -  //
    // - comun del core, ya que pienso son necesarios para mas  -  //
    // - modulos.                                                  //
    // ----------------------------------------------------------  //
    
     public function montarAdvertencia($tipo,$mensaje){
        // @ Objetivo:
        // Montar array para error
        $advertencia = array ( 'tipo'    =>$tipo,
                          'mensaje' => $mensaje
        );
        return $advertencia;

    }


    public function comprobarExisteValorCampo($generales,$campos,$descarte=array()){
        // @Objetivo
        // Comprobar varios campos si existen con el mismo valor en algun registros de la tabla indicada.
        // Puede ser necesario que enviemos un campo con un valor para descartar, por ejemplo al modificar
        // un registro queremos descartar el propio registro.
        //
        // @Parametros
        //   $generales -> Array ( tabla         => (string) Nombre de tabla que donde buscamos,
        //                         campo_obtener => (string) nombre campo obtener,
        //                         limite        => (int) 0 ->todos, n -> trae cantidad registros
        //   $campos    -> Array de arrays [0]{ "nombre_campo' => 'valor_campo'}
        //                             ...               
        //   $descarte  -> Array ( nombre_campo_descartar => Valor que vamos descartar)
        //                       
        //
        // @Devolvemos
        // Array => NombreCampo => Array ( 'datos'=>  Siempre se devuelve aunque sea  vacio,
        //                                 'error'=> En caso de que la consulta de un error.
        $respuesta = array();
        $descarte_limite = '';
        if ($generales['limite'] >0){
            $descarte_limite = ' limit 0,'.$generales['limite'];
        }
        if (count($descarte)>0){
            $campo_descarte = key($descarte);
            $valor_descarte = $descarte[$campo_descarte];
            $descarte_limite = ' AND '.$campo_descarte.'<>"'.$valor_descarte.'"'.$descarte_limite;
        }
        foreach ($campos as $nombre_campo=>$valor_campo){
            $sql='SELECT '.$generales['campo_obtener'].' FROM '.$generales['tabla'].' where '.$nombre_campo.'="'.$valor_campo.'"'.$descarte_limite;
            $consulta=$this->consulta($sql);

            if(isset($consulta['error'])){
                $respuesta[$nombre_campo]['error'] = $consulta['error'];
            } 
            if (!isset($consulta['datos'])) {
                // No se relamente es necesario, ya que si la consulta es correcta devuelve algo?
                $consulta['datos']= array();
            }
            $respuesta[$nombre_campo]['datos']= $consulta['datos'];

        }
        return $respuesta;
    }

    public function ticketsClienteDesglose($idCliente, $fechaIni, $fechaFin)
    {
        $respuesta = array();
        $resumen = [];
        $sql1 = 'SELECT id FROM `ticketst` WHERE `idCliente`=' . $idCliente;
        if ($fechaIni !== "" && $fechaFin !== "") {
            $sql1 .= ' and `Fecha` BETWEEN "' . $fechaIni . '" and  "' . $fechaFin . ' 23:00:00"';
        }
        //~ error_log($sql);
        $sql = 'SELECT i.* , t.idTienda, t.idUsuario, sum(i.totalbase) as sumabase , sum(i.importeIva)
                    as sumarIva, t.Fecha as fecha   from ticketstIva as i
                    LEFT JOIN ticketst as t on t.id=i.idticketst  where idticketst
                    in (' . $sql1 . ')  GROUP BY idticketst;';
        $resumenBases = $this->consulta($sql);

        // if (isset($resumenBases['error'])) {
        //     $respuesta = $resumenBases;
        // } else {
            $respuesta = isset($resumenBases['datos']) ? $resumenBases['datos'] : [];
//        }
        return $respuesta;
    }
}
