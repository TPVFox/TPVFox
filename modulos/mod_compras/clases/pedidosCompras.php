<?php 

include_once $URLCom.'/modulos/mod_compras/clases/ClaseCompras.php';

class PedidosCompras extends ClaseCompras{
	private $num_rows; // (array) El numero registros que tiene la tabal pedprot
	public $affected_rows; // Propiedad que guardamos cuando hacemos una consulta, para indicar filas afectadas.
	public function __construct($conexion){
		parent::__construct($conexion);
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM pedprot';
		$respuesta = $this->consulta($sql);
		if (gettype($respuesta)==='object'){
			$this->num_rows = $respuesta->fetch_object()->num_reg;
		} else {
			// Es un array porque hubo un fallo
			echo '<pre>';
			print_r($respuesta);
			echo '</pre>';
		}
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql){
		// Realizamos la consulta.
		$db = $this->db;
		$smt = $db->query($sql);
		if ($smt) {
			$respuesta = $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
		}
        // Guardamos la filas afectadas.
        $this->affected_rows = $db->affected_rows;
        return $respuesta;
	}
	
	
	public function modificarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $numPedidoTemp, $productos){
		// @ Objetivo:
		//Modificar los datos de pedidos temporal cada vez que hacemos agregamos un producto, modificamos una candidad ...
		// @ Parametros:
		//Todos los datos del pedido temporal
		$db = $this->db;
		$productos_json=json_encode($productos);
		$UnicoCampoProductos 	=$productos_json;
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$sql='UPDATE pedprotemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda
		.' , estadoPedPro="'.$estadoPedido.'" , fechaInicio="'.$fecha.'"  ,Productos="'
		.$PrepProductos.'"  WHERE id='.$numPedidoTemp;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function insertarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $productos, $idProveedor){
		//@Objetivo:
		// Insertar un pedido temporal , cuando el pedido temporal no exste lo insertamos
		//@ Parametros:
		// Todos los parametros que tenemos incialmente cuando creamos el pedido temporal
		$db = $this->db;
		$productos_json=json_encode($productos);
		$UnicoCampoProductos 	=$productos_json;
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$sql = 'INSERT INTO pedprotemporales ( idUsuario , idTienda , estadoPedPro , 
		fechaInicio, idProveedor,  Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'
		.$estadoPedido.'" , "'.$fecha.'", '.$idProveedor.' , "'.$PrepProductos.'")';
		//~ $smt = $db->query ($sql);
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			$id=$db->insert_id;
			$respuesta['id']=$id;
			$respuesta['sql']=$sql;
			$respuesta['productos']=$productos;
		}
		return $respuesta;
	}
	public function modTotales($res, $total, $totalivas){
		//@ Objetivo:
		// El objetico principal es que cada vez que modificamos una cantidad o añadimos un producto nuevo , modificar en el pedido temporal los datos de total
		//@ Parametros:
		// $res-> id del pedido temporal
		//$total->El total del pedido
		//$total_ivas->la suma de todos los ivas 
		$db=$this->db;
		$sql='UPDATE pedprotemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res;
		$smt=$db->query($sql);
		$resultado['sql']=$sql;
		return $resultado;
	}
	public function addNumRealTemporal($idTemporal, $idReal){
		// @Objetivo: Si el pedido es modificado en el temporal tenemos que registrar el id del pedido real 
		// @Parametros:
		// $idTemporal-> id del pedido temporal que hemos creado anteriormente
		// $idReal-> id del pedido real que estamos modificando
		$db=$this->db;
		$sql='UPDATE pedprotemporales set idPedpro='.$idReal .'  where id='.$idTemporal;
		$smt=$db->query($sql);
		//~ return $resultado;
	}
	public function modEstadoPedido($idPedido, $estado){
		//@Objetivo: Mofificar el estado del pedido real 
		// @estado :
			//-Facturado: que ese pedido ya está en el albarán
			//-Guardado: que el pedido no tiene ninguna modificación pendiente
			//- Sin guardar : que el pedido tiene un pedido temporal
		//@Parametros : 
		// $idPedido-> id del pedio real
		// $estado-> string del estado
		$db=$this->db;
		$sql='UPDATE pedprot set estado="'.$estado .'"  where id='.$idPedido;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function DatosTemporal($idTemporal){
		// @ Objetivo:
		// Obtener todos los datos de temporal
		// @ Parametros:
		// $idTemporal -> (string) Numero de idTemporal
		$tabla='pedprotemporales';
		$where='id='.$idTemporal;
		$pedido = parent::SelectUnResult($tabla, $where);
		return $pedido;
	}
	public function DatosPedido($idPedido){
		//@Objetivo : Mostrar todo los datos de un pedido de la tabla pedprot
		//@Parametros:
		//idPedido: id del pedido
		$tabla='pedprot';
		$where='id='.$idPedido;
		$pedido = parent::SelectUnResult($tabla, $where);
		return $pedido;
	}
	public function eliminarPedidoTablas($idPedido,$tabla = ''){
		//@Objetivo:
        // Eliminar los registros de las tablas para un idPedido determinado.
        // Se podrán eliminar de las 3 tablas relacionadas con el pedido o solo de la que indiquemos.
		//@Parametros:
			//$idPedido->(int) id del pedido real
            //$tabla -> (string) nombre de la tabla queremos borra los datos del pedido.
        $tablas = array( 'pedprot','pedprolinea','pedproIva')
        $respuesta=array();
        $OK = 'KO';
        if ($tabla !==''){
            // Controlamos que la tabla indicada exista en array
            foreach ($tablas as $t){
                if ($t = $tabla) {
                    $OK ='OK';
                } else {
                    // ELimino de array los nombres tablas que no son .
                    unset($tablas[$t]);
                }
            }
        } else {
            if ($idPedido > 0 ){
                $OK ='OK'; // Eliminamos las tres tablas.
            }
        }
        if ($OK = 'OK'){
            $where = 'where id='.$idPedido;
            foreach($tablas as $tabla){
                $respuesta[$tabla] = parent::deleteRegistrosTabla($tabla,$where='')
            }
        }
		return $respuesta;
	}
	
	public function AddPedidoGuardado($datos){
		//@Objetivo:
        // Guardar todos los datos de un pedido Nuevo , los datos se guardan en tres tablas 
		//@tablas:
		//pedprot->tabla donde se almacenan los pedidos guardados
		//pedprolinea->tabla que contiene las lineas de los productos
		//pedproIva->tabla que contiene los registros de los distintos ivas de los productos
		$db = $this->db;
        $sql='INSERT INTO pedprot ( Numtemp_pedpro, FechaPedido, idTienda, idUsuario, idProveedor, 
        estado, total, fechaCreacion) VALUES ('.$datos['Numtemp_pedpro']
        .', "'.$datos['FechaPedido'].'", '.$datos['idTienda'].' , '
        .$datos['idUsuario'].', '.$datos['idProveedor'].', "'.$datos['estado']
        .'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")';
        $smt=$this->consulta($sql);
        if (gettype($smt)==='array'){
            $respuesta['error']=$smt['error'];
            $respuesta['consulta']=$smt['consulta'];
            
        }else{
            $id=$db->insert_id;
            // Ahora añadimos es valor a campo Numpredpro, aunque los correcto sería
            // obtener el numero que va y continuar el numero siguiente.
            // Aunque en PROVEEDORES no es necesario, ya que no lo obliga en ningun sitio.
            // De momento lo sigo haciendo asi. 
            if (isset($id)){
                $respuesta['id']=$id;
                $sql='UPDATE pedprot set Numpedpro='.$id.' WHERE id='.$id;
                $smt=$this->consulta($sql);
                if (gettype($smt)==='array'){
                    $respuesta['error']=$smt['error'];
                    $respuesta['consulta']=$smt['consulta'];
                }
            }else{
                $respuesta['error']=$smt['error'];
                $respuesta['consulta']=$smt['consulta'];
            }
        }
        
		if (!isset($respuesta['error'])){
            $r =$this->AddRegistrosPedidosLineasIvas($datos,$id);
            array_push($respuesta,$r);
        }
		return $respuesta;
    }

    public function AddRegistrosPedidosLineasIvas($datos,$id){
        //@ Objetivo
        // Es añadir registros a las dos tablas con sus metodos correspondientes.
        //¿ El porque este metodo ?
        // Porque lo utilizamos tanto AddPedidoGuardado como ModPedidoGuardado
        $respuesta = array();
        $productos = $this->AddPedlineaProductos($datos['Productos'],$id);
        if ( $productos['valores_insert']){
            // No hubo error y inserto.
            // Aunque no se al insertar por ejemplo 20 productos y falla en 5 , no indicara que inserto los 5 [testear] 
            $respuesta['productoInsertados'] = $productos['valores_insert'];
        } else {
            $respuesta['error']     =$productos['error'];
            $respuesta['consulta']  =$productos['consulta'];
        }
        $desglose = AddPedproIvaDesglosePedido($datos['DatosTotales']['desglose'],$id)
        if ( $desglose['valores_insert']){
            // No hubo error y inserto.
            // Aunque no se al insertar por ejemplo 20 productos y falla en 5 , no indicara que inserto los 5 [testear] 
            $respuesta['subtotalIvasInsertados'] = $desglose['valores_insert'];
        } else {
            $respuesta['error']     =$desglose['error'];
            $respuesta['consulta']  =$desglose['consulta'];
        }
        return $respuesta;

    }

    public function AddPedlineaProductos($productos,$id){
        // @ Objetivo :
        // Añadir los productos de un pedido a la tabla pedprolinea
        $productos = json_decode($productos, true); 
        $i=1;
        $sql='INSERT INTO  pedprolinea (idpedpro, idArticulo, cref, ref_prov , 
                ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea)';
        $respuesta= array();
        $values = array();
        foreach ( $productos as $prod){
            if ($prod['estado']=='Activo'){
                $codBarras="";
                $refProveedor="";
                if ($prod['ccodbar']){
                    $codBarras=$prod['ccodbar'];
                }
                if ($prod['crefProveedor']){
                    $refProveedor=$prod['crefProveedor'];
                }
                $values[]='('.$id.', '.$prod['idArticulo'].', '
                ."'".$prod['cref']."'".', '
                ."'".$refProveedor."'".
                ', "'.$codBarras.'", "'.$prod['cdetalle'].'", '.$prod['ncant']
                .', "'.$prod['nunidades'].'", "'.$prod['ultimoCoste'].'", '
                .$prod['iva'].', '.$i.', "'.$prod['estado'].'")';
                $i++;
            }
        }
        // Hacemos una sola consulta con inserccion de todos los productos.
        $sql .= ' VALUES '.implode(',',$values);
        $smt=$this->consulta($sql);
        if (gettype($smt)==='array'){
            $respuesta['error']=$smt['error'];
            $respuesta['consulta']=$smt['consulta'];
        } else {
            $respuesta['valores_insert'] = $this->affected_rows;
        }
        return $respuesta;
    }

    public function AddPedproIvaDesglosePedido($desglose,$id) {
        // @ Objetivo:
        // Añadir los subtotales de ivas y bases a pedproIva de un pedido 
        $sql='INSERT INTO pedproIva (idpedpro, iva, importeIva, totalbase) values ';
        $values = array();
        $respuesta= array();
        foreach ($desglose as  $iva => $basesYivas){
            $values[]='('.$id.', "'.$iva.'", "'.$basesYivas['iva'].'", "'.$basesYivas['base'].'")';
        }
        $sql .= ' VALUES '.implode(',',$values);
        // Hacemos una sola consulta con insercion de todos registros subtotales de ivas.
        $smt=$this->consulta($sql);
        if (gettype($smt)==='array'){
            $respuesta['error']=$smt['error'];
            $respuesta['consulta']=$smt['consulta'];
        } else {
            $respuesta['valores_insert'] = $this->affected_rows;
        }
    }
    
    public function ModPedidoGuardado($datos){
		//@Objetivo:
        // Guardar los datos de un pedido existente
		//@tablas:
		//pedprot->tabla donde se almacenan los pedidos guardados
		//pedprolinea->tabla que contiene las lineas de los productos
		//pedproIva->tabla que contiene los registros de los distintos ivas de los productos
		$db = $this->db;
        $id =  = $datos['idPedpro'];
        $sql='UPDATE pedprot SET Numtemp_pedpro='.$datos['Numtemp_pedpro']
            .', FechaPedido ="'..$datos['FechaPedido'].'"'
            .', estado ="'.$datos['estado'].'"'
            .', total="'.$datos['total'].'"'
            .', modify_by="'.$datos['idUsuario'].'"'
            .', fechaModificacion=NOW'
            .'  WHERE id = '.$id
        $smt=$this->consulta($sql);
        if (gettype($smt)==='array'){
            $respuesta['error']=$smt['error'];
            $respuesta['consulta']=$smt['consulta'];
        } else {
            $respuesta['update_modificado'] = $this->affected_rows;
        }
        // Ahora tengo borrar los datos las tablas pedprolinea y tabla PedproIva, para
        // luego insertar los nuevos.
        $r = $this->eliminarPedidoTablas($id,'pedprolinea');
        array_push($respuesta,$r);
        $r = $this->eliminarPedidoTablas($id,'pedproIva');
        array_push($respuesta,$r);
        if (!isset($respuesta['error'])){
            $r =$this->AddRegistrosPedidosLineasIvas($datos,$id);
            array_push($respuesta,$r);
        }
        return $respuesta;
    }

	public function eliminarTemporal($idTemporal, $idPedido =0){
		//@Objetivo :
        // Eliminar temporal, tanto si recibe idTemporal o idPedido
		$db=$this->db;
		if ($idPedido>0){
			$sql='DELETE FROM pedprotemporales WHERE idPedpro='.$idPedido;
		}else{
			$sql='DELETE FROM pedprotemporales WHERE id='.$idTemporal;
		}
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
		
	}
	public function TodosTemporal($idPedpro = 0){
        // @ Objetivo:
        // Obtener los pedidostemporales (todos) o de un pedido de terminado, ya que puede suceder que
        // se este editando el mismo pedido a la vez.
        $respuesta = array();
        $db = $this->db;
		$Sql= 'SELECT tem.idPedpro, tem.id , tem.idProveedor, tem.total, b.nombrecomercial, 
		c.Numpedpro from pedprotemporales as tem left JOIN proveedores as b on 
		tem.idProveedor=b.idProveedor left JOIN pedprot as c on tem.idPedpro=c.id';
        if ($idPedpro > 0){
            // buscamos solos temporales para ese pedido.
            $Sql .= ' where tem.idPedpro='.$idPedpro;
        }
		$smt=$this->consulta($Sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			while ( $result = $smt->fetch_assoc () ) {
				array_push($respuesta,$result);
			}
		}
		return $respuesta;
	}
	
	
	public function TodosPedidosLimite($limite = ''){
		//MUestra todos los pedidos dependiendo del límite que tengamos en listado pedidos
		$db	=$this->db;
		$sql = 'SELECT a.id , a.Numpedpro , a.FechaPedido, b.nombrecomercial, 
		a.total, a.estado FROM `pedprot` as a LEFT JOIN proveedores as b on 
		a.idProveedor=b.idProveedor   '. $limite ;
		$smt=$this->consulta($sql);
		$pedidosPrincipal=array();
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);
			}
            $respuesta = array();
			$respuesta['Items'] = $pedidosPrincipal;
			$respuesta['consulta'] = $sql;
			$respuesta['limite']=$limite;
		}
		return $respuesta;
	}
	
	public function sumarIva($idpedpro){
		//Función para sumar los ivas de un pedido
		$from_where= 'from pedproIva where idpedpro ='.$idpedpro;
		$pedido = parent::sumarIvaBases($from_where);
		return $pedido;
	}
	
	public function ProductosPedidos($idPedido){
        //@Objetivo:
        //Buscar todos los productos que tenga un id de pedido real
        //@Parametros :
        //idPedido-> id del pedido real
		$tabla='pedprolinea';
		$where='idpedpro= '.$idPedido;
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}
	//Muestra los ivas de un pedido
	public function IvasPedidos($idPedido){
		//@Objetivo:
		//Extraer todos los ivas que tengamos de un pedido ya guardado
		//@Parametros: 
		//idPedido->id del pedido guardado
		$tabla='pedproIva';
		$where='idpedpro= '.$idPedido;
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}
	
	public function pedidosProveedorGuardado($idProveedor, $estado){
		//@Objetivo:
		//Mostrar datos de los pedidos de un proveedor según el estado para mostrar en albaranes
		//@parametros:
		//idProveedor: id del proveedor
		//estado: estado del que queremos buscar los datos 
		$tabla='pedprot';
		$where='idProveedor= '.$idProveedor.' and estado='."'".$estado."'";
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}
	
	public function buscarPedidoProveedorGuardado($idProveedor, $numPedido, $estado){
		$db=$this->db;
		if ($numPedido>0){
			$sql='SELECT Numpedpro, FechaPedido, total, id FROM pedprot 
			WHERE idProveedor= '.$idProveedor.' and estado='."'".$estado."'"
			.' and Numpedpro='.$numPedido;
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$pedido['error']=$smt['error'];
				$pedido['consulta']=$smt['consulta'];
			}else{
				$pedidosPrincipal=array();
				if ($result = $smt->fetch_assoc () ){
					$pedido=$result;
				}
				$pedido['Nitem']=1; // No lo entiendo , y si la consulta obtiene mas.
			}
		}else{
			$sql='SELECT Numpedpro, FechaPedido, total, id FROM pedprot
			 WHERE idProveedor= '.$idProveedor.'  and estado='."'".$estado."'";
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$pedido['error']=$smt['error'];
				$pedido['consulta']=$smt['consulta'];
			}else{
				$pedidosPrincipal=array();
				while ( $result = $smt->fetch_assoc () ) {
					array_push($pedidosPrincipal,$result);	
				}
				$pedido['datos']=$pedidosPrincipal;
			}
		}
		
		
		return $pedido;
	}
	public function modFechaPedido($fecha, $idPedido){
		$db=$this->db;
		$sql='UPDATE pedprot SET FechaPedido= "'.$fecha.'" where id='.$idPedido;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
		
	}
	public function guardarPedido(){
        // @ Objetivo:
        // Comprobar que los datos enviados por $_POST son correcto y guardar el pedido.
        // Si no fueran correctos, se devuelve un error.
        $errores    = array();
        $respuesta  = array();
        // Como tengo cargado inicial.. tengo tomar datos de session-
        $Tienda = $_SESSION['tiendaTpv'];
        $Usuario = $_SESSION['usuarioTpv'];
        if (!isset($Tienda['idTienda']) || !isset($Usuario['id'])){
			$errores[]=array ( 'tipo'=>'Danger!',
                                'dato' => '',
                                'class'=>'alert alert-danger',
                                'mensaje' => 'ERROR NO HAY DATOS DE SESIÓN!'
                             );
        }
        // Inicializo variables.
        $fecha=date('Y-m-d');
        $fechaCreacion=date("Y-m-d H:i:s");
        $idPedido=0;
        // Posibles $datosPost[estado] -> Nuevo,Sin Guardar,Guardado,Facturado.
        if ($_POST['estado'] === 'Facturado'){
            // Aunque nunca debería llegar aquí si esta facturado, lo controlo por acaso.
            // Si el estado es Facturado , no permito guardar , ni cambiar nada.
            $errores[]=array ( 'tipo'=>'Danger!',
                                'dato' => '',
                                'class'=>'alert alert-danger',
                                'mensaje' => 'No se permite modificar un pedido en estado Facturado.'
                             );
        }
        $OK ='KO';
        if (isset($_GET['tActual']) && isset($_POST['idTemporal'])){
            // Tiene que existir los dos valores.
            if ($_GET['tActual'] === $_POST['idTemporal']){
                // No se permite guardar ya que GET no tiene el numero de idtemporal.
                $OK ='=OK';
            }
        }
        if ($OK === 'KO') {
            // Los idtemporales algo esta mal.
            $errores[]=array ( 'tipo'=>'Warning!',
                     'dato' => '',
                     'class'=>'alert alert-warning',
                     'mensaje' => 'Algo salio mal con el ID de temporal, ya que no coincide get con post !!'
                     );
        }

        // Obtenemos los datos de pedido temporal.
        $pedidoTemporal=$this->DatosTemporal($_POST['idTemporal']);
        if (isset($pedidoTemporal['error'])){
                $errores[]=array ( 'tipo'=>'Danger!',
                         'dato' => $pedidoTemporal['consulta'],
                         'class'=>'alert alert-danger',
                         'mensaje' => 'Error de SQL:  !'
                         );
        } 
        // Comprobamos que tenga productos el temporal.
        if (!isset ($pedidoTemporal['Productos'])){
            $errores[]=array ( 'tipo'=>'Warning!',
                         'dato' => json_encode($pedidoTemporal),
                         'class'=>'alert alert-warning',
                         'mensaje' => 'No existen productos para el recalculo de precios!'
                         );
        }
        if (count($errores) == 0){
            // Solo continuo si no hay errores.
            $fecha = date_format(date_create($_POST['fecha']), 'Y-m-d');
            $productos_json=$pedidoTemporal['Productos'];
            $productos = json_decode($pedidoTemporal['Productos']);
            if (count($productos)>0){
                $CalculoTotales = parent::recalculoTotales($productos);
                $total=round($CalculoTotales['total'],2);
            }
            // Creamos array con los datos del pedido para AÑADIR O MODIFICAR
            $datosPedido=array(
                        'Numtemp_pedpro'=>$_POST['idTemporal'],
                        'FechaPedido'=>$fecha,
                        'idTienda'=>$Tienda['idTienda'],
                        'idUsuario'=>$Usuario['id'],
                        'idProveedor'=>$pedidoTemporal['idProveedor'],
                        'estado'=>"Guardado",
                        'total'=>$total,
                        'fechaCreacion'=>$fechaCreacion,
                        'Productos'=>$productos_json,
                        'DatosTotales'=>$CalculoTotales
                        );
            // Ahora comprobamos necesitamos hacer insert o update.
            if ($_POST['estado'] === 'Nuevo'){
                // Si es nuevo son los dos posibles estado que puede que tengamos insertar
                // Ahora compruebo que no tenga numero de pedido el pedidoTemporal
                if ($pedidoTemporal['idPedpro'] === NULL){
                    // No existe numero pedido, por lo que podemos insetar.
                    $addNuevo=$this->AddPedidoGuardado($datosPedido);
                    if (isset($addNuevo['error'])){
                        $errores[]=array ( 'tipo'=>'Danger!',
                                'dato' => $addNuevo['consulta'],
                                'class'=>'alert alert-danger',
                                'mensaje' => 'No se ha podido crear nuevo pedido.Error de SQL:  '
                            );
                    } else {
                        // Deberíamos recibir el numero de pedido creado
                        if(isset($addNuevo['id'])){
                            $respuesta['id_guardo'] = $addNuevo['id']; 
                            $eliminarTemporal=$this->eliminarTemporal($_POST['idTemporal'], $idPedido);
                            if (isset($eliminarTemporal['error'])){
                                $errores[]=array ( 'tipo'=>'Danger!',
                                'dato' => $eliminarTemporal['consulta'],
                                'class'=>'alert alert-danger',
                                'mensaje' => 'No se puedo eliminar el temporal.Error de SQL:  '
                                );
                            }
                        } else {
                            // Algo paso ya que no se obtuvo el id, pero la consulta fue correcta.
                            $errores[]=array ( 'tipo'=>'Danger!',
                                'dato' => $eliminarTemporal['consulta'],
                                'class'=>'alert alert-danger',
                                'mensaje' => 'No se puedo eliminar porque no recibe id Nuevo pedido.Error de SQL:  '
                                );

                        }
                    }
                }
            }
            // Si su estado es "Sin Guardar" entonces se modifica.
            if ($_POST['estado'] === 'Sin Guardar'){
                if ($pedidoTemporal['idPedpro'] >0){
                    $datosPedido['idPeppro'] = $pedidoTemporal['idPedpro'];
                    $modPedido=$this->ModificarPedidoGuardado($datosPedido);
                }
            }
            
        }
        $respuesta['errores'] = $errores; // Se devuelve array vacio si no hay errores.
        return $respuesta;

    }

?>
