<!DOCTYPE html>
<html>
<head>
<?php
include_once './../../inicial.php';
include_once $URLCom.'/head.php';
include_once $URLCom.'/modulos/mod_compras/funciones.php';
include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
include_once $URLCom.'/controllers/Controladores.php';
include_once $URLCom.'/clases/Proveedores.php';
include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
if (isset($_SESSION['tiendaTpv'])){
	$Tienda = $_SESSION['tiendaTpv'];
}
// Creamos el objeto de controlador.
$Controler = new ControladorComun; 

$CArticulo=new Articulos($BDTpv);
// Creamos el objeto de proveedor
$CProv= new Proveedores($BDTpv);
// Creamos el objeto de albarán
$CAlb=new AlbaranesCompras($BDTpv);

//Guardamos en un array los datos de los albaranes temporales
$todosTemporal=$CAlb->TodosTemporal();
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
	$campos = array( 'a.Numalbpro','b.nombrecomercial');

	$NPaginado->SetCamposControler($Controler,$campos);
	$NPaginado->SetOrderConsulta('a.Numalbpro');
	// --- Ahora contamos registro que hay para es filtro --- //
	$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND
	$CantidadRegistros=0;
	// Obtenemos la cantidad registros 
	$a = $CAlb->TodosAlbaranesLimite($filtro);
		
	$CantidadRegistros = count($a['Items']);
	
	// --- Ahora envio a NPaginado la cantidad registros --- //
	$NPaginado->SetCantidadRegistros($CantidadRegistros);
	$htmlPG = $NPaginado->htmlPaginado();
	//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
	$a=$CAlb->TodosAlbaranesLimite($filtro.$NPaginado->GetLimitConsulta());
	
	 //~ $albaranesDef=array_reverse($a['Items']);
	 $albaranesDef=$a['Items'];
	if (isset($a['error'])){
		$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $a['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
	}
?>

</head>

<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>     
<?php

	//~ include $URLCom.'/header.php';
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
					<h2>Compras: Editar y Añadir albaranes</h2>
				</div>
					<nav class="col-sm-4">
				<h4> Albaranes</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] > '0'){
				?>
					<li><a href="#section2" onclick="metodoClick('AgregarAlbaran');";>Añadir</a></li>
					<?php 
				}
					?>
					<li><a href="#section2" onclick="metodoClick('Ver','albaran');";>Modificar</a></li>
				
				</ul>
				<div class="col-md-12">
		<h4 class="text-center"> Albaranes Abiertos</h4>
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th WIDTH="4" >Nº Temp</th>
					<th WIDTH="4" >Nº Alb</th>
					<th WIDTH="100">Pro.</th>
					<th WIDTH="4" >Total</th>
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
						<td ><?php echo $temporal['nombrecomercial'];?></td>
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
						<label>Buscar por nombre de proveedor o número de albarán</label>
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
						$iconoCostes=0;
						foreach ($albaranesDef as $albaran){
						
							$checkUser = $checkUser + 1;
							$totaliva=$CAlb->sumarIva($albaran['Numalbpro']);
							if ($albaran['estado']<>"Sin Guardar"){
								$historico=$CArticulo->historicoCompras($albaran['Numalbpro'], "albaran", "compras");
								foreach ($historico as $his){
									if($his['estado']=="Pendiente"){
										$iconoCostes=1;
									}
								}
							}
							$date=date_create($albaran['Fecha']);
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
						<td><?php echo $albaran['estado'];?>  <a class="glyphicon glyphicon-print" onclick='imprimir(<?php echo $albaran['id'];?>, "albaran", <?php echo $_SESSION['tiendaTpv']['idTienda'];?>)'></a>
						&nbsp;
						<?php 
						if($iconoCostes==1){
						?>
						<a class="glyphicon glyphicon-th-list" style="color:red" href="../mod_producto/Recalculo_precios.php?id=<?php echo $albaran['id'];?>"></a></td>

							<?php
						}
						$iconoCostes=0;
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
