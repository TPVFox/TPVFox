<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


//~ require_once ($URLCom.'/modulos/mod_producto/clases/ClaseProductos.php');
//~ require_once ($URLCom.'/modulos/mod_cliente/clases/claseTarifaCliente.php');
//~ require_once ($URLCom.'/controllers/Controladores.php');
// Cargamos funcion html de busar producto.
$Ccontrolador = new ControladorComun();
$CArticulo = new ClaseProductos($BDTpv);

$caja = $_POST['caja'];
$valor = $_POST['valor'];  
$idcliente = isset($_POST['idcliente']) ? $_POST['idcliente'] : 0;
$idtienda = isset($POST['idtienda']) ? $POST['idtienda'] : 1;
        
$resultado = [];

$articulos = [];
$articulos ['dedonde'] = isset($POST['idtienda']) ? $POST['idtienda'] : "tarifaCliente";
$total_productos = 0;

if ($caja) {
    switch ($caja) {
        case 'idArticulo':
			// Realmente esta mal, ya que si podemos venir de tarifaCliente o popup
			// si venimos de popup entonces debería se otra consulta (like) no la que hacemos...
            $articulo = $CArticulo->GetProducto($valor);
            $array=[];
            // Realmente esta mal la clase ya que no devuelve error si no existe id.
            if ($articulo['idArticulo'] !== null) {
                $articulos['NItems'] = 1;
                $articulos['datos'][] = array( 'idArticulo' => $articulo['idArticulo'],
                                               'articulo_name' => $articulo['articulo_name'],
                                               'cref_tienda_principal' => $articulo['cref_tienda_principal'],
                                               'iva' => $articulo['iva'],
                                               'pvpCiva' => $articulo['pvpCiva'],
                                               'pvpSiva' => $articulo['pvpSiva']
                                            );
                $array[] = $articulos['datos'][0];
			} 
			$articulos['html'] = htmlProductos($articulos['NItems'],$array,'Id','idArticulo',$valor);
			

        break;

        case 'Referencia':
            // Buscamos articulos por referencia.
            $campos= array('t.crefTienda');
            $filtro=' WHERE ('.$Ccontrolador->ConstructorLike($campos,$valor).')';
            $limite = ' LIMIT 10';
            $articulos['datos'] = $CArticulo->obtenerProductos('t.crefTienda',array('filtro'=>$filtro,'limite'=>$limite));
            
            if (count($articulos['datos'])>0) {
                foreach ($articulos['datos'] as $key=>$a){
                    $articulo= $CArticulo->GetProducto($a['idArticulo']);
                    $articulos['datos'][$key]['cref_tienda_principal'] = $articulo['cref_tienda_principal'];
                    $articulos['datos'][$key]['iva']        = $articulo['iva'];
                    $articulos['datos'][$key]['pvpCiva']    = $articulo['pvpCiva'];
                    $articulos['datos'][$key]['pvpSiva']    = $articulo['pvpSiva'];
                }
				$total_productos = count($articulos['datos']);
                $articulos['NItems'] = $total_productos;
				if ($total_productos === 10){
						$total_productos = -1 ;// Ya que realmente no se cuantos hay por el limite
				}
			}
            $articulos['html'] = htmlProductos($total_productos,$articulos['datos'],'referencia','Referencia',$valor);
        break;

        case 'Descripcion':
			// Buscamos articulos por referencia.
            $campos= array('a.articulo_name');
            $filtro=' WHERE ('.$Ccontrolador->ConstructorLike($campos,$valor).')';
            $limite = ' LIMIT 10';
            $articulos['datos'] = $CArticulo->obtenerProductos('articulo_name',compact("filtro","limite"));
            if (count($articulos['datos'])>0) {
                foreach ($articulos['datos'] as $key=>$a){
                    $articulo= $CArticulo->GetProducto($a['idArticulo']);
                    $articulos['datos'][$key]['cref_tienda_principal'] = $articulo['cref_tienda_principal'];
                    $articulos['datos'][$key]['iva']        = $articulo['iva'];
                    $articulos['datos'][$key]['pvpCiva']    = $articulo['pvpCiva'];
                    $articulos['datos'][$key]['pvpSiva']    = $articulo['pvpSiva'];
                }
				$total_productos = count($articulos['datos']);
                $articulos['NItems'] = $total_productos;

				if ($total_productos === 10){
						$total_productos = -1 ;// Ya que realmente no se cuantos hay por el limite
				}
			}
			
            $articulos['html'] = htmlProductos($total_productos,$articulos['datos'],'descripcion','Descripcion',$valor);
				
            break;

        case 'Codbarras':
            // Buscamos articulos por referencia.
            $campos= array('aCodBarras.codBarras');
            $filtro=' WHERE ('.$Ccontrolador->ConstructorLike($campos,$valor).')';
            $limite = ' LIMIT 10';
            $articulos['datos'] = $CArticulo->obtenerProductos('codBarras',compact("filtro","limite"));
            if (count($articulos['datos'])>0) {
                foreach ($articulos['datos'] as $key=>$a){
                    $articulo= $CArticulo->GetProducto($a['idArticulo']);
                    $articulos['datos'][$key]['cref_tienda_principal'] = $articulo['cref_tienda_principal'];
                    $articulos['datos'][$key]['iva']        = $articulo['iva'];
                    $articulos['datos'][$key]['pvpCiva']    = $articulo['pvpCiva'];
                    $articulos['datos'][$key]['pvpSiva']    = $articulo['pvpSiva'];
                }
				$total_productos = count($articulos['datos']);
                $articulos['NItems'] = $total_productos;

				if ($total_productos === 10){
						$total_productos = -1 ;// Ya que realmente no se cuantos hay por el limite
				}
			}
            $articulos['html'] = htmlProductos($total_productos,$articulos['datos'],'Id','Codbarras',$valor);
				
            break;

    }
    $resultado = $articulos;
    
}
