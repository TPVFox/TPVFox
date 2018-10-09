<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
		include_once './../../inicial.php';
		include_once $URLCom.'/head.php';
       
		include_once $URLCom.'/modulos/mod_usuario/funciones.php';
		include_once $URLCom.'/modulos/mod_usuario/clases/claseUsuarios.php';
        include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
        include_once $URLCom.'/clases/ClasePermisos.php';
        $admin=0;
       
		$Cusuario=new ClaseUsuarios($BDTpv);
        $Cincidencias=new ClaseIncidencia($BDTpv);
		//~ if ($Usuario['estado'] === "Incorrecto"){
			//~ return;	
		//~ }
		
		?>
		<!-- Cargamos libreria control de teclado -->
		
		
	</head>
	
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_usuario/funciones.js"></script>
		<?php
        //~ include './../../header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
		// ===========  datos usuario segun id enviado por url============= //
		$idTienda = $Tienda['idTienda'];
		$tabla= 'usuarios'; // Tablas que voy utilizar.
		$AtributoLogin = '';
		$estados = array(); // Creo los estados de usuarios ( para select)
		$estados[0]['valor'] = 'inactivo'; // Por defecto
		$estados[1]['valor'] = 'activo';
		// Obtenemos id
		
		
		
		if (isset($_GET['id'])) {
            
			// Modificar Ficha Usuario
			$id=$_GET['id']; // Obtenemos id para modificar.
            $Usuario=array('id'=>$id);
            $permisosUsuario=$ClasePermisos->getPermisosUsuario($Usuario);
            $permisosUsuario=$permisosUsuario['resultado'];
           //~ echo '<pre>';
           //~ print_r($permisosUsuario);
           //~ echo '</pre>';
			$UsuarioUnico = verSelec($BDTpv,$id,$tabla);
			$titulo = "Modificar Usuario";
			$passwrd= 'password'; // Para mostrar ***** en password
			if (isset($UsuarioUnico['error'])){
				$error='NOCONTINUAR';
				$tipomensaje= "danger";
				$mensaje = "Id de usuario incorrecto ( ver get) <br/>".$UsuarioUnico['consulta'];
			} else {
				// Cambiamos atributo de login para que no pueda modificarlo.
				$AtributoLogin='readonly';
				// Ahora ponemos el estado por defecto segun el dato obtenido en la BD .
				if (count($_POST) ===0){
				
				$i = 0;
					foreach ($estados as $estado){
						if ($UsuarioUnico['estado'] == $estado['valor']){
						$estados[$i]['porDefecto'] = "selected"; // Indicamos por defecto
						}
					$i++;
					}
				}
				$configuracionesUsuario=$Cusuario->getConfiguracionModulo($id);
                $usuarios=$Cusuario->todosUsuarios();
				$incidenciasUsuario=$Cincidencias->incidenciasSinResolverUsuario($id);
              
                if (isset($configuracionesUsuario['datos'])){
                    $datos=$configuracionesUsuario['datos'];
                }else{
                    $datos=0;
                }
				$htmlConfiguracion=htmlTablaGeneral($datos, $HostNombre, "configuracion");
                $htmlInicidenciasDesplegable=htmlTablaIncidencias($incidenciasUsuario);
              
                if($ClasePermisos->getAccion("permiso")==1){
                    $admin=1;
                }
                
                $htmlPermisosUsuario=htmlPermisosUsuario($permisosUsuario, $admin, $ClasePermisos, $usuarios);
               
			}
		} else {
			// Creamos ficha Usuario.
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
		}
		
		if (!isset($error)){
			if(count($_POST)>0){
				// Ya enviamos el formulario y gestionamos lo enviado.
				$datos = $_POST;
				if($titulo === "Crear Usuario"){
					// Quiere decir que ya cubrimos los datos del usuario nuevo.
					$resp = insertarUsuario($datos,$BDTpv,$idTienda,$tabla);
					if (isset($resp['error'])){
						$tipomensaje= "danger";
						$mensaje = "Nombre de usuario ya existe!";
					} else {
						$tipomensaje= "info";
						$mensaje = "Nuevo usuario creado.";
					}
				} else {
					// Quiere decir que ya modificamos los datos del ficha del usuario
					$UsuarioUnico['nombre'] =$datos['nombreEmpleado'];
					
					$resp = modificarUsuario($datos,$BDTpv,$tabla);
					if (isset($resp['error'])){
						// Error de usuario repetido...
						$tipomensaje= "danger";
						$mensaje = "Nombre de usuario ya existe!";
					} else {
						$tipomensaje= "info";
						$mensaje = "Su registro de usuario fue editado.";
					}
				};
                $i=0;
                foreach($permisosUsuario as $permisos){
                     
                    if(isset($_POST['permiso_'.$i])){
                        $permiso=1;
                    }else{
                        $permiso=0;
                    }
                    $mod=$ClasePermisos->modificarPermisoUsuario($permisos, $permiso, $id);
                    $i++;
                }
                
                $Usuario=array('id'=>$id);
                $permisosUsuario=$ClasePermisos->getPermisosUsuario($Usuario);
                
                 $permisosUsuario=$permisosUsuario['resultado'];
                  if($ClasePermisos->getAccion("permiso")==1){
                    $admin=1;
                }
                 $htmlPermisosUsuario=htmlPermisosUsuario($permisosUsuario, $admin,  $ClasePermisos, $usuarios);
			}
		}
		
		?>
     
		<div class="container">
				
			<?php 
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
			<form action="" method="post" name="formUsuario">
                <?php if(!isset($_GET['inicio'])){
                    ?>
                    <a class="text-ritght" href="./ListaUsuarios.php">Volver Atrás</a>
                    <?php
                }?>
			
			<input type="submit" value="Guardar">
			<div class="col-md-12">
				
				<h3><?php echo $UsuarioUnico['nombre'];?></h3>
				<div class="col-md-1">
						
					<?php 
					// UrlImagen
					$img = './../../css/img/imgUsuario.png';
					?>
					<img src="<?php echo $img;?>" style="width:100%;">
				</div>
				
				<div class="col-md-7">
					<div class="Datos">
						<div class="col-md-6 form-group">
							<label>Nombre Usuario/login:</label>
							<input type="text" id="username" name="username" <?php echo $AtributoLogin;?> placeholder="usuario/login" value="<?php echo $UsuarioUnico['username'];?>"   >
							
						</div>
						<div class="col-md-6 form-group">
							<label>Nombre empleado:</label>
							<input type="text" id="nombreEmpleado" name="nombreEmpleado" placeholder="nombre empleado" value="<?php echo $UsuarioUnico['nombre'];?>" required  >
							
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
							<select class="form-control" name="grupo" id="sel1"  style="width: 100px;">
								<option value="<?php echo $UsuarioUnico['group_id'];?>" selected><?php echo $UsuarioUnico['group_id'];?></option>
							</select>
							
						</div>
						<div class="col-md-6 form-group">
							<label for="sel1">Estado:</label>
							<select class="form-control" name="estado" id="sel1"  style="width: 150px;">
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
							<input type="password" class="form-control"  style="width: 150px;" id="pwd" name="password" placeholder="contraseña" value="<?php echo $passwrd ;?>" required>
						</div>
						
						
					</div>
					
				</div>
				<div class="col-md-4">
					 <div class="panel-group">
						 <?php 
						$num = 1 ; // Numero collapse;
						$titulo = 'Configuración Modulos';
						echo htmlPanelDesplegable($num,$titulo,$htmlConfiguracion);
                        $num=2;
                        $titulo='Incidencias Sin Resolver';
                        echo htmlPanelDesplegable($num, $titulo, $htmlInicidenciasDesplegable);
                      
                        
                            $num=3;
                            $titulo='Permisos';
                            echo htmlPanelDesplegable($num, $titulo, $htmlPermisosUsuario);
                        
						?>
					</div>
				</div>
				<div class="col-md-12">
<!--
					<input type="submit" value="Guardar">
-->
	</form>			
				<div class="col-md-9">
				
			</div>
			
		</div>
	</body>
</html>
