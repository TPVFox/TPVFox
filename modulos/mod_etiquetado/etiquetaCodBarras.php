<!DOCTYPE html>
<html>
    <head>
		<?php
        include './../../head.php';
        include ("./../../controllers/Controladores.php");
        include ("./../mod_conexion/conexionBaseDatos.php");
        include '../../clases/articulos.php';
		
		include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
		$ClasesParametros = new ClaseParametros('parametros.xml');
        
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$Carticulo=new Articulos($BDTpv);
        $Tienda = $_SESSION['tiendaTpv'];
		$Usuario = $_SESSION['usuarioTpv'];
        $titulo="Crear Etiquetas de Código Barras";
        $fechaEnv=date('Y-m-d H:i:s');
        $fechaCad=date('Y-m-d');
        $numAlb="";
        $nomPro="";
        $idReal=0;
        $unidades="";
        $estado="Activo";
        $idTemporal=0;
		$idProducto=0;
        if (isset($_GET['idProducto'])){
			$idProducto=$_GET['idProducto'];
			$datosProducto=$Carticulo->datosPrincipalesArticulo($idProducto);
			$nomPro=$datosProducto['articulo_name'];
		}
		$parametros = $ClasesParametros->getRoot();	
		$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
        ?>
        <script type="text/javascript">
			var cabecera = [];
				cabecera['idUsuario'] = <?php echo $Usuario['id'];?>;
				cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
				cabecera['estado'] ='<?php echo $estado ;?>';
				cabecera['idTemporal'] = <?php echo $idTemporal ;?>;
				cabecera['idReal'] = <?php echo $idReal ;?>;
				cabecera['fechaEnv'] = '<?php echo $fechaEnv ;?>';
				cabecera['fechaCad'] = '<?php echo $fechaCad ;?>';
				cabecera['idProducto'] = <?php echo $idProducto ;?>;
			var productos = [];
			<?php 
	if (isset($etiqueta)| isset($etiquetaTemporal)){ 
	$i= 0;
		if (isset($productos)){
			foreach($productos as $product){
?>	
				datos=<?php echo json_encode($product); ?>;
				productos.push(datos);
	
<?php 
		// cambiamos estado y cantidad de producto creado si fuera necesario.
			if ($product['estado'] !== 'Activo'){
			?>	productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
			<?php
			}
			$i++;
			}
	
		}
	}
		?>
		</script>
     </head>
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_etiquetado/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
	<?php     
        include './../../header.php';
        ?>
        <script type="text/javascript">
			<?php echo $VarJS;?>
			 function anular(e) {
				  tecla = (document.all) ? e.keyCode : e.which;
				  return (tecla != 13);
			  }
		</script>
        <div class="container">
		<h2 class="text-center"> <?php echo $titulo;?></h2>
		<form action="" method="post" name="formEtiqueta" onkeypress="return anular(event)">
			<div class="col-md-12">
				<div class="col-md-12 ">
					<div class="col-md-8">
						<a href="./../mod_producto/ListaProductos.php">Volver Atrás</a>
						<input type="submit" class="btn btn-primary" name="Guardar" value="Guardar">
					</div>
					<div class="col-md-4">
						<input type="submit" class="pull-right btn btn-danger" name="Cancelar" value="Cancelar">
					</div>
				</div>
				
				<div class="col-md-12">
					<div class="col-md-2">
						<label>Fecha Envasado</label>
						<input type="date" name="fechaEnv" id="fechaEnv" size="17"  value="<?php echo $fechaEnv;?>" readonly>
					</div>
					<div class="col-md-2">
						<label>Fecha Caducidad</label>
						<input type="date" name="fechaCad" id="fechaCad" size="10" data-obj= "cajaFechaCad"  value="<?php echo $fechaCad;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
					</div>
					<div class="col-md-2">
						<label>Tipo</label>
					<select name="tipo" id="tipo" onchange="modificarTipo(value);">
						<option value='0'>Selecciona</option>
						<option value='1'>Por unidad</option>
						<option value='2'>Por peso</option>
					</select>
					</div>
					<div class="col-md-2">
						<label>Num Albarán</label>
						<input type="text" id="numAlb" name="numAlb" value="<?php echo $numAlb;?>" size="10" data-obj= "cajaNumAlb" onkeydown="controlEventos(event)">
					</div>
				</div>
				<div class="col-md-12">
					<div class="col-md-5">
						<label>Producto:</label>
						<input type="text" id="producto" name="producto" value="<?php echo $nomPro;?>" size="50" readonly>
					</div>
					<div class="col-md-2">
						<label>Unidades</label>
						<input type="text" id="unidades" name="unidades" value="<?php echo $unidades;?>" size="10" data-obj= "cajaUnidades" onkeydown="controlEventos(event)" >
					</div>
				</div>
				<div class="col-md-12">
					<table id="tabla" class="table table-striped">
						<thead>
							<tr>
								<th>L</th>
								<th>Nombre del producto</th>
								<th id="tipoTabla">Tipo</th>
								<th>Precio</th>
								<th>Fecha</th>
								<th>Num Alb</th>
								<th>Num Cod Barras</th>
							</tr>
						</thead>
						<tbody>
						
						</tbody>
					</table>
				</div>
			</div>
		</form>
		</div>
	</body>
</html>
      
