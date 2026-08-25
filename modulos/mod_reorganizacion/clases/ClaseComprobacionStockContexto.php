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

    // Vista y tablas de las que depende la lectura, además del catálogo.
    private $objetosDelEsquemaRequeridos = array(
        'vw_jerarquias_familias',
        'articulos',
        'albprot',
        'albprolinea',
        'ticketst',
        'ticketslinea',
        'albclit',
        'albclilinea',
    );

    // Umbrales y ventana de la sección posstock de mod_informes/parametros.xml.
    private $parametrosDeCriterioRequeridos = array(
        'ventana_dias',
        'umbral_sobrestock',
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
        //      array ['ok' => true, 'ano' => .., 'idTienda' => ..] si todo está listo.
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

        return array(
            'ok' => true,
            'ano' => $sesion['ano'],
            'idTienda' => $sesion['idTienda'],
            'ventanaDias' => $parametros['valores']['ventanaDias'],
            'umbralSobrestock' => $parametros['valores']['umbralSobrestock'],
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
        // umbrales y ventana de mod_informes/parametros.xml, proveedor de cierre y
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
                'umbralSobrestock' => (float) (string) $posstock->umbral_sobrestock,
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
