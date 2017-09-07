<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	
 *  */
		// Objetivo de esta aplicacion es:
		// ventana popup
		//Buscador 
		//listar productos encontrados
		
		
//https://www.w3schools.com/bootstrap/bootstrap_modal.asp
?>



	<?php 
	//~ include './../../header.php';?>
<!-- Modal -->

<!DOCTYPE html>
<html>
<head>
	<?php
	include_once('./../../head.php');?>
</head>
<body>
<div class="container">
      <div class="col-md-6 col-md-offset-3">
		  <h1>Inicio de sesion </h1>
		<?php 
		if ($_SESSION['estado']=== 'incorrecto'){ 
			?>
			<div class="alert alert-danger">
				<strong>Error sesion!</strong> Contraseña o usuario incorrectos.
			</div>
		<?php } 
			// Pasar ruta para poder devolver al mismo sitio.
		//print_r('Usuario:'.$_SESSION['estado']);
		?> 
		<form action="" method="post" name="form">
		<div class="form-group">
			<label for="usr">Nombre:</label>
			<input type="text" class="form-control" id="usr" name="usr">
		</div>
		<div class="form-group">
			<label for="pwd">Clave:</label>
			<input type="password" class="form-control" id="pwd" name="pwd">
		</div> 
		<input type="submit" value="Aceptar">
		</form>
   
      </div>
    </div>
</div>
</body>
</html>
