<?php 
include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';

class ClaseBalanza  extends Modelo  {

    public $idTienda; // (int) Id de la tienda , por defecto es la principal, pero se podrá cambiar.
    
    public function __construct($conexion='')
	{
        // Obtenemos la tienda principal
        $this->ObtenerTiendaPrincipal();
    }

    public function ObtenerTiendaPrincipal(){
		// Objetivo:
		// Obtener la tienda principal y guardarla en propiedad tienda.
		// [NOTA] -> Asi no hace falta mandar siempre idTienda
		$Sql = "SELECT idTienda FROM `tiendas` WHERE `tipoTienda`='Principal'";
		$respuesta = $this->consulta($Sql);
		if (count($respuesta['datos']) === 1){
			// Quiere decir que obtuvo un dato solo..
			$this->idTienda = $respuesta['datos'][0]['idTienda'];
           
		}
	}


    public function addBalanza($datos){
        $sql='INSERT INTO `modulo_balanza`(`nombreBalanza`, `modelo`, `conTecla`) VALUES ("'.$datos['nombreBalanza'].'", 
        "'.$datos['modeloBalanza'].'", "'.$datos['teclas'].'")';
        $consulta = $this->consultaDML($sql);
       
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
    
    public function pluDeBalanza($idBalanza, $filtro){
         $sql='Select a.*,  t.crefTienda,b.articulo_name , p.pvpCiva from modulo_balanza_plus as a 
         inner join articulos as b on a.idArticulo=b.idArticulo  INNER JOIN articulosTiendas as t 
         on t.idArticulo=b.idArticulo and t.idTienda = '.$this->idTienda. ' inner join articulosPrecios as p on p.idArticulo=a.idArticulo  
         where a.idBalanza='.$idBalanza.' 
         order by '.$filtro.' asc';
        
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    public function buscarArticuloCampo($campo){
        $sql='SELECT a.idArticulo, a.articulo_name, b.crefTienda, c.codBarras 
        from articulos as a INNER JOIN articulosTiendas as b on a.idArticulo=b.idArticulo inner JOIN
        articulosCodigoBarras as c on a.idArticulo=c.idArticulo inner join tiendas as d on 
        b.idTienda=d.idTienda 
        where d.tipoTienda="principal" and '.$campo;
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    
    public function buscarPluEnBalanza($plu, $idBalanza){
        $sql='select * from modulo_balanza_plus where idBalanza='.$idBalanza.' and plu="'.$plu.'"';
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    public function addPlu($plu, $idBalanza, $tecla, $idArticulo){
        $sql='INSERT INTO `modulo_balanza_plus`(`idBalanza`, `plu`, `tecla`, `idArticulo`) VALUES ('.$idBalanza.', "'.$plu.'", "'.$tecla.'", '.$idArticulo.')';
       //~ error_log($sql);
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
    public function modificarBalanza($id, $nombre, $modelo, $tecla){
        $sql='UPDATE `modulo_balanza` SET `nombreBalanza`="'.$nombre.'",`modelo`="'.$modelo.'",`conTecla`="'.$tecla.'"
         WHERE `idBalanza`='.$id;
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }
}


?>
