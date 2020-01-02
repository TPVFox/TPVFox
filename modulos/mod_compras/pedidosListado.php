
<!DOCTYPE html>
<html>
<head>
<?php
	include_once './../../inicial.php';
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_compras/funciones.php';
	include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
	include_once $URLCom.'/clases/Proveedores.php';
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
</head>
<body>
	<script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/js/AccionesDirectas.js"></script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
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
            <h4> Opcion general</h4> 
            <?php 
                if($ClasePermisos->getAccion("Crear")==1){
                   echo '<a href="#section2" onclick="metodoClick('."'".'AgregarPedido'."'".');";>Añadir</a>';
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
                            foreach ($todoTemporal as $pedidoTemp){
                                if ($pedidoTemp['idPedpro']){
                                    $numPed=$pedidoTemp['Numpedpro'];
                            }else{
                                $numPed="";
                            }
                            $url = 'pedido.php?tActual='.$pedidoTemp['id'];
                            $tdl = '<td style="cursor:pointer" onclick="redireccionA('
                                    ."'".$url."'".')" title="Pedido con numero temporal:'
                                    .$pedidoTemp['id'].'">';
                            $td_Pedido_temporal = $tdl.$numPed.'</td>'
                                                 .$tdl.$pedidoTemp['nombrecomercial'].'</td>'
                                                 .$tdl.number_format($pedidoTemp['total'],2).'</td>';
                            ?>
                                <tr>
                                    <?php echo $td_Pedido_temporal;
                                    // Solo mostramos la opcion de eliminar temporal si tiene permisos.
                                    if($ClasePermisos->getAccion("EliminarTemporal")==1){
                                    ?>
                                    <td>
                                        <a onclick="eliminarTemporal(<?php echo $pedidoTemp['id']; ?>, 'ListadoPedidos')">
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
                    </tr>
                </thead>
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
                            if($ClasePermisos->getAccion("Modificar")==1){
                                $accion='';
                                if ($pedido['estado']==="Sin Guardar"){
                                    $accion ='&accion=temporal';
                                }
                                echo '<a class="glyphicon glyphicon-pencil" href="./pedido.php?id='.$pedido['id'].$accion.'">';
                            }
                            ?>
                         </td>
                        <td>
                            <?php 
                            if($ClasePermisos->getAccion("Ver")==1){
                                ?>
                                <a class="glyphicon glyphicon-eye-open" href='./pedido.php?id=<?php echo $pedido['id'];?>&accion=ver'>
                                <?php 
                            }
                            ?>
                        </td>
                        <td><?php echo $pedido['Numpedpro'];?></td>
                        <td><?php echo $pedido['FechaPedido'];?></td>
                        <td><?php echo $pedido['nombrecomercial'];?></td>
                        <td><?php echo $totaliva['totalbase'];?></td>
                        <td><?php echo $totaliva['importeIva'];?></td>
                        <td><?php echo $pedido['total'];?></td>
                        <?php
                        $clas_estado ='';
                        if ($pedido['estado']!=="Sin Guardar"){
                            $linkPedido = ' <a class="glyphicon glyphicon-print" '.
                                    "onclick='imprimir(".$pedido['id'].
                                    ' , "pedido" , '.$Tienda['idTienda'].")'></a>";
                        } else {
                            $clas_estado = ' class="alert-danger"';
                            $linkPedido= '';
                        }
                        echo '<td'.$clas_estado.'>'.$pedido['estado'].$linkPedido.'</td>';
                        ?>
                    </tr>
                <?php
                }
                ?>
            </table>
            </div>
        </div>
    </div>
</body>
</html>
