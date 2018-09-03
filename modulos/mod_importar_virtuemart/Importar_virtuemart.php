
<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
    include_once $URLCom.'/head.php';
    include ($URLCom.'/controllers/Controladores.php');
    include_once ($URLCom.'/modulos/mod_importar_virtuemart/funciones.php');
    include_once ($URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php');
    
    $ClaseTienda=new ClaseTienda($BDTpv);
	$tiendasWeb=$ClaseTienda->tiendasWeb();
    $comprobaciones=array();
    $acciones=array(
        array(
            'valor'=>1,
            'accion'=>'Bajar de la web los productos nuevos a TPV'   
        ),
        array(
            'valor'=>2,
            'accion'=>'Modificar los productos TPV según la web'
        )
    );
    if(isset($_POST['enviar'])){
       
        
        if($_POST['tiendaWeb']==0){
            $comprobaciones[1]=array ( 'tipo'=>'Danger!',
								 'dato' => '',
								 'class'=>'alert alert-danger',
								 'mensaje' => 'NO HAS SELECCIONADO UNA TIENDA WEB!'
								 );
        }
        if($_POST['accionesWeb']==0){
            $comprobaciones[2]=array ( 'tipo'=>'Danger!',
								 'dato' => '',
								 'class'=>'alert alert-danger',
								 'mensaje' => 'NO HAS SELECCIONADO NINGUNA ACCIÓN!'
								 );
        }
        echo '<pre>';
        print_r($_POST);
        echo '</pre>';
    }
?>
</head>
<body>
      <?php
            include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
    <div class="container">
        <?php 
        if(count($comprobaciones>0)){
            foreach($comprobaciones as $comprobacion){
                  echo '<div class="'.$comprobacion['class'].'">'
				. '<strong>'.$comprobacion['tipo'].' </strong> '.$comprobacion['mensaje'].' <br> '.$comprobacion['dato']
				. '</div>';
            }
          
        }
        
        ?>
      <form action="Importar_virtuemart.php" method="POST">
        <h2 class="text-center">Importación o Actualizacion de datos de Virtuemart a TPV.</h2>
        <div class="col-md-5">
            <h3>Parametros a configurar</h3>
            <div class="col-md-12">
                <label>Selecciona la tienda On Line con la quieres importar o actualizar datos:</label>
                <select id="tiendaWeb" name="tiendaWeb">
                  <option value="0">Selecciona una tienda web</option>  
                  <?php 
                  foreach ($tiendasWeb['datos'] as $tienda){
                      echo '<option value="'.$tienda['idTienda'].'">'.$tienda['razonsocial'].'</option>';
                  }
                  ?>
                </select>
            </div>
            <div class="col-md-12">
                <label>Selecciona una acción:</label>
                <select id="accionesWeb" name="accionesWeb">
                  <option value="0">Selecciona una acción</option>  
                  <?php 
                  foreach ($acciones as $accion){
                      echo '<option value="'.$accion['valor'].'">'.$accion['accion'].'</option>';
                  }
                  ?>
                </select>
            </div>
             <div class="col-md-12">
                 <button type="submit" name="enviar" class="btn btn-success pull-right" >Enviar</button>
            </div>
        </div>
        <div class="col-md-7">
            <h3>Proceso</h3>
            <div class="col-md-12">
                
            </div>
        </div>
        </form>
    </div>

</body>
</html>
