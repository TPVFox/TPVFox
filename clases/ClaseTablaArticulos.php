<?php
/* Propiedades en minuscula.
 * Metodos en UpperCamelCase
 * */


include ($RutaServidor.$HostNombre.'/clases/ClaseTablaIva.php');
include ($RutaServidor.$HostNombre.'/clases/ClaseTablaFamilias.php');

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
	public $estado ='Activo'; // Estado del producto por defecto en tienda principal: Activo, Eliminado
	public $fecha_creado;
	public $fecha_modificado;
	public $codBarras; // Array de codbarras para ese producto.
	public $precios_tiendas; // Array de referencias de las tiendas.
	public $proveedores; // Array de referencias de proveedores.
	public $familias; // Array de familias de ese producto
	public $proveedor_principal; // Array con datos del proveedor principal
	public $comprobaciones = array(); // Array  de mensajes ( ver metodo de comprobaciones)
	
	
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
	
	private function Consulta($sql){
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
			$array['error'] = $db->error;
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
					
			if (isset ($consulta['NItems'])){
				if ($consulta['NItems'] === 1){
					$respuesta = $consulta['Items'][0];
					$this->MontarProducto($respuesta);
				} else {
				// Hubo un error o encontro mas de uno o 0, es decir no existe.
						if ($consulta['NItems'] > 1){
							$error = array ( 'tipo'=>'danger',
									 'dato' =>$consulta,
									 'mensaje' => 'Encontro mas de un articulo con es id, ponerse en contacto con programador'
									 );
							array_push($this->comprobaciones,$error);
						} else {
							$error = array ( 'tipo'=>'danger',
							 'dato' =>$Sql,
							 'mensaje' => 'No encontro ningun registro con ese ID:'.$id.' , ponerse en contacto con programador'
							 );
							array_push($this->comprobaciones,$error);
						}
					}
				}
		} else {
			// Se monta tanto nuevo , como si con id.
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
		if (count($datos) === 0){
			// Como no había datos es nuevo.
			$this->estado = 'Nuevo';
		}
		// Recuerda que idArticulo es 0 por defecto.
		
		if ($this->idArticulo !==0){
			// Obtenemos referencias y datos de las otras tiendas con sus precios para ese producto	
			$this->ObtenerReferenciasTiendas($this->idArticulo); 
			// Obtenemos referencia del producto para tienda principal.
			$this->ObtenerCrefTiendaPrincipal();
			// Obtenemos familias a las que pertenece ese producto
			$this->ObtenerFamiliasProducto($this->idArticulo);
			// Obtenemos Codbarras a las que pertenece ese producto
			$this->ObtenerCodbarrasProducto($this->idArticulo);
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
					$error =array('error'=>'No puedo continuar por hay error grabe',
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
	
	
	public function GetNumRows(){
		return $this->num_rows;
	}
	public function GetIdTienda(){
		return $this->idTienda;
	}
	
	
	public function Comprobaciones(){
		// Objetivo:
		// Comprobar si los datos que tiene son correctos y cuales faltan.
		// Devolvemos un array mensajes, 
		//   [comprobaciones] [0]
		//							[tipo] -> Indicando el tipo mensaje (dargen,warning,info,success)
		//							[dato] -> Dato que podemos necesitar... como propiedad,consulta, o lo que pueda necesitar.
		//							[mensaje] -> Texto que podemos mostrar al usuario.
		
		// ---- 1ª Comprobar que el tipo iva exist en la tabla ivas. ---------  //
		$ivas = $this->GetTodosIvas();
		$r = 'KO';
		foreach ($ivas as $item){
			if ($item['iva'] === $this->iva){
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
			$this->iva = 0.00;
			array_push($this->comprobaciones,$error);
		}
		
		
	}
	
	// -----  OTROS FUNCIONES NECESARIAS ------ //
	
	public function GetTodosIvas(){
		$CTivas = new ClaseTablaIva($this->db);
		$ivas = $CTivas->todoIvas();
		return $ivas;
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
	
	public function ObtenerDatosProvPredeter($id){
		// Objetivo:
		// Obtener los datos del proveedor del que indiquemos
		$Sql = 'SELECT * FROM `proveedores` WHERE `idProveedor`='.$id;
		$respuesta = $this->Consulta($Sql);
		if ($respuesta['NItems'] === 1){
			// Quiere decir que obtuvo un dato solo..
			$this->proveedor_principal = $respuesta['Items'][0];
		}
		
	}
	
	public function ObtenerReferenciasTiendas($id){
		// Objetivo:
		// Obtener los referencias de todas tiendas de ese producto y los precios con iva y sin iva de esas tiendas.
		// @Parametro
		// $id -> (int) Id del producto.
		$Sql = 'SELECT ati.crefTienda, ati.idTienda, ati.idVirtuemart, prec.pvpCiva, prec.pvpSiva, t.tipoTienda , t.dominio FROM `articulosTiendas` as ati '
			.' LEFT JOIN articulosPrecios as prec ON prec.idTienda = ati.idTienda '
			.' LEFT JOIN tiendas as t ON t.idTienda = ati.idTienda '
			.' WHERE  ati.idArticulo= '.$id.' GROUP BY prec.idTienda';
		$consulta = $this->Consulta($Sql);
		// Aqui podemos obtener varios registros.
		$this->ref_tiendas = $consulta['Items'];
	}
	
	public function ObtenerFamiliasProducto($id){
		// Objetivo:
		// Obtener idFamilias y nombre de familia de ese producto
		// @Parametro
		// $id -> (int) Id del producto.
		$Sql = 'SELECT f.*, artfam.idFamilia FROM `familias` as f '
			.' LEFT JOIN articulosFamilias as artfam ON f.idFamilia = artfam.idFamilia '
			.' WHERE artfam.idArticulo= '.$id;
		$consulta = $this->Consulta($Sql);
		// Aqui podemos obtener varios registros.
		$this->familias = $consulta['Items'];
		
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
		foreach ($consulta['Items'] as $cod){
			 $codbarras[] = $cod['codBarras'];
		};
		$this->codBarras = $codbarras;
	}
	
	public function ObtenerCrefTiendaPrincipal(){
		// Objetivo
		// Es obtener la referencia del producto de la tienda principal.		
		foreach ($this->ref_tiendas as $item){
			if ($item['idTienda'] === $this->idTienda){
				// Es la tienda que tenemos com principal en propiedades de clase.
				$this->cref_tienda_principal = $item['crefTienda'];
				break;
			}
		}
	}
}

?>
