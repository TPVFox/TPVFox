
<!DOCTYPE html>
<html>
<head>
<?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");

	include '../../clases/cliente.php';
	include 'clases/facturasVentas.php';
	$Ccliente=new Cliente($BDTpv);
	$Cfactura=new FacturasVentas($BDTpv);
	$todosTemporal=$Cfactura->TodosTemporal();
		$todosTemporal=array_reverse($todosTemporal);
	$palabraBuscar=array();
	$stringPalabras='';
	$PgActual = 1; // por defecto.
	$LimitePagina = 30; // por defecto.
	$filtro = ''; // por defecto
	$WhereLimite['filtro']="";
	$NuevoRango="";
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
		$campo = array( 'a.Numfaccli','b.Nombre');
		$NuevoWhere = $Controler->ConstructorLike($campo, $stringPalabras, 'OR');
		$NuevoRango=$Controler->ConstructorLimitOffset($LimitePagina, $desde);
		$OtrosParametros=$stringPalabras;
		$WhereLimite['filtro']='WHERE '.$NuevoWhere;
}
$CantidadRegistros=count($Cfactura->TodosFacturaFiltro($WhereLimite['filtro']));
$WhereLimite['rango']=$NuevoRango;
$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);

if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro']." ORDER BY Numfaccli desc ".$WhereLimite['rango'];
} else {
		$filtro= "ORDER BY Numfaccli desc LIMIT ".$LimitePagina." OFFSET ".$desde;
}
	
$facturasDef=$Cfactura->TodosFacturaFiltro($filtro);
?>

</head>

<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_venta/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>     
<?php
include '../../header.php';
?>
		<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Facturas de clientes: Editar y Añadir facturas </h2>
				</div>
					<nav class="col-sm-4">
				<h4> Facturas </h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] === '1'){
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
							$totaliva=$Cfactura->sumarIva($factura['Numfaccli']);
							$date=date_create($factura['Fecha']);
						?>
						<tr>
						<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $factura['id'];?>">
					
						<td><?php echo $factura['Numfaccli'];?></td>
						<td><?php echo date_format($date,'Y-m-d');?></td>
						<td><?php echo $factura['Nombre'];?></td>
						<td><?php echo $totaliva['totalbase'];?></td>
						<td><?php echo $totaliva['importeIva'];?></td>
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


