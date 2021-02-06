<!DOCTYPE html>
<html>
	<head>
		<?php 
        include_once './../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_producto/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
		include_once $URLCom.'/clases/articulos.php';
		include_once $URLCom.'/clases/Proveedores.php';
		
		include_once ($URLCom.'/controllers/parametros.php');
		$Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$ClasesParametros = new ClaseParametros('parametros.xml');
		$parametros = $ClasesParametros->getRoot();
		$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
		$CProveedor=new Proveedores($BDTpv);
		$CAlbaran=new AlbaranesCompras($BDTpv);
		$CArticulo=new Articulos($BDTpv);
		$ruta_volver= $HostNombre.'/modulos/mod_compras/albaranesListado.php';
		$titulo="Recalculo precios PVP ";
        $comprobar="";
		$Usuario = $_SESSION['usuarioTpv'];
        $i=1;
      
        if (isset($_GET['id'])){
			$id=$_GET['id'];
			$dedonde="albaran";
			
			$subtitulo='de '.$dedonde.' :'.$id;
			$titulo=$titulo.' '.$subtitulo;
			
			$datosAlbaran=$CAlbaran->datosAlbaran($id);
			$fecha=date_create($datosAlbaran['Fecha']);
			$fecha=date_format($fecha, 'Y-m-d');
			
			$productosHistoricos=$CArticulo->historicoCompras($id, "albaran", "compras");
			 //~ echo '<pre>';
            //~ print_r($productosHistoricos);
            //~ echo '</pre>';
            $productosHistoricos=comprobarRecalculosSuperiores($productosHistoricos, $CArticulo );
           
			$datosProveedor=$CProveedor->buscarProveedorId($datosAlbaran['idProveedor']);

		}
		if (isset($_POST['Guardar'])){
			$id=$_GET['id'];
			$estado="";
			$fechaCreacion=date('Y-m-d');
           
			foreach ($productosHistoricos as $producto){
				if ($producto['estado']=="Pendiente"){
                   
					$idArticulo=$producto['idArticulo'];
					
					$pvpRecomendadoCiva=$_POST['pvpRecomendado_'.$i];
					$pvpRecomendadoCiva=(float)$pvpRecomendadoCiva;
					
					$datosArticulo=$CArticulo->datosPrincipalesArticulo($idArticulo);
					$datosPrecios=$CArticulo->articulosPrecio($idArticulo);
					$articuloPrecioAnt=$datosPrecios['pvpCiva'];
					
                  
					if ($pvpRecomendadoCiva != $articuloPrecioAnt){
					
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
						'estado'=>$estado,
						'idUsuario'=>$Usuario['id']
						);
						$nuevoHistorico=$CArticulo->addHistorico($datosHistorico);	
						$modPrecios=$CArticulo->modArticulosPrecio($pvpRecomendadoCiva, $nuevoSiva, $idArticulo);
                       //~ echo '<pre>';
                       //~ print_r($nuevoHistorico);
                       //~ echo '</pre>';
                       
                      
					}
					
				}
                if($producto['estado']=="Pendiente" || $producto['estado']=="Sin revisar" || $producto['estado']=="Sin Cambios"){
                    $i++;
                }
				
				$estado="";
			}
			 $modificarHistorico=$CArticulo->modificarEstadosHistorico($id, $dedonde );
             
             //~ echo '<pre>';
             //~ print_r($productosHistoricos);
             //~ echo '</pre>';
		}
		
		
		?>
		
		
	</head>
	<body>
	<?php
	 //~ include_once $URLCom.'/header.php';
     include_once $URLCom.'/modulos/mod_menu/menu.php';
	?>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
	<script type="text/javascript">
		<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
      <?php 
      if (isset($_POST['Guardar'])){
		  ?>
		  mensajeImprimir(<?php echo $id;?>, <?php echo "'".$dedonde."'"; ?>);
		
		 
		  <?php
	  }
	  if (isset($_POST['Imprimir'])){
		?>
		 imprimir(<?php echo $id;?>, <?php echo "'".$dedonde."'"; ?>);
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
				<div class="col-md-2">
					<strong>ID Proveedor:</strong><br>
					
					<input type="text" name="idProveedor" id="idProveedor" size="10"   value="<?php echo $datosAlbaran['idProveedor'];?>" readonly >
				</div>
				<div class="col-md-3">
					<strong>Proveedor:</strong><br>
				
					<input type="text" name="nombreProveedor" id="nombreProveedor" size="10"   value="<?php echo $datosProveedor['nombrecomercial'];?>" readonly >
				</div>
				<div class="col-md-3">
					<strong>Proveedor:</strong><br>
					
					<input type="text" name="razonsocial" id="razonsocial" size="10"   value="<?php echo $datosProveedor['razonsocial'];?>" readonly >
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
						<th>REFERENCIA</th>
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
                        $datosArticuloProveedor=$CArticulo->buscarReferencia($producto['idArticulo'], $datosAlbaran['idProveedor']);
                        $ivaPrecio=$datosArticulo['iva']/100;
                        $ivaProducto=$producto['Nuevo']*$ivaPrecio;
                        $precioProducto=$producto['Nuevo']+$ivaProducto;
                        $beneficio=$datosArticulo['beneficio']/100;
                        $beneficioArticulo=$precioProducto*$beneficio;
                        $pvpRecomendado=$beneficioArticulo+$precioProducto;
                        
                        if ($producto['estado']=="Pendiente" || $producto['estado']=="Sin revisar"){
                            $class="";
                        }else{
                            $class="class='tachado'";
                        }
                        echo '<tr id="Row'.$i.'" '.$class.'>';
                        echo '<td>'.$producto['idArticulo'].'</td>';
                        echo '<td>'.$datosArticulo['articulo_name'].'</td>';
                        echo '<td>'.$datosArticuloProveedor['crefProveedor'].'</td>';
                        echo '<td>'.$producto['Nuevo'].'</td>';
                        echo '<td>'.$producto['Antes'].'</td>';
                        echo '<td>'.$datosArticulo['beneficio'].'</td>';
                        echo '<td>'.$datosArticulo['iva'].'</td>';
                        echo '<td>'.number_format($datosPrecios['pvpCiva'],4).'</td>';
                        if($producto['estado']=="Sin revisar"){
                              echo '<td><input type="text" id="pvpRecomendado_'.$i.'" name="pvpRecomendado_'.$i.'"  
                              onkeydown="controlEventos(event)" data-obj="pvpRecomendado" 
                              value="'.number_format($pvpRecomendado,2).'" size="5" disabled>
                              <span class="glyphicon glyphicon-ban-circle" style="color:red"  title="Este producto tiene recalculos de precio posteriores"></span>
                              </td>';
                        }else{
                            echo '<td><input type="text" id="pvpRecomendado_'.$i.'" name="pvpRecomendado_'.$i.'"  onkeydown="controlEventos(event)" data-obj="pvpRecomendado" value="'.number_format($pvpRecomendado,2).'" size="5"></td>';
                        }
                        if($producto['estado']=="Sin revisar"){
                            echo '<td></td>';
                        }else{
                            if ($producto['estado']=="Pendiente" ){
                                echo '<td class="eliminar"><a onclick="eliminarCoste('.$producto['idArticulo'].', '."'".$dedonde."'".', '.$id.', '."'".'compras'."'".', '.$i.')"><span class="glyphicon glyphicon-trash"></span></a></td>';
                            }else{
                                echo '<td class="eliminar"><a onclick="retornarCoste('.$producto['idArticulo'].', '."'".$dedonde."'".', '.$id.', '."'".'compras'."'".', '.$i.')"><span class="glyphicon glyphicon-export"></span></a></td>';
                            }
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
