<!DOCTYPE html>
<html>
    <head>
        <?php
    include_once './../../inicial.php';
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_cliente/funciones.php';
    include_once $URLCom.'/modulos/mod_cliente/clases/ClaseCliente.php';
    include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';

	$Cliente=new ClaseCliente();
    // --- Inicializamos objeto de Paginado --- //
    $NPaginado = new PluginClasePaginacion(__FILE__);
    $campos = array('razonsocial','Nombre','nif');
    $NPaginado->SetCamposControler($campos);
    $filtro = $NPaginado->GetFiltroWhere('OR');
    
    // --- Ahora contamos registro que hay para es filtro y enviamos clase paginado --- //
    $NPaginado->SetCantidadRegistros($Cliente->contarRegistros($filtro));
    $htmlPG = $NPaginado->htmlPaginado(); // Montamos html Paginado
    // Obtenemos clientes con filtro busqueda y la pagina que estamos.	
	$clientes = $Cliente->obtenerClientes($filtro . $NPaginado->GetLimitConsulta());
	
	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
  
    </head>

<body>
        <?php
       include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Clientes: Editar y Añadir Clientes </h2>
					<?php 
					
					?>
				</div>
	       
			<nav class="col-sm-2" id="myScrollspy">
				<div data-offset-top="505">
				<h4> Clientes</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
                    <?php
                     if($ClasePermisos->getAccion("crear")==1){
                    ?>
					<li><a href="#section1" onclick="metodoClick('AgregarCliente');">Añadir</a></li>
                    <?php 
                    }                    
                    if($ClasePermisos->getAccion("modificar")==1){
                        ?>
					<li><a href="#section2" onclick="metodoClick('VerCliente');">Modificar</a></li>
                    <?php 
                }
                if($ClasePermisos->getAccion("tarifa")==1){
                    ?>
					<li><a href="#" onclick="metodoClick('TarificarCliente');">Tarifa</a></li>
                    <?php 
                }
                    ?>
									<?php //metodoClick js case pulsado 
									//agregarUsuario nos lleva a formulario usuario
									//verUsuario si esta checkado nos lleva vista usuario de ese id
									//si NO nos indica que tenemos que elegir uno de la lista 
									$fecha = date_create(date('Y-m-01'));
									date_sub($fecha, date_interval_create_from_date_string('1 month'));
									$fechainicio = date_format($fecha, 'Y-m-d');
									
									$fecha = date_create(date('Y-m').'-01');
									date_sub($fecha, date_interval_create_from_date_string('1 day'));
									$fechafin = date_format($fecha, 'Y-m-d 23:59:59');

									$titulo = "Descuentos de tickets de cliente";
									$contenido = 'Mes de '.date_format($fecha, 'F Y').'<br/><br/> Intervalo de fechas:'.
									date_format(date_create($fechainicio),'d-m-Y').' a '.date_format(date_create($fechafin), 'd-m-Y');									
									?>

				<li><a href="#" onclick="imprimirFicha('0');">Imprimir ficha</a></li>
				<?php if($ClasePermisos->getAccion("descuento_ticket")==1){				?>
				<li><a href="#" 
				onclick="abrirModalInforme('<?php echo $titulo ?>', '<?php echo $contenido ?>', '<?php echo $fechainicio ?>', '<?php echo $fechafin ?>')">Informe mensual descuentos tickets</a></li>
				<?php } ?>
				<?php if($ClasePermisos->getAccion("descuento_ticket_update")==1){				?>
				<li><a href="#" 
				onclick="abrirModalInforme('<?php echo $titulo ?>', '<?php echo $contenido ?>', 
				'<?php echo $fechainicio ?>', '<?php echo $fechafin ?>' , 1)">Actualizar Informe descuentos tickets</a></li>
				<?php } ?>
				</ul>
				</div>	
			</nav>		
			<div class="col-md-10">
					<p>
					 -Clientes encontrados BD local filtrados:
						<?php echo $Cliente->contarRegistros($filtro);?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
				<form action="./ListaClientes.php" method="GET" name="formBuscar">
					<div class="form-group ClaseBuscar">
						<label>Buscar en nombre, razon social o nif: </label>
						<input type="text" name="buscar" value="">
						<input type="submit" value="buscar">
					</div>
				</form>
                 <!-- TABLA DE PRODUCTOS -->
			<div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th></th>
                        <th></th>
                        <th></th>
						<th>ID</th>
						<th>NOMBRE</th>
						<th>RAZON SOCIAL</th>
						<th>NIF</th>
						<th>TELEFONO</th>
                        <th>MOVIL</th>
						<th>EMAIL</th>
						<th>ESTADO</th>
						<th>IMPRIMIR</th>

					</tr>
				</thead>	
				<?php
				$checkUser = 0;
				foreach ($clientes as $cliente){ 
					$checkUser = $checkUser + 1;
                    if (trim($cliente['movil']) !==''){
                        $cliente['movil'] = $cliente['movil'].'<a href="https://web.whatsapp.com/send?phone=34+'.$cliente['movil'].'">'
                                        .'<span class="glyphicon glyphicon-comment"></span>'
                                        .'</a>';
                    }
                    // Mostrar email
                    
                    if ($cliente['email']<>''){
                        $email ='<a href="mailto:'.$cliente['email'].'"><span class="glyphicon glyphicon-envelope"></span><a>';
                    } else {
                        $email = '';
                    }
					?>
					<tr>
						<td class="rowUsuario">
							<input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $cliente['idClientes'];?>">
						</td>
                        <td>
                        <?php 
                        if($ClasePermisos->getAccion("modificar")==1){
                        ?>
                            <a class="glyphicon glyphicon-pencil" href='./cliente.php?id=<?php echo $cliente['idClientes'].'&accion=editar';?>'>
                        <?php 
                        }
                        ?>
                        </td>
                        <td>
                        <?php 
                        if($ClasePermisos->getAccion("ver")==1){
                        ?>
                        <a class="glyphicon glyphicon-eye-open" href='./cliente.php?id=<?php echo $cliente['idClientes'];?>&accion=ver'>
                        <?php 
                        }
                        ?>
                        </td>
						<td><?php echo $cliente['idClientes']; ?></td>
						<td><?php echo $cliente['Nombre']; ?></td>
						<td><?php echo $cliente['razonsocial']; ?></td>
						<td><?php echo $cliente['nif']; ?></td>
						<td><?php echo $cliente['telefono']; ?></td>
                        <td><?php echo $cliente['movil']; ?></td>
                            
                        <td><?php echo $email; ?></td>
						<td><?php echo $cliente['estado']; ?></td>
						<?php
                            $linkImprimir= '&nbsp;<a style="cursor:pointer" class="glyphicon glyphicon-print" '."onclick='imprimirFicha(".$cliente['idClientes'].")'></a>";
                        ?>
                        <td><?php echo $linkImprimir;?>  
					</tr>
					<?php 
				} //fin de foreach ckeckUser
				?>				
			</table>
			</div>
		</div>
	</div>
    </div>
	<?php 
    include_once $URLCom.'/pie.php';
	echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
	include $RutaServidor.'/'.$HostNombre.'/plugins/modal/ventanaModal.php';

    ?>		
</body>
</html>
