<?php
/* Objetivo de esta clase
 *   - Crear un objeto que contenga productos con todos los datos de estos.
 *   - Tener los parametros cargados, para interactuar con los datos.
 *
 * [Informacion sobre los estados posibles.]
 * Campo estado de las tablas de articulos :
 * Sus posibles valores , los podemos ver el metodo: posiblesEstados($tabla), donde hay unos para todas las tablas
 * y algunas tablas tiene algunos mas.
 * 
 * [OTRAS NOTAS]
 * Propiedades privadas:
 * En la clase extendida tenemos propiedades privadas , esta no las puedo obtener directamente.
 * Ejemplo:
 *   idTienda existe en la clase extendida ,pero es privada por la que no la podemos utilizar directamente ($this->idTienda)
 * Por ello si declaramos la propiedad en la clase, podríamos tener dos valores distintos de la misma propiedad... pero una
 * la llamamos parent::  ... 
 * 
 * Propiedades publicas:
 * En la clase podemos obtener propiedades de la clase extendida directamente
 * Ejemplo:
 *   $this->estado no esta declarada en la clase pero si en la extendida tiene valor...
 * 
 * Metodos en UpperCamelCase
 * 
 * 
 * 
 * */


include_once ($RutaServidor.$HostNombre.'/clases/ClaseTablaArticulos.php');
require_once ($RutaServidor.$HostNombre.'/plugins/plugins.php');
class ClaseProductos extends ClaseTablaArticulos{
	public $view ; //string ruta de la vista que estamos
	public $idTienda ; // Obtenemos el idTienda de la clase extendida.
	public $plugins; // (array) de objectos que son los plugins que vamos tener para este modulo.
	
