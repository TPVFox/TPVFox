<?php
    // No creo que debe ser así.. de momento lo dejamos..
    //Cargamos los permisos;
    $Permisos=$thisTpv->permisos->permisos;
    include_once $URLCom.'/modulos/mod_menu/clases/ClaseMenu.php';
    $Cmenu = new ClaseMenu;
    $xml = $Cmenu->items;
    //~ echo '<pre>';
    //~ print_r($Permisos);
    //~ echo '</pre>';
?>

<header>
	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
				  <span class="sr-only">Desplegar navegación</span>
				  <span class="icon-bar"></span>
				  <span class="icon-bar"></span>
				  <span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="#">TpvFox</a>
			</div>
            <div class="collapse navbar-collapse navbar-ex1-collapse">
				<ul class="nav navbar-nav navbar-left ">
<?php 

foreach ($xml->item_nivel_1 as $nivel1){
    $items_nivel2 = count($nivel1); // Contamos si tiene hijos (nivel2)
    $tipo = (isset($nivel1['vista'])) ? 'vista' : 'separador';
    if ($tipo == 'vista'){
        // Ahora tendría comprobar si tiene permiso.
        $comprobar=$ClasePermisos->comprobarPermisos($Permisos,$nivel1['modulo'],$nivel1['vista']);
        if ($comprobar['permiso'] == 'Ok'){
            echo '<li><a href="'.$HostNombre.$comprobar['link'].'">'.$nivel1['descripcion'].'</a>';
        }
    } else {
        echo '<li class="dropdown">
							<a class="dropdown-toggle" data-toggle="dropdown" href="#">'.$nivel1['descripcion'].'
							<span class="caret"></span></a>
							<ul class="dropdown-menu">';
    }
    if ($items_nivel2 > 0){
        //~ echo '<pre>';
        //~ print_r($nivel1->item_nivel_2);
        //~ echo '</pre>';
           // Entonces recorremos items de nivel 2
            foreach ($nivel1->item_nivel_2 as $nivel2){
                
                // Ahora tendría comprobar si tiene permiso.
                $comprobar=$ClasePermisos->comprobarPermisos($Permisos,$nivel2['modulo'],$nivel2['vista']);
                //~ error_log($comprobar['permiso']);
                if ($comprobar['permiso'] == 'Ok'){
                    
                    echo '<li><a href="'.$HostNombre.$comprobar['link'].'">'.$nivel2['descripcion'].'</a></li>';
                }
            }

            echo'</ul>';
    
    }
        // Cierro li nivel 1
        if ($comprobar['permiso'] == 'Ok'){
            //Solo cierro li de nivel 1 , si se creo..
            echo '</li>';
        }
    
}
?>
	</ul>
				
				<div class="nav navbar-nav navbar-right">
					
					<a href="<?php echo $HostNombre;?>/modulos/mod_usuario/usuario.php?id=<?php echo $Usuario['id']?>&inicio=1" style="color:black;"><span class="glyphicon glyphicon-user"></span><?php echo $Usuario['login'];?></a>
					<?php
					if ($_SESSION['estadoTpv'] == "Correcto"){
					?>

					<a href="<?php echo $HostNombre.'/plugins/controlUser/modalUsuario.php?tipo=cerrar';?>">Cerrar</a>
					<?php
			}
				
				?>
				</div>
				<div class="nav navbar-nav navbar-right" style="margin-right:50px">
					<div id="tienda"><?php echo $Tienda['razonsocial'];?></div>
					
				</div>
			</div>
			
		</div>
	</nav>
<!-- Fin de menu -->
</header>


