<?php 

include_once $URLCom.'/clases/ClaseTFModelo.php';

class ClaseProveedor extends TFModelo{

    public $arrayProveedor = array( 'nombrecomercial'   =>'',
                                    'razonsocial'       =>'',
                                    'nif'               =>'',
                                    'direccion'         =>'',
                                    'telefono'          =>'',
                                    'fax'               =>'',
                                    'movil'             =>'',
                                    'email'             =>'',
                                    'estado'            =>''
                                );

    public function obtenerProveedores($filtro) {
        // Function para obtener proveedores y listarlos
        //tener en cuenta el  paginado con parametros:  ,$filtro
        $proveedores = array();
        $sql = "Select * from proveedores ".$filtro; 
        $proveedores = $this->consulta($sql);
        return $proveedores['datos'];
    }

    public function contarRegistros($filtro='') {
        $proveedores = $this->obtenerProveedores($filtro);
        return count($proveedores);
    }


    public function getProveedor($id){
		//@Objetivo: cargar todos los datos de un proveedor 
		//@Parametros: 
		//id: id del proveedor
		$sql='SELECT * from proveedores where idProveedor='.$id;
		return $this->consulta($sql);
	}

    public function getProveedorCompleto($id){
        // @ Objetivo:
        // Obtener los datos de un cliente completo:
        //   - Tabla Cliente.
        //   - Obtener de ese cliente los ultimos 15 movimientos, le llamo adjuntos
        //          -Tickets
        //          -Albaranes
        //          -Facturas
        //          -Pedidos.
        if ($id > 0) {
            $ProveedorUnico =$this->getProveedor($id);
            if (!isset($ProveedorUnico['error'])){
                if (count($ProveedorUnico['datos']) >1){
                    // Hay mas de uno por lo que hay error grabe.
                    $mensaje='Para este id:'.$id.' se encontrado '.count($ProveedorUnico['datos']).' registros';
                    $ProveedorUnico['error'] =$this->montarAdvertencia('danger',$mensaje);

                } else {
                    if (count($ProveedorUnico['datos'])===1){
                        $ProveedorUnico=$ProveedorUnico['datos'][0];
                        $ProveedorUnico['adjuntos']=$this->adjuntosProveedor($id);
                    } else {
                        // No hay datos para ese id, por lo que debemos informar
                        error_log(json_encode($ClienteUnico));
                        $mensaje = 'Para este id:'.$id.' no hay datos';
                        $ProveedorUnico['error'] =$this->montarAdvertencia('danger',$mensaje);

                    }
                }
            }
        } else {
            // Debe ser nuevo porque id es 0
            $ProveedorUnico = $this->arrayProveedor;
            // Tambien devolvemos los vacios de los adjuntos.
            foreach ($this->adjuntos as $key=>$adjunto){
                $ProveedorUnico['adjuntos'][$key]['datos'] = array();
            }
        }
        return $ProveedorUnico;

    }
	
	public function getFacturas($id){
		//@Objetivo: cargar todas las facturas de compras de un proveedor determinado
		//@Parametros:
		//id: id del proveedor
		$sql='SELECT Numfacpro  as num, Fecha as fecha, total, id , idProveedor FROM facprot WHERE idProveedor='.$id.' order by Numfacpro desc limit 0,15';
		return $this->consulta($sql);
	}
	
	public function getAlbaranes($id){
		//@OBjetivo: cargar todos los albaranes de compras de un proveedor 
		//@Prametros: 
		//id : id del proveedor
		$sql='SELECT Numalbpro as num, Fecha as fecha, total , id , idProveedor  FROM albprot WHERE idProveedor='.$id.' order by Numalbpro desc limit 0,15';
		return $this->consulta($sql);
	}
	
