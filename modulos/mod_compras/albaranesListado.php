
<!DOCTYPE html>
<html>
<head>
<?php
include './../../head.php';
include './funciones.php';
include ("./../../plugins/paginacion/paginacion.php");
include ("./../../controllers/Controladores.php");


$palabraBuscar=array();
	$stringPalabras='';
	$PgActual = 1; // por defecto.
	$LimitePagina = 40; // por defecto.
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
if ($stringPalabras !== '' ){
		$campoBD='Numalbcli ';
		$WhereLimite= $Controler->paginacionFiltroBuscar($stringPalabras,$LimitePagina,$desde,$campoBD);
		$filtro=$WhereLimite['filtro'];
		$OtrosParametros=$stringPalabras;
}
$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);

$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);

if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro'].$WhereLimite['rango'];
	} else {
		$filtro= " LIMIT ".$LimitePagina." OFFSET ".$desde;
	}
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
			</nav>
	</body>
</html>
