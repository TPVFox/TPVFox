<?php
include_once './../../inicial.php';
include_once $URLCom.'/modulos/mod_cliente/funciones.php';
include_once $URLCom.'/controllers/Controladores.php';
include_once $URLCom.'/controllers/parametros.php';
include_once $URLCom.'/modulos/mod_cliente/clases/ClaseCliente.php';
$ClasesParametros = new ClaseParametros('parametros.xml');  
$Controler = new ControladorComun; 
$Controler->loadDbtpv($BDTpv);      
$Cliente=new ClaseCliente();		
$dedonde="cliente";
$id=0;
$errores = array();
$tablaHtml= array(); // Al ser nuevo, al crear ClienteUnico ya obtenemos array vacio.
$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_cliente',$Usuario['id']);
$configuracion=$configuracion['incidencias'];
$titulo= "Crear"; 
$estados_cliente = array ('Activo','inactivo');
if (isset($_GET['id'])) {
    $id=$_GET['id']; // Obtenemos id para modificar.
} else {
    $_GET['accion'] = 'Nuevo';
}
// [Get_accion] -> editar,ver,Nuevo
// Si existe accion, variable es $accion , sino es "editar"
$Get_accion = (isset($_GET['accion']))? $_GET['accion'] : 'ver';
$solo_lectura = '';
if ($Get_accion === 'ver'){
    $solo_lectura = ' readonly';
}

if ($id> 0){
    $titulo= "Modificar";
}
$ClienteUnico=$Cliente->getClienteCompleto($id);        
foreach($ClienteUnico['adjuntos'] as $key =>$adjunto){
    if (isset($adjunto['error'])){
        $errores[]=array ( 'tipo'=>'danger',
                     'mensaje' => 'ERROR EN LA BASE DE !<br/>Consulta:'. $adjunto['consulta']
                     );
    } else {
        $tablaHtml[] = htmlTablaGeneral($adjunto['datos'], $HostNombre, $key);
    }
}

// Solo permitimos guarfar si realmente no hay errores.
// ya que consideramos que son grabes y no podermo continuar. ( bueno a lo mejor.. :-)
if (count($errores) === 0){
    if(isset($_POST['Guardar'])){
        $guardar=$Cliente->guardarCliente($_POST);
        $ClienteUnico = $guardar['datos'];
        if($guardar['estado'] === 'OK'){
                // Todo fue bien , volvemos a listado.
                // Dos posibles opciones deberíamos tener un parametro configuracion.
                // 1.- Redirecionar
                // header('Location: ListaProveedores.php');
                // 2.- Recargar datos modificados.
                $mensaje = 'Fue guardo correctamente';
                $errores[]=$Cliente->montarAdvertencia('info',$mensaje);
        } else {
            // Hubo error grave, estado = KO
            $errores[] = $Cliente->montarAdvertencia('danger','No se grabo por un error grave');
            $errores[] = $Cliente->montarAdvertencia('danger',$guardar['error']);
        }
    }
}

