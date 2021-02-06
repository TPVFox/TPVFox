<?php 
/*
 * @Objetivo es mostrar listado de ticket.
 * Pueden ser cobrados o cerrados según de donde se ejecute.
 * Get 
 * 		'tickets->
 * 				Cerrados -> Estos son los ticket ya se hizo cierre.
 * 				Cobrados -> Estos son los tickets cobrados.
 * */
?>


<!DOCTYPE html>
<html>
    <head>
    <?php
    include_once './../../inicial.php';
	include_once $URLCom.'/head.php';
    include_once $URLCom.'/modulos/mod_tpv/funciones.php';
    include_once $URLCom.'/controllers/Controladores.php';
    include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
	include_once $URLCom.'/modulos/mod_tpv/clases/ClaseTickets.php';
    $Tickets = new ClaseTickets();
    $otrosParametros= '';
    if (isset($_GET['idCierre'])){
        include_once $URLCom.'/modulos/mod_cierres/clases/ClaseCierres.php';
        $CCierres = new ClaseCierres;
        $otrosParametros = 'estado=Cerrado&idUsuario='.$_GET['idUsuario'].'&idCierre='. $_GET['idCierre'].'&';
    }
    
	// Creamos objeto controlado comun, para obtener numero de registros. 
	$Controler = new ControladorComun;
    $Controler->loadDbtpv($BDTpv);
	$NPaginado = new PluginClasePaginacion(__FILE__);
	//INICIALIZAMOS variables para el plugin de paginado:
    if ($otrosParametros <>''){
        $NPaginado->AnahadirLinkBase($otrosParametros);
    }
	$mensaje_error = array();
    $campos = array('t.Numticket','c.Nombre','c.razonsocial','t.total');
	
    $NPaginado->SetCamposControler($campos);
    $filtro = $NPaginado->GetFiltroWhere('OR');
    // Definir estado
    $estado_ticket  = 'Cobrado';
    if (isset($_GET['estado'])){
        $estado_ticket  = $_GET['estado'];
    }    
    // Falta definir fechas
    if (isset($_GET['fecha_inicio']) && $_GET['fecha_final']){
        $fechas = array( 'inicio' => $_GET['fecha_inicio'],
                         'final' => $_GET['fecha_final']
                         );
    }
    
    if (!isset($fechas)) {
        // Creamo la fecha inicial y final de los tickets que tenemos de un estado determinado
        // sino recibimos fechas
        $obtenerFecha = $Tickets->getPrimerTicket($estado_ticket);
        if (isset( $obtenerFecha['fecha'])){
            $fecha_inicio = DateTime::createFromFormat('Y-m-d H:i:s', $obtenerFecha['fecha']);
            // Creamos fecha:   Inicio es la que obtenimos.
            //                  Final es la actual.. Esto puede generar un problema si hay muchos dias.
            $fechas =array( 'inicio' => $fecha_inicio->format('Y-m-d').' 00:00:00',
                        'final'  => date('Y-m-d').' 23:59:59'
                        );
        } else {
            $mensaje_error[] = 'No obtuvo fecha del Primer Ticket de este estado:'.$estado_ticket;
            $fechas = array();
        }
    }
    if (isset($_GET['idCierre'])){
        $Obtenertickets = $CCierres->obtenerTicketsUsuariosCierre($_GET['idUsuario'],$_GET['idCierre'],$Tienda['idTienda']);
    } else {
            $Obtenertickets = $Tickets->obtenerTickets($estado_ticket ,$fechas,$filtro);
    }
   
    if (isset($Obtenertickets['datos'])){
            $CantidadRegistros = count($Obtenertickets['datos']);
            if ($CantidadRegistros === 0){
                // No se obtuvo registros por lo que mostramos aviso.
                $mensaje_error[] = 'No se encontro ningun tickets';
            }
    } else {
        // hubo un error a la hora obtener el los tickets
        $mensaje_error[] = ' No se permite obtener tanto tickets.'.$filtro.'<br/>'. $Obtenertickets ['error'];
        $CantidadRegistros= 0;
    }
    if (count($mensaje_error)>0){
        echo '<pre>';
        print_r($mensaje_error);
        echo '</pre>';
    }
    
	// --- Ahora contamos registro que hay para es filtro y enviamos clase paginado --- //
    $NPaginado->SetCantidadRegistros($CantidadRegistros);
    $htmlPG = $NPaginado->htmlPaginado(); // Montamos html Paginado
    // Obtenemos clientes con filtro busqueda y la pagina que estamos.
    if (isset($_GET['idCierre'])){
        $Obtenertickets = $CCierres->obtenerTicketsUsuariosCierre($_GET['idUsuario'],$_GET['idCierre'],$Tienda['idTienda'],$filtro.$NPaginado->GetLimitConsulta());
    } else {
        $Obtenertickets = $Tickets->obtenerTickets('Cobrado',$fechas,$filtro , $NPaginado->GetLimitConsulta());
    }
    if (isset($Obtenertickets['datos'])){
        $tickets = $Obtenertickets['datos'];
    } 
	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos fuciones de modulo.
    Cargamos JS del modulo de productos para no repetir funciones: BuscarProducto, metodoClick (pulsado, adonde)
    caja de busqueda en listado 
     -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_tpv/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    </head>
