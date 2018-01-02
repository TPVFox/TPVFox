<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
		if ($Usuario['estado'] === "Incorrecto"){
			return;	
		}
		
		?>
		<!-- Cargamos libreria control de teclado -->
		
		
	</head>
	<body>
		<?php
        include './../../header.php';
		// ===========  datos cliente segun id enviado por url============= //
		$idTienda = $Tienda['idTienda'];
		$tabla= 'clientes'; // Tablas que voy utilizar.
		$estados = array(); // Creo los estados de usuarios ( para select)
		$estados[0]['valor'] = 'inactivo'; // Por defecto
		$estados[1]['valor'] = 'activo';
		// Obtenemos id
		//~ print_r($_GET);
		
		
		if (isset($_GET['id'])) {
			// Modificar Ficha Cliente
			$id=$_GET['id']; // Obtenemos id para modificar.
			$ClienteUnico = verSelec($BDTpv,$id,$tabla);
			$titulo = "Modificar Cliente";
			if (isset($ClienteUnico['error'])){
				$error='NOCONTINUAR';
				$tipomensaje= "danger";
				$mensaje = "Id de usuario incorrecto ( ver get) <br/>".$ClienteUnico['consulta'];
			} else {
				
				// Ahora ponemos el estado por defecto segun el dato obtenido en la BD .
				if (count($_POST) ===0){
				$i = 0;
					foreach ($estados as $estado){
						if ($ClienteUnico['estado'] === $estado['valor']){
						$estados[$i]['porDefecto'] = "selected"; // Indicamos por defecto
						}
					$i++;
					}
				} 
			}
		} else {
			// Creamos ficha Usuario.
			$titulo = "Crear Cliente";
			$ClienteUnico = array();
			$ClienteUnico['Nombre'] = '';
			$ClienteUnico['razonsocial'] = '';
			$ClienteUnico['nif'] = '';
			$ClienteUnico['direccion'] = '';
			$ClienteUnico['telefono'] = '';
			$ClienteUnico['movil'] = '';
			$ClienteUnico['fax'] = '';
			$ClienteUnico['email'] = '';			
			$estados[0]['porDefecto'] = "selected"; // Indicamos por defecto
			//$ClienteUnico['id']= '';
		}
		
		if (!isset($error)){
			if(count($_POST)>0){
				// Ya enviamos el formulario y gestionamos lo enviado.
				$datos = $_POST;
				if($titulo === "Crear Cliente"){
					// Quiere decir que ya cubrimos los datos del usuario nuevo.
					$resp = insertarCliente($datos,$BDTpv,$tabla);
					echo $resp['sql'];
					if (isset($resp['error'])){
						$tipomensaje= "danger";
						$mensaje = "Nombre de cliente ya existe!";
						
					} else {
						$tipomensaje= "info";
						$mensaje = "Nuevo cliente creado.";
					}
				} else {
					// Quiere decir que ya modificamos los datos del ficha del cliente
					$ClienteUnico['razonsocial'] =$datos['razonsocial'];
					//~ echo'<pre>';
					//~ print_r($datos);
					//~ echo '</pre>';
					$resp = modificarCliente($datos,$BDTpv,$tabla);
					
					if (isset($resp['error'])){
						// Error de usuario repetido...
						$tipomensaje= "danger";
						$mensaje = "Razon social de cliente ya existe!";
					} else {
						$tipomensaje= "info";
						$mensaje = "Su registro de cliente fue editado.";
						$i=$_GET['id'];
						header('Location: cliente.php?id='.$i.'&tipo='.$tipomensaje.'&mensaje='.$mensaje);
					}
				};
			}
		}
		
		?>
     
		<div class="container">
				
			<?php 
			//~ echo '<pre>';
			//~ print_r($_POST);
			//~ echo '</pre>';
			$mensaje=$_GET['mensaje'];
			$tipomensaje=$_GET['tipo'];
			if (isset($mensaje) || isset($error)){   ?> 
				<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje ;?></div>
				<?php 
				if (isset($error)){
				// No permito continuar, ya que hubo error grabe.
				return;
				}
				?>
			<?php
			}
			?>
			<h1 class="text-center"> <?php echo $titulo;?></h1>
			<a class="text-ritght" href="./ListaClientes.php">Volver Atrás</a>
			<div class="col-md-12">
				
				<h3><?php echo $ClienteUnico['razonsocial'];?></h3>

				<div class="col-md-3">
					<?php 
					// UrlImagen
					$img = './../../css/img/imgUsuario.png';
					?>
					<img src="<?php echo $img;?>" style="width:100%;">
				</div>

				<form action="" method="post" name="formCliente">
				<div class="col-md-9">
					<div class="Datos">
						<div class="col-md-6 form-group">
							<label>Nombre Cliente:</label>
							<input type="text" id="nombre" name="nombre" <?php echo $ClienteUnico['Nombre'];?> placeholder="nombre" value="<?php echo $ClienteUnico['Nombre'];?>"   >
							
						</div>
						<div class="col-md-6 form-group">
							<label>Razon Social:</label> <!--//al enviar con POST los inputs se cogen con name="xx" PRE-->
							<input type="text" id="razonsocial" name="razonsocial" placeholder="razon social" value="<?php echo $ClienteUnico['razonsocial'];?>"   >
							
						</div>
						<div class="col-md-6 form-group">
							<label>NIF:</label>
							<input type="text"	id="nif" name="nif" value="<?php echo $ClienteUnico['nif'];?>" required>
						</div>
						<div class="col-md-6 form-group">
							<label>Id del cliente:</label>
							<input type="text" id="idCliente" name="idCliente" value="<?php echo $ClienteUnico['idClientes'];?>"   readonly>
							
						</div>
						<div class="col-md-6 form-group">
							<label>Direccion:</label>
							<input type="text" id="direccion" name="direccion" value="<?php echo $ClienteUnico['direccion'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Telefono:</label>
							<input type="text" id="telefono" name="telefono" value="<?php echo $ClienteUnico['telefono'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Movil:</label>
							<input type="text" id="movil" name="movil" value="<?php echo $ClienteUnico['movil'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Fax:</label>
							<input type="text" id="fax" name="fax" value="<?php echo $ClienteUnico['fax'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Email:</label>
							<input type="text" id="email" name="email" value="<?php echo $ClienteUnico['email'];?>"  >
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
