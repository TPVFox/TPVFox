<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
        include ("./../../controllers/Controladores.php");
        include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
        $ClasesParametros = new ClaseParametros('parametros.xml');  
        
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$Usuario = $_SESSION['usuarioTpv'];
		if ($Usuario['estado'] === "Incorrecto"){
			return;	
		}
		
		include_once '../../clases/FormasPago.php';
		$CFormasPago=new FormasPago($BDTpv);
		include_once '../../clases/TiposVencimiento.php';
		$CtiposVen=new TiposVencimientos($BDTpv);
		include_once '../../clases/cliente.php';
		$Ccliente=new Cliente($BDTpv);
		include_once 'clases/ClaseCliente.php';
		$Cliente=new ClaseCliente($BDTpv);		
		$dedonde="cliente";
		$id=0;
		
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_cliente',$Usuario['id']);
		$configuracion=$configuracion['incidencias']; 
		?>
		<!-- Cargamos libreria control de teclado -->
		
		
	</head>
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
		 <script type="text/javascript" >
			<?php echo 'var configuracion='.json_encode($configuracion).';';?>	
		</script>
		<?php
        include './../../header.php';
		// ===========  datos cliente segun id enviado por url============= //
		$idTienda = $Tienda['idTienda'];
		//~ $tabla= 'clientes'; // Tablas que voy utilizar.
		$estados = array(); // Creo los estados de usuarios ( para select)
		$estados[0]['valor'] = 'inactivo'; // Por defecto
		$estados[1]['valor'] = 'activo';
		// Obtenemos id
		if (isset($_GET['id'])) {
			// Modificar Ficha Cliente
			$id=$_GET['id']; // Obtenemos id para modificar.
			//~ $ClienteUnico = verSelec($BDTpv,$id,$tabla);
			$ClienteUnico=$Cliente->getCliente($id);
			$titulo = "Modificar";
			//~ echo '<pre>';
			//~ print_r($ClienteUnico);
			//~ echo '</pre>';
			if (isset($ClienteUnico['error'])){
				//~ $error='NO CONTINUAR';
				//~ $tipomensaje= "danger";
				//~ $mensaje = "Id de usuario incorrecto ( ver get) <br/>".$ClienteUnico['consulta'];
				$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $ClienteUnico['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
			} else {
				$ClienteUnico=$ClienteUnico['datos'][0];
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
			if(isset($ClienteUnico['fomasVenci'])){
				$formaPago=json_decode($ClienteUnico['fomasVenci'], true);
				$formasPago=$CFormasPago->formadePagoSinPrincipal($formaPago['formapago']);
				$tiposVen=$CtiposVen->MenosPrincipal($formaPago['vencimiento']);
				if ($formaPago['formapago']>0){
					$principalForma=$CFormasPago->datosPrincipal($formaPago['formapago']);
				}else{
					$principalForma=0;
				}
				if ($formaPago['vencimiento']>0){
					$principalVenci=$CtiposVen->datosPrincipal($formaPago['vencimiento']);
				}else{
					$principalVenci=0;
				}
			}else{
				$formasPago=$CFormasPago->todas();
				$tiposVen=$CtiposVen->todos();
			}
			$tickets=$Cliente->getTicket($id);
			$htmlTickets=htmlTablaTickets($tickets['datos'], $HostNombre);
			$facturas=$Cliente->getFacturas($id);
			$htmlFacturas=htmlTablaFacturas($facturas['datos'], $HostNombre);
			$albaranes=$Cliente->getAlbaranes($id);
			$htmlAlbaranes=htmlTablaAlbaranes($albaranes['datos'], $HostNombre);
			$pedidos=$Cliente->getPedidos($id);
			$htmlPedidos=htmlTablaPedidos($pedidos['datos'], $HostNombre);
			//~ echo $htmlTickets;
			//~ echo '<pre>';
			//~ print_r($pedidos);
			//~ echo '</pre>';
		} else {
			// Creamos ficha Usuario.
			$titulo = "Crear";
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
			$formasPago=$CFormasPago->todas();
			$tiposVen=$CtiposVen->todos();
		}
		
		if (!isset($error)){
			if(count($_POST)>0){
				// Ya enviamos el formulario y gestionamos lo enviado.
				$datos = $_POST;
				if ($_POST['formapago']>0||$_POST['vencimiento']>0){
					$datosForma=array();
					$datosForma['formapago']=$_POST['formapago'];
					$datosForma['vencimiento']=$_POST['vencimiento'];
					$datosForma=json_encode($datosForma);
				}
				
				if($titulo === "Crear"){
					// Quiere decir que ya cubrimos los datos del usuario nuevo.
					
					$resp = insertarCliente($datos,$BDTpv,$tabla);
					$id=$resp['id'];
					if (isset ($datosForma)){
						$mod=$Ccliente->mofificarFormaPagoVenci($id,$datosForma );
					}
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
					$resp = modificarCliente($datos,$BDTpv,$tabla);
					if (isset ($datosForma)){
						$mod=$Ccliente->mofificarFormaPagoVenci($datos['idCliente'],$datosForma );
					}
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
				
				if (isset($errores)){
				foreach($errores as $error){
						echo '<div class="'.$error['class'].'">'
						. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
						. '</div>';
				}
	
				return;
				}
				?>
			
			<a  onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion , 0, <?php echo $id ;?>);">Añadir Incidencia <span class="glyphicon glyphicon-pencil"></span></a>
			<h1 class="text-center"> Cliente: <?php echo $titulo;?></h1>
			<a class="text-ritght" href="./ListaClientes.php">Volver Atrás</a>
			<div class="col-md-12">
				
				<h4>Datos del cliente con ID:<?php echo $id?></h4>

				<div class="col-md-1">
					<?php 
					// UrlImagen
					$img = './../../css/img/imgUsuario.png';
					?>
					<img src="<?php echo $img;?>" style="width:100%;">
				</div>

				<form action="" method="post" name="formCliente">
				<div class="col-md-7">
					<div class="Datos">
						<div class="col-md-6 form-group">
							<label>Nombre Cliente:</label>
							<input type="text" id="nombre"  name="nombre" <?php echo $ClienteUnico['Nombre'];?> placeholder="nombre" value="<?php echo $ClienteUnico['Nombre'];?>"   >
							 <div class="invalid-tooltip-nombre" display="none">
								No permitimos la doble comilla (") 
							</div>
						</div>
						<div class="col-md-6 form-group">
							<label>Razon Social:</label> <!--//al enviar con POST los inputs se cogen con name="xx" PRE-->
							<input  type="text" id="razonsocial" name="razonsocial" placeholder="razon social" value="<?php echo $ClienteUnico['razonsocial'];?>"   >
							 <div class="invalid-tooltip-nombre" display="none">
								No permitimos la doble comilla (") 
							</div>
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
							<label for="sel1">Forma de pago por defecto: </label>
							<select class="form-control" name="formapago" id="sel1" style="width: 15em;">
								<?php 
								if (isset($principalForma)){
								?>
								<option value="<?php echo $principalForma['id'];?>" ><?php echo $principalForma['descripcion'];?></option>
								<?php 
							}else{
								?>
								<option value="0" >	Seleccione una forma </option>
								<?php 
							}
								foreach ($formasPago as $forma){
								?>
									<option value="<?php echo $forma['id'];?>" >
									<?php echo $forma['descripcion'];?>
									</option>
								<?php
								}
								?>
								
							</select>
							
						</div>
						
						<div class="col-md-6 form-group">
							<label for="sel1">Estado:</label>
							<select class="form-control" name="estado" id="sel1" style="width: 14em;">
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
							<label for="sel1">Vencimiento por defecto:</label>
							<select class="form-control" name="vencimiento" id="sel1" style="width: 15em;">
								<?php 
								if (isset($principalVenci)){
								?>
								<option value="<?php echo $principalVenci['id'];?>" ><?php echo $principalVenci['descripcion'];?></option>
								<?php 	
								}else{
								?>
								<option value="0" >Seleccione un tipo 	</option>
								<?php 
							}
								foreach ($tiposVen as $tipo){
								?>
									<option value="<?php echo $tipo['id'];?>" >
									<?php echo $tipo['descripcion'];?>
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
						$titulo = 'Tickets';
						echo htmlPanelDesplegable($num,$titulo,$htmlTickets);
						?>
						<?php 
						$num = 2 ; // Numero collapse;
						$titulo = 'Facturas';
						echo htmlPanelDesplegable($num,$titulo,$htmlFacturas);
						?>
						<?php 
						$num = 3 ; // Numero collapse;
						$titulo = 'Albaranes';
						echo htmlPanelDesplegable($num,$titulo,$htmlAlbaranes);
						?>
						<?php 
						$num = 4 ; // Numero collapse;
						$titulo = 'Pedidos';
						echo htmlPanelDesplegable($num,$titulo,$htmlPedidos);
						?>
						 </div>
					<!-- Aquí irá el código de los grupos-->
					</div>
				<div class="col-md-12">
					<input type="submit" value="Guardar">
				
				<div class="col-md-9">
				</form>
			</div>
			
		</div>
		<?php 
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
?>

	</body>
</html>
