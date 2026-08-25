<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';

// @ Objetivo
// Reconstruir los movimientos del ejercicio anterior para el producto admitido y
// determinar el stock mínimo justificado por esos movimientos, con su margen. Si el
// histórico no permite establecerlo, lo marca como incompleto sin alterar el cálculo
// del resto de productos.
class ClaseComprobacionMinimo extends TFModelo
{
    public function calcular($filas, $contextoOperacion, $proveedorTraspaso)
    {
        // @ Objetivo
        // Para cada fila admitida, leer sus movimientos del ejercicio anterior, formar
        // los lotes y determinar el stock justificado con su margen y condiciones.
        // @ Parametros
        //      $filas -> array de filas emparejadas (ClaseComprobacionAdmision::admitir()).
        //      $contextoOperacion -> array, la salida de ClaseComprobacionContexto::abrir()
        //          en este ejercicio (el anterior): fija tienda y el borde del calendario.
        //      $proveedorTraspaso -> int, el proveedor que declara el fichero admitido:
        //          sus albaranes son los dos traspasos y quedan fuera de la ventana, sin
        //          volver a leer la configuración local de este despliegue.
        // @ Devolvemos
        //      array de filas con 'stockJustificado', 'margen' y 'condicionesConocidas'
        //      (ampliado) añadidos.
        $resultado = array();
        foreach ($filas as $fila) {
            $movimientos = $this->movimientosDe($fila['idArticulo'], $contextoOperacion, $proveedorTraspaso);
            $tipoArticulo = $this->tipoDe($fila['idArticulo']);
            $justificado = $this->justificar($movimientos, $tipoArticulo);

            $fila['stockJustificado'] = $justificado['stockJustificado'];
            $fila['margen'] = $justificado['margen'];
            $fila['condicionesConocidas'] = array_merge($fila['condicionesConocidas'], $justificado['condicionesConocidas']);
            $resultado[] = $fila;
        }
        return $resultado;
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

        $stockJustificado = 0.0;
        $ventasContadas = 0;
        foreach (array_reverse($lotes) as $lote) {
            if ($lote['balance'] < 0) {
                break;
            }
            $stockJustificado += $lote['balance'];
            $ventasContadas += $lote['ventas'];
        }

        return array(
            'stockJustificado' => $stockJustificado,
            'margen' => $this->margen($tipoArticulo, $ventasContadas),
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
        // @ Devolvemos
        //      array de ['balance' => float, 'ventas' => int], en orden cronológico.
        $ordenados = $movimientos;
        usort($ordenados, function ($a, $b) {
            return strcmp($a['fecha'], $b['fecha']);
        });

        $lotes = array();
        $loteActual = null;
        foreach ($ordenados as $movimiento) {
            if ($movimiento['tipo'] === 'recepcion') {
                if ($loteActual !== null) {
                    $lotes[] = $loteActual;
                }
                $loteActual = array('balance' => 0.0, 'ventas' => 0);
            }

            if ($loteActual === null) {
                continue;
            }

            $loteActual['balance'] += $movimiento['delta'];
            if ($movimiento['tipo'] === 'venta') {
                $loteActual['ventas']++;
            }
        }
        if ($loteActual !== null) {
            $lotes[] = $loteActual;
        }

        return $lotes;
    }

    private function margen($tipoArticulo, $ventasContadas)
    {
        if ($tipoArticulo !== 'peso') {
            return 0.0;
        }
        return max(0.5, 0.010 * $ventasContadas);
    }

    private function movimientosDe($idArticulo, $contextoOperacion, $proveedorTraspaso)
    {
        // @ Objetivo
        // Los tres orígenes de movimientos de PCP-TPX §4.6 sobre el producto, en el
        // ejercicio anterior y su tienda, sin los albaranes del proveedor de traspaso.
        // @ Devolvemos
        //      array de ['fecha','delta','tipo'].
        $ano = (int) $contextoOperacion['ano'];
        $idTienda = (int) $contextoOperacion['idTienda'];
        $idArticulo = (int) $idArticulo;
        $proveedorTraspaso = (int) $proveedorTraspaso;
        $fi = $ano . '-01-01';
        $ff = $ano . '-12-31';

        $movimientos = array();

        $filas = $this->consulta("
            SELECT DATE(c.Fecha) AS fecha, l.nunidades AS nunidades
            FROM albprolinea l
            INNER JOIN albprot c ON c.id = l.idalbpro
            WHERE c.idTienda = $idTienda
              AND l.idArticulo = $idArticulo
              AND l.estadoLinea = 'Activo'
              AND c.estado IN ('Guardado', 'Facturado', 'Exportado', 'Importado')
              AND DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.idProveedor <> $proveedorTraspaso
        ")['datos'];
        if (is_array($filas)) {
            foreach ($filas as $fila) {
                $nunidades = (float) $fila['nunidades'];
                $movimientos[] = array(
                    'fecha' => $fila['fecha'],
                    'delta' => $nunidades,
                    'tipo' => $nunidades >= 0 ? 'recepcion' : 'devolucion',
                );
            }
        }

        $filas = $this->consulta("
            SELECT DATE(c.Fecha) AS fecha, l.nunidades AS nunidades
            FROM ticketslinea l
            INNER JOIN ticketst c ON c.id = l.idticketst
            WHERE c.idTienda = $idTienda
              AND l.idArticulo = $idArticulo
              AND l.estadoLinea = 'Activo'
              AND c.estado = 'Cerrado'
              AND DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
        ")['datos'];
        if (is_array($filas)) {
            foreach ($filas as $fila) {
                $movimientos[] = array(
                    'fecha' => $fila['fecha'],
                    'delta' => -1 * (float) $fila['nunidades'],
                    'tipo' => 'venta',
                );
            }
        }

        $filas = $this->consulta("
            SELECT DATE(c.Fecha) AS fecha, l.nunidades AS nunidades
            FROM albclilinea l
            INNER JOIN albclit c ON c.id = l.idalbcli
            WHERE c.idTienda = $idTienda
              AND l.idArticulo = $idArticulo
              AND l.estadoLinea = 'Activo'
              AND c.estado IN ('Guardado', 'Procesado')
              AND DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
        ")['datos'];
        if (is_array($filas)) {
            foreach ($filas as $fila) {
                $movimientos[] = array(
                    'fecha' => $fila['fecha'],
                    'delta' => -1 * (float) $fila['nunidades'],
                    'tipo' => 'salida_cliente',
                );
            }
        }

        return $movimientos;
    }

    private function tipoDe($idArticulo)
    {
        $idArticulo = (int) $idArticulo;
        $filas = $this->consulta("SELECT tipo FROM articulos WHERE idArticulo = $idArticulo")['datos'];
        if (is_array($filas) && count($filas) > 0) {
            return (string) $filas[0]['tipo'];
        }
        return 'unidad';
    }
}
