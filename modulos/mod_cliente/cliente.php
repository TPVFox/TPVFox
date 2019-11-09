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
        include_once $URLCom.'/modulos/mod_cliente/clases/ClaseCliente.php';
        $ClasesParametros = new ClaseParametros('parametros.xml');  
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$CFormasPago=new FormasPago($BDTpv);
		$CtiposVen=new TiposVencimientos($BDTpv);
		$Cliente=new ClaseCliente();		
		$dedonde="cliente";
		$id=0;
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_cliente',$Usuario['id']);
		$configuracion=$configuracion['incidencias']; 
		
		$idTienda = $Tienda['idTienda'];
		$estados = array(); // Creo los estados de usuarios ( para select)
		$estados[0]['valor'] = 'inactivo'; // Por defecto
		$estados[1]['valor'] = 'activo';
        // Inicializamos variables como si fueramos crear ficha Usuario nuevo
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
			$formasPago=$CFormasPago->todas();
            $defaultPago = 0;
            $tiposVen=$CtiposVen->todos();
            $defaultVenci = "0";
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
				// Ahora anotamos cual es pago y vencimiento por defecto.
				$vencimiento_pago=json_decode($ClienteUnico['fomasVenci'], true);
				if(isset($vencimiento_pago)){
					if ($vencimiento_pago['formapago']>0){
						$defaultPago=$vencimiento_pago['formapago'];
					}
					if ($vencimiento_pago['vencimiento']>0){
						$defaultVenci=$vencimiento_pago['vencimiento'];
					}
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
        }
        
        // Ahora grabamos si pulso guardar.
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
        // Montamos html Option de forma de pago,vencimiento y estado con el valor por default
            $html_optionPago = '<option value="0">	Seleccione una forma </option>';
            foreach ($formasPago as $formaPago){
                $es_seleccionado = '';
                if ($defaultPago === $formaPago['id']){
                    $es_seleccionado = ' selected';
                }
                $html_optionPago .='<option value="'.$formaPago['id'].'"'.$es_seleccionado.'>'.$formaPago['descripcion'].'</option>';
            }

            $html_optionVenci = '<option value="0">	Seleccione vencimiento </option>';
            foreach ($tiposVen as $vencimiento){
                $es_seleccionado = '';
                if ($defaultVenci === $vencimiento['id']){
                    $es_seleccionado = ' selected';
                }
                $html_optionVenci .='<option value="'.$vencimiento['id'].'"'.$es_seleccionado.'>'.$vencimiento['descripcion'].'</option>';
            }
            $html_optionEstado= '';
            foreach ($estados as $i=>$estado){
                $es_seleccionado = '';
                if (isset($ClienteUnico['estado'])){
                    if($ClienteUnico['estado'] === $estado['valor']){
                        $es_seleccionado = ' selected';
                    }
                } else {
                    if ($i ===1){
                        $es_seleccionado = ' selected'; // Valor por defecto si no hay dato estad en cliente.
                    } 

                }
                $html_optionEstado .='<option value="'.$estado['valor'].'"'.$es_seleccionado.'>'.$estado['valor'].'</option>';
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
				
				<h4>Datos del cliente con ID: <input size=3 type="text" id="idCliente" name="idCliente" value="<?php echo $ClienteUnico['idClientes'];?>"   readonly></h4>

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
							<input  type="text" id="razonsocial" name="razonsocial" placeholder="razon social" value="<?php echo $ClienteUnico['razonsocial'];?>">
							 <div class="invalid-tooltip-nombre" display="none">
								No permitimos la doble comilla (") 
							</div>
						</div>
						<div class="col-md-6 form-group">
							<label>NIF:</label>
							<input type="text"	id="nif" name="nif" value="<?php echo $ClienteUnico['nif'];?>">
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
								echo $html_optionPago;
								?>
								
							</select>
							
						</div>
						
						<div class="col-md-6 form-group">
							<label for="sel1">Estado:</label>
							<select class="form-control" name="estado" id="sel1" style="width: 14em;">
								<?php 
								echo $html_optionEstado;
								?>
								
							</select>
						</div>
						<div class="col-md-6 form-group">
							<label for="sel1">Vencimiento por defecto:</label>
							<select class="form-control" name="vencimiento" id="sel1" style="width: 15em;">
								<?php
                                echo $html_optionVenci;
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
