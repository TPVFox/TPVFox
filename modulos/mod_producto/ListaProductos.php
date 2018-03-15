<!DOCTYPE html>
<html>
    <head>
        <?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include ("./clases/ClaseProductos.php");
	
	$CTArticulos = new ClaseProductos($BDTpv);
	$Controler = new ControladorComun; // Controlado comun..

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
	
	//~ $productos = obtenerProductos($BDTpv,$filtro); //aqui dentro llamamos a paginacionFiltroBusqueda montamos likes %buscar%
	$productos = $CTArticulos->obtenerProductos($filtro);
	
	
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
	        <nav class="col-sm-2">
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
			</nav>
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
						<th>CODIGO BARRAS</th>
						<th>COSTE ULTIMO</th>
						<th>BENEFICIO</th>
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
					$ObCodbarras = $CTArticulos->ObtenerCodbarrasProducto($producto['idArticulo']);
					$codBarrasProd = $CTArticulos->GetCodbarras();
					
					
					?>
					<td><?php 
					if ($codBarrasProd){
						foreach ($codBarrasProd as $cod){
							echo '<small>'.$cod.'</small><br>';
						}
					}
					?>
					</td>
					<td><?php echo number_format($producto['ultimoCoste'],2); ?></td>
					<td><?php echo $producto['beneficio']; ?></td>
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
