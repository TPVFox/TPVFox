
<!DOCTYPE html>
<html>
<head>
<?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");

	include '../../clases/cliente.php';
	include 'clases/albaranesVentas.php';
	$Ccliente=new Cliente($BDTpv);
	$Calbaran=new AlbaranesVentas($BDTpv);
	
	$todosTemporal=$Calbaran->TodosTemporal();

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
	$LinkBase = './albaranesListado.php?';
	$OtrosParametros = '';
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
		
	} else {
		$desde = 0;
	}
if ($stringPalabras !== '' ){
		$campo = array( 'a.Numalbcli','b.Nombre');
		$NuevoWhere = $Controler->ConstructorLike($campo, $stringPalabras, 'OR');
		$NuevoRango=$Controler->ConstructorLimitOffset($LimitePagina, $desde);
		$OtrosParametros=$stringPalabras;
		$WhereLimite['filtro']='WHERE '.$NuevoWhere;
}
$CantidadRegistros=count($Calbaran->TodosAlbaranesFiltro($WhereLimite['filtro']));
$WhereLimite['rango']=$NuevoRango;
$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro']." ORDER BY Numalbcli desc ".$WhereLimite['rango'];
	} else {
		$filtro= "ORDER BY Numalbcli desc LIMIT ".$LimitePagina." OFFSET ".$desde;
	}	
	$albaranesDef=$Calbaran->TodosAlbaranesFiltro($filtro);
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
					<h2> Albaranes de clientes: Editar y Añadir pedidos </h2>
				</div>
					<nav class="col-sm-4">
				<h4> Albaranes </h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] === '1'){
				?>
					<li><a href="#section2" onclick="metodoClick('AgregarAlbaran', 'albaran');";>Añadir</a></li>
					<?php 
				}
					?>
					<li><a href="#section2" onclick="metodoClick('Ver','albaran');";>Modificar</a></li>
				
				</ul>	
					<div class="col-md-12">
		<h4 class="text-center"> Albaranes Abiertos</h4>
		<table class="table table-striped">
			<thead>
				<tr>
					<th WIDTH="4">Nº Temp</th>
					<th WIDTH="100">Nº Alb</th>
					<th WIDTH="4">Cliente</th>
					<th WIDTH="4">Total</th>
				</tr>
				
			</thead>
			<tbody>
				<?php
			if (isset($todosTemporal)){
				foreach ($todosTemporal as $temporal){
					if ($temporal['numalbcli']){
						$numTemporal=$temporal['numalbcli'];
					}else{
						$numTemporal="";
					}
					?>
					<tr>
						<td><a href="albaran.php?tActual=<?php echo $temporal['id'];?>"><?php echo $temporal['id'];?></td>
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
					 -Albaranes encontrados BD local filtrados:
						<?php echo $CantidadRegistros; ?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
					<form action="./albaranesListado.php" method="GET" name="formBuscar">
					<div class="form-group ClaseBuscar">
						<label>Buscar en número de albarán </label>
						<input type="text" name="buscar" value="">
						<input type="submit" value="buscar">
					</div>
					</form>
					<div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th></th>
						
						<th>Nª ALBARÁN</th>
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
						foreach ($albaranesDef as $albaran){
						
							$checkUser = $checkUser + 1;
							$totaliva=$Calbaran->sumarIva($albaran['Numalbcli']);
							$date=date_create($albaran['Fecha']);
						?>
						<tr>
						<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $albaran['id'];?>">
					
						<td><?php echo $albaran['Numalbcli'];?></td>
						<td><?php echo date_format($date,'Y-m-d');?></td>
						<td><?php echo $albaran['Nombre'];?></td>
						<td><?php echo $totaliva['totalbase'];?></td>
						<td><?php echo $totaliva['importeIva'];?></td>
						<td><?php echo $albaran['total'];?></td>
						<?php 
						if ($albaran['estado']=="Sin Guardar"){
							?>
							<td><?php echo $albaran['estado'];?></td>
							<?php
						}else{
							$tienda=json_encode($_SESSION['tiendaTpv']);
							
							?>
						<td><?php echo $albaran['estado'];?>  <a class="glyphicon glyphicon-print" onclick='imprimir(<?php echo $albaran['id'];?>, "albaran", <?php echo $tienda;?>)'></a></td>

							
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
