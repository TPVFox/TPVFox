<!DOCTYPE html>
<html>
    <head>
		 <?php
        include './../../../head.php';
         include './../funciones.php';
        include ("./../../../controllers/Controladores.php");
        include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
        $ClasesParametros = new ClaseParametros('../parametros.xml');  
        include_once ('../clases/ClaseProveedor.php');
		$CProveedor= new ClaseProveedor($BDTpv);
		if(isset($_GET['id'])){
			$id=$_GET['id'];
			$datosProveedor=$CProveedor->getProveedor($id);
			echo '<pre>';
			print_r($datosProveedor);
			echo '</pre>';
		}else{
			$errores[1]=array ( 'tipo'=>'DANGER!',
								 'dato' => '',
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error no se ha enviado el id del proveedor'
								 );
		}
		
		
		?>
	</head>
	<body>

	<?php
        include './../../../header.php';
       
				
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
					<a  onclick="imprimirResumen('ticket', '<?php echo $id; ?>', '<?php echo $fechaInicial;?>', '<?php echo $fechaFinal;?>')">Imprimir resumen</a>
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
						
						</tbody>
					</table>
					<div class="col-md-12">
						<div class="col-md-5">
						</div>
						<div class="col-md-7">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL:</h3>
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
								<th>PRECIO</th>
								<th>IMPORTE</th>
							</tr>
						</thead>
						<tbody>
					
						</tbody>
					</table>
					<div class="col-md-12">
						<div class="col-md-7">
						</div>
						<div class="col-md-5">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL: </h3>
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
								<th>FACTURA</th>
								<th>BASE</th>
								<th>IVA</th>
								<th>TOTAL</th>
							</tr>
						</thead>
						<tbody>
						
						
						</tbody>
					</table>
					<div class="col-md-12" >
						<div class="col-md-5">
						</div>
						<div class="col-md-7">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL: </h3>
								</div>
							</div>
						</div>
					</div>
					
				</div>
			</div>
		</div>
	</body>
</html>
