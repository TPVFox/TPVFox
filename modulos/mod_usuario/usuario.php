<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
		//include ("./ObjetoRecambio.php");
		// Obtenemos id
		if ($_GET['id']) {
			$id = $_GET['id'];
		} else {
			// NO hay parametro .
			$error = "No podemos continuar";
		}
		
		
		// ===========  datos usuario segun id enviado por url============= //
			$tabla= 'usuarios';
			$idBusqueda ='id='.$id;
			$UsuarioUnico = verSelec($BDTpv,$id,$tabla);
			// Solo debería haber un resultado, creamos de ese resultado unico, pero debería comprobarlo.
			
			//~ echo '<pre>';
			//~ print_r($UsuarioUnico);
			//~ echo '</pre>';
		?>
		<!-- Cargamos libreria control de teclado -->
		<script src="<?php echo $HostNombre; ?>/lib/shortcut.js"></script>
		<!-- Añadimos atajo de teclado --> 
		<script>
			// Funciones para atajo de teclado.
		shortcut.add("Shift+A",function() {
			// Atajo de teclado para ver
			history.back(1);
		});    
		</script>
	</head>
	<body>
		<?php
        include './../../header.php';
			$atras = 1; // Variable que indica volver una atras.
			if(count($_POST)>0){
				$atras = 2;
				if (isset($id)){
					// Comprobamos: 
					//($dato['password']=== 'password') olvidarme de insertar psw
						$datos = $_POST;
						$resp = modificarUsuario($datos,$BDTpv,$tabla);
						echo $resp['consulta'];
					
					// Mandas funcion a grabar.
					//$tipomensaje= "danger";
					$tipomensaje= "info";

					$mensaje = "Su registro de usuario fue editado.";
				} else {
					$mensaje = "Nuevo usuario creado.";
				}
			echo '<pre>';
			print_r($_POST);
			echo '</pre>';
			};
			
			$estados = array(); // Por defecto
			$estados[0]['valor'] = 'inactivo'; // Por defecto
			$estados[1]['valor'] = 'activo';
			if (!isset($id)){ ///nuevo
				$titulo = "Crear Usuario";
				$UsuarioUnico = array();
				$UsuarioUnico['fecha'] = date("Y-m-d");
				$UsuarioUnico['group_id'] = 0;
				$UsuarioUnico['password'] = '';
				$UsuarioUnico['username'] = '';
				$UsuarioUnico['nombre'] = '';
				$estados[0]['porDefecto'] = "selected"; // Indicamos por defecto
				$UsuarioUnico['id']= '';
				$passwrd= '';
				
			} else {
				$titulo = "Modificar Usuario";
				$passwrd= 'password';
				$i = 0;
				//~ echo 'Alfo:'.$UsuarioUnico['estado'];
				foreach ($estados as $estado){
					if ($UsuarioUnico['estado'] == $estado['valor']){
						$estados[$i]['porDefecto'] = "selected"; // Indicamos por defecto
					}
					$i++;
				} 
			}
			
			
			
			//~ $user = $UsuarioUnico['fecha'];
			//~ $grupo = $UsuarioUnico['group_id'];
			//~ $psw = $UsuarioUnico['password']; 
			//~ if (!isset($id)){ ///nuevo
				//~ $fecha = '<input type="date" id="fecha" name="fecha" 
							//~ value="'.date("Y-m-d").'" readonly>';
				//~ $grupoId = '<option value="0" selected>0</option>';
				//~ $passwrd = '<input type="password" class="form-control" id="pwd" placeholder="contraseña" value="" required>';
										
						
				
			 //~ } else { //modificar
				//~ $fecha = '<input type="date" id="fecha" name="fecha" 
							//~ value="'.$user.'" >';
							
				//~ $grupoId = '<option value="'.$grupo.'" selected>'.$grupo.'</option>';
				//~ $passwrd = '<input type="password" class="form-control" id="pass" placeholder="contraseña" value="'.$psw.'">';
			//~ }
			
			
			?>
     
		<div class="container">
			<?php if (isset($mensaje)){   ?> 
			<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje ;?></div>
			<?php }?>
			<h1 class="text-center"> <?php echo $titulo;?></h1>
			<a class="text-ritght" href="javascript:history.back(<?php echo $atras;?>)">Volver Atrás</a>
			<div class="col-md-12">
				
				<h3><?php echo $UsuarioUnico['nombre'];?></h3>
				<div class="col-md-3">
					<?php 
					// UrlImagen
					$img = './../../css/img/imgUsuario.png';
					?>
					<a href="<?php echo $img;?>"><img src="<?php echo $img;?>" style="width:100%;"></a>
				</div>
				<form action="" method="post" name="formUsuario">
				<div class="col-md-9">
					<div class="Datos">
						<div class="col-md-6 form-group">
							<label>Nombre Usuario/login:</label>
							<input type="text" id="username" name="username" placeholder="usuario/login" value="<?php echo $UsuarioUnico['username'];?>"  required >
							
						</div>
						<div class="col-md-6 form-group">
							<label>Nombre empleado:</label>
							<input type="text" id="nombreEmpleado" name="nombreEmpleado" placeholder="nombre empleado" value="<?php echo $UsuarioUnico['nombre'];?>"  required >
							
						</div>
						<div class="col-md-6 form-group">
							<label>Fecha creación:</label>
							<input type="date" id="fecha" name="fecha" 
							value="<?php echo $UsuarioUnico['fecha'];?>" readonly>							
						</div>
						<div class="col-md-6 form-group">
							<label>Id del usuario:</label>
							<input type="text" id="idUsuario" name="idUsuario" value="<?php echo $UsuarioUnico['id'];?>"   readonly>
							
						</div>
						<div class="col-md-6 form-group">
							<label for="sel1">Grupo permisos:</label>
							<select class="form-control" name="grupo" id="sel1">
								<option value="<?php echo $UsuarioUnico['group_id'];?>" selected><?php echo $UsuarioUnico['group_id'];?></option>
							</select>
							
						</div>
						<div class="col-md-6 form-group">
							<label for="sel1">Estado:</label>
							<select class="form-control" name="estado" id="sel1">
								<?php 
								foreach ($estados as $estado){
								?>
									<option value="<?php echo $estado['valor'];?>" <?php echo (isset($estado['porDefecto']) ? $estado['porDefecto'] : '');?> >
									<?php echo $estado['valor'];?>
									</option>
								<?php
								}
								?>
								
							</select>
						</div>
						<div class="col-md-6 form-group">
							<label for="pwd">Contraseña:</label>
							<input type="password" class="form-control" id="pwd" name="password" placeholder="contraseña" value="<?php echo $passwrd ;?>" required>
						</div>
						
						
					</div>
					
				</div>
				<div class="col-md-12">
					<input type="submit" value="Guardar">
				
				<div class="col-md-9">
				</form>
			</div>
			
		</div>
	</body>
</html>
