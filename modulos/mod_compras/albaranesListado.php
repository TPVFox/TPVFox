
<!DOCTYPE html>
<html>
<head>
<?php
include './../../head.php';
include './funciones.php';
include ("./../../plugins/paginacion/paginacion.php");
include ("./../../controllers/Controladores.php");
include '../../clases/Proveedores.php';
$CProv= new Proveedores($BDTpv);
include 'clases/albaranesCompras.php';
$CAlb=new AlbaranesCompras($BDTpv);
//Guardamos en un array los datos de los albaranes temporales
$todosTemporal=$CAlb->TodosTemporal();
$palabraBuscar=array();
	$stringPalabras='';
	$PgActual = 1; // por defecto.
	$LimitePagina = 10; // por defecto.
	$filtro = ''; // por defecto
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
//~ if ($stringPalabras !== '' ){
		//~ $campoBD='Numalbcli ';
		//~ $WhereLimite= $Controler->paginacionFiltroBuscar($stringPalabras,$LimitePagina,$desde,$campoBD);
		//~ $filtro=$WhereLimite['filtro'];
		//~ $OtrosParametros=$stringPalabras;
//~ }
if ($stringPalabras !== '' ){
	//	$campoBD='Numpedpro';
		$campo = array( 'a.Numalbpro','b.nombrecomercial');
		$NuevoWhere = $Controler->ConstructorLike($campo, $stringPalabras, 'OR');
		$NuevoRango=$Controler->ConstructorLimitOffset($LimitePagina, $desde);
		$OtrosParametros=$stringPalabras;
		$WhereLimite['filtro']='WHERE '.$NuevoWhere;
	}
//$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
$CantidadRegistros=count($CAlb->TodosAlbaranesLimite($WhereLimite['filtro']));
$WhereLimite['rango']=$NuevoRango;
$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);

if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro']." ORDER BY Numalbpro desc ".$WhereLimite['rango'];
	} else {
		$filtro= " ORDER BY Numalbpro desc LIMIT ".$LimitePagina." OFFSET ".$desde;
	}
	//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
$albaranesDef=$CAlb->TodosAlbaranesLimite($filtro);
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
					<h2> Albaranes Compras: Editar y Añadir albaranes </h2>
				</div>
					<nav class="col-sm-2">
				<h4> Albaranes</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] === '1'){
				?>
					<li><a href="#section2" onclick="metodoClick('AgregarAlbaran');";>Añadir</a></li>
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
					<th>Nº Temp</th>
					<th>Nº Alb</th>
					<th>Cliente</th>
					<th>Total</th>
				</tr>
				
			</thead>
			<tbody>
				<?php
			if (isset($todosTemporal)){
				foreach ($todosTemporal as $temporal){
					if ($temporal['numalbpro']){
						$numTemporal=$temporal['numalbpro'];
					}else{
						$numTemporal="";
					}
					?>
					<tr>
						<td><a href="albaran.php?tActual=<?php echo $temporal['id'];?>"><?php echo $temporal['id'];?></td>
						<td><?php echo $numTemporal;?></td>
						<td><?php echo $temporal['idProveedor'];?></td>
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
			<div class="col-md-9">
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
							$totaliva=$CAlb->sumarIva($albaran['Numalbpro']);
							$date=date_create($albaran['Fecha']);
						//	$htmlImpirmir=montarHTMLimprimir($albaran['id'], $BDTpv, "albaran");
						?>
						<tr>
						<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $albaran['id'];?>">
					
						<td><?php echo $albaran['Numalbpro'];?></td>
						<td><?php echo date_format($date,'Y-m-d');?></td>
						<td><?php echo $albaran['nombrecomercial'];?></td>
						<td><?php echo $totaliva['totalbase'];?></td>
						<td><?php echo $totaliva['importeIva'];?></td>
						<td><?php echo $albaran['total'];?></td>
						<?php
						if ($albaran['estado']=="Sin Guardar"){
							?>
							<td><?php echo $albaran['estado'];?></td>
							<?php
						}else{
							?>
						<td><?php echo $albaran['estado'];?>  <a class="glyphicon glyphicon-print" onclick='imprimir(<?php echo $albaran['id'];?>, "albaran", <?php echo $_SESSION['tiendaTpv']['idTienda'];?>)'></a></td>

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
