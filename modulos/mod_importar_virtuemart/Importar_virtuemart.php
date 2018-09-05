
<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
    include_once $URLCom.'/head.php';
    include ($URLCom.'/controllers/Controladores.php');
    include_once ($URLCom.'/modulos/mod_importar_virtuemart/funciones.php');
    include_once ($URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php');
    include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
    $CTArticulos = new ClaseProductos($BDTpv);
    $ClaseTienda=new ClaseTienda($BDTpv);
	$tiendasWeb=$ClaseTienda->tiendasWeb();
   
    //~ $comprobaciones=array();
    //~ $acciones=array(
        //~ array(
            //~ 'valor'=>1,
            //~ 'accion'=>'Bajar de la web los productos nuevos a TPV'   
        //~ ),
        //~ array(
            //~ 'valor'=>2,
            //~ 'accion'=>'Modificar los productos TPV según la web'
        //~ )
    //~ );
    //~ if(isset($_POST['enviar'])){
       
        
        //~ if($_POST['tiendaWeb']==0){
            //~ $comprobaciones[1]=array ( 'tipo'=>'Danger!',
								 //~ 'dato' => '',
								 //~ 'class'=>'alert alert-danger',
								 //~ 'mensaje' => 'NO HAS SELECCIONADO UNA TIENDA WEB!'
								 //~ );
        //~ }
        //~ if($_POST['accionesWeb']==0){
            //~ $comprobaciones[2]=array ( 'tipo'=>'Danger!',
								 //~ 'dato' => '',
								 //~ 'class'=>'alert alert-danger',
								 //~ 'mensaje' => 'NO HAS SELECCIONADO NINGUNA ACCIÓN!'
								 //~ );
        //~ }
       //~ if(count($comprobaciones)==0){
           //~ switch ($_POST['accionesWeb']){
                //~ case 1:
                    //~ $nuevos=bajarProductosNuevosWeb();
                //~ break;
                //~ case 2:
                    //~ $modificaciones=modificarProductosWeb();
                //~ break;
                
           //~ }
       //~ }
    //~ }
?>
<script src="<?php echo $HostNombre; ?>/modulos/mod_importar_virtuemart/funciones.js"></script>
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
        <?php 
        if(count($comprobaciones>0)){
            foreach($comprobaciones as $comprobacion){
                  echo '<div class="'.$comprobacion['class'].'">'
				. '<strong>'.$comprobacion['tipo'].' </strong> '.$comprobacion['mensaje'].' <br> '.$comprobacion['dato']
				. '</div>';
            }
        }
        ?>
        <h2 class="text-center">Importación o Actualizacion de datos de Virtuemart a TPV.</h2>
        <div class="col-md-5">
            <h3>Parametros a configurar</h3>
            <div class="col-md-12">
                <label>Selecciona la tienda On Line con la quieres importar o actualizar datos:</label>
                <select id="tiendaWeb" name="tiendaWeb">
                  <option value="0">Selecciona una tienda web</option>  
                  <?php 
                  foreach ($tiendasWeb['datos'] as $tienda){
                      echo '<option value="'.$tienda['idTienda'].'">'.$tienda['razonsocial'].'</option>';
                  }
                  ?>
                </select>
            </div>
             <div class="col-md-12">
                 <button type="submit" name="enviar" class="btn btn-success pull-right" onclick="enviarFormulario()">Actualizar</button>
            </div>
        </div>
        <div class="col-md-7">
            <h3>Proceso</h3>
            <div class="col-md-12">
                <table class="col-md-6 table table-striped">
                <tr>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Nuevos</th>
                </tr>
                <tr>
                    <td>Web</td>
                    <td id="totalWeb"></td>
                    <td id="NuevosWeb"></td>
                </tr>
                <tr>
                    <td>TPV</td>
                    <td id="totalTpv"></td>
                    <td id="NuevosTpv"></td>
                </tr>
                </table>
            </div>
             <div class="col-md-12" id="DivOpciones">
                 <a class="btn btn-primary" onclick="actualizarProductosWeb(0)">Actualizar Web</a>
            </div>
            <div class="col-md-12">
            <hr/>
            </div>
            <div class="col-md-12">
                <div class="progress" style="margin:0 100px">
                                        <div id="bar" class="progress-bar progress-bar-info" 
                                             role="progressbar" aria-valuenow="0" 
                                             aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                            0 % completado
                                        </div>
                                    </div>
            </div>
        </div>
        <div class="col-md-12">
            <hr/>
           <table  class="col-md-12">
                <tr>
                <th>Datos Tpv</th>
                <th>Datos Web</th>
                <th>Acciones</th>
                </tr>
           </table>
        </div>
       
    </div>
<script type="text/javascript">
    $("#DivOpciones").hide();
</script>
</body>
</html>
