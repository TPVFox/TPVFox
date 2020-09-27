<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_proveedor/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom.'/controllers/parametros.php';
        include_once $URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php';
        $ClasesParametros = new ClaseParametros('parametros.xml');  
		$Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$CProveedor= new ClaseProveedor();
		$dedonde="proveedor";
		$id=0;
		$errores = array();
        $tablaHtml= array(); // Al ser nuevo, al crear ClienteUnico ya obtenemos array vacio.
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_proveedor',$Usuario['id']);
		$configuracion=$configuracion['incidencias'];
        $estados = array('Activo','inactivo');
		if (isset($_GET['id'])) {
			$id=$_GET['id']; // Obtenemos id para modificar.
        }
		$ProveedorUnico=$CProveedor->getProveedorCompleto($id);
        foreach($ProveedorUnico['adjuntos'] as $key =>$adjunto){
            if (isset($adjunto['error'])){
                $errores[]=array ( 'tipo'=>'danger',
                             'mensaje' => 'ERROR EN LA BASE DE !<br/>Consulta:'. $adjunto['consulta']
                             );
            } else {
                $tablaHtml[] = htmlTablaGeneral($adjunto['datos'], $HostNombre, $key);
            }
        }

        // Solo permitimos guarfar si realmente no hay errores.
        // ya que consideramos que son graves y no podemos continuar. ( bueno a lo mejor.. :-)
        if (count($errores) === 0){
            if(isset($_POST['Guardar'])){
                $guardar=$CProveedor->guardarProveedor($_POST);
                $ProveedorUnico=$guardar['datos'];
                if($guardar['estado'] === 'OK'){
                        // Todo fue bien , volvemos a listado.
                        // Dos posibles opciones deberíamos tener un parametro configuracion.
                        // 1.- Redirecionar
                        // header('Location: ListaProveedores.php');
                        // 2.- Recargar datos modificados.
                        $mensaje = 'Fue guardo correctamente';
                        $errores[]=$CProveedor->montarAdvertencia('info',$mensaje);
                } else {
                    // Hubo error grave, estado = KO
                    $errores[] = $CProveedor->montarAdvertencia('danger','No se grabo por un error grave');
                    $errores[] = $CProveedor->montarAdvertencia('danger',$guardar['error']);
                }
            }
        }
        
        // Ahora $input_disabled
         $input_disabled= 'disabled'; // Por defecto solo dejo ver.
        if (isset($_GET['accion'])) {
            if ($_GET['accion']=="editar"){
               $input_disabled='';
               $titulo= "Modificar";
            } else  {
                $titulo= "Ver";
            }
        }

        // Montamos titulo y campos que no se cubren cuando es nuevo.
        if ($id == 0){
            $titulo= "Crear";
            $ProveedorUnico['idProveedor']  ='';
            $ProveedorUnico['fecha_creado'] ='';
            $input_disabled='';
        }
        
		?>
		
		
	</head>
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js"></script>
		 <script type="text/javascript" >
			<?php echo 'var configuracion='.json_encode($configuracion).';';?>	
		</script>
		<?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
		?>
     
		<div class="container">
			
				<?php
				if (isset($errores) && count($errores)>0){
                    foreach($errores as $error){
                        echo '<div class="alert alert-'.$error['tipo'].'">'
                        . '<strong>'.$error['tipo'].' </strong><br/> ';
                        if (is_array($error['mensaje'])){
                            echo '<pre>';
                            print_r($error['mensaje']);
                            echo '</pre>';
                        } else {
                            echo $error['mensaje'];
                        }
                        echo '</div>';
                    }
                }
				?>
			
			<h1 class="text-center"> Proveedor: <?php echo $titulo;?></h1>
			<form action="" method="post" name="formProveedor">
			<a class="text-ritght" href="./ListaProveedores.php">Volver Atrás</a>
            <a  class="btn btn-warning" onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion , 0, <?php echo $id ;?>);">Añadir Incidencia </a>
            <?php
            if(isset($_GET['accion']) && $_GET['accion']=="editar"){
            ?>
                <input type="submit" value="Guardar" name="Guardar" id="Guardar" class="btn btn-primary">
            <?php
            }
            ?>
            <div class="col-md-12">
				<h4>Datos del proveedor con ID:<input size="5" type="text" id="idProveedor" name="idProveedor" value="<?php echo $ProveedorUnico['idProveedor'];?>"   readonly></h4>

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
							
							<label>Nombre comercial Proveedor:</label>
							<input type="text" id="nombrecomercial" name="nombrecomercial" <?php echo $input_disabled;?> size="40" placeholder="nombre" value="<?php echo $ProveedorUnico['nombrecomercial'];?>" required  >
							
						</div>
						<div class="col-md-6 form-group">
							<label>Razon Social:</label> <!--//al enviar con POST los inputs se cogen con name="xx" PRE-->
							<input type="text" id="razonsocial" name="razonsocial" <?php echo $input_disabled;?> size="40" placeholder="razon social" value="<?php echo $ProveedorUnico['razonsocial'];?>">
							
						</div>
						<div class="col-md-6 form-group">
							<label>NIF:</label>
							<input type="text"	id="nif" name="nif" <?php echo $input_disabled;?> value="<?php echo $ProveedorUnico['nif'];?>">
						</div>
						<div class="col-md-6 form-group">
							<label>Direccion:</label>
							<input type="text" id="direccion" name="direccion" <?php echo $input_disabled;?> size="50" value="<?php echo $ProveedorUnico['direccion'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Telefono:</label>
							<input type="text" id="telefono" name="telefono" <?php echo $input_disabled;?> value="<?php echo $ProveedorUnico['telefono'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Movil:</label>
							<input type="text" id="movil" name="movil" <?php echo $input_disabled;?> value="<?php echo $ProveedorUnico['movil'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Fax:</label>
							<input type="text" id="fax" name="fax" <?php echo $input_disabled;?> value="<?php echo $ProveedorUnico['fax'];?>"   >
						</div>
						<div class="col-md-6 form-group">
							<label>Email:</label>
							<input type="text" id="email" name="email" <?php echo $input_disabled;?> size="40" value="<?php echo $ProveedorUnico['email'];?>"  >
						</div>
						<div class="col-md-6 form-group">
							<label>Fecha alta:</label>
							<input type="text" id="fechaalta" name="fecha_creado" value="<?php echo $ProveedorUnico['fecha_creado'];?>" readonly >
						</div>
						
						<div class="col-md-6 form-group">
							<label for="sel1">Estado:</label>
                            <div class="col-md-6">
                            <select class="form-control" name="estado" <?php echo $input_disabled;?> id="sel1">
								<?php 
								foreach ($estados as $estado){
                                    $default ='';
                                    if($ProveedorUnico['estado']===$estado){
                                        $default = "selected";
                                    }
								    echo '<option size="10" value="'.$estado
                                        .'" '.$default.'>'.$estado.'</option>';
								}
								?>
								
							</select>
                            </div>
						</div>
						
						
					</div>
					
				</div>
				<div class="col-md-4">
					 <div class="panel-group">
						<?php 
						$num = 1 ; // Numero collapse;
						$titulo = 'Facturas';
						echo htmlPanelDesplegable($num,$titulo, $tablaHtml[0]);
						?>
						<?php 
						$num = 2 ; // Numero collapse;
						$titulo = 'Albaranes';
						echo htmlPanelDesplegable($num,$titulo, $tablaHtml[1]);
						?>
						<?php 
						$num = 3 ; // Numero collapse;
						$titulo = 'Pedidos';
						echo htmlPanelDesplegable($num,$titulo, $tablaHtml[2]);
						?>
						 </div>
				</div>
				
				</form>
			</div>
		<?php // Incluimos paginas modales
        echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
        include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
        // hacemos comprobaciones de estilos 
        ?>
		</div>
	</body>
</html>
