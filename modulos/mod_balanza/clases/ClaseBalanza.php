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
        //@OBjetivo: añadir una balanza nueva
        //Parametros: datos de la balanza: nombre, modelo y si tiene tecla o no
        $sql='INSERT INTO `modulo_balanza`(`nombreBalanza`, `modelo`, `conTecla`) VALUES ("'.$datos['nombreBalanza'].'", 
        "'.$datos['modeloBalanza'].'", "'.$datos['teclas'].'")';
        $consulta = $this->consultaDML($sql);
       
        if (isset($consulta['error'])) {
            return $consulta;
        }
       
    }

   
    public function todasBalanzas(){
        //@objetivo: Mostrar todos los datos de todas las balanza
        $sql='SELECT * from modulo_balanza ';
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    public function datosBalanza($idBalanza){
        //@Objetivo: Mostrar datos de una balanza en concreto
        $sql='SELECT * from modulo_balanza where idBalanza='.$idBalanza;
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    
    public function pluDeBalanza($idBalanza, $filtro){
        //Objetivo: Obtener los plu de la balanza con todos los datos que necesitamos para mostrar
        //Parametros:
        //  idBalanza: id de la balanza
        //  filtro: Filtro por el que vamos a ordenar, puede ser por tecla o por número de plu
         $sql='Select a.*,  t.crefTienda,b.articulo_name ,b.tipo, p.pvpCiva from modulo_balanza_plus as a 
         inner join articulos as b on a.idArticulo=b.idArticulo  INNER JOIN articulosTiendas as t 
         on t.idArticulo=b.idArticulo and t.idTienda = '.$this->idTienda. ' inner join articulosPrecios as p on p.idArticulo=a.idArticulo  
         where a.idBalanza='.$idBalanza.' 
         order by '.$filtro.' asc';
        
        $plus = $this->consulta($sql);
        // Ahora comprobamos si hay alguno repetido en esta balanza.
        $idsProductos = array_column($plus['datos'], 'idArticulo');
        // Eliminamos si hay duplicados con
        $idsProductosUnicos = array_unique($idsProductos);
        $duplicado = array();
        if (count($idsProductos) !== count($idsProductosUnicos)){
            // Obtenemos la diferencia, es decir aquellos registros duplicados.
            $duplicado = array_diff_assoc($idsProductos,$idsProductosUnicos);
        }
        if (count($duplicado)>0 ){
            // Hay duplicados, por lo que marcamos UNO DE ELLOS. Ojo solo puedo marcar uno.. sin recorrer todos plus
            foreach ($duplicado as $key=>$valor){
                $plus['datos'][$key]['duplicado'] = 'KO';
                
            }
        }

        $resultado = $plus;
        return $resultado;
    }
    public function buscarArticuloCampo($busqueda){
        //@ Objetivo:
        // Buscar Articulos según el campo
                $sql='SELECT a.idArticulo, a.articulo_name, b.crefTienda, c.codBarras,p.pvpCiva
                from articulos as a LEFT JOIN articulosTiendas as b on a.idArticulo=b.idArticulo LEFT JOIN
                articulosCodigoBarras as c on a.idArticulo=c.idArticulo LEFT join tiendas as d on 
                b.idTienda=d.idTienda  left join articulosPrecios as p on p.idArticulo=a.idArticulo 
                where d.tipoTienda="principal" and '.$busqueda;

        $resultado = $this->consulta($sql);
        return $resultado;
    }
    
    public function buscarPluEnBalanza($plu, $idBalanza){
        //@OBjetivo: Buscar un plu en la balanza, para que en una balanza no tenga dos plu iguales
        $sql='select * from modulo_balanza_plus where idBalanza='.$idBalanza.' and plu="'.$plu.'"';
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    public function addPlu($plu, $idBalanza, $tecla, $idArticulo){
        //#Objetivo: añadir plu 
        $sql='INSERT INTO `modulo_balanza_plus`(`idBalanza`, `plu`, `tecla`, `idArticulo`)
         VALUES ('.$idBalanza.', "'.$plu.'", "'.$tecla.'", '.$idArticulo.')';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }
    public function eliminarplu($idBalanza, $plu){
        //@Objetivo: eliminar plu
        $sql='DELETE FROM `modulo_balanza_plus` WHERE idBalanza='.$idBalanza.' and plu="'.$plu.'"';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }
    public function modificarBalanza($id, $nombre, $modelo, $tecla){
        //@Objetivo: modificar los datos de una balanza
        $sql='UPDATE `modulo_balanza` SET `nombreBalanza`="'.$nombre.'",`modelo`="'.$modelo.'",`conTecla`="'.$tecla.'"
         WHERE `idBalanza`='.$id;
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }
}


?>
