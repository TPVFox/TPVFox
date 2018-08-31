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

// [INICIALIZACION DE VARIABLES ]
$error = array(); //Control de error.
$num_items = array(); // Array control de totales de productos tpv,web y differencias
$IdVirt = array(); // Array para obtener todos los id Virtuemart tanto tpv como web.
$Productos = array() // Array donde tendremos tanto los productos tpv, como los de la web.
?>

<!DOCTYPE html>
<html>
<head>
<title>Solicitud de datos por php curl</title>
<?php
include './../../head.php';
include ("./../../controllers/Controladores.php");
include_once ("./funciones.php");	


// [OBTENEMOS DATOS CONEXION DE TIENDA QUE RECIBIMOS POR GET]
if (isset($_GET['tienda_importar'])){
	$ObtenerTienda = ObtenerTiendaImport($BDTpv,$_GET['tienda_importar']);
	$tienda_importar = $ObtenerTienda['items'][0];
	
}
// [VARIABLE DE CONEXION]
$ruta =$tienda_importar['dominio'].'/administrator/apisv/tareas.php';
$parametros = array('key' 		=>$tienda_importar['key_api'],
					'action'	=>'sincronizar_virtuemart'
					//~ 'tablaTemporal' =>json_encode($tablasTemporales)
				);
// [CONEXION CON SERVIDOR REMOTO] 
// Primero comprobamos si existe curl en nuestro servidor.
$existe_curl =function_exists('curl_version');
if ($existe_curl === FALSE){
	echo '<pre>';
	print_r(' No exite curl');
	echo '</pre>';
	exit();
}
include ($RutaServidor.$HostNombre.'/lib/curl/conexion_curl.php');
// Obtenemos arrays ( $repuestas , $error si lo hubiera.. )

// [OBTENEMOS LISTADO PRODUCTOS SERVIDOR ]
$Productos['Servidor'] = $respuesta['Obtener']['items'];
unset($respuesta); // Eliminamos de memoria lo sobrante....

// [OBTENEMOS LISTADO PRODUCTOS TPV ]
$ListadoProductosTpv = ListadoProductosCompletoTPV($BDTpv);
$Productos['Tpv'] = $ListadoProductosTpv ['items'];
// [CREAMOS ARRAY NECESARIOS PARA OBTENER LAS DIFERENCIAS]

// Contamos cuantos hay
$num_items['Web'] = count($Productos['Servidor']);
$num_items['Tpv'] = count($Productos['Tpv']);

// Creamos array con los ids de virtuemar de ambas BD
$IdVirt['Tpv'] = array_column($Productos['Tpv'],'idVirtuemart');
$IdVirt['Web'] = array_column($Productos['Servidor'],'idVirtuemart');


// AHORA OBTENEMOS LAS DIFERENCIAS  NUEVOS,MODIFICADOS O ELIMINADOS.
// RECUERDA:
// Hay dos procesos para obtener los eliminados, ya que se pueden aparecer eliminados en la web, como en tvp, o visto de 
// otra forma, cuando hay nuevos en tpv o la web, el primero de momento no es posible.. por lo que probablemente es un eliminado
// en la web.
// Aunque esto se puede saber si en tpv hay relación con virtuemart, quiere decir que elimino.

$diferencias =ObtenerDiferencias($Productos,$IdVirt);

// Creamos contadores para mostrar resumen
$Cont_Nuevo = 0; // Nuevo en web
$Cont_Modificado = 0; // Modificado
$Cont_Eliminado_web =0 ; // Eliminado en web
$Cont_Nuevo_tpv = 0; // Nuevo en la web
//~ echo '<pre>';
//~ print_r($diferencias);
//~ echo '</pre>';

echo '<script type="application/javascript">';
?>
	// Variable globales javascritp
	var configuracion = []; 
	var ProductosNuevosWeb = []; 
	var ProductosNuevosTpv = []; 
	var ProductosEliminadoWeb = []; 
	var ProductosModificadosWeb = []; 
	var ProductosTpv = [];
	var Diferencias = [];

