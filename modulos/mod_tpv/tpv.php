<?php
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	Importar ficheros de DBF
 *  */
		// Objetivo de esta aplicacion es:
		//	- Copiar DBF y guardar en directorio de copias de seguridad.
		// 	- Importar los datos copiados a MYSQL.

?>

<!DOCTYPE html>
<html>
<head>
<?php
	include './../../head.php';
	// Tengo que cargar antes el idTienda..
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];
	$ticket_estado = 'Nuevo';
	$ticket_numero = 0;
	$fechaInicio = date("d/m/Y");
	if (isset($_GET['tAbierto'])) {
		$ticket_numero = $_GET['tAbierto'];
	}

?>
<?php 
	// [PENDIENTE ERROR EN SIGUIENTE <SCRIPT JAVASCRIPT>]
	// Si no esta logueado genera un error console javascript ;
	// ERROR -> SyntaxError: expected expression, got ';

?>
<script type="text/javascript">
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
	var CONF_campoPeso="<?php echo $CONF_campoPeso; ?>";
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idCliente'] = 1; // Este dato puede cambiar
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['estadoTicket'] ="<?php echo $ticket_estado ;?>"; // Si no hay datos GET es 'Nuevo';
		cabecera['numTicket'] = <?php echo $ticket_numero ;?>; // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array

</script>


<script src="<?php echo $HostNombre; ?>/modulos/mod_tpv/funciones.js"></script>

<script src="<?php echo $HostNombre; ?>/modulos/mod_tpv/calculador.js"></script>

</head>
<!--
onBeforeUnload="return preguntarAntesDeSalir()"
-->
<body>
<?php

	include '../../header.php';
	include_once ("funciones.php");
	// Ahora obtenemos los tickets abiertos.
	// Convertiendo todos los tickets actual en abiertos de este usuario y tienda.
	$cambiosEstadoTickets = ControlEstadoTicketsAbierto($BDTpv,$Usuario['id'],$Tienda['idTienda']);
	// Ahora obtenemos la cabecera de los ticket abiertos de ese usuario.
	$ticketsAbiertos = ObtenerCabeceraTicketAbierto($BDTpv,$Usuario['id'],$Tienda['idTienda'],$ticket_numero);
	// Ahora si tenemos numero ticket -> que viene por get Obtenemos datos Ticket
	if ($ticket_numero > 0){
		//Obtenemos datos del ticket 
		$ticket= ObtenerUnTicket($BDTpv,$Tienda['idTienda'],$Usuario['id'],$ticket_numero);
		$ticket_estado = $ticket['estadoTicket'];
	}
	if ((isset($cambiosEstadoTickets['error'])) || (isset($ticket['error']))) {
		// Entonces obtenemos las caberas para mostrar.
		echo '<pre>';
		print ( 'HUBO UN ERROR ');
		if (isset($cambiosEstadoTickets['error'])) {
			echo 'Error en cambio Estado'; print_r($cambiosEstadoTickets);
		}
		if (isset($ticket['error'])) { 
			echo 'Error en al Obtener ticket'; print_r($ticket['error']);
		}
		echo '</pre>';
		exit(); // NO continuamos.
	}
	
	if ($ticket_numero > 0){
		// Si estamos en un ticket abierto.
		// Ahora ponemos fecha Inicio
		$fechaInicio = MaquetarFecha ($ticket['fechaInicio'],'dmy');
		$fechaFinal = MaquetarFecha ($ticket['fechaFinal'],'dmy');
		$horaInicio= MaquetarFecha($ticket['fechaInicio'],'HM');
		$horaFinal= MaquetarFecha($ticket['fechaFinal'],'HM');
		$cliente = $ticket['Nombre'].'-'.$ticket['razonsocial'];
		$idCliente =$ticket['idClientes'];
	} else {
		//~ $horaInicio= MaquetarFecha($fechaInicio,'HM'); // Falla no se porque... :-)
		$cliente = '';
		$idCliente =1;
	}
	
	if(isset($ticket['productos'])){
			// Obtenemos los datos totales ( fin de ticket);
			// convertimos el objeto productos en array
			$productos = json_decode( json_encode( $ticket['productos'] ), true );
			$Datostotales = recalculoTotales($ticket['productos']);	
	}
	
	//~ echo '<pre>';
	//~ print_r($ticket);
	//~ echo '</pre>';

?>

