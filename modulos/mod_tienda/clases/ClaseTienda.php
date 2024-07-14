<?php
//~ include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';
include_once $URLCom . '/clases/ClaseTFModelo.php';

class ClaseTienda extends TFModelo
{

    protected $tabla = 'tiendas';

    public function tiendasWeb()
    {
        $sql = 'SELECT * FROM tiendas where tipoTienda="web"';
        $respuesta = parent::Consulta($sql);
        return $respuesta;

    }
    public function tiendaPrincipal()
    {
        $sql = 'SELECT * FROM tiendas where tipoTienda="principal"';
        $respuesta = parent::Consulta($sql);
        return $respuesta;
    }

    public function obtenerUnaTienda($id)
    {
        $respuesta = array();
        $sql = 'SELECT * FROM tiendas WHERE idTienda=' . $id;
        $respuesta = parent::Consulta($sql);

        return $respuesta;
    }

    public function obtenerTiendas()
    {
        // Function para obtener tiendas y listarlos
        $respuesta = array();
        $sql = "Select * from tiendas";
        $res = parent::Consulta($sql);
        $respuesta['datos'] = $res['datos'];
        $respuesta['NItems'] = count($res['datos']);
        return $res;
    }

    public function addTienda($datos)
    {    
        
        ///// Como se hacia en la prehistoria. 
        ///
        // $valores = '"' . $datos['tipoTienda'] . '","'
        //     . $datos['razonsocial'] . '","'
        //     . $datos['nif'] . '","'
        //     . $datos['telefono'] . '","'
        //     . $datos['estado'] . '","'
        //     . $datos['NombreComercial'] . '","'
        //     . $datos['direccion'] . '","'
        //     . $datos['emailTienda'] . '","'
        //     . $datos['nombreEmail'] . '","'
        //     . $datos['ano'] . '","'
        //     . $datos['dominio'] . '","'
        //     . $datos['key_api'] . '"';

        // $sql = 'INSERT INTO `tiendas` (tipoTienda, razonsocial, nif, telefono,estado,NombreComercial,direccion,emailTienda,nombreEmail,ano,dominio,key_api)
        // VALUES (' . $valores . ')';
/////////

/// Permite hacer INSERT y UPDATE con el mismo array de datos y si un indice no existe 
/// no da error

        $sql = 'INSERT INTO `tiendas` ';
        $sql .= 'SET tipoTienda = ' . entreComillas($datos['tipoTienda']);
        $sql .= ', nif = ' . entreComillas($datos['nif']);
        $sql .= ', razonsocial = ' . entreComillas($datos['razonsocial']);
        $sql .= ', NombreComercial = ' . entreComillas($datos['NombreComercial']);
        $sql .= ', ano = ' . entreComillas($datos['ano']);
        $sql .= ', direccion = ' . entreComillas($datos['direccion']);
        $sql .= ', telefono = ' . entreComillas($datos['telefono']);
        // $sql .= ', estado = ' . entreComillas($datos['estado']);
        // $sql .= ', emailTienda = '.entreComillas($datos['emailTienda']);
        // $sql .= ', nombreEmail = '.entreComillas($datos['nombreEmail']);
        // $sql .= ', dominio = '.entreComillas($datos['dominio']);
        // $sql .= ', key_api = '.entreComillas($datos['key_api']);
        
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        } else {
            return ModeloP::$db->insert_id;
        }

    }

    public function modificarTienda($datos)
    {
        //@Objetivo:
        //Modificar los datos de un cliente determinado
        //@Parametros:
        //Datos-> array con todos los datos del cliente 
        //id-> id del cliente que se va a modificar (que no está en parámetros, donde está????)
        $respuesta = array();
        $consulta = $this->update($datos, 'idtienda=' . $datos['idtienda']);
        if (isset($consulta['error'])) {
            $respuesta['error'] = $consulta;
        } else {
            // Fue bien , devolvemos la cantidad de filas modificadas.
            $respuesta = ModeloP::$db->affected_rows;
            // OJ0:
            // Aunque no de error, si hacer un update y tiene lo mismos datos que tenía, la respuesta es 0
        }
        return $respuesta;
    }

    public function obtenerArrayDatosServidor($servidor_email)
    {
        // Objetivo:
        // Es devolver array con los datos de servidor que viene en JSON de ObtenerUnaTienda:wq
        $s = json_decode($servidor_email, true);
        $respuesta = [];
        foreach ($s as $v) {
            $respuesta = $respuesta + $v;
        }
        return $respuesta;
    }
}
