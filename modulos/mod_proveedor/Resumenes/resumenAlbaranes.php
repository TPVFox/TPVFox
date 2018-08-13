<!DOCTYPE html>
<html>
    <head>
		 <?php
        include_once './../../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_proveedor/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
        include_once ($URLCom.'/controllers/parametros.php');
        $ClasesParametros = new ClaseParametros('../parametros.xml');  
        include_once $URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php';
        
		$CProveedor= new ClaseProveedor($BDTpv);
		if(isset($_GET['id'])){
			$id=$_GET['id'];
			$datosProveedor=$CProveedor->getProveedor($id);
			if($datosProveedor['error']){
				$errores[1]=array ( 'tipo'=>'DANGER!',
								 'dato' => $datosProveedor['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error sql'
								 );
			
			}else{
				$titulo='Resumen Albaranes';
			}
		}else{
			$errores[1]=array ( 'tipo'=>'DANGER!',
								 'dato' => '',
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error no se ha enviado el id del proveedor'
								 );
		}
		if(isset($_POST['porfechas'])){
			//Cuando se envia por fechas se comprueba que las fechas estan bien escritas y que están las dos
			$comprobarFechas=comprobarFechas($_POST['fechaInicial'], $_POST['fechaFinal']);
			if(isset($comprobarFechas['error'])){
				$errores[8]=array ( 'tipo'=>'Info!',
								 'dato' => $comprobarFechas['consulta'],
								 'class'=>'alert alert-info',
								 'mensaje' => ''
								 );
			 }else{
				 header('Location: resumenAlbaranes.php?fechaIni='.$comprobarFechas['fechaIni'].
						'&fechaFin='.$comprobarFechas['fechaFin'].'&id='.$id);
			 }
		}
		if(isset($_POST['portodo'])){
			//Si buscamos todo recarga la página sin fechas
			 header('Location: resumenAlbaranes.php?fechaIni=&fechaFin=&id='.$id);
		}
			if(isset($_GET['fechaIni']) & isset($_GET['fechaFin'])){
			//Cuando recibimos los datos tenga fechas escritas o no buscamos los resumenes en la clase 
			//MOstramos errores de sql;
			$fechaIni=$_GET['fechaIni'];
			$fechaFin=$_GET['fechaFin'];
			$idProveedor=$_GET['id'];
			if($fechaIni<>"" & $fechaFin<>""){
				$fechaInicial =date_format(date_create($fechaIni), 'd-m-Y');
				$fechaFinal =date_format(date_create($fechaFin), 'd-m-Y');
			}
			$style="";
			$arrayNums=$CProveedor->albaranesProveedoresFechas($idProveedor, $fechaIni, $fechaFin);
			if(isset($arrayNums['error'])){
				$errores[1]=array ( 'tipo'=>'DANGER!',
								 'dato' => $arrayNums['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error de sql'
								 );
			}
		}
		
		?>
	</head>
	<body>
<script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js"></script>
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
			<div class="col-md-12 text-center" >
					<h2 class="text-center"> <?php echo $titulo;?></h2>
			</div>
			<div class="col-md-12" >
				<div class="col-md-3 " >
					<a href="<?php echo $HostNombre.'/modulos/mod_proveedor/proveedor.php?id='.$id;?>">Volver Atrás</a>
					<a  class="btn btn-primary"  onclick="imprimirResumen('albaran', '<?php echo $id; ?>', '<?php echo $fechaInicial;?>', '<?php echo $fechaFinal;?>')">Imprimir resumen</a>
					<h4><u>DATOS DEL PROVEEDOR</u></h4>
					<b>ID: </b><?php echo $id;?></br>
					<b>Nombre: </b><?php echo $datosProveedor['datos'][0]['nombrecomercial'];?></br>
					<b>Razón social: </b><?php echo $datosProveedor['datos'][0]['razonsocial'];?></br>
					<b>NIF:</b><?php echo $datosProveedor['datos'][0]['nif'];?></br>
				</div>
				<div class="col-md-4" >
					<form method="post">
					<label>Fecha Inicial</label>
					<input type="date" id="fechaInicial" name="fechaInicial" value="<?php echo $fechaInicial;?>" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
					<label>Fecha Final</label>
					<input type="date" id="fechaFinal" name="fechaFinal" value="<?php echo $fechaFinal;?>" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
					<br><br>
					<input type="submit" name="porfechas" class="btn btn-info" value="Resumen fechas">
					<input type="submit" name="portodo"class="btn btn-warning"  value="Todo">
					
					</form>
				</div>
				<div class="col-md-5 " <?php echo $style;?>>
					<h4 class="text-center" ><u>TOTALES</u></h4>
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th></th>
								<th>BASE</th>
								<th>IVA</th>
								<th>TOTAL</th>
							</tr>
						</thead>
						<tbody>
						<?php 
						$totalLinea=0;
						$totalDesglose=0;
						if(isset($arrayNums['desglose'])){
							foreach($arrayNums['desglose'] as $desglose){
								$totalLinea=$desglose['sumBase']+$desglose['sumiva'];
								$totalDesglose=$totalDesglose+$totalLinea;
								echo '<tr>
									<td>'.$desglose['iva'].'%</td>
									<td>'.$desglose['sumBase'].'</td>
									<td>'.$desglose['sumiva'].'</td>
									<td>'.$totalLinea.'</td>
								</tr>';
							}
						}
						
						?>
						</tbody>
					</table>
					<div class="col-md-12">
						<div class="col-md-5">
						</div>
						<div class="col-md-7">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL: <?php echo $totalDesglose;?></h3>
								</div>
							</div>
						</div>
					</div>
				
				</div>
			</div>
			
			
				
			<div class="col-md-6"   <?php echo $style;?>>
				<h4 class="text-center" ><u>RESUMEN PRODUCTOS</u></h4>
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>PRODUCTO</th>
								<th>CANTIDAD</th>
								<th>COSTE</th>
								<th>IMPORTE</th>
							</tr>
						</thead>
						<tbody>
					<?php 
						$totalProductos=0;
						if(isset($arrayNums['productos'])){
                            foreach ($arrayNums['productos'] as $key => $row) {
                                    $aux[$key] = $row['cdetalle'];
                                }
                                array_multisort($aux, SORT_ASC, $arrayNums['productos']);
							foreach($arrayNums['productos'] as $producto){
								$precio=$producto['totalUnidades']*$producto['costeSiva'];
								echo '<tr>'
								. '<td>'.$producto['cdetalle'].'</td>'
								.'<td>'. number_format ($producto['totalUnidades'],2).'</td>'
								.'<td>'.number_format ($producto['costeSiva'],2).'</td>'
								. '<td>'.number_format ($precio,2).'</td>'
								. '</tr>';
								$totalProductos=$totalProductos+number_format ($precio,2);
							}
						}
						?>
						</tbody>
					</table>
					<div class="col-md-12">
						<div class="col-md-7">
						</div>
						<div class="col-md-5">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL: <?php echo $totalProductos;?></h3>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-6 "   <?php echo $style;?>>
					<h4 class="text-center" ><u>ALBARANES</u></h4>
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>FECHA</th>
								<th>ALBARÁN</th>
								<th>BASE</th>
								<th>IVA</th>
								<th>TOTAL</th>
							</tr>
						</thead>
						<tbody>
						<?php 
						$totalLinea=0;
						$totalbases=0;
						if(isset($arrayNums['resumenBases'])){
							foreach($arrayNums['resumenBases'] as $bases){
								$totalLinea=$bases['sumabase']+$bases['sumarIva'];
								$totalbases=$totalbases+$totalLinea;
								echo '<tr>
								<td>'.$bases['fecha'].'</td>
								<td>'.$bases['Numalbpro'].'</td>
								<td>'.$bases['sumabase'].'</td>
								<td>'.$bases['sumarIva'].'</td>
								<td>'.$totalLinea.'</td>
								</tr>';
							}
						}
						?>
						
						</tbody>
					</table>
					<div class="col-md-12" >
						<div class="col-md-5">
						</div>
						<div class="col-md-7">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL: <?php echo $totalbases;?></h3>
								</div>
							</div>
						</div>
					</div>
					
				</div>
			</div>
		</div>
	</body>
</html>
