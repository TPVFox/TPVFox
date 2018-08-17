<!DOCTYPE html>
<html>
<head>
<?php
	//llamadas  a archivos php 
	include_once './../../inicial.php';
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_compras/funciones.php';
	include_once $URLCom.'/plugins/paginacion/paginacion.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
	include_once $URLCom.'/clases/Proveedores.php';
	include_once ($URLCom.'/controllers/parametros.php');
	
	//Carga de clases necesarias
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Cpedido=new PedidosCompras($BDTpv);
	$Cprveedor=new Proveedores($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	// Variables que utilizamos en pedidos.
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	$titulo="Pedido De Proveedor";
	$dedonde="pedidos";
	$fecha=date('d-m-Y');
	$idPedido=0;
	$numPedidoTemp=0;
	$estado='Abierto';
	$idProveedor='';
	$nombreProveedor="";
	$Datostotales=array();
	$textoNum="";
	$inciden=0;
	//Carga de los parametros de configuración y las acciones de las cajas
	$parametros = $ClasesParametros->getRoot();
	$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_compras',$Usuario['id']);
	$configuracionArchivo=array();
	foreach ($configuracion['incidencias'] as $config){
		if(get_object_vars($config)['dedonde']==$dedonde){
			array_push($configuracionArchivo, $config);
		}
	}

		if (isset($_GET['id'])){
			$idPedido=$_GET['id'];
			$textoNum=$idPedido;
			$datosPedido=$Cpedido->DatosPedido($idPedido);
            $estado='Modificado';
			if ($datosPedido['estado']=='Facturado'){
				$estado=$datosPedido['estado'];
			}
			$productosPedido=$Cpedido->ProductosPedidos($idPedido);
			$ivasPedido=$Cpedido->IvasPedidos($idPedido);
			$fecha =date_format(date_create($datosPedido['FechaPedido']), 'd-m-Y');
			$idProveedor=$datosPedido['idProveedor'];
			$datosProveedor=$Cprveedor->buscarProveedorId($idProveedor);
			$nombreProveedor=$datosProveedor['nombrecomercial'];
			$productosMod=modificarArrayProductos($productosPedido);
			$productos=json_decode(json_encode($productosMod));
			$Datostotales = recalculoTotales($productos);
			$productos=json_decode(json_encode($productosMod), true);
			$incidenciasAdjuntas=incidenciasAdjuntas($idPedido, "mod_compras", $BDTpv, "pedidos");
			$inciden=count($incidenciasAdjuntas['datos']);
		}else{
			if (isset($_GET['tActual'])){
				$numPedidoTemp=$_GET['tActual'];
				$pedido=$Cpedido->DatosTemporal($numPedidoTemp);
				$estado=$pedido['estadoPedPro'];
				$idProveedor=$pedido['idProveedor'];
				if ($pedido['idPedpro']){				
					$idPedido=$pedido['idPedpro'];	
					$textoNum=$idPedido;		
				}
				if ($pedido['fechaInicio']){
					$bandera=new DateTime($pedido['fechaInicio']);
					$fecha=$bandera->format('d-m-Y');
				}
				$productos = json_decode( $pedido['Productos']); // Array de objetos
				if ($idProveedor){
					$datosProveedor=$Cprveedor->buscarProveedorId($idProveedor);
					$nombreProveedor=$datosProveedor['nombrecomercial'];
				}
				
			}
		}
	// Añadimos al titulo el estado
	$titulo .= ' '.$textoNum.': '.$estado;
	
if(isset($pedido['Productos'])){
	// Obtenemos los datos totales;
	// convertimos el objeto productos en array
	$Datostotales = recalculoTotales($productos);
	$productos = json_decode(json_encode($productos), true); // Array de arrays	
}
//  ---------  Control y procesos para guardar el pedido. ------------------ //
if (isset($_POST['Guardar'])){
	// Objetivo :
	// Grabar el pedido.
	$guardar=guardarPedido($_POST, $_GET, $BDTpv, $Datostotales);
	if (count($guardar)==0){
		header('Location: pedidosListado.php');
	}else{
		foreach ($guardar as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br> '.$error['dato']
				. '</div>';
			}
	}
}
$htmlIvas=htmlTotales($Datostotales);
?>

<script type="text/javascript">
<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
	<?php echo 'var configuracion='.json_encode($configuracionArchivo).';';?>	
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; // Si no hay datos GET es 'Abierto'
		cabecera['idTemporal'] = <?php echo $numPedidoTemp ;?>;
		cabecera['idReal'] = <?php echo $idPedido ;?>;
		cabecera['idProveedor']='<?php echo $idProveedor ;?>';
		cabecera['fecha']='<?php echo $fecha;?>';
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	<?php 
	$i= 0;
	if (isset($productos)){
		if ($productos){
			foreach($productos as $product){
	?>
			datos=<?php echo json_encode($product); ?>;
			productos.push(datos);
	<?php //cambiamos estado y cantidad de producto creado si fuera necesario.
				if (isset ($product->estado)){
					if ($product->estado !== 'Activo'){
					?>	productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
					<?php
					}
				}
				$i++;
			 }
		 }	
	 }
	?>
</script>
<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
<script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/js/AccionesDirectas.js"></script>
<script type="text/javascript">
    <?php
	 if (isset($_POST['Cancelar'])){
		  ?>
		 mensajeCancelar(<?php echo$numPedidoTemp;?>, <?php echo "'".$dedonde."'"; ?>);
		  <?php
	  }
	  ?>
</script>
</head>
<body>
<?php
	//~ include_once $URLCom.'/header.php';
     include_once $URLCom.'/modulos/mod_menu/menu.php';
?>
<div class="container">
	<h2 class="text-center"> <?php echo $titulo;?></h2>
	<form class="form-group" action="" method="post" name="formProducto" onkeypress="return anular(event)">
		<div class="col-md-12">
			<div class="col-md-8" >
				<a  href="pedidosListado.php" onclick="ModificarEstadoPedido(pedido, Pedido);">Volver Atrás</a>
				
                <?php 
                if($idPedido>0){
                    echo '<input class="btn btn-warning" size="12" 
                    onclick="abrirModalIndicencia('."'".$dedonde."'".' , configuracion, 0, '.$idPedido.');" 
                    value="Añadir incidencia " name="addIncidencia" id="addIncidencia">';
                    
                }
               if($inciden>0){
                   echo '<input class="btn btn-info" size="15" 
                   onclick="abrirIncidenciasAdjuntas('.$idPedido.', '."'".'mod_compras'."'".', '."'".'pedidos'."'".')"
                   value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">';
               }
                
                ?>
                <input class="btn btn-primary" type="submit" value="Guardar" name="Guardar" id="bGuardar">
			</div>
            <div class="col-md-4 text-right" >
                <span class="glyphicon glyphicon-cog" title="Escoje casilla de salto"></span>
                <select  title="Escoje casilla de salto" id="salto" name="salto">
                    <option value="0">Seleccionar</option>
                    <option value="1">Id Articulo</option>
                    <option value="2">Referencia</option>
                    <option value="3">Referencia Proveedor</option>
                    <option value="4">Cod Barras</option>
                    <option value="5">Descripción</option>
                </select>
               <input type="submit"class=" btn btn-danger"  value="Cancelar" name="Cancelar" id="bCancelar">
                <?php
                if (isset($numPedidoTemp)){
                ?>
                    <input  type="text" style="display:none;" name="idTemporal" value=<?php echo $numPedidoTemp;?>>
                <?php
                }
                ?>
            </div>
		</div>
	<div class="col-md-8">
			<div class="col-md-3">
				<label>Estado:</label>
				<input type="text" id="estado" name="estado" value="<?php echo $estado;?>" readonly>
			</div>
			<div class="col-md-3">
				<label>Usuario:</label>
				<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="13" readonly>
			</div>
			<div class="col-md-3">
				<label>Fecha Pedido:</label>
				<input type="text" name="fecha" id="fecha" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
			</div>
		<div class="col-md-12">
			<label>Proveedor:</label>
			<input type="text" id="id_proveedor" name="id_proveedor" data-obj= "cajaIdProveedor" value="<?php echo $idProveedor;?>" size="2" onkeydown="controlEventos(event)" placeholder='id'>
			<input type="text" id="Proveedor" name="Proveedor" data-obj= "cajaProveedor" placeholder="Nombre de proveedor" onkeydown="controlEventos(event)" value="<?php echo $nombreProveedor; ?>" size="60" >
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarProveedor('pedidos')"></a>
		</div>
	</div>
	<!-- Tabla de lineas de productos -->
	<div class="row">
		<table id="tabla" class="table table-striped" >
			<thead>
			  <tr>
				<th>L</th>
				<th>Id Articulo</th>
				<th>Referencia</th>
				<th>Referencia Proveedor</th>
				<th>Cod Barras</th>
				<th>Descripcion</th>
				<th>Unid</th>
				<th>Coste</th>
				<th>Iva</th>
				<th>Importe</th>
				<th></th>
			  </tr>
			  <tr id="Row0">  
				<td id="C0_Linea" ></td>
				<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="4" value=""  onkeydown="controlEventos(event)"></td>
				<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="8" value="" onkeydown="controlEventos(event)"></td>
				<td><input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Referencia" data-obj="cajaReferenciaPro" size="10" value="" onkeydown="controlEventos(event)"></td>
				<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
				<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)"></td>
			  </tr>
			</thead>
			<tbody>
				<?php 
				if (isset($productos)){
					foreach (array_reverse($productos) as $producto){
						$h=htmlLineaProducto($producto, "pedidos");
						echo $h['html'];
					}
				}
			?>
			</tbody>
	  </table>
	</div>
	<?php 
	if (isset($DatosTotales)){
		?>
		<script type="text/javascript">
			total = <?php echo $Datostotales['total'];?>;
		</script>
		<?php
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
			<?php 
			echo $htmlIvas['html']; ?>
		</tbody>
		</table>
		<div class="col-md-6">
			<div class="col-md-4">
			<h3>TOTAL</h3>
			</div>
			<div class="col-md-8 text-rigth totalImporte" style="font-size: 3em;">
				<?php echo (isset($Datostotales['total']) ? number_format ($Datostotales['total'],2, '.', '') : '');?>
			</div>
		</div>
	</div>
</form>
</div>
<?php // Incluimos paginas modales
 echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
?>
<script type="text/javascript">
	 $('#id_proveedor').focus();
	 <?php
	if ($idProveedor>0){
		?>
		$('#id_proveedor').prop('disabled', true);
		$('#Proveedor').prop('disabled', true);
		$("#buscar").css("display", "none");
		$('#idArticulo').focus();
		<?php
	}else{
		?>
		$("#Row0").css("display", "none");
		<?php
	}
	if ($estado=="Facturado" || $_GET['estado']=="ver"){
		?>
		$("#tabla").find('input').attr("disabled", "disabled");
		$("#tabla").find('a').css("display", "none");
		$("#bGuardar").css("display", "none");
		$("#fecha").prop('disabled', true);
		<?php
	}
	?>
</script>
	</body>
</html>
