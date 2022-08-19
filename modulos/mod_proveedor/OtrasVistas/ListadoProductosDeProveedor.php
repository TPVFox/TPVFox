
		 <?php
        include_once './../../../inicial.php';
        include_once $URLCom.'/modulos/mod_proveedor/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
		include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
        include_once $URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php';
        $CTArticulos = new ClaseProductos($BDTpv);
		$CProveedor= new ClaseProveedor();
		$Controler = new ControladorComun; 
        $style='';
		


		//$campoOrden=$_GET['campoorden'] ? : 'articulo_name';


		//TODO: comprobar que los campos por los que se ordena están en 
		//      la lista de campos por los que se puede ordenar.


		if(isset($_GET['campoorden'])){
			$campoOrden=$_GET['campoorden'];
		} else {
			$campoOrden = 'articulo_name';
		}

		if(isset($_GET['sentidoorden'])){
			$sentidoOrden=$_GET['sentidoorden'];
		} else {
			$sentidoOrden = 'ASC';
		}

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
		$ArrayProductoPrincipales = $CTArticulos->GetProductosProveedor($id, $campoOrden, $sentidoOrden);
       
		if ($ArrayProductoPrincipales['NItems']>0){
			$estados = $CTArticulos->posiblesEstados('articulos');
			$productos = [];
			foreach ($ArrayProductoPrincipales['Items'] as $key => $array){
				// Obtenemos datos producto, para añadir nombre Codbarras.
				$p =$CTArticulos->GetProducto($array['idArticulo']);
                
                $estado = $p['estado'];
                $productos[$key]['idArticulo'] = $p['idArticulo'];
				$productos[$key]['articulo_name'] = $p['articulo_name'];
				$productos[$key]['beneficio'] = $p['beneficio'];
				$productos[$key]['costepromedio'] = $p['costepromedio'];
				$productos[$key]['ultimoCoste'] = $p['ultimoCoste'];
				$productos[$key]['estado'] = $estado;
				$productos[$key]['iva'] = $p['iva'];
				$productos[$key]['tipo'] = $p['tipo'];
				$productos[$key]['stockOn'] = $p['stocks']['stockOn'];
                $productos[$key]['stockMin'] = $p['stocks']['stockMin'];
                $productos[$key]['codBarras']= $p['codBarras'];
                $productos[$key]['pvpCiva']=$p['pvpCiva'];
				foreach ($p['proveedores_costes'] as $pc){
					if ($pc['idProveedor'] == $id){
						$productos[$key]['costeProveedor'] = $pc['coste'];
						$productos[$key]['Ref_proveedor'] = $pc['crefProveedor'];
						$productos[$key]['fecha_actualizacion_proveedor']=$pc['fechaActualizacion'];
					} 
				}
				$index_estado = $CTArticulos->comprobacionesEstado($p);
				$productos[$key]['index_estado'] =$index_estado;
				if (isset($estados[$index_estado])){
						if (!isset($estados[$index_estado]['cantidad'])){
							// es la primero, por lo que creamo y poner valor 1
							$estados[$index_estado]['cantidad']=1;
						} else {
							// incrementamos uno
							$estados[$index_estado]['cantidad']++;
						}
				} else {
					// Realmente no existe el estado de ese producto, lo creamos , pero deberíamos crearlo como un error.
					$estados[]=  array( 'estado'      =>$estado,
															  'Descripcion' =>'Estado MAL !!.',
															  'error' => 'KO',
															  'cantidad'=>1
															);
					$productos[$key]['index_estado'] =count($estado); // Recuerda que el array estado empieza en 1 , no en 0
				}
                
			}
		}
		foreach ($estados as $i=>$estado){
			if (isset($estado['cantidad'])){
				// Recuerda que la estado en texto pueden ser varias palabras, por eso ponemos index
				$html = '<div class="checkbox">
							<label title="'.$estado['Descripcion'].'">
							<input type="checkbox" value="1" id="Check'.$i
							.'" onchange="filtroEstado(this,'.$i.')"checked>'
							.$estado['estado'].'<span class="badge">'.$estado['cantidad'].'</span>
						</label>
						</div>';
				$estados[$i]['html'] =$html;
			}
		}
        //Strock minimo
        $stockMin=5;
        $stockMinKilos=2;
		?>

