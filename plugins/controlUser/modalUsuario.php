<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	
 *  */
// Objetivo de esta aplicacion es:
// Es crear un formulario de entrada usuario.
?>

<?php
	// Incrementamos contador paginas abiertas.
	if (!class_exists ('ClaseSession')){
		// LLega aquí cuando cerramos session
		?>
		<!DOCTYPE html>
		<html>
			<head>
			<?php
            include_once './../../inicial.php';
			include './../../head.php';
			?>
			</head>
			<body>
			<?php
			//~ include '../../header.php';
            include_once $URLCom.'/modulos/mod_menu/menu.php';
	} else {
	?>
		<meta name="language" content="es">
		<meta charset="UTF-8">
		<link rel="stylesheet" href="<?php echo $HostNombre;?>/css/bootstrap.min.css" type="text/css">
		<link rel="stylesheet" href="<?php echo $HostNombre;?>/css/template.css" type="text/css">
		<script src="<?php echo $HostNombre;?>/jquery/jquery-2.2.5-pre.min.js"></script>
		<script src="<?php echo $HostNombre;?>/css/bootstrap.min.js"></script>
		</head>
		<body>
	<?php
		//~ include $URLCom.'/header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
	}
		
	
	$_SESSION['N_Pagina_Abiertas'] = $_SESSION['N_Pagina_Abiertas'] +1;
	if (isset($_GET['tipo'])){
		if ($_GET['tipo']==='cerrar'){ 
		 $titulo = 'Cierre sesion';	
		}
	} else {
		$titulo = 'Inicio de sesion';	
	}
	?>
	
<div id="formularioUsuario">
      <div class="col-md-6 col-md-offset-3">
		<?php
		echo '<h1>'.$titulo.'</h1>';
		if ($_SESSION['estadoTpv'] === 'Correcto'){ 
			// Quiere decir que ya esta logueado correctamente.
			if (isset($_GET['tipo'])){
				if ($_GET['tipo']==='cerrar'){ 
					echo 'Cerramos session,'.$_SESSION['usuarioTpv']['nombre'];
					$thisTpv->cerrarSession();
				}	
			} else {
				echo ' Hola '.$_SESSION['usuarioTpv']['nombre'].'<br/>';
				echo 'Ya estás logueada, quiere cerrar session.<br>';
				echo '<a href="'.$HostNombre.'/plugins/controlUser/modalUsuario.php?tipo=cerrar">Cerrar</a>';
			}
			
			exit();
		
		}
		if (count($thisTpv->GetComprobaciones())>0) { 
			foreach ($thisTpv->GetComprobaciones() as $error){
				echo '<div class="alert alert-'.$error['tipo'].'">';
				echo $error['mensaje'];
				echo '</div>';
				if ($error['tipo'] === 'danger'){
					exit();
				}
			}
		}
		?> 
		
		<form action="" method="post" name="form">
		<div class="form-group">
			<label for="usr">Nombre:</label>
			<input type="text" class="form-control" id="usr" name="usr" required>
		</div>
		<div class="form-group">
			<label for="pwd">Clave:</label>
			<input type="password" class="form-control" id="pwd" name="pwd" required>
		</div> 
		<input type="submit" value="Aceptar">
		</form>
   
      </div>
    </div>
</div>

<?php
if (isset($_GET['tipo'])){
		if ($_GET['tipo']==='cerrar'){
			// Cargamos cabecera
		?>
		
		</body>
		</html>

		<?php
		}
}
?>
