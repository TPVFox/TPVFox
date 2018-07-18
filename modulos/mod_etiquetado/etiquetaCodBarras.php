<!DOCTYPE html>
<html>
    <head>
		<?php
		include_once './../../inicial.php';
		
        include_once $URLCom.'/head.php';
        include_once $URLCom."/controllers/Controladores.php";
        include_once $URLCom.'/clases/articulos.php';
        include_once $URLCom.'/modulos/mod_etiquetado/clases/modulo_etiquetado.php';
        include_once $URLCom.'/modulos/mod_etiquetado/funciones.php';
		include_once ( $URLCom.'/controllers/parametros.php');
		
		$ClasesParametros = new ClaseParametros('parametros.xml');
        
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$Carticulo=new Articulos($BDTpv);
		$Cetiqueta=new Modulo_etiquetado($BDTpv);
        $Tienda = $_SESSION['tiendaTpv'];
		$Usuario = $_SESSION['usuarioTpv'];
        $titulo="Crear Etiquetas de Código Barras";
        $fechaEnv=date('Y-m-d H:i:s');
        $nuevafecha = strtotime ( '+7 day' , strtotime ( $fechaEnv ) ) ;
		$fechaCad = date ( 'Y-m-d' , $nuevafecha );
        $numAlb="";
        $nomPro="";
        $idReal=0;
        $unidades="";
        $estado="Activo";
        $idTemporal=0;
		$idProducto="";
		$tipo=0;
		$productos=array();
		$errores=array();
		$parametros = $ClasesParametros->getRoot();	
		$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
		
		
		
		if(isset($_GET['id'])){
			$idReal=$_GET['id'];
			$etiquetaReal=$Cetiqueta->datosLote($idReal);
			if(isset($etiquetaReal['error'])){
				$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $etiquetaReal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
			}else{
				$fechaEnv=$etiquetaReal['fecha_env'];
				$fechaCad=$etiquetaReal['fecha_cad'];
				$numAlb=$etiquetaReal['numAlb'];
				$idProducto=$etiquetaReal['idArticulo'];
				$nomPro=$etiquetaReal['articulo_name'];
				$estado=$etiquetaReal['estado'];
				$tipo=$etiquetaReal['tipo'];
				$productos=$etiquetaReal['productos'];
				
				$productos=json_decode($productos, true);
				
				if(isset($etiquetaReal['num_lote'])){
					$idReal=$etiquetaReal['num_lote'];
				}
				if(isset($tipo)){
					switch($tipo){
						case '1':
							$TipoTexto="Por unidad";
						break;
						case '2':
							$TipoTexto="Por peso";
						break;
						default:
							$TipoTexto="Sin seleccionar";
						break;
					}
				}else{
					$TipoTexto="Sin seleccionar";
				}
				
			}
		}
		if(isset($_GET['tActual'])){
			//@Obejtivo: si resibe los datos de un temporal los carga
			$idTemporal=$_GET['tActual'];
			$etiquetaTemporal=$Cetiqueta->buscarTemporal($idTemporal);
			if(isset($etiquetaTemporal['error'])){
				$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $etiquetaTemporal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
			}else{
				$fechaEnv=$etiquetaTemporal['fecha_env'];
				$fechaCad=$etiquetaTemporal['fecha_cad'];
				$numAlb=$etiquetaTemporal['numAlb'];
				$idProducto=$etiquetaTemporal['idArticulo'];
				$nomPro=$etiquetaTemporal['articulo_name'];
				$estado=$etiquetaTemporal['estado'];
				$tipo=$etiquetaTemporal['tipo'];
				$productos=$etiquetaTemporal['productos'];
				$productos=json_decode($productos, true);
				if(isset($etiquetaTemporal['num_lote'])){
					$idReal=$etiquetaTemporal['num_lote'];
				}
			}
		}
		if(isset($_POST['Guardar'])){
			//@OBjetivo: guardar los datos de un temporal como real
			//Funcionamiento:
			//Primero se buscan los datos del temporal, sólo se guardan los productos que el estado
			//sea activo
			//Si se eliminan todos los productos muestra un error
			//A continuación se guardar el real y se elimina el temporal mientras no suceda nigún error
			if($idTemporal>0){
				$datosTemporal=$Cetiqueta->buscarTemporal($idTemporal);
				$productos=$datosTemporal['productos'];
				$productos=json_decode($productos, true);
				//~ echo '<pre>';
				//~ print_r($productos);
				//~ echo '</pre>';
				if(isset($_POST['fechaCad'])){
					$fechaCad=$_POST['fechaCad'];
				}
				if(isset($productos)){
					
					$i=0;
					foreach($productos as $producto){
						if($producto['estado']=='Eliminado'){
							unset($productos[$i]);
							$i++;
						}
					}
					
					$cantidadProd=count($productos);
					
					if($cantidadProd>0){
						$productos=json_encode($productos);
					}else{
						$errores[0]=array ( 'tipo'=>'Info!',
								 'dato' =>'NO puedes eliminar todos los elementos',
								 'class'=>'alert alert-info',
								 'mensaje' => ''
								 );
					}
					
					
				}
				if(isset($datosTemporal['tipo'])){
					$tipo=$datosTemporal['tipo'];
				}
				
				
				$datos=array(
					'idReal'	=>$idReal,
					'tipo'		=>$tipo,
					'fecha_env'	=>$fechaEnv,
					'fecha_cad'	=>$fechaCad,
					'idArticulo'=>$idProducto,
					'numAlb'	=>$numAlb,
					'estado'	=>"Guardado",
					'productos'	=>$productos,
					'idUsuario'	=>$Usuario['id']
				);
				if(count($errores)==0){
					$guardar=$Cetiqueta->addLoteGuardado($datos);
					if(isset($guardar['error'])){
						$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $guardar['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
					}else{
						$eliminar=$Cetiqueta->eliminarTemporal($idTemporal);
						if(isset($eliminar['error'])){
							$errores[2]=array ( 'tipo'=>'Danger!',
								 'dato' => $eliminar['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
						}else{
							header('Location: ListadoEtiquetas.php');
						}
					}
				}
			}else{
				$errores[2]=array ( 'tipo'=>'Info!',
								 'dato' => '',
								 'class'=>'alert alert-info',
								 'mensaje' => 'No se puede guardar ya que no tiene ninguna modificación!'
								 );
				//Mostrar advertencia de que no se puede guardar un lote que ya está guardado
				//controlar cuando no hay temporal y se guarda solo la fecha o numalb
			}
		}
		if(isset($_POST['Cancelar'])){
			if($idTemporal>0){
				$eliminar=$Cetiqueta->eliminarTemporal($idTemporal);
				if(isset($eliminar['error'])){
					$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $eliminar['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
				}else{
					header('Location: ListadoEtiquetas.php');
				}
			}else{
					$errores[2]=array ( 'tipo'=>'Info!',
								 'dato' => '',
								 'class'=>'alert alert-info',
								 'mensaje' => 'No puedes cancelar un lote que ya está guardado!'
								 );
			}
			
		}
	
				foreach($productos as $producto){
					$nFila=1;
					$producto['Nfila']=$nFila;
					$nFila++;
				}
		
				//~ echo count($productos);
				
        ?>
        <script type="text/javascript">
			var cabecera = [];
				cabecera['idUsuario'] = <?php echo $Usuario['id'];?>;
				cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
				cabecera['estado'] ='<?php echo $estado ;?>';
				cabecera['idTemporal'] = <?php echo $idTemporal ;?>;
				cabecera['idReal'] = <?php echo $idReal ;?>;
				cabecera['fechaEnv'] = '<?php echo $fechaEnv ;?>';
				cabecera['fechaCad'] = '<?php echo $fechaCad ;?>';
				cabecera['idProducto'] = '<?php echo $idProducto ;?>';
				cabecera['tipo'] = '<?php echo $tipo ;?>';
				cabecera['numAlb'] = '<?php echo $numAlb ;?>';
			var productos = [];
			<?php 
	if (isset($etiquetaReal)| isset($etiquetaTemporal)){ 
	$i= 0;
		if (isset($productos)){
			foreach($productos as $product){
?>	
				datos=<?php echo json_encode($product); ?>;
				productos.push(datos);
	
<?php 
		// cambiamos estado y cantidad de producto creado si fuera necesario.
			if ($product['estado'] !== 'Activo'){
			?>	productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
			<?php
			}
			$i++;
			}
	
		}
	}
		?>
		</script>
     </head>
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_etiquetado/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
	<?php     
        //~ include './../../header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        if (isset($errores)){
		foreach($errores as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br> '.$error['dato']
				. '</div>';
		}
	}
	
        ?>
        <script type="text/javascript">
			<?php echo $VarJS;?>
			 function anular(e) {
				  tecla = (document.all) ? e.keyCode : e.which;
				  return (tecla != 13);
			  }
		</script>
        <div class="container">
		<h2 class="text-center"> <?php echo $titulo;?></h2>
		<form action="" method="post" name="formEtiqueta" onkeypress="return anular(event)">
			<div class="col-md-12">
				<div class="col-md-12 ">
					<div class="col-md-8">
						<a href="ListadoEtiquetas.php">Volver Atrás</a>
						<input type="submit" class="btn btn-primary" name="Guardar" value="Guardar">
					</div>
					<div class="col-md-4">
						<input type="submit" class="pull-right btn btn-danger" name="Cancelar" value="Cancelar">
					</div>
				</div>
				
				<div class="col-md-12">
					<div class="col-md-2">
						<label>Fecha Envasado</label>
						<input type="date" name="fechaEnv" id="fechaEnv" size="17"  value="<?php echo $fechaEnv;?>" readonly>
					</div>
					<div class="col-md-2">
						<label>Fecha Caducidad</label>
						<input type="date" name="fechaCad" id="fechaCad" size="10" data-obj= "cajaFechaCad"  value="<?php echo $fechaCad;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
					</div>
					<div class="col-md-2">
						<label>Tipo</label>
						<?php 
						if($estado=='Guardado'){
							?>
							<input type="text" name="tipo" id="tipo" value="<?php echo $TipoTexto;?>" readonly>
							<?php
						}else{
						?>
					<select name="tipo" id="tipo" onchange="modificarTipo(value);">
						<option value='0'>Selecciona</option>
						<option value='1'>Por unidad</option>
						<option value='2'>Por peso</option>
					</select>
					<?php }?>
					</div>
					<div class="col-md-2">
						<label>Num Albarán</label>
						<input type="text" id="numAlb" name="numAlb" value="<?php echo $numAlb;?>" size="10" data-obj= "cajaNumAlb" onkeydown="controlEventos(event)">
					</div>
				</div>
				<div class="col-md-12">
					<div class="col-md-6">
						<label>Producto:</label>
						<input type="text" id="id_producto" name="id_producto" data-obj= "cajaIdProducto" value="<?php echo $idProducto;?>" size="4" onkeydown="controlEventos(event)" placeholder='id'>
						<input type="text" id="producto" name="producto" value="<?php echo $nomPro;?>" size="50" data-obj="cajaNombreProducto" onkeydown="controlEventos(event)" placeholder='Nombre del producto'>
						<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarProducto()"></a>
					</div>
					<div class="col-md-2">
						<label>Unidades</label>
						<input type="text" id="unidades" name="unidades" value="<?php echo $unidades;?>" size="10" data-obj= "cajaUnidades" onkeydown="controlEventos(event)" >
					</div>
				</div>
				<div class="col-md-12">
					<table id="tabla" class="table table-striped">
						<thead>
							<tr>
								<th>L</th>
								<th class="col-sm-4">Nombre del producto</th>
								<th id="tipoTabla">Tipo</th>
								<th>Precio</th>
								<th>Fecha</th>
								<th>Num Alb</th>
								<th>Num Cod Barras</th>
							</tr>
						</thead>
						<tbody>
						<?php 
						if(count($productos)>0){
							$html=lineasProductos($productos);
							echo $html;
						}
						?>
						</tbody>
					</table>
				</div>
			</div>
		</form>
		</div>
		<?php // Incluimos paginas modales
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
// hacemos comprobaciones de estilos 
?>
<script type="text/javascript">
<?php 
if($idProducto>0){
	?>
		$('#producto').prop('disabled', true);
		$('#id_producto').prop('disabled', true);
		$("#buscar").css("display", "none");
	<?php
}
if($estado=="Guardado"){
	?>
	$('#fechaCad').prop('disabled', true);
	<?php
	if($tipo==1){
		?>
		$('#tipoTabla').html("Unidad");
			<?php
	}
	if($tipo==2){
		?>
		$('#tipoTabla').html("Peso");
			<?php
	}
}
if($tipo>0){
	?>
	$("#tipo option[value="+<?php echo $tipo;?> +"]").attr("selected",true);
	<?php
}
?>
</script>
	</body>
</html>
      