<!DOCTYPE html>
<html>
    <head>
    <?php
        include_once $URLCom.'/head.php';
    ?>
    <script src="<?php echo $HostNombre; ?>/lib/js/tpvfoxSinExport.js"></script>   
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
                        echo '<div>';
                        echo '<h4>Datos del proveedor</h4>';
                        echo '<strong>Nombre Comercial:</strong>'.$datosProveedor['datos'][0]['nombrecomercial'].'<br/>';
						echo '<strong>Razon Social:</strong>'.$datosProveedor['datos'][0]['razonsocial'].'<br/>';
						echo '<strong>email:</strong>'.$datosProveedor['datos'][0]['email'].'<br/>';
						echo '<strong>Telefono:</strong>'.$datosProveedor['datos'][0]['telefono'].'<br/>';
						echo '<strong>fax:</strong>'.$datosProveedor['datos'][0]['fax'].'<br/>';
						echo '<strong>movil:</strong>'.$datosProveedor['datos'][0]['movil'].'<br/>';
                        echo '</div>';
                        echo '<div>';
                        echo '<h4>Otros Datos</h4>';
                        echo '<strong>Productos:</strong>'.count($productos);
						echo '</div>';
						echo '<div><h4>Filtrar por estado:</h4>';
                        foreach ($estados as $estado){
							if (isset($estado['html'])){
								echo $estado['html'];
							}   
                        }
                        echo '<a onclick="imprimirSeleccion('.$id.')">Imprimir selección</a>';
                        echo '</div>';
					?>
				</div>
				<div class="col-md-9">
					<table class="table">
						<thead>
							<tr>
                                <th><input type="checkbox" id= "chekArticuloAll" name="chekArticuloAll" value="0" onchange="SeleccionarTodos()"></th>
								<th>ID</th>
								<th></th>
                                <?php $icon = obtenerIconoOrden($campoOrden,$sentidoOrden,'articulo_name');?>
								<th class="ordenar" data-campo="articulo_name">Nombre Producto
                                    <?php echo $icon;?>
								</th>
								<th>Ultimo</th>
                                <?php $icon = obtenerIconoOrden($campoOrden,$sentidoOrden,'crefProveedor');?>
								<th class="ordenar" data-campo="crefProveedor">Ref_Proveedor
                                    <?php echo $icon;?>
								</th>
								<th>Coste Prov</th>
                                <?php $icon = obtenerIconoOrden($campoOrden,$sentidoOrden,'fechaActualizacion');?>
								<th class="ordenar" data-campo="fechaActualizacion">Fecha_Actualiza
                                    <?php echo $icon;?>
								</th>
                                <th>Precio</th>
                                <th>Código de Barras</th>
								<th>Stock</th>
                                <?php $icon = obtenerIconoOrden($campoOrden,$sentidoOrden,'articulo_name');?>
								<th class="ordenar" data-campo="a.estado">Estado
                                    <?php echo $icon;?>
								</th>
								<th></th>
							</tr>
						</thead>
							<tbody>

					<?php
                    
						foreach ($productos as $producto){
						$link_producto = '<a class="glyphicon glyphicon-eye-open" target="_blank" href="./../../mod_producto/producto.php?id='.$producto['idArticulo'].'"></a>';
						$link_mayor = '<a class="glyphicon glyphicon-list" target="_blank" href="./../../mod_producto/DetalleMayor.php?idArticulo='
								.$producto['idArticulo'].'"></a>';
                        
                        if(number_format($producto['stockOn'],0)< $producto['stockMin']){
                                $lineaRoja='danger';
                            }else{
                                $lineaRoja='';
                            }
						echo
							'<tr class="'.$lineaRoja.' Row'.$producto['index_estado'].'">
                                <td><input type="checkbox" class="chekArticulo" name="chekArticulo" value="'.$producto['idArticulo'].'">
								<td>'.$producto['idArticulo'].'</td>
								<td>'.$link_producto.'</td>
								<td>'.$producto['articulo_name'].'</td>
								<td>'.number_format($producto['ultimoCoste'],2).'</td>
								<td>'.$producto['Ref_proveedor'].'</td>
								<td>'.number_format($producto['costeProveedor'],2).'</td>
								<td>'.$producto['fecha_actualizacion_proveedor'].'</td>
                                <td>'.number_format($producto['pvpCiva'],2).'€</td>
                                <td>';
                                foreach ($producto['codBarras'] as $codBarras){
                                    echo $codBarras.'   ';
                                    
                                }
                                echo '</td>
								<td>';
                                
								if ($producto['tipo'] == 'peso'){
                                        echo '<p>'.number_format($producto['stockOn'],3).'</p>';
								} else {
                                         echo '<p>'.number_format($producto['stockOn'],0).'</p>';
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
		<script>
		catchEvents();
	</script>
	</body>
</html>
