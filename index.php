<?php
	/*
	 *  @Aplicacion    TpvFox (Soluciones Vigo)
	 *  @copyright  Copyright (C) 2017 - 2018  Soluciones Vigo.
	 *  @license    GNU General Public License version 2 or later; see LICENSE.txt
	 * */
?>

<!DOCTYPE html>
<html>
<head>
<?php
	include 'head.php';?>
</head>
<body>
	<?php 
     include_once $URLCom.'/modulos/mod_menu/menu.php';
	$titulos=array('Almacén', 'Compras', 'Ventas', 'Tickets', 'Contactos', 'Sistema');
	$links=array( '0'=>array(
					'icono'=>'css/img/productosP.png',
					'link'=>'modulos/mod_producto/ListaProductos.php',
                    'modulo'=>'mod_producto',
                    'vista'=>'ListaProductos.php',
					'texto'=>'Productos',
					'permmiso'=>0
					),
				'1'=>array(
					'icono'=>'css/img/familiasP.png',
					'link'=>'modulos/mod_familia/ListaFamilias.php',
                     'modulo'=>'mod_familia',
                    'vista'=>'ListaFamilias.php',
					'texto'=>'Familias',
					'permmiso'=>1
					),
				'2'=>array(
					'icono'=>'css/img/lotesP.png',
					'link'=>'modulos/mod_etiquetado/ListadoEtiquetas.php',
                    'modulo'=>'mod_etiquetado',
                    'vista'=>'ListadoEtiquetas.php',
					'texto'=>'Lotes',
					'permmiso'=>1
					),
				'3'=>array(
					'icono'=>'css/img/pedidosComP.png',
					'link'=>'modulos/mod_compras/pedidosListado.php',
                    'modulo'=>'mod_compras',
                    'vista'=>'pedidosListado.php',
					'texto'=>'Pedidos',
					'permmiso'=>0
					),
				'4'=>array(
					'icono'=>'css/img/albaranComP.png',
					'link'=>'modulos/mod_compras/albaranesListado.php',
                    'modulo'=>'mod_compras',
                    'vista'=>'albaranesListado.php',
					'texto'=>'Albaranes',
					'permmiso'=>0
					),
				'5'=>array(
					'icono'=>'css/img/facturasComP.png',
					'link'=>'modulos/mod_compras/facturasListado.php',
                    'modulo'=>'mod_compras',
                    'vista'=>'facturasListado.php',
					'texto'=>'Facturas',
					'permmiso'=>0
					),
				'6'=>array(
					'icono'=>'css/img/PedidosVenP.png',
					'link'=>'modulos/mod_venta/pedidosListado.php',
                    'modulo'=>'mod_venta',
                    'vista'=>'pedidosListado.php',
					'texto'=>'Pedidos',
					'permmiso'=>0
					),	
				'7'=>array(
					'icono'=>'css/img/albaranVenP.png',
					'link'=>'modulos/mod_venta/albaranesListado.php',
                    'modulo'=>'mod_venta',
                    'vista'=>'albaranesListado.php',
					'texto'=>'Albaranes',
					'permmiso'=>0
					),	
				'8'=>array(
					'icono'=>'css/img/facturaVenP.png',
					'link'=>'modulos/mod_venta/facturasListado.php',
                    'modulo'=>'mod_venta',
                    'vista'=>'facturasListado.php',
					'texto'=>'Facturas',
					'permmiso'=>0
					),	
				'9'=>array(
					'icono'=>'css/img/cajaP.png',
					'link'=>'modulos/mod_tpv/tpv.php',
                    'modulo'=>'mod_tpv',
                    'vista'=>'tpv.php',
					'texto'=>'Caja',
					'permmiso'=>0
					),	
				'10'=>array(
					'icono'=>'css/img/cierresP.png',
					'link'=>'modulos/mod_cierres/CierreCaja.php?dedonde=tpv',
                     'modulo'=>'mod_cierres',
                    'vista'=>'CierreCaja.php',
					'texto'=>'Cierres',
					'permmiso'=>0
					),
				'11'=>array(
					'icono'=>'css/img/ticketsP.png',
					'link'=>'modulos/mod_tpv/ListaTickets.php?estado=Cobrado',
                     'modulo'=>'mod_tpv',
                    'vista'=>'ListaTickets.php',
					'texto'=>'Tickets',
					'permmiso'=>0
					),	
				'12'=>array(
					'icono'=>'css/img/usuariosP.png',
					'link'=>'modulos/mod_usuario/ListaUsuarios.php',
                    'modulo'=>'mod_usuario',
                    'vista'=>'ListaUsuarios.php',
					'texto'=>'Usuarios',
					'permmiso'=>9
					),
				'13'=>array(
					'icono'=>'css/img/clientesP.png',
					'link'=>'modulos/mod_cliente/ListaClientes.php',
                    'modulo'=>'mod_cliente',
                    'vista'=>'ListaClientes.php',
					'texto'=>'Clientes',
					'permmiso'=>1
					),
				'14'=>array(
					'icono'=>'css/img/proveedoresP.png',
					'link'=>'modulos/mod_proveedor/ListaProveedores.php',
                    'modulo'=>'mod_proveedor',
                    'vista'=>'ListaProveedores.php',
					'texto'=>'Proveedores',
					'permmiso'=>1
					),
				'15'=>array(
					'icono'=>'css/img/tiendaP.png',
					'link'=>'modulos/mod_tienda/ListaTiendas.php',
                     'modulo'=>'mod_tienda',
                    'vista'=>'ListaTiendas.php',
					'texto'=>'Tiendas',
					'permmiso'=>9
					),
				'16'=>array(
					'icono'=>'css/img/incidenciasP.png',
					'link'=>'modulos/mod_incidencias/ListadoIncidencias.php',
                     'modulo'=>'mod_incidencias',
                    'vista'=>'ListadoIncidencias.php',
					'texto'=>'Incidencias',
					'permmiso'=>9
					),
				'17'=>array(
					'icono'=>'css/img/tablasP.png',
					'link'=>'modulos/mod_configuracion/tablasPrincipales.php',
                    'modulo'=>'mod_configuracion',
                    'vista'=>'tablasPrincipales.php',
					'texto'=>'Tablas',
					'permmiso'=>9
					),
				);
	?>
	
	
		<div class="container">
			<div class="col-md-8">
				<h1>TPVfox</h1>
			</div>
			<div class="col-md-12 row">
				<h2 class="text-center">Accesos Directos</h2>
				<?php 
				$t=0;
				$i=0;
				$c=0;
                $perm=0;
				foreach ($links as $link){
					if($c==0){
						echo '<div class="col-md-12 row">';
					
					}
					if($i==0){
						echo '<div class="col-md-4" >';
						echo '<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">';
						echo '<h4 class="text-center">'.$titulos[$t].'</h4>';
					}
				echo '<div class="col-md-4 text-center ">
						<h6 class="text-center">'.$link['texto'].'</h6>';
				
                foreach ($Permisos['resultado'] as $permiso){
                   
                    if($link['vista']==$permiso['vista'] & $permiso['accion']==''){
                       //~ echo $permiso['accion'];
                        $perm=$permiso['permiso'];
                       
                    }
                }
             if($perm==1){
            
				echo '<a class="text-center" title="'.$link['texto'].'" href="'.$link['link'].'"><img  src="'.$link['icono'].'" alt="'.$link['texto'].'" /></a>';
			}else{
				echo '<img  style="opacity:0.2" src="'.$link['icono'].'" alt="'.$link['texto'].'" />';
			}
				
				echo '</div>';
					
					$i++;
					if($i==3){
						$i=0;
						$t=$t+1;
						echo '</div>';
						echo '</div>';
					}
					$c++;
					if($c==9){
						$c=0;
						echo '</div>';
						
					}
				}
				?>
				</div>
				</div>
				
			</div>
<!--
			<div id="col-md-12">
				<p>Está aplicación es OPEN SOURCE, con ello queremos decir que puedes utilizar este código en otras aplicaciones y modificarlo sin problemas.</p>
			</div>
-->
			
		</div>
        
	</div>

    <?php 
    include_once $URLCom.'/pie.php';
    ?>
</body>
</html>