	public function __construct($conexion='')
	{
		// Solo realizamos asignamos 
		if (gettype($conexion) === 'object'){
			parent::__construct($conexion);
			$this->idTienda = parent::GetIdTienda();
		}
		$this->view = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['PHP_SELF']);
		$plugins = new ClasePlugins('mod_producto',$this->view);
		$this->plugins = $plugins->GetParametrosPlugins();
	}
	
	public function obtenerProductos($campo,$filtro=''){
		// @ Objetivo 
		// Obtener los campos idArticulo,articulo_name,ultimoCoste,beneficio,iva,pvpCiva,estado productos según con el filtro indicado.
		switch ($campo) {
			case 'articulo_name':
				// Buscamos por nombre de articulo..
				$consulta = "SELECT a.idArticulo,a.articulo_name as articulo_name"
				." ,a.ultimoCoste,a.beneficio,a.iva,p.pvpSiva,p.pvpCiva,a.estado"
				." FROM `articulos` AS a "
				."LEFT JOIN `articulosPrecios` AS p "
				."ON (p.`idArticulo` = a.`idArticulo`) AND  "
                ."(p.idTienda =".$this->idTienda.") "
                .$filtro;
				break;
			
			case 'crefTienda':
				// Buscamos por Referencia de tienda.
				$consulta = "SELECT a.idArticulo,a.articulo_name as articulo_name"
				." ,atiendas.crefTienda as crefTienda,a.ultimoCoste,a.beneficio,a.iva,p.pvpSiva,p.pvpCiva,a.estado"
				." FROM `articulos` AS a "
				."LEFT JOIN `articulosPrecios` AS p "
				."ON p.`idArticulo` = a.`idArticulo` "
				."LEFT JOIN `articulosTiendas` AS atiendas ON (atiendas.idArticulo = a.idArticulo) AND "
				."(atiendas.idTienda =".$this->idTienda.") "
				.$filtro;
				break;
			
			case 'codBarras':
				// Buscamos por Codbarras.
				$consulta = "SELECT a.idArticulo,a.articulo_name as articulo_name"
				." ,aCodBarras.codBarras as codBarras,a.ultimoCoste,a.beneficio,a.iva,p.pvpSiva,p.pvpCiva,a.estado"
				." FROM `articulos` AS a "
				."LEFT JOIN `articulosPrecios` AS p "
				."ON p.`idArticulo` = a.`idArticulo` "
				."LEFT JOIN `articulosCodigoBarras` AS aCodBarras ON (aCodBarras.idArticulo = a.idArticulo)"
				.$filtro;
				
			
		}
		
		$respuesta = parent::Consulta($consulta);
		if (isset($respuesta['error'])){
			// Si existe error devolvemos todo el array
			return $respuesta;
		}
		if ($respuesta['NItems'] === 0){
			$respuesta['Items'] = array();
		}
		return $respuesta['Items'];

	}
	public function buscarReferenciaProductoTienda($referencia){
		$consulta="SELECT * FROM articulosTiendas WHERE idTienda=".$this->idTienda.'  and crefTienda="'.$referencia.'"';
		$respuesta = parent::Consulta($consulta);
		if (isset($respuesta['error'])){
			// Si existe error devolvemos todo el array
			return $respuesta;
		}
		if ($respuesta['NItems'] === 0){
			$respuesta['Items'] = array();
			
		}
		return $respuesta['Items'];
		
	}
	public function GetProducto($id= 0){
		// Objetivo:
		// Este metodo existe en padre, pero necesito que añada a ArrayPropiedades las comprobaciones hacemos aquí.
		parent::GetProducto($id);
		// Ahora hacemos nuestra comprobaciones.
		$producto = $this->ArrayPropiedades();
                                        
		// Ahora comprobamos si el ultimo_coste es realmente el ultimo coste.
		// Se considera ultimo_coste a :
		//    - A la ultimo precio de coste de un albaran o factura de compra.
		// No se considera ultimo coste a:
		// 	  - A un precio tarifa puesto en un proveedor.
		// [NO PUEDO CONTINUAR MIENTRAS NO SE ARREGLE ISSUE 31 ]
		
		// Reinicio comprobacionesEstado.
		$this->comprobaciones = array();
		$this->comprobacionesEstado($producto);
		
		return $this->ArrayPropiedades();
		
	}
	public function GetPlugins(){
		$plugins = $this->plugins;
		return $plugins;//->GetDir();
	}

    public function SetPlugin($nombre_plugin){
        // @ Objetivo
        // Devolver el Object del plugin en cuestion.
        // @ nombre_plugin -> (string) Es el nombre del plugin que hay parametros de este.
        // Devuelve:
        // Puede devolcer Objeto  o boreano false.
        $Obj = false;
        if (count($this->plugins)>0){
            foreach ($this->plugins as $plugin){
                if ($plugin['datos_generales']['nombre_fichero_clase'] === $nombre_plugin){
                    $Obj = $plugin['clase'];
                }
            }
        }
        return $Obj;

    }
	
	public function GetView(){
		return $this->view;//->GetDir();
	}
	
	public function GetProductosConCodbarras($codbarras){
		// Objetivo:
		// Obtener array con id de productos que tiene ese codbarras.
		$sql = 'SELECT idArticulo FROM `articulosCodigoBarras` WHERE `codBarras`="'.$codbarras.'"';
		$items = parent::Consulta($sql);
		return $items;
		
	}
		
	
	
	public function cambiarTienda($id){
		// @Objetivo
		// Cambiar el id de la tienda , por si queremos buscar en otras tiendas simplemente.
		// Ten en cuenta que solo la cambia en esta clase no en la extendida, por lo que las consultas realizadas e
		// en la clase extendida, seguira haciendolo en la tienda asignada en la clase extendida.
		$this->idTienda= $id;
	}
    
	public function addTiendaProducto($idProducto, $idTienda, $idVirtuemart, $estado){
        // @Objetivo
        // Añadir a la tabla articulosTiendas la relacion con otra tienda.
        $sql='INSERT INTO articulosTiendas (idArticulo, idTienda, idVirtuemart, estado)VALUES
        ('.$idProducto.', '.$idTienda.', '.$idVirtuemart.', "'.$estado.'")';
        $respuesta = $this->Consulta_insert_update($sql);
        $respuesta['consulta'] = $sql;
        return $respuesta;
    }
    
	public function posiblesEstados($tabla){
		// @Objetivo
		// Obtener los estados posibles para la tabla que indicamos en parametro.
		// Posibles estado generales:
		$posibles_estados = array(  '1'=> array(
											'estado'      =>'Activo',
											'Descripcion' =>'Estado normal.'
												),
									'2' =>  array(
											'estado'      =>'Nuevo',
											'Descripcion' =>'Estado por defecto cuando se creo hace menos de 30 días.'
											),
									
									'3' =>  array(
											'estado'      =>'Temporal',
											'Descripcion' =>'Un producto que solo se comprar de forma temporal, en una epoca. En el proceso compra, debería advertilo y saber porque se compra.'
											),
									
									'4' =>  array(
											'estado'      =>'Baja',
											'Descripcion' =>'Indica que es un producto que se puede vender hasta fin existencias. Debería advertir a encargados compra que no se puede comprar.'
											),
									'5' =>  array(
											'estado'      =>'importado',
											'Descripcion' =>'Producto importado, de alguna tienda. Se creo forma automatica. Se cambia el estado, cuando ya lo compremos o cuando lo modifiquemos en ficha de producto'
											)
									);
		// Añado en todas la tablas menos en la articulos ya que son los por defecto.
		switch ($tabla) {
			case 'articulosTiendas':
				$array = array( '6' => array(
									'estado' =>'NoPublicado',
									'Descripcion'=>'Que existe en la tienda web pero no está publicado para la venta.'
									),
								'7' => array(
									'estado' =>'Publicado',
									'Descripcion'=>'Si esta creado y la venta en la tienda web'
									)
								);
				$posibles_estados= $posibles_estados +$array;
				break;
			case 'articulosProveedores':
				$array = array( '8' => array(
									'estado' =>'SinStock',
									'Descripcion'=>'El proveedor en estos momento no tiene Stock de producto.'
									),
								'9' => array(
									'estado' =>'Tarifa',
									'Descripcion'=>'Precio propuesto por el proveedor pero aun no se compro.'
									),
									
								);
				$posibles_estados= $posibles_estados +$array;
				break;
  
		}
		return $posibles_estados;
		
	}
	
	public function comprobacionesEstado($producto){
		// @ Objetivo 
		// Comprobar que el estado que enviamos sea correcta.
		// @ Parametros:
		// 		$producto-> (array) Con los datos del producto
		// @ Responde :
		// 		Si hubiera un error crea una comprobación y le añade a la propiedad comprobaciones y tb la devuelve.
		$estado = $producto['estado'];
		switch ($estado) {
			case 'Nuevo':
				// Debemos saber si la fecha_actualizacion ultima es superior a un mes.
				$datetime1 = date_create($producto['fecha_creado']);
				$datetime2 = date_create();
				$interval = date_diff($datetime1, $datetime2);
				if ($interval->days >20){
					// Creamos la advertencia.
					$error = array ( 'tipo'=>'warning',
								 'dato' => '',
								 'mensaje' => 'Ojo !! Este producto tiene el estado [Nuevo], lleva '.$interval->days.' días, recomiendo ponerlo como Activo.'
								 );
					parent::SetComprobaciones($error);
					
				}
				break;
			case 'importado':
				// Debemos saber si la fecha_actualizacion ultima es superior a un mes.
				$datetime1 = date_create($producto['fecha_creado']);
				$datetime2 = date_create();
				$interval = date_diff($datetime1, $datetime2);
				if ($interval->days >20){
					// Creamos la advertencia.
					$error = array ( 'tipo'=>'warning',
								 'dato' => '',
								 'mensaje' => 'Ojo !! Este producto tiene el estado [importado], lleva '.$interval->days.' días, recomiendo cambiarlo.'
								 );
					parent::SetComprobaciones($error);
					
				}
				break;
				
			
		}
	}
	

	public function AnhadirProductoNuevo($datos){
		// @ Objetivo
		// Crear un producto nuevo con los datos que tengamos.
		// @ Parametros:
		// 		$datos-> (array) con los datos para crear producto.
		// @ Devuelve:
		// 		(array) -> id (int) el numero id creado
		// 				-> errores (array) con tipo,mensaje,dato.
		$fecha_ahora= date("Y-m-d H:i:s");   // Obtenemos la fecha sistema 
		// ---- 		Insertamos un producto nuevo en tabla articulos 		----- //
		$sqlArticulo = 'INSERT INTO `articulos`(iva, articulo_name, estado,ultimoCoste, fecha_creado,beneficio, tipo) VALUES ("'
						.$datos['iva'].'","'.$datos['articulo_name'].'","'.$datos['estado'].'","'
						.$datos['coste'].'","'.$fecha_ahora.'","'.$datos['beneficio'].'", "'.$datos['tipo'].'")';
		// De momento inserto ultimoCoste, pero no deberíamos... :-) ya que no se compro.	
		$respuesta = array();
		$DB = parent::GetDb();
		$smt = $DB->query($sqlArticulo);
		if ($smt) {
			$respuesta['idInsert'] = $DB->insert_id;
			// Hubo resultados
		} else {
			// Quiere decir que hubo error en la consulta.
			$respuesta['error'] = $DB->connect_errno;
			$error = array ( 'tipo'=>'danger',
						 'mensaje' =>'Error al insertar en tabla Articulos '.json_encode($respuesta['error']),
						 'dato' => $sqlArticulo
					);
			$comprobaciones['insert_articulos'] = $error;
		}
				$respuesta['consulta'] = $sqlArticulo;
		if (!isset($respuesta['error'])){
			
			$comprobaciones['insert_articulos'] = array( 'id_producto_nuevo' => $respuesta['idInsert'],
														 'consulta'=> $respuesta['consulta'] = $sqlArticulo
														);
			
			// ---- 		Insertamos un producto precios del producto nuevo en tabla articulosprecios 		----- //
			$datos['id'] = $respuesta['idInsert'];
			// Hay que tene en cuenta que si el precio es 0 lo va añadir igualmente, ya que asi se podrá modificar , no insertar.
			$comprobaciones['insert_articulos_precios']  = parent::InsertarPreciosVentas($datos);
			
			// ----         Insertamos codbarras  del producto nuevo 											----- //
			$comprobaciones['codbarras']=$this->ComprobarCodbarrasUnProducto($datos['id'],$datos['codBarras']);
            $comprobaciones['familias']=$this->ComprobarFamiliasProducto($datos['id'],$datos['familias']);
			$comprobaciones['RefTienda']= $this->ComprobarReferenciaProductoTienda($datos['id'], $datos['refProducto']);
		}
		return $comprobaciones;
		
	}
		
    public function AnhadirCodbarras($id,$codbarras = array()){
			// @ Objetivo 
			// Una funcion para añadir uno o mas codBarras al producto que enviamos.
			// @ parametros:
			// 		id-> (int) ID del producto
			// 		codbarras -> (array) -> (strings) Codbarras queremos añadir.
			$respuesta = array();
			$values = array();
			if ($id > 0){
				if (count($codbarras)>0){
					// Entonces eliminamos solo el codbarras que indicamos.
					foreach ($codbarras as $key=>$cd){
						$values[]= '('.$id.',"'.$cd.'")';
					}
				}
				$stringValues = implode(',',$values);
				$sql = 'INSERT INTO `articulosCodigoBarras`(`idArticulo`, `codBarras`) VALUES '.$stringValues;
				$respuesta = $this->Consulta_insert_update($sql);
				//~ $DB = parent::GetDb();
				//~ $smt = $DB->query($sql);
				//~ if ($smt) {
					//~ $respuesta['NAnhadidos'] = $DB->affected_rows;
					//~ // Hubo resultados
				//~ } else {
					//~ // Quiere decir que hubo error en la consulta.
					//~ $respuesta['consulta'] = $sql;
					//~ $respuesta['error'] = $DB->connect_errno;
				//~ }
				$respuesta['consulta'] = $sql;
				
			}
			$respuesta['consulta'] = $sql;
			return $respuesta;
			
	}	
	
	public function AnhadirFamilias($id,$familias = array()){
        $respuesta = array();
			$values = array();
			if ($id > 0){
				if (count($familias)>0){
               
					foreach ($familias as $key=>$cd){
                      
						$values[]= '('.$id.',"'.$cd.'")';
					}
                 
				}
				$stringValues = implode(',',$values);
				$sql = 'INSERT INTO `articulosFamilias`(`idArticulo`, `idFamilia`) VALUES '.$stringValues;
          
				$respuesta = $this->Consulta_insert_update($sql);
				
				$respuesta['consulta'] = $sql;
				
			}
			$respuesta['consulta'] = $sql;
			return $respuesta;
    }
	
	public function EliminarCodbarras($id,$codbarras = array()){
			// @ Objetivo 
			// Una funcion para eliminar uno o todos los codBarras del producto que enviamos.
			// @ parametros:
			// 		id-> (int) ID del producto
			// 		codbarras -> (array) -> (strings) Codbarras queremos eliminar.
			$respuesta = array();
			if ($id > 0){
				if (count($codbarras)>0){
					// Entonces eliminamos solo el codbarras que indicamos.
					foreach ($codbarras as $key=>$cd){
						$codbarras[$key]= 'codbarras="'.$cd.'"';
					}
					$stringCodbarras = ' AND ('.implode(' OR ',$codbarras).')'; 
				}
				$sql = 'DELETE FROM `articulosCodigoBarras` WHERE `idArticulo`='.$id.$stringCodbarras;
				$DB = parent::GetDb();
				$smt = $DB->query($sql);
				if ($smt) {
					$respuesta['NEliminados'] = $DB->affected_rows;
					// Hubo resultados
				} else {
					// Quiere decir que hubo error en la consulta.
					$respuesta['consulta'] = $sql;
					$respuesta['error'] = $DB->connect_errno;
				}
				$respuesta['consulta'] = $sql;
				
			}
			$respuesta['consulta'] = $sql;
			return $respuesta;
			
	}
	
	public function EliminarFamilia($id,$familias = array()){
        $respuesta = array();
			if ($id > 0){
				if (count($familias)>0){
					// Entonces eliminamos solo el codbarras que indicamos.
					foreach ($familias as $key=>$cd){
						$familias[$key]= 'idFamilia="'.$cd.'"';
					}
					$stringfamilias = ' AND ('.implode(' OR ',$familias).')'; 
				}
				$sql = 'DELETE FROM `articulosFamilias` WHERE `idArticulo`='.$id.$stringfamilias;
				$DB = parent::GetDb();
				$smt = $DB->query($sql);
				if ($smt) {
					$respuesta['NEliminados'] = $DB->affected_rows;
					// Hubo resultados
				} else {
					// Quiere decir que hubo error en la consulta.
					$respuesta['consulta'] = $sql;
					$respuesta['error'] = $DB->connect_errno;
				}
				$respuesta['consulta'] = $sql;
				
			}
			$respuesta['consulta'] = $sql;
			return $respuesta;
    }
	public function ComprobarCodbarrasUnProducto($id_pro,$Pro_Nuevo_codBarras){
		// @ Objetivo:
		// Que codigo de barras hay que añadir, modificar o eliminar.
		// @ Parametros: 
		//   $id -> (int) Id del producto que vamos añadir,modificar o elimnar codbarras.
		// 	 $Pro_Nuevo_codBarras-> (array) Los codbarras modificados, eliminado o nuevos.
		// @ Devuelve:
		//   comprobaciones : (array) con tipo,mensaje,dato para poder mostrar.
		$producto_sin_modificar = $this->getProducto($id_pro);
		$Pro_codBarras = $producto_sin_modificar['codBarras'];// (array) con los codbarras que tenía ante de modificar.
		//~ var_dump($producto_sin_modificar['codBarras']);
		$comprobaciones = array(); // Array que utilizamos para informar de lo que hicimos
		// ---       	    Ahora empezamos con CodBarras por partes   						--- //
		// Obtengo aquellos codbarras que no este el el Post, estos son los que tengo eliminar.
		$codbarras_eliminados = array_diff($Pro_codBarras,$Pro_Nuevo_codBarras);
		
		if (count($codbarras_eliminados)>0){
			// Quiere decir que SE BORRO alguno o todos los codbarras que existia.
			// Eliminamos el codbarras del producto.
			$Sqls['eliminados'] = $this->EliminarCodbarras($id_pro,$codbarras_eliminados);
			if ($Sqls['eliminados']['NEliminados']===count($codbarras_eliminados)){
				$comprobaciones[0]['tipo']= 'warning';
				$comprobaciones[0]['mensaje']= 'Eliminamos los siguiente codbarras para este producto:'.implode(',',$codbarras_eliminados);
			} else {
				$comprobaciones[0]['tipo']= 'dargen';
				$comprobaciones[0]['mensaje']= 'Error no coincide el numero eliminado de codbarras: '.implode(',',$codbarras_eliminados);
			}
			$comprobaciones[0]['dato']= json_encode($Sqls['eliminados']);
		}
		// Ahora vemos los que tenemos que añadir.
		// En array de los codbarras recibidos ($DatosProducto['codBarras']) eliminamos aquellos que vamos añadir.
		$codbarras_nuevos = array_diff($Pro_Nuevo_codBarras,$Pro_codBarras);
		if (count($codbarras_nuevos)>0){
			$Sqls['anhadidos'] = $this->AnhadirCodbarras($id_pro,$codbarras_nuevos);
			if ($Sqls['anhadidos']['NAfectados']===count($codbarras_nuevos)){
				$comprobaciones[1]['tipo']= 'success';
				$comprobaciones[1]['mensaje']= 'Añadimos los siguiente codbarras: '.implode(',',$codbarras_nuevos);
			} else {
				$comprobaciones[1]['tipo']= 'danger';
				$comprobaciones[1]['mensaje']= 'Error no coincide el numero insertado con los codbarras que iba añadir codbarras: '.implode(',',$codbarras_nuevos);
			}
			$comprobaciones[1]['dato'] = json_encode($Sqls['anhadidos']);
		}	
		
		return $comprobaciones;
	}
	public function ComprobarFamiliasProducto($id_pro,$familiasNuevas){
        $producto_sin_modificar = $this->getProducto($id_pro);
       
        $familiasNoGuardadas=array();
        foreach ($familiasNuevas as $familia){
            array_push($familiasNoGuardadas, $familia['idFamilia']);
        }
         //~ echo '<pre>';
        //~ print_r($familiasNoGuardadas);
        //~ echo '</pre>';
        $familiasGuardadas=array();
        $familiasGuardadas = $producto_sin_modificar['familias'];
        $Pro_familias=array();
       foreach ($familiasGuardadas as $familia){
           array_push($Pro_familias, $familia['idFamilia']);
       }
        $comprobaciones = array();
        $familias_eliminar = array_diff($Pro_familias,$familiasNoGuardadas);
        if (count($familias_eliminar)>0){
			$Sqls['eliminados'] = $this->EliminarFamilia($id_pro, $familias_eliminar);
			if ($Sqls['eliminados']['NEliminados']===count($familias_eliminar)){
				$comprobaciones[0]['tipo']= 'warning';
				$comprobaciones[0]['mensaje']= 'Eliminamos los siguientes familias para este producto:'.implode(',',$familias_eliminar);
			} else {
				$comprobaciones[0]['tipo']= 'dargen';
				$comprobaciones[0]['mensaje']= 'Error no coincide el numero eliminado de idFamilia: '.implode(',',$familias_eliminar);
			}
			$comprobaciones[0]['dato']= json_encode($Sqls['eliminados']);
		}
        $familias_nuevos = array_diff($familiasNoGuardadas,$Pro_familias);
          //~ echo '<pre>';
        //~ print_r($familias_nuevos);
        //~ echo '</pre>';
		if (count($familias_nuevos)>0){
             //~ print_r($familias_nuevos);
			$Sqls['anhadidos'] = $this->AnhadirFamilias($id_pro,$familias_nuevos);
			if ($Sqls['anhadidos']['NAfectados']===count($familias_nuevos)){
				$comprobaciones[1]['tipo']= 'success';
				$comprobaciones[1]['mensaje']= 'Añadimos los siguientes familias: '.implode(',',$familias_nuevos);
			} else {
				$comprobaciones[1]['tipo']= 'danger';
				$comprobaciones[1]['mensaje']= 'Error no coincide el numero insertado con las familias que iba añadir idFamilia: '.implode(',',$familias_nuevos);
			}
			$comprobaciones[1]['dato'] = json_encode($Sqls['anhadidos']);
		}	
		
		return $comprobaciones;
    }
	public function ComprobarProveedoresCostes($id,$datosProveedores){
		// @ Objetivos
		//  Comprobar que proveedores son nuevos, existen o hay que modificarlos.
		// @ Parametros
		// 		id 			-> (int)  Id de producto a consultar.
		//		datosProveedores -> (array) de arrays que contienen los datos costes, referencia y id de producto.
		parent::ObtenerCostesProveedores($id); // Obtenemos datos de los proveedores de ese producto.
		$proveedores_costes_sin = $this->proveedores_costes;
		// Eliminamos del array los elementos que no vamos comprobar.
		$elementos_descartados = array('fechaActualizacion','estado','nombrecomercial','razonsocial');
		// --- 		Comprobamos si se modifico			  --- //
		foreach ($proveedores_costes_sin as $key=>$p){
			foreach ($elementos_descartados as $elemento){
				unset($p[$elemento]);
			}
			// Ahora tenemos que buscar el mismo proveedor en los datos recibidos.
			foreach ($datosProveedores as $k=>$datos){
				if ($datos['idProveedor'] === $p['idProveedor']){
					// Es el mismo proveedor, comprobamos si tiene los mismos datos.
					if ( serialize($p) === serialize($datos) ){
						// tiene los mismos datos por lo que debemos eliminarlo de $datosProveedores.
						// ya que no vamos hacer nada.
						unset($datosProveedores[$k]);
					} else {
						// Se modifico el proveedor...
						$datosProveedores[$k]['estado'] =$proveedores_costes_sin[$key]['estado']; // Mantengo el mismo estado.
						$datosProveedores[$k]['se_hizo'] = 'modificado';
					}
				}
			}
		}
		//  ----  Ahora monstamos SQL  para grabar ----- //
		$comprobaciones = array();
		foreach ($datosProveedores as $k=>$datos){
			if (!isset($datos['se_hizo'])){
				//  Es nuevo registro coste proveedor, le añadimos el estado y fecha actualizacion 
				$datosProveedores[$k]['estado'] = 'Activo';
				$datosProveedores[$k]['se_hizo'] = 'nuevo';
				// Montamos Sql insert ya que es nuevo.
				$sql = 'INSERT INTO `articulosProveedores`(`idArticulo`, `idProveedor`, `crefProveedor`,
                 `coste`, `fechaActualizacion`, `estado`) VALUES ('.$datos['idArticulo'].','
                 .$datos['idProveedor'].',"'.$datos['crefProveedor'].'","'.$datos['coste']
                 .'",NOW(),"'.'Tarifa'.'")';
               
				$comprobaciones['nuevo'][]=$this->Consulta_insert_update($sql);
			} else {
				// Es modificado montamos sql update
				$sql = 'UPDATE `articulosProveedores` SET `idArticulo`='
						.$datos['idArticulo'].',`idProveedor`='.$datos['idProveedor'].',`crefProveedor`="'
						.$datos['crefProveedor'].'",`coste`="'.$datos['coste']
						.'",`fechaActualizacion`= NOW(),`estado`="'.$datos['estado'].'" WHERE idArticulo = '
						.$datos['idArticulo'].' AND idProveedor ='.$datos['idProveedor'];
				$comprobaciones['modificado'][]=$this->Consulta_insert_update($sql);

			}
		}
		//	----- 		EJECUTAMOS SQLS 		---- //
		
		
		return $comprobaciones;
	}
	
	public function ComprobarReferenciaProductoTienda($id, $referenciaTienda){
		$comprobaciones=array();
		$sql='SELECT * FROM articulosTiendas WHERE idArticulo='.$id.' and idTienda='.$this->idTienda;
        //~ echo 'consulta:'.$referenciaTienda;
        $respuesta = parent::Consulta($sql);
		if (isset($respuesta['error'])){
			return $respuesta;
		}
		if ($respuesta['NItems'] === 0){
			$sql='INSERT INTO `articulosTiendas`(`idArticulo`, `idTienda`, `crefTienda`, `estado`) VALUES ('.$id.', '.$this->idTienda.', "'.$referenciaTienda.'", "Nuevo")';
			$comprobaciones['nuevo'][]=$this->Consulta_insert_update($sql);
			return $comprobaciones;
			
		}else{
			$sql='UPDATE `articulosTiendas` SET `crefTienda`="'.$referenciaTienda.'" WHERE `idArticulo`='.$id.' and idTienda='.$this->idTienda;
			$comprobaciones['modificado'][]=$this->Consulta_insert_update($sql);
			return $comprobaciones;
		}
		
		
	}
	public function ComprobarNuevosDatosProducto($id,$DatosPostProducto){
		// @ Objetivo
		// Comprobar que datos son distintos y grabarlos.
		// @ Parametros 
		//  	id -> (int) id producto queremos modificar 
		//		datosProveedores -> (array) de arrays que contienen los datos costes, referencia y id de producto.
		// @ Devolvemos 
		// 		comprobaciones -> (array) con los cambios realizados.
		$comprobaciones = array();
		parent::GetProducto($id);
		// ---- Ahora montamos datos generales actuales  de producto ---- //
		$datosgenerales_actual = array(
								'idTienda' 				=> $this->GetIdTienda(),
								'idArticulo' 			=> $id,
								'articulo_name'			=> $this->articulo_name,
								'iva'					=> $this->iva,
								'estado'				=> $this->estado,
								'ultimoCoste'			=> number_format($this->ultimoCoste,2),
								'beneficio'				=> $this->beneficio,
                                'tipo'                  =>$this->tipo
								);
		// Obtenemos id de proveedor principal
		if (gettype($this->proveedor_principal) === 'array'){
			$datosgenerales_actual['idProveedor'] = $this->proveedor_principal['idProveedor'];
		}
		// ---- Ahora montamos datos generales post					--- //
		$datosgenerales_post = array(
							'idTienda' 					=> $DatosPostProducto['idTienda'],
							'idArticulo' 				=> $DatosPostProducto['idArticulo'],
							'articulo_name'				=> $DatosPostProducto['articulo_name'],
							'iva'						=> $DatosPostProducto['iva'],
							'estado'					=> $DatosPostProducto['estado'],
							'ultimoCoste'				=> $DatosPostProducto['ultimoCoste'],
							'beneficio'					=> $DatosPostProducto['beneficio'],
                            'tipo'                      => $DatosPostProducto['tipo']
							);
		
		// Obtenemos id de proveedor principal
		if (gettype($DatosPostProducto['proveedor_principal']) === 'array'){
			$datosgenerales_post['idProveedor'] = $DatosPostProducto['proveedor_principal']['idProveedor'];
		}
		// Ahora comparamos si no es igual guardamos cambios, sino no hacemos nada.
		if (serialize($datosgenerales_actual) !== serialize($datosgenerales_post) ){
			// Montamos sql para guardar...
			$d =$datosgenerales_post;
			$sql =	'UPDATE `articulos` SET `iva`="'.$d['iva'].'",`idProveedor`="'
					.$d['idProveedor'].'",`articulo_name`="'.$d['articulo_name'].'",`beneficio`="'.$d['beneficio'].'",`estado`="'.$d['estado'].'",`fecha_modificado`=NOW(),`ultimoCoste`="'.$d['ultimoCoste'].'", tipo="'.$d['tipo'].'" WHERE idArticulo = '.$d['idArticulo'];
			$comprobaciones['datos_generales']=$this->Consulta_insert_update($sql);
		}
		return $comprobaciones;
		
	}
	
	
	public function ComprobarNuevosPreciosProducto($id,$DatosPostProducto,$idUsuario){
		// @ Objetivo
		// Comprobar si los precios cambiaron y grabarlos.
		// @ Parametros 
		//  	id -> (int) id producto queremos modificar 
		//		datosProveedores -> (array) de arrays que contienen los datos costes, referencia y id de producto.
		//
		// @ Devolvemos 
		// 		comprobaciones -> (array) con los cambios realizados y con elemento mensajes con array aunque este vacio.
		$comprobaciones = array();
		$comprobaciones['mensajes'] = [];
		$estado = '';
		parent::GetProducto($id); // Obtenemos los datos de producto actual.
		$precio_recalculado = number_format($this->precioCivaRecalculado(),2);
				
		if ($this->idArticulo >0 ){
			$precioCIva_post = number_format($DatosPostProducto['pvpCiva'],2);
			$precioSIva_post = number_format($DatosPostProducto['pvpSiva'],2);
			$c_precio = 'No';
			if ( $precioSIva_post !== number_format($this->pvpSiva,2)){
				$c_precio = 'Si';
			}
			if ( $precioCIva_post !== number_format($this->pvpCiva,2)){
				$c_precio = 'Si';
			}
			if ($c_precio === 'Si' ){
				// ---  Cambiamos el precio en la tabla articulosPrecios    ---- //
				$sql= 'UPDATE `articulosPrecios` SET `pvpCiva`="'
					.$precioCIva_post.'",`pvpSiva`="'.$precioSIva_post.'" WHERE idArticulo='
					.$id.' AND  idTienda='.$this->idTienda;
				$consulta = $this->Consulta_insert_update($sql);
				if ($consulta['NAfectados'] === 1){
					// Cambio un registro
					$success = array ( 'tipo'=>'success',
							 'mensaje' =>'Se ha grabado correctamente los nuevos precios.',
							 'dato' => ' Cantidad de registros modificados '.$consulta['NAfectados']
							);
					$comprobaciones['mensajes'][] = $success;
				} else {
					$success = array ( 'tipo'=>'danger',
							 'mensaje' =>'Hubo un error en la consulta '.$slq,
							 'dato' => $consulta
							);
					$comprobaciones['mensajes'][] = $success;
					
				}
				// ---  Añadimos historico de precios en la tabla historico_precios    ---- //
				$estado = 'Recomendado';
				if ($precio_recalculado !== $precioCIva_post){
					// El precio nuevo fue metido a mano, no es el recalculado.
					$estado = 'A mano';
				} 
				// Montamos los datos que necesita para añadir historico (addHistorico).
				$datos = array();
				$datos['antes'] = $this->pvpCiva;
				$datos['nuevo'] = $DatosPostProducto['pvpCiva'];
				$datos['idArticulo' ] = $id;
				$datos['estado'] = $estado;
				$datos['dedonde'] = 'producto'; // De que vista
				$datos['tipo'] = 'Productos'; // Que modulo;
				$datos['numDoc'] = 0; // No hay numero de documento, podría ser el idArticulo pero es absurdo ponerlo.
				$datos['idUsuario'] = $idUsuario;
				$anhadirHistorico = $this->addHistorico($datos);
				if ($anhadirHistorico['NAfectados'] === 1){
					// Cambio un registro
					$success = array ( 'tipo'=>'success',
							 'mensaje' =>'Se ha grabado correctamente el historico de precios.',
							 'dato' => ' Cantidad de registros añadidos '.$anhadirHistorico['NAfectados']
							);
					$comprobaciones['mensajes'][] = $success;
				} else {
					$success = array ( 'tipo'=>'danger',
							 'mensaje' =>'Hubo un error en la consulta a la hora grabar el historico'
							 .$anhadirHistorico['consulta'],
							 'dato' => $$anhadirHistorico
							);
					$comprobaciones['mensajes'][] = $success;
					
				}
			}
		}
		
		$comprobaciones['pvpCiva_antes'] =$this->pvpCiva;
		$comprobaciones['pvpCiva_nuevo'] =$precioCIva_post;
		$comprobaciones['pvpCiva_recalculado'] =$precio_recalculado;		
		$comprobaciones['estado'] =$estado;		
		//~ $comprobaciones['Sql_articulosPrecios'] =$sql;		
		//~ $comprobaciones['Sql_'] =$estado;		

		return $comprobaciones;
	}
	
	public function ObtenerCostesDeUnProveedor($id,$idProveedor){
		// @ Objectivo: 
		// Obtener los datos del proveedor seleccionado y el ultimo coste.()...
		// @ Parametros:
		// 	  $id -> (int) Id del producto a buscar.
		// 	  $idProveedor-> (int) Id de proveedor
		$respuesta = array();
		$Sql= 'SELECT art_prov.*, pro.nombrecomercial, pro.razonsocial  FROM `articulosProveedores` AS art_prov LEFT JOIN proveedores AS pro ON pro.idProveedor = art_prov.idProveedor WHERE art_prov.idArticulo ='.$id. ' AND art_prov.idProveedor ='.$idProveedor;
		$resp = $this->Consulta($Sql);
		if ($resp['NItems'] > 0){
			// Solo puede obtener un proveedor.
			$respuesta = $resp['Items'];
		} else {
			// Hubo error - No encontro
			$error = array ( 'tipo'=>'success',
							 'dato' => 'idArticulo:'.$id.' idProveedor:'.$idProveedor,
							 'mensaje' => 'No encontro ningún coste para es producto de ese proveedor.'
							 );
			$respuesta['error'] = $error;
		}
		return $respuesta;
	}
    
    public function contarProductosTpv(){
       $respuesta = array();
       $sql='SELECT count(idArticulo) as cantTpv from articulos';
       $resp = $this->Consulta($sql);
       if ($resp['NItems'] > 0){
            $respuesta = $resp['Items']; 
       }else {
			$error = array ( 'tipo'=>'success',
							 'dato' => '',
							 'mensaje' => 'No se encontró ningun articulo.'
							 );
			$respuesta['error'] = $error;
		}
		return $respuesta;
    }
	
    public function productosEnTpvNoWeb($idTienda){
         $respuesta = array();
         $sql='select count(idArticulo) as cantArticulo FROM articulosTiendas WHERE idTienda !='.$idTienda.' and 
         idArticulo NOT in (select idArticulo from articulosTiendas where idTienda='.$idTienda.')';
         $resp = $this->Consulta($sql); 
         if ($resp['NItems'] > 0){
            $respuesta = $resp['Items']; 
           }else {
                $error = array ( 'tipo'=>'success',
                                 'dato' => $sql,
                                 'mensaje' => 'No se encontró nungun producto.'
                                 );
                $respuesta['error'] = $error;
            }
		return $respuesta;
    }
    
    public function productosTienda($idTienda){
         $respuesta = array();
         $sql='select count(idArticulo) as cantArticulo from articulosTiendas where idTienda='.$idTienda;
          $resp = $this->Consulta($sql); 
         if ($resp['NItems'] > 0){
            $respuesta = $resp['Items']; 
           }else {
                $error = array ( 'tipo'=>'success',
                                 'dato' => $sql,
                                 'mensaje' => 'No se encontró nungun producto.'
                                 );
                $respuesta['error'] = $error;
            }
		return $respuesta;
    }
    
    public function comprobarIdWebTpv($idTienda, $idProducto){
        $respuesta = array();
         $sql='select idArticulo from articulosTiendas where idTienda='.$idTienda.' and idVirtuemart='.$idProducto;
          
          $resp = $this->Consulta($sql); 
       
        if(isset($resp['NItems'])){
           
            $respuesta['res']=$resp;
            $respuesta = $resp['Items']; 
            
           }else {
                $error = array ( 'tipo'=>'success',
                                 'dato' => $sql,
                                 'mensaje' => 'No se encontró nungun producto.'
                                 );
                $respuesta['error'] = $error;
            }
		return $respuesta;
    }
	public function comprobacionCamposObligatoriosProducto($datos){
		// Objetivo es comprobar que los datos enviados son correctos.
		// @ Parametros
		// 		$datos = (array asociativo) 
		$campos_obligatorios = array('articulo_name','estado','iva','pvpSiva','pvpCiva','coste','beneficio', 'tipo');
		$comprobaciones = array(); // Lo utilizo para guardar resultado de comprobaciones o errores.
		// ---- 	Comprobamos que existe campo y tiene dato correcto.		--------- //
		foreach ($campos_obligatorios as $key){
			$existe = 'NO';
			if (isset($datos[$key])){
				$existe ='Si'; // Ya que existe
				if ($key === 'iva'){
					// Comprobamos si el iva que vamos añadir es correcto, si no ponemos el por defecto.
					$comprobarIva = parent::ComprobarIva($datos['iva']);
					if (gettype($comprobarIva['error'])==='array'){
						// Hubo un error el tipo de iva 
						$datos['iva'] = $comprobarIva['iva'];
						$comprobaciones['iva'] = $comprobarIva['error']; // Podrías utilizar perfectamente $comprobarIva
					}
					
				}				
			} else {
				$error = array ( 'tipo'=>'danger',
								 'mensaje' =>'Error no existe o no es correcto '.$key,
								 'dato' => $key);
				$comprobaciones['campos'] = $error;
				
			}
			
		}
		return $comprobaciones;

	}
	
	public  function Consulta_insert_update($sql) {
		// Objetivo
		// Un metodo para realiza consulta de update insert, sin tener que devolver el id del insert.....
		// Dudo que sea util.. 
		$respuesta = array();
		$DB = parent::GetDb();
		$smt = $DB->query($sql);
		if ($smt) {
			$respuesta['NAfectados'] = $DB->affected_rows;
			// Hubo resultados
		} else {
			// Quiere decir que hubo error en la consulta.
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $DB->connect_errno;
		}
		return $respuesta;
	}
	
	
	
	
	public function addHistorico($datos){
		// @ Objetivo 
		// 	Añadir la tabla de historico_precios que cambio precio.
		// @ Parametros 
		//   $datos -> array() con :
		// 					idArticulo-> (int) 
		//					Antes-> (float) precioCiva anterior.
		//					Nuevo-> (float) precioCiva nuevo.
		// 					NumDoc-> (int)  numero de documento si vienes de un albaran compra
		// 					dedonde-> (string) Indica modulo (vista) que ejecuta.
		// 					idUsuario-> (int) id del usuario que lo genera.
		//					estado->  (string) Estado que puede ser , Recalculado o A mano.
		// 					tipo ->  (string) modulo que lo ejecuta.			
		$campos = 'idArticulo, Antes, Nuevo, Fecha_Creacion , NumDoc, Dedonde, Tipo, estado, idUsuario';
		$sql	='INSERT INTO historico_precios ('.$campos.') VALUES ('.$datos['idArticulo']
				.' , "'.$datos['antes'].'" , "'.$datos['nuevo']
				.'", NOW(), '.$datos['numDoc'].', '."'".$datos['dedonde']."'".', '
				."'".$datos['tipo']."'".' , '."'".$datos['estado']."'".', '.$datos['idUsuario'].')';

		$consulta = $this->Consulta_insert_update($sql);
		
		return $consulta;
	}
    public function EliminarHistorico($id){
        $sql='DELETE FROM historico_precios WHERE id='.$id;
        $consulta =$this->Consulta_insert_update($sql);
        return $consulta;
    }
	
	public function precioCivaRecalculado(){
		// @ Objetivo
		//  Obtener el precio con iva que recomendamos vender con el beneficio.
		// @ Parametros
		// 	De momento solo obtenemos el coste, iva , beneficio de objeto.
		$coste = $this->ultimoCoste;
		if ( $this->iva >0 ){
			$costeCiva = $coste + ($coste * $this->iva/100);
		}
		$precio_recalculado = $costeCiva;
		if ($this->beneficio > 0){
			$precio_recalculado = $precio_recalculado + ($precio_recalculado * $this->beneficio/100);
		} 
		
		return $precio_recalculado;
		
	}
    
    public function ComprobarEliminar($id, $idTienda){
        //Comprobar que el id del producto no este en ninguna linea de albaranes
        $sql=array();
        $sql[1]='select count(id) as cant from albprolinea where idArticulo='.$id;
        $sql[2]='select count(id) as cant from pedprolinea where idArticulo='.$id;
        $sql[3]='select count(id) as cant from facprolinea where idArticulo='.$id;
        $sql[4]='select count(id) as cant from ticketslinea where idArticulo='.$id;
        $sql[5]='select count(id) as cant from albclilinea where idArticulo='.$id;
        $sql[6]='select count(id) as cant from pedclilinea where idArticulo='.$id;
        $sql[7]='select count(id) as cant from facclilinea where idArticulo='.$id;
        $sql[8]='select count(idArticulo) as cant from articulosTiendas where idArticulo='.$id.' and idTienda='.$idTienda;
        $bandera=0;
        foreach ($sql as $consulta){
             $items = parent::Consulta($consulta);
             if($items['Items'][0]['cant']>0){
                 $bandera=1;
                 break;
             }
        }
        if($bandera==0){
            $sql=array();
            $sql[1]='delete from articulos where idArticulo='.$id;
            $sql[2]='delete from articulosTiendas where idArticulo='.$id;
            $sql[3]='delete from articulosClientes where idArticulo='.$id;
            $sql[4]='delete from articulosCodigoBarras where idArticulo='.$id;
            $sql[5]='delete from articulosFamilias where idArticulo='.$id;
            $sql[6]='delete from articulosPrecios where idArticulo='.$id;
            $sql[7]='delete from articulosProveedores where idArticulo='.$id;
            $sql[8]='delete from articulosStocks where idArticulo='.$id;
            foreach ($sql as $consulta){
                $eliminar =$this->Consulta_insert_update($consulta);
                if(isset($eliminar['error'])){
                    $resultado['consulta']=$eliminar['consulta'];
                    $resultado['error']=$eliminar['error'];
                }
            }
        }
        $resultado['bandera']=$bandera;
        
        return $resultado;
        
       
    }
    
    
    public function modificarProductoTPVWeb($datos){
        // Objetivo es modificar los productos con los datos de la web)
        // Esta funcion realmente no debería estar aquí.
        
       
        $sql='UPDATE articulos SET iva="'.floatval ($datos['iva']).'", articulo_name="'.$datos['nombre'].'", 
        fecha_modificado="'.date("Y-m-d H:i:s").'" where idArticulo='.$datos['id'];
        $respuesta = array();
		$DB = parent::GetDb();
		$smt = $DB->query($sql);
        
        if($DB->connect_errno){
            $respuesta['error']=$sql;

        } else {  

            $sql='UPDATE articulosPrecios SET pvpSiva="'.floatval ($datos['precioSiva']).'" , pvpCiva="'.floatval ($datos['precioCiva']).'" where idArticulo='.$datos['id'];
            $smt = $DB->query($sql);

            
            if($DB->connect_errno){
                $respuesta['error']=$sql;
            }
            if($datos['refTienda']<>"" && $datos['optRefWeb'] <> '3' ){
                // Ahora comprobamos que opcion seleccionamos para hacer.
                // Ya que podemos seleccionar tres opcion:
                //     1- Option accion de Referencia en tienda web
                //     2- Option accion de Referencia en tienda principal
                //     3- Option accion de Referencia no importa.
                // La primera es grabar referencia en tienda web, esta opcion se hace siempre...
                $sql='UPDATE `articulosTiendas` SET `crefTienda`="'.$datos['refTienda'].'" where idTienda='.$datos['tiendaWeb'].' and idArticulo='.$datos['id'];
                if ( $datos['optRefWeb'] == '2'){
                    // La segunda es grabar tambien en tienda principal
                    $sql .=';
                            UPDATE `articulosTiendas` SET `crefTienda`="'.$datos['tiendaPrincipal'].'" where idTienda='.$datos['tiendaWeb'].' and idArticulo='.$datos['id'];

                }
                $smt = $DB->query($sql);
                if($DB->connect_errno){
                    $respuesta['error']=$sql;
                }
            }
            if(count($datos['codBarras']) >0 ){
                $sql='DELETE FROM `articulosCodigoBarras` WHERE idArticulo='.$datos['id'];
                $smt = $DB->query($sql);
                        if($DB->connect_errno){
                            $respuesta['error']=$sql;
                        }
                foreach($datos['codBarras'] as $cod){
                    if($cod<>""){
                         $sql='INSERT INTO `articulosCodigoBarras`(`idArticulo`, `codBarras`) VALUES ('.$datos['id'].',"'.$cod.'")';
                    $smt = $DB->query($sql);
                        if($DB->connect_errno){
                            $respuesta['error']=$sql;
                        }
                    }
                    
                }
            }
        
        }
        return $respuesta;
    }
    
    public function addProductoWebTPV($datos){
        $fecha_ahora= date("Y-m-d H:i:s");   
		$sqlArticulo = 'INSERT INTO `articulos`(iva, articulo_name, estado,ultimoCoste, fecha_creado,beneficio) VALUES ("'
						.$datos['iva'].'","'.$datos['nombre'].'","'.$datos['estado'].'","'
						.$datos['ultimoCoste'].'","'.$fecha_ahora.'","'.$datos['beneficio'].'")';
		
		$respuesta = array();
		$DB = parent::GetDb();
		$smt = $DB->query($sqlArticulo);
		if ($smt) {
			$respuesta['idInsert'] = $DB->insert_id;
            $id=$respuesta['idInsert'];
			// Hubo resultados
		} else {
			// Quiere decir que hubo error en la consulta.
			$respuesta['error'] = $DB->connect_errno;
			$error = array ( 'tipo'=>'danger',
						 'mensaje' =>'Error al insertar en tabla Articulos '.json_encode($respuesta['error']),
						 'dato' => $sqlArticulo
					);
			$comprobaciones['insert_articulos'] = $error;
		}
        if (!isset($respuesta['error'])){
            // Ahora comprobamos que opcion seleccionamos para hacer.
            // Ya que podemos seleccionar tres opcion:
            //     1- Option accion de Referencia en tienda web
            //     2- Option accion de Referencia en tienda principal
            //     3- Option accion de Ignorar...
            // La primera es grabar referencia en tienda web, esta opcion se hace siempre ...
             $sql='INSERT INTO `articulosTiendas`(`idArticulo`, `idTienda`, `crefTienda`, 
                `idVirtuemart`, `estado`) VALUES ('.$id.','.$datos['tiendaWeb'].',"'.$datos['refTienda'].'",
                '.$datos['id'].', "'.$datos['estadoWeb'].'")';
             if ( $datos['optRefWeb'] == '2'){
                // La segunda es grabar tambien en tienda principal
                $sql .=';
                        INSERT INTO `articulosTiendas`(`idArticulo`, `idTienda`, `crefTienda`, 
                        `idVirtuemart`, `estado`) VALUES ('.$id.','.$datos['tiendaPrincipal']
                        .',"'.$datos['refTienda'].'",'.$datos['id'].', "'.$datos['estadoWeb'].'")';
            }

            $smt = $DB->query($sql);
            $respuesta['sqlArticulosTienda']=$sql;
            if($DB->connect_errno){
                $respuesta['error']=$sql;
            }
            // Ahora insertamos los precios .
            $sql='INSERT INTO `articulosPrecios`(`idArticulo`, `pvpCiva`, `pvpSiva`, `idTienda`) 
            VALUES ('.$id.','.$datos['precioCiva'].','.$datos['precioSiva'].','.$datos['tiendaPrincipal'].')';
            $smt = $DB->query($sql);
             if($DB->connect_errno){
                $respuesta['error']=$sql;
            }
            $respuesta['sqlArticuloPrecios']=$sql;
            // Ahora insertamos los codbarras
            if(count($datos['codBarras'])>0){
                $bandera=0;
                foreach($datos['codBarras'] as $cod){
                    if($cod<>""){
                    $sql='INSERT INTO `articulosCodigoBarras`(`idArticulo`, `codBarras`) VALUES ('.$id.',"'.$cod.'")';
                    $smt = $DB->query($sql);
                    if($DB->connect_errno){
                        $respuesta['error']=$sql;
                    }
                    $respuesta['sqlcodBar'.$bandera]=$sql;
                    $bandera++;
                    }
                   
                }
                
            }
            
            
        }
        return $respuesta;
        
    }
    
    public function modificarEstadoWeb($idProducto, $idEstado, $idTienda){
        if($idEstado==1){
            $estado="Sin Publicar";
        }else{
            $estado="Publicado";
        }
        $sql='UPDATE articulosTiendas SET estado="'.$estado.'" where idArticulo='.$idProducto.' and idTienda='.$idTienda;
        $respuesta['Consulta']=$this->Consulta_insert_update($sql);
        return $respuesta;
    }
    
    public function modificarVariosEstados($estado, $productos){
        $sql='UPDATE articulos SET estado="'.$estado.'" WHERE idArticulo IN ('.$productos.')';
         $respuesta['Consulta']=$this->Consulta_insert_update($sql);
        return $respuesta;
    }
    
    public function buscarFamiliasProducto($idProducto, $idTienda){
        // Objetivo buscar las familias de un producto.
        $respuesta=array();
        $sql='Select a.idFamilia, b.idFamilia_tienda from articulosFamilias as a 
        inner join familiasTienda as b on a.idFamilia=b.idFamilia where b.idTienda='.$idTienda.' and 
        a.idArticulo='.$idProducto.' ';
        $resp = $this->Consulta($sql); 
         if ($resp['NItems'] > 0){
            $respuesta = $resp['Items']; 
           }else {
                $error = array ( 'tipo'=>'success',
                                 'dato' => $sql,
                                 'mensaje' => 'No se encontró ninguna familia web para ese producto.'
                                 );
                $respuesta['error'] = $error;
            }
		return $respuesta;
    }
    
}



?>
