<?php 
$pulsado = $_POST['pulsado'];

include_once './../../inicial.php';
include_once $URLCom.'/modulos/mod_cliente/funciones.php';
include_once $URLCom.'/modulos/mod_cliente/clases/claseTarifaCliente.php';
include_once $URLCom."/modulos/mod_cliente/clases/ClaseCliente.php";
include_once $URLCom."/modulos/mod_cliente/clases/ClaseDescuentosTicket.php";
include_once $URLCom."/modulos/mod_incidencias/clases/ClaseIncidencia.php";
require_once ($URLCom.'/modulos/mod_producto/clases/ClaseProductos.php');
require_once ($URLCom.'/controllers/Controladores.php');

switch ($pulsado) {
    case 'abririncidencia':
        include_once $URLCom.'/modulos/mod_cliente/tareas/incidencias_popup.php';
        break;
        
    case 'nuevaIncidencia':
        include_once $URLCom.'/modulos/mod_cliente/tareas/incidencias_grabar.php';
        breaK;
    
    case 'Grabar_tarifa_producto_cliente':
        include_once $URLCom.'/modulos/mod_cliente/tareas/grabarArticuloCliente.php';
        $respuesta = $resultado;
        break;
    
    case 'Borrar_producto_tarifa_cliente':
        include_once $URLCom.'/modulos/mod_cliente/tareas/borrarArticuloCliente.php';
        $respuesta = $resultado;
        break;
    
    case 'leerArticulo':
        include_once $URLCom.'/modulos/mod_cliente/tareas/leerArticulo.php';
        $respuesta = $resultado;
        break;
    case 'imprimirResumen':
        include_once $URLCom.'/modulos/mod_cliente/tareas/imprimirResumen.php';
        $respuesta=$resultado;
        break;
    case 'imprimirTarifasCliente':
        include_once $URLCom.'/modulos/mod_cliente/tareas/imprimirTarifasCliente.php';
        $respuesta=$resultado;
        break;
    case 'imprimirFichaCliente':
        include_once $URLCom.'/modulos/mod_cliente/tareas/imprimirFichaCliente.php';
        $respuesta=$resultado;
        break;
    case 'generarResumenTicketsClientes':
        include_once $URLCom.'/modulos/mod_cliente/tareas/ResumenMensualVentaClientes.php';
        $respuesta=$resultado;
        break;
}
echo json_encode($respuesta);
return $respuesta;
?>
