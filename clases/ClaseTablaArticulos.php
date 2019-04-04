<?php
/* Propiedades en minuscula.
 * Metodos en UpperCamelCase
 * */


include ($RutaServidor.$HostNombre.'/clases/ClaseTablaIva.php');
include ($RutaServidor.$HostNombre.'/clases/ClaseTablaFamilias.php');
include_once $RutaServidor.$HostNombre.'/modulos/mod_producto/clases/ClaseArticulosStocks.php';

class ClaseTablaArticulos{
	
	private $db; // (Objeto) Conexion
	private $idTienda; // (int) Id de la tienda , por defecto es la principal, pero se podrá cambiar.
	// Propiedades particulares de tabla articulos.
	private $num_rows; // (int) Numero de registros.
	public $idArticulo;
	public $iva= '0.00'; // String ya que obtenemos decimales... 
	public $articulo_name = '';
	public $cref_tienda_principal = ''; // La referencia de la tienda principal.
	public $beneficio =  25; // Beneficio por defecto
	public $costepromedio = 0; // Sino se compro , 0
	public $ultimoCoste = 0; // Es el ultimo coste compra, si no se compro es el ultimo conocido, que pusimos.
	public $pvpCiva = 0; // Precio con iva del producto en esa tienda.
	public $pvpSiva = 0; // Precio sin iva del producto en esa tienda.
	public $estado ='Nuevo'; // Estado del producto en la tabla articulos al crear.
	public $fecha_creado;
	public $fecha_modificado;
	public $codBarras; // Array de codbarras para ese producto.
	public $precios_tiendas; // Array de referencias de las tiendas.
	public $proveedores_costes; // Array de proveedores para ese producto ( costes,referencias)
	public $familias; // Array de familias de ese producto
	public $proveedor_principal; // Array con datos del proveedor principal
    public $productos_historico;
	public $comprobaciones = array(); // Array  de mensajes ( ver metodo de comprobaciones)
	public $ref_tiendas ; // (array) No inicializado, se utiliza para guardar las referencias distintas tiendas.
    public $stocks = [];
    public $tipo='unidad';
	
	public function __construct($conexion='')
	{
		// Solo realizamos asignamos 
		if (gettype($conexion) === 'object'){
			$this->db = $conexion;
			// Obtenemos el numero registros.
			$sql = 'SELECT count(*) as num_reg FROM articulos';
			$respuesta = $this->db->query($sql);
			$this->num_rows = $respuesta->fetch_object()->num_reg;
			// Obtenemos la tienda principal
			$this->ObtenerTiendaPrincipal();
		}
	}
	
