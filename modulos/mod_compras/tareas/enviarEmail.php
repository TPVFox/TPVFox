<?php
//@Objetivo:
//Imprimir un documento , dependiendo de donde venga se pone el nombre y envía todos los datos  
        
        $id=$_POST['id'];
        $dedonde=$_POST['dedonde'];
        $idTienda=$_POST['idTienda'];
        $destinatario=$_POST['destinatario'] ;

        $mensaje=$_POST['message'];
        $asunto=$_POST['asunto'];
        $nombreTmp=$dedonde."compras.pdf";
        include_once $URLCom.'/modulos/mod_compras/tareas/imprimirDocumento.php';    
        $margen_top_caja_texto= 56;
        include_once $URLCom.'/controllers/planImprimir.php';
              
        $fichero = $RutaServidor.$rutatmp.'/'.$nombreTmp;
        
        include_once $URLCom.'/clases/CorreoElectronico.php';
             
        $respuesta = CorreoElectronico::enviar($destinatario,$mensaje,$asunto,$fichero);
        //~ echo '<pre>';
        //~ print_r($respuesta);
        //~ echo '</pre>';
    ?>
