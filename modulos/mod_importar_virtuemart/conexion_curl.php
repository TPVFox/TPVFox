<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	Importar ficheros de web Joomla
 * */	

 // Gestion de errores
 // error = [String] No permito continuar.
 // error_warning =  Array() - Si permito continuar pero con restrinciones.

 // Datos que se necesita para la conexion:
 // ruta: http://webJoomla/rutaApi
 // key: Clave introduccida en plugin de instalacion de Joomla
 // action: Clave a accion.
 
?>

<!DOCTYPE html>
<html>
<head>
<?php
$error = array(); //Control de error.
include './../../head.php';
include ("./../../controllers/Controladores.php");
include_once ("./funciones.php");	


// [OBTENEMOS DATOS CONEXION DE TIENDA QUE RECIBIMOS POR GET]
if (isset($_GET['tienda_importar'])){
	$ObtenerTienda = ObtenerTiendaImport($BDTpv,$_GET['tienda_importar']);
	$tienda_importar = $ObtenerTienda['items'][0];
	
}

$ruta =$tienda_importar['dominio'].'/administrator/apisv/tareas.php';
$parametros = array('key' 		=>$tienda_importar['key_api'],
					'action'	=>'sincronizar_virtuemart'
					//~ 'tablaTemporal' =>json_encode($tablasTemporales)
				);

//~ $clave = 'key='.$tienda_importar['key_api'];
//~ $action = 'action=sincronizar_virtuemart';
// Obtenemos los datos de la conexion con servidor remoto.

//Lo primerito, creamos una variable iniciando curl, pasándole la url
$ch = curl_init($ruta);
 
//especificamos el POST (tambien podemos hacer peticiones enviando datos por GET
curl_setopt ($ch, CURLOPT_POST, 1);
 
//le decimos qué paramáetros enviamos (pares nombre/valor, también acepta un array)

curl_setopt ($ch, CURLOPT_POSTFIELDS, $parametros);
 
//le decimos que queremos recoger una respuesta (si no esperas respuesta, ponlo a false)
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
 
//recogemos la respuesta
$respuesta = curl_exec ($ch);
 
//o el error, por si falla
$error = curl_error($ch);
 
//y finalmente cerramos curl
curl_close ($ch);
// [ OBTENEMOS ARRAY DE DATOS DE TMP ARTICULOS COMPLETA ]
$respuesta = json_decode($respuesta,true);
//~ echo '<pre>';
//~ print_r($respuesta);
//~ echo '</pre>';
//~ echo '<pre>';
//~ print_r($error);
//~ echo '</pre>';
$tmp_articulos = $respuesta['Obtener']['items'];
unset($respuesta); // Eliminamos de memoria.
$articulos = ListadoProductosCompletoTPV($BDTpv);
// Ahora comparamos lo que tenemos en tpv y lo que hay en la web
// Contamos cuantos hay
$Num_itemsWeb = count($tmp_articulos);
$Num_itemsTpv = count($articulos['items']);
$idVirtuemart_tpv = array_column($articulos['items'],'idVirtuemart');//$idVirtuemart_web-> Array columna de idVirtuemart de tpv

$idVirtuemart_web = array_column($tmp_articulos,'idVirtuemart');// $idVirtuemart_web-> Array columna de idVirtuemart de Web 




?>
<title>Solicitud de datos por php curl</title>
<script type="application/javascript">
	// Objeto configuracion
	var configuracion = []; 
	var ProductosNuevosWeb = []; 
