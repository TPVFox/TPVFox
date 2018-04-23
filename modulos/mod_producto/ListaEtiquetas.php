<!DOCTYPE html>
<html>
    <head>
	<?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include ("./clases/ClaseProductos.php");
	include ('../../clases/articulos.php');
	$CArticulos = new Articulos($BDTpv);
	$Tienda = $_SESSION['tiendaTpv'];
	$idTienda= $Tienda['idTienda'];	
	
	
	?>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
		</head>

<body>
		
        <?php
        include './../../header.php';
        //~ echo $_POST['tamanhos'];
        //~ echo '<pre>';
        //~ print_r($_SESSION['productos_seleccionados']);
        //~ echo '</pre>';
        ?>
        <script type="text/javascript">
        <?php
        if(isset($_POST['Imprimir'])){
			echo 'imprimirEtiquetas('."'".json_encode($_SESSION['productos_seleccionados'])."'".',"'.$dedonde.'","'
									.$idTienda.'","'.$_POST['tamanhos'].'");';
		}
        ?>
       </script>
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
				<h2> Etiquetas: Imprimir etiquetas del producto </h2>
			</div>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
			<div class="col-sm-2">
				<a class="text-ritght" href="./ListaProductos.php">Volver Atrás</a>
				<br><br>
				Selecciona el tamaño: 
				<select  name="tamanhos">
					<option value="1">A9</option>
					<option value="2">A5</option>
					<option value="3">A7</option>
				</select>
				<br><br>
				<input type="submit" value="Imprimir" name="Imprimir">
				
				
			</div>
			<div class="col-md-10">
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th>ID</th>
						<th>PRODUCTO</th>
						<th>P.V.P</th>
						<th>ELIMINAR</th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach($_SESSION['productos_seleccionados'] as $producto){
						$articulo=$CArticulos->buscarNombreArticulo($producto);
						$precio=$CArticulos->articulosPrecio($producto);
						?>
						<tr>
						<td><?php echo $producto;?></td>
						<td><?php echo $articulo['articulo_name'];?></td>
						<td><?php echo number_format($precio['pvpCiva'],2);?>€</td>
						<td>
							<a onclick="selecionarItemProducto(<?php echo $producto;?>, 'ListaEtiquetas')">
							<span class="glyphicon glyphicon-trash"></span>
							</a>
						</td>
					</tr>
					<?php
					}
				?>
				</tbody>
			</table>
			</div>
			</form>
		</div>
		 </div>
		
</body>
</html>
