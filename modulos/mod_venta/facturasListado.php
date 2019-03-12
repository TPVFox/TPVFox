
<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
	include $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_venta/funciones.php';
	include_once $URLCom.'/clases/cliente.php';
    include_once $URLCom.'/modulos/mod_venta/clases/facturasVentas.php';
	include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
	include_once $URLCom.'/controllers/Controladores.php';
    $Controler = new ControladorComun; 
	$Ccliente=new Cliente($BDTpv);
	$Cfactura=new FacturasVentas($BDTpv);
	$todosTemporal=$Cfactura->TodosTemporal();
	if (isset($todosTemporal['error'])){
	$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $todosTemporal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
	}
	$todosTemporal=array_reverse($todosTemporal);
	$Tienda = $_SESSION['tiendaTpv'];
		
	// ===========    Paginacion  ====================== //
	$NPaginado = new PluginClasePaginacion(__FILE__);
	$campos = array( 'a.Numfaccli','b.Nombre');

	$NPaginado->SetCamposControler($campos);
	$NPaginado->SetOrderConsulta('a.Numfaccli');
	// --- Ahora contamos registro que hay para es filtro --- //
	$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND

	$CantidadRegistros=0;
	// Obtenemos la cantidad registros 
	$f= $Cfactura->TodosFacturaFiltro($filtro);
		
	$CantidadRegistros = count($f['Items']);
	
	// --- Ahora envio a NPaginado la cantidad registros --- //
	$NPaginado->SetCantidadRegistros($CantidadRegistros);
	$htmlPG = $NPaginado->htmlPaginado();
	//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
	$f=$Cfactura->TodosFacturaFiltro($filtro.$NPaginado->GetLimitConsulta());
	$facturasDef=$f['Items'];
if (isset($f['error'])){
		$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $f['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
}
?>

</head>

<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_venta/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>     
<?php
//~ include '../../header.php';
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
		<div class="row">
			<div class="col-md-12 text-center">
					<h2>Ventas: Editar y Añadir facturas </h2>
				</div>
					<nav class="col-sm-4">
				<h4> Facturas </h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if($ClasePermisos->getAccion("Crear")==1){
                        echo '<li><a href="#section2" onclick="metodoClick('."'".'AgregarFactura'."'".');";>Añadir</a></li>';
                    }
                     if($ClasePermisos->getAccion("Modificar")==1){
                         echo '<li><a href="#section2" onclick="metodoClick('."'".'Ver'."'".','."'".'factura'."'".');";>Modificar</a></li>';
                     }
					?>
				</ul>	
					<div class="col-md-12">
		<h4 class="text-center"> Facturas Abiertas</h4>
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Nº Temp</th>
					<th>Nº Fac</th>
					<th>Cliente</th>
					<th>Total</th>
				</tr>
				
			</thead>
			<tbody>
				<?php
			if (isset($todosTemporal)){
				foreach ($todosTemporal as $temporal){
					if ($temporal['numfaccli']){
						$numTemporal=$temporal['numfaccli'];
					}else{
						$numTemporal="";
					}
					?>
					<tr>
						<td><a href="factura.php?tActual=<?php echo $temporal['id'];?>"><?php echo $temporal['id'];?></td>
						<td><?php echo $numTemporal;?></td>
						<td><?php echo $temporal['Nombre'];?></td>
						<td><?php echo number_format($temporal['total'],2);?></td>
						</tr>
					<?php
				}
			}
				?>
			</tbody>
		</table>
		</div>
			</nav>
			<div class="col-md-8">
					<p>
					 -Facturas encontrados BD local filtrados:
						<?php echo $CantidadRegistros; ?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
					<form action="./facturasListado.php" method="GET" name="formBuscar">
					<div class="form-group ClaseBuscar">
						<label>Buscar en número de factura </label>
						<input type="text" name="buscar" value="">
						<input type="submit" value="buscar">
					</div>
					</form>
					<div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th></th>
						<th></th>
                        <th></th>
						<th>Nª FACTURA</th>
						<th>FECHA</th>
						<th>CLIENTE</th>
						<th>BASE</th>
						<th>IVA</th>
						<th>TOTAL</th>
						<th>ESTADO</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					
						$checkUser = 0;
						foreach ($facturasDef as $factura){
						
							$checkUser = $checkUser + 1;
							$totalBase="0.00";
							$importeIva="0.00";
							$totaliva=$Cfactura->sumarIva($factura['Numfaccli']);
							if(isset( $totaliva['totalbase'])){
								$totalBase=$totaliva['totalbase'];
							}
							if(isset( $totaliva['importeIva'])){
								$importeIva=$totaliva['importeIva'];
							}
							$date=date_create($factura['Fecha']);
						?>
						<tr>
						<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $factura['id'];?>">
                         <td>
                             <?php 
                             if($ClasePermisos->getAccion("Modificar")==1){
                             ?>
                                <a class="glyphicon glyphicon-pencil" href='./factura.php?id=<?php echo $factura['id'];?>'>
                            <?php 
                            }
                            ?>
                        </td>
                        <td>
                               <?php 
                             if($ClasePermisos->getAccion("Ver")==1){
                            ?>
                            <a class="glyphicon glyphicon-eye-open" href='./factura.php?id=<?php echo $factura['id'];?>&estado=ver'>
                            <?php 
                            }
                            ?>
                        </td>
						<td><?php echo $factura['Numfaccli'];?></td>
						<td><?php echo date_format($date,'Y-m-d');?></td>
						<td><?php echo $factura['Nombre'];?></td>
						<td><?php echo $totalBase;?></td>
						<td><?php echo $importeIva;?></td>
						<td><?php echo $factura['total'];?></td>
						<?php 
						if ($factura['estado']=="Sin Guardar"){
							?>
							<td><?php echo $factura['estado'];?></td>
							<?php
						}else{
							$tienda=json_encode($_SESSION['tiendaTpv']);
							
							?>
						<td><?php echo $factura['estado'];?>  <a class="glyphicon glyphicon-print" onclick='imprimir(<?php echo $factura['id'];?>, "factura", <?php echo $tienda;?>)'></a></td>

							
							<?php
						}
						
						?>
						</tr>
						<?php
					}
					?>
				</tbody>
				</table>
			</div>
		</div>
	</div>
    </div>
	</body>
</html>
