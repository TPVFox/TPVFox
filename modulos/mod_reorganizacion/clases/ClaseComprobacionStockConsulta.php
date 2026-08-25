<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/modulos/mod_informes/clases/PosstockQueryRepository.php';
include_once $URLCom . '/modulos/mod_informes/clases/PosstockC1Detector.php';

// @ Objetivo
// Reunir todas las lecturas de la comprobación de stock. Es el único punto del
// módulo que construye SQL, y el único que instancia el repositorio y el detector
// del informe de existencias negativas: el resto del módulo recibe de aquí
// estructuras propias y no nombra ningún tipo de POSStock.
//
// Devuelve filas normalizadas —tipos convertidos y nombres de campo estables— y
// nada más. Ninguna decisión sobre lo leído se toma aquí: el signo de un
// movimiento, la clase de movimiento que es, quién queda fuera de un conjunto o
// qué valor se supone cuando no hay fila son criterio, y viven en las clases
// ejecutoras.
//
// La regla se comprueba leyendo: las únicas guardas de esta clase son la de la
// lista vacía —que en SQL no es una consulta sin resultados, sino un error de
// sintaxis— y la de la lectura que no devuelve conjunto. Cualquier otra condición
// que decida algo sobre lo leído está fuera de sitio.
class ClaseComprobacionStockConsulta extends TFModelo
{
    private $repositorio = null;
    private $detector = null;

    // El ejercicio vigente: catálogo, trayectoria e incidencias

    public function catalogoFisico()
    {
        // @ Objetivo
        // Los identificadores del catálogo completo de productos físicos, sin filtro
        // de familia: el conjunto sobre el que se compone la trayectoria.
        // @ Devolvemos
        //      array de int, idArticulo.
        $ids = array();
        foreach ($this->repositorio()->queryArticulosFisicos('') as $fila) {
            $ids[] = (int) $fila['idArticulo'];
        }
        return $ids;
    }

    public function stockBase($fechaInicio, $fechaFin, $ids)
    {
        // @ Objetivo
        // El saldo de partida de cada producto en el borde del ejercicio, con las
        // fechas de su última compra y su última venta.
        // @ Parametros
        //      $fechaInicio, $fechaFin -> string 'AAAA-MM-DD', el borde del ejercicio.
        //      $ids -> array de int, los productos que se leen.
        // @ Devolvemos
        //      array [idArticulo => ['saldo_acumulado' => float, 'ultima_compra' => ..,
        //      'ultima_venta' => ..]], la forma con la que el detector lo recibe.
        $idsCsv = $this->idsCsv($ids);
        if ($idsCsv === '') {
            return array();
        }

        $resultado = array();
        foreach ($this->repositorio()->queryStockBase($fechaInicio, $fechaFin, $idsCsv) as $fila) {
            $resultado[(int) $fila['idArticulo']] = array(
                'saldo_acumulado' => (float) $fila['saldo_acumulado'],
                'ultima_compra' => $fila['ultima_compra'],
                'ultima_venta' => $fila['ultima_venta'],
            );
        }
        return $resultado;
    }

    public function movimientosDelPeriodo($fechaInicio, $fechaFin)
    {
        // @ Objetivo
        // Los movimientos de todo el catálogo entre las dos fechas, sin filtro de
        // familia ni de artículo. Se entregan tal como los da el repositorio: la
        // trayectoria los recorre con esa misma forma.
        // @ Parametros
        //      $fechaInicio, $fechaFin -> string 'AAAA-MM-DD'.
        // @ Devolvemos
        //      array de filas ['tipo_movimiento', 'idArticulo', 'nunidades', 'fecha'].
        return $this->repositorio()->queryMovimientosPeriodo($fechaInicio, $fechaFin, '', '');
    }