<?php
foreach ($diferencias as $key => $diferencia){
	if ($diferencia['tipo'] === 'Nuevo_web'){
		// Añado a variable global de javascript y boton
		echo "ProductosNuevosWeb.push(".json_encode($diferencia['Servidor'],true).");";
		// Ojo a este boton hay que obtener id_tienda_actual
		$tienda_actual = 1;
		$diferencias[$key]['boton'] ='<a onclick="AnhadirProductoTpv('.$key.','.$tienda_importar['idTienda'].','.$tienda_actual.',ProductosNuevosWeb['.$Cont_Nuevo.'])"'.' href="#Nuevo_web" class="btn btn-primary">Crear en Tpv</a>';
		$Cont_Nuevo++;	
	}
	if ($diferencia['tipo'] === 'Modificado'){
		// Añado a variable global de javascript y boton
		echo "ProductosModificadosWeb.push(".json_encode($diferencia['Servidor'],true).");";
		echo "ProductosTpv.push(".json_encode($diferencia['Tpv'],true).");";
		echo "Diferencias.push(".json_encode($diferencia['Diferencia'],true).");";
		// Ojo a este boton hay que obtener id_tienda_actual
		$tienda_actual = 1;
		$diferencias[$key]['boton'] ='<a onclick="ModificadoProductoTpv('.$key.','.$tienda_importar['idTienda'].','.$tienda_actual.',ProductosModificadosWeb['.$Cont_Modificado.'],ProductosTpv['.$Cont_Modificado.'],Diferencias['.$Cont_Modificado.'])"'.' href="#Modificado" class="btn btn-success">Modificar en Tpv</a>';
		$Cont_Modificado++;
	}
	if ($diferencia['tipo'] === 'Eliminado_web'){
		// Ojo a este boton hay que obtener id_tienda_actual
		//[PENDIENTE AÑADIR JAVASCRIPT]

		$tienda_actual = 1;
		$diferencias[$key]['boton'] ='<a onclick="EliminadoProductoWeb('.$key.','.$tienda_importar['idTienda'].','.$tienda_actual.',ProductosEliminadoWeb['.$Cont_Eliminado_web.'],ProductosTpv['.$$Cont_Eliminado_web.'],Diferencias['.$$Cont_Eliminado_web.'])"'.' href="#Eliminado_web" class="btn btn-danger">Eliminar referencia en tpv de web</a>';
		$Cont_Eliminado_web ++;
	}
	if ($diferencia['tipo'] === 'Nuevo_tpv'){
		//[PENDIENTE AÑADIR JAVASCRIPT]
		// Ojo a este boton hay que obtener id_tienda_actual
		$tienda_actual = 1;
		$diferencias[$key]['boton'] ='<a onclick="AnhadirProductoWeb('.$key.','.$tienda_importar['idTienda'].','.$tienda_actual.',ProductosNuevoTpv['.$Cont_Nuevo_tpv.'],ProductosTpv['.$Cont_Nuevo_tpv.'],Diferencias['.$Cont_Nuevo_tpv.'])"'.' href="#Nuevo_tpv" class="btn btn-warning">Crear producto en Web</a>';
		$Cont_Nuevo_tpv	++;
	}

	
}
echo '</script>';


//~ echo '<pre>';
//~ print_r($diferencias);
//~ echo '</pre>';
?>
<!-- Lo depues de añadir las variables. -->
<script src="<?php echo $HostNombre; ?>/modulos/mod_importar_virtuemart/funciones.js"></script>

</head>
 <body>
	<?php
	//~ include './../../header.php';
     include_once $URLCom.'/modulos/mod_menu/menu.php';
		// Debug
	
	// [ SI HUBO ALGUN ERROR NO DEJAMOS CONTINUAR ]
	if ($error !==''){
		echo '<div><pre>'.print_r(json_decode($error)).'</pre></div>';
		exit;		
	}
	?>

	  <div class="container">
	  <h1>Resumen</h1>
	  <p>Articulos encontrados en la Web:<?php echo $num_items['Web'];?></p>
	  <p>Articulos encontrados en Tpv:<?php echo $num_items['Tpv'];?></p>
	  <p>Productos Nuevos en Web:<?php echo $Cont_Nuevo ;?></p>
	  <p>Productos Modificados:<?php echo $Cont_Modificado;?>(<a title="No sabes donde, si web o tpv, o los dos">*</a>)</p>
	  <p>Productos Nuevos en Tpv:<?php echo $Cont_Nuevo_tpv;?></p>
	  <p>Productos Eliminados en Web:<?php echo $Cont_Eliminado_web;?></p>

		<?php
	echo '<div class="row">';

	echo '<h1>Diferencias encontradas</h1>';
	foreach ($diferencias as $key => $diferencia){
		echo '<div id="fila'.$key.'" class="col-md-12" style="border-top:1px solid;">';
		echo 	'<div>';
			echo '<h3>Diferencia encontrada '.$key.': '.$diferencia['tipo'].'</h3>';
			if (isset($diferencia['boton'])){
				echo $diferencia['boton'];	
			}
			echo'</div>';
			echo '<div class="col-md-4">';
				echo '<h4>Servidor Web</h4>';
				echo '<pre>';
				if (isset($diferencia['Servidor'])){
					print_r($diferencia['Servidor']);
				}
				echo '</pre>';
			echo '</div>';
				echo '<div class="col-md-4">';
				echo '<h4>tpv</h4>';
				echo '<pre>';
				if (isset($diferencia['Tpv'])){
					print_r($diferencia['Tpv']);
				}
				echo '</pre>';
			echo '</div>';
			echo '<div class="col-md-4">';
				echo '<h4>Differencia</h4>';
				echo '<pre>';
				print_r($diferencia['Diferencia']);
				echo '</pre>';
			echo '</div>';
		echo '</div>';
	}
	?>
	</div>



</div>        
        
 </body>
</html>
