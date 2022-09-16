
<?php
include_once './../../inicial.php';
include_once $URLCom.'/modulos/mod_compras/funciones.php';
include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
include_once $URLCom.'/controllers/Controladores.php';
include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
include_once $URLCom.'/clases/Proveedores.php';
include_once ($URLCom.'/controllers/parametros.php');
//Carga de clases necesarias
$ClasesParametros = new ClaseParametros('parametros.xml');
// Creamos el objeto de controlador.
$Controler = new ControladorComun; 
// Creamos el objeto de pedido
$Cpedido=new PedidosCompras($BDTpv);
// Creamos el objeto de proveedor
$Cproveedor=new Proveedores($BDTpv);
//Obtenemos los registros temporarles
$todoTemporal=$Cpedido->TodosTemporal();
if (isset($todoTemporal['error'])){
    $errores[0]=array ( 'tipo'=>'Danger!',
                             'dato' => $todoTemporal['consulta'],
                             'class'=>'alert alert-danger',
                             'mensaje' => 'ERROR EN LA BASE DE DATOS!'
                             );
}

$todoTemporal=array_reverse($todoTemporal);

// ===========    Paginacion  ====================== //
$NPaginado = new PluginClasePaginacion(__FILE__);
$campos = array( 'a.Numpedpro','b.nombrecomercial');
$NPaginado->SetOrderConsulta('a.Numpedpro');
$NPaginado->SetCamposControler($campos);
// --- Ahora contamos registro que hay para es filtro --- //
$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND
$CantidadRegistros=0;
// Obtenemos la cantidad registros 
$p= $Cpedido->TodosPedidosLimite($filtro);
$CantidadRegistros = count($p['Items']);
// --- Ahora envio a NPaginado la cantidad registros --- //
$NPaginado->SetCantidadRegistros($CantidadRegistros);
$htmlPG = $NPaginado->htmlPaginado();
//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
$p=$Cpedido->TodosPedidosLimite($filtro.$NPaginado->GetLimitConsulta());
$pedidosDef=$p['Items'];
	if (isset($p['error'])){
		$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $p['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
	}
	if (count($pedidosDef)==0){
		$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'No tienes pedidos guardados!'
								 );
	}
	?>
<!DOCTYPE html>
<html>
<head>
 <?php include_once $URLCom.'/head.php';?>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/js/AccionesDirectas.js"></script>    
</head>
<body>
<?php
    include_once $URLCom.'/modulos/mod_menu/menu.php';
	if (isset($errores)){
		foreach($errores as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br> '.$error['dato']
				. '</div>';
				if ($error['tipo']=='Danger!'){
					exit;
				}
		}
	}
	?>
    <div class="container">
        <div class="col-md-12 text-center">
            <h2>Pedidos de proveedores </h2>
        </div>
        <nav class="col-sm-3">
            <h4> Opciones generales</h4> 
            <?php 
                if($ClasePermisos->getAccion("Crear")==1){
                   echo '<a class="btn btn-default" href="./pedido.php">Añadir</a>';
                }
            ?>
            <h4> Opciones para una selección</h4>
            <div class="col-md-12">
                <h4 class="text-center"> Pedidos Abiertos</h4>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nº Ped</th>
                            <th>Pro.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (isset ($todoTemporal)){
                            foreach ($todoTemporal as $temporal){
                                $numTemporal="";
                                if ($temporal['idPedpro']){
                                    $numTemporal=$temporal['Numpedpro'];
                                 }
                                $url = 'pedido.php?temporal='.$temporal['id'];
                                $tdl = '<td style="cursor:pointer" onclick="redireccionA('
                                        ."'".$url."'".')" title="Pedido con numero temporal:'
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
                                        <a onclick="eliminarTemporal(<?php echo $temporal['id']; ?>, 'ListadoPedidos')">
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
        </nav>
        <div class="col-md-9">
            <p>
             -Pedidos encontrados BD local filtrados:
                <?php echo $CantidadRegistros; ?>
            </p>
            <?php 	// Mostramos paginacion 
            echo $htmlPG;
            //enviamos por get palabras a buscar, las recogemos al inicio de la pagina
            ?>
            <form action="./pedidosListado.php" method="GET" name="formBuscar">
                <div class="form-group ClaseBuscar">
                    <label>Buscar por nombre de proveedor o número de pedido</label>
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
                        <th>Nª PEDIDO</th>
                        <th>FECHA</th>
                        <th>PROVEEDOR</th>
                        <th>BASE</th>
                        <th>IVA</th>
                        <th>TOTAL</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $checkUser = 0;
                foreach($pedidosDef as $pedido){
                    $checkUser ++;
                    $totaliva=$Cpedido->sumarIva($pedido['id']);
                    ?>
                    <tr>
                        <td class="rowUsuario">
                            <?php
                            $check_name = 'checkUsu'.$checkUser;
                            echo '<input type="checkbox" id="'.$check_name.'" name="'.$check_name.'" value="'.$pedido['id'].'" class="check_pedido">';
                            ?>
                        </td>
                        <td>
                            <?php 
                            if($ClasePermisos->getAccion("Modificar")==1 && $pedido['estado']=='Guardado' ){
                                $accion='';
                                if ($pedido['estado']==="Sin Guardar"){
                                    $accion ='&accion=temporal';
                                } else {
                                    // Solo muestro si esta Guardado 
                                    echo '<a class="glyphicon glyphicon-pencil" href="./pedido.php?id='.$pedido['id'].$accion.'"></a>';
                                }
                            }
                            ?>
                         </td>
                        <td>
                            <?php 
                            if($ClasePermisos->getAccion("Ver")==1){
                                ?>
                                <a class="glyphicon glyphicon-eye-open" href='./pedido.php?id=<?php echo $pedido['id'];?>&accion=ver'></a>
                                <?php 
                            }
                            ?>
                        </td>
                        <td><?php echo $pedido['Numpedpro'];?></td>
                        <td><?php echo $pedido['Fecha'];?></td>
                        <td><?php echo $pedido['nombrecomercial'];?></td>
                        <td><?php echo $totaliva['totalbase'];?></td>
                        <td><?php echo $totaliva['importeIva'];?></td>
                        <td><?php echo $pedido['total'];?></td>
                        <?php
                        $clas_estado ='';
                        if ($pedido['estado']!=="Sin Guardar"){
                            $linkImprimir = ' <a class="glyphicon glyphicon-print" '.
                                    "onclick='imprimir(".$pedido['id'].
                                    ' , "pedido" , '.$Tienda['idTienda'].")'></a>";
                            $linkEmail = '';
                            if($ClasePermisos->getAccion("EnviarPedidoEmail")==1){
                                // Hay permiso para poder enviar un pedido por email.
                                $linkEmail = $pedido['email'] ? ' <a class="glyphicon glyphicon-envelope" '.
                                        'title="'.$pedido['email'].'"'."onclick='formularioEnvioEmail(".$pedido['id'].
                                        ' , "pedido" , '.$Tienda['idTienda'].',"'.$pedido['email'].'"'.")'></a>" : '';
                            }
                        } else {
                            // Color danger cuando es Sin Guardar
                            $clas_estado = ' class="alert-danger"';
                            $linkImprimir= '';
                            $linkEmail = '';
                        }
                        
                        echo '<td'.$clas_estado.'>'.$pedido['estado'].'</td>';
                        echo '<td'.$clas_estado.'>'.$linkImprimir.'&nbsp'.$linkEmail.'</td>';
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
    <?php
     echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
     include $URLCom.'/plugins/modal/ventanaModal.php';
    ?>
</body>
</html>
