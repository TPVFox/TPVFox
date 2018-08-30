<!DOCTYPE html>
<html>
    <head>
        <?php
    include_once './../../inicial.php';
    include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_proveedor/funciones.php';
    include_once $URLCom.'/plugins/paginacion/paginacion.php';
    include_once $URLCom.'/controllers/Controladores.php';
	
	//INICIALIZAMOS variables para el plugin de paginado:
	//$PgActual = 1 por defecto
	//$CantidadRegistros , usamos la funcion contarRegistro de la class controladorComun /controllers/Controladores  
	//$LimitePagina = 40 o los que queramos
	//$LinkBase --> en la vista que estamos trabajando ListaProductos.php? para moverse por las distintas paginas
	//$OtrosParametros
	$palabraBuscar=array();
	$stringPalabras='';
	$filtro = ''; // por defecto
	$PgActual = 1; // por defecto.
	$LimitePagina = 40; // por defecto.
	// Obtenemos datos si hay GET y cambiamos valores por defecto.
		if (isset($_GET['pagina'])) {
			$PgActual = $_GET['pagina'];
		}
		if (isset($_GET['buscar'])) {  
			//recibo un string con 1 o mas palabras
			$stringPalabras = $_GET['buscar'];
			$palabraBuscar = explode(' ',$_GET['buscar']);
		} 

	// Creamos objeto controlado comun, para obtener numero de registros. 
	//parametro necesario para plugin de paginacion
	//funcion contarRegistro necesita:
	//$BDTpv 
	//$vista --> es la tabla en la que trabajamos
	//$filtro --> por defecto es vacio, suele ser WHERE x like %buscado%, caja de busqueda
	
	$Controler = new ControladorComun; 
	
	$vista = 'proveedores';
	$LinkBase = './ListaProveedores.php?';
	$OtrosParametros = '';
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
	} else {
		$desde = 0;
	}
	//si hay palabras para buscar
	if ($stringPalabras !== '' ){
		$campoBD='razonsocial';
		$campo2BD='nombrecomercial';
		$WhereLimite= $Controler->paginacionFiltroBuscar($stringPalabras,$LimitePagina,$desde,$campoBD,$campo2BD);
		$filtro=$WhereLimite['filtro'];
		$OtrosParametros=$stringPalabras;
	}
	
	//consultamos 2 veces: 1 para obtner numero de registros y el otro los datos.
	$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
	
		
	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
	if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro'].$WhereLimite['rango'];
	} else {
		$filtro= " LIMIT ".$LimitePagina." OFFSET ".$desde;
	}
	$proveedores = obtenerProveedores($BDTpv,$filtro);
	//~ echo '<pre>';
	//~ print_r($proveedores);
	//~ echo '</pre>';
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
        //~ include_once $URLCom.'/header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Proveedores: Editar y Añadir Proveedor </h2>
			</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
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
						<?php echo $CantidadRegistros;?>
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
						<th>EMAIL</th>
						<th>FECHA ALTA</th>
						<th>ESTADO</th>
					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
				foreach ($proveedores['items'] as $proveedor){ 
					$checkUser = $checkUser + 1; 
					// Para evitar notice
					if (!isset($proveedor['fechaalta'])){
						$proveedor['fechaalta'] = "";
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
					<td><?php echo $proveedor['email']; ?></td>
					<td><?php echo $proveedor['fechaalta']; ?></td>
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
