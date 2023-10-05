<?php 
    include_once './../../inicial.php';
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
        if (!isset($_GET['fecha_inicial']) || $_GET['fecha_inicial'] ==''){
            // No existe o viene vacio.
            $fecha_inicial="0000-00-00";   
        } else {
             $fecha_inicial = $_GET['fecha_inicial'];
        }
        if (!isset($_GET['fecha_final']) || $_GET['fecha_inicial'] ==''){
            $date =new DateTime('NOW');
            $fecha_final = $date->format("Y-m-d");
            
        } else {
            $fecha_final = $_GET['fecha_final'];
        }
    }
    $producto = $ClassProductos->GetProducto($idArticulo);
    $idTienda = $Tienda['idTienda'];
    $idUsuario = $Usuario['id'];
    $datos = compact("fecha_inicial","fecha_final","idArticulo","idTienda","idUsuario");
    $movimientos  = $CArticulo->calculaMayor($datos);

   $activarMensaje = false;
    
    if($producto['idArticulo'] == ""){
        
        $mensaje ="<div class='alert alert-danger'>No se encontró un producto con ese id</div>";
        $activarMensaje = true;
    }


    /*
    echo '<pre>';
    print_r($movimientos);
    echo '</pre>';
    */
?>
<!DOCTYPE html>
<html>
	<head>
        <?php include_once $URLCom.'/head.php'; ?>
        <script>
            let id=<?php echo $producto['idArticulo'];?> ;
            let fechaInicio='<?php echo $fecha_inicial;?>';
            let fechaFin ='<?php echo $fecha_final;?>';
        </script>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/js/AccionesDirectas.js"></script>
        <style>
            .col-md-12{
                display:flex;
                flex-direction:column-reverse;
            }
            .centrarDomingos{
                text-align:center;
            }
            .colorSubtotal{
                background-color:#eefad4;
                text-align:center;
            }
            .unidades{
                margin-top:1vh;
                text-align:center;
                border-radius:3vh;
                background-color:#cdf5f3;
            }
            .importes{
                margin-top:1vh;
                text-align:center;
                border-radius:3vh;
                background-color:#b9c7fa;
            }
            .sinEspeciales{
                margin-top:1vh;
                text-align:center;
                border-radius:3vh;
                background-color:#f3e1fa;
            }
        </style>
	</head>
	<body>
	<?php
     include_once $URLCom.'/modulos/mod_menu/menu.php';
	?>
	<div class="container">
		<h2 class="text-center"><?php echo $titulo.' '.$producto['articulo_name'];?></h2>



        <?php 

            //Datos para la tabla lateral        
            $numVeces = 0;
            $datosMeses = array();
            $datosMesesEspeciales = array();
        ?>
		
        
		<div class="col-md-9 col-md-push-3">
            <div id="error"><?php if($activarMensaje){echo $mensaje;} ?></div>
            <div class="col-md-2">
				<strong>ID Articulo: </strong><br>
				<input type="num" name="idArticulo" size="10" id="idArticulo"  value="<?php echo $producto['idArticulo'];?>"  >
			</div>
            <div class="col-md-2">
				<strong>Fecha Inicial:</strong><br>
                <!-- Preguntar -->
				<input type="date" name="fecha" id="fecha_inicial" size="10"  value="<?php echo $fecha_inicial;?>"  >
			</div>
            <div class="col-md-2">
				<strong>Fecha Final:</strong><br>
				<input type="date" name="fecha" id="fecha_final" size="10"   value="<?php echo $fecha_final;?>">
			</div>
            
            
			<div class="col-md-12">
				<table class="table table-bordered table-hover">
					<thead>
					<tr>
                            <th>Domingos</th>
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

                                $mismoMes=False;
                                $entradasMes = 0;
                                $salidasMes = 0;
                                $compradoMes = 0;
                                $vendidoMes = 0;
                                $beneficioMes = 0;

                                $compradoSubEs = 0;
                                $vendidoSubEs = 0;
                                $entregaSubEs = 0;
                                $salidasSubEs = 0;


                        foreach ($movimientos['datos'] as $movimiento){
                            
                                    $mesFecha = Date("m", strtotime($movimiento['fecha']));
                       
                                    if(!$mismoMes){
                                        $mes = $mesFecha;
                                        $mismoMes = TRUE;
                                    }
                            
                                    $dia = Date("w",strtotime($movimiento['fecha']));
                                    //print_r($movimiento['preciosalida']);
                                    //print_r($movimiento['entrega']);
                                    //print_r($movimiento['salida']);

                            
                            $stock = $stock+$movimiento['entrega'] - $movimiento['salida'];
                            $e = 0;// variable bandera para indicar decimales
                            if ($producto['tipo'] ==='peso'){   
                                $e = 3;
                            }
                            $tipo_doc= '';
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

                                        //
                                        $entradasMes += $movimiento['entrega'];
                                if ( $movimiento['precioentrada'] !== 0){
                                    $precio_coste_civa = $movimiento['precioentrada']+($movimiento['precioentrada']*$producto['iva'])/100;
                                }
                                        $c = $movimiento['entrega'] * $precio_coste_civa;
                                        $comprado += $c;
                                        //
                                        $compradoMes += $c;

                                        if($movimiento['estadoCliente'] == 'Especial'){
                                            $compradoES += $c;
                                            $entradasES += $movimiento['entrega'];
                                            $compradoSubEs+= $c;
                                            $entregaSubEs+=$movimiento['entrega'];
                                        }
                                                                
                            } else {
                                if ($movimiento['tipodoc']=== 'T'){
                                    // Es un ticket
                                    $tipo_doc = 'mod_tpv/ticketCobrado.php?id='.$movimiento['numid'];
                                }
                                if ($movimiento['tipodoc']=== 'V'){
                                    // Es un albaran de venta
                                    $tipo_doc = 'mod_venta/albaran.php?id='.$movimiento['numid'].'&estado=ver';
                                }
                                $td_salida = '<td>'. number_format(round($movimiento['salida'],$e),$e).'</td>';
                                $td_precio = '<td>'.number_format($movimiento['preciosalida'],2).' €'.'</td>';
                                $salidas += $movimiento['salida'];

                                        $salidasMes += $movimiento['salida'];

                                        $b = $movimiento['salida']*$movimiento['preciosalida'];
                                        $vendido += $b;
                                        //
                                        $vendidoMes += $b;

                                        if($movimiento['estadoCliente'] == 'Especial'){
                                            $vendidoEs += $b;
                                            $salidasES += $movimiento['salida'];
                                            $vendidoSubEs += $b;
                                            $salidasSubEs+=$movimiento['salida'];
                            }
                                    }
                            
                            $url= $HostNombre.'/modulos/'.$tipo_doc;
                            

                                    if($mes <> Date("m", strtotime($movimiento['fecha']))){
                                        
                                        $mismoMes = FALSE;
                                        
                                        $datosMeses[$numVeces] = subMesTotal($vendidoMes, $compradoMes, $mes, $entradasMes , 
                                        $salidasMes, $entregaSubEs,$salidasSubEs,$compradoSubEs,$vendidoSubEs);
                                        
                                        $entradasMes =0;
                                        $salidasMes = 0;
                                        $compradoMes=0;
                                        $vendidoMes =0;
                                        $beneficioMes = 0;
                                        
                                        $entregaSubEs = 0;
                                        $salidasSubEs = 0;
                                        $compradoSubEs =0;
                                        $vendidoSubEs = 0;

                                        $numVeces++;
                                    }
                            
                                    //Si es 0 significa que es domingo
                                    if($dia == 0){
                                        $domingo = "X";
                                    }else{
                                        $domingo = "";
                                    }
                           
                                    if($movimiento['estadoCliente'] == "Especial"){
                                        echo "<tr class='bg-danger'>";
                                                               
                                    }else{

                                        colorTabla($stock);
                                
                                    }
                           
                                    echo "<td class='centrarDomingos'><b>".$domingo."</b></td>
                                        <td>".$movimiento['fecha']."</td>"
                                    ;
                                echo $td_entrada.$td_salida;
                                echo '<td>'.$stock.'</td>';
                                echo $td_coste.$td_precio;
                                echo '<td>'.$movimiento['serie'].$movimiento['numdocu'].'</td>';
                                echo '<td>'.$movimiento['nombre'].'</td>';
                                echo '<td>'.$movimiento['estado'].'</td>';
                                
                                echo '<td>'.'<a target="_blank" href="'.$url.'"><span class="glyphicon glyphicon-eye-open"></span></a></td>';
                            echo '</tr>';
                        }   
                                $datosMeses[$numVeces] = subMesTotal($vendidoMes, $compradoMes, $mes, $entradasMes , $salidasMes,
                                 $entregaSubEs,$salidasSubEs, $compradoSubEs, $vendidoSubEs);
                        // Calculo del beneficio.
                        $beneficio = $vendido - $comprado;

                                $regulaciones = $vendidoEs - $compradoES;

                                $beneficioReal = $beneficio - $regulaciones;
                        
                            
                                echo '<tr>
                                        <td></td>
                                        <td></td>
                                        <td><b>Entradas</b></td>
                                        <td><b>Salidas</b></td>
                                        <td></td>
                                        <td><b>Compras</b></td>
                                        <td><b>Ventas</b></td>
                                        <td><b>Beneficios</b></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td><b>Total</b></td>
                                        <td><b>'.$entradas.'</b></td>
                                        <td><b>'.number_format($salidas,3, '.', '').'</b></td>
                                        <td></td>
                                        <td><b>'.number_format($comprado,2, '.', '').'</b></td>
                                        <td><b>'.number_format($vendido,2, '.', '').'</b></td>
                                        <td><b>Beneficio = '.number_format($beneficio,2, '.', '').' €</b></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td><b>Especiales</b></td>
                                        <td><b>'.$entradasES.'</b></td>
                                        <td><b>'.number_format($salidasES,3, '.', '').'</b></td>
                                        <td></td>
                                        <td><b>'.number_format($compradoES,2, '.', '').'</b></td>
                                        <td><b>'.number_format($vendidoEs,2, '.', '').'</b></td>
                                        <td><b>Beneficio Real = '.number_format($beneficioReal,2, '.', '').' €</b></td>
                                    </tr>'
                                ;
                            }
                
                    
                    
                        ?>
                  
                    </tbody>
                </table>


                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Entradas</th>
                            <th>Salidas</th>
                        
                            <th>Compras</th>
                            <th>Ventas</th>
                            <th>Beneficios</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php  
                            echo '<tr>
                                    <td><b>Total</b></td>
                                    <td><b>'.$entradas.'</b></td>
                                    <td><b>'.number_format($salidas,3, '.', '').'</b></td>
                                    <td><b>'.number_format($comprado,2, '.', '').'</b></td>
                                    <td><b>'.number_format($vendido,2, '.', '').'</b></td>
                                    <td><b>Beneficio = '.number_format($beneficio,2, '.', '').' €</b></td>
                                </tr>'
                            ;
                        
                            echo '<tr>
                                    <td><b>Especiales</b></td>
                                    <td><b>'.$entradasES.'</b></td>
                                    <td><b>'.number_format($salidasES,3, '.', '').'</b></td>
                                    <td><b>'.number_format($compradoES,2, '.', '').'</b></td>
                                    <td><b>'.number_format($vendidoEs,2, '.', '').'</b></td>
                                    <td><b>Beneficio Real = '.number_format($beneficioReal,2, '.', '').' €</b></td>
                                </tr>'
                            ;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
            <div class="col-md-3 col-md-pull-9">
                <div class="">
                    <?php echo $Controler->getHtmlLinkVolver();?>
                </div>
                <div class="">
                    <fieldset class="unidades">

                        <h1>Unidades</h1>
                        <table class="table table-bordered table-hover">
                            <caption title="Las suma contienen los especiales">Subtotal <b>unidades con</b>  especiales</caption>
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            
                            <tbody>
                                
                                <?php 
                                    foreach($datosMeses as $dato){
                                        colorTabla($dato['stock']);
                               
                                        echo "<td>". $dato['mes'] ."</td>";
                                        echo '<td>'.$dato['entrada'].'</td>';
                                        echo '<td>'.$dato['salida'].'</td>';
                                        echo '<td>'.$dato['stock'].'</td>';
                                        echo '</tr>';

                                    }
                                ?>
                                                    
                            </tbody>
                        
                        </table>

                        <table class="table table-bordered table-hover">
                            <caption title="Las sumas solo contienen los especiales">Subtotal <b>unidades especiales</b></caption>
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            
                            <tbody>
                                
                                <?php 
                                    foreach($datosMeses as $dato){
                                        colorTabla($dato['stockSubEs']);
                               
                                        echo "<td>".$dato['mes'] ."</td>";
                                        echo '<td>'.$dato['entradaSubEs'].'</td>';
                                        echo '<td>'.$dato['salidaSubEs'].'</td>';
                                        echo '<td>'.$dato['stockSubEs'].'</td>';
                                        echo '</tr>';

                                    }
                                ?>
                                                    
                            </tbody>
                        
                        </table>
                

                    </fieldset>
                    <fieldset class="importes">

                        <h1>Importes</h1>
                        <table class="table table-bordered table-hover">
                            <caption title="Las sumas contienen los especiales">Subtotal <b>importes con</b>  especiales</caption>                        
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Compra</th>
                                    <th>Venta</th>
                                    <th>Beneficio</th>
                                </tr>
                            </thead>
                            <tbody>
                        
                                <?php 
                                    foreach($datosMeses as $dato){
                                        colorTabla($dato['beneficio']);
                               
                                        echo "<td>".$dato['mes'] ."</td>";
                                        echo '<td>'.$dato['compras'].' €</td>';
                                        echo '<td>'.$dato['ventas'].' €</td>';
                                        echo '<td>'.$dato['beneficio'].' €</td>';
                                        echo '</tr>';

                                    }
                                ?>
                            
                        
                        
                            </tbody>
                        </table>

                        <table class="table table-bordered table-hover">
                            <caption title="Las sumas solo contienen los especiales">Subtotal <b>importes especiales</b></caption>
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Compra</th>
                                    <th>Venta</th>
                                    <th>Beneficio</th>
                                </tr>
                            </thead>
                            
                            <tbody>
                                
                                <?php 
                                    foreach($datosMeses as $dato){
                                        colorTabla($dato['beneficioEs']);
                               
                                        echo "<td>".$dato['mes'] ."</td>";
                                        echo '<td>'.$dato['comprasEs'].' €</td>';
                                        echo '<td>'.$dato['ventasEs'].' €</td>';
                                        echo '<td>'.$dato['beneficioEs'].' €</td>';
                                        echo '</tr>';

                                    }
                                ?>
                                                    
                            </tbody>
                        
                        </table>

                    </fieldset>
                    <fieldset class="sinEspeciales">

                        <h1>Unidades</h1>
                        <table class="table table-bordered table-hover">
                            <caption title="Las sumas no contienen los especiales">Subtotal <b>unidades sin</b> especiales</caption>                        
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                        
                                <?php 
                                    foreach($datosMeses as $dato){
                                        colorTabla(($dato['beneficio'] - $dato['beneficioEs']));

                               
                                        echo "<td>".$dato['mes'] ."</td>";
                                        echo '<td>'.($dato['entrada'] - $dato['entradaSubEs']).'</td>';
                                        echo '<td>'.($dato['salida'] - $dato['salidaSubEs']).'</td>';
                                        echo '<td>'.($dato['beneficio'] - $dato['beneficioEs']).'</td>';
                                        echo '</tr>';

                                    }
                                ?>
                            
                        
                        
                            </tbody>
                        </table>
                        
                        <h1>Importes</h1>
                        <table class="table table-bordered table-hover">
                            <caption title="Las sumas no contienen los especiales">Subtotal <b>importes sin</b> especiales</caption>
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Compra</th>
                                    <th>Venta</th>
                                    <th>Beneficio</th>
                                </tr>
                            </thead>
                            
                            <tbody>
                                
                                <?php 
                                    foreach($datosMeses as $dato){
                                        colorTabla(($dato['beneficio'] - $dato['beneficioEs']));
                               
                                        echo "<td>".$dato['mes'] ."</td>";
                                        echo '<td>'.($dato['compras'] - $dato['comprasEs']).' €</td>';
                                        echo '<td>'.($dato['ventas'] - $dato['ventasEs']).' €</td>';
                                        echo '<td>'.($dato['beneficio'] - $dato['beneficioEs']).' €</td>';
                                        echo '</tr>';

                                    }
                                ?>
                                                    
                            </tbody>
                        
                        </table>

                    </fieldset>
                </div>



                        
            </div>
        

      
        </div>
        
    
	</body>	
