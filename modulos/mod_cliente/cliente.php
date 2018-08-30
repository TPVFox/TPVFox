<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
        include $URLCom.'/head.php';
        include $URLCom.'/modulos/mod_cliente/funciones.php';
        include $URLCom.'/controllers/Controladores.php';
        include_once $URLCom.'/controllers/parametros.php';
        include_once $URLCom.'/clases/FormasPago.php';
        include_once $URLCom.'/clases/TiposVencimiento.php';
        include_once $URLCom.'/clases/cliente.php';
        include_once $URLCom.'/modulos/mod_cliente/clases/ClaseCliente.php';
        $ClasesParametros = new ClaseParametros('parametros.xml');  
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$CFormasPago=new FormasPago($BDTpv);
		$CtiposVen=new TiposVencimientos($BDTpv);
		$Ccliente=new Cliente($BDTpv);
		$Cliente=new ClaseCliente($BDTpv);		
		$dedonde="cliente";
		$id=0;
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_cliente',$Usuario['id']);
		$configuracion=$configuracion['incidencias']; 
		
		$idTienda = $Tienda['idTienda'];
		$estados = array(); // Creo los estados de usuarios ( para select)
		$estados[0]['valor'] = 'inactivo'; // Por defecto
		$estados[1]['valor'] = 'activo';
		// Obtenemos id
		if (isset($_GET['id'])) {
			$id=$_GET['id']; // Obtenemos id para modificar.
			$ClienteUnico=$Cliente->getCliente($id);
			$titulo = "Modificar";
			if (isset($ClienteUnico['error'])){
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
				$formaPago=json_decode($ClienteUnico['fomasVenci'], true);
				if(count($formaPago)>0){
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
			}
			$adjuntos=$Cliente->adjuntosCliente($id);
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
			
			$htmlTickets=htmlTablaGeneral($adjuntos['tickets']['datos'], $HostNombre, "ticket");
				
			$htmlFacturas=htmlTablaGeneral($adjuntos['facturas']['datos'], $HostNombre, "factura");
				
			$htmlAlbaranes=htmlTablaGeneral($adjuntos['albaranes']['datos'], $HostNombre, "albaran");
				
			$htmlPedidos=htmlTablaGeneral($adjuntos['pedidos']['datos'], $HostNombre, "pedido");
			
			
			
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
	
			if(isset($_POST['Guardar'])){
			$guardar=guardarCliente($_POST, $BDTpv);
			if($guardar['cliente']['error']=="0"){
				if($guardar['buscarCliente']['error']=="Existe"){
					$errores[7]=array ( 'tipo'=>'Info!',
								 'dato' => $guardar['buscarCliente']['consulta'],
								 'class'=>'alert alert-info',
								 'mensaje' => 'COINCIDENCIA!'
								 );
				}else{
					 header('Location: ListaClientes.php');
				}
				
			}else{
				$errores[7]=array ( 'tipo'=>'Danger!',
								 'dato' => $guardar['cliente']['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
			}
		}
		
		?>
	</head>
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
		 <script type="text/javascript" >
			<?php echo 'var configuracion='.json_encode($configuracion).';';?>	
		</script>
		<?php
        //~ include $URLCom.'/header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
		?>
     
		<div class="container">
			
				<?php 
				
				if (isset($errores)){
				foreach($errores as $error){
						echo '<div class="'.$error['class'].'">'
						. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
						. '</div>';
				}
				}
				?>
			
			
			<h1 class="text-center"> Cliente: <?php echo $titulo;?></h1>
			<form action="" method="post" name="formCliente">
					
			<a class="text-ritght" href="./ListaClientes.php">Volver Atrás</a>
            <a  class="btn btn-warning" onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion , 0, <?php echo $id ;?>);">Añadir Incidencia </a>
			<input type="submit" class="btn btn-primary" value="Guardar" name="Guardar" id="Guardar">
			
			<div class="col-md-12">
				
				<h4>Datos del cliente con ID:<?php echo $id?></h4>

				<div class="col-md-1">
					<?php 
					// UrlImagen
					$img = $HostNombre.'/css/img/imgUsuario.png';
					?>
					<img src="<?php echo $img;?>" style="width:100%;">
				</div>

			
				<div class="col-md-7">
					<div class="Datos">
						<div class="col-md-6 form-group">
							<label>Nombre Cliente:</label>
							<input type="text" id="nombre"  name="nombre" <?php echo $ClienteUnico['Nombre'];?> placeholder="nombre" value="<?php echo $ClienteUnico['Nombre'];?>"  required >
							 <div class="invalid-tooltip-nombre" display="none">
								No permitimos la doble comilla (") 
							</div>
						</div>
						<div class="col-md-6 form-group">
							<label>Razon Social:</label> <!--//al enviar con POST los inputs se cogen con name="xx" PRE-->
							<input  type="text" id="razonsocial" name="razonsocial" placeholder="razon social" value="<?php echo $ClienteUnico['razonsocial'];?>"  required >
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
				</form>
			</div>
			
		</div>
		<?php 
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';

include_once $URLCom.'/pie.php';
?>
 <script type="text/javascript">
        <?php 
        if(isset($_GET['estado'])){
            if($_GET['estado']=="ver"){
                ?>
                $(".container").find('input').attr("disabled", "disabled");
                $(".container").find('select').attr("disabled", "disabled");
                 $("#Guardar").css("display", "none");
                <?php
            }
        }
        
        ?>
        
        </script>
	</body>
</html>
