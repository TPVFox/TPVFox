
<?php
    include_once './../../inicial.php';
	include_once $URLCom.'/modulos/mod_informes/funciones.php';
    include_once $URLCom.'/modulos/mod_informes/clases/ClaseInformes.php';
    $CInformes= new ClaseInformes();
?>
	
    <!DOCTYPE html>
<html>
    <head>  
    <?php include_once $URLCom.'/head.php';?>	
    </head>
<body>
        <?php
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Listados de informes </h2>
			</div>
	       
			<nav class="col-sm-2" id="myScrollspy">
				<div data-offset-top="505">
				<h4> Informes</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
                    <?php 
                      if($ClasePermisos->getAccion("ejecutar")==1){
                    ?>
					<li><a href="#section1" onclick="metodoClick('Ejecutar');";>Ejecutar</a></li>
                    <?php 
                    }
                    ?>
                    <li><a>Exportar CSV</a></li>
				</ul>
				</div>	
			</nav>		
			<div class="col-md-10">
                    <div class="col-md-4 form-group">
						<label>Fecha Inicio</label>
						<input type="date" id="idFechaInicio" name="fechaInicio">
					</div>
                    <div class="col-md- 4 form-group">
						<label>Fecha Final</label>
						<input type="date" id="idFechaFinal" name="fechaFinal">
					</div>
			<div>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>NOMBRE INFORME</th>
                            <th>DESCRIPCION INFORME</th>
                            <th>Opciones</th>
                            <th>Guardado</th>
                        </tr>
                    </thead>
                   
                    <tr>
                        <td><input class="rowCheck" type="checkbox" name="informe" value="1">
                        <td><strong>Suma compras por proveedores</strong></td>
                        <td>Sumamos albaranes por proveedores y como opción los facturados ,Guardados o ambos</td>
                        <td>
                            <select id="opcion2">
                              <option value="1" selected>Todos</option>
                              <option value="2">Facturados</option>
                              <option value="2">Sin facturar</option>
                            </select>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><input class="rowCheck" type="checkbox" name="informe" value="2">
                        <td><strong>Suma compras por familias</strong></td>
                        <td>Sumamos compras por familias desglosando hijos y como opción desglosando productos o no, segun la opcion escogida</td>
                        <td>
                            <select id="opcion1">
                              <option value="1" selected>Solo familias</option>
                              <option value="2">Familias y productos</option>
                            </select>
                        </td>
                        <td></td>
                    </tr>
			</div>
		</div>
	</div>
    </div>
    <script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos funciones de modulo. -->
    <script src="<?php echo $HostNombre; ?>/modulos/mod_informes/funciones.js" type="module"></script>	
    <?php
     echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
     include $URLCom.'/plugins/modal/ventanaModal.php';
    ?>
</body>
</html>
