<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
		
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_tienda/funciones.php';
	$tiendas = obtenerTiendas($BDTpv);
	
	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_tienda/funciones.js"></script>
    
    <!-- Cargamos libreria control de teclado -->
	<script src="<?php echo $HostNombre; ?>/lib/shortcut.js"></script>
  
	
	
    </head>

<body>
        <?php
       //~ include_once $URLCom.'/header.php';
        include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Tiendas: Editar y Añadir Tiendas </h2>
			</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<nav class="col-sm-2" id="myScrollspy">
				<div data-spy="affix" data-offset-top="505">
				<h4> Tiendas</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
					<li><a href="#section1" onclick="metodoClick('AgregarTienda');";>Añadir</a></li>
					<li><a href="#section2" onclick="metodoClick('VerTienda');";>Modificar</a></li>
									<?php //metodoClick js case pulsado 
									//agregarUsuario nos lleva a formulario usuario
									//verUsuario si esta checkado nos lleva vista usuario de ese id
												//si NO nos indica que tenemos que elegir uno de la lista ?>
				</ul>
				</div>	
			</nav>		
			<div class="col-md-10">
				<?php
				//~ echo '<pre>';
					//~ print_r($usuarios);
				//~ echo '</pre>';
				?>
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
						<th>DIRECCION</th>
						<th>TELÉFONO</th>
						<th>ANO</th>
						<th>ESTADO</th>
					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
				foreach ($tiendas['items'] as $tienda){ 
					$checkUser = $checkUser + 1; 
				?>

				<tr>
					<td class="rowTienda"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $tienda['idTienda'];?>">
					</td>
					<td><?php echo $tienda['idTienda']; ?></td>
					<td><?php echo $tienda['NombreComercial']; ?></td>
					<td><?php echo $tienda['razonsocial']; ?></td>
					<td><?php echo $tienda['nif']; ?></td>
					<td><?php echo $tienda['direccion']; ?></td>
					<td><?php echo $tienda['telefono']; ?></td>
					<td><?php echo $tienda['ano']; ?></td>
					<td><?php echo $tienda['estado']; ?></td>
					
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