	public function getPedidos($id){
		//@Objetivo: cargar todos los pedidos de compras de un proveedor
		//@Parametros:
		//id: id del proveedor
		$sql='SELECT Numpedpro as num, FechaPedido as fecha , total, id, idProveedor  FROM pedprot WHERE idProveedor='.$id.' order by Numpedpro desc limit 0,15';
		return $this->consulta($sql);
	}
	public function adjuntosProveedor($id){
		//@Objetivo: 
		//Cargar todos los adjunto de un cliente , tickets, facturas, albaranes y pedidos
		//@Parametros:
		//id-> id del cliente

        // Obtenemos los adjuntos, si hay error devuelve array[error] ,si tene datos array['datos']

		$adjuntos=array();
		$adjuntos['facturas']=$this->getFacturas($id);
		$adjuntos['albaranes']=$this->getAlbaranes($id);
		$adjuntos['pedidos']=$this->getPedidos($id);
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
	public function modificarDatosProveedor($datos){
		//@Objetivo: modificar los datos de un proveedor determinado
		//@Paramatros: 
		//datos: datos a modificar del provvedor 
		$sql='UPDATE `proveedores` SET `nombrecomercial`="'.$datos['nombrecomercial'].'",
		`razonsocial`="'.$datos['razonsocial'].'",`nif`="'.$datos['nif'].'",`direccion`="'.$datos['direccion'].'",
		`telefono`="'.$datos['telefono'].'",`fax`="'.$datos['fax'].'",`movil`="'.$datos['movil'].'",
		`email`="'.$datos['email'].'",`estado`="'.$datos['estado'].'" WHERE idProveedor='.$datos['idProveedor'];
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			$respuesta['error']= $consulta;
		} else {
             // Fue bien , devolvemos la cantidad de filas modificadas.
             $respuesta = ModeloP::$db->affected_rows;
        }
	}
	public function addProveedorNuevo($datos){
		//@Objetivo:
		//Añadir un proveedor nuevo
		//Parametros: 
		//datos del proveedor 
		$sql='INSERT INTO `proveedores`( `nombrecomercial`, `razonsocial`, 
		`nif`, `direccion`, `telefono`, `fax`, `movil`, `email`, `fecha_creado`, 
		`estado`) VALUES ("'.$datos['nombrecomercial'].'","'.$datos['razonsocial'].'",
		"'.$datos['nif'].'","'.$datos['direccion'].'","'.$datos['telefono'].'","'.$datos['fax'].'",
		"'.$datos['movil'].'","'.$datos['email'].'",NOW() , "'.$datos['estado'].'" )';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			$respuesta['error']= $consulta;
		} else {
             // Fue bien , devolvemos la cantidad de filas modificadas.
             $respuesta = ModeloP::$db->affected_rows;
        }
        return $respuesta;
	}
	

	public function albaranesProveedoresFechas($idProveedor, $fechaIni, $fechaFin){
		$respuesta=array();
		$productos=array();
		$resumenBases=array();
        $sql='SELECT Numalbpro , id FROM albprot WHERE  idProveedor ='.$idProveedor;
		if(!$fechaIni=="" & !$fechaFin==""){
			$sql='SELECT Numalbpro , id FROM albprot WHERE idProveedor ='.$idProveedor.' and `Fecha` BETWEEN 
		 "'.$fechaIni.'" and  "'.$fechaFin.'"';
		}
		$albaranes=$this->consulta($sql);
		if(isset($albaranes['error'])){
			$respuesta=$albaranes;
		}else{
			$ids=implode(', ', array_column($albaranes['datos'], 'id'));
			if($ids==0){
                $respuesta['error']=1;
                $respuesta['consulta']='No hay resumen entre las fechas seleccionadas';
            }else{
                $sql='SELECT	*,	SUM(nunidades) as totalUnidades	FROM	`albprolinea`	WHERE idalbpro  IN('.$ids.') and 
                `estadoLinea` <> "Eliminado" GROUP BY idArticulo + costeSiva';
                
                $productos=$this->consulta($sql);
                if(isset($albaranes['error'])){
                    $respuesta=$productos;
                }else{
                    $respuesta['productos']=$productos['datos'];
                }
                $sql='SELECT i.* , t.idTienda, t.idUsuario, sum(i.totalbase) as sumabase , sum(i.importeIva) 
                as sumarIva, t.Fecha as fecha   from albproIva as i  
                left JOIN albprot as t on t.id=i.idalbpro   where idalbpro  
                in ('.$ids.')  GROUP BY idalbpro ;';
                $resumenBases=$this->consulta($sql);
                if(isset($resumenBases['error'])){
                    $respuesta=$resumenBases;
                }else{
                    $respuesta['resumenBases']=$resumenBases['datos'];
                }
                $sql='SELECT *, sum(importeIva) as sumiva , sum(totalbase) as sumBase from albproIva where idalbpro 
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


    public function guardarProveedor($datosPost){
        // @ Objetivo:
        // Añadir un proveedor o modificar los datos de uno.
        // @ Parametros:
        //  Array post con los datos para añadir o modificar.
        // @ Devuelve:
        // Array con :
        //     ['id'] el id del proveedor nuevo o modificado, puede que no lo devuelva nada si hay error al crear nuevo.
        //     ['comprobaciones'] -> Advertencias de la comprobaciones.
        $respuesta = array();
        $errores   = array();
        $datosNuevos = $this->arrayProveedor;
        foreach ($datosPost as $key=>$datos){
            $datosNuevos[$key]=$datos;
        }
        // Ahora se debería validar los datos.
                   
        $comprobar=$this->validarDatos($datosNuevos);
        error_log(json_encode($comprobar));
       
        if (!isset($comprobar['errores'])){    
            if($datosPost['idProveedor']>0){
                // Cuando ya existe de modifica
                $modificar= $this->modificarDatosProveedor($datosPost);
                $respuesta['id'] =  $datosPost['idProveedor'];
                if ($modificar <> 1){
                    // Si se modifico mas uno o 0 es un error o una advertencia tener un cuenta. 
                    $respuesta['error'] = 'Hubo un error al modificiar, ya se modifico '.$modificar. ' Proveedores';
                }
            }else{
                $respuesta['id']=$this->addProveedorNuevo($datosPost);
            }
        } else {
            $errores = $comprobar['errores'];
        }

        if (isset($respuesta['error'])){
                // Comprobar siempre debería devuelver array ..
                 $errores[] = array(  'tipo'=>"danger",
                                        'mensaje'=>$respuesta['error']
                                    );
        }
        
        if (count($errores)>0){
            $comprobar['comprobaciones']= $errores;
        }
        // Se devuelve siempre comprobaciones, ya que alguna puede ser necesaria con OK
        // Si no hay nada , ya se manda un array vacio.
        $respuesta['comprobaciones']=$comprobar['comprobaciones'];
        
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

    public function validarDatos($datos){
        // @ Objetivo:
        // Hacer las validaciones de los campos correpondientes antes de guardar o modificar.
        // @ Parametros:
        //  $datos -> Array de campos a validar.
        // @ Devuelve:
        // Un array con:
        //    ['datos']         -> Siempre devuelve array con datos o vacio.
        //    ['errores']       -> No siempre lo tiene que devolver, solo si queremos indicar errores graves.
        //    ['comprobaciones']-> Son advertencia que permitimos continuar, pero queremos indicarlos.
        $respuesta=array();
        // Eliminamos el campo de guardar.
        unset($datos['Guardar']);
        // Si existe tb eliminamos el campo Resumen que envia el post.
        if (isset($datos['Resumen'])){
            unset($datos['Resumen']);
        }
        $comprobar = $this->comprobarExisteValorCampo($datos,$datos['idProveedor']);
        foreach ($comprobar as $campo =>$c){
            if (isset($c['error'])){
                // Hubo uno o varios errores en la consulta, es un error grave.
                $respuesta['errores'][] =$c['error'];
            } else{
                // Ahora validar aquí.. es decir si existe alguno repetido cual permitimos y cual no.
        
                if ($campo === 'nif'){
                    // Si existe el nif ( no esta vacio) no permito continuar grabando/modificando
                    if ($c<>'' && count($c['datos'])>0){
                        error_log('Nif y en el campo '.$campo.' entro');
                        $respuesta['errores'][] = $this-> montarAdvertencia('danger','Existe proveedor con nif');
                    }
                }
            }

        }
        return $respuesta;
        
	}

    public function comprobarExisteValorCampo($campos,$id_descarte=0){
        // @Objetivo
        // Comprobar varios campos ya existe con el mismo valor en algun registros de la tabla cliente.
        // Cuando estamos modificamos tenemos que enviar el id_descarte para que no lo devuelva, ya que
        // no queremos que lo haga, seguramente.
        //
        // @Parametros
        //   $campos-> Array de arrays [0]{ "nombre_campo' => 'valor_campo'}
        //                             ...               
        //   $id_descarte => (int) Id del cliente que no queremos cuente
        //
        // @Devolvemos
        // Array => NombreCampo => Array ( 'datos'=>  Siempre se devuelve aunque sea  vacio,
        //                                 'error'=> En caso de que la consulta de un error.
        $respuesta = array();
        foreach ($campos as $nombre_campo=>$valor_campo){
            $sql='SELECT  idProveedor FROM proveedores where '.$nombre_campo.'="'.$valor_campo.'"';
            $consulta=$this->consulta($sql);

            if(isset($consulta['error'])){
                $respuesta[$nombre_campo]['error'] = array(  'tipo'=>"danger",
                                        'mensaje'=>$consulta['error']
                                    );
            } else {
                if (isset($consulta['datos'])){
                    if ($id_descarte>0){
                       if(count($consulta['datos'])>0){
                            foreach ($consulta['datos'] as $key=>$dato){
                                if($dato['idProveedor'] != $id_descarte){
                                    // Eliminamos array este resultado.
                                    unset($consulta['datos'][$key]);
                                }
                            }
                        } else {
                            // No tiene mas que uno comprobamos si es el mismo.
                            if($consulta['datos'][0]['idProveedor'] != $id_descarte){
                                // Eliminamos array este resultado.
                                unset($consulta['datos'][0]);
                            }
                            
                        }
                    }
                }
            }
            if (!isset($consulta['datos'])) {
                $consulta['datos']= array();
            }
            $respuesta[$nombre_campo]['datos']= $consulta['datos'];

        }
        return $respuesta;
    }
    
}

?>
