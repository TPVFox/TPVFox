
<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
    include_once $URLCom.'/head.php';
    include ($URLCom.'/controllers/Controladores.php');
    include_once ($URLCom.'/modulos/mod_virtuemart/funciones.php');
    include_once ($URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php');
    include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
    $CTArticulos = new ClaseProductos($BDTpv);
    $ClaseTienda=new ClaseTienda($BDTpv);
	$tiendasWeb=$ClaseTienda->tiendasWeb();
    include_once ($URLCom.'/controllers/parametros.php');
    $ClasesParametros = new ClaseParametros('parametros.xml');
    $parametros = $ClasesParametros->getRoot();
    $conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
   ?>
<script src="<?php echo $HostNombre; ?>/modulos/mod_virtuemart/funciones.js"></script>
<script src="<?php echo $HostNombre; ?>/controllers/funcionesComunes.js"></script>

<?php 
 if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false){
        $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');
        echo $ObjVirtuemart->htmlJava();
    }
?>
</head>
<body>
      <?php
            include_once $URLCom.'/modulos/mod_menu/menu.php';
            
        ?>
    <div class="container">
        <h2 class="text-center">Acciones para realizar en Virtuemart.</h2>
        <div class="col-md-3">
            <div style="margin: 1%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
                <h3>Importación o Actualizacion de datos de Virtuemart</h3>
                <p> Podrás importación o actualizacion de datos de Virtuemart a TPV:</p>
                <ol>
                    <li>Añadir los producto que hay virtuemart que no tiene relacion tpv</li>
                    <li>Modificar los datos de tpv obteniendo los datos virtuemart</li>
                </ol> 
                <p><a href="Importar_virtuemart.php">Empezar</a></p>
            </div>
        </div>
        <div class="col-md-3">
            <div style="margin: 1%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
                <h3>Relacionar las imagenes con el producto</h3>
                <p> Lo que realizamos es :</p>
                <p> Obtener todos los productos de virtuemart, ver si no tiene imagen , buscamo en los registros de la tabla de media de virtuemart si existe alguna imagen con nombre que sea el id de tpv</p>
                <p><a href="Relacionar_imagenes_virtuemart.php">Empezar</a></p>
            </div>
        </div>
        <div class="col-md-3">
            <div style="margin: 1%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
                <h3>Campo personalizado a los producto que son peso</h3>
                <p> Obtener los productos de tpv que son peso</p>
                <p> Marcamos los pesos por los queremos venderlos en la tienda online, al procesar comprobamos si existe o no, si no existe entonceslo lo creamos.</p>
                <p><a href="Subir_campospersonalizado_peso_virtuemart.php">Empezar</a></p>
            </div>
        </div>
        
    </div>
</body>
</html>