	public function Consulta($sql){
		// @ Objetivo:
		// Realizar una consulta y devolver numero respuesta... o error..
		// [NOTA]
		// Solo valido para SELECT
		// No debería se funcion publica.
		// Habría que hacer algo como :
		// http://php.net/manual/es/mysqli-stmt.bind-param.php
		$respuesta = array();
		$db = $this->db;
		$smt = $db->query($sql);
		if ($db->query($sql)) {
			$respuesta['NItems'] = $smt->num_rows;
			// Hubo resultados
			while ($fila = $smt->fetch_assoc()){
				$respuesta['Items'][] = $fila;
			}
		} else {
			// Quiere decir que hubo error en la consulta.
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
		}
		
		return $respuesta;
	}
	
	
	public function GetProducto($id= 0){
		// @ Objetivo :
		// Obtener los datos de un articulo ( producto).
		// @ Parametro -> (int) id de articulo..
		// Todos los datos posibles según las propiedades que tengamos. (idTienda)
		// tenemos la propiedad de idTienda
		$respuesta = array();
		// El campo ultimoCoste, tendría que llamarse coste_ultimo
		// El campo costepromedio -> coste_promedio ...
		
        
        if ($id !=0){                    
			$Sql = 'SELECT a.*, prec.* FROM articulos as a '
				.'  LEFT JOIN articulosPrecios as prec ON a.idArticulo= prec.idArticulo '
				.'  WHERE a.idArticulo ='.$id.' AND '
				.'  prec.idArticulo='.$id.' AND prec.idTienda= '.$this->idTienda;
			$consulta = $this->Consulta($Sql);
            $marco_error = 'No' ;// Control si hubo error en consulta , lo normal es que no exista.	
			if (isset ($consulta['NItems'])){
				if ($consulta['NItems'] !== 1){ 
				// Hubo un error o encontro mas de uno o 0, es decir no existe.
						if ($consulta['NItems'] > 1){
							$error = array ( 'tipo'=>'danger',
									 'dato' =>$consulta,
									 'mensaje' => 'Encontro mas de un articulo con es id, ponerse en contacto con programador'
									 );
							$this->SetComprobaciones($error);
						} else {
                            // No obtuvo Item en la consulta.
                            // El motivo puedo ser porque no tiene registro de precio, por lo que  realizo consulta
                            // de nuevo, pero solo de la tabla principal.
                            $SoloSql = 'SELECT * FROM articulos '
                            .'  WHERE idArticulo ='.$id;
                            $consulta = $this->Consulta($SoloSql);
                            if ($consulta['NItems'] !== 1){ 
                                 $error = array ( 'tipo'=>'danger',
                                 'dato' =>$SoloSql,
                                 'mensaje' => 'No encontro ningun registro con ese ID:'.$id.' , ponerse en contacto con programador'
                                 );
                                $this->SetComprobaciones($error);
                            } else {
                                $error = array ( 'tipo'=>'warning',
                                 'dato' =>$Sql,
                                 'mensaje' => 'El producto con ID:'.$id.' , no tiene registro de precio, por lo creo en cero, para evitar problemas al guardar.'
                                 );
                                $this->SetComprobaciones($error);
                                $marco_error = 'Si';
                            }
						}
					}
				}
                if ($consulta['NItems'] === 1){
					$respuesta = $consulta['Items'][0];
					$this->MontarProducto($respuesta);
				}
                if ($marco_error === 'Si'){
                    // Existe registro , pero no existe registro precios, lo creo en 0
                    $datos = $this->ArrayPropiedades();
                    // Ahora tengo que crear elemento id, ya que es el que utiliza en funcion de insertar
                    $datos['id'] = $datos['idArticulo'];
                    $this->InsertarPreciosVentas($datos);
                    
                }




                
		} else {
			// Se monta tanto id sea 0 como si no existe id.
			$this->MontarProducto();
		}

        
		return $this->ArrayPropiedades();
	}
	
	// ----- METODOS PARA OBTENER PROPIEDADES --- //	
	public function MontarProducto($datos=array()){
		// Metodo para montar añadir los datos al producto.
		foreach ($datos as $propiedad => $valor){
			if ($propiedad === 'idProveedor'){
				// El proveedor principal guardamos como proveedor_principal y todos datos.
				$this->ObtenerDatosProvPredeter($valor);
			} else {
				$this->$propiedad = $valor;
			}
		}
		// Recuerda que idArticulo es 0 por defecto.
		if ($this->idArticulo !==NULL && $this->idArticulo !==0){
			// Obtenemos referencias y datos de las otras tiendas con sus precios para ese producto	
			$this->ObtenerReferenciasTiendas($this->idArticulo); 
			// Obtenemos referencia del producto para tienda principal.
			$this->ObtenerCrefTiendaPrincipal();
			// Obtenemos precios de coste de proveedores.
			$this->ObtenerCostesProveedores($this->idArticulo);
			// Obtenemos familias a las que pertenece ese producto
			$this->ObtenerFamiliasProducto($this->idArticulo);
            //Obtener el historico precio de ese producto
            $this->ObtenerHistoricoPrecio($this->idArticulo);
			// Obtenemos Codbarras a las que pertenece ese producto
			$this->ObtenerCodbarrasProducto($this->idArticulo);
            $this->stocks = alArticulosStocks::leer($this->idArticulo, $this->idTienda,true);                       
                        
			// Por ultimo realizamos comprobaciones.
			$this->Comprobaciones();
		}
		
	}
	
	
	public function ArrayPropiedades(){
		// Convertimos las propiedades en array
		$respuesta = (array) $this;
		if (count($this->comprobaciones)>0){
			// Quiere decir que hay comprobaciones realizadas,y puede ser errores.
			foreach ( $this->comprobaciones as $comprobaciones){
				// Si existe comprobaciones con tipo error no continuamos.
				if ($comprobaciones['tipo'] === 'danger'){
					$error =array('error'=>'No puedo continuar porque hay error grave',
								  'comprobaciones' => $this->comprobaciones);
					return $error;
				}
			}
		}
		// Eliminamos respuesta las que son privadas.
		foreach ($respuesta as $key=>$valor){
						
			if (strrpos($key,'ClaseTablaArticulos')!== FALSE){
				// Quiere decir que es privada, por lo que eliminamos 
				unset($respuesta[$key]);
			}
		}
		
		return $respuesta;
	}
	public function GetDb(){
		// Puede hacer falta para insert ,update, delete...
		return $this->db;
	}
	
