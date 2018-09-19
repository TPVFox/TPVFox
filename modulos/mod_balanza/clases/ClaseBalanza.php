<?php 
include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';

class ClaseBalanza extends Modelo  {
    public function addBalanza($datos){
        $sql='INSERT INTO `modulo_balanza`(`nombreBalanza`, `modelo`, `conTecla`) VALUES ("'.$datos['nombreBalanza'].'", 
        "'.$datos['modeloBalanza'].'", "'.$datos['teclas'].'")';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }
    public function todasBalanzasLimite($filtro){
        $sql='SELECT * from modulo_balanza '.$filtro;
        $resultado = $this->consulta($sql);
        return $resultado;
    }
}


?>
