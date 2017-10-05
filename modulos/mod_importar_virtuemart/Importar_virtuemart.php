<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	Importar ficheros de web con Virtuemart
 * */	

?>

<!DOCTYPE html>
<html>
<head>
<?php
	include './../../head.php';
	// Creamos variables de los ficheros para poder automatizar el añadir articulos y categorias
	// @ Parametros de array $tablas
	//		nombre		-> Nombre de la tabla tpv
	//		obligatorio	-> Campos que tiene contener datos
	//		campos->	Los campos que obtenemos
	$tablas = 		array(
						'0' => array(
								'nombre'		=>'articulos',
								'obligatorio'	=> array(),
								'campos'		=>array('idArticulo','iva','idProveedor','articulo_name', 'beneficio','costepromedio', 'estado', 'fecha_creado', 'fecha_modificado')
								),
						'1' => array(
								'nombre'		=>'articulosCodigoBarras',
								'obligatorio'	=> array('codBarras'),
								'campos'		=> array('idArticulo', 'codBarras')
								),
						'2' => array(
								'nombre'		=>'articulosPrecios',
								'obligatorio'	=> array(),
								'campos'		=> array('idArticulo','pvpCiva', 'pvpSiva', 'idTienda')
								),
						'3' => array(
								'nombre'		=>'articulosTiendas',
								'obligatorio'	=>array('crefTienda'),
								'campos'		=>array('idArticulo','idTienda','crefTienda')
								)
						);

	// [ANTES CARGAR FUNCIONES JS]
	// Montamos la variables en JAVASCRIPT de nombre_tabla que lo vamos utilizar .js
	?>
	<script type="application/javascript">
	var nombretabla = [];
	// Objeto tabla
	var tablaImpor = [];

	<?php
	foreach ($tablas as $tabla){
		// Llenamos array javascript con los nombres ficheros
		echo "nombretabla.push('".$tabla['nombre']."');";
		echo "tablaImpor.push(".json_encode($tabla).");";
	}
	?>
	</script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_importar_virtuemart/funciones.js"></script>
	<?php
	// Controlamos ( Controllers ... fuera de su sitio ... :-)
	if (isset($Usuario['estado'])){
		if ($Usuario === "Incorrecto"){
			return;	
		}
	}
	?>

</head>
<body>
<?php 
	include './../../header.php';
	include_once ("./funciones.php");
	include ("./../../controllers/Controladores.php");
	// Cargamos el controlador.
	$Controler = new ControladorComun; 
	$Items_tabla = array();
	$sum_Items = 0;
	foreach ( $tablas as $tabla ) {
		$n_tabla = $tabla['nombre'];
		$Items_tabla[$n_tabla] = $Controler->contarRegistro($BDTpv,$n_tabla);
		$sum_Items += (int)$Items_tabla[$n_tabla] ;
	}
	// Ahora creamos la tablas temporales 
	$resp = prepararTablaTempArticulosComp($BDVirtuemart,$prefijoBD);
	if (isset($resp['error'])) {
		$arrayErrores = $resp['error'];
		// Quiere decir que hubo un error al crear la tabla temporal.
		echo '<pre>';
		if (count($arrayErrores['ComprobarCodbarras']['Codbarras_repetidos']) >0){
			// Hay codbarras repetirdos con distintos articulos.
			// Buscamos id y nombre 
			$where = array();
			foreach ($arrayErrores['ComprobarCodbarras']['Codbarras_repetidos'] as $codbarrasRepetido){
				$where[] = 'codbarras="'.$codbarrasRepetido.'"';
			}
			$stringwhere = implode(' OR ',$where);
			$sql = "SELECT `idArticulo`,`crefTienda`,`articulo_name`,`codbarras` FROM `tmp_articulosCompleta` WHERE ".$stringwhere;
			if ($registros = $BDVirtuemart->query($sql)) {
				echo ' Registros que tiene duplicados los codbarras:<br/>';
				while ($fila = $registros->fetch_assoc()) {
					 printf ("%s	%s (%s)\n",$fila['codbarras'],$fila['crefTienda'],$fila['articulo_name']);
					echo '<br/>';
				}
			}else {
				print_r( 'Error en consulta:'.$sql.'<br/>');
				echo $BDVirtuemart->error;
			}
		
		}
		
		print_r($resp);
		echo '</pre>';
		exit(); // No continuamos
	}
	$Num_articulos_virtuemart = $resp['BDVirtuemart']['Num_articulos'];
	
	// Ahora añadimos datos a la tabla tempora creada en BDtpv
	//~ $InsertTablas= prepararInsertRegistroTpv($BDVirtuemart,$BDTpv,$prefijoBD,$tablas);
	//~ echo '<pre>';
		//~ print_r($InsertTablas);
	//~ echo '</pre>';
	//~ 
	
