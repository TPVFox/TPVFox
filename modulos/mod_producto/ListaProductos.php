<!DOCTYPE html>
<html>
    <head>
       <?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include ("./clases/ClaseProductos.php");
	$CTArticulos = new ClaseProductos($BDTpv);

	include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
	$Controler = new ControladorComun; // Controlado comun..
	// Añado la conexion
	$Controler->loadDbtpv($BDTpv);
	// Inicializo varibles por defecto.
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];
	
	if(!isset($_SESSION['productos'])){
		$_SESSION['productos']=array();
	}
	
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$parametros = $ClasesParametros->getRoot();
	// Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	// Obtenemos la configuracion del usuario o la por defecto
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_productos',$Usuario['id']);
	// Compruebo que solo halla un campo por el que buscar por defecto.
	if (!isset($configuracion['tipo_configuracion'])){
		// Hubo un error en la carga de configuracion.
		$error = array(	'tipo'=>'danger',
						'dato'=>'Fichero Parametros.xml',
						'mensaje' =>'Error al cargar configuracion, puede ser en el fichero como en tablas modulo_configuracion.'
				);
		$CTArticulos->SetComprobaciones($error);

	}
	$htmlConfiguracion = HtmlListadoCheckMostrar($configuracion['mostrar_lista']);
	
	if (isset($htmlConfiguracion['error'])){
		// quiere decir que hubo error en la configuracion.
		$error = array(	'tipo'=>'danger',
						'dato'=>'Fichero Parametros.xml',
						'mensaje' =>$htmlConfiguracion['error']
				);
		$CTArticulos->SetComprobaciones($error);
	}
	
	//INICIALIZAMOS variables para el plugin de paginado:
	$palabraBuscar=array();
	$stringPalabras='';
	$PgActual = 1; // por defecto.
	$LimitePagina = 40; // por defecto.
	$filtro = ''; // por defecto
	$LinkBase = './ListaProductos.php?';
	$OtrosParametros = '';
	
	if (isset($_GET['pagina'])) {
		$PgActual = $_GET['pagina'];
	}
	if (isset($_GET['buscar'])) {  
		//recibo un string con 1 o mas palabras
		$stringPalabras = $_GET['buscar'];
		$palabraBuscar = explode(' ',$_GET['buscar']); 
	} 
	
	$paginasMulti = $PgActual-1;
	
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
		
	} else {
		$desde = 0;
	}
	
	// Realizamos consulta 
	//si existe palabraBuscar introducida en buscar, la usamos en la funcion obtenerProductos
	if ($stringPalabras !== '' ){
		$WhereLimite= $Controler->paginacionFiltroBuscar($stringPalabras,$LimitePagina,$desde,$htmlConfiguracion['campo_defecto']);
		$filtro=$WhereLimite['filtro'];
		$OtrosParametros=$stringPalabras;
	}
		
	
	if ($htmlConfiguracion['campo_defecto']=== 'articulo_name' && $filtro===''){
		// Si el campo por defecto es nombre... no hace falta hacer consulta para obtener total.
		$CantidadRegistros = $CTArticulos->GetNumRows(); 
	} else {
		$CantidadRegistros = count($CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'],$filtro));
	}
	$htmlPG= ''; 
	if ($CantidadRegistros > 0){
		$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
		if ($stringPalabras !== '' ){
			$filtro = $WhereLimite['filtro'].$WhereLimite['rango'];
		} else {
			$filtro= " LIMIT ".$LimitePagina." OFFSET ".$desde;
		}
			
		$productos = $CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'],$filtro);
	}
	//~ echo '<pre>';
	//~ print_r($nuevo);
	//~ echo '</pre>';
	
	
	// Añadimos a JS la configuracion
		echo '<script type="application/javascript"> '
		. 'var configuracion = '. json_encode($configuracion);
		echo '</script>';
	?>
	
	<script>
	// Declaramos variables globales
	var checkID = [];
	
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    </head>

<body>
        <?php
        include './../../header.php';
        ?>
       
	<div class="container">
		<?php // Control de errores..
			$comprobaciones = $CTArticulos->GetComprobaciones();
			if ( count($comprobaciones)>0){
				foreach ( $comprobaciones as $comprobacion){
					echo '<div class="alert alert-'.$comprobacion['tipo'].'">'.$comprobacion['mensaje'].'</div>';
					if ($comprobacion['tipo']==='danger'){
						// No permito continuar.
						exit();
					}
				}
			}
			
			?>
		
		<div class="row">
			<div class="col-md-12 text-center">
				<h2> Productos: Editar y Añadir Productos </h2>
			</div>
	        <div class="col-sm-2">
				
				<h4> Productos</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] === '1'){
				?>
					<li><a href="#section2" onclick="metodoClick('AgregarProducto');";>Añadir</a></li>
					<?php 
				}
					?>
					<li><a href="#section2" onclick="metodoClick('VerProducto','producto');";>Modificar</a></li>
				<?php		//metodoClick js case pulsado 
								//agregarUsuario nos lleva a formulario usuario
								//verUsuario si esta checkado nos lleva vista usuario de ese id
											//si NO nos indica que tenemos que elegir uno de la lista ?>
				<h4 class='imprimir'>Etiquetas</h4>
				<h5 class='imprimir'>Imprimir etiquetas</h5>
				<li class='imprimir'><a href='ListaEtiquetas.php'; onclick="metodoClick('ImprimirEtiquetas','listaEtiqueta');";>Imprimir</a></li>
				</ul>	
				<h4>Configuracion</h4>
				<h5>Marca que campos quieres mostrar y por lo quieres buscar.</h5>
				<?php 
					echo $htmlConfiguracion['htmlCheck'];
				?>
			
			</div>
			
			<div class="col-md-10">
					<p>
					 -Productos encontrados BD local filtrados:
						<?php echo $CantidadRegistros; ?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
				<form action="./ListaProductos.php" method="GET" name="formBuscar">
					<div class="form-group ClaseBuscar">
						<label>Buscar por:</label>
						<select onchange="GuardarBusqueda(event);" name="SelectBusqueda" id="sel1"> <?php echo $htmlConfiguracion['htmlOption'];?> </select>
						<input type="text" name="buscar" value="<?php echo $stringPalabras; ?>">
						<input type="submit" value="buscar">
					</div>
				</form>
                 <!-- TABLA DE PRODUCTOS -->
			<div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th></th>
						<th>ID</th>
						<th>PRODUCTO</th>
						<?php if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'],'codBarras') === 'Si'){
							echo '<th>CODIGO BARRAS</th>';
						}
						if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'],'crefTienda') === 'Si'){
							echo '<th>REFERENCIA</th>';
						}
						?>
				
						<th>COSTE <br/> ULTIMO</th>
						<th><span title="Beneficio que tiene ficha">%</span> </th>
						<th>Precio<br/>Sin Iva</th>
						<th>IVA</th>
						<th>P.V.P</th>
						<th>Estado</th>

					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
			if (isset($productos)){
				foreach ($productos as $producto){ 
					$checkUser = $checkUser + 1; 
					$checked="";
					if(in_array($producto['idArticulo'], $_SESSION['productos'])){
						$checked="checked";
					}
				?>

				<tr>
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" onclick="imprimirEtiquetas(<?php echo $producto['idArticulo']; ?>)" value="<?php echo $producto['idArticulo'];?>" <?php echo $checked;?>>
					</td>
					<td><?php echo $producto['idArticulo']; ?></td>
					<td><?php echo $producto['articulo_name']; ?></td>
					
					<?php
					if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'],'crefTienda') === 'Si'){
						$CTArticulos->ObtenerCodbarrasProducto($producto['idArticulo']);
						$codBarrasProd = $CTArticulos->GetCodbarras();
						echo '<td>'; 
						if ($codBarrasProd){
							foreach ($codBarrasProd as $cod){
								echo '<small>'.$cod.'</small><br>';
							}
						}
						echo '</td>';
					}
					?>
					<?php 
					if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'],'codBarras') === 'Si'){
						$CTArticulos->ObtenerReferenciasTiendas($producto['idArticulo']);
						$refTiendas = $CTArticulos->GetReferenciasTiendas();
						echo '<td>'; 
						if ($refTiendas){
							foreach ($refTiendas as $ref){
								echo $ref['crefTienda'];
							}
						}
						echo '</td>';
					}
					
					?>
					
					<td><?php echo number_format($producto['ultimoCoste'],2); ?></td>
					<td><?php echo $producto['beneficio']; ?></td>
					<td style="text-align:right;"><?php echo number_format($producto['pvpSiva'],2); ?><small>€</small></td>
					<td><?php echo $producto['iva']; ?></td>
					<td style="text-align:right;"><?php echo number_format($producto['pvpCiva'],2); ?><small>€</small></td>
					<td><?php echo $producto['estado']; ?></td>

				</tr>

				<?php 
				}
			}
				?>
				
			</table>
			</div>
		</div>
	</div>
    </div>
		
</body>
</html>