<?php if (isset($ticket)){
	// Solo cargamos estas lineas javascript si es un ticket Abierto
 ?>
	<script type="text/javascript">
	cabecera['idCliente'] = <?php echo $idCliente;?>;
	datos = [];
	<?php
	$i= 0;
	foreach($ticket['productos'] as $product){
	?>
		// Añadimos datos de productos a variable productos Javascript
		datos['idArticulo'] 	= <?php echo $product->id;?>;
		datos['crefTienda'] 	= <?php echo '"'.$product->cref.'"';?>;
		datos['articulo_name'] 	= <?php echo '"'.$product->cdetalle.'"';?>;
		datos['pvpCiva'] 		= <?php echo $product->pvpconiva;?>;
		datos['iva'] 			= <?php echo $product->ctipoiva;?>;
		datos['codBarras']		= <?php echo '"'.$product->ccodebar.'"';?>;
		productos.push(new ObjProducto(datos));
		<?php
		// cambiamos estado y cantidad de producto creado si fuera necesario.
		if ($product->estado != 'Activo'){
		?>	productos[<?php echo $i;?>].estado=<?php echo'"'.$product->estado.'"';?>;
		<?php
		}
		if ($product->unidad != 1){
		?>	productos[<?php echo $i;?>].unidad=<?php echo $product->unidad;?>;
		<?php
		}
	}
	?>

	</script>
<?php } ?>
<div class="container">
<nav class="col-md-3">
	<div class="col-md-6">
		<h3 class="text-center"> TpvFox</h3>
		<h4>Otros opciones</h4>
		<ul class="nav nav-pills nav-stacked">
			<li><a href="tpv.php">Nuevo ticket</a></li>
			<li><a href="CierreCaja.php">Cierre Caja</a></li>
			<li><a href="ListaTickets.php">Tickets Cerrados</a></li>
		</ul>
	</div>
	<div class="col-md-6">
		<h3 class="text-center"> Tickets</h3>
		<h4>Este ticket</h4>
		<ul class="nav nav-pills nav-stacked">
			<li><a onclick="buscarClientes()">Cliente</a></li>
			<li><a href="#section3">Abrir Cajon</a></li>
			<li><a onclick="cobrarF5()">Cobrar</a></li>

		</ul>
	</div>
	<div>
	<?php if (isset($ticketsAbiertos['items'])){
	?>
	<h3 class="text-center"> Tickets Abiertos</h3>
	<table class="table table-striped">
		<thead>
			<tr>
				<th>Nº</th>
				<th>Cliente</th>
				<th>Total</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($ticketsAbiertos['items'] as $item){?>
			<tr>
				<td><a href="tpv.php?tAbierto=<?php echo $item['numticket']; ?>">
				<?php echo $item['numticket']; ?>
				</a>
				</td>
				<td>
				<?php echo $item['Nombre']; ?><br/>
				<small><?php echo $item['razonsocial']; ?></small>
				</td>
				<td class="text-right">
				<?php echo number_format ($item['total'],2); ?>
				</td>
			</tr>
			<?php
			// Cerramos foreach
			}
			 ?>
		</tbody>
	</table>
	</div>
	<?php
	// Cerramos if de mostrar tickets abiertos o no.
	}
	?>
