<?php 
include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';

class ClaseBalanza  extends Modelo  {

    public function addBalanza($datos){
        $sql='INSERT INTO `modulo_balanza`(`nombreBalanza`, `modelo`, `conTecla`) VALUES ("'.$datos['nombreBalanza'].'", 
        "'.$datos['modeloBalanza'].'", "'.$datos['teclas'].'")';
        $consulta = $this->consultaDML($sql);
        $balanza=$this->ultimaBalanza();
        $consulta['id']=$balanza['datos'][0]['idBalanza'];
        if (isset($consulta['error'])) {
            return $consulta;
        }
       
    }
    public function todasBalanzas(){
        $sql='SELECT * from modulo_balanza ';
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    public function datosBalanza($idBalanza){
        $sql='SELECT * from modulo_balanza where idBalanza='.$idBalanza;
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    
    public function pluDeBalanza($idBalanza){
         $sql='select a.*, b.articulo_name from modulo_balanza_plus as a 
         inner join articulos as b on a.idArticulo=b.idArticulo where a.idBalanza='.$idBalanza;
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    public function buscarArticuloCampo($campo){
        $sql='SELECT a.idArticulo, a.articulo_name, b.crefTienda, c.codBarras 
        from articulos as a INNER JOIN articulosTiendas as b on a.idArticulo=b.idArticulo inner JOIN
        articulosCodigoBarras as c on a.idArticulo=c.idArticulo inner join tiendas as d on 
        b.idTienda=d.idTienda where d.tipoTienda="principal" and '.$campo;
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    
    public function buscarPluEnBalanza($plu, $idBalanza){
        $sql='select * from modulo_balanza_plus where idBalanza='.$idBalanza.' and plu="'.$plu.'"';
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    public function addPlu($plu, $idBalanza, $tecla, $idArticulo){
        $sql='INSERT INTO `modulo_balanza_plus`(`idBalanza`, `plu`, `tecla`, `idArticulo`) VALUES ('.$idBalanza.', 
        "'.$plu.'", "'.$tecla.'", '.$idArticulo.')';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }
    public function eliminarplu($idBalanza, $plu){
        $sql='DELETE FROM `modulo_balanza_plus` WHERE idBalanza='.$idBalanza.' and plu="'.$plu.'"';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }
    public function ultimaBalanza(){
        $sql='select idBalanza from modulo_balanza order by idBalanza desc limit 1 ';
        $resultado = $this->consulta($sql);
        return $resultado;
    }
}


?>
