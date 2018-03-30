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
		?>
		<!DOCTYPE html>
		<html>
			<head>
			<?php
			include './../../head.php';
			?>
			</head>
			<body>
			<?php
			include '../../header.php';
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
			
			return;
		
		}
		if ($_SESSION['estadoTpv'] !== 'SinActivar'){ 
			// ya quiere decir quiere decir que no es la primera vez... de intento logueo.
			if ($_SESSION['estadoTpv']==='ErrorIndiceUsuario'){
				$mensaje = '<strong>Error tabla de indice!</strong> Avisa servicio tecnico.
				<p> No se encuentra Indice del usuario o hay mas de un registros. <br/>Tienes '.$_SESSION['N_Pagina_Abiertas'].' paginas del proyecto abierto.</p>';
			} else {
				$mensaje= '<strong>Error sesion!</strong> Contraseña o usuario incorrectos.
				<p> Tienes '.$_SESSION['N_Pagina_Abiertas'].'paginas del proyecto abierto.</p>';
			}
			
		} 
		?>
		<?php if (isset($mensaje)) { ?> 
		<div class="alert alert-danger">
			<?php echo $mensaje;?>
		</div>
		<?php } ?> 
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
