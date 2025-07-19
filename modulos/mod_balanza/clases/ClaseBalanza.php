<?php 
include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';

class ClaseBalanza  extends Modelo  {

    public $idTienda;

    public function __construct($conexion='')
    {
        $this->ObtenerTiendaPrincipal();
    }

    public function ObtenerTiendaPrincipal(){
        $Sql = "SELECT idTienda FROM `tiendas` WHERE `tipoTienda`='Principal'";
        $respuesta = $this->consulta($Sql);
        if (count($respuesta['datos']) === 1){
            $this->idTienda = $respuesta['datos'][0]['idTienda'];
        }
    }

    public function addBalanza($datos){
        // Ahora se esperan los nuevos campos en $datos
        $sql = 'INSERT INTO `modulo_balanza`
            (`nombreBalanza`, `modelo`, `conSeccion`, `Grupo`, `Dirección`, `IP`, `soloPLUS`)
            VALUES (
                "'.$datos['nombreBalanza'].'",
                "'.$datos['modeloBalanza'].'",
                "'.$datos['secciones'].'",
                '.intval($datos['Grupo']).',
                '.intval($datos['Direccion']).',
                "'.$datos['IP'].'",
                '.(isset($datos['soloPLUS']) ? intval($datos['soloPLUS']) : 1).'
            )';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }

    public function todasBalanzas(){
        $sql='SELECT * from modulo_balanza';
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function datosBalanza($idBalanza){
        $sql='SELECT * from modulo_balanza where idBalanza='.$idBalanza;
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function pluDeBalanza($idBalanza, $filtro){
        $sql ='Select a.*, t.crefTienda,b.articulo_name ,b.tipo, p.pvpCiva,pro.nombrecomercial from modulo_balanza_plus as a 
         inner join articulos as b on a.idArticulo=b.idArticulo  INNER JOIN articulosTiendas as t 
         on t.idArticulo=b.idArticulo and t.idTienda ='.$this->idTienda.' inner join articulosPrecios as p on p.idArticulo=a.idArticulo  
         left join proveedores as pro on pro.idProveedor=b.idProveedor
         where a.idBalanza='.$idBalanza.'
         order by '.$filtro.' asc';
        
        $plus = $this->consulta($sql);

        // Controlar si no hay plus para la balanza
        if (empty($plus['datos'])) {
            $plus['datos'] = [];
            $plus['mensaje'] = 'No hay PLUs para esta balanza.';
            return $plus;
        }

        $idsProductos = array_column($plus['datos'], 'idArticulo');
        $idsProductosUnicos = array_unique($idsProductos);
        $duplicado = array();
        if (count($idsProductos) !== count($idsProductosUnicos)){
            $duplicado = array_diff_assoc($idsProductos,$idsProductosUnicos);
        }
        if (count($duplicado)>0 ){
            foreach ($duplicado as $key=>$valor){
                $plus['datos'][$key]['duplicado'] = 'KO';
            }
        }
        $resultado = $plus;
        return $resultado;
    }

    public function buscarArticuloCampo($busqueda){
        $sql='SELECT a.idArticulo, a.articulo_name, b.crefTienda, c.codBarras,p.pvpCiva
                from articulos as a LEFT JOIN articulosTiendas as b on a.idArticulo=b.idArticulo LEFT JOIN
                articulosCodigoBarras as c on a.idArticulo=c.idArticulo LEFT join tiendas as d on 
                b.idTienda=d.idTienda  left join articulosPrecios as p on p.idArticulo=a.idArticulo 
                where d.tipoTienda="principal" and '.$busqueda;
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function buscarPluEnBalanza($plu, $idBalanza){
        $sql='select * from modulo_balanza_plus where idBalanza='.$idBalanza.' and plu="'.$plu.'"';
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function addPlu($plu, $idBalanza, $seccion, $idArticulo){
        $seccion = intval($seccion);
        $sql='INSERT INTO `modulo_balanza_plus`(`idBalanza`, `plu`, `seccion`, `idArticulo`)
         VALUES ('.$idBalanza.', "'.$plu.'", "'.$seccion.'", '.$idArticulo.')';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }

    public function updatePlu($idArticulo, $idBalanza, $plu, $seccion) {
        $seccion = intval($seccion);
        $sql = 'UPDATE `modulo_balanza_plus` 
                SET `plu` = "'.$plu.'", `seccion` = "'.$seccion.'" 
                WHERE `idArticulo` = '.$idArticulo.' AND `idBalanza` = '.$idBalanza;
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
        return ['success' => true];
    }

    public function obtenerPluActual($idBalanza, $idArticulo) {
        $sql = 'SELECT plu, seccion FROM modulo_balanza_plus WHERE idBalanza = '.intval($idBalanza).' AND idArticulo = '.intval($idArticulo);
        $resultado = $this->consulta($sql);
        if (!empty($resultado['datos'][0])) {
            return $resultado['datos'][0];
        }
        return null;
    }

    public function eliminarplu($idBalanza, $plu){
        $sql='DELETE FROM `modulo_balanza_plus` WHERE idBalanza='.$idBalanza.' and plu="'.$plu.'"';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }

    public function modificarBalanza($idBalanza, $datos){
        // Obtener datos actuales de la balanza
        $balanzaActual = $this->datosBalanza(intval($idBalanza));
        if (empty($balanzaActual['datos'][0])) {
            return ['error' => 'Balanza no encontrada'];
        }
        $actual = $balanzaActual['datos'][0];

        // Campos que pueden ser modificados
        $campos = [
            'nombreBalanza' => 'nombreBalanza',
            'modeloBalanza' => 'modelo',
            'secciones'     => 'conSeccion',
            'Grupo'         => 'Grupo',
            'Direccion'     => 'Dirección',
            'IP'            => 'IP',
            'soloPLUS'      => 'soloPLUS'
        ];

        // Construir SET solo con los campos que han cambiado
        $set = [];
        foreach ($campos as $key => $columna) {
            $nuevoValor = isset($datos[$key]) ? $datos[$key] : null;
            $valorActual = isset($actual[$columna]) ? $actual[$columna] : null;

            // Normalizar valores numéricos
            if (in_array($key, ['Grupo', 'Direccion', 'soloPLUS'])) {
                $nuevoValor = intval($nuevoValor);
                $valorActual = intval($valorActual);
            }

            // Si el valor ha cambiado, añadirlo al SET
            if ($nuevoValor !== null && $nuevoValor !== $valorActual) {
                if (in_array($key, ['Grupo', 'Direccion', 'soloPLUS'])) {
                    $set[] = "`$columna` = $nuevoValor";
                } else {
                    $set[] = "`$columna` = \"".$this->escapeString($nuevoValor)."\"";
                }
            }
        }

        if (empty($set)) {
            return ['mensaje' => 'No hay cambios para actualizar'];
        }

        $sql = 'UPDATE `modulo_balanza` SET '.implode(', ', $set).' WHERE `idBalanza` = '.intval($idBalanza);
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }

    // Funcion para saber si una balanza usa secciones
    public function usaSecciones($idBalanza){
        $sql = 'SELECT conSeccion FROM `modulo_balanza` WHERE idBalanza = '.intval($idBalanza);
        $resultado = $this->consulta($sql);
        if (isset($resultado['datos'][0]['conSeccion'])) {
            return strtolower($resultado['datos'][0]['conSeccion']) === 'si';
        }
        return false; // Si no se encuentra la balanza, asumimos que no usa secciones
    }

    // Función auxiliar para escapar cadenas (puedes adaptarla según tu framework/conexión)
    private function escapeString($str) {
        return addslashes($str);
    }
}
?>
