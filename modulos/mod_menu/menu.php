<?php
    // No creo que debe ser así.. de momento lo dejamos..
    $Permisos=$thisTpv->permisos->permisos;
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
$xml=simplexml_load_file($URLCom.'/modulos/mod_menu/parametrosMenu.xml');
foreach ($xml->item_nivel_1 as $nivel1){
    if(isset($nivel1['vista'])){
        if(isset($nivel1['modulo'])){
            $comprobar=$ClasePermisos->comprobarPermisos($nivel1, $Permisos);
            if($comprobar==1){
                 echo '<li><a href="'.$HostNombre.'/modulos/'.$nivel1['modulo'].'/'.$nivel1['vista'].'">'.$nivel1['descripcion'].'</a></li>';
            }
        }else{
            echo '<li><a href="'.$HostNombre.'/'.$nivel1['vista'].'">'.$nivel1['descripcion'].'</a></li>';
        }
        
    }else{
         echo '<li class="dropdown">
							<a class="dropdown-toggle" data-toggle="dropdown" href="#">'.$nivel1['descripcion'].'
							<span class="caret"></span></a>
							<ul class="dropdown-menu">';
        foreach ($nivel1->item_nivel_2 as $nivel2){
             
            if(isset($nivel2['modulo'])){
               $comprobar=$ClasePermisos->comprobarPermisos($nivel2, $Permisos);
                 if($comprobar==1){
                    echo '<li><a href="'.$HostNombre.'/modulos/'.$nivel2['modulo'].'/'.$nivel2['vista'].'">'.$nivel2['descripcion'].'</a></li>';
               }
            }else{
                echo '<li><a href="'.$HostNombre.'/'.$nivel2['vista'].'"></a>'.$nivel2['descripcion'].'</li>';
            }
           
        }
          echo'</ul>
                        </li>';
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