?>

<div class="container">
	<div class="col-md-6">
		<h2>Importación de datos de Virtuemart a TPV.</h2>
		<p> Encontramos en Virtuemart <?php echo $Num_articulos_virtuemart; ?></p>
		<?php 
		if ( $sum_Items > 0){
			// Quiere decir que no puede ser una iniciacion.
			echo '<div class="alert alert-danger">Hay datos BDTpv , ten cuidado por se pueden eliminar el contenido (vaciar) los datos tpv.<br/>
				 Si quieres actualizar no pulses en borra.
				 </div>';
		} else {
			echo '<div class="alert alert-info"> No tienes datos en BDTpv puedes importar</div>';
		}
		?>
		<h3>Las FAXES para la importación de Virtuemart a BDTPV:</h3>
		<?php include_once 'faxesImportar.html';?>
		<script>
			// Cambien id por clase, pero no funciona correctamente
			// ya que pone - a todos cuando uno esta desplegado.
			$(document).ready(function(){
			  $(".collapse.pepe").on("hide.bs.collapse", function(){
				$(".icono-collapse").html('+');
			  });
			  $(".collapse.pepe").on("show.bs.collapse", function(){
				$(".icono-collapse").html('-');
			  });
			});
		</script>
		
	</div>
		
	<div class="col-md-6">
		<div>
		<div class="text-center" id="idCabeceraBarra"></div>

	    <div class="progress" style="margin:0 100px">
			<div id="bar" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                   0 % completado
             </div>
		</div>
		</div>
		<div id="resultado"></div>

		<div>
		<h3 class="text-center"> Control de procesos de importacion</h3>
		<table class="table table-bordered">
			<thead>
			  <tr>
				<th>Tabla temporal Preparada
				<span class="glyphicon glyphicon-ok-sign"></span>
				</th>
				<th>NºReg <br/> BDTpv
				</th>
				<th><!-- Borrada -->
					 <?php // Si no tiene articulos en tpv no ponemos link.
					 if ($sum_Items >0){ ?>
					<a  href="#VaciarTablas" title="Vaciar tablas TPV" onclick="ControlPulsado('vaciar_tablas');">
					<?php } ?>
						<span class="glyphicon glyphicon-trash"></span>
					<?php
					if ($sum_Items >0){ ?>
					</a>
					<?php } ?>
				</th>
				<th id="PrepararInsert"><!-- Creada -->
					<a href="#PrepararInsert" title="Preparar los insert, (N/n) (N)Inserts y (n)descartados en grupos 1000" onclick="ControlPulsado('preparar_insert');">
						<span class="glyphicon glyphicon-log-in"></span>
					</a>
				</th>
				<th><!-- Vaciar -->
					<a title="Se limpio la tabla, vacio">
						<span class="glyphicon glyphicon-repeat"></span>
					</a>
				</th>
				
				
				<th>Importar</th>
				<th>Actualizar</th>
			  </tr>
			</thead>
			<tbody>
				<?php 
				foreach ( $tablas as $tabla ) {
					$n_tabla = $tabla['nombre'];
					echo '<tr id="'.$n_tabla.'">';
					echo '<th>'.$n_tabla.'</th>';
					echo '<td>'.$Items_tabla[$n_tabla] .'</td>';
					echo '<td>'.'</td>';
					echo '<td class="inserts">'.'</td>';
					echo '<td>'.'</td>';
					echo '<td>'.'</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		 </table>		
		</div>		
	
	</div>	
	<div>
	
	</div>
</div>
</body>
</html>
