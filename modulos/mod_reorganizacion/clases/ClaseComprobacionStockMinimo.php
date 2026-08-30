<?php

include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockConsulta.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockCantidad.php';

// @ Objetivo
// Reconstruir los movimientos del ejercicio anterior para el producto admitido y
// determinar el stock mínimo justificado por esos movimientos, con su margen. Si el
// histórico no permite establecerlo, lo marca como incompleto sin alterar el cálculo
// del resto de productos.
//
// No lee la base: las líneas de cada origen se las pide a la clase de consulta, y
// qué significa cada una —qué suma, qué resta y qué abre lote— lo decide aquí.
class ClaseComprobacionStockMinimo
{
    private $consulta = null;

    public function calcular($filas, $contextoOperacion, $proveedorTraspaso)
    {
        // @ Objetivo
        // Para cada fila admitida, leer sus movimientos del ejercicio anterior, formar
        // los lotes y determinar el stock justificado con su margen y condiciones.
        // @ Parametros
        //      $filas -> array de filas emparejadas (ClaseComprobacionStockAdmision::admitir()),
        //          cada una con su marca 'comparable'.
        //      $contextoOperacion -> array, la salida de ClaseComprobacionStockContexto::abrir()
        //          en este ejercicio (el anterior): fija el borde del calendario.
        //      $proveedorTraspaso -> int, el proveedor que declara el fichero admitido:
        //          sus albaranes de frontera son los dos traspasos y quedan fuera de la
        //          ventana, sin volver a leer la configuración local de este despliegue.
        // @ Devolvemos
        //      array de filas con 'stockJustificado', 'margen' y 'condicionesConocidas'
        //      (ampliado) añadidos.
        $consulta = $this->consulta();
        $ano = (int) $contextoOperacion['ano'];
        $desde = $ano . '-01-01';
        $hasta = $ano . '-12-31';

        $resultado = array();
        foreach ($filas as $fila) {
            // Un producto que no está en el catálogo de este ejercicio no tiene nada que
            // reconstruir: no hay histórico incompleto que declarar, porque lo que le
            // ocurre es que aquí no existe, y colgarle esa condición pondría en el informe
            // un hallazgo sobre un producto del que no hay hallazgo ninguno.
            if (!$fila['comparable']) {
                $fila['stockJustificado'] = null;
                $fila['margen'] = 0.0;
                $resultado[] = $fila;
                continue;
            }

            $idArticulo = (int) $fila['idArticulo'];

            $movimientos = $this->componerMovimientos(
                $consulta->lineasDeAlbaranDeProveedor($idArticulo, $desde, $hasta, $proveedorTraspaso),
                $consulta->lineasDeTicket($idArticulo, $desde, $hasta),
                $consulta->lineasDeAlbaranDeCliente($idArticulo, $desde, $hasta)
            );
            $tipoArticulo = $this->tipoSupuesto($consulta->tipoDeArticulo($idArticulo));
            $justificado = $this->justificar($movimientos, $tipoArticulo);

            $fila['stockJustificado'] = $justificado['stockJustificado'];
            $fila['margen'] = $justificado['margen'];
            $fila['condicionesConocidas'] = array_merge($fila['condicionesConocidas'], $justificado['condicionesConocidas']);
            $resultado[] = $fila;
        }
        return $resultado;
    }

    public function componerMovimientos($lineasDeProveedor, $lineasDeTicket, $lineasDeCliente)
    {
        // @ Objetivo
        // Reunir los tres orígenes en una sola lista de movimientos, poniendo a cada
        // línea el signo con el que afecta a las existencias y la clase de movimiento
        // que es. Una línea de albarán de proveedor entra tal cual: en positivo es una
        // recepción y abre lote; en negativo es una devolución al proveedor, resta por
        // su propio signo y no abre lote. Ticket y albarán de cliente son salidas y
        // restan siempre.
        // @ Parametros
        //      $lineasDeProveedor, $lineasDeTicket, $lineasDeCliente -> array de filas
        //          ['fecha','nunidades'], cada una de su origen.
        // @ Devolvemos
        //      array de ['fecha', 'delta', 'tipo'], sin ordenar.
        $movimientos = array();

        foreach ($lineasDeProveedor as $linea) {
            $movimientos[] = array(
                'fecha' => $linea['fecha'],
                'delta' => $linea['nunidades'],
                'tipo' => $linea['nunidades'] >= 0 ? 'recepcion' : 'devolucion',
            );
        }

        foreach ($lineasDeTicket as $linea) {
            $movimientos[] = array(
                'fecha' => $linea['fecha'],
                'delta' => -1 * $linea['nunidades'],
                'tipo' => 'venta',
            );
        }

        foreach ($lineasDeCliente as $linea) {
            $movimientos[] = array(
                'fecha' => $linea['fecha'],
                'delta' => -1 * $linea['nunidades'],
                'tipo' => 'salida_cliente',
            );
        }

        return $movimientos;
    }

    public function tipoSupuesto($tipoDelCatalogo)
    {
        // @ Objetivo
        // Cómo se mide el producto. Si el catálogo no lo dice, se supone que se mide
        // por unidades: es el caso general, y el que no aplica margen.
        // @ Parametros
        //      $tipoDelCatalogo -> string o null, lo que devuelve el catálogo.
        // @ Devolvemos
        //      string.
        if ($tipoDelCatalogo === null) {
            return 'unidad';
        }
        return $tipoDelCatalogo;
    }

