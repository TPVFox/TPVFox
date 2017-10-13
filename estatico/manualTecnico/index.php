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
							'ruta'			=> '/help_import_virtuemart/',
							'fichero'		=> 'index.php'
							),
					'2' => array(
							'titulo_cuadro'	=> 'Ayuda Importar Virtuemart',
							'introduccion'	=> 'El modulo de importar desde Virtuemart, una ayuda tecnica como para ejecutarlos.',
							'ruta'			=> '/help_import_virtuemart/',
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
        echo '<pre>';
			echo '[PENDIENTE] Ver como controlar el tema usuarios... :-)';
			print_r($Usuario);
        echo '</pre>';
        $UrlActual = $HostNombre.'/estatico/manualTecnico';
		$DirectorioActual = getcwd();
		?>
     
		<div class="container">
				
			<?php if ($id <= 0 ){ ?>
			<h1 class="text-center">Manual de ayuda para soporte tecnico</h1>
			<div class="col-md-12">
				<?php foreach ( $ayudas as $key => $ayuda){ ?>
				<div class="col-md-3">
					<div style="margin: 1%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h3><?php echo $ayuda['titulo_cuadro'];?></h3>
						<p> <?php echo $ayuda['introduccion'];?>.</p>
						<p><a href="index.php?cargo_ayuda=<?php echo $key;?>">Mas info</a></p>
					</div>
				</div>	
				<?php // fin foreach
				} 
				?>
			</div>
			
			<?php } else {
				$DirectorioActual = $DirectorioActual.$ayudas[$id]['ruta'];
				$UrlActual = $UrlActual.$ayudas[$id]['ruta'];
				$fichero = $DirectorioActual.$ayudas[$id]['fichero'];
				include $fichero;
			 }
			 ?>
			
		</div>
	</body>
</html>
