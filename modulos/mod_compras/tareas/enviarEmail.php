<?php
//@Objetivo:
        //Imprimir un documento , dependiendo de donde venga se pone el nombre y envía todos los datos  
        //a la función montarHTMLimprimir que lo que realiza es simplemente montar el html una parte copn la cabecera y 
        //otra con el cuerpo del documento
        //debajo cargamos las clases de imprimir y la plantilla una vez generada y lista la plantilla devolvemos la ruta
        //para así desde javascript poder abrirla
        
        $id=$_POST['id'];
        $dedonde=$_POST['dedonde'];
        $idTienda=$_POST['idTienda'];
        $destinatario=$_POST['destinatario'] ;

        $mensaje='';
        $asunto='';
        $nombreTmp=$dedonde."compras.pdf";
        $htmlImprimir=montarHTMLimprimir($id, $BDTpv, $dedonde, $idTienda);
        $cabecera=$htmlImprimir['cabecera'];
        $margen_top_caja_texto= 56;
        $html=$htmlImprimir['html'];
        include_once $URLCom.'/controllers/planImprimir.php';
        
        $fichero = $RutaServidor.$rutatmp.'/'.$nombreTmp;
        include_once $URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php';
        $CTienda = new ClaseTienda();
       
        $res = $CTienda->tiendaPrincipal();
        $tiendaPrincipal=$res['datos'][0];
        $datosServidor = $CTienda->obtenerArrayDatosServidor($tiendaPrincipal['servidor_email']);
        $origen = array( 'email'    => $datosServidor['emailTienda'],
                         'nombre'   => $datosServidor['nombreEmail']
                         );
        include_once $URLCom.'/clases/CorreoElectronico.php';
        $respuesta = CorreoElectronico::enviar($destinatario,'mensaje del correo','asunto importante',$fichero,$origen);
    ?>
