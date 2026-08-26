<?php

include_once $URLCom . '/clases/ClaseIOXML.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockIntercambioXML.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockConsulta.php';

// @ Objetivo
// Admitir el resultado que llega del otro ejercicio: validar su origen e integridad,
// y emparejar cada fila con el catálogo por la misma identidad con la que se
// reconoce el traspaso. Si el fichero entero no es válido lo rechaza entero; si solo
// una fila falla, esa fila queda marcada y el resto sigue.
//
// No lee la base: el catálogo con el que empareja se lo pide a la clase de consulta.
class ClaseComprobacionStockAdmision
{
    private $rutaXSD = '/modulos/mod_reorganizacion/comprobacion_stock_intercambio_v1.xsd';
    private $consulta = null;

    public function subidaAdmisible($subida, $limiteDelMotor)
    {
        // @ Objetivo
        // Decidir si llegó un fichero con el que se pueda seguir, y con qué motivo si
        // no llegó. Es la primera rama del flujo y ocurre antes de establecer el
        // contexto: comprobar que hay algo con lo que trabajar no necesita ni la
        // sesión, ni el esquema, ni los parámetros, ni abrir el bloque de lectura.
        //
        // Que llegue o no llegue tiene cuatro causas distintas y quien las junta en
        // una sola deja al operador sin saber qué hacer: no es lo mismo no haber
        // elegido fichero que haber elegido uno que el servidor no acepta por tamaño.
        // La segunda se acompaña del límite, porque sin la cifra el aviso no dice
        // nada que se pueda usar.
        //
        // Queda fuera de su alcance el caso en que la petición entera excede lo que
        // el servidor admite: entonces no llega ningún campo, esta acción no se
        // ejecuta y no hay nada aquí que pueda notarlo.
        // @ Parametros
        //      $subida -> array del fichero recibido, o null si no llegó el campo.
        //      $limiteDelMotor -> string, el tamaño máximo por fichero que admite el
        //          servidor, tal como lo declara, para nombrarlo en el motivo.
        // @ Devolvemos
        //      array ['ok' => true] o ['ok' => false, 'motivo' => ..].
        if (!is_array($subida) || !isset($subida['error'])) {
            return $this->rechazo('No llegó ningún fichero: elija el fichero de intercambio del ejercicio vigente');
        }

        switch ($subida['error']) {
            case UPLOAD_ERR_OK:
                return array('ok' => true);
            case UPLOAD_ERR_NO_FILE:
                return $this->rechazo('No se eligió ningún fichero');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return $this->rechazo('El fichero excede el tamaño que admite este servidor, que es de '
                    . $limiteDelMotor . ' por fichero');
            case UPLOAD_ERR_PARTIAL:
                return $this->rechazo('El fichero llegó incompleto: vuelva a intentarlo');
        }

        return $this->rechazo('El servidor no pudo recibir el fichero');
    }

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
        //      $contextoOperacion -> array, la salida de ClaseComprobacionStockContexto::abrir()
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

        $datos = ClaseComprobacionStockIntercambioXML::simpleXMLToArray($xml);

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
