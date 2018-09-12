<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	Importar ficheros de web con Virtuemart
 * */	

 // Gestion de errores
 // error = [String] No permito continuar.
 // error_warning =  Array() - Si permito continuar pero con restrinciones.

?>

<!DOCTYPE html>
<html>
<head>
<?php
	include './../../head.php';
	// [DEFINICION Y OBTENCION DE VARIABLES]
	//[Obtenemos variables, recuerda los array de inicio necesitar prefijoBD 
	// Los arrays que obtenemos son : ($tablasTemporales,$comprobaciones ,$tablas_importar,$optcrefs)
	include ("./../../controllers/Controladores.php");
	include_once ("./funciones.php");

	// Creo variable de IdTienda -> principal 
	$Tienda = (isset($_SESSION['tiendaTpv']) ? $_SESSION['tiendaTpv']: array('razonsocial'=>''));
	$idTienda = $Tienda['idTienda']; // Id de la tienda actual , no hace falta antes de la carga ArraysInicio
	// Creo variables de  IdTIenda_exportar si ya pulso configuracion:
	if (isset($_POST['tiendaOnLine'])){
		$idTienda_export = $_POST['tiendaOnLine'];
	} else {
		// Por defecto no hay empresa seleccionada.
		$idTienda_export = 0;
	}
	
	// Cargamos variables inicio , estas necesitana la variables idTienda, idTienda_export, PrefijoBD...
	// Lo ideal sería hacer una clase configuración donde obtener esos Arrays_inicio de forma independiente, 
	// es decir:
	//  - Antes pulsar opciones configurarion , obtenemos solo el array de los selects
	//	- Al haber seleccionado y pulsado botton de configuaracion carga los demas arrays.
	include_once ('./Arrays_inicio.php');

	// [ DEFINIMOS VARIABLES POR DEFECTO ]
	$titulo_control_proceso = 'Control de procesos';
	$optcrefs['1']['checked'] = 'checked'; 	// Se cambiaría si hay POST
	$optprecios['1']['checked'] = 'checked'; 	// Se cambiaría si hay POST
	$confirmación_cfg = '' ; 				// Solo tendrá valor si pulsa btn y por get viene variable.
	$tienda_on_line_seleccionada = ''; // Tienda On Line seleccionad.. 
	//[OBTENEMOS LAS TIENDAS ON LINE QUE HAY]
	$tiendasOnLine = ObtenerTiendasWeb($BDTpv);
	//[COMPROBAMOS GET Y POST]
	// Por defecto ponemos : 
	// Vemos si se cambía según el id obtenido, si hay claro.
	if (isset($_GET['configuracion'])){
		$confirmación_cfg =$_GET['configuracion'];
		// Ahora comprobamos que configuración seleciono el usuario.
		//  [OPCION CREF] Seleccionada.
		foreach ($optcrefs as $key => $optcref){
			if ($_POST['optcref'] === $optcref['value']){
				$optcrefs[$key]['checked'] = 'checked';
			} else {
				$optcrefs[$key]['checked'] = '';
			}
		}
		// [TIENDA ONLINE] Seleccionada.
		$tienda_on_line_seleccionada = $_POST['tiendaOnLine'];
		$tienda_importar = ObtenerTiendaImport($BDTpv,$tienda_on_line_seleccionada);
		//~ echo $tienda_on_line_seleccionada;
		//~ $tiendasOnLine[$tienda_on_line_seleccionada]['porDefecto']= 'select'; 
	} 
	// Si NO pulso en configuración entonces no hacemos nada de esto.. ya no lo vamos mostrar.
	$error_warning =  Array() ; // Para advertencias y puede restrinciones de uso.
	$error = ''; // Reiniciamos variable error, no permitimos continuar.
	if ($confirmación_cfg === 'SI'){
		// Compramos que exista tienda selecciona , sino marcamos error
		if ($tienda_on_line_seleccionada <= 0) {
			$error = ' No tiene selecciona tienda de la quieres importar';
		} 
		// Contamos cuantos si tienen registros las tabla BDTPV utilizando controlador general.
		$Controler = new ControladorComun; 
		// Obtenemos los registros de las tablas y se lo añadimos al array $tablas_importar
		$tablas_importar= ObtenerNumRegistrosVariasTablas($Controler,$BDTpv,$tablas_importar);
		// Sumamos los registros de tolas tablas.	
		$sum_Items_articulos = SumarNumRegistrosVariasTablas($tablas_importar);
		// Si hay datos entonces
		if ($sum_Items_articulos > 0){
			// Por defecto el tipo ponemos este, ya que tiene registros
			$tipo = 'actualizar';
			
			} else {
			// Quiere decir que es una importacion.
			$tipo = 'importar';
		}

		// [ELIMINAMOS TIPO INSERTS EN ARRAY tablas_importar]
		// Comprobamos [optcref]  que selecciono para crear en la tabla articulosTiendas:
		switch ($_POST['optcref']) {
			case 'cref_id':
				// Creamos solo en tienda principal registro CREF con idVirtuemart
				foreach ($tablas_importar as  $key => $t){
					if ($t['nombre'] === 'articulosTiendas') {
						// Eliminamos los tipos de insert que no vamos hacer.
						unset($tablas_importar[$key]['tipos_inserts'][2]);
					}
				}
				break;
			case 'cref_SKU':
				// Creamos solo en tienda principal registro CREF con idVirtuemart
				foreach ($tablas_importar as  $key => $t){
					if ($t['nombre'] === 'articulosTiendas') {
						// Eliminamos los tipos de insert que no vamos hacer.
						unset($tablas_importar[$key]['tipos_inserts'][1]);
					}
				}
				break;
			case 'No_cref':
				// Creamos solo en tienda principal registro CREF con idVirtuemart
				foreach ($tablas_importar as  $key => $t){
					if ($t['nombre'] === 'articulosTiendas') {
						// Eliminamos los tipos de insert que no vamos hacer.
						unset($tablas_importar[$key]['tipos_inserts'][1]);
						unset($tablas_importar[$key]['tipos_inserts'][2]);

					}
				}
				break;
				
			default:
				$error = 'No tiene definido forma crear CREF.';
				break;
		}
		// Comprobamos [optcref]  que selecciono para crear en la tabla articulosPrecios:
		switch ($_POST['optprecio']) {
			case 'No_pvp':
				// Creamos solo registros precio de la tienda_exportada, no creamos precio en tienda principal.
				foreach ($tablas_importar as  $key => $t){
					if ($t['nombre'] === 'articulosPrecios') {
						// Eliminamos los tipos de insert que no vamos hacer.
						unset($tablas_importar[$key]['tipos_inserts'][1]);
					}
				}
				break;
			case 'Pvp_principal':
				// Creamos en tienda principal registro Precio igual al de idVirtuemart
				// Aquí no eliminamos ninguno.. hacemos los dos.
				break;
				
			default:
				$error = 'No tiene definido forma crear precios.';
				break;
		}
		// [MONTAMOS OBJETO CONFIGURACION]
		$configuracion = array( 
								'tienda'	=>$tienda_on_line_seleccionada,
								'optCref'	=>$_POST['optcref'],
								'optPrecio' =>$_POST['optprecio'],
								'tipo'		=>$tipo
							);
		
		// [AHORA MONTAMOS VARIABLES GLOBALES JS]
		// Montamos los objectos en JAVASCRIPT de nombre_tabla que lo vamos utilizar .js
		// Recuerda que se debe hacer antes de cargar fichero funciones.js ya sino genera un error
		// no carga variables correctamente.
		?>
		<script type="application/javascript">

		// Objeto configuracion
		var configuracion = []; 
		<?php
		echo "configuracion.push(".json_encode($configuracion).");";
		?>
		// Objeto nombretabla
		var nombretabla = [];
		// Objeto tabla
		var tablaImpor = [];
		// Objeto tablaTemporales

		var tablasTemporales = [];
		
		var tablatemporal_actual; // GLOBAL: Variable que utilizamos para indicar en la que estamos haciendo bucle
		
		var comprobacionesTemporales = [] ; // Array GLOBAL para ejecturar funciones comprobaciones.
		
		var comprobacion_actual; // GLOBAL: Variable que utilizamos para indicar en la que estamos haciendo bucle
		<?php
		// Añadimmos a variable global JS nombretabla 
		// [PENDIENTE] -> Realmente esta variable ya no hacer falta JS 
		// porque ya la podemos obtener tablasTemporales. 
		foreach ($tablas_importar as $tabla){
			echo "nombretabla.push('".$tabla['nombre']."');";
		?>
		
		<?php
			echo "tablaImpor.push(".json_encode($tabla).");";
		}
		?>
		<?php
		// Añadimos a variable global JS tablatemporales
		foreach ($tablasTemporales as $tablaTemporal){
			echo "tablasTemporales.push(".json_encode($tablaTemporal).");";
		
		}
		?>
		<?php 
		// Añadimos a variable global JS tablatemporales
		foreach ($comprobaciones as $comprobacion){
			echo "comprobacionesTemporales.push(".json_encode($comprobacion).");";
		
		}
		
	}
	
	
	?>
	
	</script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_importar_virtuemart/funciones.js"></script>
	
	

