<?php 

include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';

class ClaseTienda extends Modelo  {
   

    public function tiendasWeb(){
        $sql='SELECT * FROM tiendas where tipoTienda="web"';
        $respuesta = parent::Consulta($sql);
        return $respuesta;
        
    }
}



?>
