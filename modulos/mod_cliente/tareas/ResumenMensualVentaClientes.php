<?php

$Cliente = new ClaseCliente();
$DescuentosTicket = new ClaseDescuentosTicket();

$clientes = $Cliente->obtenerClientes('WHERE estado="Activo"');
$contador = 0;
$now = (new \DateTime())->format('Y-m-d H:i:s');
$fechainicio = isset($_POST['fechainicio']) ? $_POST['fechainicio'] : '2021-01-01';
$fechafin = isset($_POST['fechafin']) ? $_POST['fechafin'] : $now;
$actualizar = isset($_POST['actualizar']) && ($ClasePermisos->getAccion("descuento_ticket_update",array('modulo'=>'mod_cliente','vista'=>'ListaClientes.php')) == 1) ? $_POST['actualizar'] : 0;
$resumen = [];
$resultado = [];
$registros_insertados = 0;
$registros_actualizados = 0;
$registros_ignorados = 0;
$registros_sintickets = 0;
foreach ($clientes as $cliente) {
    $tickets = $Cliente->ticketsClienteDesglose($cliente['idClientes'], $fechainicio, $fechafin);
    if (count($tickets) > 0) {
        $resumen[$cliente['idClientes']] = $tickets;
        $totalLinea = 0;
        $totalTickets = 0;
        $contaTicket = 0;
        foreach ($tickets as $ticket) {
            $totalLinea = $ticket['sumabase'] + $ticket['sumarIva'];
            $totalTickets += $totalLinea;
            $contaTicket++;
        }
        $importeDescuento = $totalTickets * (($cliente['descuento_ticket'] / 100));
        $descuentosCliente = $DescuentosTicket->leerCliente($cliente['idClientes'], ['DATE(fechaInicio)=\'' . $fechainicio . '\'']);
        $registro = [
            'idCliente' => $cliente['idClientes'],
            'descuentoCliente' => $cliente['descuento_ticket'],
            'fechaInicio' => $fechainicio,
            'fechaFin' => $fechafin,
            'numTickets' => $contaTicket,
            'importeTickets' => $totalTickets,
            'importeDescuento' => $importeDescuento,
            'idUsuario' => $Usuario['id'],
        ];
        if (count($descuentosCliente) > 0) {
            if ($actualizar==1 && round($importeDescuento,2) != floatval($descuentosCliente[0]['importeDescuento'])) {
                $registros_actualizados++;
                error_log('Entro-actualizar el id cliente:'.$cliente['idClientes']);
                $DescuentosTicket->update($registro,['idCliente='.$cliente['idClientes']]);
            } else {
                $registros_ignorados++;
            }
        } else {
            $registros_insertados++;
            $DescuentosTicket->insert($registro);
        }
    } else {
        $registros_sintickets++;
    }

    $contador++;
}

$resultado[] = '<table><tr><td>' . 'Clientes procesados: '.$contador . '</td></tr>' .
'<tr><td>' . 'Clientes sin tickets: '.$registros_sintickets . '</td></tr>' .
'<tr><td>' . 'Descuentos insertados: '.$registros_insertados . '</td></tr>' .
'<tr><td>' . 'Descuentos actualizados: '.$registros_actualizados . '</td></tr>' .
'<tr><td>' . 'Descuentos ignorados: '.$registros_ignorados . '</td></tr>' .
'<tr><td>' . 'Actualizar si existe: <b>'.($actualizar ? 'Si' : 'No') . '</b></td></tr>'.
'</table';


// SELECT id,fechaInicio,fechaFin,idCliente,C.Nombre,descuentoCliente,numTickets,importeTickets,importeDescuento FROM `descuentos_tickets` as D LEFT JOIN clientes as C on D.idCliente=C.idClientes
//$resultado = $resumen; //'insertados: '.$contador;
