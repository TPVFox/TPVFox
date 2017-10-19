<!DOCTYPE html>
<?php
	$DirectorioActual = getcwd();
?>
<html>
    <head>
        <?php
        include './../../head.php';
        include ("./../../modulos/mod_conexion/conexionBaseDatos.php");
		
		// No utilizar el registro 0 
		$ayudas = array(
					'1' => array(
							'titulo_cuadro'	=> 'Generar Ayudas',
							'introduccion'	=> 'Una plantilla que para como generar mas ayudas.',
							'ruta'			=> '/help_plantilla/',
							'fichero'		=> 'index.php'
							),
					'2' => array(
							'titulo_cuadro'	=> 'Ayuda Importar Virtuemart',
							'introduccion'	=> 'El modulo de importar desde Virtuemart, una ayuda tecnica como para ejecutarlos.',
							'ruta'			=> '/help_import_virtuemart/',
							'fichero'		=> 'index.php'
							),
					'3' => array(
							'titulo_cuadro'	=> 'Testeo Productos',
							'introduccion'	=> 'El modulo de productos, una ayuda tecnica como para ejecutar el testeo.',
							'ruta'			=> '/help_productos/',
							'fichero'		=> 'index.php'
							),	
					'4' => array(
							'titulo_cuadro'	=> 'Ayuda Tickets',
							'introduccion'	=> 'El modulo de tickets, una ayuda tecnica como para ejecutar el testeo.',
							'ruta'			=> '/help_tickets/',
							'fichero'		=> 'index.php'
							),
					'5' => array(
							'titulo_cuadro'	=> 'Ayuda Tickets Cerrados Cobrados',
							'introduccion'	=> 'El listado de tickets cerrados, punto pendiente de revisar, paginacion y filtrado.',
							'ruta'			=> '/help_tickets_cerrados_cobrados/',
							'fichero'		=> 'index.php'
							),
					'6' => array(
							'titulo_cuadro'	=> 'Fichero HEADER',
							'introduccion'	=> 'En cargado mostrar menu y controlar que items mostrar según el usuario',
							'ruta'			=> '/help_fichero_header/',
							'fichero'		=> 'index.php'
							)
				);
		
		// Ahora buscamos si tenemos $_GET con alguna de ayudas.
		$id = 0;
		if (count($_GET)>0){
			if ($_GET['cargo_ayuda']){
			// Quiere decir que hay url de cargar ayuda.
			$id = 	$_GET['cargo_ayuda'];
			}
			
		}
		?>
		
		
	</head>
	<body>
		<?php
        include './../../header.php';
        //~ echo '<pre>';
			//~ echo '[PENDIENTE] Ver como controlar el tema usuarios... :-)';
			//~ print_r($Usuario);
        //~ echo '</pre>';
        $UrlActual = $HostNombre.'/estatico/manualTecnico';
		$DirectorioActual = getcwd();
		?>
     
		<div class="container">
			<?php if ($id <= 0 ){ ?>
			<h1 class="text-center">Manual de ayuda para soporte tecnico</h1>
			<?php 
			$colu = 0;
			$numColumnas= 4;
			
			foreach ( $ayudas as $key => $ayuda){ 
				if ($colu > $numColumnas  || $colu === 0){
					$colu = 1;
				?>
				<div class="col-md-12">
				<?php }?>
				<div class="col-md-3">
					<div style="margin: 1%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h3><?php echo $ayuda['titulo_cuadro'];?></h3>
						<p> <?php echo $ayuda['introduccion'];?>.</p>
						<p><a href="index.php?cargo_ayuda=<?php echo $key;?>">Mas info</a></p>
					</div>
				</div>	
				<?php if ($colu <= $numColumnas ){
					$colu++; 
				}?>
				<?php 
				if ($colu > $numColumnas || $key === count($ayudas)){?>
				</div>
				<?php }
				} 
			} else {
				$DirectorioActual = $DirectorioActual.$ayudas[$id]['ruta'];
				$UrlActual = $UrlActual.$ayudas[$id]['ruta'];
				$fichero = $DirectorioActual.$ayudas[$id]['fichero'];
				include $fichero;
				}
			?>
			
		</div>
	</body>
</html>
