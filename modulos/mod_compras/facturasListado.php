<!DOCTYPE html>
<html>
<head>
<?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/ClasePaginacion.php");
	

	include ("./../../controllers/Controladores.php");
	$Controler = new ControladorComun; 

	include '../../clases/Proveedores.php';
	$CProv= new Proveedores($BDTpv);
	include 'clases/facturasCompras.php';
	$CFac=new FacturasCompras($BDTpv);
	//Guardamos en un array todos los datos de las facturas temporales
	$todosTemporal=$CFac->TodosTemporal();
	$todosTemporal=array_reverse($todosTemporal);
	$Tienda = $_SESSION['tiendaTpv'];
	$WhereLimite = array();
	$WhereLimite['filtro'] = '';
	$NuevoRango = '';
	if (isset($_GET['pagina'])) {
		$PgActual = $_GET['pagina'];
	}
	if (isset($_GET['buscar'])) {  
		//recibo un string con 1 o mas palabras
		$stringPalabras = $_GET['buscar'];
		$palabraBuscar = explode(' ',$_GET['buscar']); 
	} 
	$Controler = new ControladorComun; 
	$vista = 'albclit';
	$LinkBase = './facturasListado.php?';
	$OtrosParametros = '';
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
		
	} else {
		$desde = 0;
	}
if ($stringPalabras !== '' ){
		$campo = array( 'a.Numfacpro','b.nombrecomercial');
		$NuevoWhere = $Controler->ConstructorLike($campo, $stringPalabras, 'OR');
		$NuevoRango=$Controler->ConstructorLimitOffset($LimitePagina, $desde);
		$OtrosParametros=$stringPalabras;
		$WhereLimite['filtro']='WHERE '.$NuevoWhere;
	}
$CantidadRegistros=count($CFac->TodosFacturaLimite($WhereLimite['filtro']));
$WhereLimite['rango']=$NuevoRango;
$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);

	
	// ===========    Paginacion  ====================== //
	$NPaginado = new PluginClasePaginacion(__FILE__);
	$campos = array( 'a.Numfacpro','b.nombrecomercial');
	$NPaginado->SetCamposControler($Controler,$campos);
	// --- Ahora contamos registro que hay para es filtro --- //
	$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND
	$CantidadRegistros=0;
	// Obtenemos la cantidad registros 
	$f = $CFac->TodosFacturaLimite($filtro);
	$CantidadRegistros = count($f['Items']);
	// --- Ahora envio a NPaginado la cantidad registros --- //
	$NPaginado->SetCantidadRegistros($CantidadRegistros);
	$htmlPG = $NPaginado->htmlPaginado();
	$f = $CFac->TodosFacturaLimite($filtro.$NPaginado->GetLimitConsulta());
	$facturasDef=array_reverse($f['Items']);

?>

</head>

<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>  
<?php

	include '../../header.php';
	?>
		<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Facturas Compras: Editar y Añadir facturas </h2>
				</div>
					<nav class="col-sm-4">
				<h4> Facturas</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] > '0'){
				?>
					<li><a href="#section2" onclick="metodoClick('AgregarFactura');";>Añadir</a></li>
					<?php 
				}
					?>
					<li><a href="#section2" onclick="metodoClick('Ver','factura');";>Modificar</a></li>
				
				</ul>
				<div class="col-md-12">
		<h4 class="text-center"> Facturas Abiertas</h4>
		<table class="table table-striped">
			<thead>
				<tr>
					<th WIDTH="4">Nº Temp</th>
					<th WIDTH="4">Nº Fac</th>
					<th WIDTH="100">Pro.</th>
					<th WIDTH="4">Total</th>
				</tr>
				
			</thead>
			<tbody>
				<?php
				
			if (isset($todosTemporal)){
				foreach ($todosTemporal as $temporal){
					if ($temporal['numfacpro']){
						$numTemporal=$temporal['numfacpro'];
					}else{
						$numTemporal="";
					}
					?>
					<tr>
						<td><a href="factura.php?tActual=<?php echo $temporal['id'];?>"><?php echo $temporal['id'];?></td>
						<td><?php echo $numTemporal;?></td>
						<td><?php echo $temporal['nombrecomercial'];?></td>
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
						<label>Buscar por nombre de proveedor o número de factura</label>
						<input type="text" name="buscar" value="">
						<input type="submit" value="buscar">
					</div>
					</form>
					<div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th></th>
						
						<th>Nª FACTURA</th>
						<th>FECHA</th>
						<th>PROVEEDOR</th>
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
							$totaliva=$CFac->sumarIva($factura['Numfacpro']);
							$totalBase="0.00";
							$importeIva="0.00";
							if(isset( $totaliva['totalbase'])){
								$totalBase=$totaliva['totalbase'];
							}
							if(isset( $totaliva['importeIva'])){
								$importeIva=$totaliva['importeIva'];
							}
						
							$date=date_create($factura['Fecha']);
							
							$htmlImpirmir=montarHTMLimprimir($factura['id'], $BDTpv, "factura", $Tienda['idTienda']);
							
						?>
						<tr>
						<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $factura['id'];?>">
					
						<td><?php echo $factura['Numfacpro'];?></td>
						<td><?php echo date_format($date,'Y-m-d');?></td>
						<td><?php echo $factura['nombrecomercial'];?></td>
						<td><?php echo $totalBase;?></td>
						<td><?php echo $importeIva;?></td>
						<td><?php echo $factura['total'];?></td>
						<?php 
						if ($factura['estado']=="Sin Guardar"){
							?>
							<td><?php echo $factura['estado'];?></td>
							<?php
						}else{
						?>
						<td><?php echo $factura['estado'];?>  <a class="glyphicon glyphicon-print" onclick='imprimir(<?php echo $factura['id'];?>, "factura", <?php echo $_SESSION['tiendaTpv']['idTienda'];?>)'></a></td>
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