</script>
</head>
 <body>
	<?php
	include './../../header.php';
		// Debug
	
	
	if ($error !==''){
	echo '<div><pre>'.print_r(json_decode($error)).'</pre></div>';
	exit;		
	
	}
	?>
	  <div class="container">
	  <h1>Resumen</h1>
	  <p>Articulos encontrados en la Web:<?php echo $Num_itemsWeb;?></p>
	  <p>Articulos encontrados en Tpv:<?php echo $Num_itemsTpv;?></p>
		<?php
		echo '<div class="row">';

		echo '<h1>Diferencias encontradas en Web</h1>';
		$i = 0;
		$Cont_Nuevo = 0;
		$Nuevo = array();
		foreach ($tmp_articulos as $tmp_articulo){
			$diff = array();
			$bottones = array();
			// Busco idVirtuemart de tabla Web en array columna de tpv,($idVirtuemart_web-> Array columna de idVirtuemart de tpv)
			$array_search = array_search($tmp_articulo['idVirtuemart'],$idVirtuemart_tpv);
			if (gettype($array_search) === 'boolean'){
				// Quiere decir que no encontro, lo mas probable es que se un producto nuevo
				// ya que en tvp de momento no permitimos eliminar. :-)
				$diff = array('idVirtuemart' => $tmp_articulo['idVirtuemart'],
							  'error' => 'Eliminado en tpv o creado en la web'
						);
				// Añadimos a array Nuevos el articulo nuevo encontrado.
				$Nuevo[$Cont_Nuevo] = $tmp_articulo;
				// Ahora añadimos a Javascript datos ProductosNuevosWeb
				echo '<script type="application/javascript">';
				echo "ProductosNuevosWeb.push(".json_encode($tmp_articulo,true).");";
				echo '</script>';
				
				$bottones = array(
						'Nuevo' =>'<a onclick="AnhadirProducto('.$i.','.$tienda_importar['idTienda'].','.'1'.',ProductosNuevosWeb['.$Cont_Nuevo.'])"'.' href="#Nuevo" class="btn btn-primary">Crear en Tpv</a>'
						);
				$Cont_Nuevo++;
				}else{
				// Ahora creamos un array para comparar sin idArticulo, ya que sino siempre no daría diferencia.
				$item_tpv_sin_idVirtuemart = $articulos['items'][$array_search];
				unset($item_tpv_sin_idVirtuemart['idArticulo']);
				
				$diff = array_diff($item_tpv_sin_idVirtuemart, $tmp_articulo);
				if (isset($diff['fecha_modificado']) && count($diff)===1){
					// Lo elimino ya que solo hay diferencia de modificacion.
					unset($diff['fecha_modificado']);
				} 
			
			}
			if (count($diff)>0){
			// quiere decir que hay differencias.
				echo '<div id="fila'.$i.'" class="col-md-12" style="border-top:1px solid;">';
				echo 	'<div><h3>Diferencia encontrada</h3>Accion'.$i;
				if (isset($bottones['Nuevo'])){
					echo $bottones['Nuevo'];
				}
				echo'</div>';
					echo '<div class="col-md-4">';
						echo '<h4>Web</h4>';
						echo '<pre>';
						print_r($tmp_articulo);
						echo '</pre>';
					echo '</div>';
					echo '<div class="col-md-4">';
						echo '<h4>tpv</h4>';
						echo '<pre>';
						if (!isset($diff['error'])){
							print_r($articulos['items'][$array_search]);
						}
						echo '</pre>';
					echo '</div>';
					echo '<div class="col-md-4">';
						echo '<h4>Differencia</h4>';
						echo '<pre>';
						print_r($diff);
						echo '</pre>';
					echo '</div>';
				echo '</div>';
				$i++;
			}
		}

		echo ' Productos Nuevos son: ' .$Cont_Nuevo;
		echo '</div>';
		
		echo '<div class="row">';
		echo '<h1>Diferencias encontradas en TPV</h1>';
		$i = 0;
		$Cont_Nuevo = 0;
		foreach ($articulos['items'] as $articulo){
			$diff = array();
			// Busco idVirtuemart de tabla TPV en array columna de web ($idVirtuemart_web-> Array columna de idVirtuemart de Web)
			$array_search = array_search($articulo['idVirtuemart'],$idVirtuemart_web);
			if (gettype($array_search) === 'boolean'){
				// Quiere decir que no encontro en tpv el idVirtuemart
				$diff =	 array( 'idArticulo' 	=> $articulo['idArticulo'],
								'idVirtuemart' 	=> $articulo['idVirtuemart'],
								'error'			=> 'Eliminado en la web o creado en tpv'
						);
				} else {
				// Ahora creamos un array para comparar sin idArticulo, ya que sino siempre no daría diferencia.
				$item_tpv_sin_idVirtuemart = $articulo;
				unset($item_tpv_sin_idVirtuemart['idArticulo']);
				$diff = array_diff($tmp_articulos[$array_search],$item_tpv_sin_idVirtuemart);
				
				if (isset($diff['fecha_modificado']) && count($diff)===1){
					// Lo elimino ya que solo hay diferencia de modificacion.
					// y no se cambio nada mas.
					unset($diff['fecha_modificado']);
				} 
				
				
			}
			if (count($diff)>0){
				// Solo mostramos aquellos que son nuevos en tpv o se eliminaron en Web.
				// Ya que los demas ya los mostramos en el anterior listado..
				if (isset($diff['error'])){
				echo '<div class="col-md-12">';
					echo 'Row'.$i;
					echo '<div class="col-md-4">';
						echo '<h4>Web</h4>';
						echo '<pre>';
						// Esto no tiene sentido,ya siempre va existir, es por si algún dia queremos cambiar algo..
						if (!isset($diff['error'])){
							print_r($tmp_articulos[$array_search]);
						}
						echo '</pre>';
					echo '</div>';
					echo '<div class="col-md-4">';
						echo '<h4>tpv</h4>';
						echo '<pre>';
						print_r($articulo);
						echo '</pre>';
					echo '</div>';
					echo '<div class="col-md-4">';
						echo '<h4>Differencia</h4>';
						echo '<pre>';
						print_r($diff);
						echo '</pre>';
					echo '</div>';
				echo '</div>';
				$i++;
				}
			}
		$diff = array();
		}
	
		echo '</div>';

		?>


<!-- Lo cargo al final, ya que durante todo el documento preparamos variables ProductosNuevos y ProductosModificados. -->
<script src="<?php echo $HostNombre; ?>/modulos/mod_importar_virtuemart/funciones.js"></script>

</div>        
        
 </body>
</html>
