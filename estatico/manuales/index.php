<!DOCTYPE html>
<?php
	$DirectorioActual = getcwd();
?>
<html>
    <head>
        <?php
        include './../../head.php';
        include ("./../../modulos/mod_conexion/conexionBaseDatos.php");
		// Incluimos el fichero arrayAyudas donde obtenemos los cuadros de ayuda que hay.
		// Recuerda que no se puede utilizar el registro 0 
		// Si quieres añadir una ayuda, recuerda que debes añadirla en ese fichero.
		include ("./arrayAyudas.php");
		// Ahora buscamos si tenemos $_GET 
		// ya que si tiene Get [cargo_ayuda] > 0 es que va mostrar la ayuda.
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
			<h1 class="text-center">Manual de ayuda nivel usuario</h1>
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
