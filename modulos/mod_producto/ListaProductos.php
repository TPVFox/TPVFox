<!DOCTYPE html>
<html>
    <head>
        <?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	
	
	
	//INICIALIZAMOS variables para el plugin de paginado:
	//$PgActual = 1 por defecto
	//$CantidadRegistros , usamos la funcion contarRegistro de la class controladorComun /controllers/Controladores  
	//$LimitePagina = 40 o los que queramos
	//$LinkBase --> en la vista que estamos trabajando ListaProductos.php? para moverse por las distintas paginas
	//$OtrosParametros
	$PgActual = 1; // por defecto.
	$LimitePagina = 40; // por defecto.
	// Obtenemos datos si hay GET y cambiamos valores por defecto.
	if ($_GET) {
		if ($_GET['pagina']) {
			$PgActual = $_GET['pagina'];
		}
		if ($_GET['buscar']) {
			$palabraBuscar = $_GET['buscar'];
			$filtro =  "WHERE a.`articulo_name` LIKE '%".$palabraBuscar."%'";
		} 
	}
	
	// Creamos objeto controlado comun, para obtener numero de registros. 
	//parametro necesario para plugin de paginacion
	//funcion contarRegistro necesita:
	//$BDTpv 
	//$vista --> es la tabla en la que trabajamos
	//$filtro --> por defecto es vacio, suele ser WHERE x like %buscado%, caja de busqueda
	
	$Controler = new ControladorComun; 
	$filtro = ''; // por defecto
	$vista = 'articulos';
	$LinkBase = './ListaProductos.php?';
	$OtrosParametros = '';
	$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
	} else {
		$desde = 0;
	}
	// Realizamos consulta 
	if ($palabraBuscar !== '') {
		$filtro =  "WHERE a.`articulo_name` LIKE '%".$palabraBuscar."%'";
	} else {
		$filtro = '';
	}

	$OtrosParametros = $palabraBuscar;	
	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
	$productos = obtenerProductos($BDTpv,$LimitePagina ,$desde,$filtro);
	
	?>
	
	<script>
	// Declaramos variables globales
	var checkID = [];
	var BRecambios ='';
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
    
    <!-- Cargamos libreria control de teclado -->
	<script src="<?php echo $HostNombre; ?>/lib/shortcut.js"></script>
  
	
	<script>
	// Funciones para atajo de teclado.
	//~ shortcut.add("Shift+V",function() {
		//~ // Atajo de teclado para ver
		//~ metodoClick('VerUsuario');
	//~ });    
	    
	</script> 
    </head>

<body>
        <?php
        include './../../header.php';
        ?>
        <?php
				//~ echo '<pre>';
					//~ print_r($productos);
				//~ echo '</pre>';
		?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Productos: Editar y Añadir Productos </h2>
					<?php 
					//~ echo 'Numero filas'.$Familias->num_rows.'<br/>';
					//~ echo '<pre class="text-left">';
					//~ print_r($Familias);
					//~ 
					//~ echo '</pre>';
					?>
				</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<nav class="col-sm-2" id="myScrollspy">
				<div data-spy="affix" data-offset-top="505">
				<h4> Usuarios</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					//~ <li><a href="#section1" onclick="metodoClick('AgregarProducto','producto');";>Añadir</a></li>
					//~ <li><a href="#section2" onclick="metodoClick('VerProducto','producto');";>Modificar</a></li>
				?><?php		//metodoClick js case pulsado 
								//agregarUsuario nos lleva a formulario usuario
								//verUsuario si esta checkado nos lleva vista usuario de ese id
											//si NO nos indica que tenemos que elegir uno de la lista ?>
				</ul>
				</div>	
			</nav>
			<div class="col-md-10">
					<p>
					 -Productos encontrados BD local filtrados:
						<?php echo $CantidadRegistros;?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
					?>
				<div class="form-group ClaseBuscar">
					<label>Buscar en descripcion </label>
					<input type="text" name="Buscar" value="">
										<?php // la idea es enviar parametro de donde para atacar a un mismo js mod_producto?>
					<input type="submit" name="BtnBuscar" value="Buscar" onclick="metodoClick('NuevaBusqueda','ListaProductos');">
				</div>
				
                 <!-- TABLA DE PRODUCTOS -->
			<div>
			<table class="table table-striped">
				<thead>
					<tr>
						<th></th>
						<th>ID</th>
						<th>PRODUCTO</th>
						<th>CODIGO BARRAS</th>
						<th>COSTE</th>
						<th>BENEFICIO</th>
						<th>IVA</th>
						<th>PRECIO VENTA</th>
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
					<td><?php echo $producto['codBarras']; ?></td>
					<td><?php echo $producto['costepromedio']; ?></td>
					<td><?php echo $producto['beneficio']; ?></td>
					<td><?php echo $producto['iva']; ?></td>
					<td><?php echo $producto['pvpCiva']; ?></td>
					
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