// Montamos html Option de forma de pago,vencimiento y estado con el valor por default
            
    if (!isset($Defaultvenci)){ 
        // No se obtuvo formas vencimiento y de pago
        // Creamos objeto con valores por defecto, necesario mostrar formulario.
        $DefaultVenci =(object) array('vencimiento' => '0','formapago' => '0');
    } else {
        $DefaultVenci = json_decode($ClienteUnico['formasVenci']); // obtenemos un objeto con vencimiento y formapago
    }
    $html_optionVenci =  getHtmlOptions($Cliente->getVencimientos(),$DefaultVenci->vencimiento);
    $formasPago = $Cliente->getFormasPago();
    $html_optionPago = getHtmlOptions($formasPago,$DefaultVenci->formapago);
    $html_optionEstado= '';
    foreach ($estados_cliente as $i=>$estado_cliente){
        $es_seleccionado = '';
        if (isset($ClienteUnico['estado'])){
            if($ClienteUnico['estado'] === $estado_cliente){
                $es_seleccionado = ' selected';
            }
        } 
        $html_optionEstado .='<option value="'.$estado_cliente.'"'.$es_seleccionado.'>'.$estado_cliente.'</option>';
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <?php   include_once $URLCom.'/head.php';?>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
		<script>
        <?php echo 'var configuracion='.json_encode($configuracion).';';?>	
		</script>
        <?php
        if ($Get_accion ==='Nuevo'){
        echo "<script>history.replaceState(null,'','?accion=Nuevo');</script>";
        }
        ?>
		
	</head>
	<body>
		<?php include_once $URLCom.'/modulos/mod_menu/menu.php';?>
		<div class="container">
            <?php
            if (isset($errores) && count($errores)>0){
                foreach($errores as $error){
                    echo '<div class="alert alert-'.$error['tipo'].'">'
                    . '<strong>'.$error['tipo'].' </strong><br/> ';
                    if (is_array($error['mensaje'])){
                        echo '<pre>';
                        print_r($error['mensaje']);
                        echo '</pre>';
                    } else {
                        echo $error['mensaje'];
                    }
                    echo '</div>';
                }
            }
            ?>
			<h1 class="text-center"> Cliente: <?php echo $titulo;?></h1>
            <div class="col-md-12">
                <div class="col-md-2">
                    <?php echo $Controler->getHtmlLinkVolver('Volver ');?>
                </div>
            <?php
            if ($Get_accion !=='Nuevo'){
                echo '<div class="col-md-2">';
                echo  '<a  class="btn btn-warning" onclick="abrirModalIndicencia('."'".$dedonde."'".' , configuracion , 0,'.$id.');">Añadir Incidencia </a>';
                echo '</div>';
            }
            ?>
            
            <form method="post" name="formCliente">
                <div class="col-md-2">
                <?php
                if ($Get_accion !== 'ver'){ ?>
                    <input type="submit" class="btn btn-primary" value="Guardar" name="Guardar" id="Guardar">
                <?php
                }
                ?>
                </div>
                <div class="col-md-12">
                    <h4>Datos del cliente con ID: <input size=3 type="text" id="idClientes" name="idClientes" value="<?php echo $ClienteUnico['idClientes'];?>"   readonly></h4>

                    <div class="col-md-1">
                        <?php 
                        // UrlImagen
                        $img = $HostNombre.'/css/img/imgUsuario.png';
                        ?>
                        <img src="<?php echo $img;?>" style="width:100%;">
                    </div>
                    <div class="col-md-7">
                        <div class="Datos">
                            <div class="col-md-6 form-group">
                                <label>Nombre Cliente:</label>
                                <input type="text" id="nombre"  name="Nombre" <?php echo $solo_lectura;?> placeholder="nombre" value="<?php echo $ClienteUnico['Nombre'];?>"  required >
                                 <div class="invalid-tooltip-nombre" >
                                    No permitimos la doble comilla (") 
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Razon Social:</label> <!--//al enviar con POST los inputs se cogen con name="xx" PRE-->
                                <input  type="text" id="razonsocial" name="razonsocial" <?php echo $solo_lectura;?> placeholder="razon social" value="<?php echo $ClienteUnico['razonsocial'];?>">
                                 <div class="invalid-tooltip-nombre">
                                    No permitimos la doble comilla (") 
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>NIF:</label>
                                <input type="text"	id="nif" name="nif" <?php echo $solo_lectura;?> value="<?php echo $ClienteUnico['nif'];?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Codigo Postal:</label>
                                <input type="text" id="codigo_postal" name="codpostal" <?php echo $solo_lectura;?> value="<?php echo $ClienteUnico['codpostal'];?>"   >
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Direccion:</label>
                                <textarea id="direccion" name="direccion" <?php echo $solo_lectura;?>><?php echo $ClienteUnico['direccion'];?> </textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Telefono:</label>
                                <input type="text" id="telefono" name="telefono" <?php echo $solo_lectura;?> value="<?php echo $ClienteUnico['telefono'];?>"   >
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Movil:</label>
                                <input type="text" id="movil" name="movil" <?php echo $solo_lectura;?> value="<?php echo $ClienteUnico['movil'];?>"   >
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Fax:</label>
                                <input type="text" id="fax" name="fax" <?php echo $solo_lectura;?> value="<?php echo $ClienteUnico['fax'];?>"   >
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Email:</label>
                                <input type="text" id="email" name="email" <?php echo $solo_lectura;?> value="<?php echo $ClienteUnico['email'];?>"  >
                            </div>
                            
                            <div class="col-md-6 form-group">
                                <label for="sel1">Forma de pago por defecto: </label>
                                <select class="form-control" name="formapago" id="sel1" <?php echo $solo_lectura;?> style="width: 15em;">
                                    <?php 
                                    echo $html_optionPago;
                                    ?>
                                    
                                </select>
                                
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="sel1">Estado:</label>
                                <select class="form-control" name="estado" <?php echo $solo_lectura;?> id="sel2" style="width: 14em;">
                                    <?php 
                                    echo $html_optionEstado;
                                    ?>
                                    
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="sel1">Vencimiento por defecto:</label>
                                <select class="form-control" name="vencimiento" <?php echo $solo_lectura;?> id="sel3" style="width: 15em;">
                                    <?php
                                    echo $html_optionVenci;
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                    </div>
                    <div class="col-md-4">
                         <div class="panel-group">
                             <?php 
                            $num = 1 ; // Numero collapse;
                            $titulo = 'Tickets';
                            echo htmlPanelDesplegable($num,$titulo,$tablaHtml[0]);
                            ?>
                            <?php 
                            $num = 2 ; // Numero collapse;
                            $titulo = 'Facturas';
                            echo htmlPanelDesplegable($num,$titulo,$tablaHtml[1]);
                            ?>
                            <?php 
                            $num = 3 ; // Numero collapse;
                            $titulo = 'Albaranes';
                            echo htmlPanelDesplegable($num,$titulo,$tablaHtml[2]);
                            ?>
                            <?php 
                            $num = 4 ; // Numero collapse;
                            $titulo = 'Pedidos';
                            echo htmlPanelDesplegable($num,$titulo,$tablaHtml[3]);
                            ?>
                        </div>
                        <!-- Aquí irá el código de los grupos-->
                    </div>
                </div>
            </form>
        </div>
    </div>
		<?php 
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/ventanaModal.php';
?>
<?php
include_once $URLCom.'/pie.php';
?>
</body>

</html>
