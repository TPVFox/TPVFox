<!DOCTYPE html>
<html>
    <head>
        <?php
        // Recuerda que la variable $Usuario es global, por decirlo de una forma.
        // No debemos utilizarla para tomar los datos ficha del usuario.
		include_once './../../inicial.php';
		include_once $URLCom.'/head.php';      
		include_once $URLCom.'/modulos/mod_usuario/funciones.php';
		include_once $URLCom.'/modulos/mod_usuario/clases/claseUsuarios.php';
        include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
        //~ include_once $URLCom.'/clases/ClasePermisos.php';
        $id = (isset($_GET['id']) ? $_GET['id'] : 0); // Valor id es 0 o el get
		$CUsuario=new ClaseUsuarios($BDTpv);
        $Cincidencias=new ClaseIncidencia($BDTpv);
		$tabla= 'usuarios'; // Tablas que voy utilizar.
		$AtributoLogin = '';
        // Creo los estados de usuarios ( para select)
		$estados = array(0 => array('valor' => 'inactivo',
                                    'porDefecto' => "selected"),
                         1 => array('valor' => 'activo')
                        );
        // No hace falta todos los usuarios para poder copiar permisos.
        $usuarios=$CUsuario->todosUsuarios();
        // Valores por defecto de ficha cuando id = 0
        $titulo = "Crear Usuario";
        $UsuarioUnico = array(
                            'fecha'     => date("Y-m-d"),
                            'group_id'  => 0,
                            'password'  => '',
                            'username'  => '',
                            'nombre'    => '',
                            'id'        => ''
                        );
        $passwrd= '';

		?>
	</head>
	
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_usuario/funciones.js"></script>
		<?php
         include_once $URLCom.'/modulos/mod_menu/menu.php';
		// ===========  datos usuario segun id enviado por url============= //
		
		if (isset($_GET['id'])) {
			// Modificar Ficha Usuario
            $id_array=array('id'=>$id);
            $permisosUsuario=$ClasePermisos->getPermisosUsuario($id_array);
            $permisosUsuario=$permisosUsuario['resultado'];
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
				$configuracionesUsuario=$CUsuario->getConfiguracionModulo($id);
				$incidenciasUsuario=$Cincidencias->incidenciasSinResolverUsuario($id);
                $datos=0;
                if (isset($configuracionesUsuario['datos'])){
                    $datos=$configuracionesUsuario['datos'];
                }
				$htmlConfiguracion=htmlTablaGeneral($datos, $HostNombre, "configuracion");
                $htmlInicidenciasDesplegable=htmlTablaIncidencias($incidenciasUsuario);
			}
		} 
		
		if (!isset($error)){
    
			if(count($_POST)>0 ){
				// Ya enviamos el formulario y gestionamos lo enviado.
				$datos = $_POST;
				if($titulo === "Crear Usuario"){
					// Quiere decir que ya cubrimos los datos del usuario nuevo.
					$resp = insertarUsuario($datos,$BDTpv,$Tienda['idTienda'],$tabla);
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
                    $permiso=0;
                    if(isset($_POST['permiso_'.$i])){
                        $permiso=1;
                    }
                    $mod=$ClasePermisos->modificarPermisoUsuario($permisos, $permiso, $id);
                    $i++;
                }
                $id_array=array('id'=>$resp['id']);
                $permisosUsuario=$ClasePermisos->getPermisosUsuario($id_array);
                $permisosUsuario=$permisosUsuario['resultado'];
                $UsuarioUnico = verSelec($BDTpv,$id_array['id'],$tabla);
			}
		}
        $htmlPermisosUsuario=htmlPermisosUsuario($permisosUsuario, $ClasePermisos->getAccion("permiso"), $ClasePermisos, $usuarios);

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
