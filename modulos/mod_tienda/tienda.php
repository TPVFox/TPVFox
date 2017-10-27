<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
		// Obtenemos id
		if ($_GET['id']) {
			$id = $_GET['id'];
		} else {
			// NO hay parametro .
			$error = "No podemos continuar";
		}
		
		
		// ===========  datos usuario segun id enviado por url============= //
			$tabla= 'tiendas';
			$idBusqueda ='idTienda='.$id;
			$TiendaUnica = verSelec($BDTpv,$idBusqueda,$tabla);
			// Solo debería haber un resultado, creamos de ese resultado unico, pero debería comprobarlo.
			
			//~ echo '<pre>';
				//~ print_r($id);
			//~ echo '</pre>';
		?>
	
	</head>
	<body>
		<?php
        include './../../header.php';
			if(count($_POST)>0){
				if (isset($id)){
					// Comprobamos: 
					//($dato['password']=== 'password') olvidarme de insertar psw
					$datos = $_POST;
					$resp = modificarDatos($datos,$BDTpv,$tabla);
					//echo $resp['consulta'];
					if (isset($resp['error'])){
						$tipomensaje= "danger";
						$mensaje = "Nombre de usuario ya existe!";
					} else {
						// Mandas funcion a grabar.
						//$tipomensaje= "danger";
					
						$tipomensaje= "info";
						$mensaje = "Su registro de usuario fue editado.";
					}
				} else {
					$datos = $_POST;
					$resp = insertarDatos($datos,$BDTpv,$tabla);
					//echo $resp['consulta'];
					//echo $resp['consulta1'];
					if (isset($resp['error'])){
						$tipomensaje= "danger";
						$mensaje = $resp['error'];
					} else {
						$tipomensaje= "info";
						$mensaje = "Nueva tienda creada.";
					}
					
					
				}
			//~ echo '<pre>';
			//~ print_r($_POST);
			//~ echo '</pre>';
			};
			
			$estados = array(); // Por defecto
			$estados[0]['valor'] = 'cerrado'; // Por defecto
			$estados[1]['valor'] = 'activo';
			if (!isset($id)){ ///nuevo
				$titulo = "Crear Tienda";
				$TiendaUnica = array();
				$TiendaUnica['NombreComercial'] = '';
				$TiendaUnica['razonsocial'] = '';
				$TiendaUnica['nif'] = '';
				$TiendaUnica['direccion'] = '';
				$TiendaUnica['telefono'] = '';
				$TiendaUnica['ano'] = '';
				$estados[0]['porDefecto'] = "selected"; // Indicamos por defecto
				$TiendaUnica['id']= '';
				
			} else {
				$titulo = "Modificar Tienda";
				
				$i = 0;
				//~ echo 'Alfo:'.$UsuarioUnico['estado'];
				foreach ($estados as $estado){
					if ($TiendaUnica['estado'] == $estado['valor']){
						$estados[$i]['porDefecto'] = "selected"; // Indicamos por defecto
					}
					$i++;
				} 
			}
			
			
			?>
     
		<div class="container">
			<?php if (isset($mensaje)){   ?> 
			<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje ;?></div>
			<?php }?>
			<h1 class="text-center"> <?php echo $titulo.':'. $TiendaUnica['NombreComercial'];?></h1>
			<a class="text-ritght" href="./ListaTiendas.php">Volver Atrás</a>

			<div class="col-md-12">
				<div class="col-md-3">
					<?php 
					// UrlImagen
					$img = './../../css/img/tienda.svg';
					?>
					<a href="<?php echo $img;?>"><img src="<?php echo $img;?>" style="width:100%;"></a>
				</div>
				<form action="" method="post" name="formUsuario">
				<div class="col-md-9">
					<div class="Datos">
						<div class="col-md-12">
							<div class="form-group">
								<label>Tipo de tienda</label>
								<label class="radio-inline">
								<input type="radio" name="tipoTienda" value="fisica" title="Tienda en local">
								Tienda Fisica</label>
								<label class="radio-inline">
								<input type="radio" name="tipoTienda" value="web" title="Tienda en la web">
								Web de la tienda</label>
							</div>
						</div>
						
						<div class="col-md-4">
							<h3>Datos Comunes</h3>
							<div class="form-group">
								<label class="col-md-5">Id(<span title="Id de Tienda">*</span>):
								<input type="text" id="idtienda" name="idtienda" value="<?php echo $TiendaUnica['idTienda'] ;?>" readonly size="2">
								</label>
								<label class="col-md-7">NIF:
								<input type="text" id="nif" name="nif" placeholder="B36332211"  
								value="<?php echo $TiendaUnica['nif'];?>" required size="9">
								</label>
							</div>
							
							<div class="form-group">
								<label>Razón social:</label>
								<input type="text" id="razonsocial" name="razonsocial" placeholder="razon social" value="<?php echo $TiendaUnica['razonsocial'];?>" required  >
							</div>
							<div class="form-group">
								
							</div>
							<div class="form-group">
								<label>Teléfono:</label>
								<input type="text" id="telefono" name="telefono" placeholder="986 22 22 22"  value="<?php echo $TiendaUnica['telefono'];?>"  required >
							</div>
							<div class="form-group">
							<label for="sel1">Estado:
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
							</label>
							</div>
						
						</div>
						<div class="col-md-4">
							<h3>Datos Tienda Fisica</h3>
							<div class="form-group">
								<label>Nombre Comercial:</label>
								<input type="text" id="nombrecomercial" name="nombrecomercial" placeholder="nombre comercial" value="<?php echo $TiendaUnica['NombreComercial'];?>" required  >
							</div>
							<div class="form-group">
								<label>Dirección:</label>
								<input type="text" id="direccion" name="direccion" placeholder="direccion"  value="<?php echo $TiendaUnica['direccion'];?>"  required >
							</div>
							
							<div class="form-group">
								<label>Año Contable:</label>
								<input type="text" id="ano" name="ano" placeholder="2014"  value="<?php echo $TiendaUnica['ano'];?>"  required >
							</div>
						</div>
						<div class="col-md-4">
							<h3>Datos Tienda Web</h3>
							<div class="form-group">
								<label>Dominio:(<span title="Sin http, ni www">*</span>)</label>
								<input type="text"  name="dominio" placeholder="dominio.com" value="" required  >
							</div>
							<div class="form-group">
								<label>Base Datos:(<span title="Nombre bases datos">*</span>)</label>
								<input type="text" name="nom_bases_datos" placeholder="name_basedatos" value="" required  >
							</div>
							<div class="form-group">
								<label>Usuario:(<span title="Nombre Usuario">*</span>)</label>
								<input type="text" name="nom_usuario_base_datos" placeholder="user_name" value="" required  >
							</div>
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
