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
	// Incrementamos contador paaginas abiertas.
	$_SESSION['N_Pagina_Abiertas'] = $_SESSION['N_Pagina_Abiertas'] +1;
	?>
	
<div id="formularioUsuario">
      <div class="col-md-6 col-md-offset-3">
		  <h1>Inicio de sesion </h1>
		<?php 
		if ($_SESSION['estadoTpv'] === 'Correcto'){ 
			// Quiere decir que ya esta logueado correctamente.
			echo 'Relamente quiere desloguearte '.$UsuarioLogin;
			echo '</div></div>';
			return;
		
		}
		if ($_SESSION['estadoTpv'] !== 'SinActivar'){ 
			// ya quiere decir quiere decir que no es la primera vez... de intento logueo.
			?>
			<div class="alert alert-danger">
				<strong>Error sesion!</strong> Contraseña o usuario incorrectos.
				<p> Tienes <?php echo $_SESSION['N_Pagina_Abiertas'];?> paginas del proyecto abierto.</p>
			</div>
		<?php } 
		// Pasar ruta para poder devolver al mismo sitio.
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