    public function incidenciasC1($fiMovimientos, $ffMovimientos, $fiStock, $ffStock, $stockBase)
    {
        // @ Objetivo
        // Las incidencias que el detector de existencias negativas emite sobre el
        // periodo, con el saldo de partida ya calculado para que no lo rehaga. Sin
        // filtros de familia, de artículo ni de tienda: el conjunto lo decide quien
        // llama, no el detector.
        // @ Parametros
        //      $fiMovimientos, $ffMovimientos -> string 'AAAA-MM-DD', el periodo.
        //      $fiStock, $ffStock -> string 'AAAA-MM-DD', el borde del ejercicio.
        //      $stockBase -> array, la salida de stockBase().
        // @ Devolvemos
        //      array de incidencias del detector, sin tocar.
        return $this->detector()->detectar(
            $fiMovimientos,
            $ffMovimientos,
            $fiStock,
            $ffStock,
            array(),
            array(),
            array(),
            $stockBase
        );
    }

    public function deFamiliasExcluidas($ids, $familiasExcluidas)
    {
        // @ Objetivo
        // De los productos indicados, cuáles pertenecen a alguna de las familias
        // excluidas del cierre. El repositorio expande cada familia a sus subfamilias
        // antes de buscar.
        // @ Parametros
        //      $ids -> array de int, los productos que se leen.
        //      $familiasExcluidas -> array de int, las familias de la configuración.
        // @ Devolvemos
        //      array de int, idArticulo de los que pertenecen a alguna.
        $idsCsv = $this->idsCsv($ids);
        $familiasCsv = $this->repositorio()->expandirFamilias($familiasExcluidas);
        if ($idsCsv === '' || $familiasCsv === '') {
            return array();
        }

        return $this->idsDe("
            SELECT DISTINCT idArticulo
            FROM articulosFamilias
            WHERE idArticulo IN ($idsCsv)
              AND idFamilia IN ($familiasCsv)
        ");
    }

    public function conStockPositivoEnElCierre($ids)
    {
        // @ Objetivo
        // De los productos indicados, cuáles cumplen el criterio de selección del
        // propio cierre: stockOn > 0 en la tienda principal.
        // @ Parametros
        //      $ids -> array de int.
        // @ Devolvemos
        //      array de int, idArticulo de los que el cierre sí habría tomado.
        $idsCsv = $this->idsCsv($ids);
        if ($idsCsv === '') {
            return array();
        }

        return $this->idsDe("
            SELECT idArticulo
            FROM articulosStocks
            WHERE idArticulo IN ($idsCsv)
              AND idTienda = 1
              AND stockOn > 0
        ");
    }

    public function conRegularizacionEntre($ids, $desde, $hasta)
    {
        // @ Objetivo
        // De los productos indicados, cuáles tienen una regularización activa fechada
        // dentro del periodo.
        // @ Parametros
        //      $ids -> array de int.
        //      $desde, $hasta -> string 'AAAA-MM-DD'; el periodo se toma por días
        //      completos, de la primera hora del primero a la última del último.
        // @ Devolvemos
        //      array de int, idArticulo de los que tienen alguna.
        $idsCsv = $this->idsCsv($ids);
        if ($idsCsv === '') {
            return array();
        }

        return $this->idsDe("
            SELECT DISTINCT idArticulo
            FROM stocksRegularizacion
            WHERE idArticulo IN ($idsCsv)
              AND estado = 1
              AND fechaRegularizacion BETWEEN '$desde 00:00:00' AND '$hasta 23:59:59'
        ");
    }

    // El ejercicio anterior: catálogo y movimientos del producto

    public function catalogoDe($ids)
    {
        // @ Objetivo
        // De los identificadores indicados, cuáles existen en el catálogo de este
        // ejercicio.
        // @ Parametros
        //      $ids -> array de int, los que trae el fichero de intercambio.
        // @ Devolvemos
        //      array de filas ['idArticulo' => int].
        $idsCsv = $this->idsCsv($ids);
        if ($idsCsv === '') {
            return array();
        }

        $resultado = array();
        foreach ($this->idsDe("SELECT idArticulo FROM articulos WHERE idArticulo IN ($idsCsv)") as $id) {
            $resultado[] = array('idArticulo' => $id);
        }
        return $resultado;
    }

    public function lineasDeAlbaranDeProveedor($idTienda, $idArticulo, $desde, $hasta, $idProveedorExcluido)
    {
        // @ Objetivo
        // Las líneas activas de albarán de proveedor del producto en el periodo,
        // dejando fuera las del proveedor indicado: sus albaranes son los dos
        // traspasos entre ejercicios y no son movimiento del negocio.
        // @ Parametros
        //      $idTienda, $idArticulo -> int.
        //      $desde, $hasta -> string 'AAAA-MM-DD'.
        //      $idProveedorExcluido -> int, el proveedor de traspaso.
        // @ Devolvemos
        //      array de filas ['fecha' => string, 'nunidades' => float].
        $idTienda = (int) $idTienda;
        $idArticulo = (int) $idArticulo;
        $idProveedorExcluido = (int) $idProveedorExcluido;

        return $this->lineasDe("
            SELECT DATE(c.Fecha) AS fecha, l.nunidades AS nunidades
            FROM albprolinea l
            INNER JOIN albprot c ON c.id = l.idalbpro
            WHERE c.idTienda = $idTienda
              AND l.idArticulo = $idArticulo
              AND l.estadoLinea = 'Activo'
              AND c.estado IN ('Guardado', 'Facturado', 'Exportado', 'Importado')
              AND DATE(c.Fecha) BETWEEN '$desde' AND '$hasta'
              AND c.idProveedor <> $idProveedorExcluido
        ");
    }

    public function lineasDeTicket($idTienda, $idArticulo, $desde, $hasta)
    {
        // @ Objetivo
        // Las líneas activas de ticket cerrado del producto en el periodo.
        // @ Parametros
        //      $idTienda, $idArticulo -> int.
        //      $desde, $hasta -> string 'AAAA-MM-DD'.
        // @ Devolvemos
        //      array de filas ['fecha' => string, 'nunidades' => float].
        $idTienda = (int) $idTienda;
        $idArticulo = (int) $idArticulo;

        return $this->lineasDe("
            SELECT DATE(c.Fecha) AS fecha, l.nunidades AS nunidades
            FROM ticketslinea l
            INNER JOIN ticketst c ON c.id = l.idticketst
            WHERE c.idTienda = $idTienda
              AND l.idArticulo = $idArticulo
              AND l.estadoLinea = 'Activo'
              AND c.estado = 'Cerrado'
              AND DATE(c.Fecha) BETWEEN '$desde' AND '$hasta'
        ");
    }

    public function lineasDeAlbaranDeCliente($idTienda, $idArticulo, $desde, $hasta)
    {
        // @ Objetivo
        // Las líneas activas de albarán de cliente del producto en el periodo.
        // @ Parametros
        //      $idTienda, $idArticulo -> int.
        //      $desde, $hasta -> string 'AAAA-MM-DD'.
        // @ Devolvemos
        //      array de filas ['fecha' => string, 'nunidades' => float].
        $idTienda = (int) $idTienda;
        $idArticulo = (int) $idArticulo;

        return $this->lineasDe("
            SELECT DATE(c.Fecha) AS fecha, l.nunidades AS nunidades
            FROM albclilinea l
            INNER JOIN albclit c ON c.id = l.idalbcli
            WHERE c.idTienda = $idTienda
              AND l.idArticulo = $idArticulo
              AND l.estadoLinea = 'Activo'
              AND c.estado IN ('Guardado', 'Procesado')
              AND DATE(c.Fecha) BETWEEN '$desde' AND '$hasta'
        ");
    }

    public function tipoDeArticulo($idArticulo)
    {
        // @ Objetivo
        // Cómo se mide el producto en el catálogo. Si no hay fila no se supone
        // ninguno: quien llama decide qué hacer con la ausencia.
        // @ Parametros
        //      $idArticulo -> int.
        // @ Devolvemos
        //      string con el tipo, o null si el producto no está en el catálogo.
        $idArticulo = (int) $idArticulo;
        $filas = $this->filasDe("SELECT tipo FROM articulos WHERE idArticulo = $idArticulo");
        if (!isset($filas[0])) {
            return null;
        }
        return (string) $filas[0]['tipo'];
    }

    // El esquema y el bloque de lectura

    public function existeObjetoDeEsquema($objeto)
    {
        // @ Objetivo
        // Si la vista o la tabla indicada está presente en la base.
        // @ Parametros
        //      $objeto -> string, el nombre del objeto.
        // @ Devolvemos
        //      bool.
        $resultado = $this->conexionBDTPV()->query("SHOW TABLES LIKE '" . $objeto . "'");
        return $resultado !== false && $resultado->num_rows > 0;
    }

    public function abrirBloqueDeLectura()
    {
        // @ Objetivo
        // Abrir el bloque de lectura en una transacción de solo lectura. Si el motor
        // no la admite, la apertura misma es la comprobación de que no la sostiene.
        // @ Devolvemos
        //      bool, true si se abrió.
        try {
            return $this->conexionBDTPV()->query('START TRANSACTION READ ONLY') !== false;
        } catch (mysqli_sql_exception $motorNoLoAdmite) {
            return false;
        }
    }

    public function cerrarBloqueDeLectura()
    {
        // @ Objetivo
        // Cerrar el bloque de lectura abierto por abrirBloqueDeLectura().
        $this->conexionBDTPV()->query('COMMIT');
    }

    // Apoyo interno

    private function repositorio()
    {
        // @ Objetivo
        // El repositorio de POSStock sobre la conexión del módulo, una sola vez por
        // instancia.
        // @ Devolvemos
        //      PosstockQueryRepository.
        if ($this->repositorio === null) {
            $this->repositorio = new PosstockQueryRepository($this->conexionBDTPV());
        }
        return $this->repositorio;
    }

    private function detector()
    {
        // @ Objetivo
        // El detector de existencias negativas sobre la misma conexión y el mismo
        // repositorio, una sola vez por instancia.
        // @ Devolvemos
        //      PosstockC1Detector.
        if ($this->detector === null) {
            $this->detector = new PosstockC1Detector($this->conexionBDTPV(), $this->repositorio());
        }
        return $this->detector;
    }

    private function idsCsv($ids)
    {
        // @ Objetivo
        // La lista de identificadores lista para una cláusula IN. Una lista vacía no
        // da una consulta sin resultados, da un error de sintaxis: por eso devuelve
        // cadena vacía y quien llama no consulta.
        // @ Parametros
        //      $ids -> array.
        // @ Devolvemos
        //      string, los identificadores separados por comas, o '' si no hay.
        return implode(',', array_map('intval', $ids));
    }

    private function filasDe($sql)
    {
        // @ Objetivo
        // Ejecutar la consulta y entregar siempre un array de filas: una lectura que
        // no devuelve conjunto es un conjunto vacío, y no algo que cada método tenga
        // que mirar por su cuenta.
        // @ Parametros
        //      $sql -> string.
        // @ Devolvemos
        //      array de filas.
        $filas = $this->consulta($sql)['datos'];
        if (!is_array($filas)) {
            return array();
        }
        return $filas;
    }

    private function idsDe($sql)
    {
        // @ Objetivo
        // La columna idArticulo de la consulta, como enteros.
        // @ Parametros
        //      $sql -> string, con idArticulo entre los campos seleccionados.
        // @ Devolvemos
        //      array de int.
        $ids = array();
        foreach ($this->filasDe($sql) as $fila) {
            $ids[] = (int) $fila['idArticulo'];
        }
        return $ids;
    }

    private function lineasDe($sql)
    {
        // @ Objetivo
        // Las líneas de movimiento de la consulta, con las unidades ya en número. El
        // signo y la clase de movimiento que sea no se ponen aquí.
        // @ Parametros
        //      $sql -> string, con fecha y nunidades entre los campos seleccionados.
        // @ Devolvemos
        //      array de filas ['fecha' => string, 'nunidades' => float].
        $lineas = array();
        foreach ($this->filasDe($sql) as $fila) {
            $lineas[] = array(
                'fecha' => $fila['fecha'],
                'nunidades' => (float) $fila['nunidades'],
            );
        }
        return $lineas;
    }
}
