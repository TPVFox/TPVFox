<?php 

//~ include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';
include_once $URLCom.'/clases/ClaseTFModelo.php';

class ClaseTienda extends TFModelo  {
   

    public function tiendasWeb(){
        $sql='SELECT * FROM tiendas where tipoTienda="web"';
        $respuesta = parent::Consulta($sql);
        return $respuesta;
        
    }
    public function tiendaPrincipal(){
        $sql='SELECT * FROM tiendas where tipoTienda="principal"';
        $respuesta = parent::Consulta($sql);
        return $respuesta;
    }

    public function obtenerUnaTienda($id){
        $respuesta =array();
        $sql = 'SELECT * FROM tiendas WHERE idTienda='.$id;
        $respuesta = parent::Consulta($sql);
        
        return $respuesta ;
    }

    public function obtenerTiendas() {
        // Function para obtener tiendas y listarlos
        $respuesta = array();
        $sql = "Select * from tiendas";
        $res = parent::Consulta($sql);
        $respuesta['datos'] = $res['datos'];
        $respuesta['NItems'] = count($res['datos']);
        return $res;
    }

    public function addTienda($datos){
        $valores =   '"'.$datos['tipoTienda'].'","'
                    .$datos['razonsocial'].'","'
                    .$datos['nif'].'","'
                    .$datos['telefono'].'","'
                    .$datos['estado'].'","'
                    .$datos['NombreComercial'].'","'
                    .$datos['direccion'].'","'
                    .$datos['emailTienda'].'","'
                    .$datos['nombreEmail'].'","'
                    .$datos['ano'].'","'
                    .$datos['dominio'].'","'
                    .$datos['key_api'].'"';
    
        $sql='INSERT INTO `tiendas` (tipoTienda, razonsocial, nif, telefono,estado,NombreComercial,direccion,emailTienda,nombreEmail,ano,dominio,key_api) 
        VALUES ('.$valores.')';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        } else {
            return ModeloP::$db->insert_id;
        }
        
    }

    
}

?>