</html>


<?php 

function colorTabla($controlStock){
    if($controlStock <= 0){
        echo "<tr class='bg-warning'>";
    }else{
        echo "<tr>";
    }
   
}

function subMesTotal($vendidoMes, $compradoMes, $mes, $entradasMes , $salidasMes, $entregaSubEs,$salidasSubEs, $compradoSubEs,$vendidoSubEs){
    $beneficioMes = $vendidoMes - $compradoMes;
    $beneficioSubMes = $vendidoSubEs - $compradoSubEs;
                               
    $datosMeses['mes']=$mes;
    $datosMeses['entrada']=$entradasMes;
    $datosMeses['salida']= number_format($salidasMes,3, '.', '');
    $datosMeses['stock']= ($entradasMes - number_format($salidasMes,3, '.', ''));

    $datosMeses['compras']=number_format($compradoMes,2,'.', '');
    $datosMeses['ventas']=number_format($vendidoMes,2,'.', '');
    $datosMeses['beneficio']=number_format($beneficioMes,2,'.', '');

                                        
    $datosMeses['entradaSubEs'] = $entregaSubEs;
    $datosMeses['salidaSubEs'] = number_format($salidasSubEs,3, '.', '');
    $datosMeses['stockSubEs'] = ($entregaSubEs - number_format($salidasSubEs,3, '.',''));

    $datosMeses['comprasEs']=number_format($compradoSubEs,2,'.', '');
    $datosMeses['ventasEs']=number_format($vendidoSubEs,2,'.', '');
    $datosMeses['beneficioEs']=number_format($beneficioSubMes,2,'.', '');

                                       
                                                                     
    echo "<tr class='colorSubtotal'>
            <td colspan=2></td>                                        
            <td><b>Entradas</b></td>
            <td><b>Salidas</b></td>
            <td></td>
            <td><b>Compras</b></td>
            <td><b>Ventas</b></td>
            <td><b>Beneficios</b></td>
        </tr>"
    ;

    echo "<tr class='colorSubtotal'>
                                        
            <td colspan=2><b>Subtotal</b></td>
            <td><b>".$entradasMes."</b></td>
            <td><b>".number_format($salidasMes,3, '.', '')."</b></td>
            <td></td><td><b>".number_format($compradoMes,2,'.', '')."</b></td>
            <td><b>".number_format($vendidoMes,2,'.', '')."</b></td>
            <td><b>".number_format($beneficioMes,2,'.', '')." €</b></td>
        </tr>"
     ;

                                        

    return $datosMeses;


}

?>