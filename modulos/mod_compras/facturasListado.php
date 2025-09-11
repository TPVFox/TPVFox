<?php
include_once './../../inicial.php';
include_once $URLCom.'/modulos/mod_compras/funciones.php';
include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
include_once $URLCom.'/controllers/Controladores.php';
include_once $URLCom.'/clases/Proveedores.php';
include_once $URLCom.'/modulos/mod_compras/clases/facturasCompras.php';
// Creamos el objeto de controlador.
$Controler = new ControladorComun; 
// Creamos el objeto de proveedor
$CProv= new Proveedores($BDTpv);
// Creamos el objeto de albarán
$CFac=new FacturasCompras($BDTpv);
//Guardamos en un array todos los datos de las facturas temporales
$todosTemporal=$CFac->TodosTemporal();
if (isset($todosTemporal['error'])){
    $errores[0]=array ( 'tipo'=>'Danger!',
                                 'dato' => $todosTemporal['consulta'],
                                 'class'=>'alert alert-danger',
                                 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
                                 );
}
$todosTemporal=array_reverse($todosTemporal);
// ===========    Paginacion  ====================== //
$NPaginado = new PluginClasePaginacion(__FILE__);
$campos = array( 'a.Numfacpro','b.nombrecomercial');
$NPaginado->SetCamposControler($campos);
$NPaginado->SetOrderConsulta('a.Numfacpro');
// --- Ahora contamos registro que hay para es filtro --- //
$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND
$CantidadRegistros=0;
// Obtenemos la cantidad registros 
$listado = $CFac->TodosFacturaLimite($filtro);
$CantidadRegistros = count($listado['Items']);
// --- Ahora envio a NPaginado la cantidad registros --- //
$NPaginado->SetCantidadRegistros($CantidadRegistros);
$htmlPG = $NPaginado->htmlPaginado();
//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
$listado = $CFac->TodosFacturaLimite($filtro.$NPaginado->GetLimitConsulta());
$ListadoFacturas=$listado['Items'];
    if (isset($listado['error'])){
        $errores[]=array ( 'tipo'=>'Danger!',
                                 'dato' => $listado['consulta'],
                                 'class'=>'alert alert-danger',
                                 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
                                 );
    }
    if (count($ListadoFacturas)==0){
        $errores[]=array ( 'tipo'=>'Warning!',
                                 'dato' => '',
                                 'class'=>'alert alert-warning',
                                 'mensaje' => 'No tienes facturas guardados!'
                                 );
    }
?>
<!DOCTYPE html>
<html>
<head>
 <?php include_once $URLCom.'/head.php';?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/js/AccionesDirectas.js"></script>
   <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
   <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
