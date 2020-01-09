<?php 

include_once $URLCom.'/modulos/mod_compras/clases/ClaseCompras.php';

class PedidosCompras extends ClaseCompras{
	private $num_rows; // (array) El numero registros que tiene la tabal pedprot
	public function __construct($conexion){
		parent::__construct($conexion);
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM pedprot';
		$respuesta = parent::consulta($sql);
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
    
	public function modificarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $numPedidoTemp, $productos){
		// @ Objetivo:
		//Modificar los datos de pedidos temporal cada vez que hacemos agregamos un producto, modificamos una candidad ...
		// @ Parametros:
		//Todos los datos del pedido temporal
		$productos_json=json_encode($productos);
		$UnicoCampoProductos 	=$productos_json;
		$PrepProductos = $this->db->real_escape_string($UnicoCampoProductos);
		$sql='UPDATE pedprotemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda
		.' , estadoPedPro="'.$estadoPedido.'" , fechaInicio="'.$fecha.'"  ,Productos="'
		.$PrepProductos.'"  WHERE id='.$numPedidoTemp;
		$smt=parent::consulta($sql);
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
		$productos_json=json_encode($productos);
		$UnicoCampoProductos 	=$productos_json;
		$PrepProductos = $this->db->real_escape_string($UnicoCampoProductos);
		$sql = 'INSERT INTO pedprotemporales ( idUsuario , idTienda , estadoPedPro , 
		fechaInicio, idProveedor,  Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'
		.$estadoPedido.'" , "'.$fecha.'", '.$idProveedor.' , "'.$PrepProductos.'")';
		$smt=parent::consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			$id=$this->insert_id;
			$respuesta['id']=$id;
			$respuesta['sql']=$sql;
			$respuesta['productos']=$productos;
		}
		return $respuesta;
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
		$sql='UPDATE pedprot set estado="'.$estado .'"  where id='.$idPedido;
		$smt=parent::consulta($sql);
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
		//@Objetivo :
        // Obtner los datos de un pedido de la tabla pedprot
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
        $tablas = array( 'pedprot'=>'id','pedprolinea'=>'idpedpro','pedproIva'=>'idpedpro');
        $respuesta=array();
        $OK = 'KO';
        if ($tabla !==''){
            // Controlamos que la tabla indicada exista en array
            foreach ($tablas as $key=>$t){
                if ($t === $tabla) {
                    $OK ='OK';
                } else {
                    // ELimino de array los nombres tablas que no son .
                    unset($tablas[$key]);
                }
            }
        } else {
            // Pongo en OK porque queremos eliminar las 3 tablas.
            $OK = 'OK';
        }
        if ($idPedido > 0){
            // Solo ejecuto si hay un idPedido y esta OK
            if ($OK === 'OK'){
                foreach($tablas as $tabla => $campo){
                    $where = 'where '.$campo.' = '.$idPedido;
                    $respuesta[$tabla] = parent::deleteRegistrosTabla($tabla,$where);
                }
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
        $sql='INSERT INTO pedprot'
            .' ( Numtemp_pedpro, FechaPedido, idTienda, idUsuario, idProveedor, estado, total, fechaCreacion)'
            .' VALUES ('.$datos['Numtemp_pedpro']
            .', "'.$datos['FechaPedido'].'", '.$datos['idTienda'].' , '
            .$datos['idUsuario'].', '.$datos['idProveedor'].', "'.$datos['estado']
            .'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")';
        $smt=parent::consulta($sql);
        if (gettype($smt)==='array'){
            $respuesta =$smt;
        }else{
            $id=$this->insert_id;
            // Ahora añadimos es valor a campo Numpredpro, aunque los correcto sería
            // obtener el numero que va y continuar el numero siguiente.
            // Aunque en PROVEEDORES no es necesario, ya que no lo obliga en ningun sitio.
            // De momento lo sigo haciendo asi. 
            if (isset($id)){
                $respuesta['id']=$id;
                $sql='UPDATE pedprot set Numpedpro='.$id.' WHERE id='.$id;
                $smt=parent::consulta($sql);
                if (gettype($smt)==='array'){
                    $respuesta =$smt;
                }
            }else{
                $respuesta['error']='Error no se obtuvo id para insertar Numpredpro.';
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
            $r['productoInsertados'] = $productos['valores_insert'];
        } else {
            $r = $productos;
        }
        array_push($respuesta,$r);
        $desglose = $this->AddPedproIvaDesglosePedido($datos['DatosTotales']['desglose'],$id);
        if ( $desglose['valores_insert']){
            // No hubo error y inserto.
            // Aunque no se al insertar por ejemplo 20 productos y falla en 5 , no indicara que inserto los 5 [testear] 
            $p['subtotalIvasInsertados'] = $desglose['valores_insert'];
        } else {
            $p = $desglose;
        }
        array_push($respuesta,$p);
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
        $smt=parent::consulta($sql);
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
        $sql='INSERT INTO pedproIva (idpedpro, iva, importeIva, totalbase)';
        $values = array();
        $respuesta= array();
        foreach ($desglose as  $iva => $basesYivas){
            $values[]='('.$id.', "'.$iva.'", "'.$basesYivas['iva'].'", "'.$basesYivas['base'].'")';
        }
        $sql .= ' VALUES '.implode(',',$values);
        // Hacemos una sola consulta con insercion de todos registros subtotales de ivas.
        $smt=parent::consulta($sql);
        if (gettype($smt)==='array'){
            $respuesta['error']=$smt['error'];
            $respuesta['consulta']=$smt['consulta'];
        } else {
            $respuesta['valores_insert'] = $this->affected_rows;
        }
        return $respuesta;
    }
    
    public function ModPedidoGuardado($datos){
		//@ Objetivo:
        // Guardar los datos de un pedido que ya existente, en las distintas tablas:
        //      pedprot-> Update
        //      pedprolinea y pedproIva -> Eliminamos registros y añadimos los nuevos.
        // @ Devolvemos:
        //  Array de arrays con:
        //  Si NO FUE CORRECTO en cada array no devuelve:
        //      [error] -> Error Mysql
        //      [consulta] -> Consulta que provoca error.
        //  Si fue devuelve
        //      [0] => [update_modificado] -> (int) Nº registros cambiadas en pedprot , siempre debe ser 1
        //      [1] => [pedprolinea] =>  (int) Nº registros ELIMINADOS en pedprolinea, son las lineas del pedido (ANTERIORES)
        //      [2] => [pedproIva] => (int) Nº registros ELIMINADOS EN pedproIva, subtotales ivas y bases, maximo 3 registros
        //      [3] => [productoInsertados] => (int) Nº registros añadido a pedprolines, las nuevas lineas del pedido
        //      [4] => [subtotalIvasInsertados] => (int) Nº registros de pedproIva, subtotales ivas y bases, maximo 3 registros
        if (isset($datos['idPedpro']) && $datos['idPedpro'] >0){
            $id = $datos['idPedpro'];
            $sql='UPDATE pedprot SET Numtemp_pedpro='.$datos['Numtemp_pedpro']
                .', FechaPedido ="'.$datos['FechaPedido'].'"'
                .', estado ="'.$datos['estado'].'"'
                .', total="'.$datos['total'].'"'
                .', modify_by="'.$datos['idUsuario'].'"'
                .', fechaModificacion=NOW()'
                .'  WHERE id = '.$id;
            $smt=parent::consulta($sql);
            if (gettype($smt)==='array'){
                $respuesta[] =$smt;
            } else {
                $respuesta[]['update_modificado'] = $this->affected_rows;
            }
            // Ahora tengo borrar los datos las tablas pedprolinea y tabla PedproIva, para
            // luego insertar los nuevos.
            if (!isset($smt['error'])){
                $r = $this->eliminarPedidoTablas($id,'pedprolinea');
                array_push($respuesta,$r);
            }
            if (!isset($r['error'])){
                $r = $this->eliminarPedidoTablas($id,'pedproIva');
                array_push($respuesta,$r);
            }
            if (!isset($r['error'])){
                // Añadir registros a dos tablas
                $res =$this->AddRegistrosPedidosLineasIvas($datos,$id);
                // Como recibimos un array de arrays, recorro y añado uno a uno 
                foreach ($res as $r){ 
                    $respuesta[] = $r;
                }
            }
        }
        return $respuesta;
    }

    public function NumAlbaranDePedido($idPedido){
		$tabla='pedproAlb';
		$where='idPedido='.$idPedido;
		$relacion_alb_pedido = parent::SelectUnResult($tabla, $where);
		return $relacion_alb_pedido;
    }

	public function eliminarTemporal($idTemporal, $idPedido =0){
		//@Objetivo :
        // Eliminar temporal, tanto si recibe idTemporal o idPedido
		if ($idPedido>0){
			$sql='DELETE FROM pedprotemporales WHERE idPedpro='.$idPedido;
		}else{
			$sql='DELETE FROM pedprotemporales WHERE id='.$idTemporal;
		}
		$smt=parent::consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
		}else {
            $respuesta['valores_insert'] = $this->affected_rows;
        }
        return $respuesta;
	}
    
	public function TodosTemporal($idPedpro = 0){
        // @ Objetivo:
        // Obtener los pedidostemporales (todos) o de un pedido de terminado, ya que puede suceder que
        // se este editando el mismo pedido a la vez.
        $respuesta = array();
		$Sql= 'SELECT tem.idPedpro, tem.id , tem.idProveedor, tem.total, b.nombrecomercial, 
		c.Numpedpro from pedprotemporales as tem left JOIN proveedores as b on 
		tem.idProveedor=b.idProveedor left JOIN pedprot as c on tem.idPedpro=c.id';
        if ($idPedpro > 0){
            // buscamos solos temporales para ese pedido.
            $Sql .= ' where tem.idPedpro='.$idPedpro;
        }
		$smt=parent::consulta($Sql);
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
		$sql = 'SELECT a.id , a.Numpedpro , a.FechaPedido, b.nombrecomercial, 
		a.total, a.estado FROM `pedprot` as a LEFT JOIN proveedores as b on 
		a.idProveedor=b.idProveedor   '. $limite ;
		$smt=parent::consulta($sql);
		$pedidosPrincipal=array();
		if (gettype($smt)==='array'){
			$respuesta = array( 'error'     => $smt['error'],
                                'consulta'  => $smt['consulta']
                            );
		}else{
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);
			}
            $respuesta = array( 'Items'     => $pedidosPrincipal,
                                'consulta'  => $sql,
                                'limite'    => $limite
                            );
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
		if ($numPedido>0){
			$sql='SELECT Numpedpro, FechaPedido, total, id FROM pedprot 
			WHERE idProveedor= '.$idProveedor.' and estado='."'".$estado."'"
			.' and Numpedpro='.$numPedido;
			$smt=parent::consulta($sql);
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
			$smt=parent::consulta($sql);
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
		$sql='UPDATE pedprot SET FechaPedido= "'.$fecha.'" where id='.$idPedido;
		$smt=parent::consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
		
	}
    
	public function guardarPedido(){
        // @ Objetivo:
        // Comprobar que los datos enviados por $_POST son correcto y guardamos el pedido.
        // Si no fueran correctos, se devuelve un error.
        $errores    = array();
        $respuesta  = array();
        // Como tengo cargado inicial.. tengo tomar datos de session-
        $Tienda = $_SESSION['tiendaTpv'];
        $Usuario = $_SESSION['usuarioTpv'];
        if (!isset($Tienda['idTienda']) || !isset($Usuario['id'])){
            array_push($errores,$this->montarAdvertencia('danger',
                                    'ERROR NO HAY DATOS DE SESIÓN!'
                                    )
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
            array_push($errores,$this->montarAdvertencia('warning',
                                    'No se permite modificar un pedido en estado Facturado'
                                    )
                        );
        }
        if (isset($_GET['tActual']) && isset($_POST['idTemporal'])){
            // Tiene que existir los dos valores.
            if ($_GET['tActual'] !== $_POST['idTemporal']){
                // Los idtemporales algo esta mal.
                array_push($errores,$this->montarAdvertencia('warning',
                                    'Algo salio mal con el ID de temporal, ya que no coincide get con post !!'
                                    )
                        );
            }
        }
        // Obtenemos los datos de pedido temporal.
        $pedidoTemporal=$this->DatosTemporal($_POST['idTemporal']);
        if (isset($pedidoTemporal['error'])){
            array_push($errores,$this->montarAdvertencia('danger',
                                    'Error 2 de SQL: '. $pedidoTemporal['consulta']
                                    )
                        );
        } 
        // Comprobamos que tenga productos el temporal.
        if (!isset ($pedidoTemporal['Productos'])){
            array_push($errores,$this->montarAdvertencia('warning',
                                    'No existen productos para el recalculo de precios!'
                                    )
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
                         array_push($errores,$this->montarAdvertencia('danger',
                                    'No se ha podido crear nuevo pedido.Error de SQL:'.$addNuevo['consulta']
                                    )
                            );
                    } else {
                        // Deberíamos recibir el numero de pedido creado
                        if(isset($addNuevo['id'])){
                            $respuesta['id_guardo'] = $addNuevo['id'];
                            // Eliminamos pedido temporal, si envio idPedido y mayor de 0, elimina
                            // todos los temporales que tenga idPedido.
                            $eliminarTemporal=$this->eliminarTemporal($_POST['idTemporal'], $idPedido);
                            if (isset($eliminarTemporal['error'])){
                                array_push($errores,$this->montarAdvertencia('danger',
                                    'No se puedo eliminar el temporal.Error de SQL:'.$eliminarTemporal['consulta']
                                    )
                                );
                            }
                        } else {
                            // Algo paso ya que no se obtuvo el id, pero la consulta fue correcta.
                                array_push($errores,$this->montarAdvertencia('danger',
                                                'No se puedo eliminar porque no recibe id Nuevo pedido.Error de SQL:'
                                                .$eliminarTemporal['consulta']
                                                )
                                        );
                        }
                    }
                }
            }
            if ($_POST['estado'] === 'Sin Guardar'){
                // Si su estado es "Sin Guardar" entonces se modifica.
                if ($pedidoTemporal['idPedpro'] >0){
                    $datosPedido['idPedpro'] = $pedidoTemporal['idPedpro'];
                    $modPedido=$this->ModPedidoGuardado($datosPedido);
                    $respuesta['modPedido'] = $modPedido;
                    // Lo posobles errores que podemos obtener.
                    $e = array ( 0 => 'Error update en pedprot:',
                                 1 => 'Error al eliminar tabla pedprolinea:',
                                 2 => 'Error al eliminar tabla pedproIva:',
                                 3 => 'Error al insertar productoInsertados:',
                                 4 => 'Error al insertar subtotalIvasInsertados:'
                                );
                    // Recorro todos los resultado comprobando si hubo errores.
                    $control_error_modPedido = 'OK';
                    foreach ($modPedido as $key=>$r){
                        if (isset($r['error'])){
                            $control_error_modPedido = 'KO';
                            array_push($errores,$this->montarAdvertencia('danger',
                                                        $e[$key].$r['error'].'->'.$r['consulta']
                                                    )
                                                );
                        }
                    }
                    if ($control_error_modPedido === 'OK'){
                        // No hubo errores por lo que elimina temporales de ese pedido
                        $eliminarTemporal=$this->eliminarTemporal($_POST['idTemporal'], $idPedido);
                        if (isset($eliminarTemporal['error'])){
                            array_push($errores,$this->montarAdvertencia('danger',
                                'No se puedo eliminar el temporal.Error de SQL:'.$eliminarTemporal['consulta']
                                )
                            );
                        }
                    }
                }
            }
        }
        if (count($errores) >0){
            $respuesta['errores'] = $errores; 
        }
        return $respuesta;
    }

