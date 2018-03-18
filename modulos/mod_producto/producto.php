<!DOCTYPE html>
<html>
    <head>
        <?php
        include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
        include ("./clases/ClaseProductos.php");
		// Cargamos los fichero parametros.
		include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
		$ClasesParametros = new ClaseParametros('parametros.xml');
		$parametros = $ClasesParametros->getRoot();
		// Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		echo '<pre>';
		print_r($conf_defecto);
		echo '</pre>';
		
		// Creamos objeto de productos		
		$CTArticulos = new ClaseProductos($BDTpv);
		
		$titulo = 'Productos:';
		$id = 0 ; // Por  defecto el id a buscar es 0
		$tabla= 'articulos'; // Tablas que voy utilizar.
		$estadoInput = 'disabled'; //desactivado input de entrada 
		
		$ivas = $CTArticulos->getTodosIvas(); // Obtenemos todos los ivas.
		
		if (isset($_GET['id'])) {
			// Modificar Ficha Producto
			$id=$_GET['id']; // Obtenemos id producto para modificar.
			$titulo .= "Modificar";
		} else {
			// Quiere decir que no hay id, por lo que es nuevo
			$titulo .= "Crear";

		}
		// Obtenemos los datos del id, si es 0, quiere decir que es nuevo.
		$Producto = $CTArticulos->getProducto($_GET['id']);
		
		?>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
	</head>
	<body>
		<?php     
        include './../../header.php';
		// Comprobamos si el estado es Nuevo
		if ($Producto['estado'] === 'Nuevo'){
			$disabled = '';
		} else {
			$disabled = '';
		}
		// Ahora montamos html 
		$htmlIvas = htmlOptionIvas($ivas,$Producto['iva']);
		$htmlCodBarras = htmlTablaCodBarras($Producto['codBarras']);
		$htmlProveedoresCostes = htmlTablaProveedoresCostes($Producto['proveedores_costes']);
		$htmlFamilias =  htmlTablaFamilias($Producto['familias']);
		
		//~ echo '<pre>';
		//~ print_r($Producto);
		//~ echo '</pre>';
			
			
		
		if ($_POST){
			echo '<pre>';
			print_r($_POST);
			echo '</pre>';
			
			
			
			
			// Comprobamos los datos antes de grabar.
			// header('Location: producto.php?id='.$i.'&tipo='.$tipomensaje.'&mensaje='.$mensaje);
		}

		
		?>
     
		<div class="container">
				
			<?php 
			if (isset($Producto['comprobaciones'])){   ?> 
				<?php 
				foreach ($Producto['comprobaciones'] as $comprobaciones){?>
					<div class="alert alert-<?php echo $comprobaciones['tipo']; ?>"><?php echo $comprobaciones['mensaje'] ;?></div>
					<?php 
				}
				if (isset($Producto['error'])){
				// No permito continuar, ya que hubo error grabe.
				return;
				}
				?>
			<?php
			}
			?>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
			<div class="col-md-12">
				<div class="col-md-12 btn-toolbar">
					<a class="text-ritght" href="./ListaProductos.php">Volver Atrás</a>
					<input type="submit" value="Guardar">
				</div>
				<div class="col-md-6 Datos">
					<?php // si es nuevo mostramos Nuevo ?>
					<h4>Datos del producto con ID:<?php echo $id?></h4>
					<input type="text" id="id" name="id" size="10" style="display:none;" value="<?php echo $id?>"   >
					<div class="col-md-12">
						<div class="form-group col-md-3 ">	
							<label class="control-label " > Referencia:</label>
							<input type="text" id="referencia" name="referencia" size="10" placeholder="referencia producto" value="<?php echo $Producto['cref_tienda_principal'];?>"   >
						</div>
						<div class="form-group col-md-9 ">	
							<label class="control-label " > Nombre producto:</label>
							<input type="text" id="nombre" name="nombre" placeholder="nombre producto" value="<?php echo $Producto['articulo_name'];?>"    size="50" >
						</div>
					</div>
					<div class="col-md-12">
						<h4> Costes del Producto</h4>
						<div class="form-group col-md-4">
							<?php // Si es nuevo solo se utiliza para calcular precio, no se graba ?>
							<label class="control-label " >
								Coste Ultimo:
								<span title="Editamos coste ultimo, este campo no se cambia en BD aquí, vete a proveedores y cambialo, solo sirve para recalcular precio" class="glyphicon glyphicon-cog"></span>
							</label>
							<div>
								<input type="text" id="coste" size="8" name="costeultimo" value="<?php echo number_format($Producto['ultimoCoste'],2, '.', '');?>"   readonly> 
								<span class="Euro_grande">€</span> 
							</div>
						</div>
						<div class="form-group col-md-4 ">	
							<label class="control-label " > Iva:</label>
							<select id="idIva" name="idIva" onchange="modifPrecioCiva();">
								<?php echo $htmlIvas; ?>
							</select>
						</div>
						<div class="form-group col-md-4 ">
							<?php // Si es nuevo no se muestra ?>
							<label class="control-label " >Coste Promedio:</label>
							<div>
								<input type="text" id="costepromedio" size="8" name="costepromedio" placeholder="coste" value="<?php echo number_format($Producto['costepromedio'],2, '.', '');?>"   readonly> 
								<span class="Euro_grande">€</span> 
							</div>
						</div>
					</div>
					<div class="col-md-12">
						<h4> Precios de venta</h4>
						<div class="col-md-4 ">	
								<?php // beneficio solo 2 enteros ?>
								<label class="control-label-inline " > Beneficio:</label>
								<input type="text" id="beneficio" size="5" name="beneficio" placeholder="beneficio" value="<?php echo number_format($Producto['beneficio'],2,'.','');?>"   > %
						</div>
						<div class="col-md-4 ">	
							<label class="control-label " > Precio sin Iva:</label>
							<input type="text" id="pvpSiva" name="pvpSiva"  onchange="modifPrecioCiva();" value="<?php echo number_format($Producto['pvpSiva'],2, '.', '');?>"   >
						</div>
						<div class="col-md-4 ">	
							<label class="control-label " >
								Precio con Iva:
							<a onclick="recalcularPrecioSegunCosteBeneficio()">
							<span title ="Recalcular según beneficio y ultimo coste" class="glyphicon glyphicon-refresh"></span>
							</a>
							</label>
							<input type="text" id="pvpCiva" name="pvpCiva"  onchange="modifPrecioSiva();" value="<?php echo number_format($Producto['pvpCiva'],2, '.', '');?>"   >
						</div>
					</div>
				</div>
				<div class="col-md-6 text-center">
					 <div class="panel-group">
						<!-- Inicio collapse de CobBarras --> 
						<?php 
						$num = 1 ; // Numero collapse;
						$titulo = 'Códigos de Barras';
						echo htmlPanelDesplegable($num,$titulo,$htmlCodBarras);
						?>
						<!-- Inicio collapse de Proveedores --> 
						<?php 
						$num = 2 ; // Numero collapse;
						$titulo = 'Proveedores - Costes';
						echo htmlPanelDesplegable($num,$titulo,$htmlProveedoresCostes);
						?>
						<!-- Inicio collapse de Familias --> 
						<?php 
						$num = 3; // Numero collapse;
						$titulo = 'Familias';
						echo htmlPanelDesplegable($num,$titulo,$htmlFamilias);
						?>

						
						
						<!-- Inicio collapse de Referencias Tiendas --> 

					<!-- Fin de panel-group -->
					</div> 
				<!-- Fin div col-md-6 -->
				</div>
			</div>
		<!--fin de div container-->
		</div> 
	</body>
</html>
