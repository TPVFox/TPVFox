
<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
    include_once $URLCom.'/head.php';
    include ($URLCom.'/controllers/Controladores.php');
    include_once ($URLCom.'/modulos/mod_virtuemart/funciones.php');
    include_once ($URLCom.'/modulos/mod_virtuemart/clases/ClaseVirtuemart.php');
    
    if ( isset($ProductoPorPeso['error'])){
        $errores = $ProductoPorPeso['error'];
        $errores[0]['tipo'] = 'danger';
    }
    //~ echo '<pre>';
    //~ print_r($ProductoPorPeso);
    //~ echo '</pre>';
    $CVirtuemart = new ClaseVirtuemart($BDTpv);
    
    $idTiendaWeb= $CVirtuemart->idTiendaWeb;
    
    // Obtener ids de productos tipo peso recuerda que extiende clase productos.
    
    $ProductoPorPeso = $CVirtuemart->ObtenerIdProductoPorTipo('Peso');

    if ( isset ($idTiendaWeb)){
        if ( $idTiendaWeb == 0){
            $errores[] = array ( 'tipo'=>'danger',
                                     'mensaje' => 'Hay mas de una empresa web',
                                     'dato' =>$tiendasWeb
                                );

        } 
    }
    
    
    include_once ($URLCom.'/controllers/parametros.php');
    $ClasesParametros = new ClaseParametros('parametros.xml');
    $parametros = $ClasesParametros->getRoot();
    $conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
    // Ahora creamos las variables globales de javascript que necesitamos
        echo '<script type="text/javascript">'
            .'var totaProductoPeso = '.count($ProductoPorPeso['Items']).';'
            .'var reg_inicial = 0;'
            .'var SinIDVirtuemart = 0;'
            .'var Ids = '.json_encode($ProductoPorPeso['Items']).';'
            .'</script>'
   ?>
<script src="<?php echo $HostNombre; ?>/modulos/mod_virtuemart/funciones.js"></script>
<script src="<?php echo $HostNombre; ?>/controllers/funcionesComunes.js"></script>


</head>
<body>
      <?php
            include_once $URLCom.'/modulos/mod_menu/menu.php';
            
        ?>
    <div class="container">
    <?php 
			if (isset($errores)){ 
				foreach ($errores as $error){
                    $e = 'OK';
                    echo '<div class="alert alert-'.$error['tipo'].'">'.$error['mensaje'].'</div>';
                    if( $error['tipo']=== 'danger'){
                        $e = 'KO';
                    }
                }
				if ($e ='KO'){
                    // No permito continuar, ya que hubo error grabe.
                    echo '<pre>';
                    print_r($error['dato']);
                    echo '</pre>';
                    return;
				}
			}
    ?>

        <h2 class="text-center">Añadir campos personalizados productos por peso en la tienda online.</h2>
        <div class="col-md-12">
            <p>Los producto que sea de peso se le añade 3 campos personalizado por peso ( 100 grs, 200 grs y 500 grs): <?php echo count($ProductoPorPeso['Items']);?>
            /<span id="reg_actual">0</span></p>
        </div>
        <div class="col-md-6">
            <p>
                <b>Productos sin id en virtuemart:</b><span id="SinIdVirtuemar"></span><br/>
                
            </p>
            
        </div>
        <div class="col-md-6">
            <div class="progress" style="margin:10px 100px">
                <div id="bar" class="progress-bar progress-bar-info" 
                     role="progressbar" aria-valuenow="0" 
                     aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                    0 % completado
                </div>
            </div>
        </div>
        <div class="col-md-12" id="DivOpciones">
             <a class="btn btn-primary" onclick="AnhadirCamposPersonalidosIdPeso()">Añadir campos personalizados a productos por peso</a>
        </div>
        <?php
            
        ?>
      
       
    </div>

</body>
</html>
