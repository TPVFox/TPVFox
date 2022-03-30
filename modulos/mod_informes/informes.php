
   <?php 
   /* @Objetivo
     Obtener los datos para montar los informes necesarios.

   */  
    include_once './../../inicial.php';
    
	include_once $URLCom.'/modulos/mod_informes/funciones.php';
    include_once $URLCom.'/modulos/mod_informes/clases/ClaseInformes.php';
    $CInformes= new ClaseInformes();
    $DatosInforme = $CInformes->ObtenerdatosInforme();
    $cabecera = $DatosInforme['cabecera'];
    $datosInforme = $DatosInforme['datos'];
    echo '<pre>';
    print_r($cabecera);
    echo '</pre>';
   	?>
	
       <!DOCTYPE html>
<html>
    <head>
        <?php include_once $URLCom.'/head.php';?>
    </head>

<body>
        <?php
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
				<?php echo getHtmlTitulo($cabecera) ;?>
            </div>
            <div class="col-md-12">
                <?php echo getRangoFechas($cabecera) ;?>
            </div>
            <div><?php 
            foreach ($datosInforme['datos'] as $key=>$proveedor){
                if ($proveedor['idProveedor']== "158" || $proveedor['idProveedor']== "1" ) {
                    /* echo '<pre>';
                    print_r($proveedor['albaranes']);
                    echo '</pre>'; */
                }
               
                
            }
            echo '<pre>';
            print_r($datosInforme['informe']['suma_albaranes']);
            echo '</pre>';
                
            ?></div>
	       
			
	</div>
    
    <!-- Cargamos funciones de modulo. -->
    <script src="<?php echo $HostNombre; ?>/modulos/mod_informes/funciones.js" type="module"></script>	

</body>
</html>
