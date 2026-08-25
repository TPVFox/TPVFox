<?php

include_once $URLCom . '/clases/ClaseIOXML.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionIntercambioXML.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockConsulta.php';

// @ Objetivo
// Admitir el resultado que llega del otro ejercicio: validar su origen e integridad,
// y emparejar cada fila con el catálogo por la misma identidad con la que se
// reconoce el traspaso. Si el fichero entero no es válido lo rechaza entero; si solo
// una fila falla, esa fila queda marcada y el resto sigue.
//
// No lee la base: el catálogo con el que empareja se lo pide a la clase de consulta.
class ClaseComprobacionAdmision
{
    private $rutaXSD = '/modulos/mod_reorganizacion/comprobacion_intercambio_v1.xsd';
    private $consulta = null;

    public function admitir($rutaFichero, $contextoOperacion)
    {
        // @ Objetivo
        // Validar el fichero de intercambio contra su esquema, su resumen de
        // contenido y su correspondencia de ejercicio y tienda, en ese orden: el
        // primer fallo rechaza el fichero entero y detiene la ejecución antes de
        // calcular nada. Superadas las tres, empareja cada fila con el catálogo de
        // este ejercicio.
        // @ Parametros
        //      $rutaFichero -> string, ruta del servidor al fichero de intercambio.
        //      $contextoOperacion -> array, la salida de ClaseComprobacionContexto::abrir()
        //          en este ejercicio (el anterior).
        // @ Devolvemos
        //      array ['ok' => false, 'motivo' => ..] en el primer fallo.
        //      array ['ok' => true, 'filas' => [...], 'contexto' => [...]] si se admite.
        global $RutaServidor, $HostNombre;

        try {
            $io = new ClaseIOXML($rutaFichero, $RutaServidor . $HostNombre . $this->rutaXSD);
            $xml = $io->cargar();
        } catch (Exception $error) {
            return $this->rechazo('El fichero no valida contra el esquema: ' . $error->getMessage());
        }

        $datos = ClaseComprobacionIntercambioXML::simpleXMLToArray($xml);

        if ($datos['resumenDeclarado'] !== $datos['resumenRecalculado']) {
            return $this->rechazo('El resumen de contenido no coincide con lo declarado');
        }

        $ejercicioEsperado = (string) ((int) $contextoOperacion['ano'] + 1);
        if ($datos['contexto']['ano'] !== $ejercicioEsperado
            || (int) $datos['contexto']['idTienda'] !== (int) $contextoOperacion['idTienda']
        ) {
            return $this->rechazo('El fichero no corresponde a este ejercicio y tienda');
        }

        $catalogo = $this->consulta()->catalogoDe($this->idsDelFichero($datos['filas']));
        $filas = $this->emparejar($datos['filas'], $catalogo);

        return array('ok' => true, 'filas' => $filas, 'contexto' => $datos['contexto']);
    }

    public function emparejar($filas, $catalogo)
    {
        // @ Objetivo
        // Marcar cada fila como comparable si su idArticulo existe en el catálogo de
        // este ejercicio. Es la única clave de emparejamiento: ningún otro campo del
        // catálogo participa, así que un producto cuyo nombre o código de barras
        // cambió entre ejercicios se empareja igual. Ninguna fila desaparece.
        // @ Parametros
        //      $filas -> array de filas del fichero admitido.
        //      $catalogo -> array de filas del catálogo de este ejercicio, cada una
        //          con al menos 'idArticulo'.
        // @ Devolvemos
        //      array de filas con 'comparable' => bool añadido.
        $idsPresentes = array();
        foreach ($catalogo as $producto) {
            $idsPresentes[(int) $producto['idArticulo']] = true;
        }

        $resultado = array();
        foreach ($filas as $fila) {
            $fila['comparable'] = isset($idsPresentes[$fila['idArticulo']]);
            $resultado[] = $fila;
        }
        return $resultado;
    }

    private function idsDelFichero($filas)
    {
        $ids = array();
        foreach ($filas as $fila) {
            $ids[] = $fila['idArticulo'];
        }
        return $ids;
    }

    private function consulta()
    {
        // @ Objetivo
        // La clase de consulta del módulo, una sola vez por instancia.
        // @ Devolvemos
        //      ClaseComprobacionStockConsulta.
        if ($this->consulta === null) {
            $this->consulta = new ClaseComprobacionStockConsulta();
        }
        return $this->consulta;
    }

    private function rechazo($motivo)
    {
        return array('ok' => false, 'motivo' => $motivo);
    }
}
