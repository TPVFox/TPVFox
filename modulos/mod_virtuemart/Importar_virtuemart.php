
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
                <h4>Acciones a realiza en NUEVOS/MODIFICADOS y comprobaciones</h4>
                <div class ="col-md-6">
                    <label>¿ Que quieres hacer con Codigo Barras de la Web?</label>
                     <select id="codBarras">
                     <?php
                        foreach ($conf_defecto['option_cod_barras'] as $opt){
                            $default ="";
                            if ( isset($opt->default)){
                                // Si tiene default siempre es si... por lo que
                                $default = "selected";
                            }
                            echo '<option value="'.$opt->valor.'" title="'.$opt->descripcion.'" '.$default.'>'
                                .$opt->nombre.'</option>';
                        }           
                     ?>
                     </select >
                </div>
                <div class="col-md-6">
                 <label>¿ Que vas hacer con Referencia de la Web ?</label>
                 <select id="refTienda">
                 <?php
                    foreach ($conf_defecto['option_referencia'] as $opt){
                        $default ="";
                        if ( isset($opt->default)){
                            // Si tiene default siempre es si... por lo que
                            $default = "selected";
                        }
                        echo '<option value="'.$opt->valor.'" title="'.$opt->descripcion.'" '.$default.'>'
                            .$opt->nombre.'</option>';
                    }           
                 ?>
                 </select >
                </div>
            </div>
             
            <div class="col-md-12">
                <h4>Acciones a realiza solo en NUEVOS</h4>
                <div class="col-md-4">
                    <label>¿ Calculamos el ultimo coste ?</label>
                    <select id="ultimoCoste">
                    <?php 
                       foreach ($conf_defecto['option_coste'] as $opt){
                           $default ="";
                           if ( isset($opt->default)){
                               // Si tiene default siempre es si... por lo que
                               $default = "selected";
                           }
                           echo '<option value="'.$opt->valor.'" title="'.$opt->descripcion.'" '.$default.'>'
                               .$opt->nombre.'</option>';
                       }           
                    ?>
                    </select >
                </div>
                <div class="col-md-4">
                    <label>Beneficio por defecto</label>
                    <?php
                    $defecto = $conf_defecto['defecto'];
                    // Obtenemos el indice del objeto que tiene nombre beneficio.
                    $index= array_search('beneficio', array_column($defecto, 'nombre'));
                    
                    ?>
                    <input type="text" id="beneficio" value="<?php echo $defecto[$index]->valor;?> " size="5px">%
                 </div>
                <div class="col-md-4">
                    <label>¿Estado que quieres poner?</label>
                    <select id="estadoNuevo">
                        <?php 
                        // Obtenemos el indice del objeto que tiene nombre estado_nuevo.
                        $index= array_search('estado_nuevo', array_column($defecto, 'nombre'));
                        // Creamos array de estados posibles.
                        $estados_posibles = array ( 0 => 'Activo',
                                                    1 => 'Nuevo',
                                                    2 => 'Temporal',
                                                    3 => 'Baja',
                                                    4 => 'importado'
                                                );
                        foreach ($estados_posibles as $estado) {
                            $default = 's';
                            if ($estado === $defecto[$index]->valor){
                                // El estado que pusimos por defecto.
                                $default = "selected";
                            }
                            echo '<option value="'.$estado.'" '.$default.'>'.$estado.'</option>';

                        }
                        ?>
                    </select >
                 </div>


                 
            </div>
              <div class="col-md-12">
                <h4>Acciones a realiza solo en MODIFICADOS</h4>

                 <div class="col-md-6">
                     <label>¿Estado que quieres poner?</label>
                     <select id="estadoMod">
                     <?php 
                        // Obtenemos el indice del objeto que tiene nombre estado_nuevo.
                        $index= array_search('estado_modificado', array_column($defecto, 'nombre'));
                        // Los estados posibles son los mismos que los nuevos
                        foreach ($estados_posibles as $estado) {
                            $default = 's';
                            if ($estado === $defecto[$index]->valor){
                                // El estado que pusimos por defecto.
                                $default = "selected";
                            }
                            echo '<option value="'.$estado.'" '.$default.'>'.$estado.'</option>';

                        }
                        ?>
                     </select >
                </div>
            </div>
                  
            
             <div class="col-md-12">
                 <button type="submit" name="enviar" class="btn btn-success pull-right" onclick="enviarFormulario()">Actualizar</button>
            </div>
        </div>
        <div class="col-md-5">
            <h3>Proceso</h3>
            <div class="col-md-12">
                <table class="col-md-6 table table-striped">
                <tr>
                    <th>Productos</th>
                    <th>Web</th>
                    <th>TPV</th>
                </tr>
                <tr>
                    <td>Total</td>
                    <td id="totalWeb"></td>
                    <td id="totalTpv"></td>
                </tr>
                <tr>
                    <th>Productos</th>
                    <th>Nuevos</th>
                    <th>Modificados</th>
                </tr>
                <tr>
                    <td>TPV</td>
                    <td id="NuevosWeb"></td>
                    <td id="modifTpv"></td>
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
       <div class="col-md-2">
           <h3></h3>
            <div class="col-md-12">
            <table class="col-md-12 table table-striped">
                <tr>
                    <td><b>Productos en TPV sin Web</b></td>
                </tr>
                <tr>
                    <td id="NuevosTpv"></td>
                </tr>
            </table>
        </div>
       </div>
        <div class="col-md-12">
            <hr/>
            <div class="col-md-5">
           <table  class="col-md-12 table-bordered table-hover" id="productosNuevos">
               
                
           </table>
           </div>
           <div class="col-md-7">
           <table  class="col-md-12 table-bordered table-hover" id="productosMod">
               
                
           </table>
           </div>
        </div>
       
    </div>
<script type="text/javascript">
    $("#DivOpciones").hide();
</script>
</body>
</html>
