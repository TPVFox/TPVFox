
<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
    include $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_venta/funciones.php';
	include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once $URLCom.'/clases/cliente.php';
    include_once $URLCom.'/modulos/mod_venta/clases/albaranesVentas.php';
	$Ccliente=new Cliente($BDTpv);
	$Calbaran=new AlbaranesVentas($BDTpv);
	$Controler = new ControladorComun; 
	$todosTemporal=$Calbaran->TodosTemporal();
	if (isset($todosTemporal['error'])){
		$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $todosTemporal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
	}
	$todosTemporal=array_reverse($todosTemporal);
	// ===========    Paginacion  ====================== //
	$NPaginado = new PluginClasePaginacion(__FILE__);
	$campos = array( 'a.Numalbcli','b.Nombre');

	$NPaginado->SetCamposControler($Controler,$campos);
	$NPaginado->SetOrderConsulta('a.Numalbcli');
	// --- Ahora contamos registro que hay para es filtro --- //
	$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND

	$CantidadRegistros=0;
	// Obtenemos la cantidad registros 
	$a= $Calbaran->TodosAlbaranesFiltro($filtro);
		
	$CantidadRegistros = count($a['Items']);
	
	// --- Ahora envio a NPaginado la cantidad registros --- //
	$NPaginado->SetCantidadRegistros($CantidadRegistros);
	$htmlPG = $NPaginado->htmlPaginado();
	//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
	$a=$Calbaran->TodosAlbaranesFiltro($filtro.$NPaginado->GetLimitConsulta());
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
					<h2>Ventas: Editar y Añadir albaranes </h2>
				</div>
					<nav class="col-sm-4">
				<h4> Albaranes </h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					 if($ClasePermisos->getAccion("Crear")==1){
                         echo '<li><a href="#section2" onclick="metodoClick('."'".'AgregarAlbaran'."'".', '."'".'albaran'."'".');";>Añadir</a></li>';
                    }
                    if($ClasePermisos->getAccion("Modificar")==1){
                        echo '<li><a href="#section2" onclick="metodoClick('."'".'Ver'."'".','."'".'albaran'."'".');";>Modificar</a></li>';
                    }
					?>
				</ul>	
					<div class="col-md-12">
		<h4 class="text-center"> Albaranes Abiertos</h4>
		<table class="table table-striped table-hover">
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
						<th></th>
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
						$c = 0;
						foreach ($albaranesDef as $albaran){
                            $c = $c+1;
							$checkUser = '<input class="check_albaran" type="checkbox" name="checkUsu'.$c
                                        .'" value="'.$albaran['id'].'" id="checkUsu'.$c.'">';
							$totaliva=$Calbaran->sumarIva($albaran['Numalbcli']);
							$date=date_create($albaran['Fecha']);
						?>
						<tr>
						<td class="rowUsuario">
                            <?php echo $checkUser;?>
                        <td>
                            <?php 
                             if($ClasePermisos->getAccion("Modificar")==1){
                            ?>
                                <a class="glyphicon glyphicon-pencil" href='./albaran.php?id=<?php echo $albaran['id'];?>'>
                            <?php 
                            }
                            ?>
                        </td>
                        <td>
                               <?php 
                             if($ClasePermisos->getAccion("Ver")==1){
                            ?>
                            <a class="glyphicon glyphicon-eye-open" href='./albaran.php?id=<?php echo $albaran['id'];?>&estado=ver'>
                            <?php 
                            }
                            ?>
                        </td>
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
							$onclick=" onclick='imprimir(".$albaran['id'].',"albaran",'.json_encode($_SESSION['tiendaTpv']).")'";
							
							?>
						<td><?php echo $albaran['estado'];?>  <a class="glyphicon glyphicon-print" <?php echo $onclick;?> ></a>
                        </td>

							
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
