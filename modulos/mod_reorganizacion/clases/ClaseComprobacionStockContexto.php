<?php

include_once $URLCom . '/controllers/parametros.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockConsulta.php';

// @ Objetivo
// Fijar ejercicio y tienda desde la sesión activa, comprobar que el esquema y los
// parámetros de los que depende el cálculo están presentes, y abrir el bloque de
// solo lectura que envuelve toda la ejecución. Si algo falta, detiene la ejecución
// con el motivo; nunca deja pasar un resultado vacío.
//
// No lee la base: qué objetos del esquema hacen falta y en qué orden se comprueban
// se decide aquí; si cada uno está presente, y la apertura misma, lo resuelve la
// clase de consulta.
class ClaseComprobacionStockContexto
{
    private $consulta = null;

    // Cada vista y cada tabla que la ejecución llega a leer, por consulta propia o
    // a través del componente de existencias negativas que consume. Una que falte y
    // no esté aquí no detiene nada: deja llegar la ejecución hasta la consulta que la
    // necesita.
    private $objetosDelEsquemaRequeridos = array(
        'vw_jerarquias_familias',
        'articulos',
        'articulosFamilias',
        'articulosStocks',
        'stocksRegularizacion',
        'proveedores',
        'albprot',
        'albprolinea',
        'ticketst',
        'ticketslinea',
        'albclit',
        'albclilinea',
    );

    // Ventana y umbrales de la sección posstock de mod_informes/parametros.xml. Los
    // cuatro que empiezan por c1_ gobiernan la detección de existencias negativas: si
    // no se le pasan, el componente que la resuelve aplica el valor por defecto de su
    // firma y el resultado depende de un número que nadie fijó ni declara.
    private $parametrosDeCriterioRequeridos = array(
        'ventana_dias',
        'c1_umbral_fraccionado',
        'c1_umbral_magnitud',
        'c1_umbral_por_venta',
        'c1_timing_ventana_dias',
    );

    private $rutaParametrosInformes = '/modulos/mod_informes/parametros.xml';
    private $rutaParametrosModulo = '/modulos/mod_reorganizacion/parametros.xml';
    private $rutaProveedorCierre = 'configuracion/cierre_stock_anual/ajustes_globales/proveedor';
    private $rutaFamiliasExcluidas = 'configuracion/cierre_stock_anual/familias_excluidas';

    public function __construct(
        $objetosDelEsquemaRequeridos = null,
        $parametrosDeCriterioRequeridos = null,
        $rutaProveedorCierre = null,
        $rutaFamiliasExcluidas = null
    ) {
        // @ Objetivo
        // Admitir, solo para comprobación, listas o rutas distintas de las que exige el
        // criterio real: así se puede probar que la ausencia de cualquiera de ellas
        // detiene la ejecución sin tocar la instalación real.
        // @ Parametros
        //      $objetosDelEsquemaRequeridos -> array, opcional. Sin él, exige el real.
        //      $parametrosDeCriterioRequeridos -> array, opcional. Sin él, exige el real.
        //      $rutaProveedorCierre -> string, opcional. Sin ella, exige la real.
        //      $rutaFamiliasExcluidas -> string, opcional. Sin ella, exige la real.
        if ($objetosDelEsquemaRequeridos !== null) {
            $this->objetosDelEsquemaRequeridos = $objetosDelEsquemaRequeridos;
        }
        if ($parametrosDeCriterioRequeridos !== null) {
            $this->parametrosDeCriterioRequeridos = $parametrosDeCriterioRequeridos;
        }
        if ($rutaProveedorCierre !== null) {
            $this->rutaProveedorCierre = $rutaProveedorCierre;
        }
        if ($rutaFamiliasExcluidas !== null) {
            $this->rutaFamiliasExcluidas = $rutaFamiliasExcluidas;
        }
    }

    public function abrir()
    {
        // @ Objetivo
        // Recorrer sesión, esquema, parámetros y apertura del bloque de lectura, en
        // ese orden.
        // @ Devolvemos
        //      array ['ok' => true, 'ano' => .., 'idTienda' => ..] si todo está listo,
        //          con el momento y la fecha de corte de esta ejecución y los valores
        //          del criterio, para que nadie vuelva a leer las mismas fuentes.
        //      array ['ok' => false, 'motivo' => ..] en la primera comprobación que falle.
        $sesion = $this->deLaSesion();
        if ($sesion === null) {
            return $this->parada('No hay ejercicio ni tienda establecidos en la sesión');
        }

        $objetoAusente = $this->objetoDelEsquemaAusente();
        if ($objetoAusente !== null) {
            return $this->parada('Falta en el esquema: ' . $objetoAusente);
        }

        $parametros = $this->parametrosDeCriterio();
        if ($parametros['ausente'] !== null) {
            return $this->parada('Falta el parámetro: ' . $parametros['ausente']);
        }

        if (!$this->consulta()->abrirBloqueDeLectura()) {
            return $this->parada('El motor no admite el bloque de lectura de solo lectura');
        }

        // El reloj se lee una sola vez, y aquí, que es donde la ejecución empieza. De
        // esa lectura salen las dos cosas que dependen de él: el momento que identifica
        // lo emitido, y la fecha hasta la que se lee, que acota los movimientos y desde
        // la que se cuenta hacia atrás la ventana de consolidación. Leyendo el reloj en
        // dos sitios, una ejecución larga puede cruzar la medianoche y declarar un
        // momento de un día sobre datos de otro.
        $instante = time();

        return array(
            'ok' => true,
            'ano' => $sesion['ano'],
            'idTienda' => $sesion['idTienda'],
            'momento' => date('c', $instante),
            'fechaCorte' => date('Y-m-d', $instante),
            'ventanaDias' => $parametros['valores']['ventanaDias'],
            'umbralFraccionado' => $parametros['valores']['umbralFraccionado'],
            'umbralMagnitud' => $parametros['valores']['umbralMagnitud'],
            'umbralPorVenta' => $parametros['valores']['umbralPorVenta'],
            'timingVentanaDias' => $parametros['valores']['timingVentanaDias'],
            'proveedorCierre' => $parametros['valores']['proveedorCierre'],
            'familiasExcluidas' => $parametros['valores']['familiasExcluidas'],
        );
    }

