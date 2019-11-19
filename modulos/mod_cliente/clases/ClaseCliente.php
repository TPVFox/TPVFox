<?php 
include_once $URLCom.'/clases/FormasPago.php';
include_once $URLCom.'/clases/TiposVencimiento.php';
include_once $URLCom.'/clases/ClaseTFModelo.php';


class ClaseCliente extends TFModelo{
    protected $tabla = 'clientes';
    public $adjuntos = array('tickets'  => array(),
                             'facturas' => array(),
                             'albaranes'=> array(),
                             'pedidos'  => array()
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
                        

    public function getClienteCompleto($id){
        // @ Objetivo:
        // Obtener los datos de un cliente completo:
        //   - Tabla Cliente.
        //   - Obtener de ese cliente los ultimos 15 movimientos, le llamo adjuntos
        //          -Tickets
        //          -Albaranes
        //          -Facturas
        //          -Pedidos.
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
            foreach ($this->adjuntos as $key=>$adjunto){
                $ClienteUnico['adjuntos'][$key]['datos'] = array();
            }
        }
        return $ClienteUnico;

    }

    public function obtenerClientes($filtro='') {
	// Function para obtener clientes y listarlos

	$clientes = array();
	$sql = "Select * from clientes ".$filtro; //.$filtroFinal.$rango; 
	$clientes = $this->consulta($sql);

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
	public function getTicket($id){
		//@Objetivo: Cargar los ultimos 15 tickets de un cliente en orden descendente
		//@Parametros:
		//id -> id del cliente
		$sql='SELECT Numticket as num , Fecha as fecha , total ,id, idCliente , estado FROM ticketst WHERE idCliente= '.$id.' order by Numticket desc limit 0,15';
		return $this->consulta($sql);
	}
	public function getFacturas($id){
		//@Objetivo:
		//Cargar todas las facturas de un cliente detrerminado en orden descendente
		//@Parametros:
		//id -> id del cliente
		$sql='SELECT Numfaccli as num, Fecha as fecha, total, id , idCliente , estado FROM facclit WHERE idCliente='.$id.' order by Numfaccli desc limit 0,15';
		return $this->consulta($sql);
	}
	public function getAlbaranes($id){
		//@objetivo:
		//Cargar todos los albaranes de clientes de un cliente determinado en ordes descendente
		//@Parametros:
		//id->id del cliente
		$sql='SELECT Numalbcli as  num, Fecha as fecha, total, id , idCliente, estado FROM albclit WHERE idCliente='.$id.' order by Numalbcli desc limit 0,15';
		return $this->consulta($sql);
	}
	public function getPedidos($id){
		//@Objetivo:
		//Cargar todos los pedidos de clientes de un cliente determinado en orden descendente
		//@Parametros:
		//id-> id del cliente
		$sql='SELECT Numpedcli as num, FechaPedido as fecha, total, id , idCliente , estado FROM pedclit WHERE idCliente='.$id.' order by Numpedcli desc limit 0,15';
		return $this->consulta($sql);
	}
	public function adjuntosCliente($id){
		//@Objetivo: 
		//Cargar todos los adjunto de un cliente , tickets, facturas, albaranes y pedidos
		//@Parametros:
		//id-> id del cliente

        // Obtenemos los adjuntos, si hay error devuelve array[error] ,si tene datos array['datos']

		$adjuntos=array( 'tickets'  => $this->getTicket($id),
                         'facturas' => $this->getFacturas($id),
                         'albaranes'=> $this->getAlbaranes($id),
                         'pedidos'  => $this->getPedidos($id)
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

	public function modificarDatosCliente($datos, $id){
		//@Objetivo:
		//Modificar los datos de un cliente determinado
		//@Parametros:
		//Datos-> array con todos los datos del cliente
		//id-> id del cliente que se va a modificar
        $respuesta = array();
        $sql='UPDATE `clientes` SET Nombre="'.$datos['Nombre'].'" , razonsocial="'.$datos['razonsocial'].'" , 
		nif="'.$datos['nif'].'" , direccion="'.$datos['direccion'].'" , codpostal="'.$datos['codpostal'].'" , telefono="'.$datos['telefono']
		.'" , movil="'.$datos['movil'].'" , fax="'.$datos['fax'].'" , email="'.$datos['email'].'" , estado="'.$datos['estado'].'" ,
		formasVenci='."'".$datos['formasVenci']."'".' WHERE idClientes='.$id;
        $consulta=$this->consultaDML($sql);
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
        $sql='INSERT INTO `clientes`( `Nombre`, `razonsocial`, 
		`nif`, `direccion`, `codpostal`, `telefono`, `movil`, `fax`, `email`, 
		`estado`, `formasVenci`, `fecha_creado`) VALUES ("'.$datos['Nombre'].'", "'.$datos['razonsocial'].'", 
		"'.$datos['nif'].'", "'.$datos['direccion'].'", "'.$datos['codpostal'].'", "'.$datos['telefono'].'",
		 "'.$datos['movil'].'", "'.$datos['fax'].'", "'.$datos['email'].'", "'.$datos['estado'].'", '."'".$datos['formasVenci']."'".', NOW())';
   		$consulta=$this->consultaDML($sql);

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
			$sql .=' and `Fecha` BETWEEN "'.$fechaIni.'" and  "'.$fechaFin.' 23:00:00"';
		}
		//~ error_log($sql);
		$tickets=$this->consulta($sql);
		if(isset($tickets['error'])){
			$respuesta=$tickets;
		}else{
			$ids=implode(', ', array_column($tickets['datos'], 'id'));
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

    public function getVencimientos(){
        // @Objetivo:
        // Crear el objeto de vencimientos y poder:
        // - Obtener todos los tipos vencimiento que tenemos.
        // @Notas:
        // Creo que lo ideal sería que fuera una propiedad
        $CtiposVen=new TiposVencimientos();
        $tiposVen=$CtiposVen->todos();
        return $tiposVen;

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
        //@ Objetivo:
        //Guardar los datos de un cliente
        //Primero realiza comprobaciones de todos los campos y dependiendo si tiene id de cliente o no
        //modifica o crear un nuevo cliente
        //@ Parametros:
        //datosPost: datos que recibimos del formulario
        //@ Devolvemos respuesta (array):
        //  ['id'] => (int) id del cliente añadido o modificado. ( Si hay error puede que no lo devuelva.
        //  ['comprobaciones'] = (Array) con errores , tanto graves como no.
        $respuesta=array();
        $datosNuevos = $this->arrayCliente;
        $datosForma=array(
                        'formapago'     =>$datosPost['formapago'],
                        'vencimiento'   =>$datosPost['vencimiento']
                        );
        $datosForma=json_encode($datosForma);
        
        foreach ($datosPost as $key=>$datos){
            $datosNuevos[$key]=$datos;
        }
        $datosNuevos['formasVenci']= $datosForma;
        
        $comprobar=$this->comprobarExistenDatos($datosNuevos);
            
        if($datosPost['idClientes']>0){
            // Cuando ya existe de modifica
            $modificar =$this->modificarDatosCliente($datosNuevos, $datosPost['idClientes']);
            // Devolvemos el id que devolvimos.
            $respuesta['id'] =  $datosPost['idClientes'];
            if ($modificar <> 1){
              // hubo un error , por lo que lo enviamos 
            $respuesta['error'] = 'Se modifico '.$modificar. ' clientes.';
            }
        }else{
            // Cuando no existe de añade.
            $respuesta['id']=$this->addcliente($datosNuevos);
        }
        // Si hubo error en la consulta 
        if (isset($respuesta['error'])){
            // Comprobar siempre debería devuelver array ..
             $comprobar[] = array(  'tipo'=>"danger",
                                    'mensaje'=>$respuesta['error']
                                );
        }
        // Se devuelve siempre comprobaciones, ya que alguna puede ser necesaria con OK
        // Si no hay nada , ya se manda un array vacio.
        $respuesta['comprobaciones']=$comprobar;

        return $respuesta;
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
    public function comprobarExistenDatos($datos){
		//@ Objetivo:
		//Comprobar cuando guardamos que le nif del cliente no es el mismo que otro cliente
		//@ Parametros:
		//Los datos del cliente
        //@ Devuelve:
        // Siempre devuelve array con datos o sin datos.
        // Indicando tipo error.
		$respuesta=array();

        if (isset($datos['nif']) & $datos['nif']<>''){
            // Solo hacemos la comprobación si trae datos nif
            $sql='select nif , idClientes  FROM clientes where nif="'.$datos['nif'].'"';
            $consulta=$this->consulta($sql);
            if(isset($consulta['error'])){
                $comprobacion = array(  'tipo'=>"danger",
                                        'mensaje'=>$consulta['error']
                                    );
            } else {
                if (isset($consulta['datos'])){  
                    if($consulta['datos']>0){
                        if($consulta['datos'][0]['idClientes'] != $datos['idClientes']){
                            $comprobacion = array(  'tipo'=>"warning",
                                                    'mensaje'=>$consulta
                                                );
                            $respuesta[]= $comprobacion;
                        }   
                    }
                }
            }
        }
        return $respuesta;

	}
  
}


?>
