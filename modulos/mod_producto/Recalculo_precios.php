<!DOCTYPE html>
<html>
	<head>
		<?php 
		include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
        include ("./../../controllers/Controladores.php");
		include '../mod_compras/clases/albaranesCompras.php';
		include '../../clases/articulos.php';
       $CAlbaran=new AlbaranesCompras($BDTpv);
         $CArticulo=new Articulos($BDTpv);
        $titulo="Recalculo precios PVP ";
        if ($_GET['id']){
			$id=$_GET['id'];
			$dedonde="albaran";
			
			$subtitulo='de '.$dedonde.' :'.$id;
			$titulo=$titulo.' '.$subtitulo;
			
			$datosAlbaran=$CAlbaran->datosAlbaran($id);
			$fecha=date_create($datosAlbaran['Fecha']);
			$fecha=date_format($fecha, 'Y-m-d');
			//~ echo '<pre>';
			//~ print_r($datosAlbaran);
			//~ echo '</pre>';
			$productosHistoricos=$CArticulo->historicoCompras($id, "albaran", "compras");
				//~ echo '<pre>';
			//~ print_r($productosHistoricos);
			//~ echo '</pre>';
		}
		if ($_POST['Guardar']){
			echo "entre en guardar";
			$id=$_GET['id'];
			
		}
		
		?>
		
		
	</head>
	<body>
		<?php
	include '../../header.php';
	?>
		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
		<div class="container">
			<h2 class="text-center"><?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" >
			<input type="submit" value="Guardar" name="Guardar" id="Guardar">
			<div class="col-md-12">
				
				<div class="col-md-2">
					<strong>Fecha albarán:</strong><br>
					<input type="date" name="fecha" id="fecha" size="10"   value="<?php echo $fecha;?>" readonly >
				</div>
				<div class="col-md-2">
					<strong>Estado albarán:</strong><br>
					<input type="text" name="estado" id="estado" size="10"   value="<?php echo $datosAlbaran['estado'];?>" readonly >
				</div>
				</div>
				<div class="col-md-12">
					<table class="table table-bordered table-hover">
							<thead>
					<tr>
						<th>ID</th>
						<th>NOMBRE</th>
						<th>COSTE ULTIMO</th>
						<th>COSTE ANTERIOR</th>
						<th>BENEFICIO</th>
						<th>IVA</th>
						<th>PVP ACTUAL</th>
						<th>PVP RECOMENDADO</th>
					</tr>
				</thead>
				<tbody>
				<?php 
				$i=1;
				foreach ($productosHistoricos as $producto){
					$datosArticulo=$CArticulo->datosPrincipalesArticulo($producto['idArticulo']);
					$datosPrecios=$CArticulo->articulosPrecio($producto['idArticulo']);
					$ivaPrecio=$datosArticulo['iva']/100;
					$ivaProducto=$producto['Nuevo']*$ivaPrecio;
					$pvpRecomendado=$producto['Nuevo']+$datosArticulo['beneficio']+$ivaProducto;
					echo '<tr>';
					echo '<td>'.$producto['idArticulo'].'</td>';
					echo '<td>'.$datosArticulo['articulo_name'].'</td>';
					echo '<td>'.$producto['Nuevo'].'</td>';
					echo '<td>'.$producto['Antes'].'</td>';
					echo '<td>'.$datosArticulo['beneficio'].'</td>';
					echo '<td>'.$datosArticulo['iva'].'</td>';
					echo '<td>'.number_format($datosPrecios['pvpCiva'],4).'</td>';
					echo '<td><input type="text" id="pvpRecomendado'.$i.'" name="pvpRecomendado'.$i.'" value="'.number_format($pvpRecomendado,6).'"></td>';
					echo '</tr>';
					$i++;
					}
				?>
				
				</tbody>
						</table>
					</div>
				</form>
		</div>
	</body>	
</html>
