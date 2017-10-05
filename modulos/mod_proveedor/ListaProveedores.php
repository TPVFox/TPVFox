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
		if (isset($_GET['pagina'])) {
			$PgActual = $_GET['pagina'];
		}
		if (isset($_GET['Buscar'])) {  
			$palabraBuscar = $_GET['Buscar'];
			$filtro = $palabraBuscar;
		} 

	// Creamos objeto controlado comun, para obtener numero de registros. 
	//parametro necesario para plugin de paginacion
	//funcion contarRegistro necesita:
	//$BDTpv 
	//$vista --> es la tabla en la que trabajamos
	//$filtro --> por defecto es vacio, suele ser WHERE x like %buscado%, caja de busqueda
	
	$Controler = new ControladorComun; 
	$filtro = ''; // por defecto
	$vista = 'proveedores';
	$LinkBase = './ListaProveedores.php?';
	$OtrosParametros = '';
	$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
	} else {
		$desde = 0;
	}
	// Realizamos consulta 
	//si existe palabraBuscar introducida en buscar, la usamos en la funcion obtenerProductos
	if (isset($palabraBuscar)) {
		$filtro =  "$palabraBuscar";
		
	} else {
		
		$filtro = '';
	}

	//~ $OtrosParametros = $palabraBuscar;	
	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase);

	
	$proveedores = obtenerProveedores($BDTpv,$LimitePagina ,$desde,$filtro);
	//~ echo '<pre>';
	//~ print_r($proveedores);
	//~ echo '</pre>';
	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	var BRecambios ='';
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js"></script>
    
  
	
    </head>

<body>
        <?php
        include './../../header.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Proveedores: Editar y Añadir Proveedor </h2>
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
				<div data-offset-top="505">
				<h4> Proveedores</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
					<li><a href="#section1" onclick="metodoClick('AgregarProveedor');";>Añadir</a></li>
					<li><a href="#section2" onclick="metodoClick('VerProveedor');";>Modificar</a></li>
									<?php //metodoClick js case pulsado 
									//agregarUsuario nos lleva a formulario usuario
									//verUsuario si esta checkado nos lleva vista usuario de ese id
												//si NO nos indica que tenemos que elegir uno de la lista ?>
				</ul>
				</div>	
			</nav>		
			<div class="col-md-10">
					<p>
					 -Proveedores encontrados BD local filtrados:
						<?php echo $CantidadRegistros;?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
				<form action="./ListaProveedores.php" method="GET" name="formBuscar">
					<div class="form-group ClaseBuscar">
						<label>Buscar en nombre comercial </label>
						<input type="text" name="Buscar" value="">
						<input type="submit" value="Buscar">
					</div>
				</form>
                 <!-- TABLA DE PRODUCTOS -->
			<div>
			<table class="table table-striped">
				<thead>
					<tr>
						<th></th>
						<th>ID</th>
						<th>NOMBRE COMERCIAL</th>
						<th>RAZON SOCIAL</th>
						<th>NIF</th>
						<th>TELEFONO</th>
						<th>EMAIL</th>
						<th>FECHA ALTA</th>
						<th>ESTADO</th>
					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
				foreach ($proveedores['items'] as $proveedor){ 
					$checkUser = $checkUser + 1; 
				?>

				<tr>
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $proveedor['idProveedor'];?>">
					</td>
					<td><?php echo $proveedor['idProveedor']; ?></td>
					<td><?php echo $proveedor['nombrecomercial']; ?></td>
					<td><?php echo $proveedor['razonsocial']; ?></td>
					<td><?php echo $proveedor['nif']; ?></td>
					<td><?php echo $proveedor['telefono']; ?></td>
					<td><?php echo $proveedor['email']; ?></td>
					<td><?php echo $proveedor['fechaalta']; ?></td>
					<td><?php echo $proveedor['estado']; ?></td>
					
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
