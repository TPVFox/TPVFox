<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion Importar ficheros de DBF
 *  */
        // Objetivo de esta aplicacion es:
        //  - Copiar DBF y guardar en directorio de copias de seguridad.
        //  - Importar los datos copiados a MYSQL.
        //  - Poner estado importado si es un producto nuevo.
        //  - Poner estado actualizaco si cambio alguno de los campos siguiente
        //              - Precio
        //              - Nombre
        //              - Stock

     
include_once './../../inicial.php';
include_once './clases/ClaseImportarDbf.php';
$ruta_segura = $thisTpv->getRutaSegura();
$importarDbf = new ImportarDbf($ruta_segura);
$link = '';
// Ultimo registro
$dregistro = $importarDbf->ultimoRegistro();
$datos_registro =$dregistro['datos'][0];
if ($datos_registro['id'] >0){
    $link = '<a href="importarDBF.php">Ir ver información importar</a>';
}
?>
<!DOCTYPE html>
<html>
<head>
<?php
    include_once $URLCom.'/head.php';
?>

</head>
<body>
<?php 
     include_once $URLCom.'/modulos/mod_menu/menu.php';
?>



<div class="container">
    <div class="col-md-12">
        <?php echo $link; ?>
        <h1>Subir fichero de articulos</h1>
        <p>Subimos fichero dbf no puede exceder lo indica tu php.ini</p>
        <form method="POST" action="upload.php" enctype="multipart/form-data" type="application/x-dbf"><p>Subir ficheros:
        <input type="file" name="fichero" />
        <input type="hidden" name="token" value="<?php echo $thisTpv->getTokenUsuario($Usuario);?>">
        <?php
        $dir_subida = $thisTpv->getRutaUpload();
        
        // Comprobamos si existe la ruta donde guardar el fichero subido.
        if (file_exists($dir_subida)) {
            // Solo muestro btn enviar si existe ruta upload
            ?>
            <input type="submit" name="uploadBtn" value="Enviar" />
        <?php
        } else {
            ?>
            <div class="alert alert-danger">
                No existe ruta upload o no tengo acceso.
            </div>
        <?php    
        }
        ?>
        </form>
    </div>  
</div>
</body>
</html>
