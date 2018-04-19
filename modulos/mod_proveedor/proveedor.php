<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
		?>
		<!-- Cargamos libreria control de teclado -->
		
		
	</head>
	<body>
		<?php
        include './../../header.php';
		// ===========  datos proveedor segun id enviado por url============= //
		$idTienda = $Tienda['idTienda'];
		$tabla= 'proveedores'; // Tablas que voy utilizar.
		$estados = array(); // Creo los estados de usuarios ( para select)
		$estados[0]['valor'] = 'inactivo'; // Por defecto
		$estados[1]['valor'] = 'activo';
		// Obtenemos id
		//~ print_r($_GET);
		
		
		if (isset($_GET['id'])) {
			// Modificar Ficha fichero
			$id=$_GET['id']; // Obtenemos id para modificar.
			$ProveedorUnico = verSelec($BDTpv,$id,$tabla);
			$titulo = "Modificar Proveedor";
			if (isset($ProveedorUnico['error'])){
				$error='NOCONTINUAR';
				$tipomensaje= "danger";
				$mensaje = "Id de usuario incorrecto ( ver get) <br/>".$ProveedorUnico['consulta'];
			} else {
				
				// Ahora ponemos el estado por defecto segun el dato obtenido en la BD .
				if (count($_POST) ===0){
				$i = 0;
					foreach ($estados as $estado){
						if ($ProveedorUnico['estado'] === $estado['valor']){
						$estados[$i]['porDefecto'] = "selected"; // Indicamos por defecto
						}
					$i++;
					}
				} 
			}
		} else {
			// Creamos ficha Usuario.
			$titulo = "Crear Proveedor";
			$ProveedorUnico = array();
			$ProveedorUnico['nombrecomercial'] = '';
			$ProveedorUnico['razonsocial'] = '';
			$ProveedorUnico['nif'] = '';
			$ProveedorUnico['direccion'] = '';
			$ProveedorUnico['telefono'] = '';
			$ProveedorUnico['movil'] = '';
			$ProveedorUnico['fax'] = '';
			$ProveedorUnico['email'] = '';	
			$ProveedorUnico['fechaalta'] = date('Y-m-d');
			$ProveedorUnico['idUsuario'] = $Usuario['id'];
			$estados[0]['porDefecto'] = "selected"; // Indicamos por defecto
			//$ProveedorUnico['id']= '';
		}
		
		if (!isset($error)){
			if(count($_POST)>0){
				// Ya enviamos el formulario y gestionamos lo enviado.
				$datos = $_POST;
				if($titulo === "Crear Proveedor"){
					// Quiere decir que ya cubrimos los datos del usuario nuevo.
					$resp = insertarProveedor($datos,$BDTpv,$tabla);
					if (isset($resp['error'])){
						$tipomensaje= "danger";
						$mensaje = "Nombre comercial de proveedor ya existe!";
						header('Location:proveedor.php?mensaje='.$mensaje.'&tipomensaje='.$tipomensaje);
						
					} else {
						$tipomensaje= "info";
						$mensaje = "Nuevo proveedor creado.";
						header('Location:ListaProveedores.php');
					}
				} else {
					// Quiere decir que ya modificamos los datos del ficha del cliente
					$ProveedorUnico['razonsocial'] =$datos['razonsocial'];
					//~ echo'<pre>';
					//~ print_r($datos);
					//~ echo '</pre>';
					$resp = modificarProveedor($datos,$BDTpv,$tabla);
					
					if (isset($resp['error'])){
						// Error de usuario repetido...
						$tipomensaje= "danger";
						$mensaje = "Razon social de proveedor ya existe!";
					} else {
						$tipomensaje= "info";
						$mensaje = "Su registro de proveedor fue editado.";
						
					}
					header('Location:proveedor.php?id='.$_GET['id'].'&mensaje='.$mensaje.'&tipomensaje='.$tipomensaje);
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
			$tipomensaje=$_GET['tipomensaje'];
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
			<a class="text-ritght" href="./ListaProveedores.php">Volver Atrás</a>
			<div class="col-md-12">
				
				<h3><?php echo $ProveedorUnico['razonsocial'];?></h3>

				<div class="col-md-3">
					<?php 
					// UrlImagen
					$img = './../../css/img/imgUsuario.png';
					?>
					<img src="<?php echo $img;?>" style="width:100%;">
				</div>

				<form action="" method="post" name="formProveedor">
				<div class="col-md-9">
					<div class="Datos">
						<div class="col-md-6 form-group">
							<label>Nombre comercial Proveedor:</label>
							<input type="text" id="nombrecomercial" name="nombrecomercial" <?php echo $ProveedorUnico['nombrecomercial'];?> placeholder="nombre" value="<?php echo $ProveedorUnico['nombrecomercial'];?>"   >
							
						</div>
						<div class="col-md-6 form-group">
							<label>Razon Social:</label> <!--//al enviar con POST los inputs se cogen con name="xx" PRE-->
							<input type="text" id="razonsocial" name="razonsocial" placeholder="razon social" value="<?php echo $ProveedorUnico['razonsocial'];?>"   >
							
						</div>
						<div class="col-md-6 form-group">
							<label>NIF:</label>
							<input type="text"	id="nif" name="nif" value="<?php echo $ProveedorUnico['nif'];?>" required>
						</div>
						<div class="col-md-6 form-group">
							<label>Id del proveedor:</label>
							<input type="text" id="idProveedor" name="idProveedor" value="<?php echo $ProveedorUnico['idProveedor'];?>"   readonly>
							
						</div>
						<div class="col-md-6 form-group">
							<label>Direccion:</label>
							<input type="text" id="direccion" name="direccion" value="<?php echo $ProveedorUnico['direccion'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Telefono:</label>
							<input type="text" id="telefono" name="telefono" value="<?php echo $ProveedorUnico['telefono'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Movil:</label>
							<input type="text" id="movil" name="movil" value="<?php echo $ProveedorUnico['movil'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Fax:</label>
							<input type="text" id="fax" name="fax" value="<?php echo $ProveedorUnico['fax'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Email:</label>
							<input type="text" id="email" name="email" value="<?php echo $ProveedorUnico['email'];?>"  >
						</div>
						<div class="col-md-6 form-group">
							<label>Fecha alta:</label>
							<input type="text" id="fechaalta" name="fechaalta" value="<?php echo $ProveedorUnico['fechaalta'];?>" readonly >
						</div>
						<div class="col-md-6 form-group">
							<label>Id Usuario:</label>
							<input type="text" id="idusuario" name="idusuario" value="<?php echo $Usuario['id'];?>"  readonly>
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
