<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *
 *   
 */


/* ===============  REALIZAMOS CONEXIONES  =============== */


$pulsado = $_POST['pulsado'];

include_once ("./../../inicial.php");

// Crealizamos conexion a la BD Datos
include_once'./clases/ClaseReorganizar.php';
include_once '../mod_producto/clases/ClaseArticulos.php';
include_once '../mod_producto/clases/ClaseArticulosStocks.php';

switch ($pulsado) {

    case 'contarproductos':
        $tipo = $_POST['tipo'];
        $CReorganizar = new ClaseReorganizar();
        if (isset($CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb)){
            $TiendaWeb = $CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb;
            $CReorganizar->setIdTiendaWeb($TiendaWeb['idTienda']);
        }
        $totalProductos = $CReorganizar->contar($tipo);
        echo json_encode(compact('totalProductos'));
        break;
    case 'generastock':
        $inicial = $_POST['inicial'];
        $pagina = $_POST['pagina'];
        $totalProductos = $_POST['totalProductos'];

        if($inicial == 0){
            alArticulosStocks::limpiaStock(); //idTienda = 1
            $resultado['stocks'][] = ['id'=>0,'stock'=> '000'];
        }
        
        $resultado = ['totalProductos' => $totalProductos, 'pagina' => $pagina];
        $articulo = new alArticulos();
        $seleccionArticulos = $articulo->leer(0, $inicial, $pagina);
        if ($seleccionArticulos) {
            $resultado['elementos'] = count($seleccionArticulos);
            $resultado['actual'] = $inicial + $resultado['elementos'];
            $idTienda = 1;
            foreach ($seleccionArticulos as $seleccionado) {
                
                $idArticulo = $seleccionado['idArticulo'];
                if ($articulo->existe($idArticulo)) {
                    $stock = $articulo->calculaStock($idArticulo);
                    if($stock != 0){
                    alArticulosStocks::actualizarStock($idArticulo, $idTienda, $stock, K_STOCKARTICULO_SUMA);
                    $resultado['stocks'][] = ['id'=>$idArticulo,'stock'=> $stock];
                    }
                    
                } else {
                    $resultado = 'No existe articulo';
                }
            }
        }
        echo json_encode($resultado);
        break;

    case 'subirStockYPrecio':
        $inicial = $_POST['inicial'];
        $cantidad = $_POST['cantidad'];
        $totalProductos = $_POST['totalProductos'];
        $CReorganizar = new ClaseReorganizar();
        if (isset($CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb)){
            $CVirtuemart = $CReorganizar->SetPlugin('ClaseVirtuemart');
            $TiendaWeb = $CVirtuemart->TiendaWeb;
            $CReorganizar->setIdTiendaWeb($TiendaWeb['idTienda']);
            
        }
        // Ahora obtenemos los ids productos de la web
        $idsWeb =$CReorganizar->obtenerIdsWeb($inicial,$cantidad);
        // Ahora enviamos a la web.
        $productos= json_encode($idsWeb['datos']);
        $r =$CVirtuemart->enviarStockYPrecio($productos);
        $resultado = array ( 'elementos' => $r['Datos']['consulta1'],
                             'elementos_precios' => $r['Datos']['consulta2'],
                             'actual'=> $inicial+ count($idsWeb['datos']) + 1,
                             'totalProductos' => $totalProductos
                            );
        // Si hubo un error deberías añadirlo.
        if (isset($r['Datos']['error'])){
            $resultado['error'] = $r;
        }

        echo json_encode($resultado);
    break;

    case 'reorganizarPermisosModulos':
        // Objetivo limpiar los permisos de modulos que no existen.
        $registro = $_POST['inicial'];
        $total_usuario = $_POST['total']; // total usuarios
        $usuario_default = array( 'id' => '0','group_id' =>'0');
        if ($registro === '0'){
            // Eliminamos los permiso del usuario 0 , que son los permisos por defecto.
            $borrado = $ClasePermisos->borrarPermisosUsuario(0);
            $resultado['borrado_default'] = $borrado;
            
        }
         $permisos_usuario_actual = $ClasePermisos->permisos ;
        // Ahora obtenemos los permisos por defecto.
        $permisos_defecto = $ClasePermisos->getPermisosUsuario($usuario_default); // Agray con todos los permisos.
        $ClasePermisos->permisos = $permisos_defecto; // La instancia de permisos tiene los permisos por defecto.

        // Ahora obtenemos todos los usuarios, para enviar .. el registro actual 
        $CReorganizar = new ClaseReorganizar();
        $usuarios = $CReorganizar->obtenerUsuarios();
        $permisos_usuario_analizar = $ClasePermisos->getPermisosUsuario($usuarios[$registro]);
        // Ahora comprobamos los permisos del usuario analizar y vemos si existe en permisos default, si no existe los eliminamos BD
        $eliminamos = 0;
        foreach ($permisos_usuario_analizar['resultado'] as $k=>$permiso){
            $valor = ''; // valor por defecto.
            if ($permiso['accion'] === ''){
                if ($permiso['vista'] === ''){
                    // Es el permiso de un modulo
                    $valor = $ClasePermisos->getModulo($permiso['modulo']);
                } else {
                    // Es el permiso de una vista
                    $valor = $ClasePermisos->getVista($permiso['vista'],$permiso['modulo']);
                }
            } else {
                // Es el permiso de una accion
                $valor = $ClasePermisos->getAccion($permiso['accion'],array( 'modulo' => $permiso['modulo'],'vista' =>$permiso['vista']));
            }
            if ($valor === ''){
                // No existe default , por debemos eliminar registro de permiso de ese usuario en Base de datos.
                $eliminamos += $ClasePermisos->borrarPermisosUsuario($permiso['idUsuario'],$permiso['id']);
            } 
        }
        // Ahora hay que comprobar en permisos default los que no existen en usuario y crearlos.
        $ClasePermisos->permisos = $permisos_usuario_analizar; // La instancia de clase permisos le ponemos los permisos por usuario analizar
        $creados = 0;
        foreach ($permisos_defecto['resultado'] as $k=>$permiso){
           $valor = ''; // valor por defecto.
            if ($permiso['accion'] === ''){
                if ($permiso['vista'] === ''){
                    // Es el permiso de un modulo
                    $valor = $ClasePermisos->getModulo($permiso['modulo']);
                } else {
                    // Es el permiso de una vista
                    $valor = $ClasePermisos->getVista($permiso['vista'],$permiso['modulo']);
                }
            } else {
                // Es el permiso de una accion
                $valor = $ClasePermisos->getAccion($permiso['accion'],array( 'modulo' => $permiso['modulo'],'vista' =>$permiso['vista']));
            }
            if ($valor === ''){
                // No existe permiso en usuario , por lo que debemos crearlo para ese usuario.
                unset($permiso['id']);
                $permiso['idUsuario'] = $usuarios[$registro]['id'];
                if ($usuarios[$registro]['group_id'] == 9){
                    // Entonces es administrador y el permiso es 1
                    $permiso['permiso']= 1;
                }
                $creados += $ClasePermisos->crearUnPermisoUsuario($permiso);
            } 
        }
        // Volvemos a poner en la instancia de permisos tiene los permisos del usuario actual.
        $ClasePermisos->permisos = $permisos_usuario_actual;   
        $resultado['usuario'] = $usuarios[$registro];
        $resultado['eliminado'] =  $eliminamos;
        $resultado['creados'] =  $creados;

        echo json_encode($resultado);

    break;

        

}

