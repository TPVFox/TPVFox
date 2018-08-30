<!DOCTYPE html>
<html>
    <head>
        <?php
		
        include_once './../../inicial.php';
        include $URLCom.'/head.php';
        include_once  $URLCom.'/modulos/mod_proveedor/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
        include_once ($URLCom.'/controllers/parametros.php');
        include_once ($URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php');
        
        $ClasesParametros = new ClaseParametros('parametros.xml');  
		$Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$CProveedor= new ClaseProveedor($BDTpv);
		$dedonde="proveedor";
		$idProveedor=0;
			// ===========  datos proveedor segun id enviado por url============= //
		$idTienda = $Tienda['idTienda'];
		$Usuario = $_SESSION['usuarioTpv'];
	
		$estados = array(); // Creo los estados de usuarios ( para select)
		$estados[0]['valor'] = 'inactivo'; // Por defecto
		$estados[1]['valor'] = 'activo';
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_proveedor',$Usuario['id']);
		$configuracion=$configuracion['incidencias'];
		if (isset($_GET['id'])) {
			$idProveedor=$_GET['id'];
			// Modificar Ficha fichero
			$id=$_GET['id']; // Obtenemos id para modificar.
			$ProveedorUnico=$CProveedor->getProveedor($id);
			if (isset($ProveedorUnico['error'])){
				$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $ProveedorUnico['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
			} else {
			$ProveedorUnico=$ProveedorUnico['datos'][0];
			$titulo = "Modificar";
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
				$adjuntos=$CProveedor->adjuntosProveedor($id);
				$i=2;
				foreach($adjuntos as $adjunto){
					if(isset($adjunto['error'])){
						$errores[$i]=array ( 'tipo'=>'Danger!',
									 'dato' => $adjunto['consulta'],
									 'class'=>'alert alert-danger',
									 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
									 );
									 $i++;
					}
				
				}
					
				$htmlFacturas=htmlTablaGeneral($adjuntos['facturas']['datos'], $HostNombre, "factura");
					
				$htmlAlbaranes=htmlTablaGeneral($adjuntos['albaranes']['datos'], $HostNombre, "albaran");
					
				$htmlPedidos=htmlTablaGeneral($adjuntos['pedidos']['datos'], $HostNombre, "pedido");
				
			}
			
			
		} else {
			// Creamos ficha Usuario.
			$titulo = "Crear";
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
		}
		if(isset($_POST['Guardar'])){
			
			$guardar=guardarProveedor($_POST, $BDTpv);
			//~ echo '<pre>';
			//~ print_r($guardar);
			//~ echo '</pre>';
			if($guardar['Proveedor']['error']=="0"){
				if($guardar['comprobar']['error']=="Existe"){
					$errores[7]=array ( 'tipo'=>'Info!',
								 'dato' => $guardar['comprobar']['consulta'],
								 'class'=>'alert alert-info',
								 'mensaje' => 'COINCIDENCIA!'
								 );
				}else{
					 header('Location: ListaProveedores.php');
				}
			}else{
				$errores[7]=array ( 'tipo'=>'Danger!',
								 'dato' => $guardar['Proveedor']['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
			}
		}
		?>
		<!-- Cargamos libreria control de teclado -->
		
		
	</head>
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js"></script>
		 <script type="text/javascript" >
			<?php echo 'var configuracion='.json_encode($configuracion).';';?>	
		</script>
		<?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
        //~ include $URLCom.'/header.php';
		?>
     
		<div class="container">
			
				<?php 
				
				if (isset($errores)){
				foreach($errores as $error){
						echo '<div class="'.$error['class'].'">'
						. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
						. '</div>';
				}
	
				//~ return;
				}
				?>
			
			<h1 class="text-center"> Proveedor: <?php echo $titulo;?></h1>
			<form action="" method="post" name="formProveedor">
			<a class="text-ritght" href="./ListaProveedores.php">Volver Atrás</a>
            <a  class="btn btn-warning" onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion , 0, <?php echo $idProveedor ;?>);">Añadir Incidencia </a>
			<input type="submit" value="Guardar" name="Guardar" id="Guardar" class="btn btn-primary">
			<div class="col-md-12">
				
				<h4>Datos del proveedor con ID:<?php echo $id?></h4>

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
							<input type="text" id="nombrecomercial" name="nombrecomercial" <?php echo $ProveedorUnico['nombrecomercial'];?> placeholder="nombre" value="<?php echo $ProveedorUnico['nombrecomercial'];?>" required  >
							
						</div>
						<div class="col-md-6 form-group">
							<label>Razon Social:</label> <!--//al enviar con POST los inputs se cogen con name="xx" PRE-->
							<input type="text" id="razonsocial" name="razonsocial" placeholder="razon social" value="<?php echo $ProveedorUnico['razonsocial'];?>"   required>
							
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
						
						<div class="col-md-4 form-group">
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
				<div class="col-md-4">
					 <div class="panel-group">
						
						<?php 
						$num = 1 ; // Numero collapse;
						$titulo = 'Facturas';
						echo htmlPanelDesplegable($num,$titulo,$htmlFacturas);
						?>
						<?php 
						$num = 2 ; // Numero collapse;
						$titulo = 'Albaranes';
						echo htmlPanelDesplegable($num,$titulo,$htmlAlbaranes);
						?>
						<?php 
						$num = 3 ; // Numero collapse;
						$titulo = 'Pedidos';
						echo htmlPanelDesplegable($num,$titulo,$htmlPedidos);
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
        <script type="text/javascript">
        <?php 
        if(isset($_GET['estado'])){
            if($_GET['estado']=="ver"){
                ?>
                $(".container").find('input').attr("disabled", "disabled");
                 $("#Guardar").css("display", "none");
                <?php
            }
        }
        
        ?>
        
        </script>
	</body>
</html>
