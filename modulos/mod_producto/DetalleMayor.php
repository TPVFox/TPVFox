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

    $VarJS = $Controler->ObtenerCajasInputParametros($parametros).$OtrosVarJS;

  
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
        
        <script type="text/javascript">
            // Declaramos variables globales
            <?php echo $VarJS;?>
        </script>
        <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
        <style>
            .margenDivSuperior{
                margin:2vh;
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
            #buscar{
                display:none;
            }
            .tablasEstilos{
                display: flex;
                flex-direction: column-reverse;
            }
        </style>
	</head>
	<body>
	<?php
     include_once $URLCom.'/modulos/mod_menu/menu.php';
	?>
	<div class="container">
        <?php //Quito los 3 ultimos caracteres del titulo 'de ' ?>
		<h3 class="text-center"><?php echo substr($titulo, 0, -3);?></h3>


        <?php 

            //Datos para la tabla lateral        
            $numVeces = 0;
            $$datosMesesTabla = array();

        ?>
		
        <h2 class="text-center"><?php echo $producto['articulo_name'] ?></h2>
            <div id="error"><?php if($activarMensaje){echo $mensaje;} ?></div>

        <div class="col-md-12 margenDivSuperior">
        
            <div class="col-md-3">
                <?php echo $Controler->getHtmlLinkVolver();?>
            </div>
            <div class="col-md-2">
                    <strong>ID Articulo:</strong>
                    <input type="num" name="id_producto" size="10" id="id_producto" data-obj= "cajaIdDetalleMayor" onfocus=borrar() onkeydown="controlEventos(event)" onblur="controlEventos(event)" value="<?php echo $producto['idArticulo'];?>" >
            </div>
            <div class="col-md-2">
                    <strong>Fecha Inicial:</strong>
                    <input type="date" name="fecha" id="fecha_inicial" size="10" onblur=visibleButton() value="<?php echo $fecha_inicial;?>"  >
            </div>
            <div class="col-md-2">
                    <strong>Fecha Final:</strong>
                    <input type="date" name="fecha" id="fecha_final" size="10" onblur=visibleButton() value="<?php echo $fecha_final;?>">
            </div>
            <div class="col-md-1">
                <button id="buscar"  class="btn btn-primary" onclick=clikMayor(event)>Buscar</button>
            </div>            
        </div>

		<div class="col-md-9 col-md-push-3 tablasEstilos">           
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
                                        
                                        
                                       
                                        if($producto['tipo'] == 'peso'){
                                            //Subtotales por meses en listado
                                            $HtmlSubtotalesMeses = tablasSubMes('colorSubtotal',$vendidoMes, $compradoMes, $entradasMes , $salidasMes, $vendidoSubEs, 3);                                            
                                            // Guardamos datos de esos subtotales por meses
                                            $datosMesesTabla[0][$mes] = guardarDatosTablasLaterales($entradasMes , $salidasMes, $entregaSubEs, $salidasSubEs,3);
                                        }else{
                                            $HtmlSubtotalesMeses = tablasSubMes('colorSubtotal',$vendidoMes, $compradoMes, $entradasMes , $salidasMes, $vendidoSubEs, 0);
                                            $datosMesesTabla[0][$mes] = guardarDatosTablasLaterales($entradasMes , $salidasMes, $entregaSubEs, $salidasSubEs,0);
                                            
                                        } 
                                        $datosMesesTabla[1][$mes] = guardarDatosTablasLaterales($compradoMes,$vendidoMes, $compradoSubEs, $vendidoSubEs, 2);
                                                            
                                        echo $HtmlSubtotalesMeses;
                                        
                                        $entradasMes =0;
                                        $salidasMes = 0;
                                        $compradoMes=0;
                                        $vendidoMes =0;
                                       
                                        
                                        $entregaSubEs = 0;
                                        $salidasSubEs = 0;
                                        $compradoSubEs =0;
                                        $vendidoSubEs = 0;

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
                                        $color = ($stock> 0 ? '':' class="bg-warning"');
                                        echo '<tr'.$color.'>';
                                
                                    }
                           
                                    echo "<td class='centrarDomingos'><b>".$domingo."</b></td>
                                        <td>".$movimiento['fecha']."</td>";
                                    echo $td_entrada.$td_salida;
                                    echo '<td>'.$stock.'</td>';
                                    echo $td_coste.$td_precio;
                                    echo '<td>'.$movimiento['serie'].$movimiento['numdocu'].'</td>';
                                    echo '<td>'.$movimiento['nombre'].'</td>';
                                    echo '<td>'.$movimiento['estado'].'</td>';
                                
                                    echo '<td>'.'<a target="_blank" href="'.$url.'"><span class="glyphicon glyphicon-eye-open"></span></a></td>';
                                    echo '</tr>';
                        }   
                                //Tabla submes
                                if($producto['tipo'] == 'peso'){
                                    //Subtotales por meses en listado
                                    $HtmlSubtotalesMeses = tablasSubMes('colorSubtotal',$vendidoMes, $compradoMes, $entradasMes , $salidasMes, $vendidoSubEs, 3);                                            
                                    // Guardamos datos de esos subtotales por meses
                                    $datosMesesTabla[0][$mes] = guardarDatosTablasLaterales($entradasMes , $salidasMes, $entregaSubEs, $salidasSubEs,3);
                                }else{
                                    $HtmlSubtotalesMeses = tablasSubMes('colorSubtotal',$vendidoMes, $compradoMes, $entradasMes , $salidasMes, $vendidoSubEs, 0);
                                    $datosMesesTabla[0][$mes] = guardarDatosTablasLaterales($entradasMes , $salidasMes, $entregaSubEs, $salidasSubEs,0);
                                    
                                } 
                                $datosMesesTabla[1][$mes] = guardarDatosTablasLaterales($compradoMes,$vendidoMes, $compradoSubEs, $vendidoSubEs, 2);

                               
                                 echo $HtmlSubtotalesMeses;
                            }
                
                    
                    
                        ?>
                  
                    </tbody>
                </table>
                <div >
                <?php  // Calculo del beneficio.


                    if($producto['tipo'] == 'peso'){
                        $resultado = tablaTotal('table table-bordered table-hover',$vendido, $comprado, $vendidoEs, 
                                $compradoES, $entradas, $salidas,$entradasES, $salidasES, 3);
                    }else{
                        $resultado = tablaTotal('table table-bordered table-hover',$vendido, $comprado, $vendidoEs, 
                                $compradoES, $entradas, $salidas,$entradasES, $salidasES, 0);    
                    } 

                    
                    echo $resultado;
                ?>
                </div >

                
            </div>
            <div class="col-md-12">
                    <?php //Tabla resumen Arriba                 
                        if($producto['tipo'] == 'peso'){
                            $tablaTotalArriba = tablaTotal('table table-bordered table-hover',$vendido, $comprado, $vendidoEs, 
                                    $compradoES, $entradas, $salidas,$entradasES, $salidasES, 3);
                        }else{
                            $tablaTotalArriba = tablaTotal('table table-bordered table-hover',$vendido, $comprado, $vendidoEs, 
                                    $compradoES, $entradas, $salidas,$entradasES, $salidasES, 0);    
                        } 
                        echo $tablaTotalArriba; 
                    ?>
                </div>
        </div>
            <div class="col-md-3 col-md-pull-9 ">
                    <fieldset class="unidades">
                        <h1>Unidades</h1>
                        <table class="table table-bordered table-hover">
                            <caption title="Las suma contienen los especiales">Subtotal <b>unidades con</b>  especiales</caption>                        
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Diferencias</th>
                                </tr>
                            </thead>
                            <tbody>
                        
                                <?php 
                                    $tablaLateral = tablasLateral($datosMesesTabla[0],0);
                                    echo $tablaLateral;
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
                                    <th>Diferencias</th>
                                </tr>
                            </thead>                            
                            <tbody>
                                
                                <?php                                 
                                    $tablaLateral1 = tablasLateral($datosMesesTabla[0],1);
                                    echo $tablaLateral1;
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
                                    $tablaLateral2 = tablasLateral($datosMesesTabla[1],0);
                                    echo $tablaLateral2;
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
                                    $tablaLateral3 = tablasLateral($datosMesesTabla[1],1);
                                    echo $tablaLateral3;
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
                                    $tablaLateral3 = tablasLateral($datosMesesTabla[0],2);
                                    echo $tablaLateral3;                            
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
                                    $tablaLateral4 = tablasLateral($datosMesesTabla[1],2);
                                    echo $tablaLateral4;
                                ?>                                                    
                            </tbody>
                        
                        </table>

                    </fieldset>
                



                        
            </div>
        

      
        </div>
        
    
	</body>	
</html>


<?php 
