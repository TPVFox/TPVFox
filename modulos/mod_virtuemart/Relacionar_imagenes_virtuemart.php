
<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
    include_once $URLCom.'/head.php';
    include ($URLCom.'/controllers/Controladores.php');
    include_once ($URLCom.'/modulos/mod_virtuemart/funciones.php');
    include_once ($URLCom.'/modulos/mod_virtuemart/clases/ClaseVirtuemart.php');

    $CVirtuemart = new ClaseVirtuemart($BDTpv);
    
    $idTiendaWeb= $CVirtuemart->idTiendaWeb;
    


    if ( isset ($idTiendaWeb)){
        if ( $idTiendaWeb == 0){
            $errores[] = array ( 'tipo'=>'danger',
                                     'mensaje' => 'Hay mas de una empresa web',
                                     'dato' =>$tiendasWeb
                                );

        } 
    }
    // Obtenemos las relaciones de los productos tpv con virtuemart.
    $e =$CVirtuemart->productosTienda($idTiendaWeb);
    $TotalProd_Web_conRelacion = $e[0]['cantArticulo'];
    
    include_once ($URLCom.'/controllers/parametros.php');
    $ClasesParametros = new ClaseParametros('parametros.xml');
    $parametros = $ClasesParametros->getRoot();
    $conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
    // Ahora creamos las variables globales de javascript que necesitamos
        echo '<script type="text/javascript">'
            .'var totalReferenciasWeb = '.$TotalProd_Web_conRelacion.';'
            .'var reg_inicial = 0;'
            .'var img_encontradas = 0;'
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

            //~ echo '<pre>';
            //~ print_r($CVirtuemart->ObjVirtuemart->ruta_web);
            //~ echo '</pre>';ruta_web; ?>

        <h2 class="text-center">Relacionar imagenes con productos en virtuemart.</h2>
        <div class="col-md-6">
            <p>Los producto que vamos a revisar si tiene imagen son : <?php echo $TotalProd_Web_conRelacion;?>
            /<span id="reg_actual">0</span></p>
        </div>
        <div class="col-md-6">
            <p>Imagenes encontradas:<span id="img_encontradas"></span></p>
        </div>
        <div class="col-md-12">
            <div class="progress" style="margin:10px 100px">
                <div id="bar" class="progress-bar progress-bar-info" 
                     role="progressbar" aria-valuenow="0" 
                     aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                    0 % completado
                </div>
            </div>
        </div>
        <div class="col-md-12" id="DivOpciones">
             <a class="btn btn-primary" onclick="BuscarImagenes_producto()">Buscar Imagenes de productos</a>
        </div>
        <?php
            //  Primero contamos producto en la web:
            //~ echo '<pre>';
            //~ print_r($CVirtuemart);
            //~ echo '</pre>';
        ?>
      
       
    </div>

</body>
</html>
