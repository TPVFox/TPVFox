<!DOCTYPE html>
<html>
    <head>
        <?php
    include_once './../../inicial.php';
    include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_proveedor/funciones.php';
    include_once $URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php';
    include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';

    $CProveedor= new ClaseProveedor($BDTpv);
    // --- Inicializamos objeto de Paginado --- //
    $NPaginado = new PluginClasePaginacion(__FILE__);
    $campos = array('razonsocial','nombrecomercial','nif');
    $NPaginado->SetCamposControler($campos);
    $filtro = $NPaginado->GetFiltroWhere('OR');
    
    // --- Ahora contamos registro que hay para es filtro y enviamos clase paginado --- //
    $NPaginado->SetCantidadRegistros($CProveedor->contarRegistros($filtro));
    $htmlPG = $NPaginado->htmlPaginado(); // Montamos html Paginado

	$proveedores =  $CProveedor->obtenerProveedores($filtro . $NPaginado->GetLimitConsulta());

	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js"></script>	
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    
  
	
    </head>

<body>
        <?php
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Proveedores: Editar y Añadir Proveedor </h2>
			</div>
	       
			<nav class="col-sm-2" id="myScrollspy">
				<div data-offset-top="505">
				<h4> Proveedores</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
                    <?php 
                      if($ClasePermisos->getAccion("crear")==1){
                    ?>
					<li><a href="#section1" onclick="metodoClick('AgregarProveedor');";>Añadir</a></li>
                    <?php 
                    }
                    if($ClasePermisos->getAccion("modificar")==1){
                    ?>
					<li><a href="#section2" onclick="metodoClick('VerProveedor');";>Modificar</a></li>
                    <?php 
                    }
                    ?>
									<?php //metodoClick js case pulsado 
									//agregarUsuario nos lleva a formulario usuario
									//verUsuario si esta checkado nos lleva vista usuario de ese id
												//si NO nos indica que tenemos que elegir uno de la lista ?>
				</ul>
				</div>	
			</nav>		
			<div class="col-md-10">
					<p>
					 -Proveedores encontrados BD local filtrados:
						<?php echo $CProveedor->contarRegistros($filtro);?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
				<form action="./ListaProveedores.php" method="GET" name="formBuscar">
					<div class="form-group ClaseBuscar">
						<label>Buscar en nombre comercial </label>
						<input type="text" name="buscar" value="">
						<input type="submit" value="buscar">
					</div>
				</form>
                 <!-- TABLA DE PRODUCTOS -->
			<div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>ID</th>
                        <th>NOMBRE COMERCIAL</th>
                        <th>RAZON SOCIAL</th>
                        <th>NIF</th>
                        <th>TELEFONO</th>
                        <th>MOVIL</th>
                        <th>EMAIL</th>
                        <th>FECHA ALTA</th>
                        <th>ESTADO</th>
					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
				foreach ($proveedores as $proveedor){ 
					$checkUser = $checkUser + 1; 
					// Para evitar notice
					if (!isset($proveedor['fecha_creado'])){
						$proveedor['fecha_creado'] = "";
					}
				?>

				<tr>
                    
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $proveedor['idProveedor'];?>">
					</td>
                    <td>
                     <?php 
                    if($ClasePermisos->getAccion("modificar")==1){
                        ?>
                        <a class="glyphicon glyphicon-pencil" href='./proveedor.php?id=<?php echo $proveedor['idProveedor'];?>'>
                    <?php 
                    }
                        ?>
                    </td>
                    <td>
                    <?php 
                    if($ClasePermisos->getAccion("ver")==1){
                        ?>
                        <a class="glyphicon glyphicon-eye-open" href='./proveedor.php?id=<?php echo $proveedor['idProveedor'];?>&estado=ver'>
                    <?php 
                    }
                        ?>
                    
                    </td>
					<td><?php echo $proveedor['idProveedor']; ?></td>
					<td><?php echo $proveedor['nombrecomercial']; ?></td>
					<td><?php echo $proveedor['razonsocial']; ?></td>
					<td><?php echo $proveedor['nif']; ?></td>
                    <td><?php echo $proveedor['telefono']; ?></td>
                    <td><?php echo $proveedor['movil']; ?></td>
                    <?php
                    // Mostrar email
                    
                    if ($proveedor['email']<>''){
                        $email ='<a href="mailto:'.$proveedor['email'].'"><span class="glyphicon glyphicon-envelope"></span><a>';
                    } else {
                        $email = '';
                    }
                    ?>
					<td><?php echo $email; ?></td>
					<td><?php echo $proveedor['fecha_creado']; ?></td>
					<td><?php echo $proveedor['estado']; ?></td>
					
				</tr>

				<?php 
				}
				?>
				
			</table>
			</div>
		</div>
	</div>
    </div>
		
</body>
</html>