	public function GetNumRows(){
		return $this->num_rows;
	}
	public function GetIdTienda(){
		return $this->idTienda;
	}
	public function GetCodbarras(){	
		return $this->codBarras;
	}
	public function GetReferenciasTiendas(){	
		return $this->ref_tiendas;
	}
	public function GetProveedorPrincipal(){	
		return $this->proveedor_principal;
	}
	public function GetComprobaciones(){	
		return $this->comprobaciones;
	}
	public function Comprobaciones(){
		// Objetivo:
		// Comprobar si los datos que tiene son correctos y cuales faltan.
		// Devolvemos un array mensajes, 
		//   [comprobaciones] [0]
		//			[tipo] -> (string) Indicando el tipo mensaje (dargen,warning,info,success)
		//			[dato] -> (string-json) Dato que podemos necesitar... como propiedad,consulta, o lo que pueda necesitar.
		//			[mensaje] ->(string) Texto que podemos mostrar al usuario.
		
		// ---- 1ª Comprobar que el tipo iva exist en la tabla ivas. ---------  //
		$comprobarIva = $this-> ComprobarIva($this->iva);
		if (gettype($comprobarIva['error'])==='array'){
			$this->SetComprobaciones($error);
		} 
		$this->iva = $comprobarIva['iva']; // El iva por defecto (0.00) en caso de error 
		
		
	}
	
	function ComprobarIva($iva){
		// @ Objetivo:
		// Comprobar si el iva es correcto
		// @ Parametros:
		// 		$iva -> int que es el valor del iva, no el id.
		// @ Devuelve:
		// 	(array) con error (array) en caso de que falle, o string 'Ok' indicando que no fallo.
		$ivas = $this->GetTodosIvas();
		$r = 'KO';
		foreach ($ivas as $item){
			if ($item['iva'] === $iva){
				// Quiere decir que no existe el iva.
				$r = 'OK';
				break;
			}
		}
		if ($r === 'KO'){
			$error = array ( 'tipo'=>'warning',
								 'dato' => $Sql,
								 'mensaje' => 'Cambiamos el iva, ya que no existe el tipo con iva '.$this->iva.' ponemos iva por defecto, mientras no lo guardes no lo arreglas.'
								 );
			$iva = 0.00;
		}
		if (!isset($error)){
			$error = $r;
		}
		$respuesta= array('error'=>$error,
						  'iva'=> $iva);
		
		return $respuesta;
	}
	
	// -----  OTROS FUNCIONES NECESARIAS ------ //
	
	public function GetTodosIvas(){
		$CTivas = new ClaseTablaIva($this->db);
		$ivas = $CTivas->todoIvas();
		return $ivas;
	}
	
	public function GetUnIva($id){
		$CTivas = new ClaseTablaIva($this->db);
		$iva = $CTivas->getIva($id);
		return $iva;
	}
	
	public function ObtenerTiendaPrincipal(){
		// Objetivo:
		// Obtener la tienda principal y guardarla en propiedad tienda.
		// [NOTA] -> Asi no hace falta mandar siempre idTienda
		$Sql = "SELECT idTienda FROM `tiendas` WHERE `tipoTienda`='Principal'";
		$respuesta = $this->Consulta($Sql);
		if ($respuesta['NItems'] === 1){
			// Quiere decir que obtuvo un dato solo..
			$this->idTienda = $respuesta['Items'][0]['idTienda'];
		}
	}
	