</head>
<body>
<?php 
	//~ include './../../header.php';
     include_once $URLCom.'/modulos/mod_menu/menu.php';
		// Debug
	
	
	if ($error !==''){
	echo '<div>'.$error.'</div>';
	exit;		
	
	}
		
	
	
?>
	

<div class="container">
	<h2 class="text-center">Importación o Actualizacion de datos de Virtuemart a TPV.</h2>

	<div class="col-md-5">
		<!-- Solo mostramos parametros configuración si No pulso "Cambiar o confirmación de configuración -->
		<?php 
		if ($confirmación_cfg === 'SI'){
			$disable_conf = 'disabled';
		} else {
			$disable_conf = '';

		}
		?>
		<h3>Parametros a configurar</h3>
			<div class="col-md-12">
				<form action="Importar_virtuemart.php?configuracion=SI" method="POST">
				<div class="col-md-12">
					<label for="sel1" title="El cruce con la tienda on-line es con virtuemart_id y en tabla tpv articulosTienda">Selecciona la tienda On Line con la quieres importar o actualizar datos:</label>
						<select <?php echo $disable_conf;?>  class="form-control" name="tiendaOnLine" id="sel1">
							<option value="0">Sin selecciona tienda on-line</option>
							<?php
							$porDefecto = ''; 
							foreach ($tiendasOnLine['items'] as $tiendaOnLine){
								if ($tienda_on_line_seleccionada===$tiendaOnLine['idTienda']){
									$porDefecto = 'selected';
								}
							?>
								<option <?php echo $porDefecto;?> value="<?php echo $tiendaOnLine['idTienda'];?>" >
								<?php echo $tiendaOnLine['idTienda'].'-'.$tiendaOnLine['dominio'];?>
								</option>
							<?php
							}
							?>
						</select>
					</label>
							
				</div>
				
				<div class="col-md-6">
				<h4>Tabla ArticulosTiendas</h4>
				<p>En tabla se añade un registros con idVirtuemart y idTienda exportada.</p>
				<label><small>¿Que CREF quieres poner en empresa principal?</small></label>
	
					<?php
					foreach ($optcrefs as $optcref){
						echo '<div class="radio">';
						echo '<label class="radio">';
						echo '	<input type="radio" name="optcref" title="'.$optcref['EtiqueTitle'].'" value ="'.$optcref['value'].'" '.$optcref['checked'].' '.$disable_conf.'>'.$optcref['descripcion'].'</label>';
						echo '</div>';

					}
					?>
				</div>
				<div class="col-md-6">
				<h4>Tabla ArticulosPrecios</h4>
				<p>En está tabla se añade por defecto los precios virtuemart relacionad con idTienda exportada.</p>
				<label><small>¿Quieres los precios de la web en el tpv?</small></label>
	
					<?php
					foreach ($optprecios as $optprecio){
						echo '<div class="radio">';
						echo '<label class="radio">';
						echo '	<input type="radio" name="optprecio" title="'.$optprecio['EtiqueTitle'].'" value ="'.$optprecio['value'].'" '.$optprecio['checked'].' '.$disable_conf.'>'.$optprecio['descripcion'].'</label>';
						echo '</div>';
					}
					?>
				</div>
				
				<div class="col-md-12">
				<button <?php echo $disable_conf;?> type="submit" class="btn btn-primary">Cambiar o confirma configuracion</button>
				</div>
				</form>
			</div>
	
		<?php 
		if ($confirmación_cfg === 'SI'){
		?>
			<!-- Lo que mostramos si ya configuracion. -->
			<div class="col-md-12" style="padding: 10px 0 0">
			
			<?php
			 
			if ( $sum_Items_articulos > 0){
				// Quiere decir que tiene datos BDTpv por lo que puede ser no puede ser una iniciacion.
				?>
				<div class="alert alert-warning"><strong>¡¡ Actua con producencia !!</strong>
				<p>Ya que hay datos en BDTpv y los cambios que realices no se pueden deshacer, tanto en importacion como si actualizas.</p>
				</div>
				
				<div class="col-md-12">
					<h3>¿ Que deseas hacer ?</h3>
					<div class="col-md-6">
						<h4>Eliminar y reiniciar.</h4>
						<p>Puedes eliminar los datos BDTVP para luego importar todos los productos de virtuemart y reiniciar todo.</p>
						<div class="alert alert-danger">
							Vas eliminar todo, piensa si necesitas copia de seguridad de BDTPV, va borrar.
						</div>
					<a  href="#VaciarTablas" title="Vaciar tablas TPV" onclick="VaciarTablas();" class="btn btn-danger">
					Eliminar datos TPV
					</a>
					</div>
					<div class="col-md-6">
						
						<h4>Actualizar.</h4>
						<p>Se buscan los productos nuevos y los modificados, solo se tiene en cuenta como modificados, los campos: nombre, precios,estado.</p>
						<a  href="#ActualizarTablas" title="Actualizar tablas TPV" onclick="InicioActualizar();" class="btn btn-warning">
						Actualizar tablas
						</a>
					</div>

					
				</div>
				<?php 
				// La opcion de actualizar solo mostramos cuando hay datos.

			} else {
				echo '<div class="alert alert-info"> Vas importar los datos de Virtuemart</div>';
				echo '<div>
					   <a  href="#ImportarVirtuemart" title="Importar tablas de Virtuemart" onclick="InicioImportar();" class="btn btn-primary">
					  Importar datos de Virtuemart
					  </a>
					</div>';
			}
			?>
			</div>
		<?php
		}
		?>
	
	</div>
	<div class="col-md-7">
	<?php 
	if ($confirmación_cfg === 'SI'){
	?>	
		<div class="col-md-12">
		<h3 class="text-center"> <?php echo $titulo_control_proceso.' de importacion tablas';?></h3>
		<div>
		<div class="text-center" id="idCabeceraBarra"></div>

	    <div class="progress" style="margin:0 100px">
			<div id="barra" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                   0 % completado
             </div>
		</div>
		</div>
		<div id="resultado" class="text-center"></div>
			<div class="col-md-7">
			<table class="table table-bordered">
				<thead>
				  <tr>
					<th colspan="4" class="text-center">
						Base datos TPV
					</th>
				  </tr>
				  <tr>
					<td></td>
					<th>NºReg
					</th>
					<th id="BDTpv_trash"><!-- Borrada -->
						<span class="glyphicon glyphicon-trash"></span>
					</th>
					<th id="PrepararInsert"><!-- Creada -->
						<span title="Preparar los insert, (N/n) (N)Inserts y (n)descartados en grupos 1000" class="glyphicon glyphicon-log-in"></span>
					</th>
				  </tr>
				</thead>
				<tbody>
					<?php 
					// Ahora obtenemos html de tr datos tablas tpv
					echo htmlBDTpvTR($tablas_importar)
					
					?>
				</tbody>
			</table>
			</div>
			<div class="col-md-5">
			<table class="table table-bordered">
				<thead>
				<tr>
					<th colspan="3" class="text-center">
						Base datos virtuemart
					</th>
				</tr>
				<tr class="info">
					<th>CREACION TABLAS</th>
					<th >
						NºReg
					</th>
					<th>
						<span class="glyphicon glyphicon-floppy-open"></span>
					</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($tablasTemporales as $temporal){
				?>
				<tr id="<?php echo $temporal['nombre_tabla_temporal'];?>">
					<th><?php echo $temporal['nombre_tabla_temporal'];?></th>
					<td class="num_registros"></td>
					<td class="check"></td>
				</tr>	
				<?php	
				}
				?>
				<tr class="info">
					<th>COMPROBACIONES</th>
					<th><span title="Subprocesos dentro comprobacion">Sub(*)</span> / Errores</th>
					<th></th>
				</tr>
				<?php foreach ($comprobaciones as $key =>$comprobacion){?>
				<tr id="comprobacion_<?php echo $key;?>">
					<td><h5>
						<a data-toggle="collapse" data-parent="#accordion" href="#<?php echo $comprobacion['nom_funcion'];?>" aria-expanded="false" class="collapsed">
						<?php echo $comprobacion['link_collapse'];?>
						<span style="float:right;" class="icono-collapse">+</span>
						</a>
						</h5>
						<div id="<?php echo $comprobacion['nom_funcion'];?>" class="pepe collapse" style="height: 0px;" aria-expanded="false">
							
							<?php
							$i = 1;
							foreach($comprobacion['explicacion_subprocesos'] as $explicacion){
								echo '<p><b>'.$i.'.-</b>'.$explicacion.'</p>';
								$i++;
							}
							?>
						</div>
					</td>
					<td class="errores"></td>
					<td></td>
				</tr>
				<?php	
				}
				?>
				<tr>
				</tr>
				</tbody>
			</table>
			</div>
					
		</div>		
	
	<?php 
	// fin de mostrar CONTROL DE PROCESOS DE IMPORTACION O ACTUALIZACION DE DATOS
	} else {
		echo '<h3 class="text-center">'.$titulo_control_proceso.'</h3>';
		echo '<div class="alert alert-info"> Pulsa confirmacion de configuracion para mostrar cuadro de '.$titulo_control_proceso.'</div>';
	}
	?>
	</div>
		
	<div>

	
	</div>
</body>
</html>
