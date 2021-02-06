<!DOCTYPE html>
<html>
    <head>
		 <?php
		include_once './../../../inicial.php';
		include $URLCom.'/head.php';
		include $URLCom.'/modulos/mod_cliente/funciones.php';
		include $URLCom.'/controllers/Controladores.php';
        include_once ($URLCom.'/controllers/parametros.php');
        include_once $URLCom.'/modulos/mod_cliente/clases/ClaseCliente.php';

        $ClasesParametros = new ClaseParametros('../parametros.xml'); 
		$Cliente= new ClaseCliente($BDTpv);
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$errores=array();
		$titulo="Resumen tickets";
		$fechaInicial="";
		$fechaFinal="";
        $redirect='';
		if(isset($_GET['id'])){
			//Se cargar los datos del cliente, se controlan los errores de que no reciba id de cliente 
			//y si hay error en la consulta 
			$id=$_GET['id'];
			$datosCliente=$Cliente->getCliente($id);
			if(isset($datosCliente['error'])){
                $errores[] = $Cliente->montarAdvertencia('danger','Error en sql:'.$datosCliente['consulta']);
			} 
		}else{
            $errores[] = $Cliente->montarAdvertencia('danger','Error no se ha enviado el id del cliente');
		}
        if (count($_POST) > 0){
            // Ya se envio post
            $redirect ='Location: resumenTickets.php?hacerResumen=Si&id='.$id; 
            if(isset($_POST['porfechas'])){
                //Cuando se envia por fechas se comprueba que las fechas estan bien escritas 
                $comprobarFechas=comprobarFechas($_POST['fechaInicial'], $_POST['fechaFinal']);
                $redirect .='&fechaIni='.$comprobarFechas['fechaIni'].'&fechaFin='.$comprobarFechas['fechaFin']; 
                if(isset($comprobarFechas['error'])){
                    $errores[] = $Cliente->montarAdvertencia('warning',$comprobarFechas['consulta']);
                }
            }
            if(isset($_POST['portodo'])){
                //Si buscamos todo recarga la página sin fechas
                 $redirect .='&todo=Si';
            }
            if (isset($_POST['mes_anterior'])){
                $redirect .='&mes_anterior=Si';
            }
            if (count($errores) === 0 ){
                // No hay errores, por lo que redireccionamos.
                header($redirect);
            }
        }
		if(isset($_GET['hacerResumen'])){
            if (isset($_GET['todo'])){
                // Todo , quiere decir ese ejercicio. (desde 01/01 de este año)
				$fechaInicial = date('Y').'-01-01';
                $fechaFinal   = date('Y-d-m');
            } else {
                if (isset($_GET['mes_anterior'])){
                    $m= date('m')-1; // Numero mes anterior
                    if ($m == 0) {
                        $m= 1;
                    }
                    $fin_mes = cal_days_in_month(CAL_GREGORIAN, $m,2020);
                    $f= date_create('2020-'.$m.'-'.$fin_mes);
                    $fechaFinal= date_format($f, 'Y-m-d');
                    $f= date_create('2020-'.$m.'-'.'01');
                    $fechaInicial= date_format($f, 'Y-m-d');
                } else {
                    $fechaInicial =$_GET['fechaIni'];
                    $fechaFinal =$_GET['fechaFin'];
                }
            }
			//Queremos que haga el resumen
            $arrayNums=$Cliente->ticketClienteFechas($id, $fechaInicial, $fechaFinal);
            if(isset($arrayNums['error'])){
                $errores[] = $Cliente->montarAdvertencia('danger','Error en sql:'.$arrayNums['consulta']);
			}

		}
		?>
	</head>
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
		<?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
        if (count($errores)>0 ){
            foreach($errores as $error){
                echo '<div class="alert alert-' . $error['tipo'] . '">' . $error['mensaje'] . '</div>';
                if ($error['tipo'] === 'danger') {
                    // No permito continuar.
                    exit();
                }
            }
        }
        ?>
		
		<div class="container">
			<div class="col-md-12 text-center" >
					<h2 class="text-center"> <?php echo $titulo;?></h2>
			</div>
		
			<div class="col-md-12" >
				<div class="col-md-3 " >
					<a href="<?php echo $HostNombre.'/modulos/mod_cliente/cliente.php?id='.$id;?>">Volver Atrás</a>
					<a class="btn btn-primary" onclick="imprimirResumen('ticket', '<?php echo $id; ?>', '<?php echo $fechaInicial;?>', '<?php echo $fechaFinal;?>')">Imprimir resumen</a>
					<h4><u>DATOS DEL CLIENTE</u></h4>
					<b>ID: </b><?php echo $id;?></br>
					<b>Nombre: </b><?php echo $datosCliente['datos'][0]['Nombre'];?></br>
					<b>Razón social: </b><?php echo $datosCliente['datos'][0]['razonsocial'];?></br>
					<b>NIF:</b><?php echo $datosCliente['datos'][0]['nif'];?></br>
				</div>
				<div class="col-md-4" >
					<form method="post">
                        <input type="submit" name="portodo"class="btn btn-warning"  value="Este año">
                        <input type="submit" name="mes_anterior"class="btn btn-warning"  value="Mes anterior">
                        <label>Fecha Inicial</label>
                        <input type="date" id="fechaInicial" name="fechaInicial" value="<?php echo $fechaInicial;?>" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
                        <label>Fecha Final</label>
                        <input type="date" id="fechaFinal" name="fechaFinal" value="<?php echo $fechaFinal;?>" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
                        <br><br>
                        <input type="submit" name="porfechas" class="btn btn-info" value="Resumen fechas">
					</form>
				</div>
                <?php
                if(isset($_GET['hacerResumen'])){?>

				<div class="col-md-5">
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
                <?php
                }
                ?>
			</div>
			
			
            <?php
            if(isset($_GET['hacerResumen']))
            {?>	
			<div class="col-md-8">
				<h4 class="text-center" ><u>RESUMEN PRODUCTOS</u></h4>
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
                                <th>ID</th>

                                <th>CODBARRAS</th>
                                <th>PRODUCTO</th>
								<th>CANTIDAD</th>
								<th>PRECIO</th>
								<th>IMPORTE</th>
							</tr>
						</thead>
						<tbody>
						<?php 
                        $lineas = getHmtlTrProductos($arrayNums['productos'],'pantalla');
                        echo $lineas['html'];
						?>
						</tbody>
					</table>
					<div class="col-md-12">
						<div class="col-md-7">
						</div>
						<div class="col-md-5">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL: <?php echo number_format($lineas['totalLineas'],2);?></h3>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-4 ">
					<h4 class="text-center" ><u>TICKETS</u></h4>
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
						<?php 
						$totalLinea=0;
						$totalbases=0;
						if(isset($arrayNums['resumenBases'])){
							foreach($arrayNums['resumenBases'] as $bases){
								$totalLinea=$bases['sumabase']+$bases['sumarIva'];
								$totalbases=$totalbases+$totalLinea;
								$numTicket=$bases['idTienda'].'-'.$bases['idUsuario'].'-'.$bases['Numticket'];
								echo '<tr>
								<td>'.$bases['fecha'].'</td>
								<td>'.$numTicket.'</td>
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
            <?php
            }
            ?>
		</div>
	</body>
</html>
