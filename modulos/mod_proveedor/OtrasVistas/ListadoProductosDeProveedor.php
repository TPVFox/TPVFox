
		 <?php
        include_once './../../../inicial.php';
        include_once $URLCom.'/modulos/mod_proveedor/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
		include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
        include_once $URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php';
        $CTArticulos = new ClaseProductos($BDTpv);
		$CProveedor= new ClaseProveedor();
		$Controler = new ControladorComun; 
        $style='style="display:none;"';
		if(isset($_GET['id'])){
			$id=$_GET['id'];
			$datosProveedor=$CProveedor->getProveedor($id);
			if(isset($datosProveedor['error'])){
				$errores[1]=array ( 'tipo'=>'DANGER!',
								 'dato' => $datosProveedor['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error sql'
								 );
			
			}else{
				$titulo='Listado de Productos como principal Proveedor:';
			}
		}else{
			$errores[1]=array ( 'tipo'=>'DANGER!',
								 'dato' => '',
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error no se ha enviado el id del proveedor'
								 );
		}
		$ArrayProductoPrincipales = $CTArticulos->GetProductosProveedor($id);
		if ($ArrayProductoPrincipales['NItems']>0){
			$productos = [];
			foreach ($ArrayProductoPrincipales['Items'] as $key => $array){
				// Obtenemos datos producto, para añadir nombre Codbarras.
				$p =$CTArticulos->GetProducto($array['idArticulo']);
				$productos[$key]['idArticulo'] = $p['idArticulo'];
				$productos[$key]['articulo_name'] = $p['articulo_name'];
				$productos[$key]['beneficio'] = $p['beneficio'];
				$productos[$key]['costepromedio'] = $p['costepromedio'];
				$productos[$key]['ultimoCoste'] = $p['ultimoCoste'];
				$productos[$key]['estado'] = $p['estado'];
				$productos[$key]['iva'] = $p['iva'];
				$productos[$key]['tipo'] = $p['tipo'];
				$productos[$key]['stockOn'] = $p['stocks']['stockOn'];
				foreach ($p['proveedores_costes'] as $pc){
					if ($pc['idProveedor'] == $id){
						$productos[$key]['costeProveedor'] = $pc['coste'];
						$productos[$key]['Ref_proveedor'] = $pc['crefProveedor'];
						$productos[$key]['fecha_actualizacion_proveedor']=$pc['fechaActualizacion'];
					} 
				}
			}
		}
		?>

<!DOCTYPE html>
<html>
    <head>
    <?php
        include_once $URLCom.'/head.php';
    ?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js"></script>

	</head>
	<body>
	<?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
        if (isset($errores)){
            foreach($errores as $error){
                echo '<div class="'.$error['class'].'">'
                . '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
                . '</div>';
            }
        }
	?>
		<div class="container">
			<div class="col-md-12 text-center" >
					<h2 class="text-center"> <?php echo $titulo;?></h2>
			</div>
			<div class="col-md-12" >
				<div class="col-md-3">
				<?php 
						echo $Controler->getHtmlLinkVolver().'<br/>';
						echo 'Nombre Comercial:'.$datosProveedor['datos'][0]['nombrecomercial'].'<br/>';
						echo 'Razon Social:'.$datosProveedor['datos'][0]['razonsocial'].'<br/>';
						echo 'email:'.$datosProveedor['datos'][0]['email'].'<br/>';
						echo 'Telefono:'.$datosProveedor['datos'][0]['telefono'].'<br/>';
						echo 'fax:'.$datosProveedor['datos'][0]['fax'].'<br/>';
						echo 'movil:'.$datosProveedor['datos'][0]['movil'].'<br/>';
					?>
				</div>
				<div class="col-md-9">
					<table class="table">
						<thead>
							<tr>
								<th>ID</th>
								<th></th>
								<th>Nombre Producto</th>
								<th>Ultimo</th>
								<th>Ref_Proveedor</th>
								<th>Coste Prov</th>
								<th>Fecha_Actualiza</th>
								<th>Stock</th>
								<th>Estado</th>
								<th></th>
							</tr>
						</thead>
							<tbody>
					<?php
						foreach ($productos as $producto){
						$link_producto = '<a class="glyphicon glyphicon-eye-open" target="_blank" href="./../../mod_producto/producto.php?id='.$producto['idArticulo'].'"></a>';
						$link_mayor = '<a class="glyphicon glyphicon-list" target="_blank" href="./../../mod_producto/DetalleMayor.php?idArticulo='
								.$producto['idArticulo'].'"></a>';
						echo
							'<tr>
								<td>'.$producto['idArticulo'].'</td>
								<td>'.$link_producto.'</td>
								<td>'.$producto['articulo_name'].'</td>
								<td>'.number_format($producto['ultimoCoste'],2).'</td>
								<td>'.$producto['Ref_proveedor'].'</td>
								<td>'.number_format($producto['costeProveedor'],2).'</td>
								<td>'.$producto['fecha_actualizacion_proveedor'].'</td>
								<td>';
								if ($producto['tipo'] == 'peso'){
									echo number_format($producto['stockOn'],3);
								} else {
									echo number_format($producto['stockOn'],0);
								}
								echo '</td>
								<td>'.$producto['estado'].'</td>
								<td>'.$link_mayor.'</td>
							</tr>';
						}
					?>
						</tbody>
					</table>
            	</div>
        	</div>
		</div>
	</body>
</html>
