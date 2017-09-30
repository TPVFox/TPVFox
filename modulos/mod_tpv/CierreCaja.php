<!DOCTYPE html>
<html>
    <head>
        <?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	
	//recoger datos... variables del html
	
	$FechaInicial = 'fecha inicial'; //recoger fecha inicial de la caja
	$FechaFinal = 'fecha final'; //recoger fecha final de la caja
	$usuario['id'] = '1';
	$usuario['Nombre'] = 'admin';
	$usuario['totalTickets'] = '30'; 
	$usuario['NumticketInic']= '1'; 
	$usuario['NumticketFinal']= '30'; 
	$efectivo = 'contado'; //mysql contado o tarjeta
	$tarjeta = '';
	$totalCaja = '';

	$base4 = '';
	$base10 = '';
	$base21 = '';
	$totalBase = '';
	
	
	$iva4 = '';
	$iva10 = '';
	$iva21 = '';
	$totalIva ='';
	//fecha para obtener caja de ese dia
	if ($_POST['fecha']){
		$fecha=$_POST['fecha'];
		$caja = ticketsPorFecha($fecha,$BDTpv);
	}

	echo '<pre>';
	print_r($caja);
	echo '</pre>';
	?>
	
	<script>
	// Declaramos variables globales
	</script> 
    <!-- Cargamos fuciones de modulo.
    Cargamos JS del modulo de productos para no repetir funciones: BuscarProducto, metodoClick (pulsado, adonde)
    caja de busqueda en listado 
     -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
    
    <!-- Cargamos libreria control de teclado -->
	<script src="<?php echo $HostNombre; ?>/lib/shortcut.js"></script>
  
	
	<script>
	// Funciones para atajo de teclado.
	//~ shortcut.add("Shift+V",function() {
		//~ // Atajo de teclado para ver
		//~ metodoClick('VerUsuario');
	//~ });    
	    
	</script> 
    </head>

<body>
        <?php
        include './../../header.php';
        ?>
        <?php
				//~ echo '<pre>';
					//~ print_r($tickets);
				//~ echo '</pre>';
		?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Total Caja </h2>
				</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<nav class="col-sm-2" id="myScrollspy">
				<div data-spy="affix" data-offset-top="505">
				<h4> Cierre Caja</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
									
					<li><a href="#section2" >Aceptar</a></li>
					<li><a href="#section2" >Cancelar</a></li>
					<li><a href="#section2" >Fechas</a></li>
				<?php //	 <li><a href="#section2" onclick="metodoClick('VerProducto','ticket');";>Aceptar</a></li>
				?><?php		//metodoClick js case pulsado 
								//agregarUsuario nos lleva a formulario usuario
								//verUsuario si esta checkado nos lleva vista usuario de ese id
											//si NO nos indica que tenemos que elegir uno de la lista ?>
				</ul>
				</div>	
			</nav>
			<div class="col-md-10">
				<div class=" form-group">
					
					<form action="./CierreCaja.php" method="post"> <label class="control-label col-sm-2" > Fecha Caja:</label>
					<div class="col-sm-10"> 
						<input type="date" name="fecha" autofocus placeholder="2017-09-30" value=<?php echo (!isset($_POST['fecha']) ? $_POST['fecha'] : $_POST['fecha']); ?>>
						<input type="submit" value="Consulta caja">
					</div>
					</form>
				</div>
			<div> 
				<!-- TABLA USUARIOS -->
			<div class="col-md-10 text-center">
				<h3> Usuario por Usuario </h3>
			</div>
			<table class="table table-striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>NOMBRE USUARIO</th>
						<th>TOTAL</th>
						<th>Nº TICKET INICIAL</th>
						<th>Nº TICKET FINAL</th>
					</tr>
				</thead>
				<tr>
					<td><?php echo $usuario['id']; ?></td>
					<td><?php echo $usuario['Nombre']; ?></td>
					<td><?php echo $usuario['totalTickets']; ?></td>
					<td><?php echo $usuario['NumticketInic']; ?></td>
					<td><?php echo $usuario['NumticketFinal']; ?></td>
				</tr>
			</table>
			<div class="row">
				<!-- Cobrado por -->
				<div class="col-md-4">
					<h3 class="text-left"> Cobrado por: </h3>
					<div class="form-group">
						<label class="control-label col-sm-2" >Efectivo:</label>
						<div class="col-sm-10"> 
							<input type="text" id="efectivo" name="efectivo" value="<?php echo $efectivo;?>" disabled>
						</div>
						<label class="control-label col-sm-2">Tarjeta:</label>
						<div class="col-sm-10"> 
							<input type="text" id="tarjeta" name="tarjeta" value="<?php echo $tarjeta;?>" disabled>
						</div>
					</div>
				</div>
					
				
					<!-- BASES -->
				<div class="col-md-4">
					<h3 class="text-left"> Bases: </h3>
					<div class=" form-group">
						<label class="control-label col-sm-2" >4%:</label>
						<div class="col-sm-10"> 
							<input type="text" id="base4" name="base4" value="<?php echo $base4;?>" disabled>
						</div>
						<label class="control-label col-sm-2">10%:</label>
						<div class="col-sm-10"> 
							<input type="text" id="base10" name="base10" value="<?php echo $base10;?>" disabled>
						</div>
						<label class="control-label col-sm-2">21%:</label>
						<div class="col-sm-10"> 
							<input type="text" id="base21" name="base21" value="<?php echo $base21;?>" disabled>
						</div>
						<label class="control-label col-sm-2">Total:</label>
						<div class="col-sm-10"> 
							<input type="text" id="totalBase" name="totalBase" value="<?php echo $totalBase;?>" disabled>
						</div>
					</div>
					
				</div>
					<!-- IVAS -->
				<div class="col-md-4">
					<h3 class="text-left"> Ivas: </h3>
					<div class=" form-group">
						<label class="control-label col-sm-2" >4%:</label>
						<div class="col-sm-10"> 
							<input type="text" id="iva4" name="iva4" value="<?php echo $iva4;?>" disabled>
						</div>
						<label class="control-label col-sm-2">10%:</label>
						<div class="col-sm-10"> 
							<input type="text" id="iva10" name="iva10" value="<?php echo $iva10;?>" disabled>
						</div>
						<label class="control-label col-sm-2">21%:</label>
						<div class="col-sm-10"> 
							<input type="text" id="iva21" name="iva21" value="<?php echo $iva21;?>" disabled>
						</div>
						<label class="control-label col-sm-2">Total:</label>
						<div class="col-sm-10"> 
							<input type="text" id="totalIva" name="totalIva" value="<?php echo $totalIva;?>" disabled>
						</div>
					</div>
				<div> 
					<!-- Fin IVAS -->
			
			</div> 
					
		
				
			</div> 	
				<label class="control-label col-sm-2">Total Caja:</label>
					<div class="col-sm-10"> 
						<input type="text" id="totalCaja" name="totalCaja" value="<?php echo $totalCaja;?>" disabled>
					</div>
			</div>
			<!-- fin row -->
		</div>
	</div>
    </div>
		
</body>
</html>
