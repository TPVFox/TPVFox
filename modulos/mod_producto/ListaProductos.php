<!DOCTYPE html>
<html>
    <head>
        <?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include ("./clases/ClaseProductos.php");
	
	include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
	$Controler = new ControladorComun; // Controlado comun..
	// Añado la conexion
	$Controler->loadDbtpv($BDTpv);
	// Inicializo varibles por defecto.
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];
	
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$parametros = $ClasesParametros->getRoot();
	// Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	// Obtenemos la configuracion del usuario o la por defecto
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_productos',$Usuario['id']);
	
	echo '<pre>';
	print_r($configuracion);
	echo '</pre>';
	
	$CTArticulos = new ClaseProductos($BDTpv);



	//INICIALIZAMOS variables para el plugin de paginado:
	$palabraBuscar=array();
	$stringPalabras='';
	$PgActual = 1; // por defecto.
	$LimitePagina = 40; // por defecto.
	$filtro = ''; // por defecto
	$vista = 'articulos';
	$LinkBase = './ListaProductos.php?';
	$OtrosParametros = '';
	
	if (isset($_GET['pagina'])) {
		$PgActual = $_GET['pagina'];
	}
	if (isset($_GET['buscar'])) {  
		//recibo un string con 1 o mas palabras
		$stringPalabras = $_GET['buscar'];
		$palabraBuscar = explode(' ',$_GET['buscar']); 
	} 
	
	$paginasMulti = $PgActual-1;
	
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
		
	} else {
		$desde = 0;
	}
	
	// Realizamos consulta 
	//si existe palabraBuscar introducida en buscar, la usamos en la funcion obtenerProductos
	if ($stringPalabras !== '' ){
		$campoBD='articulo_name';
		$WhereLimite= $Controler->paginacionFiltroBuscar($stringPalabras,$LimitePagina,$desde,$campoBD);
		$filtro=$WhereLimite['filtro'];
		$OtrosParametros=$stringPalabras;
	}
	//consultamos 2 veces: 1 para obtner numero de registros y el otro los datos.
	$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);

	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
	if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro'].$WhereLimite['rango'];
	} else {
		$filtro= " LIMIT ".$LimitePagina." OFFSET ".$desde;
	}
	
	$productos = $CTArticulos->obtenerProductos($filtro);
	
	// Añadimos a JS la configuracion
		echo '<script type="application/javascript"> '
		. 'var configuracion = '. json_encode($configuracion);
		echo '</script>';
	?>
	
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    </head>

<body>
        <?php
        include './../../header.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
				<h2> Productos: Editar y Añadir Productos </h2>
			</div>
	        <div class="col-sm-2">
				
				<h4> Productos</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] === '1'){
				?>
					<li><a href="#section2" onclick="metodoClick('AgregarProducto');";>Añadir</a></li>
					<?php 
				}
					?>
					<li><a href="#section2" onclick="metodoClick('VerProducto','producto');";>Modificar</a></li>
				<?php		//metodoClick js case pulsado 
								//agregarUsuario nos lleva a formulario usuario
								//verUsuario si esta checkado nos lleva vista usuario de ese id
											//si NO nos indica que tenemos que elegir uno de la lista ?>
				</ul>	
				<h4>Configuracion</h4>
				<h5>Mostrar y buscar por:</h5>
				<?php 
				foreach ($configuracion['mostrar_lista'] as $mostrar){
					$c= ' onchange="GuardarConfiguracion(this)"';
					if ($mostrar->valor==='Si'){
						$c ='checked '.$c;
					}
					echo '<input class="configuracion" type="checkbox" name="'.$mostrar->nombre.'" value="'.$mostrar->valor.'"'.$c.'>';
					echo $mostrar->descripcion.'<br>';
					
				}
				?>
			
			</div>
			
			<div class="col-md-10">
					<p>
					 -Productos encontrados BD local filtrados:
						<?php echo $CantidadRegistros; ?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
				<form action="./ListaProductos.php" method="GET" name="formBuscar">
					<div class="form-group ClaseBuscar">
						<label>Buscar en descripcion </label>
						<input type="text" name="buscar" value="">
						<input type="submit" value="buscar">
					</div>
				</form>
                 <!-- TABLA DE PRODUCTOS -->
			<div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th></th>
						<th>ID</th>
						<th>PRODUCTO</th>
						<?php if ($configuracion['mostrar_lista'][1]->valor === 'Si'){
							echo '<th>CODIGO BARRAS</th>';
						}
						if ($configuracion['mostrar_lista'][0]->valor === 'Si'){
							echo '<th>REFERENCIA</th>';
						}
						?>
				
						<th>COSTE <br/> ULTIMO</th>
						<th><span title="Beneficio que tiene ficha">%</span> </th>
						<th>Precio<br/>Sin Iva</th>
						<th>IVA</th>
						<th>P.V.P</th>
						<th>Estado</th>

					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
				foreach ($productos as $producto){ 
					$checkUser = $checkUser + 1; 
				?>

				<tr>
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $producto['idArticulo'];?>">
					</td>
					<td><?php echo $producto['idArticulo']; ?></td>
					<td><?php echo $producto['articulo_name']; ?></td>
					
					<?php
					if ($configuracion['mostrar_lista'][1]->valor === 'Si'){
						$ObCodbarras = $CTArticulos->ObtenerCodbarrasProducto($producto['idArticulo']);
						$codBarrasProd = $CTArticulos->GetCodbarras();
						echo '<td>'; 
						if ($codBarrasProd){
							foreach ($codBarrasProd as $cod){
								echo '<small>'.$cod.'</small><br>';
							}
						}
						echo '</td>';
					}
					?>
					<?php 
					if ($configuracion['mostrar_lista'][0]->valor === 'Si'){
						// $ObCodbarras = $CTArticulos->ObtenerCodbarrasProducto($producto['idArticulo']);
						// $codBarrasProd = $CTArticulos->GetCodbarras();
						echo '<td>'; 
						// if ($codBarrasProd){
							// foreach ($codBarrasProd as $cod){
								// echo '<small>'.$cod.'</small><br>';
						// }
						echo '</td>';
					}
					
					?>
					
					<td><?php echo number_format($producto['ultimoCoste'],2); ?></td>
					<td><?php echo $producto['beneficio']; ?></td>
					<td style="text-align:right;"><?php echo number_format($producto['pvpSiva'],2); ?><small>€</small></td>
					<td><?php echo $producto['iva']; ?></td>
					<td style="text-align:right;"><?php echo number_format($producto['pvpCiva'],2); ?><small>€</small></td>
					<td><?php echo $producto['estado']; ?></td>

				</tr>

				<?php 
				}
				?>
				
			</table>
			</div>
		</div>
	</div>
    </div>
		
</body>
</html>
