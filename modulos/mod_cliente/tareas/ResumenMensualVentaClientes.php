<?php

$Cliente = new ClaseCliente();

$clientes = $Cliente->obtenerClientes('WHERE estado="Activo"');

$contador = 0;
$now = (new \DateTime())->format('Y-m-d H:i:s');
$resumen=[];
foreach( $clientes as $cliente ){
    $resumen['idClientes'] = $Cliente->ticketsClienteDesglose($cliente['idClientes'],'2021-01-01',$now);
    error_log(json_encode($resumen));
        if($contador == 5){
        break;
    }
//    error_log(json_encode($resumen['desglose']));
//     $sql = "INSERT INTO 'descuentos_tickets' 
//         SET 'idCliente' =".$cliente['id'].",";
//     $sql .= "'descuentoCliente' = 3.0 ,";
//     $sql .= "'fechaInicio' = '".$now."',";
//     $sql .= "'fechaFin' = '".$now."',";
//     $sql .= "'numTickets' = 0,";
//     $sql .= "'ImporteTickets' = 0.00,";
//     $sql .= "'ImporteDescuento' = 0.00,";
//     $sql .= "'IdUsuario' = 1,";
//     $sql .= "'fechaCreacion' = '".$now."',";
//     $smt=$BDTpv->query($sql);
//     if (gettype($smt)==='array'){
//         $respuesta['error']=$smt['error'];
//         $respuesta['consulta']=$smt['consulta'];
//         $resultado = 'error';
//         break;
//     }
    $contador++;
}


            // $totalLinea=0;
            // $totalbases=0;
            // $resultado = [];
            // if(count($resumen)>0){
            //     $resultado[] = '<table>';
            //     foreach($resumen as $bases){
            //         $totalLinea=$bases['sumabase']+$bases['sumarIva'];
            //         $totalbases=$totalbases+$totalLinea;
            //         $numTicket=$bases['idTienda'].'-'.$bases['idUsuario'].'-'.$bases['Numticket'];
            //         $resultado[] = '<tr>
            //         <td>'.$bases['fecha'].'</td>
            //         <td>'.$numTicket.'</td>
            //         <td>'.$bases['sumabase'].'</td>
            //         <td>'.$bases['sumarIva'].'</td>
            //         <td>'.$totalLinea.'</td>
            //         </tr>';
            //     }
            //     $resultado[] = '</table>';
            // }

$resultado = 'insertados: '.$contador;