<body>
        <?php
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Tickets <?php echo $estado_ticket;?>s</h2>
				</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<div class="col-sm-2" id="myScrollspy">
                <?php echo $Controler->getHtmlLinkVolver('Volver');?>
                <?php //echo $link_volver;?>
				<h4> Tickets <?php echo $estado_ticket;?>s</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				 	<li><a onclick="metodoClick('VerTicket');";>Ver Ticket</a></li>
				 	<li><a onclick="metodoClick('imprimirTicket');";>Imprimir</a></li>
                <?php if ($ClasePermisos->getAccion("cambiarfechatickets")==1){?>
                    <li><a onclick="metodoClick('cambiarFechaItemsSeleccionado');";>Cambiar Fecha Tickets</a></li>
                <?php }?>
				</ul>
			</div>
			<div class="col-md-10">
                    <?php
                    $texto_fechas = '';
                    if (count($fechas)>0){
                        $texto_fechas = $fechas['inicio'].' a '.$fechas['final'];
                    }
                    echo '<p><strong>'.$CantidadRegistros.' tickets '.$estado_ticket.'s </strong> encontrados entre fecha '.$texto_fechas.'.</p>'
                    ?>
					<div>
						<div>
							<?php
								echo $htmlPG;
							?>	
						</div>
						
					</div>
					<div>
						<form action="./ListaTickets.php" method="GET" name="formBuscar">
							<div class="form-group ClaseBuscar">
								<label>Buscar en Formas de pago, en Num Ticket y por Nombre Cliente.</label>
								<input type="text" name="buscar" value="<?php echo $NPaginado->GetBusqueda()?>">
								<input type="hidden" name ="estado" value="<?php echo $estado_ticket;?>">
								<input type="submit" value="buscar">
								
							</div>
						</form>		
					</div>		
                 <!-- TABLA DE TICKETS -->
			<div>
			<table class="table table-striped">
				<thead>
					<tr>
						<th title='Este es el id de ticketst, el mismo que idticketst de ticketstIva'>ID</th>
						<th>FECHA</th>
						<th>Nº TICKET (<a title="Recuerda que el numero ticket es IdTienda-IdUsuario-NºTicket">*</a>)</th>
						<th>ID CLIENTE</th>
						<th>NOMBRE CLIENTE</th>
						<th>ESTADO</th>
						<th>FORMA PAGO</th>
						<th>TOTAL</th>
						<th>DESCONTAR WEB</th>
					</tr>
				</thead>
				<?php
				$checkUser = 0;
                if (isset($tickets)){
                    foreach ($tickets as $ticket){ 
                        $checkUser = $checkUser + 1;
                        // obtenemos si fue enviado stock
                        $envio = ObtenerEnvioIdTickets($BDTpv,$ticket['id']);
                        echo '<tr>'
                            .'<td class="rowUsuario"><input type="checkbox" name="checkUsu'.$checkUser
                            .'" value="'.$ticket['id'].'">'
                            .'</td>'
                            .'<td>'.$ticket['Fecha'].'</td>'
                            .'<td>'.$ticket['idTienda'].'-'.$ticket['idUsuario'].'-'.$ticket['Numticket'].'</td>'
                            .'<td>'.$ticket['idCliente'].'</td>'
                            .'<td>'.$ticket['Nombre'].'</td>'
                            .'<td>'.$ticket['estado'].'</td>'
                            .'<td>'.$ticket['formaPago'].'</td>'
                            .'<td>'.$ticket['total'].'</td>'
                            .'<td>'.(isset($ticket['idCierre']) ? $ticket['idCierre']['idCierre']:''); ?>
                            </td>
                            <td>
                                <?php
                                    if (isset($envio['enviado_stock'])){
                                        // Quiere decir que se encontro registro
                                        echo '<span title="'.$envio['respuesta_envio'].'">'.$envio['enviado_stock'].'</span>';
                                    }  ;?>
                            </td>
                            
                        </tr>
                    <?php 
                    }
                }
                ?>
			</table>
			</div>
		</div>
	</div>
    </div>
    <?php
	// Añadimos JS necesario para modal.
    echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
    include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
    ?>
</body>
</html>
