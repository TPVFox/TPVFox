<?php 

include_once $URLCom.'/clases/ClaseTFModelo.php';

class ClaseProveedor extends TFModelo{
    public $adjuntos = array('facturas' => array('datos' =>array()),
                             'albaranes'=> array('datos' =>array()),
                             'pedidos'  => array('datos' =>array())
                             );  // Los ultimos 15 movimientos de ese proveedor.
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

    public function obtenerProveedores($filtro='') {
        // Function para obtener proveedores y listarlos
        //tener en cuenta el  paginado con parametros:  ,$filtro
        $sql = "Select * from proveedores ".$filtro; 
        $proveedores = $this->consulta($sql);
        if (!isset($proveedores['datos'])){
            $proveedores['datos']=array(); // mandamos array vacio.
        };
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
                        error_log('Error al intentar obtener este proveedor:'.json_encode($ProveedorUnico));
                        $mensaje = 'Para este id:'.$id.' no hay datos';
                        $ProveedorUnico['error'] =$this->montarAdvertencia('danger',$mensaje);

                    }
                }
            }
        } else {
            // Debe ser nuevo porque id es 0
            $ProveedorUnico = $this->arrayProveedor;
            // Tambien devolvemos los vacios de los adjuntos.
            $ProveedorUnico['adjuntos']=$this->adjuntos;
            
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
		$sql='SELECT Numpedpro as num, Fecha as fecha , total, id, idProveedor  FROM pedprot WHERE idProveedor='.$id.' order by Numpedpro desc limit 0,15';
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
		$sql='UPDATE proveedores SET nombrecomercial="'.$datos['nombrecomercial'].'",
		razonsocial="'.$datos['razonsocial'].'",nif="'.$datos['nif'].'",direccion="'.$datos['direccion'].'",
		telefono="'.$datos['telefono'].'",fax="'.$datos['fax'].'",movil="'.$datos['movil'].'",
		email="'.$datos['email'].'",estado="'.$datos['estado'].'" WHERE idProveedor='.$datos['idProveedor'];
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
             $respuesta = ModeloP::$db->insert_id;
        }
        return $respuesta;
	}
	

	public function albaranesProveedoresFechas($idProveedor, $fechaIni, $fechaFin){
        // @ Objetivo:
        // Obtener los albaranes de un proveedor en un intervalo de tiempo.
		$respuesta=array();
		$productos=array();
		$resumenBases=array();
        $sql='SELECT Numalbpro , id FROM albprot WHERE  idProveedor ='.$idProveedor;
        $and= '';
		if(!$fechaIni=="" & !$fechaFin==""){
			$and =' and `Fecha` BETWEEN "'.$fechaIni.'" and  "'.$fechaFin.'"';
		}
        $sql = $sql.$and;
		$albaranes=$this->consulta($sql);
		if(isset($albaranes['error'])){
			$respuesta=$albaranes;
		}else{
            $ids= 0;
            if (isset($albaranes['datos'])){
			    $ids=implode(', ', array_column($albaranes['datos'], 'id'));
            }
            if($ids==0){
                
                $respuesta['error'][]= $this->montarAdvertencia('warning','No hay albaranes para ese proveedor entre las fechas seleccionadas');
            }else{
                $sql='SELECT idalbpro, idArticulo, costeSiva ,SUM(nunidades) as totalUnidades FROM `albprolinea` WHERE idalbpro  IN('.$ids.') and 
                `estadoLinea` <> "Eliminado" GROUP BY idalbpro,idArticulo,costeSiva';
                $productos=$this->consulta($sql);
                if(isset($albaranes['error'])){
                    $respuesta['error'][]= $this->montarAdvertencia('danner','Error al obtener los albaranes encontrados.<br/>'
                                                                            .'Error:'.$albaranes['error'].'<br/>'
                                                                            .'Consulta:'.$albaranes['consulta'],'<br/>');
                }else{
                    $respuesta['productos']=$productos['datos'];
                }
                $sql='SELECT i.* , t.Su_numero,t.idTienda, t.estado, t.idUsuario, sum(i.totalbase) as sumabase , sum(i.importeIva) 
                as sumarIva, t.Fecha as fecha   from albproIva as i  
                left JOIN albprot as t on t.id=i.idalbpro   where idalbpro  
                in ('.$ids.')  GROUP BY idalbpro ORDER BY fecha;';

                $resumenBases=$this->consulta($sql);
                if(isset($resumenBases['error'])){
                     $respuesta['error'][]= $this->montarAdvertencia('danner','Error al obtener el resumen de las bases de albaranes encontrados.<br/>'
                                                                            .'Error:'.$resumenBases['error'].'<br/>'
                                                                            .'Consulta:'.$resumenBases['consulta'],'<br/>');
                }else{
                    $respuesta['resumenBases']=$resumenBases['datos'];
                }
                $sql='SELECT *, sum(importeIva) as sumiva , sum(totalbase) as sumBase from albproIva where idalbpro 
                in ('.$ids.')  GROUP BY iva;';
                $desglose=$this->consulta($sql);
                if(isset($desglose['error'])){
                    $respuesta['error'][]= $this->montarAdvertencia('danner','Error al obtener el desglose ivas de albaranes encontrados.<br/>'
                                                                            .'Error:'.$desglose['error'].'<br/>'
                                                                            .'Consulta:'.$desglose['consulta'],'<br/>');
                }else{
                    $respuesta['desglose']=$desglose['datos'];
                }
            }
        }
		return $respuesta;
	}


    public function SumaLineasAlbaranesProveedores($LineasProductos,$quitarIdArticulo = 'OK') {
        // @ Objetivo
        // Obtener un array con la suma de productos comprados con su precio coste medio de unos albaranes determinado.
        // @ Parametros:
        // $LineasProductos -> Es un array que tiene que trae :
        //          - idArticulo
        //          - costeSiva
        //          - totalUnidades
        // $quitarIdArticulo -> (string)- >'OK  para indicar si devolvemos array con key = IdArticulo o 'KO' pone autonumerico.
        // @ Devolvemos:
        //  El mismo array , cambiando:
        //       costeSiva=  cambia por coste medio de todas lineas del mismo producto.
        //       totalUnidades = cambia por la suma de todas la cantidad unidades de todas las lineas
        //  y añadiendo:
        //       num_comprados = Indica la cantidad lineas que había de ese mismo producto.
        //       coste_medio = Es un string que con 'KO' o 'OK' que indica si se calculo coste medio o no.
       
        $totalProductos=0;
        $totalLineas = 0;

        $Productos = []; // inicializa tabla que aparece como resumen productos
        foreach ($LineasProductos as $producto) {			
            $id_producto = $producto['idArticulo'];
            if(array_key_exists($id_producto, $Productos) == false){ // busca el indice. Si no existe lo crea con $producto
                $Productos[$id_producto] = $producto;
                $Productos[$id_producto]['costeSiva'] = $producto['costeSiva'];
                $Productos[$id_producto]['coste_medio'] = 'KO';
                $Productos[$id_producto]['totalUnidades'] = $producto['totalUnidades'];
                $Productos[$id_producto]['num_compras'] = 1;
            } else {  // Si ya existe suma las unidades y calcula el precio medio
                $total_producto = $producto['totalUnidades'] * $producto['costeSiva'];  
                if($Productos[$id_producto]['costeSiva'] !== $producto['costeSiva']){
                    $Productos[$id_producto]['coste_medio'] = 'OK';
                    $suma = $Productos[$id_producto]['totalUnidades'] + $producto['totalUnidades'];
                    if ( $suma != 0){
                        $Productos[$id_producto]['costeSiva'] = ($Productos[$id_producto]['total_linea'] + $total_producto) / $suma;
                    }
                }				
                $Productos[$id_producto]['totalUnidades'] += $producto['totalUnidades'];
                $Productos[$id_producto]['num_compras'] += 1;
            }
            $Productos[$id_producto]['total_linea'] = $Productos[$id_producto]['totalUnidades'] * $Productos[$id_producto]['costeSiva'];
        }
        // Una vez terminado, Volvemos a recorrer el array para quitar indice que pusimos como el idArticulo,
        // esto podría se opcional, ya que si queremos utilizar el array para añadir mas datos, puede ser interesante 
        // poder recibirlo asi , o no.
        $respuesta = [];
        if ($quitarIdArticulo == 'OK'){
            foreach ($Productos  as $producto){
                $respuesta[] = $producto;
            }
        } else {
            $respuesta = $Productos;
        }
        return $respuesta;

    }



    public function guardarProveedor($datosPost){
        // @ Objetivo:
        // Añadir un proveedor o modificar los datos.
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
        $errores   = array();
         // Eliminamos el campos de btn de ficha.
        $datosPost = $this->eliminarBtnFichaPost($datosPost);
        // Creamos array con todos los datos, ya que puede que no vengan todos.
        $datosNuevos = $this->arrayProveedor;
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
            if($datosPost['idProveedor']>0){
                // Cuando ya existe se modifica
                $modificar= $this->modificarDatosProveedor($datosPost);

                if (isset($modificar['error'])){
                    // Hubo error grave.
                    $respuesta['error'] = $modificar['error'];
                } else {
                    if ($modificar <> 1){
                        // Si se modifico mas uno o 0 es un error grave. 
                        $respuesta['error'] = 'Se ha modificado los datos de '.$modificar.' proveedores.[ERROR:proveedor 1]';
                    } else {
                        $respuesta['datos'] = $this->getProveedorCompleto( $datosPost['idProveedor']);
                        $respuesta['estado'] = 'OK';
                    }
                }
            }else{
                $guardar= $this->addProveedorNuevo($datosPost);
                if (isset($modificar['error'])){
                    // Hubo error grave.
                    $respuesta['error'] = $modificar['error'];
                } else {
                    $respuesta['datos'] = $this->getProveedorCompleto( $guardar);
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
        // Hacer las validaciones y comprobaciones de los campos antes de guardar o modificar.
        // @ Parametros:
        //  $datos -> Array de campos a validar.
        // @ Devuelve:
        // Un array con:
        //    ['datos']         -> Siempre devuelve array con datos o vacio.
        //    ['errores']       -> No siempre lo tiene que devolver, solo si queremos indicar errores graves.
        //    ['comprobaciones']-> Siempre lo devolvemos , aunque se vacio, montamos warning
        $respuesta=array();
        $comprobaciones = array();
        $generales = array('tabla' => 'proveedores','campo_obtener' => 'idProveedor','limite' => 15);
        $descarte = array ('idProveedor' =>$datos['idProveedor']);
        $comprobar = $this->comprobarExisteValorCampo($generales,$datos,$descarte);
        foreach ($comprobar as $campo =>$c){
            if (isset($c['error'])){
                // Hubo uno o varios errores en la consulta, es un error grave.
                $respuesta['errores'][] =$c['error'];
            } else{
                // Validamos aquí los campos que tienen valor y encontro registros iguales.
                if ($datos[$campo]<>'' && count($c['datos'])>0){
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
        if ($datos['nombrecomercial']==='' && $datos['razonsocial']===''){
            // Damos error ya que no tiene sentido no cubrir cualquiera de estos campos.
            $respuesta['errores'][] = $this-> montarAdvertencia('danger','Debe poner nombre comercial o razon social');

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
    
}

?>
