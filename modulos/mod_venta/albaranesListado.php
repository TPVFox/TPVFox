<?php
include_once './../../inicial.php';
include_once $URLCom.'/modulos/mod_venta/funciones.php';
include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
include_once $URLCom.'/controllers/Controladores.php';
include_once $URLCom.'/clases/cliente.php';
include_once $URLCom.'/modulos/mod_venta/clases/albaranesVentas.php';
include_once ($URLCom.'/controllers/parametros.php');
$ClasesParametros = new ClaseParametros('parametros.xml');
$Ccliente=new Cliente($BDTpv);
$Calbaran=new AlbaranesVentas($BDTpv);
$Controler = new ControladorComun; 
$todosTemporal=$Calbaran->TodosTemporal();
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
$campos = array( 'a.Numalbcli','b.Nombre');
$NPaginado->SetCamposControler($campos);
$NPaginado->SetOrderConsulta('a.Numalbcli');
// --- Ahora contamos registro que hay para es filtro --- //
$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND
$CantidadRegistros=0;
// Obtenemos la cantidad registros 
$a= $Calbaran->TodosAlbaranesFiltro($filtro);
$CantidadRegistros = count($a['Items']);
// --- Ahora envio a NPaginado la cantidad registros --- //
$NPaginado->SetCantidadRegistros($CantidadRegistros);
$htmlPG = $NPaginado->htmlPaginado();
//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
$d=$Calbaran->TodosAlbaranesFiltro($filtro.$NPaginado->GetLimitConsulta());
$albaranesDef=$d['Items'];
if (isset($d['error'])){
		$errores[]=array ( 'tipo'=>'Danger!',
								 'dato' => $d['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
                             );
}
if (count($d['Items'])==0){
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
	<script src="<?php echo $HostNombre; ?>/modulos/mod_venta/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_venta/js/AccionesDirectas.js"></script>
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
    <div class="row">
        <div class="col-md-12 text-center">
            <h2>Albaranes de Clientes</h2>
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
                if($ClasePermisos->getAccion("CambiarEstadoAlbaran")==1){
                    echo '<button class="btn btn-default" onclick="metodoClick('."'".'cambiarEstado'."','".'albaran'."'".')">Cambiar estado</button>';
                }
            ?>
            <div class="col-md-12">
            <h4 class="text-center"> Albaranes Abiertos</h4>
            <table class="table table-striped table-hover">
			<thead>
				<tr>
					<th WIDTH="4">Nº Temp</th>
					<th WIDTH="100">Nº Alb</th>
					<th WIDTH="4">Cliente</th>
					<th WIDTH="4">Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
			if (isset ($todosTemporal)){
				foreach ($todosTemporal as $temporal){
                    $numDocumento = "";
                    if ($temporal['Numalbcli']){
						$numDocumento = $temporal['Numalbcli'];
					}
					?>
					<tr>
						<td><a href="albaran.php?tActual=<?php echo $temporal['id'];?>"><?php echo $temporal['id'];?></td>
						<td><?php echo $numDocumento;?></td>
						<td><?php echo $temporal['Nombre'];?></td>
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
             -Albaranes encontrados BD local filtrados:
                <?php echo $CantidadRegistros; ?>
            </p>
            <?php 	// Mostramos paginacion 
                echo $htmlPG;
                //enviamos por get palabras a buscar, las recogemos al inicio de la pagina
            ?>
            <form action="./albaranesListado.php" method="GET" name="formBuscar">
            <div class="form-group ClaseBuscar">
                <label>Buscar en número de albarán </label>
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
                            <th>CLIENTE</th>
                            <th>BASE</th>
                            <th>IVA</th>
                            <th>TOTAL</th>
                            <th>ESTADO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($albaranesDef as $k => $albaran){
                            $totaliva   = $Calbaran->sumarIva($albaran['Numalbcli']);
                            $date       = date_create($albaran['Fecha']);
                        ?>
                        <tr>
                    <td>
                                <input class="Check" type="checkbox" name="check_<?php echo $k;?>" value="<?php echo $albaran['id'];?>">
                            </td>

                            <td>
                                <?php 
                                 if($ClasePermisos->getAccion("Modificar")==1){
                            echo '<a class="glyphicon glyphicon-pencil" href="./albaran.php?id='.$albaran['id'].'&accion=editar"></a>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if($ClasePermisos->getAccion("Ver")==1){
                                   echo '<a class="glyphicon glyphicon-eye-open" href="./albaran.php?id='.$albaran['id'].'&estado=ver"></a>';
                                }
                                ?>
                            </td>
                            <td><?php echo $albaran['Numalbcli'];?></td>
                            <td><?php echo date_format($date,'Y-m-d');?></td>
                            <td><?php echo $albaran['Nombre'];?></td>
                            <td><?php echo $totaliva['totalbase'];?></td>
                            <td><?php echo $totaliva['importeIva'];?></td>
                            <td><?php echo $albaran['total'];?></td>
                            <td>
                            <?php 
                            echo $albaran['estado'];
                            if ($albaran !== 'Sin Guardar'){
                                $onclick=" onclick='imprimir(".$albaran['id'].',"albaran",'.json_encode($_SESSION['tiendaTpv']).")'";
                                echo '<a class="glyphicon glyphicon-print" '.$onclick.'></a>';
                            }
                            ?>
                            </td>
                        </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
