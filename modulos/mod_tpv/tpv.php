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
?>

<script type="text/javascript"> 
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO 
	var CONF_campoPeso="<?php echo $CONF_campoPeso; ?>";
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
	cabecera['idCliente'] = 1; // Este dato puede cambiar
	cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
	cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; // Tuve que adelantar la carga, sino funcionaria js.
	
	
	
	
	
	
	
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
	//~ echo '<pre>';
	//~ print_r($Usuario);
	//~ echo '</pre>';
	
?>
<style type="text/css">
<!-- css necesario para agregar o eliminar filas -->
.fila-base { display: none; }<!-- fila base oculta --> 
.eliminar { cursor: pointer; }
.agregar {	cursor: pointer;  } <!-- class para que aparezca cursor --> 
<!-- Fin css para agregar o eliminar filas -->
</style>






<div class="container">
<!--=================  Sidebar -- Menu y filtro =============== 
Efecto de que permanezca fixo con Scroll , el problema es en
movil
-->
<nav class="col-md-2" id="myScrollspy">
	<div data-spy="affix" data-offset-top="505">
		<h4> TpvFox</h4>
		<h5>Menú Generales</h5>
		<ul class="nav nav-pills nav-stacked">
			<li><a href="#section1">Nuevo ticket</a></li>
			<li><a href="#section2">Ver Cerrados</a></li>
			<li><a href="#section3">Arqueo</a></li>
			<li><a href="#section3">Imprimir Ticket</a></li>

			
			
		</ul>
		<h5>Opciones de ticket</h5>Generales</h5>
		<ul class="nav nav-pills nav-stacked">
			<li><a href="#section1">Nuevo Linea</a></li>
			<li><a href="#section2">Eliminar Linea</a></li>
			<li><a href="#section3">Añadir Cliente</a></li>
			<li><a href="#section3">Abrir Cajon</a></li>
			<li><a href="#section3">Cobrar</a></li>
			
		</ul>
	</div>
</nav>
<div class="col-md-10" >
	<div class="col-md-8">
		<div class="col-md-12">
			<div class="col-md-4">
			<strong>Fecha:</strong>
			<span id="Fecha"><?php echo date("d/m/Y");?></span>
			</div>
			<div class="col-md-4">
			<strong>Estado/N:</strong>
			<span id="Estado">NUEVO</span>
			</div>
			<div class="col-md-4">
			<strong>Numero Ticket:</strong>
			<span id="NTicket"></span>

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
			<input type="text" id="id" name="id" value="1" size="2" readonly>
			<input type="text" id="Cliente" name="Cliente" placeholder="Sin identificar" value="" size="60" readonly>
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="nombreCampo('busquedaCliente',0,'',0)"></a>
		</div>
	</div>
	<div class="fondoNegro col-md-4" style="background-color:black;height:150px;">
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
			<td id="C0_Linea" ><span id="agregar" class="glyphicon glyphicon-plus-sign agregar"></span></td>
			<td><input id="C0_Codbarras" type="text" name="Codbarras" placeholder="Codbarras" size="13" value="" autofocus  onkeydown="teclaPulsada(event,'Codbarras',0)"></td>
			<td><input id="C0_Referencia" type="text" name="Referencia" placeholder="Referencia" size="13" value="" onkeydown="teclaPulsada(event,'Referencia',0)"></td>
			<td><input id="C0_Descripcion" type="text" name="Descripcion" placeholder="Descripcion" size="20" value="" onkeydown="teclaPulsada(event,'Descripcion',0)"> </td>
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
