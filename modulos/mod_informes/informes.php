
   <?php 
   /* @Objetivo
     Obtener los datos para montar los informes necesarios.

   */  
    include_once './../../inicial.php';
	include_once $URLCom.'/modulos/mod_informes/funciones.php';
    include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
    include_once $URLCom.'/modulos/mod_informes/clases/ClaseInformes.php';
    $CInformes= new ClaseInformes();
    $CTArticulos = new ClaseProductos($BDTpv);
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
            <div>
                    <table class="table table-striped table-bordered table-hover">
					<thead>
						<tr>
							<th>ID</th>
							<th>PRODUCTO</th>
							<th>NºVECES</th>
							<th>CANTIDAD</th>
							<th>COSTE</th>
							<th title= "Si cambio el precio, se calcula coste medio, marcado *">CM</th>
							<th>IMPORTE</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$totalLineas = 0;
						if(isset($_GET['Finicio']) & isset($_GET['Ffinal'])){
							foreach ($datosInforme['informe']['productos'] as $producto) {
								$totalLineas += $producto['total_linea'];
                                $p =$CTArticulos->GetProducto($producto['idArticulo']);
                                $cdetalle = $p['articulo_name'];
                                $tipo = $p['tipo'];
							?>
							<tr>
								<td><?php echo $producto['idArticulo'];?></td>
								<td><?php echo $cdetalle;?></td>
								<td><?php echo $producto['num_compras'];?></td>
								
								<td><?php
									if ($tipo == 'peso'){
										echo number_format($producto['totalUnidades'],3);
									} else {
										echo number_format($producto['totalUnidades'],0);
									};?></td>
								<td><?php echo number_format($producto['costeSiva'],2);?></td>
								<td><?php
								if ($producto['coste_medio'] == 'OK'){
									echo '*';
								}
								?></td>
								<td><?php echo number_format($producto['total_linea'],2);?></td>
							</tr>
						<?php
							}
						}
						?>
					</tbody>
				</table>             
            <?php    
            
            // Sumamos deglose.
          /*   echo '<pre>';
            print_r($datosInforme['informe']['productos']);
            echo '</pre>'; */
            ?></div>
	       
			
	</div>
    
    <!-- Cargamos funciones de modulo. -->
    <script src="<?php echo $HostNombre; ?>/modulos/mod_informes/funciones.js" type="module"></script>	

</body>
</html>
