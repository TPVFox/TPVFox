<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
		// Variables por defecto:
		$titulo = "Crear Tienda";
		$img = './../../css/img/tienda.svg'; // Url imagen por defecto.
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
					'valor' =>'abierto',
					'porDefecto' =>  ''
					)
		);
		// Definimos posibles tipos de tiendas.
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
					)
		);
			
		// Obtenemos id
		if (isset($_GET['id'])) {
			
			//  Ahora obtenemos los datos de esa tienda según id si es distinto 0
			
			$tabla= 'tiendas';
			$idBusqueda ='idTienda='. $_GET['id'];
			$TiendaUnica = verSelec($BDTpv,$idBusqueda,$tabla);
			// Solo deberíamos obtener un resultado, si obtenemos mas es que hay algo mal.
			if ( $TiendaUnica['idTienda'] = $_GET['id']){
				$id=  $_GET['id']; // es un String
				$titulo = "Modificar Tienda:".$TiendaUnica['NombreComercial'];
				// Obtenemos el tipo de tieneda que es ..
				foreach ($tiposTiendas as $key => $tipoTienda){
					
					if ($TiendaUnica['tipo_empresa'] === $tipoTienda['value']) {
						$tiposTiendas[$key]['checked'] = "checked";
					} else {
						$tiposTiendas[$key]['checked'] = "";
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
		// Cambiamos valores por defecto si es nuevo.
		if ($id === ''){
			$tiposTiendas['0']['disabled'] = ''; // No se muestra por defecto
			$tiposTiendas['1']['disabled'] = ''; // No se muestra por defecto
		}
		?>
		<script type="text/javascript">
		$(document).ready(function()
		{
			$("input:radio[name=tipoTienda]").change(function () {	
				// Si cambiamos valor
				// debemos cambiar el display de uno u otro campo. 
				var clase = 'mostrar_'+$('input:radio[name=tipoTienda]:checked').val();
				if ( clase === 'mostrar_web'){
					$('.'+clase).css("display","block");
					$('.mostrar_fisica').css("display","none");

				} else {
				  $('.'+clase).css("display","block");
					$('.mostrar_web').css("display","none");
				}
			})
		 });
		</script>
		
	
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
			};
			
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
								foreach ($tiposTiendas as $tipoTienda){
									echo '<label class="radio-inline">';
									echo '<input type="radio" name="tipoTienda" value="'.$tipoTienda['value'].
										'" title="'.$tipoTienda['title'].'"'.$tipoTienda['checked'].'  '.$tipoTienda['disabled'].'>'.
										$tipoTienda['texto'].'</label>';
								}
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
							<div class="mostrar_web" <?php echo $tiposTiendas[1]['display'] ?>>
								<!-- Solo debería mostrar uno según tipo tienda-->
								<h3>Datos Tienda Web</h3>
								<div class="form-group">
									<label>Dominio:(<span title="Sin http, ni www">*</span>)</label>
									<input type="text"  name="dominio" placeholder="dominio.com" value="" >
								</div>
								<div class="form-group">
									<label>Base Datos:(<span title="Nombre bases datos">*</span>)</label>
									<input type="text" name="nom_bases_datos" placeholder="name_basedatos" value="" >
								</div>
								<div class="form-group">
									<label>Usuario:(<span title="Nombre Usuario">*</span>)</label>
									<input type="text" name="nom_usuario_base_datos" placeholder="user_name" value="" >
								</div>
								<div class="form-group">
									<label>Prefijo:(<span title="Prefijo tablas Joomla">*</span>)</label>
									<input type="text" name="prefijoBD" placeholder="_mxjrs" value="" >
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
	</body>
</html>
