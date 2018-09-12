
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
    $conf_defecto=$conf_defecto['defecto'];
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
                 <label>Selecciona Acción a realizar con los Código de Barras</label>
                 <select id="codBarras">
                 <?php 
                    $posCodBarras= array_search('cod_barras', array_column($conf_defecto, 'nombre'));
                    if($conf_defecto[$posCodBarras]->default=="Si"){
                        echo '<option value="1">'.$conf_defecto[$posCodBarras]->descripcion.'</option>';
                        echo '<option value="2">No importar</option>';
                    }else{
                        echo '<option value="2">No importar</option>';
                        echo '<option value="1">'.$conf_defecto[$posCodBarras]->descripcion.'</option>';
                    }
                 ?>
                 </select >
            </div>
             <div class="col-md-12">
                 <label>Selecciona Acción a realizar con los Referencia de Tienda</label>
                 <select id="refTienda">
                 <?php 
                    $porRefProv= array_search('ref_producto', array_column($conf_defecto, 'nombre'));
                    if($conf_defecto[$porRefProv]->default=="Si"){
                        echo '<option value="1">'.$conf_defecto[$porRefProv]->descripcion.'</option>';
                        echo '<option value="2">Importar como referencia Principal</option>';
                        echo '<option value="3">Las dos anteriores</option>';
                    }else{
                        echo '<option value="2">Importar como referencia Principal</option>';
                        echo '<option value="1">'.$conf_defecto[$porRefProv]->descripcion.'</option>';
                        echo '<option value="3">Las dos anteriores</option>';
                    }
                 ?>
                 </select >
            </div>
              <div class="col-md-12">
                   <div class="col-md-6">
                 <label>Selecciona Acción cuando  es nuevo</label>
                 <select id="estadoNuevo">
                 <?php 
                    $podEstadoNuevo= array_search('estado_nuevo', array_column($conf_defecto, 'nombre'));
                    if($conf_defecto[$podEstadoNuevo]->default=="Activo"){
                        echo '<option value="Activo">'.$conf_defecto[$podEstadoNuevo]->default.'</option>';
                       echo '<option value="Nuevo">Nuevo</option>';
                        echo '<option value="Temporal">Temporal</option>';
                        echo '<option value="Baja">Baja</option>';
                        echo '<option value="importado">importado</option>';
                    }else{
                       echo '<option value="Nuevo">Nuevo</option>';
                        echo '<option value="Temporal">Temporal</option>';
                        echo '<option value="Baja">Baja</option>';
                        echo '<option value="importado">importado</option>';
                        echo '<option value="Activo">'.$conf_defecto[$porRefProv]->default.'</option>';
                       
                    }
                 ?>
                 </select >
                 </div>
                  <div class="col-md-6">
                 <label>Selecciona Acción cuando  se va a Modificar</label>
                 <select id="estadoMod">
                 <?php 
                    $podEstadoMod= array_search('estado_modificado', array_column($conf_defecto, 'nombre'));
                    if($conf_defecto[$podEstadoMod]->default=="Activo"){
                        echo '<option value="Activo">'.$conf_defecto[$podEstadoMod]->default.'</option>';
                        echo '<option value="Nuevo">Nuevo</option>';
                        echo '<option value="Temporal">Temporal</option>';
                        echo '<option value="Baja">Baja</option>';
                        echo '<option value="importado">importado</option>';
                    }else{
                        echo '<option value="Nuevo">Nuevo</option>';
                        echo '<option value="Temporal">Temporal</option>';
                        echo '<option value="Baja">Baja</option>';
                        echo '<option value="importado">importado</option>';
                        echo '<option value="Activo">'.$conf_defecto[$porRefProv]->default.'</option>';
                       
                    }
                 ?>
                 </select >
                </div>
                 </div>
                   <div class="col-md-12">
                          <div class="col-md-6">
                 <label>Beneficio por defecto</label>
                 <?php 
                    $podBeneficio= array_search('beneficio', array_column($conf_defecto, 'nombre'));
                 ?>
                 <input type="text" id="beneficio" value="<?php echo $conf_defecto[$podBeneficio]->default;?> " readonly=”readonly” size="5px">%
                 </div>
                    <div class="col-md-6">
                 <label>Coste Promedio por defecto</label>
                 <?php 
                    $podCostProm= array_search('coste_promedio', array_column($conf_defecto, 'nombre'));
                 ?>
                 <input type="text" id="costePromedio" value="<?php echo $conf_defecto[$podCostProm]->default;?> " readonly=”readonly” size="5px">
                 </div>
                  <div class="col-md-12">
                 <label>Selecciona Acción cuando con el ultimo Coste</label>
                 <select id="ultimoCoste">
                 <?php 
                    $podUltimoCoste= array_search('ultimo_coste', array_column($conf_defecto, 'nombre'));
                    if($conf_defecto[$podUltimoCoste]->default=="Activo"){
                        echo '<option value="1">'.$conf_defecto[$podUltimoCoste]->descripcion.'</option>';
                        echo '<option value="2">Si se calcula el último coste</option>';
                      
                    }else{
                        echo '<option value="2">Si se calcula el último coste</option>';
                        echo '<option value="1">'.$conf_defecto[$podUltimoCoste]->descripcion.'</option>';
                       
                    }
                 ?>
                 </select >
                </div>
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
