<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_cliente/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom.'/controllers/parametros.php';
        include_once $URLCom.'/modulos/mod_cliente/clases/ClaseCliente.php';
        $ClasesParametros = new ClaseParametros('parametros.xml');  
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);      
		$Cliente=new ClaseCliente();		
		$dedonde="cliente";
		$id=0;
        $errores = array();
        $tablaHtml= array(); // Al ser nuevo, al crear ClienteUnico ya obtenemos array vacio.
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_cliente',$Usuario['id']);
		$configuracion=$configuracion['incidencias']; 
		$estados = array ('Activo','inactivo');
        // Inicializamos variables como si fueramos crear ficha Usuario nuevo
        $titulo = "Crear";       
		// Obtenemos id
		if (isset($_GET['id'])) {
			$id=$_GET['id']; // Obtenemos id para modificar.
            if ($id> 0){
                $titulo= "Modificar";
            }
        }
        $ClienteUnico=$Cliente->getClienteCompleto($id);        
        foreach($ClienteUnico['adjuntos'] as $key =>$adjunto){
            if (isset($adjunto['error'])){
                $errores[]=array ( 'tipo'=>'danger!',
                             'mensaje' => 'ERROR EN LA BASE DE !<br/>Consulta:'. $adjunto['consulta']
                             );
            } else {
                $tablaHtml[] = htmlTablaGeneral($adjunto['datos'], $HostNombre, $key);
            }
        }
        
        // Ahora grabamos si pulso guardar.
        if (count($errores) === 0){
            // Solo guardamos si no hay errores.
            if(isset($_POST['Guardar'])){
                $guardar=$Cliente->guardarCliente($_POST);
                if(isset ($guardar['comprobaciones']) && count($guardar['comprobaciones'])>0){
                    $errores= $guardar['comprobaciones'];
                    // Fallo debo meter los datos del POST para que no tenga que volver a meterlos.
                    $ClienteUnico = $_POST;
                } else{
                    // Todo fue bien , volvemos a listado.
                    // Dos posibles opciones.
                    // 1.- Redirecionar
                    //~ header('Location: ListaClientes.php');
                    // 2.- Recargar datos modificados.
                    $ClienteUnico=$Cliente->getClienteCompleto($guardar['id']);
                    $mensaje = 'Fue guardo correctamente';
                    $errores[]=$Cliente->montarAdvertencia('info',$mensaje);
                }  
            }
        }
        // Montamos html Option de forma de pago,vencimiento y estado con el valor por default
            $DefaultVenci = json_decode($ClienteUnico['formasVenci']); // obtenemos un objeto con vencimiento y formapago
            $vencimientos = $Cliente->getVencimientos();
            $html_optionVenci =  getHtmlOptions($vencimientos['datos'],$DefaultVenci->vencimiento);
            $formasPago = $Cliente->getFormasPago();
            $html_optionPago = getHtmlOptions($formasPago,$DefaultVenci->formapago);
            $html_optionEstado= '';
            foreach ($estados as $i=>$estado){
                $es_seleccionado = '';
                if (isset($ClienteUnico['estado'])){
                    if($ClienteUnico['estado'] === $estado){
                        $es_seleccionado = ' selected';
                    }
                } 
                $html_optionEstado .='<option value="'.$estado.'"'.$es_seleccionado.'>'.$estado.'</option>';
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
							<input type="text" id="nombre"  name="Nombre" <?php echo $ClienteUnico['Nombre'];?> placeholder="nombre" value="<?php echo $ClienteUnico['Nombre'];?>"  required >
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
							<label>Codigo Postal:</label>
							<input type="text" id="codigo_postal" name="codpostal" value="<?php echo $ClienteUnico['codpostal'];?>"   >
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
						echo htmlPanelDesplegable($num,$titulo,$tablaHtml[0]);
						?>
						<?php 
						$num = 2 ; // Numero collapse;
						$titulo = 'Facturas';
						echo htmlPanelDesplegable($num,$titulo,$tablaHtml[1]);
						?>
						<?php 
						$num = 3 ; // Numero collapse;
						$titulo = 'Albaranes';
						echo htmlPanelDesplegable($num,$titulo,$tablaHtml[2]);
						?>
						<?php 
						$num = 4 ; // Numero collapse;
						$titulo = 'Pedidos';
						echo htmlPanelDesplegable($num,$titulo,$tablaHtml[3]);
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
