<!DOCTYPE html>
<html>
    <head>
        <?php
    include_once './../../inicial.php';
	include $URLCom.'/head.php';
	include $URLCom.'/modulos/mod_cliente/funciones.php';
	include $URLCom.'/plugins/paginacion/paginacion.php';
	include $URLCom.'/controllers/Controladores.php';
	
	
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
	
	$vista = 'clientes';
	$LinkBase = './ListaClientes.php?';
	$OtrosParametros = '';
	//$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
	

	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
	} else {
		$desde = 0;
	}
	//si hay palabras a buscar
	if ($stringPalabras !== '' ){
		$campoBD='razonsocial';
		$campo2BD = 'Nombre';
		$campo3BD = 'nif';
		$WhereLimite= $Controler->paginacionFiltroBuscar($stringPalabras,$LimitePagina,$desde,$campoBD,$campo2BD,$campo3BD);
		$filtro=$WhereLimite['filtro'];
		$OtrosParametros=$stringPalabras;
	}
	
	//consultamos 2 veces: 1 para obtner numero de registros y el otro los datos.
	$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
	//echo 'pgactual: '.$PgActual.' cantidadReg : '.$CantidadRegistros.' lmtPag :'.$LimitePagina.' linkBase :'.$LinkBase.' OtrosParametros: '.$OtrosParametros;

	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
	if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro'].$WhereLimite['rango'];
	} else {
		$filtro= " LIMIT ".$LimitePagina." OFFSET ".$desde;
	}

	//echo '</br>'.$filtro.' ';
	$clientes = obtenerClientes($BDTpv,$filtro);
	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
  
    </head>

<body>
        <?php
       include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Clientes: Editar y Añadir Clientes </h2>
					<?php 
					//~ echo 'Numero filas'.$Familias->num_rows.'<br/>';
					//~ echo '<pre class="text-left">';
					//~ print_r($Familias);
					//~ 
					//~ echo '</pre>';
					?>
				</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<nav class="col-sm-2" id="myScrollspy">
				<div data-offset-top="505">
				<h4> Clientes</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
                    <?php
                     if($ClasePermisos->getAccion("crear")==1){
                    ?>
					<li><a href="#section1" onclick="metodoClick('AgregarCliente');">Añadir</a></li>
                    <?php 
                    }
                    
                    if($ClasePermisos->getAccion("modificar")==1){
                        ?>
					<li><a href="#section2" onclick="metodoClick('VerCliente');">Modificar</a></li>
                    <?php 
                }
                if($ClasePermisos->getAccion("tarifa")==1){
                    ?>
					<li><a href="#" onclick="metodoClick('TarificarCliente');">Tarifa</a></li>
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
					 -Clientes encontrados BD local filtrados:
						<?php echo $CantidadRegistros;?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
				<form action="./ListaClientes.php" method="GET" name="formBuscar">
					<div class="form-group ClaseBuscar">
						<label>Buscar en nombre, razon social o nif: </label>
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
						<th>NOMBRE</th>
						<th>RAZON SOCIAL</th>
						<th>NIF</th>
						<th>TELEFONO</th>
						<th>EMAIL</th>
						<th>ESTADO</th>

					</tr>
				</thead>	
				<?php
				$checkUser = 0;
				foreach ($clientes as $cliente){ 
					$checkUser = $checkUser + 1; 
					?>
					<tr>
						<td class="rowUsuario">
							<input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $cliente['idClientes'];?>">
						</td>
                        <td>
                        <?php 
                        if($ClasePermisos->getAccion("modificar")==1){
                        ?>
                            <a class="glyphicon glyphicon-pencil" href='./cliente.php?id=<?php echo $cliente['idClientes'];?>'>
                        <?php 
                        }
                        ?>
                        </td>
                        <td>
                        <?php 
                        if($ClasePermisos->getAccion("ver")==1){
                        ?>
                        <a class="glyphicon glyphicon-eye-open" href='./cliente.php?id=<?php echo $cliente['idClientes'];?>&estado=ver'>
                        <?php 
                        }
                        ?>
                        </td>
						<td><?php echo $cliente['idClientes']; ?></td>
						<td><?php echo $cliente['Nombre']; ?></td>
						<td><?php echo $cliente['razonsocial']; ?></td>
						<td><?php echo $cliente['nif']; ?></td>
						<td><?php echo $cliente['telefono']; ?></td>
						<td><?php echo $cliente['email']; ?></td>
						<td><?php echo $cliente['estado']; ?></td>
					</tr>
					<?php 
				} //fin de foreach ckeckUser
				?>				
			</table>
			</div>
		</div>
	</div>
    </div>
	<?php 
    include_once $URLCom.'/pie.php';
    ?>	
</body>
</html>