</head>
<body>
<?php
    include_once $URLCom.'/modulos/mod_menu/menu.php';
    if (isset($errores)){
        foreach($errores as $error){
                echo '<div class="'.$error['class'].'">'
                . '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
                . '</div>';
        }
    }
    ?>
    <div class="container">
        <div class="col-md-12 text-center">
            <h2>Facturas de proveedores</h2>
        </div>
        <div class="col-sm-3">
            <h4> Opciones generales</h4>
            <?php 
                if($ClasePermisos->getAccion("Crear")==1){
                    echo '<a class="btn btn-default" href="./factura.php" accesskey="A" >Añadir (Alt+A)</a>';
                }
                if($ClasePermisos->getAccion("Ver")==1){
                    echo '<button class="btn btn-default" onclick="metodoClick('."'".'Ver'."','".'factura'."'".')">Ver</button>';
                }
                if($ClasePermisos->getAccion("Modificar")==1){
                    echo '<button class="btn btn-default" onclick="metodoClick('."'".'Modificar'."','".'factura'."'".')">Modificar</button>';
                }
            ?>
            <div class="col-md-12">
            <h4 class="text-center"> Facturas Abiertas</h4>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nº Fac</th>
                        <th>Pro.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                if (isset($todosTemporal)){
                    foreach ($todosTemporal as $temporal){
                        $numTemporal="";
                        if ($temporal['numfacpro']){
                            $numTemporal=$temporal['numfacpro'];
                        }
                        $url = 'factura.php?tActual='.$temporal['id'];
                        ?>
                        <tr style="cursor:pointer" onclick="redireccionA('<?php echo $url;?>')" title="Factura con numero temporal: <?php echo $temporal['id'];?>">
                            <td><?php echo $numTemporal;?></td>
                            <td><?php echo $temporal['nombrecomercial'];?></td>
                            <td><?php echo number_format($temporal['total'],2);?></td>
                        </tr>
                        <?php
                    }
                }
                    ?>
                </tbody>
            </table>
            </div>
        </div>
        <div class="col-md-9">
            <p>
             -Facturas encontrados BD local filtrados:
                <?php echo $CantidadRegistros; ?>
            </p>
            <?php   // Mostramos paginacion 
                echo $htmlPG;
            //enviamos por get palabras a buscar, las recogemos al inicio de la pagina
            ?>
            <form action="./facturasListado.php" method="GET" name="formBuscar">
            <div class="form-group ClaseBuscar">
                <label>Buscar por nombre de proveedor o número de factura</label>
                <input type="text" name="buscar" value="">
                <input type="submit" value="buscar">
            </div>
            </form>
            <div>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Nª FACTURA</th>
                        <th>FECHA</th>
                        <th>PROVEEDOR</th>
                        <th>BASE</th>
                        <th>IVA</th>
                        <th>TOTAL</th>
                        <th>ID PROV</th>
                        <th>ESTADO</th>
                    </tr>
                </thead>
                <tbody>
            <?php 
                $checkUser = 0;
                foreach ($ListadoFacturas as $factura){
                    $checkUser++;
                    $totaliva=$CFac->sumarIva($factura['Numfacpro']);
                    $totalBase="0.00";
                    $importeIva="0.00";
                    if(isset( $totaliva['totalbase'])){
                        $totalBase=$totaliva['totalbase'];
                    }
                    if(isset( $totaliva['importeIva'])){
                        $importeIva=$totaliva['importeIva'];
                    }
                    $date=date_create($factura['Fecha']);
                    ?>
                    <tr>
                        <td class="rowUsuario">
                            <?php
                            $check_name = 'checkUsu'.$checkUser;
                            echo '<input type="checkbox" id="'.$check_name.'" name="'.$check_name.'" value="'.$factura['id'].'" class="check_factura">';
                            ?>
                        </td>
                        <td>
                            <?php
                            if($ClasePermisos->getAccion("Modificar")==1 && $factura['estado']!=='Contabilizado'){
                            
                            echo '<a class="glyphicon glyphicon-pencil" href="./factura.php?id='.$factura['id'].'&accion=editar"></a>';
                        }
                        ?>
                        </td>
                       <td>
                        <?php 
                        if($ClasePermisos->getAccion("Ver")==1){
                            echo '<a class="glyphicon glyphicon-eye-open" href="./factura.php?id='.$factura['id'].'&accion=ver"></a>';
                        }
                        ?>
                        </td>
                        <td><?php echo $factura['Numfacpro'];?></td>
                        <td><?php echo date_format($date,'Y-m-d');?></td>
                        <td><?php echo $factura['nombrecomercial'];?></td>
                        <td><?php echo $totalBase;?></td>
                        <td><?php echo $importeIva;?></td>
                        <td><?php echo $factura['total'];?></td>
                        <td><?php echo $factura['idProveedor'];?></td>
                        <?php 
                        $clas_estado ='';
                        if ($factura['estado']!=="Sin Guardar"){
                            $linkImprimir = ' <a style="cursor:pointer" class="glyphicon glyphicon-print" '.
                                    "onclick='imprimir(".$factura['id'].
                                    ' , "factura" , '.$Tienda['idTienda'].")'></a>";
                            
                        }else {
                            // Color danger cuando es Sin Guardar
                            $clas_estado = ' class="alert-danger"';
                            $linkImprimir= '';
                        }                    
                        echo '<td'.$clas_estado.'>'
                                .$factura['estado'].$linkImprimir;
                        echo '</td>';
                        ?>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php // Incluimos paginas modales
    echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
    include $RutaServidor.'/'.$HostNombre.'/plugins/modal/ventanaModal.php';
    ?>
</body>
</html>
