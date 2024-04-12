<?php
include_once './../../inicial.php';
include_once $URLCom.'/modulos/mod_compras/funciones.php';
include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
include_once $URLCom.'/controllers/Controladores.php';
include_once $URLCom.'/clases/Proveedores.php';
include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
// Creamos el objeto de controlador.
$Controler = new ControladorComun; 
// Creamos el objeto de proveedor
$CProv= new Proveedores($BDTpv);
// Creamos el objeto de albarán
$CAlb=new AlbaranesCompras($BDTpv);
// Creamos el objeto de Articulos, por que lo necesitamos para historico.
$CArticulo=new Articulos($BDTpv);
//Guardamos en un array los datos de los albaranes temporales
$todosTemporal=$CAlb->TodosTemporal();
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
$campos = array( 'a.Numalbpro','b.nombrecomercial');
$NPaginado->SetCamposControler($campos);
$NPaginado->SetOrderConsulta('a.Numalbpro');
// --- Ahora contamos registro que hay para es filtro --- //
$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND
$CantidadRegistros=0;
// Obtenemos la cantidad registros 
$a = $CAlb->TodosAlbaranesLimite($filtro);
$CantidadRegistros = count($a['Items']);
// --- Ahora envio a NPaginado la cantidad registros --- //
$NPaginado->SetCantidadRegistros($CantidadRegistros);
$htmlPG = $NPaginado->htmlPaginado();
//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
$a=$CAlb->TodosAlbaranesLimite($filtro.$NPaginado->GetLimitConsulta());
$albaranesDef=$a['Items'];
	if (isset($a['error'])){
		$errores[]=array ( 'tipo'=>'Danger!',
								 'dato' => $a['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
	}
    if (count($albaranesDef)==0){
		$errores[]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'No tienes albaranes guardados!'
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
            <h2>Albaranes de proveedores</h2>
        </div>
        <div class="col-sm-3">
            <h4> Opciones generales</h4>
            <?php 
                if($ClasePermisos->getAccion("Crear")==1){
                  echo '<a class="btn btn-default" href="./albaran.php">Añadir</a>';
                }
                if($ClasePermisos->getAccion("Ver")==1){
                    echo '<button class="btn btn-default" onclick="metodoClick('."'".'Ver'."','".'albaran'."'".')">Ver</button>';
                }
                if($ClasePermisos->getAccion("Modificar")==1){
                    echo '<button class="btn btn-default" onclick="metodoClick('."'".'Modificar'."','".'albaran'."'".')">Modificar</button>';
                }
                if($ClasePermisos->getAccion("CambiarEstado")==1){
                    echo '<button class="btn btn-default" onclick="metodoClick('."'".'cambiarEstado'."','".'albaranes'."'".')">Cambiar estado</button>';
                }
            ?>
            <div class="col-md-12">
                <h4 class="text-center"> Albaranes Abiertos</h4>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nº Alb</th>
                            <th>Pro.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                    if (isset($todosTemporal)){
                        foreach ($todosTemporal as $temporal){
                            $numTemporal="";
                            if ($temporal['Numalbpro']){
                                $numTemporal=$temporal['Numalbpro'];
                            }
                            $url = 'albaran.php?tActual='.$temporal['id'];
                            $tdl = '<td style="cursor:pointer" onclick="redireccionA('
                                    ."'".$url."'".')" title="Albaran con numero temporal:'
                                    .$temporal['id'].'">';
                            $td_temporal = $tdl.$numTemporal.'</td>'
                                                 .$tdl.$temporal['nombrecomercial'].'</td>'
                                                 .$tdl.number_format($temporal['total'],2).'</td>';
                            ?>
                            <tr>
                            <?php echo $td_temporal;
                                // Solo mostramos la opcion de eliminar temporal si tiene permisos.
                                if($ClasePermisos->getAccion("EliminarTemporal")==1){
                                    ?>
                                    <td>
                                        <a onclick="eliminarTemporal(<?php echo $temporal['id']; ?>, 'ListadoAlbaranes')">
                                            <span class="glyphicon glyphicon-trash"></span>
                                        </a>
                                    </td>
                                    <?php
                                 }
                                    ?>
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
             -Albaranes encontrados BD local filtrados:
                <?php echo $CantidadRegistros; ?>
            </p>
            <?php 	// Mostramos paginacion 
            echo $htmlPG;
            //enviamos por get palabras a buscar, las recogemos al inicio de la pagina
            ?>
            <form action="./albaranesListado.php" method="GET" name="formBuscar">
            <div class="form-group ClaseBuscar">
                <label>Buscar por nombre de proveedor o número de albarán</label>
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
                        <th>Nª ALBARÁN</th>
                        <th>FECHA</th>
                        <th>PROVEEDOR</th>
                        <th>BASE</th>
                        <th>IVA</th>
                        <th>TOTAL</th>
                        <th>ESTADO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $checkUser = 0;
                    foreach ($albaranesDef as $albaran){
                        $iconoCostes='';
                        $checkUser++;
                        $totaliva=$CAlb->sumarIva($albaran['Numalbpro']);
                        if ($albaran['estado']<>"Sin Guardar"){
                            $historico=$CArticulo->historicoCompras($albaran['Numalbpro'], "albaran", "compras");
                            foreach ($historico as $his){
                                if($his['estado']=="Pendiente"){
                                    $iconoCostes=' <a class="glyphicon glyphicon-th-list" style="color:red" href="../mod_producto/Recalculo_precios.php?id='.$albaran['id'].'"></a>';
                                }
                            }
                        }
                        $date=date_create($albaran['Fecha']);
                    ?>
                        <tr>
                        <td class="rowUsuario">
                            <?php
                            $check_name = 'checkUsu'.$checkUser; // El prefijo esta mal, debería corregirlo en todas la aplicacion
                            echo '<input type="checkbox" id="'.$check_name.'" name="'.$check_name.'" value="'.$albaran['id'].'" class="check_albaran">';
                            ?>
                        </td>
                        <td>
                            <?php
                            if($ClasePermisos->getAccion("Modificar")==1 && $albaran['estado']!=='Facturado'){
                                $accion='';
                                if ($albaran['estado']==="Sin Guardar"){
                                    $accion ='&accion=editar';
                                }
                                echo '<a class="glyphicon glyphicon-pencil" href="./albaran.php?id='.$albaran['id'].$accion.'"></a>';
                            }
                            ?>
                        </td>
                        <td>
                        <?php 
                        if($ClasePermisos->getAccion("Ver") == 1){
                            echo '<a class="glyphicon glyphicon-eye-open" href="./albaran.php?id='.$albaran['id'].'&accion=ver"></a>';
                        }
                        ?>
                        </td>
                        <td><?php echo $albaran['Numalbpro'];?></td>
                        <td><?php echo date_format($date,'Y-m-d');?></td>
                        <td><?php echo $albaran['nombrecomercial'];?></td>
                        <td><?php echo $totaliva['totalbase'];?></td>
                        <td><?php echo $totaliva['importeIva'];?></td>
                        <td><?php echo $albaran['total'];?></td>
                        <?php
                        $clas_estado ='';
                        if ($albaran['estado']!=="Sin Guardar"){
                            $linkImprimir = ' <a style="cursor:pointer" class="glyphicon glyphicon-print" '.
                                    "onclick='imprimir(".$albaran['id'].
                                    ' , "albaran" , '.$Tienda['idTienda'].")'></a>";
                        } else {
                            // Color danger cuando es Sin Guardar
                            $clas_estado = ' class="alert-danger"';
                            $linkImprimir= '';
                        }                    
                        echo '<td'.$clas_estado.'>'
                                .$albaran['estado'].$linkImprimir.$iconoCostes;
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
