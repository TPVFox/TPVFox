<?php 
include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
include_once $URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php';
$CTArticulos = new ClaseProductos($BDTpv);
$CProveedor= new ClaseProveedor();

$datosProveedor=$CProveedor->getProveedor($_POST['idProveedor']);
$nombrecomercial=$datosProveedor['datos'][0]['nombrecomercial'];
$razonsocial=$datosProveedor['datos'][0]['razonsocial'];

$cabecera=<<<EOD
<p></p><font size="20">Listado de Productos como principal Proveedor: </font><br/>
<strong>Nombre Comercial:</strong>$nombrecomercial<br/>
<strong>Razón Social:</strong>$razonsocial<br/>
EOD;

$html=<<<EOD
<table WIDTH="140%" border="1px">
    <tr>
        <td WIDTH="8%">ID</td>
        <td>Nombre Producto</td>
       
        
        <td WIDTH="8%">Precio</td>
        <td>Código de Barras</td>
        <td WIDTH="8%">Stock</td>
        <td>Estado</td>
        </tr>
   
EOD;
foreach ($_POST['productos'] as $producto ){
    $p = $CTArticulos->GetProducto($producto);
    $codigo="";
    
    foreach ($p['codBarras'] as $codBarras){
            $codigo.=$codBarras.' ';
                                    
    }
    $stock=number_format($p['stocks']['stockOn'],3);
    $precio=number_format($p['pvpCiva'],2);
    $html.=<<<EOD
        <tr>
        <td>$producto</td>
        <td>$p[articulo_name]</td>
        
       
        <td>$precio €</td>
        <td>$codigo</td>
        <td>$stock</td>
        <td>$p[estado]</td>
        </tr>
EOD;
}
$html.=<<<EOD
</table>
   
EOD;

        $nombreTmp="Listado.pdf";
        $margen_top_caja_texto= 50;
		require_once  ($URLCom.'/clases/imprimir.php');
		require_once($URLCom.'/controllers/planImprimir.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$resultado=$ficheroCompleto;
?>
