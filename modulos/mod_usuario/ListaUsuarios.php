<!DOCTYPE html>
<html>
    <head>
        <?php
    include_once './../../inicial.php';
    
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_usuario/funciones.php';

	$usuarios = obtenerUsuarios($BDTpv);
	
	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_usuario/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
    
    <!-- Cargamos libreria control de teclado -->
	<script src="<?php echo $HostNombre; ?>/lib/shortcut.js"></script> 
  
    </head>

<body>
        <?php
        //~ include './../../header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Usuarios: Editar y Añadir Usuarios </h2>
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
					<li><a href="#section1" onclick="metodoClick('AgregarUsuario');";>Añadir</a></li>
					<li><a href="#section2" onclick="metodoClick('VerUsuario');";>Modificar</a></li>
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
						<th>USUARIO</th>
						<th>NOMBRE</th>
						<th>FECHA</th>
						<th>GRUPO ID</th>
						<th>ESTADO</th>

					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
				foreach ($usuarios['items'] as $usuario){ 
					$checkUser = $checkUser + 1; 
				?>

				<tr>
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $usuario['id'];?>">
					</td>
					<td><?php echo $usuario['id']; ?></td>
					<td><?php echo $usuario['username']; ?></td>
					<td><?php echo $usuario['nombre']; ?></td>
					<td><?php echo $usuario['fecha']; ?></td>
					<td><?php echo $usuario['group_id']; ?></td>
					<td><?php echo $usuario['block']; ?></td>
					
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
