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
        //
        // Rechazar y no poder terminar son dos desenlaces distintos, y aquí es donde
        // se distinguen: rechazar dice que el material no vale y que hay que traer
        // otro; no poder terminar dice que el material puede estar bien y que quien
        // falló fue el sistema. Quién de los dos ocurrió no se deduce del tipo de
        // error que devuelve el componente que lee el fichero —señala con el mismo
        // aviso que falta el esquema del servidor y que el fichero está corrupto—,
        // sino de lo que se haya comprobado antes de llamarlo.
        // @ Parametros
        //      $rutaFichero -> string, ruta del servidor al fichero de intercambio.
        //      $contextoOperacion -> array, la salida de ClaseComprobacionStockContexto::abrir()
        //          en este ejercicio (el anterior).
        // @ Devolvemos
        //      array ['ok' => false, 'motivo' => ..] en el primer fallo.
        //      array ['ok' => true, 'filas' => [...], 'contexto' => [...]] si se admite.
        // @ Lanza
        //      RuntimeException si el esquema no está donde tiene que estar: eso no es
        //      culpa de quien sube el fichero y no puede anunciarse como un rechazo.
        global $RutaServidor, $HostNombre;

        $rutaEsquema = $RutaServidor . $HostNombre . $this->rutaXSD;

        // Lo primero es el esquema, y se comprueba aquí porque su ausencia es lo único
        // de este tramo que no depende del fichero recibido. Sin esta comprobación,
        // un servidor mal instalado le diría a quien admite que su fichero no vale.
        if (!is_readable($rutaEsquema)) {
            throw new RuntimeException('El esquema del formato de intercambio no está disponible en el servidor');
        }

        // Y lo segundo, que el fichero sea siquiera un documento: es el rechazo más
        // corriente de todos —un fichero truncado, uno de otro tipo, uno que se copió
        // a medias— y se comprueba aquí porque, dejándoselo al componente, llega como
        // un error del lenguaje y se anunciaría como un fallo del sistema.
        // El buffer de avisos se vacía antes de leer: es del proceso y no de esta
        // lectura, y arrastrar lo que dejó otra haría que el registro del rechazo
        // nombrara un motivo que no es el suyo.
        $avisosInternos = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $documento = simplexml_load_file($rutaFichero);
        $avisos = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($avisosInternos);

        if ($documento === false) {
            $this->registrarDetalleDelRechazo($rutaFichero, $avisos);
            return $this->rechazo('El fichero no es un documento de intercambio legible: compruebe que es el que descargó del ejercicio vigente y vuelva a intentarlo');
        }

        // Comprobadas las dos, lo único que el componente puede reprochar ya es el
        // contenido del fichero, así que cualquier cosa que lance es un rechazo. Lo
        // que dijo no sale a pantalla —nombra la ruta del esquema en el servidor y no
        // es accionable para quien admite—, pero queda registrado: sin él, un rechazo
        // por esquema es la única salida del módulo que no deja rastro de su motivo.
        try {
            $io = new ClaseIOXML($rutaFichero, $rutaEsquema);
            $xml = $io->cargar();
        } catch (Throwable $error) {
            $this->registrarDetalleDelRechazo($rutaFichero, $error);
            return $this->rechazo('El fichero no cumple el formato de intercambio de este sistema: use el que emite el ejercicio vigente, sin editarlo');
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

    private function registrarDetalleDelRechazo($rutaFichero, $detalle)
    {
        // @ Objetivo
        // Dejar constancia de por qué se rechazó un fichero. Lo que se muestra es el
        // motivo que este sistema establece; el detalle de quien lo leyó nombra la
        // ruta del esquema en el servidor y el elemento que falló, que no le sirven a
        // quien admite pero sí a quien mantiene el sistema. Sin esto, un rechazo por
        // formato sería la única salida del módulo sin rastro de su causa.
        //
        // Escribe donde el servidor ya escribe sus errores: el módulo no elige destino
        // ni abre un registro propio.
        // @ Parametros
        //      $rutaFichero -> string, el fichero rechazado.
        //      $detalle -> Throwable, o array de errores de la lectura del documento.
        if (is_array($detalle)) {
            $texto = '';
            foreach ($detalle as $aviso) {
                $texto .= trim($aviso->message) . '; ';
            }
        } else {
            $texto = get_class($detalle) . ' — ' . $detalle->getMessage();
        }

        error_log('mod_reorganizacion/admitirComprobacionStock: fichero rechazado ('
            . basename($rutaFichero) . '): ' . $texto);
    }
}