	public function ObtenerDatosProvPredeter($id_proveedor){
		// @ Objetivo:
		// Obtener los datos principal del proveedor del que indiquemos
		// @ Parametro: 
		//   $id_proveedor -> (int) Id del proveedor 
		if (isset($id_proveedor) && $id_proveedor > 0 ) {
			$Sql = 'SELECT * FROM `proveedores` WHERE `idProveedor`='.$id_proveedor;
			$respuesta = $this->Consulta($Sql);
			if ($respuesta['NItems'] === 1){
				// Solo puede obtener un proveedor.
				$this->proveedor_principal = $respuesta['Items'][0];
			} else {
				// Hubo error  ( No puede suceder nunca que sea resultado mas 1...
				if ($respuesta['NItems'] === 0){
					// No encontro
					$error = array ( 'tipo'=>'warning',
									 'dato' => 'idProveedor:'.$id_proveedor,
									 'mensaje' => 'No fue encontrado el proveedor, con id:'.$id_proveedor.' ponemos 0 por defecto, mientras no lo guardes no lo arreglas.'
									 );
					$this->SetComprobaciones($error);
				} 
				
			}
		}
		
	}
	
	public function ObtenerCostesProveedores($id){
		// @ Objectivo: 
		// Obtener los costes de los proveedores para un producto.
		// @ Parametros:
		// 	  $id -> (int) Id del producto a buscar.
		$Sql= 'SELECT art_prov.*, pro.nombrecomercial, pro.razonsocial  FROM `articulosProveedores` AS art_prov LEFT JOIN proveedores AS pro ON pro.idProveedor = art_prov.idProveedor WHERE art_prov.idArticulo ='.$id;
		$respuesta = $this->Consulta($Sql);
		if ($respuesta['NItems'] > 0){
			// Solo puede obtener un proveedor.
			$this->proveedores_costes = $respuesta['Items'];
		} else {
			// Hubo error - No encontro
			$error = array ( 'tipo'=>'success',
							 'dato' => 'idArticulo:'.$id,
							 'mensaje' => 'No encontro ningún coste para es producto.'
							 );
			$this->SetComprobaciones($error);
		}
		
	}
	
	
	public function ObtenerReferenciasTiendas($id){
		// Objetivo:
		// Obtener los referencias de todas tiendas de ese producto y los precios con iva y sin iva de esas tiendas.
		// @Parametro
		// $id -> (int) Id del producto.
		$Sql = 'SELECT ati.crefTienda, ati.idTienda, ati.idVirtuemart, t.tipoTienda , t.dominio '
            .' FROM `articulosTiendas` as ati '
			.' LEFT JOIN tiendas as t ON t.idTienda = ati.idTienda '
			.' WHERE  ati.idArticulo= '.$id;
		$consulta = $this->Consulta($Sql);
		// Aqui podemos obtener varios registros.
		if (isset($consulta['Items'])){
			$this->ref_tiendas = $consulta['Items'];
		} else {
            $this->ref_tiendas = array();
        }
	}
	
	public function ObtenerFamiliasProducto($id){
		// Objetivo:
		// Obtener idFamilias y nombre de familia de ese producto
		// @Parametro
		// $id -> (int) Id del producto.
		$Sql = 'SELECT f.*, artfam.idFamilia FROM `familias` as f '
			.' LEFT JOIN articulosFamilias as artfam ON f.idFamilia = artfam.idFamilia '
			.' WHERE artfam.idArticulo= '.$id;
        
        //~ $Sql = 'SELECT f.idFamilia FROM `familias` as f '
			//~ .' LEFT JOIN articulosFamilias as artfam ON f.idFamilia = artfam.idFamilia '
			//~ .' WHERE artfam.idArticulo= '.$id;
		$consulta = $this->Consulta($Sql);
		// Aqui podemos obtener varios registros.
		if (isset($consulta['Items'])){
			$this->familias = $consulta['Items'];
		}
	}
	