</nav>
<div class="col-md-9" >
	<div class="col-md-8">
		<div class="col-md-12">
			<div class="col-md-7">
				<div class="col-md-6">
					<strong>Fecha Inicio:</strong><br/>
					<span id="Fecha"><?php echo $fechaInicio;?></span><br/>
					<?php // NO se muestra si es un ticket nuevo
					if ( $ticket_numero != 0){
					?>
					<div style="background-color:#f9f3f3;">
					<strong>Hora Inicio:</strong>
					<span id="HoraInicio"><?php echo $horaInicio;?></span><br/>
					<strong>Fecha Final:</strong><br/>
					<span id="FechaFinal"><?php echo $fechaFinal;?></span><br/>
					<strong>Hora Inicio:</strong>
					<span id="HoraFinal"><?php echo $horaFinal;?></span>
					</div>
					<?php 
					}
					?>
				</div>
				<div class="col-md-6">
					<strong>Estado:</strong>
					<span id="EstadoTicket"> <?php echo $ticket_estado ;?></span><br/>
					<strong>NºTicket:</strong>
					<span id="NTicket"><?php echo $ticket_numero ;?></span><br/>
					<span id="EstadoImpresion">	SIN IMPRIMIR</span>
				</div>
			</div>
			<div class="col-md-5">
				<label>Empleado:</label>
				<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="25" readonly>
			</div>
		</div>
		<div class="form-group">
			<label>Cliente:</label>
			<input type="text" id="id_cliente" name="idCliente" value="<?php echo $idCliente;?>" size="2" readonly>
			<input type="text" id="Cliente" name="Cliente" placeholder="Sin identificar" value="<?php echo $cliente; ?>" size="60" readonly>
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes()"></a>
		</div>
	</div>
	<div class="visor fondoNegro col-md-4" style="color:#0ade0a;background-color:black;height:150px;">
		<div class="col-md-4">
		<h3>TOTAL</h3>
		</div>
		<div class="col-md-8 totalImporte text-right" style="font-size: 3em;">
		<?php echo (isset($Datostotales['total']) ? $Datostotales['total'] : '');?>
		</div>
	</div>
	<!-- Tabla de lineas de productos -->
	<div>
		<table id="tabla" class="table table-striped">
		<thead>
		  <tr>
			<th>L</th>
			<th>Codbarras</th>
			<th>Referencia</th>
			<th>Descripcion</th>
			<th>Unid</th>
			<?php
			if ($CONF_campoPeso === 'si'){ ?>
				<th>Cant/Kilo</th>
			<?php
			} else { ?>
				<th style="display: none;">Cant/Kilo</th>
			<?php
			}  ?>

			<th>PVP</th>
			<th>Iva</th>
			<th>Importe</th>
			<th></th>
		  </tr>
		<tr id="Row0">  <!--id agregar para clickear en icono y agregar fila-->
			<td id="C0_Linea" ></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" size="13" value="" autofocus  onkeyup="teclaPulsada(event,'Codbarras',0)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" size="13" value="" onkeyup="teclaPulsada(event,'Referencia',0)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion"
				placeholder="Descripcion" size="20" value="" onkeyup="teclaPulsada(event,'Descripcion',0)">
				<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarProductos('Descripcion','','tpv')"></a>
			</td>
		</tr>
		</thead>
		<tbody>
		<?php
		// Si es un ticket abierto o que existe..
		if (isset($ticket['productos'])){
			$htmllineas = anhadirLineasTicket(array_reverse($ticket['productos']),$CONF_campoPeso);
			//~ $htmllineas = anhadirLineasTicket(array_reverse($ticket['productos'], TRUE),$CONF_campoPeso);
			//~ $htmllineas = anhadirLineasTicket($ticket['productos'],$CONF_campoPeso);
			//~ echo '<pre>';
			//~ print_r($htmllineas[0]);
			//~ echo '</pre>';
			echo implode(' ',$htmllineas);

		}
		?>
		</tbody>
	  </table>
	</div>
	<?php
		// Inicializamos variables a 0

		if (isset($ticket['productos'])){
			// Ahora montamos base y ivas

			foreach ($Datostotales['desglose'] as  $iva => $basesYivas){
				switch ($iva){
				case 4 :
					$base4 = $basesYivas['base'];
					$iva4 = $basesYivas['iva'];

				break;
				case 10 :
					$base10 = $basesYivas['base'];
					$iva10 = $basesYivas['iva'];
				break;
				case 21 :
					$base21 = $basesYivas['base'];
					$iva21 = $basesYivas['iva'];
				break;
				}
			}
			// Ahora cambiamos valor a variable global de javascritp total
			?>
			<script type="text/javascript">
			total = <?php echo $Datostotales['total'];?>;
			</script>
			<?php
			//~ echo '<pre>';
			//~ print_r($Datostotales );
			//~ echo '</pre>';
		}
	?>

	<div class="col-md-10 col-md-offset-2 pie-ticket">
		<table id="tabla-pie" class="col-md-6">
		<thead>
			<tr>
				<th>Tipo</th>
				<th>Base</th>
				<th>IVA</th>
			</tr>
		</thead>
		<tbody>
			<tr id="line4">
				<td id="tipo4">
					<?php echo (isset($base4) ? " 4%" : '');?>
				</td>
				<td id="base4">
					<?php echo (isset($base4) ? $base4 : '');?>
				</td>
				<td id="iva4">
					<?php echo (isset($iva4) ? $iva4 : '');?>
				</td>
				
			</tr>
			<tr id="line10">
				<td id="tipo10">
					<?php echo (isset($base10) ? "10%" : '');?>
				</td>
				<td id="base10">
					<?php echo (isset($base10) ? $base10 : '');?>
				</td>
				<td id="iva10">
					<?php echo (isset($iva10) ? $iva10 : '');?>
				</td>
				
			</tr>
			<tr id="line21">
				<td id="tipo21">
					<?php echo (isset($base21) ? "21%" : '');?>
				</td>
				<td id="base21">
					<?php echo (isset($base21) ? $base21 : '');?>
				</td>
				<td id="iva21">
					<?php echo (isset($iva21) ? $iva21 : '');?>
				</td>
				
			</tr>
		</tbody>
		</table>
		<div class="col-md-6">
			<div class="col-md-4">
			<h3>TOTAL</h3>
			</div>
			<div class="col-md-8 text-rigth totalImporte" style="font-size: 3em;">
			<?php echo (isset($Datostotales['total']) ? $Datostotales['total'] : '');?>
			</div>
		</div>
	</div>

</div>
<?php // Incluimos paginas modales
include 'busquedaModal.php';

?>
</body>

</html>