    public function justificar($movimientos, $tipoArticulo)
    {
        // @ Objetivo
        // Formar los lotes del periodo entre recepciones y recorrerlos desde el más
        // reciente hacia atrás, sumando sus balances hasta el primero negativo, que
        // queda fuera de la suma. Sin ninguna recepción no hay lote que reconstruir.
        // @ Parametros
        //      $movimientos -> array de ['fecha','delta','tipo'], ya sin los dos
        //          traspasos. tipo: 'recepcion', 'devolucion', 'venta' o 'salida_cliente'.
        //      $tipoArticulo -> string, 'peso' o 'unidad'.
        // @ Devolvemos
        //      array ['stockJustificado' => float|null, 'margen' => float,
        //      'condicionesConocidas' => array].
        $lotes = $this->formarLotes($movimientos);

        if (empty($lotes)) {
            return array(
                'stockJustificado' => null,
                'margen' => 0.0,
                'condicionesConocidas' => array('historico_incompleto'),
            );
        }

        // Si el ancla es el lote más reciente, no queda nada posterior y el mínimo sale
        // cero. No es el cero que la falta de histórico produciría: aquí hay lotes y se
        // han recorrido, y lo que dicen es que ni el último tramo se sostiene solo. Cero
        // es entonces una restricción establecida —el registro no justifica ninguna
        // existencia— y no una ausencia de base, así que se emite como cantidad.
        $stockJustificado = 0.0;
        $salidasContadas = 0;
        foreach (array_reverse($lotes) as $lote) {
            if ($lote['balance'] < 0) {
                break;
            }
            $stockJustificado = ClaseComprobacionStockCantidad::normalizar($stockJustificado + $lote['balance']);
            $salidasContadas += $lote['salidas'];
        }

        return array(
            'stockJustificado' => $stockJustificado,
            'margen' => $this->margen($tipoArticulo, $salidasContadas),
            'condicionesConocidas' => array(),
        );
    }

    private function formarLotes($movimientos)
    {
        // @ Objetivo
        // Un lote empieza en una recepción (inclusive) y termina justo antes de la
        // siguiente, o en el borde de la ventana si es el último. Una devolución a
        // proveedor no abre lote: se suma al balance del lote en curso, igual que una
        // venta. Los movimientos anteriores a la primera recepción no entran en ningún
        // lote: no hay recepción que los delimite.
        //
        // Los movimientos se fechan por día, sin hora, de modo que coincidir en el
        // mismo día es lo corriente y hay que decidirlo: dentro de un día la recepción
        // va primero, y lo que salió ese día pertenece al lote que la recepción abre.
        // Es lo coherente con que el lote empiece en la recepción inclusive; dejarlo al
        // orden en que se leyeron los tres orígenes haría que el resultado dependiera de
        // en qué orden se concatenan.
        // @ Devolvemos
        //      array de ['balance' => float, 'salidas' => int], en orden cronológico.
        $ordenados = $movimientos;
        usort($ordenados, function ($a, $b) {
            $porFecha = strcmp($a['fecha'], $b['fecha']);
            if ($porFecha !== 0) {
                return $porFecha;
            }
            $primeroLaRecepcion = ($a['tipo'] === 'recepcion' ? 0 : 1) - ($b['tipo'] === 'recepcion' ? 0 : 1);
            return $primeroLaRecepcion;
        });

        $lotes = array();
        $loteActual = null;
        foreach ($ordenados as $movimiento) {
            if ($movimiento['tipo'] === 'recepcion') {
                if ($loteActual !== null) {
                    $lotes[] = $loteActual;
                }
                $loteActual = array('balance' => 0.0, 'salidas' => 0);
            }

            if ($loteActual === null) {
                continue;
            }

            // En la precisión en que la cantidad existe, porque el balance del lote se
            // compara después con cero para decidir dónde se corta el recorrido: un lote
            // que se cancela exactamente daría negativo por residuo y cortaría antes de
            // tiempo, dejando fuera lotes que sí justifican existencia.
            $loteActual['balance'] = ClaseComprobacionStockCantidad::normalizar(
                $loteActual['balance'] + $movimiento['delta']
            );
            // Cuenta toda salida al cliente, por ticket o por albarán: lo que el margen
            // acota es la imprecisión que cada pesada acumula, y una salida por albarán
            // se pesa igual que una de ticket. Cuenta líneas y no documentos por lo
            // mismo: dos líneas del mismo producto en un ticket son dos pesadas.
            if ($movimiento['tipo'] === 'venta' || $movimiento['tipo'] === 'salida_cliente') {
                $loteActual['salidas']++;
            }
        }
        if ($loteActual !== null) {
            $lotes[] = $loteActual;
        }

        return $lotes;
    }

    private function margen($tipoArticulo, $salidasContadas)
    {
        // Solo el producto que se registra por peso acumula imprecisión de pesaje, y
        // solo cuentan las salidas de los lotes contados: las anteriores al ancla no
        // sostienen el resultado y ensancharían el margen sin motivo.
        if ($tipoArticulo !== 'peso') {
            return 0.0;
        }
        return max(0.5, 0.010 * $salidasContadas);
    }

    private function consulta()
    {
        // @ Objetivo
        // La clase de consulta del módulo, una sola vez por instancia: el cálculo
        // recorre producto a producto y no abre una lectura nueva en cada vuelta.
        // @ Devolvemos
        //      ClaseComprobacionStockConsulta.
        if ($this->consulta === null) {
            $this->consulta = new ClaseComprobacionStockConsulta();
        }
        return $this->consulta;
    }
}
