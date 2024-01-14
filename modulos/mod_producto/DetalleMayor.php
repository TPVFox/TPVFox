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
    $titulo="Listado de mayor ";   
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
        if (!isset($_GET['stock'])){
            $stock = 0;
        }else{
            $stock = $_GET['stock'];
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

    $VarJS = $Controler->ObtenerCajasInputParametros($parametros);

  
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
        <h3 class="text-center"><?php echo $titulo;?></h3>


        <?php 

            //Datos para la tabla lateral        
            $numVeces = 0;
            $datosMesesTabla = array();
            $datosGuardar = array();
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
                        <th>Stock<span class="glyphicon glyphicon-info-sign" title="El stock inicial es <?php echo $stock;?>"></span></th>
                        <th>Coste <br/>Sin Iva</th>
                        <th>PVP</th>
                        <th>doc</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                    </tr>
                    </thead>
                    <tbody>
                <?php
                    if (isset($movimientos['datos'])){
                        
                        $mismoMes=False;
                        $mes = 0;
                        $importes = array();
                        $datosMes = array();        
                        $datosMesEs = array();                               
                        $e = 0;// variable bandera para indicar decimales
                        if ($producto['tipo'] ==='peso'){   
                            $e = 3;
                        }

                        foreach ($movimientos['datos'] as $movimiento){
                            $tipo_doc= '';
                            $url = '';
                            $td_entrada = '<td></td>';
                            $td_salida = '<td></td>';
                            $td_precio = '<td></td>';
                            $td_coste = '<td></td>';
                            if ($mes== 0){
                                $mes =  Date("m", strtotime($movimiento['fecha']));
                            }
                            if ( $movimiento['precioentrada'] !== 0){
                                $precio_coste_civa = $movimiento['precioentrada']+($movimiento['precioentrada']*$producto['iva'])/100;
                            }
                            $movimiento['compras'] = $movimiento['entrega'] * $precio_coste_civa;
                            $movimiento['ventas']= $movimiento['salida']*$movimiento['preciosalida'];
                            

                            
                            $dia = Date("w",strtotime($movimiento['fecha'])); // Obtengo un numero de la semana (0-6)
                           
                            if($mes <> Date("m", strtotime($movimiento['fecha']))){
                                // Solo entra cuando el mes cambia
                                $datosMes = array_reduce($datosMes,
                                    function ($result, $item) {
                                        if (!isset($result['entrega'])){
                                                $result = array('entrega'=>0,'salida'=>0,'compras'=>0,'ventas'=>0);
                                        }
                                        $result['entrega'] +=  $item['entrega'];
                                        $result['salida'] += $item['salida'];
                                        $result['compras'] +=  $item['compras'];
                                        $result['ventas'] +=  $item['ventas'];
                                        return $result;
                                    }
                                );
                               
                                if (count($datosMesEs)>0){
                                    $datosMesEs = array_reduce($datosMesEs,
                                        function ($result, $item) {
                                            if (!isset($result['entrega'])){      
                                                    $result = array('entrega'=>0,'salida'=>0,'compras'=>0,'ventas'=>0);
                                            }
                                            $result['entrega'] +=  $item['entrega'];
                                            $result['salida'] += $item['salida'];
                                            $result['compras'] +=  $item['compras'];
                                            $result['ventas'] +=  $item['ventas'];
                                            return $result;
                                        }
                                    ); 
                                } else {
                                    $datosMesEs = array('entrega'=>0,'salida'=>0,'compras'=>0,'ventas'=>0);
                                }    
                                $datosGuardar[$mes]['cantidades'] = guardarDatosUnidades($datosMes,$datosMesEs,$e);
                                $datosGuardar[$mes]['importes'] = guardarDatosImportes($datosMes,$datosMesEs,$e);                                
                                                              

                                $HtmlSubtotalesMeses = tablasSubMes('colorSubtotal', $datosGuardar[$mes], $e);
                                    
                                echo $HtmlSubtotalesMeses;

                                $datosMes = array();
                                $datosMesEs = array();

                                
                                $mes = Date("m", strtotime($movimiento['fecha']));

                               
                            }
                           
                            array_push($datosMes,$movimiento);                            

                            if($movimiento['estadoCliente'] == "Especial"){
                                array_push($datosMesEs,$movimiento);                                



                            }




                            

                            
                          
                            if ($movimiento['tipodoc']=== 'C'){
                                // Entonces es una entrada
                                $tipo_doc   = 'mod_compras/albaran.php?id='.$movimiento['numid'].'&accion=ver';
                                $td_entrada = '<td>'.number_format(round($movimiento['entrega'],$e),$e).'</td>';
                                $td_coste  = '<td>'.number_format($movimiento['precioentrada'],2).' €'.'</td>';
                                $stock += $movimiento['entrega'];

                                        


                                        


                                                                
                            } else {
                                $stock -= $movimiento['salida'];
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



                            }
                            
                            $url= $HostNombre.'/modulos/'.$tipo_doc;
                            

                            //Si es 0 significa que es domingo
                            $domingo = "";
                            if($dia == 0){
                                $domingo = "X";
                            }
                   
                            if($movimiento['estadoCliente'] == "Especial"){
                                echo "<tr class='bg-danger'>";
                                                       
                            }else{
                                $color = ($stock> 0 ? '':' class="bg-warning"');
                                echo '<tr'.$color.'>';
                        
                            }
                   
                            echo "<td class='text-center'><b>".$domingo."</b></td>
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


                        $datosMes = array_reduce($datosMes,function ($result, $item) {
                            if (!isset($result['entrega'])){
                                                $result = array('entrega'=>0,'salida'=>0,'compras'=>0,'ventas'=>0);
                                        }
                            $result['entrega'] +=  $item['entrega'];
                            $result['salida'] += $item['salida'];
                            $result['compras'] +=  $item['compras'];
                            $result['ventas'] +=  $item['ventas'];
                            return $result;
                        });
                        if (count($datosMesEs)>0){
                            $datosMesEs = array_reduce($datosMesEs,function ($result, $item) {
                                if (!isset($result['entrega'])){
                                                    $result = array('entrega'=>0,'salida'=>0,'compras'=>0,'ventas'=>0);
                                            }
                                $result['entrega'] +=  $item['entrega'];
                                $result['salida'] += $item['salida'];
                                $result['compras'] +=  $item['compras'];
                                $result['ventas'] +=  $item['ventas'];
                                return $result;
                            });
                       } else {
                           $datosMesEs = array('entrega'=>0,'salida'=>0,'compras'=>0,'ventas'=>0);
                       }  
                        $datosGuardar[$mes]['cantidades'] = guardarDatosUnidades($datosMes,$datosMesEs,$e);
                        $datosGuardar[$mes]['importes'] = guardarDatosImportes($datosMes,$datosMesEs,$e);

                        $HtmlSubtotalesMeses = tablasSubMes('colorSubtotal', $datosGuardar[$mes], $e);
                                                                             
                                

                                         
                                echo $HtmlSubtotalesMeses;

                                
                            }
                
                    
                    
                        ?>
                  
                    </tbody>
                </table>
                <div >
                <?php  //Calculo del total.

                $datosMesTotal = array_reduce($datosGuardar,function ($result, $item) {
                    if (!isset($result['entrega'])){
                        $result = array('entrega'=>0,'salida'=>0,'compras'=>0,'ventas'=>0,'beneficio'=>0,'entregaES'=>0,'salidaES'=>0,'comprasES'=>0,'ventasES'=>0,'beneficioES'=>0);
                    }
                    $result['entrega'] +=  $item['cantidades'][0][0];
                    $result['salida'] += $item['cantidades'][0][1];
                    $result['compras'] +=  $item['importes'][0][0];
                    $result['ventas'] +=  $item['importes'][0][1];
                    $result['beneficio'] +=  $item['importes'][0][2];
                    //~ if (!isset($result['entregaES'])){
                        //~ $result = array('entregaES'=>0,'salidaES'=>0,'comprasES'=>0,'ventasES'=>0,'beneficioES'=>0);
                    //~ }
                    $result['entregaES'] +=  $item['cantidades'][1][0];
                    $result['salidaES'] += $item['cantidades'][1][1];
                    $result['comprasES'] +=  $item['importes'][1][0];
                    $result['ventasES'] +=  $item['importes'][1][1];
                    $result['beneficioES'] +=  $item['importes'][1][2];

                    return $result;
                });
                    
                    $resultado = tablaTotal('table table-bordered table-hover',$datosMesTotal, $e);                     
                    echo $resultado;
                ?>
                </div >

                
            </div>
            <div class="col-md-12">
                    <?php //Tabla resumen Arriba                 
                         echo $resultado; 
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
                                    $tablaLateral = tablasLateral($datosGuardar,'cantidades',0);
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
                                    $tablaLateral1 = tablasLateral($datosGuardar,'cantidades',1);
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
                                    $tablaLateral2 = tablasLateral($datosGuardar,'importes',0);
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
                                    $tablaLateral3 = tablasLateral($datosGuardar,'importes',1);
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
                                    $tablaLateral3 = tablasLateral($datosGuardar,'cantidades',2);
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
                                    $tablaLateral4 = tablasLateral($datosGuardar,'importes',2);
                                    echo $tablaLateral4;
                                ?>                                                    
                            </tbody>
                        
                        </table>

                    </fieldset>
                



                        
            </div>
        

      
        </div>
        
    
    </body> 
</html>


