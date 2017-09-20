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
	// Si no hay $_GET entonces es nuevo.
	if (isset($_GET['tAbierto'])) {
		// Tenemos que abrir un tique ya abierto
		$ticket_estado = 'Abierto';
		$ticket_numero = $_GET['tAbierto'];
	}
	
	
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
	if (isset($cambiosEstadoTickets['error'])){
		// Entonces obtenemos las caberas para mostrar.
		echo '<pre>';
		print ( 'Hubo error en la consulta de ticket abierto ');
		print_r($cambiosEstadoTickets);
		echo '</pre>';
	}
	// Ahora obtenemos la cabecera de los ticket abiertos de ese usuario.
	$ticketsAbiertos = ObtenerCabeceraTicketAbierto($BDTpv,$Usuario['id'],$Tienda['idTienda'],$ticket_numero);
	if ($ticket_numero > 0){
		//Entonces cargamos los productos.
		$respuesta= ObtenerUnTicket($BDTpv,$Tienda['idTienda'],$Usuario['id'],$ticket_numero);
	}
	

	
	echo '<pre>';
	print_r($respuesta);
	echo '</pre>';
	
?>
<style type="text/css">
<!-- css necesario para agregar o eliminar filas -->
.fila-base { display: none; }<!-- fila base oculta --> 
.eliminar { cursor: pointer; }
.agregar {	cursor: pointer;  } <!-- class para que aparezca cursor --> 
<!-- Fin css para agregar o eliminar filas -->
</style>






<div class="container">

<nav class="col-md-3">
	<div class="col-md-6">
		<h3 class="text-center"> TpvFox</h3>
		<h4>Otros opciones</h4>
		<ul class="nav nav-pills nav-stacked">
			<li><a href="#section1">Nuevo ticket</a></li>
			<li><a href="#section3">Arqueo</a></li>
			<li><a href="#section3">Imprimir Ticket</a></li>
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
			<div class="col-md-4">
			<strong>Fecha:</strong>
			<span id="Fecha"><?php echo date("d/m/Y");?></span>
			</div>
			<div class="col-md-4">
			<strong>Estado:</strong>
			<span id="EstadoTicket"> <?php echo $ticket_estado ;?></span>
			</div>
			<div class="col-md-4">
			<strong>NºTicket:</strong>
			<span id="NTicket"><?php echo $ticket_numero ;?></span>

			</div>
			
			<div class="col-md-4">
			<strong>Hora Inicio:</strong>
			<span id="HoraInicio"><?php echo '00:00';//date("H:i");?></span>
			</div>
			<div class="col-md-4">
			<strong>Hora Final:</strong>
			<span id="HoraFinal"><?php echo '00:00';//date("H:i");?></span>
			</div>
			<div class="col-md-4">
				<span id="EstadoImpresion">	SIN IMPRIMIR</span>
			</div>
		</div>
		<div class="form-group">
			<label>Empleado:</label>
			<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="40" readonly>	
			<label>Cliente:</label>
			<input type="text" id="id_cliente" name="idCliente" value="1" size="2" readonly>
			<input type="text" id="Cliente" name="Cliente" placeholder="Sin identificar" value="" size="60" readonly>
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes()"></a>
		</div>
	</div>
	<div class="visor fondoNegro col-md-4" style="background-color:black;height:150px;">
	<span> Total: 0</span>
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

		
		
		</tbody>
	  </table>
	</div>
	
	<table id="tabla-pie" class="table table-striped">
	<tbody>
		<tr id="titulo">
			<td id="bases">Base
				<div id="base4"></div>
				<div id="base10"></div>
				<div id="base21"></div>
			</td>
			<td id="ivas">IVA
				<div id="iva4"></div>
				<div id="iva10"></div>
				<div id="iva21"></div>
			</td>
			<td id="total">Total
				<div id="totalImporte"></div>
			</td>
		</tr>
	</tbody>
	</table>
	
</div>
<?php // Incluimos paginas modales
include 'busquedaModal.php';

?>
</body>
	
</html>