    public function ObtenerHistoricoPrecio($id){
        //@Objetivo:
        //Obtener historico precios de un producto
        //@Parametro:
        //$id -> (int) Id del producto
        $sql=' SELECT * FROM historico_precios WHERE idArticulo='.$id;
        $consulta = $this->Consulta($sql);
        if(isset($consulta['Items'])){
            $this->productos_historico=$consulta['Items'];
        }
        
    }
    
	public function ObtenerCodbarrasProducto($id){
		// Objetivo:
		// Obtener codbarras para ese producto.
		// @Parametro
		// $id -> (int) Id del producto.
		$codbarras= array();
		$Sql = 'SELECT codBarras FROM articulosCodigoBarras WHERE idArticulo='.$id;
		$consulta = $this->Consulta($Sql);
		// Aqui podemos obtener varios registros.
		if (isset($consulta['Items'])){
			foreach ($consulta['Items'] as $cod){
				$codbarras[] = $cod['codBarras'];
			};
		}
		// Si no hay resultado devolvemos array vacio igualmente... 
		$this->codBarras = $codbarras;
		 
	}
	
	public function ObtenerCrefTiendaPrincipal(){
		// Objetivo
		// Es obtener la referencia del producto de la tienda principal.		
		if (gettype($this->ref_tiendas) === 'array'){
			foreach ($this->ref_tiendas as $item){
				if ($item['idTienda'] === $this->idTienda){
					// Es la tienda que tenemos com principal en propiedades de clase.
					$this->cref_tienda_principal = $item['crefTienda'];
					break;
				}
			}
		}
	}
	
	public function SetComprobaciones($error){
		// Objetivo 
		// Añadir al array una comprobacion.
		if (gettype($error) === 'array'){
			// Es un array , ahora deberíamos comprobar que el tipo es corecto...:-)
			// De momento no lo hago..
			array_push($this->comprobaciones,$error);
		}
		
	}
	
	public function InsertarPreciosVentas($datos){
		// @ Objectivo
		// Modificar precio venta del producto indicado para tienda indicada.
		// @ Parametro:
		// 		$datos -> (array) con los datos insertar.
		//					$dato['id'] -> (int) de producto.
		//					$dato['idTienda']-> (int) de la tienda a la que se quiere aplicar los precios.
		// 					$dato['pvpSiva']-> (float) Precio sin iva, solo 2 decimales,
		//					$dato['pvpCiva']-> (float) Precio con iva, solo 2 decimales.
		// 	Los precios son con dos decimales, ya que solo vamos utilizar uno el otro es aproximado.
		//  Esto puede ser valido para algunas tienda, pero no para otras, ya que no otras necesitan los dos datos y correctos.
		// @ Devuelve:
		// 		$respuesta -> (array) donde envimamos la cantidad de registro insertados


        if ($datos['id'] >0 ){
			// Solo compruebo que se aun numero y superior a 0;
			$sql= 'INSERT INTO `articulosPrecios`(`idArticulo`, `pvpCiva`, `pvpSiva`, `idTienda`) VALUES ('.$datos['id'].',"'.$datos['pvpCiva'].'","'.$datos['pvpSiva'].'",'.$datos['idTienda'].')';
			$respuesta= array();
			$DB = $this->db;
					$smt = $DB->query($sql);
					if ($smt) {
						$respuesta['Afectados'] = $DB->affected_rows;
						// Hubo resultados
					} else {
						// Quiere decir que hubo error en la consulta.
						$error = array ( 'tipo'=>'danger',
									 'mensaje' =>'Error al insertar en tabla Articulos '.json_encode($DB->connect_errno),
									 'dato' => $sql
								);
						$respuesta['error'] = $error;
					}
					$respuesta['consulta'] = $sql;

		} else {
			// El id es 0 por lo que no añadimos nada y enviamos error.
			$error = array ( 'tipo'=>'danger',
									 'mensaje' =>'El id del producto '.$datos['id'].' enviado es incorrecto',
									 'dato' => $datos
								);
						$respuesta['error'] = $error;
			
		}
		
		return $respuesta;
	
		
		
	}
}

?>