    public function cerrar()
    {
        // @ Objetivo
        // Cerrar el bloque de lectura abierto por abrir(). Ninguna ejecución lo deja
        // pendiente.
        $this->consulta()->cerrarBloqueDeLectura();
    }

    private function deLaSesion()
    {
        // @ Objetivo
        // Tomar ejercicio y tienda de la sesión activa, sin derivarlos de ningún otro
        // sitio.
        // @ Devolvemos
        //      array ['ano' => .., 'idTienda' => ..] o null si no están establecidos.
        if (!isset($_SESSION['tiendaTpv']['ano']) || !isset($_SESSION['tiendaTpv']['idTienda'])) {
            return null;
        }

        return array(
            'ano' => $_SESSION['tiendaTpv']['ano'],
            'idTienda' => $_SESSION['tiendaTpv']['idTienda'],
        );
    }

    private function objetoDelEsquemaAusente()
    {
        // @ Objetivo
        // Comprobar que existen la vista y las tablas de las que depende la lectura.
        // @ Devolvemos
        //      string con el nombre del primer objeto que falte, o null si están todos.
        $consulta = $this->consulta();
        foreach ($this->objetosDelEsquemaRequeridos as $objeto) {
            if (!$consulta->existeObjetoDeEsquema($objeto)) {
                return $objeto;
            }
        }

        return null;
    }

    private function parametrosDeCriterio()
    {
        // @ Objetivo
        // Comprobar que están presentes los parámetros de los que depende el criterio:
        // ventana y umbrales de mod_informes/parametros.xml, proveedor de cierre y
        // familias excluidas de mod_reorganizacion/parametros.xml. Si lo están, extraer
        // sus valores: son los que necesitan los componentes que operan con el
        // contexto de operación, y así no vuelven a leer los mismos ficheros.
        // @ Devolvemos
        //      array ['ausente' => string] con la ruta del primero que falte.
        //      array ['ausente' => null, 'valores' => [...]] si están todos.
        global $URLCom;

        $informes = new ClaseParametros($URLCom . $this->rutaParametrosInformes);
        $posstock = $informes->getNode('configuracion/posstock');
        foreach ($this->parametrosDeCriterioRequeridos as $parametro) {
            if ($posstock === null || !isset($posstock->$parametro) || (string) $posstock->$parametro === '') {
                return array('ausente' => 'posstock/' . $parametro);
            }
        }

        $modulo = new ClaseParametros($URLCom . $this->rutaParametrosModulo);
        $nodoProveedor = $modulo->getNode($this->rutaProveedorCierre);
        if ($nodoProveedor === null) {
            return array('ausente' => 'cierre_stock_anual/ajustes_globales/proveedor');
        }
        $nodoFamilias = $modulo->getNode($this->rutaFamiliasExcluidas);
        if ($nodoFamilias === null) {
            return array('ausente' => 'cierre_stock_anual/familias_excluidas');
        }

        $atributosProveedor = $nodoProveedor->attributes();
        $familiasExcluidas = array();
        foreach ($nodoFamilias->familia as $familia) {
            $atributosFamilia = $familia->attributes();
            if (isset($atributosFamilia['id'])) {
                $familiasExcluidas[] = (int) $atributosFamilia['id'];
            }
        }

        return array(
            'ausente' => null,
            'valores' => array(
                'ventanaDias' => (int) (string) $posstock->ventana_dias,
                'umbralFraccionado' => (float) (string) $posstock->c1_umbral_fraccionado,
                'umbralMagnitud' => (float) (string) $posstock->c1_umbral_magnitud,
                'umbralPorVenta' => (float) (string) $posstock->c1_umbral_por_venta,
                'timingVentanaDias' => (int) (string) $posstock->c1_timing_ventana_dias,
                'proveedorCierre' => isset($atributosProveedor['id']) ? (int) $atributosProveedor['id'] : null,
                'familiasExcluidas' => $familiasExcluidas,
            ),
        );
    }

    private function consulta()
    {
        // @ Objetivo
        // La clase de consulta del módulo, una sola vez por instancia: abrir() y
        // cerrar() han de operar sobre el mismo bloque de lectura.
        // @ Devolvemos
        //      ClaseComprobacionStockConsulta.
        if ($this->consulta === null) {
            $this->consulta = new ClaseComprobacionStockConsulta();
        }
        return $this->consulta;
    }

    private function parada($motivo)
    {
        return array('ok' => false, 'motivo' => $motivo);
    }
}
