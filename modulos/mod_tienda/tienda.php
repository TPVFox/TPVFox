<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
		// Reinicio variables
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_tienda/funciones.php';
        
		// Variables por defecto:
		$titulo = "Crear Tienda";
		$img = $HostNombre.'/css/img/tienda.svg'; // Url imagen por defecto.
		// Nombre tabla que vamos utilizar.
		$tabla= 'tiendas';
		// Array por defecto.
		// Definicion de campos 
		$Campos = array('fisica' => array(
									'0' => array( 
										'id' => 'NombreComercial'
									),
									'1' => array( 
										'id' => 'direccion'
									),
									'2' => array( 
										'id' => 'ano'
									)
								),
						'web' => array(
									'0' => array( 
										'id' => 'dominio'
									),
									'1' => array( 
										'id' => 'key_api'
									)
								),
						'principal' => array( // Tienda que utilizamos.
									'0' => array( 
										'id' => 'NombreComercial'
									),
									'1' => array( 
										'id' => 'direccion'
									),
									'2' => array( 
										'id' => 'ano'
									)
								)
						);

		
		
		
		
		
		$TiendaUnica = array(
					'NombreComercial'	=> '',
					'razonsocial' 		=> '',
					'nif'				=> '',
					'direccion' 		=> '',
					'telefono' 			=> '',
					'ano' 				=> '',
					'id'				=> ''
					);
		$id = '' ; // Por defecto.
		// Definimos posibles estados para Select.
		$estados = array(
			'0' => array( 
					'valor' =>'cerrado',
					'porDefecto' => 'selected' // Indicamos por defecto
					),
			'1' => array(
					'valor' =>'activo',
					'porDefecto' =>  ''
					)
		);
		// Definimos posibles tipos de tiendas y estado por defecto.
		$tiposTiendas = array(
			'0' => array (
					'value' 	=> "fisica",
					'title' 	=> "Tienda en local",
					'texto' 	=> "Tienda fisica",
					'checked'	=> "checked", // Indicamos por defecto
					'display'	=> '' ,// Indicamos por defecto que si muestra campos tienda fisica
					'disabled' 	=> 'disabled' // por defecto esta activo el cambio tipo tienda.

					),
			'1' =>  array (
					'value' 	=> "web",
					'title' 	=> "Tienda On-Line",
					'texto' 	=> "Web de la tienda",
					'checked'	=> "",
					'display'	=> 'style="display:none;"', // Indicamos NO se campos tienda web por defecto
					'disabled' 	=> 'disabled' // por defecto esta desactivado el cambio tipo tienda.
					),
			'2' =>  array (
					'value' 	=> "principal",
					'title' 	=> "Tienda Actual",
					'texto' 	=> "tienda que estás",
					'checked'	=> "",
					'display'	=> 'style="display:none;"', // Indicamos por defecto que si muestra campos tienda fisica
					'disabled' 	=> 'disabled' // por defecto esta desactivado el cambio tipo tienda.
					)
		);
		
		// Ahora comprobamos si tenemos POST. Es decir se envio ya el formulario
		if(count($_POST)>0){
			// Si ya venimos de vuelta de mandar el formulario.
			// [CREAMOS LOS CAMPOS OBLICATORIOS];
			$campos_obligatorios= array('razonsocial','nif','telefono','tipoTienda','estado');
			// [OBTENEMOS LOS CAMPOS PARA EL TIPO TIENDA]
			// Recuerda que los campos es una array indexado [id].
			foreach ($Campos[$_POST['tipoTienda']] as $campo) {
				$camposExtra[] = $campo['id'];
			}
			// Montamos array de campos a enviar.
			$campos_enviar = array_merge($campos_obligatorios,$camposExtra);
			$datos= array();
			foreach ($campos_enviar as $key => $campo){
				$datos[$campo] = $_POST[$campo];
			}
			if (intval($_POST['idtienda']) > 0){
				// Entramos si ya existe tienda, porque tiene id, por lo que modificamos.
				
				// Comprobamos: 
				$resp = modificarDatos($datos,$BDTpv,$tabla,$_POST['idtienda']);
				if (isset($resp['error'])){
					echo '<pre>';
					print_r($resp);
					echo '</pre>';
					$tipomensaje= "danger";
					$mensaje = "Error a la hora modificar datos de la tienda!";
				} else {
					echo '<pre>';
					echo ' Despues de consulta';
					print_r($datos);
					echo '</pre>';
					$tipomensaje= "info";
					$mensaje = "Modificada correctamente la tienda.";
				}
			} else {
				// Entramos si es uno nuevo y se va añadir
				$resp = insertarDatos($datos,$BDTpv,$tabla,$campos_enviar);
				//~ echo '<pre>';
				//~ print_r($resp);
				//~ echo '</pre>';
				if (isset($resp['error'])){
					$tipomensaje= "danger";
					$mensaje = $resp['error'];
				} else {
					$tipomensaje= "info";
					$mensaje = "Nueva tienda creada.";
				}
			}
		};
		//~ echo '<pre>';
		//~ print_r($resp);
		//~ echo '</pre>';

		// Obtenemos id
		if (isset($_GET['id'])) {
			//  Ahora obtenemos los datos de esa tienda según id si es distinto 0
			$idBusqueda ='idTienda='. $_GET['id'];
			$TiendaUnica = verSelec($BDTpv,$idBusqueda,$tabla);
			//~ echo '<pre>';
			//~ print_r($TiendaUnica);
			//~ echo '</pre>';
			// Solo deberíamos obtener un resultado, si obtenemos mas es que hay algo mal.
			if ( $TiendaUnica['idTienda'] = $_GET['id']){
				$id=  $_GET['id']; // es un String
				$titulo = "Modificar Tienda:".$TiendaUnica['NombreComercial'];
				// Obtenemos el tipo de tieneda que es ..
				foreach ($tiposTiendas as $key => $tipoTienda){
					if ($TiendaUnica['tipoTienda'] === $tipoTienda['value']) {
						$tiposTiendas[$key]['checked'] = "checked"; // Marcamos check que tipo empresa es.
						$tiposTiendas[$key]['display'] = 'style="display:block;"'; //Ponemo para se muestre

					} else {
						$tiposTiendas[$key]['checked'] = "";
						$tiposTiendas[$key]['display'] = 'style="display:none;"'; //Ponemo para se muestre

					}
					
				}
				// Obtenemos el estado que tiene la tienda.
				foreach ($estados as $key => $estado){
					if ($TiendaUnica['estado'] == $estado['valor']){
						$estados[$key]['porDefecto'] = "selected"; // Indicamos por defecto
					} else {
						$estados[$key]['porDefecto'] = ''; // Indicamos por defecto

					}
				} 	
					
			} 
		} 
		
		?>
		
		
		
		
		
		<script type="text/javascript">
		// Defino variable global de nombre idscampos
		var idsCampos = [] ;
		idsCampos['web'] = [];
		idsCampos['fisica'] = [];
		idsCampos['principal'] = [];

		<?php
		foreach ($Campos as $key => $tipocampo){
			foreach ( $tipocampo as  $campo ){
				echo 'idsCampos['."'".$key."'"."].push('".$campo['id']."');";
			}
		}
		?>
		
		</script>
		
		<script src="<?php echo $HostNombre; ?>/modulos/mod_tienda/funciones.js"></script>
		
		
		
		<script type="text/javascript">
		// Javascript para controlar los cambios de valor ...						
		$(document).ready(function()
		{
			$("input:radio[name=tipoTienda]").change(function () {	
				// Si cambiamos valor
				// debemos cambiar el display de uno u otro campo. 
				var clase = 'mostrar_'+$('input:radio[name=tipoTienda]:checked').val();
				if ( clase === 'mostrar_web'){
					// Selecciono tipo web
					
					// Añadimos campos requeridos:
					camposFormRequired(idsCampos['web']);
					// Quitamos campos requeridos
					camposFormQuitarRequired(idsCampos['fisica']);
					// Mostramos y ocultamos campos
					$('.'+clase).css("display","block");
					$('.mostrar_fisica').css("display","none");
					$('.mostrar_principal').css("display","none");

					
				} else {
					// Selecciono tipo fisica
					
					// Añadimos campos requeridos:
					camposFormRequired(idsCampos['fisica']);
					// Quitamos campos requeridos
					camposFormQuitarRequired(idsCampos['web']);
					// Mostramos y ocultamos.
					$('.'+clase).css("display","block");
					$('.mostrar_web').css("display","none");
					$('.mostrar_principal').css("display","none");

					
				}
			})
		 });
		

		
		</script>
		<?php
		// Cobrobamos que si es nuevo o modificado
		if ($id === ''){
			// Es nuevo
			$tiposTiendas['0']['disabled'] = ''; // No se muestra por defecto
			$tiposTiendas['1']['disabled'] = ''; // No se muestra por defecto
			$tiposTiendas['2']['disabled'] = 'disabled'; // No permitimos esta opcion nunca.

		}
		?>

	</head>
	<body>
		<?php
        //~ include_once $URLCom.'/header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
		?>
     
		<div class="container">
			<?php if (isset($mensaje)){   ?> 
			<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje ;?></div>
			<?php }?>
			<h1 class="text-center"> <?php echo $titulo.':'. $TiendaUnica['NombreComercial'];?></h1>
			<a class="text-ritght" href="./ListaTiendas.php">Volver Atrás</a>

			<div class="col-md-12">
				<div class="col-md-3">
					<a href="<?php echo $img;?>"><img src="<?php echo $img;?>" style="width:100%;"></a>
				</div>
				<form action="" method="post" name="formUsuario">
				<div class="col-md-9">
					<div class="Datos">
						<div class="col-md-12">
							<div class="form-group">
								<label>Tipo de tienda</label>
								<?php 
								$inputHidden = '';
								foreach ($tiposTiendas as $tipoTienda){
									echo '<label class="radio-inline">';
									echo '<input type="radio" name="tipoTienda" value="'.$tipoTienda['value'].
										'" title="'.$tipoTienda['title'].'"'.$tipoTienda['checked'].'  '.$tipoTienda['disabled'].'>'.
										$tipoTienda['texto'].'</label>';
									// Si lo tenemos disabled, debemos crear input oculto para enviarlo, ya que al esta disabled no envia los campos y en la carga, no el lado del cliente.
									if (($tipoTienda['disabled'] === 'disabled') && ($tipoTienda['checked'] === 'checked')){
										$inputHidden= '<input type="hidden" name ="tipoTienda" value="'.$tipoTienda['value'].'">';
									}
								}
								// Si es disabled los inputs radios, entonces el que esta marcado lo mandamos oculto...
								echo $inputHidden;
								?>
								
							</div>
						</div>
						
						<div class="col-md-6">
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
						<div class="col-md-6">
							<div class="mostrar_fisica" <?php echo $tiposTiendas[0]['display'] ?>>
							<!-- Solo debería mostrar uno según tipo tienda-->
								<h3>Datos Tienda Fisica</h3>
								<div class="form-group">
									<label>Nombre Comercial:</label>
									<input type="text" id="NombreComercial" name="NombreComercial" placeholder="nombre comercial" value="<?php echo $TiendaUnica['NombreComercial']?>" >
								</div>
								<div class="form-group">
									<label>Dirección:</label>
									<input type="text" id="direccion" name="direccion" placeholder="direccion"  value="<?php echo $TiendaUnica['direccion'];?>"  >
								</div>
								
								<div class="form-group">
									<label>Año Contable:</label>
									<input type="text" id="ano" name="ano" placeholder="2014"  value="<?php echo $TiendaUnica['ano'];?>"  >
								</div>
							</div>
							<div class="mostrar_web" <?php echo $tiposTiendas[1]['display'] ?>>
								<!-- Solo debería mostrar uno según tipo tienda-->
								<h3>Datos Tienda Web</h3>
								<div class="form-group">
									<label>Dominio:(<span title="Sin http, ni www">*</span>)</label>
									<input type="text"  id="dominio" name="dominio" placeholder="dominio.com" value="<?php echo $TiendaUnica['dominio'];?>" >
								</div>
								<div class="form-group">
									<label>Key Api:(<span title="Key Api Plugin Joomla">*</span>)</label>
									<input type="text" id="key_api" name="key_api" value="<?php echo $TiendaUnica['key_api'];?>" >
								</div>
							</div>
							<div class="mostrar_principal" <?php echo $tiposTiendas[2]['display'] ?>>
							<!-- Solo debería mostrar uno según tipo tienda-->
								<h3>Datos Tienda Fisica</h3>
								<div class="form-group">
									<label>Nombre Comercial:</label>
									<input type="text" id="NombreComercial" name="NombreComercial" placeholder="nombre comercial" value="<?php echo $TiendaUnica['NombreComercial']?>" >
								</div>
								<div class="form-group">
									<label>Dirección:</label>
									<input type="text" id="direccion" name="direccion" placeholder="direccion"  value="<?php echo $TiendaUnica['direccion'];?>"  >
								</div>
								
								<div class="form-group">
									<label>Año Contable:</label>
									<input type="text" id="ano" name="ano" placeholder="2014"  value="<?php echo $TiendaUnica['ano'];?>"  >
								</div>
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
	<!-- Ahora ponemos lo campos requeridos -->

	<script type="text/javascript">
	<?php
		if ($tiposTiendas['1']['checked'] === "checked") {
			 // Si web tiene checked
			 echo "camposFormRequired(idsCampos['web']);";
		} else {
			// Si fisica tiene checked
			 echo "camposFormRequired(idsCampos['fisica']);";

		}
	?>
	</script>
	</body>
</html>
