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
?>
<script src="<?php echo $HostNombre; ?>/modulos/mod_tpv/funciones.js"></script>
<!--
<script src="<?php //echo $HostNombre; ?>/modulos/mod_tpv/calculador.js"></script>
-->


</head>
<body>
<?php 
	include './../../header.php';
	
	include_once ("./funciones.php");

?>
<?php
	//~ $busqueda = '3012';
	//~ $campoAbuscar = 'CREF';
	//~ $respuesta =  BuscarProducto($campoAbuscar,$busqueda,$BDImportDbf);
	//$respuesta = teclaPulsada(event,id);
	
	//~ echo '<pre>';
	//~ print_r($respuesta);
	//~ echo '</pre>';
?>
<style type="text/css">
<!-- css necesario para agregar o eliminar filas -->
.fila-base { display: none; }<!-- fila base oculta --> 
.eliminar { cursor: pointer; }
.agregar {	cursor: pointer;  } <!-- class para que aparezca cursor --> 
<!-- Fin css para agregar o eliminar filas -->
</style>

<script type="text/javascript">
	
 //Sera funcion que agrega o elimina linea.
//~ $(function(){
	//~ // Clona la fila oculta que tiene los campos base, y la agrega al final de la tabla
	 //~ $("#agregar").on("click", function(){
		//~ $("#tabla tbody tr:eq(0)").clone().removeClass('fila-base').appendTo("#tabla tbody");
	 //~ });
 
	//~ // Evento que selecciona la fila y la elimina 
	//~ $(document).on("click",".eliminar",function(){
		//~ var parent = $(this).parents().get(0);
		//~ $(parent).remove();
	//~ });
//~ });
//fin funcion que agrega o elimina linea

</script>




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
			<strong>Numero Ticket:</strong>
			<span id="NTicket">0</span>
			</div>
			<div class="col-md-4">
			<strong>Estado:</strong>
			<span id="Estado">NUEVO</span>
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
			<input type="text" id="Usuario" name="Usuario" value="" size="40" readonly>	
			<label>Cliente:</label>
			<input type="text" id="Cliente" name="Cliente" placeholder="Sin identificar" value="" size="60" readonly>	
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
			<th>Cant/Kilo</th>
			<th>PVP</th>
			<th>Iva</th>
			<th>Importe</th>
			<th></th>
		  </tr>
		</thead>
		<tbody>
<!--
		  <tr id="Row1">
			<td id="C1_Linea">1</td>
			<td id="C1_Codbarras">8470002523128</td>
			<td id="C1_Referencia">000-14525</td>
			<td id="C1_Descripcion">Alcohol Sanitario VF 96% 250ml Peligro </td>
			<td id="C1_Unid">10</td>
			<td id="C1_Cant_Kilo"></td>
			<td id="C1_Pvp">1.00</td>
			<td id="C1_Iva">21%</td>
			<td id="C1_Importe">10.22</td>
			<td id="C1_Comentario"></td>

		  </tr>
-->
		<tr id="Row0">  <!--id agregar para clickear en icono y agregar fila-->
			<td id="C0_Linea" ><span id="agregar" class="glyphicon glyphicon-plus-sign agregar"></span></td>
			<td><input id="C0_Codbarras" type="text" name="Codbarras" placeholder="Codbarras" size="13" value="" onkeypress="teclaPulsada(event,'C0_Codbarras')"></td>
			<td><input id="C0_Referencia" type="text" name="Referencia" placeholder="Referencia" size="13" value="" onkeypress="teclaPulsada(event,'C0_Referencia')"></td>
			<td><input id="C0_Descripcion" type="text" name="Descripcion" placeholder="Descripcion" size="20" value="" onkeypress="teclaPulsada(event,'C0_Descripcion')"> </td>
		</tr>
		</tbody>
	  </table>
	</div>	
		
</div>


</body>
</html>