    public function comprobarTemporalIdPedpro($idPedido,$numPedidoTemp = 0){
        // @Objetivo:
        // Compruebo que solo hay un pedido temporal para ese idPedpro 
        // @Devuelvo:
        //  Array con o sin errores.
        $errores = array();
        if ($idPedido > 0){
            $posible_duplicado = $this->TodosTemporal($idPedido);
            if (!isset($posible_duplicado['error'])){
                $OK ='OK';
                if (count($posible_duplicado)>1){
                     $OK = 'Hay mas de un temporarl con el mismo numero pedido.';
                } else {
                    // Hay uno solo.
                    if ($numPedidoTemp > 0) {
                        if (isset($posible_duplicado[0]['id']) && $posible_duplicado[0]['id'] !== $numPedidoTemp){
                            $OK = 'Hay un temporal y no coincide el idtemporal.';
                        }
                    } else {
                        if (isset( $posible_duplicado[0]['id']) && $posible_duplicado[0]['id'] >0 ){
                            // Solo devuelvo idTemporal si id > 0    
                            $errores['idTemporal'] = $posible_duplicado[0]['id'];
                        }
                    }
                }
                if ($OK !== 'OK' ){
                    // Existe un registro o el que existe es distinto al actual.
                    array_push($errores,$this->montarAdvertencia('danger',
                                         '<strong>Ojo posible duplicidad en pedido temporal !! </strong>  <br> '.$OK
                                        )
                            );
                }
            }

        }
        return $errores;
    }

    /* ===================     Metodos antiguos que no toque    ========================
     *  Utiliza $this->db
     * */
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
    
}
?>
