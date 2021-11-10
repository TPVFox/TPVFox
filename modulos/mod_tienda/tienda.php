<?php
        /* Tenemos 3 tipos de tienda
         *    principal -> Es la tienda que esta utilizando por defecto, esta no se puede cambiar nunca.
         *    fisica -> Este tipo de tiendas puede haber muchas, se utilizan para estadisticas (años anteriores )
         *    web -> Este tipo de tienda es la actual en la web, solo podemos tener una.
         * */

        include_once './../../inicial.php';
        include_once $URLCom.'/modulos/mod_tienda/funciones.php';
        include_once $URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php';
        $ClaseTienda = new ClaseTienda();
		// Variables por defecto:
        $tabla= 'tiendas'; // Tabla que vamos utilizar.
        $errores= array();    
		$nuevo_creado = '';
		// Definimos posibles tipos de tiendas y estado por defecto.
        // Obtenmos las tiendas principal y  web si hay, para no permitir crear.
        $tiendas =array();
        $t = $ClaseTienda->obtenerTiendas();
        foreach ($t['datos'] as $key=> $tienda){
            if ($tienda['tipoTienda'] == 'principal' || $tienda['tipoTienda'] == 'web' ){
                $tipo = $tienda['tipoTienda'];
                $tiendas[$tipo] = $tienda;
            }
        }
        $montar_radio = 'Si';
        if (isset($_GET['id']){
            if (isset($tiendas['principal']) && $_GET['id'] === $tiendas['principal']['idTienda']){
                // Si existe tienda principal en BD y estamos modificandola no permitimos cambiarla.
                $montar_radio = 'No' ;
            }
        }
		$tiposTiendas = array(
			'fisica' => array (
					'title' 	    => "Tienda fisica",
                    'texto_title'   => "Puede haber varias",
                    'checked'	    => "checked",   // Indicamos por defecto
					'display'	    => '' ,         // Indicamos por defecto que si muestra campos tienda fisica
					'disabled' 	    => '' ,         // por defecto esta activo el cambio tipo tienda.
                    'montar_radio'  => $montar_radio
					),
			'web' =>  array (
					'title' 	    => "Tienda Web",
                    'texto_title'   => isset($tiendas['web']) ? 'Existe id:'.$tiendas['web']['idTienda'] :"No hay, solo puede haber una",
                    'checked'	    => "",
					'display'	    => 'style="display:none;"',                 // Indicamos NO se campos tienda web por defecto
					'disabled' 	    => isset($tiendas['web'])? 'disabled':'',    // por defecto esta desactivado el cambio tipo tienda.
                    'montar_radio'  => $montar_radio
                    ),
			'principal' =>  array (
					'title' 	    => "Tienda Principal",
                    'texto_title'   => isset($tiendas['principal']) ? 'Existe id:'.$tiendas['principal']['idTienda'] :"No hay, solo puede haber una",
					'checked'	    => "",
					'display'	    => 'style="display:none;"',                     // Indicamos por defecto que si muestra campos tienda fisica
					'disabled' 	    => isset($tiendas['principal'])?'disabled':'',  // por defecto esta desactivado el cambio tipo tienda.
                    'montar_radio'  => 'Si'
					)
		);
        
        
        // Si tiene GET id cargamos datos tiendaUnica
		if (isset($_GET['id'])){
            $titulo = "Modificar Tienda:";
            $T = $ClaseTienda->obtenerUnaTienda($_GET['id']);
            if (isset($T['error'])){
                echo '<pre>';
                    print_r($T);
                echo '</pre>';
                exit();
            } else {
                $TiendaUnica= $T['datos']['0'];
            }
        } else {
            $titulo = "Crear Tienda";
            $TiendaUnica = array(
                        'idTienda'          => '',
                        'tipoTienda'        => 'fisica',
                        'razonsocial'       => '',
                        'nif'               => '',
                        'telefono'          => '',
                        'estado'            => '',
                        'NombreComercial'   => '',
                        'direccion'         => '',
                        'emailTienda'       => '',
                        'nombreEmail'       => '',
                        'ano'               => '',
                        'dominio'           => '', 
                        'key_api'           => ''
                        );
        }
		// Ahora comprobamos si tenemos POST. Es decir se envio ya el formulario
		if(count($_POST)>0){
			// Venimos de vuelta debemos tener los campos obligatorios cubiertos por lo menos.
			// [CREAMOS LOS CAMPOS COMPROBAR];
			$campos_comprobar= array('razonsocial','nif','telefono','tipoTienda','estado');
            // Ahora insertamos o modificamos
            $resp = FALSE;
			if (intval($_POST['idtienda']) > 0){
				// Entramos si ya existe tienda, porque tiene id, por lo que modificamos.
				// Comprobamos: 
				$resp = modificarDatos($datos,$BDTpv,$tabla,$_POST['idtienda']);
				if (isset($resp['error'])){
					$tipomensaje= "danger";
					$mensaje = "Error a la hora modificar datos de la tienda!";
				} else {
					$tipomensaje= "info";
					$mensaje = "Modificada correctamente la tienda.";
				}
			} else {
				// Entramos si es uno nuevo y se va añadir
				//~ $resp = insertarDatos($datos,$BDTpv,$tabla,$campos_enviar);
                $resp = $ClaseTienda->addTienda($_POST);
				if (isset($resp['error'])){
					$tipomensaje= "danger";
					$mensaje = $resp['error'];
				} else {
					$tipomensaje= "info";
					$mensaje = "Nueva tienda creada con id.".$resp;
                    $titulo = "Nueva Tienda Creada";
                    $T = $ClaseTienda->obtenerUnaTienda($resp);
                    if (isset($T['error'])){
                        echo '<pre>';
                            print_r($T);
                        echo '</pre>';
                        exit();
                    } else {
                        $TiendaUnica= $T['datos']['0'];
                        // Ahora monto script para cambiar url y añadir id creado url para que pueda modificar y crearlo
                        $nuevo_creado = "<script>history.pushState(null,'','?id='+".$resp.");</script>";
                    }
				}
			}
		};
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include_once $URLCom.'/head.php';?>	
		<script src="<?php echo $HostNombre; ?>/modulos/mod_tienda/funciones.js"></script>
        <?php echo $nuevo_creado;?>
	</head>
	<body>
		<?php
         include_once $URLCom.'/modulos/mod_menu/menu.php';
		?>
		<div class="container">
			<?php if (isset($mensaje)){   ?> 
			<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje ;?></div>
			<?php }?>
			<h1 class="text-center"> <?php echo $titulo.':'. $TiendaUnica['NombreComercial'];?></h1>
			<a class="text-ritght" href="./ListaTiendas.php">Volver Atrás</a>

			<div class="col-md-12">
				<div class="col-md-3">
					<img src="<?php echo $HostNombre.'/css/img/tienda.svg';?>" style="width:100%;">
				</div>
				<form action="" method="post" name="formUsuario">
				<div class="col-md-9">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Tipo de tienda</label>
                            <?php 
                            echo htmlInputRatio($tiposTiendas,$TiendaUnica['tipoTienda']);
                            ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h3>Datos Comunes</h3>
                        <div class="form-group">
                            <div class="col-md-2">
                                <label>Id(<span title="Id de Tienda">*</span>):</label>
                                <input type="text" id="idtienda" name="idtienda" value="<?php echo $TiendaUnica['idTienda'] ;?>" readonly size="2">
                            </div>
                            <div class="col-md-4">
                                <label>NIF:</label>
                                <input type="text" id="nif" name="nif" placeholder="B36332211"  
                                value="<?php echo $TiendaUnica['nif'];?>" required size="9">
                            </div>
                            <div class="col-md-6">
                                <?php echo htmlEstados($TiendaUnica['estado'])?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Razón social:</label>
                            <input type="text" id="razonsocial" name="razonsocial" placeholder="razon social" value="<?php echo $TiendaUnica['razonsocial'];?>" required  >
                        </div>
                        <div class="form-group">
                            <label>Nombre Comercial:</label>
                            <input type="text" id="NombreComercial" name="NombreComercial" placeholder="nombre comercial" value="<?php echo $TiendaUnica['NombreComercial']?>" >
                        </div>
                        <div class="form-group">
                            <label>Año Contable:</label>
                            <input type="text" id="ano" name="ano" placeholder="2014"  value="<?php echo $TiendaUnica['ano'];?>"  >
                        </div>

                    </div>
                    <div class="col-md-6">
                         <!-- Solo debería mostrar uno según tipo tienda-->
                         <?php
                            $Tipo = $TiendaUnica['tipoTienda'];
                            if ($Tipo === 'fisica' || $Tipo === 'principal' ){
                                $display = '';
                            } else{
                                $display = 'style="display:none;"';
                            }
                            echo '<h3> Datos '.$tiposTiendas[$Tipo]['title'].'</h3>';
                         ?>
                        <div class="mostrar_fisica_y_principal" <?php echo $display; ?>>
                            <div class="form-group">
                                <label>Dirección:</label>
                                <textarea id="direccion" name="direccion"><?php echo $TiendaUnica['direccion'];?> </textarea>
                            </div>
                            <div class="form-group">
                                <label>Teléfono:</label>
                                <input type="text" id="telefono" name="telefono" placeholder="986 22 22 22"  value="<?php echo $TiendaUnica['telefono'];?>"  required >
                            </div>  
                        </div>
                        <div class="mostrar_principal" <?php echo $tiposTiendas['principal']['display']; ?>>
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="text"  id="emailTienda" name="emailTienda" placeholder="Email de tienda" value="<?php echo $TiendaUnica['nombreEmail'];?>" >
                            </div>
                            <div class="form-group">
                                <label>Nombre usuario email:</label>
                                <input type="text"  id="nombreEmail" name="nombreEmail" placeholder="Nombre mostrar email" value="<?php echo $TiendaUnica['nombreEmail'];?>" >
                            </div>
                        </div>
                        <!-- Solo debería mostrar uno según tipo tienda-->
                        <div class="mostrar_web" <?php echo $tiposTiendas['web']['display'] ?>>
                            <div class="form-group">
                                <label>Dominio:(<span title="Sin http, ni www">*</span>)</label>
                                <input type="text"  id="dominio" name="dominio" placeholder="dominio.com" value="<?php echo $TiendaUnica['dominio'];?>" >
                            </div>
                            <div class="form-group">
                                <label>Key Api:(<span title="Key Api Plugin Joomla">*</span>)</label>
                                <input type="text" id="key_api" name="key_api" value="<?php echo $TiendaUnica['key_api'];?>" >
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-md-12 text-right">
                    <input class="btn btn-primary" type="submit" value="Guardar">
                </div>
                </form>
            </div>
        </div>
	</body>
</html>
