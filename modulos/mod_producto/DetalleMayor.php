<!DOCTYPE html>
<html>
	<head>
		<?php 
        include_once './../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_producto/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
       	include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';   
		include_once ($URLCom.'/controllers/parametros.php');
        include_once $URLCom.'/modulos/mod_producto/clases/ClaseArticulos.php';
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$ClasesParametros = new ClaseParametros('parametros.xml');
		$parametros = $ClasesParametros->getRoot();
        $ClassProductos = new ClaseProductos($BDTpv);
        $CArticulo =  new alArticulos();
        $ruta_volver= $HostNombre.'/modulos/mod_producto/ListaMayor.php'; // De momento este, pero tiene que se dinamico.
		$titulo="Listado de mayor de ";
      
        if (isset($_GET['idArticulo'])){
            $idArticulo = $_GET['idArticulo'];
            // Por get debo recibir
            //   idArticulo
            //   FechaInicio y FechaFinal ( opcional)
            //   StockInicial (opcional) 
            // Nota: Normalmente venimos de vista ListaMayor y este nos envia Fecha Inicio y Fecha Final
            //      si no viniera fecha o no fuera correcta, obtenemos la fecha por defecto que debería
            //      ser un parametro configuracion. ( ahora e fija )
            if (isset($_GET['fecha_inicial'])){
                $fecha_inicial = $_GET['fecha_inicial'];
            }
            if (isset($_GET['fecha_final'])){
                $fecha_final = $_GET['fecha_final'];
            }
		}
		$producto = $ClassProductos->GetProducto($idArticulo);
        $idTienda = $Tienda['idTienda'];
        $idUsuario = $Usuario['id'];
        $datos = compact("fecha_inicial","fecha_final","idArticulo","idTienda","idUsuario");
        $movimientos  = $CArticulo->calculaMayor($datos);
		?>
	</head>
	<body>
	<?php
     include_once $URLCom.'/modulos/mod_menu/menu.php';
     //~ echo '<pre>';
     //~ print_r($producto);
     //~ echo '</pre>';
	?>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
	<script type="text/javascript">
    </script>
	<div class="container">
		<h2 class="text-center"><?php echo $titulo.' '.$producto['articulo_name'];?></h2>
		<div class="col-md-2">
			<a class="text-right" href="<?php echo $ruta_volver;?>">Volver Atrás</a>
        </div>
		<div class="col-md-10">
            <div class="col-md-2">
				<strong>ID Articulo:</strong><br>
				<input type="text" name="idArticulo" size="10"   value="<?php echo $producto['idArticulo'];?>" readonly >
			</div>
            <div class="col-md-2">
				<strong>Fecha Inicial:</strong><br>
				<input type="date" name="fecha" id="fecha_inicial" size="10"   value="<?php echo $fecha_inicial;?>" readonly >
			</div>
            <div class="col-md-2">
				<strong>Fecha Final:</strong><br>
				<input type="date" name="fecha" id="fecha_final" size="10"   value="<?php echo $fecha_final;?>" readonly >
			</div>
			<div class="col-md-12">
				<table class="table table-bordered table-hover">
					<thead>
					<tr>
						<th>Fecha</th>
						<th>Entrada</th>
                        <th>Salida</th>
                        <th>Stock</th>
						<th>Coste <br/>Sin Iva</th>
						<th>PVP</th>
						<th>doc</th>
						<th>Nombre</th>
						<th>Estado</th>
					</tr>
                    </thead>
                    <tbody>
				<?php
                    $stock = 0;
                    if (isset($movimientos['datos'])){
                        $entradas = 0;
                        $comprado = 0;
                        $salidas = 0;
                        $vendido = 0;
                        foreach ($movimientos['datos'] as $movimiento){
                            $stock = $stock+$movimiento['entrega'] - $movimiento['salida'];
                            $e = 0;// variable bandera para indicar decimales
                            if ($producto['tipo'] ==='peso'){   
                                $e = 3;
                            }
                            $url = '';
                            $td_entrada = '<td></td>';
                            $td_salida = '<td></td>';
                            $td_precio = '<td></td>';
                            $td_coste = '<td></td>';
                            if ($movimiento['tipodoc']=== 'C'){
                                // Entonces es una entrada
                                $tipo_doc   = 'mod_compras/albaran.php?id='.$movimiento['numid'].'&estado=ver';
                                $td_entrada = '<td>'.number_format(round($movimiento['entrega'],$e),$e).'</td>';
                                $td_coste  = '<td>'.number_format($movimiento['precioentrada'],2).' €'.'</td>';
                                $entradas += $movimiento['entrega'];
                                $comprado += $movimiento['entrega']*$movimiento['precioentrada'];
                            } else {
                                $td_salida = '<td>'. number_format(round($movimiento['salida'],$e),$e).'</td>';
                                $td_precio = '<td>'.number_format($movimiento['preciosalida'],2).' €'.'</td>';
                                $salidas += $movimiento['salida'];
                                $vendido += $movimiento['salida']*$movimiento['preciosalida'];
                            }
                            if ($movimiento['tipodoc']=== 'T'){
                                // Es un ticket
                               $tipo_doc = 'mod_tpv/ticketCobrado.php?id='.$movimiento['numid'];
                            }
                            if ($movimiento['tipodoc']=== 'V'){
                                // Es un albaran de venta
                                $tipo_doc = 'mod_venta/albaran.php?id='.$movimiento['numid'].'&estado=ver';
                            }
                            $url= $HostNombre.'/modulos/'.$tipo_doc;
                            echo '<tr>';
                                echo '<td>'.$movimiento['fecha'].'</td>';
                                echo $td_entrada.$td_salida;
                                echo '<td>'.$stock.'</td>';
                                echo $td_coste.$td_precio;
                                echo '<td>'.$movimiento['serie'].$movimiento['numdocu'].'</td>';
                                echo '<td>'.$movimiento['nombre'].'</td>';
                                echo '<td>'.$movimiento['estado'].'</td>';
                                
                                echo '<td>'.'<a target="_blank" href="'.$url.'"><span class="glyphicon glyphicon-eye-open"></span></a></td>';
                            echo '</tr>';
                        }   
                    
                        // Calculo del beneficio.
                        $beneficio = $vendido - $comprado +($comprado * $producto['iva'])/100;
                        echo '<td><b>Total</b></td><td><b>'.$entradas.'</b></td>'.'<td><b>'.$salidas.'</b></td><td></td>'.'<td><b>'.$comprado.'</b></td>'
                            .'<td><b>'.$vendido.'</b></td><td><b>Beneficio = </b>'.number_format($beneficio,2).' €</td>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
	</body>	
</html>
