<?php 

$pulsado = $_POST['pulsado'];

include_once ("./../../configuracion.php");
include_once ("./../../inicial.php");
include_once ("./funciones.php");
include_once "../../clases/articulos.php";
include_once "clases/modulo_etiquetado.php";

$CArticulos=new Articulos($BDTpv);
$CEtiquetado= new Modulo_etiquetado($BDTpv);

$respuesta=array();
switch ($pulsado) {
	case 'repetirProductos':
	//@OBjetivo: Repetir un productos tantas veces como el usuario indicara
	//@Parametros:
	//Unidades: el número de veces a repetir el producto
	//IdProducto: es el id del producto a repetir
	//IdTienda: id de la tieneda en la que estamos(lo necesitamos por que en la función vamos a buscar los datos de ese producto)
	//fechaCad: fecha de caducidad del producto (todos los productos de un lote tendrán la misma fecha de caducidad)
	//tipo: puede ser unidades o peso
	//@Devuelve: 
	//el html con las líneas generadas
		$unidades=$_POST['unidades'];
		$idProducto=$_POST['idProducto'];
		$idTienda=$_POST['idTienda'];
		$fechaCad=$_POST['fechaCad'];
		$tipo=$_POST['tipo'];
		$numProd=0;
		if(isset($_POST['productos'])){
			$numProd=count($_POST['productos']);
		}
		if($idProducto>0){
			$htmlProductos=repetirLineasProducto($unidades, $idProducto, $BDTpv, $idTienda, $fechaCad, $numProd, $tipo);
			$respuesta['numProd']=$numProd;
			$respuesta['productos']=$htmlProductos['productos'];
			$respuesta['html']=$htmlProductos['html'];
		}else{
			$respuesta['error']="No has seleccionado el producto";
		}
		
	break;
	case 'addEtiquetadoTemporal':
	//@Objetivo: Añadir/Modificar etiqueta temporal
	//@Parámetros: 
	//$_POST: todos los datos que enviamos por ajax 
	//@Funcionamiento: Si el idTemporal es mayor que 0 , se modifica el temporal, si no se crea.
	//Si existe el idReal (o sea que ya hay un lote creado) modifica el estado a "Sin guardar"
	//@Devuelve:
	//El idTemporal, lo errores y los productos
		$idTemporal=0;
		$idReal=0;
		$existe=0;
		$productos=array();
		if(isset($_POST['productos'])){
			$productos=$_POST['productos'];
		}
		if(isset($_POST['idReal'])){
			$idReal=$_POST['idReal'];
		}
		$productos=json_encode($productos, true);
		$respuesta['productos']=$productos;
		if(isset($_POST['idTemporal'])){
			$idTemporal=$_POST['idTemporal'];
		}
		if($idTemporal>0){
			//MOdificar temporal
			$modif=$CEtiquetado->modificarTemporal($_POST, $productos, $idTemporal);
			if(isset($modif['error'])){
				$respuesta['error']=$modif['error'];
				$respuesta['consulta']=$modif['consulta'];
			}
		}else{
			//crear temporal y devolver idTemporal
			$nuevo=$CEtiquetado->addTemporal($_POST, $productos);
			if(isset($nuevo['error'])){
				$respuesta['error']=$nuevo['error'];
				$respuesta['consulta']=$nuevo['consulta'];
			}else{
				$idTemporal=$nuevo['id'];
			}
			
		}
		if($idReal>0){
			$modReal=$CEtiquetado->modifEstadoReal("Sin guardar", $idReal);
			if(isset($modReal['error'])){
				$respuesta['error']=$modReal['error'];
				$respuesta['consulta']=$modReal['consulta'];
			}
		}
		$respuesta['existe']=$existe;
		$respuesta['idTemporal']=$idTemporal;
		$respuesta['productos']=$_POST['productos'];
	
	break;
	case 'buscarProducto':
	//@Objetivo:
	//Buscar los datos del producto y comprueba que la referencia del producto no tenga - si es así envia un error
	
		$valor=$_POST['valor'];
		$caja=$_POST['caja'];
		$idTienda=$_POST['idTienda'];
		if($caja=='id_producto'){
			$buscarId=$CArticulos->datosArticulosPrincipal($valor, $idTienda);
			if(isset($buscarId['error'])){
				$respuesta['error']=$buscarId['error'];
				$respuesta['consulta']=$buscarId['consulta'];
			}else{
				$comprobar = strpos($buscarId['crefTienda'],'-');
				if($comprobar==false){
					$respuesta['Nitem']=1;
					$respuesta['datos']=$buscarId;
				}else{
					$respuesta['error']='Error';
					$respuesta['consulta']='La referencia del producto tiene que ser *****';
				}
				
			}
		}else{
			$buscarTodo=$CArticulos->buscarPorNombre($valor, $idTienda);
			if(isset($buscarTodo['error'])){
				$respuesta['error']=$buscarTodo['error'];
				$respuesta['consulta']=$buscarTodo['consulta'];
			}else{
				$html=htmlProductos($valor, $buscarTodo);
				$respuesta['html']=$html['html'];
			}
		}
	
	break;
	case 'modificarCodigoBarras':
	//@Objetivo:
	//Modificar el código de barras según el tipo y referencia
		$tipo=$_POST['tipo'];
		$producto=$_POST['producto'];
		switch($tipo){
			case 1:
				$codigoBarras=codigoBarrasUnidades($producto['crefTienda'], $producto['peso']);
			break;
			case 2:
				$codigoBarras=codigoBarrasPeso($producto['crefTienda'],  $producto['peso']);
			break;
		}
		$respuesta['codBarras']=$codigoBarras;
	
	break;
	
	case 'contarEtiquetas':
	//@Objetivo:
	//Contar la cantidad de etiquetas que se van a imprimir para mostrar un alert
		$lotes=$_POST['lotes'];
		$etiquetas=0;
		foreach($lotes as $lote){
			$etiquetaReal=$CEtiquetado->datosLote($lote);
			$productos=json_decode($etiquetaReal['productos'], true);
			$etiquetas=$etiquetas+count($productos);
		}
		$respuesta['etiquetas']=$etiquetas;
	break;
	case 'imprimirEtiquetas':
	//@OBjetivo: imprimir las etiquetas
		$lotes=$_POST['lotes'];
		$nombreTmp="etiquetas.pdf";
		include('../../lib/tcpdf/tcpdf.php');
		include ('../../clases/imprimir.php');
		include('../../controllers/planImprimirCodBarras.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$respuesta=$ficheroCompleto;
	
	break;
	
	
}
 echo json_encode($respuesta);
 return $respuesta;
?>
