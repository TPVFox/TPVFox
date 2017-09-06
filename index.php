<?php
	// Directorio actual de index.php ( del proyecto) debe coincidir con $HostNombre.$RutaServidor
	$DirectorioInicio = getcwd();
	//session_start();

		
	
?>

<!DOCTYPE html>
<html>
<head>
<?php
	include 'head.php';?>
	<?php
			//include 'plugins/controlUser/modalUsuario.php'; 
		 
			include_once ($RutaServidor . $HostNombre . "/plugins/controlUser/funcion.php");
			$varSesion = new ComprobarSession;
			$dato= $varSesion->recibir($BDTpv, $HostNombre);
			
			//print_r($BDTpv);
		
		 ?>
	
		
	<?php 
		echo '<pre>';
		print_r($dato);
		echo '</pre>';
		 //~ print_r($_POST)
			//~ $nombre = $_POST['usr'];
			//~ $clave = $_POST['pwd'];
			//~ echo $clave;
			//~ if (isset($nombre) && isset($clave))
			//~ { 
				//~ $res = $varSesion->comprobarUser($nombre,$clave);
				//~ echo $res;
			//~ } 

	?>
	

	
</head>
<body>
	<?php 
	include 'header.php';
	
	echo '<pre>';
	print_r($DirectorioInicio);
	
	echo '</pre>';
	
	?>
	<section>
		<div class="container">
			<div class="col-md-8">
				<h1>TPVfox</h1>
				<p></p>
				<p></p>
				
				</div>
			</div>
			<div class="col-md-4">
				<div>
				<h2>Información funcionamiento</h2>
				<p></p>
				</div>
				<div>
					<div class="alert alert-info">
					<p>Está aplicación es OPEN SOURCE, con ello queremos decir que puedes utilizar este código en otras aplicaciones y modificarlo sin problemas.</p>
					</div>
					<div class="alert alert-danger">
					<p>Lo que no se puede es publicar son los datos de BD (estructura SI)y documentacion de la empresa. </p>
					</div>
				</div>
			</div>
			
		</div>
	</section>
	
	
	
	
	
</body>
</html>
