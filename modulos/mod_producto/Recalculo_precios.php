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
		$volver = 
		$CAlbaran=new AlbaranesCompras($BDTpv);
		$CArticulo=new Articulos($BDTpv);
		$ruta_volver= $HostNombre.'/modulos/mod_compras/albaranesListado.php';
		$titulo="Recalculo precios PVP ";
        if ($_GET['id']){
			$id=$_GET['id'];
			$dedonde="albaran";
			
			$subtitulo='de '.$dedonde.' :'.$id;
			$titulo=$titulo.' '.$subtitulo;
			
			$datosAlbaran=$CAlbaran->datosAlbaran($id);
			$fecha=date_create($datosAlbaran['Fecha']);
			$fecha=date_format($fecha, 'Y-m-d');
			
			$productosHistoricos=$CArticulo->historicoCompras($id, "albaran", "compras");
			
		}
		if ($_POST['Guardar']){
			echo "entre en guardar";
			$id=$_GET['id'];
			$i=1;
			$estado="";
			$fechaCreacion=date('Y-m-d');
			foreach ($productosHistoricos as $producto){
				if ($producto['estado']=="Pendiente"){
					$idArticulo=$producto['idArticulo'];
					
					$pvpRecomendadoCiva=$_POST['pvpRecomendado'.$i];
					$pvpRecomendadoCiva=(float)$pvpRecomendadoCiva;
					
					$datosArticulo=$CArticulo->datosPrincipalesArticulo($idArticulo);
					$datosPrecios=$CArticulo->articulosPrecio($idArticulo);
					$articuloPrecioAnt=$datosPrecios['pvpCiva'];
					
					if ($pvpRecomendadoCiva<>$articuloPrecioAnt){
					
						$ivaPrecio=$datosArticulo['iva']/100;
						$ivaProducto=$producto['Nuevo']*$ivaPrecio;
						$precioProducto=$producto['Nuevo']+$ivaProducto;
						$beneficio=$datosArticulo['beneficio']/100;
						$beneficioArticulo=$precioProducto*$beneficio;
						$pvpRecomendado=$beneficioArticulo+$precioProducto;
							
						
						if ($pvpRecomendado<>$pvpRecomendadoCiva){
							$estado="A mano";
						}else{
							$estado="Recomendado";
						}
						
						$nuevoIva=1+$ivaPrecio;
						$nuevo=$pvpRecomendadoCiva/$nuevoIva;
						$nuevoSiva=number_format($nuevo,6);
						$nuevo=number_format($pvpRecomendadoCiva,2);
						$datosHistorico=array(
						'idArticulo'=>$idArticulo,
						'antes'=>$datosPrecios['pvpCiva'],
						'nuevo'=>$pvpRecomendadoCiva,
						'fechaCreacion'=>$fechaCreacion,
						'numDoc'=>$id,
						'dedonde'=>"Recalculo",
						'tipo'=>"Productos",
						'estado'=>$estado
						);
						$nuevoHistorico=$CArticulo->addHistorico($datosHistorico);	
						$modPrecios=$CArticulo->modArticulosPrecio($pvpRecomendadoCiva, $nuevoSiva, $idArticulo);
						$i++;
						$estado="";
					}
				}
			}
			
			 $modificarHistorico=$CArticulo->modificarEstadosHistorico($id, $dedonde );
			
			// header('Location: ../mod_compras/albaranesListado.php');
			
		}
		echo '<pre>';
		print_r($datosAlbaran);
		echo '</pre>';
		?>
		
		
	</head>
	<body>
	<?php
	include '../../header.php';
	?>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
	<script type="text/javascript">
// Objetos cajas de tpv
<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
      <?php 
      if ($_POST['Guardar']){
		  ?>
		 mensajeImprimir(<?php echo $id;?>, <?php echo "'".$dedonde."'"; ?>);
		
		 
		  <?php
	  }
      ?>
</script>
		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
		<div class="container">
			<h2 class="text-center"><?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
			<div class="col-md-12">
				<!-- De momento devolvemos a albaranes ya que es donde se hace recalculo
					 pero esto tendrá cambiar, ya que el recalculo se podrá acceder desde varios sitios -->
				<a class="text-right" href="<?php echo $ruta_volver;?>">Volver Atrás</a>
				<input type="submit" value="Guardar" name="Guardar" id="Guardar" onclick="">
				<input type="submit" value="Imprimir" name="Imprimir" id="Imprimir" onclick="">
			</div>
			<div class="col-md-12">
				
				<div class="col-md-2">
					<strong>Fecha albarán:</strong><br>
					<input type="date" name="fecha" id="fecha" size="10"   value="<?php echo $fecha;?>" readonly >
				</div>
				<div class="col-md-8">
					<strong>Proveedor:</strong><br>
					<!-- Deberíamos mostrar tanto ID-NombreComercial-RazonSocial  -->
					<input type="text" name="estado" id="estado" size="10"   value="<?php echo $datosAlbaran['idProveedor'];?>" readonly >
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
						<th>ELIMINAR</th>
					</tr>
				</thead>
				<tbody>
				<?php 
				$i=1;
				foreach ($productosHistoricos as $producto){
					if ($producto['estado']<>"Revisado"){
					$datosArticulo=$CArticulo->datosPrincipalesArticulo($producto['idArticulo']);
					$datosPrecios=$CArticulo->articulosPrecio($producto['idArticulo']);
					$ivaPrecio=$datosArticulo['iva']/100;
					$ivaProducto=$producto['Nuevo']*$ivaPrecio;
					$precioProducto=$producto['Nuevo']+$ivaProducto;
					$beneficio=$datosArticulo['beneficio']/100;
					$beneficioArticulo=$precioProducto*$beneficio;
					$pvpRecomendado=$beneficioArticulo+$precioProducto;
					//~ $beneficio=$datosArticulo['beneficio']/100;
					//~ $beneficioArticulo=$producto['Nuevo']*$beneficio;
					//~ $pvpRecomendado=$producto['Nuevo']+$beneficioArticulo+$ivaProducto;
					if ($producto['estado']=="Pendiente"){
						$class="";
					}else{
						$class="class='tachado'";
					}
					echo '<tr id="Row'.$i.'" '.$class.'>';
					echo '<td>'.$producto['idArticulo'].'</td>';
					echo '<td>'.$datosArticulo['articulo_name'].'</td>';
					echo '<td>'.$producto['Nuevo'].'</td>';
					echo '<td>'.$producto['Antes'].'</td>';
					echo '<td>'.$datosArticulo['beneficio'].'</td>';
					echo '<td>'.$datosArticulo['iva'].'</td>';
					echo '<td>'.number_format($datosPrecios['pvpCiva'],4).'</td>';
					echo '<td><input type="text" id="pvpRecomendado'.$i.'" name="pvpRecomendado'.$i.'" value="'.number_format($pvpRecomendado,2).'"></td>';
					if ($producto['estado']=="Pendiente"){
						echo '<td class="eliminar"><a onclick="eliminarCoste('.$producto['idArticulo'].', '."'".$dedonde."'".', '.$id.', '."'".'compras'."'".', '.$i.')"><span class="glyphicon glyphicon-trash"></span></a></td>';
					}else{
						echo '<td class="eliminar"><a onclick="retornarCoste('.$producto['idArticulo'].', '."'".$dedonde."'".', '.$id.', '."'".'compras'."'".', '.$i.')"><span class="glyphicon glyphicon-export"></span></a></td>';
					}
					
					
					echo '</tr>';
					$i++;
					}
				}
				?>
				
				</tbody>
						</table>
					</div>
				</form>
		</div>
		
	</body>	
</html>
