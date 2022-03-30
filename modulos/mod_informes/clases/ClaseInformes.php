<?php 
include_once $URLCom.'/clases/ClaseTFModelo.php';
include_once $URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php';
class ClaseInformes extends TFModelo{
    public $informes = array ( '1' => array('Titulo' => 'Informe de Compras por Proveedores.',
                                    'opciones'=> array
                                        (
                                            '1' => 'Todos', // Todos los provedores y albaranes (esten o no facturados.)
                                            '2' => 'Facturados',
                                            '3' => 'Sin Facturar',
                                            '4' => 'Proveedores activos'
                                        )
                                    ),
                                '2' => array('Titulo' => 'Suma compras por familias',
                                    'opciones'=> array
                                        (
                                        '1' => 'Solo Familias',
                                        '2' => 'Familias y Productos',
                                        )   
                                    )             
                                );
    public function ObtenerdatosInforme(){
        $parametros = $_GET;
        // Deberíamos obtener: 
        //      id -> indica el informe que vamos tratar
        //      Finicio y Ffinal -> Rango de fechas para calcular informe.
        //      opcion-> la opcion seleccionada por el usuario para hacer el informe.
        $id = $parametros['id'];
        if ($id == 1){
            // Añadimos parametros que pueden varias segun desde donde venga la peticion
            $parametros['filtroProveedores'] = 'todos'; // Los valores podemos enviar son 'todos','Activos','solo_ids'
                                                       // extra ultima tenemos añadir un parametros mas idsProveedor:array()
            $datos= $this->ResumenProveedores($parametros);
            $cabecera = array(
                        'id'                =>  $id,
                        'titulo_informe'    =>  $this->informes[$id]['Titulo'],
                        'Fecha_Inicio'      =>  $parametros['Finicio'],
                        'Fecha_Final'       =>  $parametros['Ffinal'],
                        'opcion'            =>  $parametros['opcion']
                    );
        }
        $respuesta = array( 'datos'=>$datos,
                            'cabecera'=>$cabecera);


        return $respuesta;
    }

    public function ResumenProveedores($parametros = array()) {
        // @Objetivo:
        // Sumas los albaranes de los proveedores y por las fechas que indiquemos en los parametros.
        // @ Parametros
        // Array (  [id] => (int) Indica el Informe que vamos hacer.
        //          [titulo_informe] => (String) Nombre del informe ,
        //          [Fecha_Inicio] => (fecha Y-m-d),
        //          [Fecha_Final] => (fecha Y-m-d),
        //          [opcion] => (int) Indica el la opcion seleccionada para realizar filtros.
        // @ Devolvemos


        $BDTpv = $this->conexionBDTPV();
		$CProveedor= new ClaseProveedor($BDTpv);
        // Tratamos parametros para añadir ids_proveedores y tratarlos
        $opcion = $parametros['opcion'];
        $id_informe = $parametros['id'];

        // Cargamos los ids de los proveedores que indicamos.
        if ($this->informes[$id_informe]['opciones'][$opcion] == 'Todos'){
            $todosProveedores = $CProveedor->obtenerProveedores();
            $ids = $this->ObtenerIdsArray($todosProveedores,'idProveedor');
        }
        // ----     Cargamos los albaranes de todos los proveedores   ------ //
        foreach ($ids as $key=>$idProveedor){
            $errores= array();
            $fechaInicial=$parametros['Finicio'];
            $fechaFinal=$parametros['Ffinal'];
            $datosProveedor=$CProveedor->getProveedor($idProveedor);          
            if(!isset($datosProveedor['datos'])){
                $errores[1]=array ( 'tipo'=>'DANGER!',
                                'dato' => $datosProveedor['consulta'],
                                'class'=>'alert alert-danger',
                                'mensaje' => 'Error al obtener datos proveedor '.$idProveedor.',No debe existir proveedor'
                                );          
            }else{
                $resumenProveedor=$CProveedor->albaranesProveedoresFechas($idProveedor, $fechaInicial, $fechaFinal);
                if(isset($resumenProveedor['error'])){
                    $errores[2]=array ( 'tipo'=>'DANGER!',
                                        'dato' => $resumenProveedor['consulta'],
                                        'class'=>'alert alert-danger',
                                        'mensaje' => 'El proveedor '.$idProveedor.' no debe tener albaranes.'
                                        );                 
                } 
            }
			if (count($errores)>0) {
                // error_log('***************************************');
                // error_log('Error en ResumenProveedores de ClaseInformes:'.json_encode($errores));
                $todosProveedores[$key]['errores'] =$resumenProveedor;

            } else {
                 // Creamos la propiedad de albaranes y cant_albaranes
                $todosProveedores[$key]['cant_albaranes'] =count($resumenProveedor);
                $todosProveedores[$key]['albaranes'] =$resumenProveedor;
               
            }
		}
        /* El codigo anterior debe mantenerse en esta clase, ya que es para Resumen de varios albaranes
         * Solo deberíamos tener en cuenta que se añadio al array de todos los albaranes la cantidad de albaranes 
         * que tiene.
         * Y ademas controlamos si no tiene albaranes ya no generamos [albaranes][productos]
         * */
        // Ahora tenemos ordenar y hacer las sumas de lineas albaranes por producto y totales por proveedor.
        $ArrayProductos = [];
        $SumaAlbaranes = [];
        $DesgloseAlbaranes = [];
        foreach ($todosProveedores as $key=>$proveedor){
            if (isset($proveedor['albaranes']['productos'])){
                $p = $CProveedor->SumaLineasAlbaranesProveedores($proveedor['albaranes']['productos']);
                // Ahora montamos array con todos los productos comprado de cada proveedor para luego sumarlos,
                // es decir tener un array con la suma de todos los productos, de todos los proveedores en el intervalo de 
                // tiempo que indicamos.
                $ArrayProductos[] = $p;
                // Ahora añadimo propiedad "cant_referencias", que indica (int) la cantidad de referencias compradas
                // en esos albaranes.
                $todosProveedores[$key]['referencias_productos'] = count($p);
                // Ahora sumar el desglose .
                
                
                //Bloqueo para debug
                if ($proveedor['idProveedor']== "158" || $proveedor['idProveedor']== "1" ) {
                    error_log( 'Bloqueo Debug de solo este '.$proveedor['idProveedor']);
                    $SumaAlbaranes[] = $proveedor['albaranes']['resumenBases'];
                    $SumaAlbaranes[] = $todosProveedores[$key];

                    $DesgloseAlbaranes[] = $proveedor['albaranes']['resumenBases'];
                }
            }
        }
        // Esto es necesario ya que tenemos varios array con productos, uno por cada Proveedor.
        /* Queda pendiente sumar los albaranes y los desglose.
         * y ver como controlar cuando queremos filtrar algun proveedor o albaran no facturado.
         * Aquí en el proceso anterior, añadimos [referencias_productos]
         * */
        $Productos =[];
        foreach ($ArrayProductos as $P){
            foreach ( $P as $producto){
                $Productos[] = $producto;
            }
        } 
        // Ahora sumamos todos los productos ( deberíamos controlar si hay mas de un proveedor), ya que no tiene sentido, si es uno

        $Productos = $this->SumaProductosTodosProveedores($Productos);
        // Ahora tenemos sumar los totales de albaranes.




        // Montamos lo que devolvemos..
        // Hay que tener en cuenta que la memoria es limitada, por a lo mejor sería bueno devolver solo informe , no los datos, deberíamos
        // utilizar uset si lo quisieramos hacer.
        // ya todosProveedores no hacen falta para obtener resto datos :
        // Nombre
        // idProveedor
        // cant_albaranes  ( este dato en esta metodo)
        $respuesta = array(
                        'datos'     => $todosProveedores,
        
                        'informe'   => array(
                                'productos'         => $Productos,
                                'suma_albaranes'    => $SumaAlbaranes,
                                'suma_desgloseIvas' => $DesgloseAlbaranes                      
                            )
                    );
        return $respuesta;
    }

    /* public function SumaLineasAlbaranesProveedores($LineasProductos,$quitarIdArticulo = 'OK') {
        // NOTA: Realmente este metodo debería esta en Clase de Provedores, ya que es ahí donde ya lo ejecutamos.
        // @ Objetivo
        // Obtener un array con la suma de productos comprados con su precio coste medio de unos albaranes determinado.
        // @ Parametros:
        // $LineasProductos -> Es un array que tiene que trae :
        //          - idArticulo
        //          - costeSiva
        //          - totalUnidades
        // $quitarIdArticulo -> (string)- >'OK  para indicar si devolvemos array con key = IdArticulo o 'KO' pone autonumerico.
        // @ Devolvemos:
        //  El mismo array , cambiando:
        //       costeSiva=  cambia por coste medio de todas lineas del mismo producto.
        //       totalUnidades = cambia por la suma de todas la cantidad unidades de todas las lineas
        //  y añadiendo:
        //       num_comprados = Indica la cantidad lineas que había de ese mismo producto.
        //       coste_medio = Es un string que con 'KO' o 'OK' que indica si se calculo coste medio o no.
       
        $totalProductos=0;
        $totalLineas = 0;

        $Productos = []; // inicializa tabla que aparece como resumen productos
        foreach ($LineasProductos as $producto) {			
            $id_producto = $producto['idArticulo'];
            if(array_key_exists($id_producto, $Productos) == false){ // busca el indice. Si no existe lo crea con $producto
                $Productos[$id_producto] = $producto;
                $Productos[$id_producto]['costeSiva'] = $producto['costeSiva'];
                $Productos[$id_producto]['coste_medio'] = 'KO';
                $Productos[$id_producto]['totalUnidades'] = $producto['totalUnidades'];
                $Productos[$id_producto]['num_compras'] = 1;
            } else {  // Si ya existe suma las unidades y calcula el precio medio
                $total_producto = $producto['totalUnidades'] * $producto['costeSiva'];  
                if($Productos[$id_producto]['costeSiva'] !== $producto['costeSiva']){
                    $Productos[$id_producto]['coste_medio'] = 'OK';
                    $suma = $Productos[$id_producto]['totalUnidades'] + $producto['totalUnidades'];
                    if ( $suma != 0){
                        $Productos[$id_producto]['costeSiva'] = ($Productos[$id_producto]['total_linea'] + $total_producto) / $suma;
                    }
                }				
                $Productos[$id_producto]['totalUnidades'] += $producto['totalUnidades'];
                $Productos[$id_producto]['num_compras'] += 1;
            }
            $Productos[$id_producto]['total_linea'] = $Productos[$id_producto]['totalUnidades'] * $Productos[$id_producto]['costeSiva'];
        }
        // Una vez terminado, Volvemos a recorrer el array para quitar indice que pusimos como el idArticulo,
        // esto podría se opcional, ya que si queremos utilizar el array para añadir mas datos, puede ser interesante 
        // poder recibirlo asi , o no.
        $respuesta = [];
        if ($quitarIdArticulo == 'OK'){
            foreach ($Productos  as $producto){
                $respuesta[] = $producto;
            }
        } else {
            $respuesta = $Productos;
        }
        return $respuesta;

    } */


    public function SumaProductosTodosProveedores($LineasProductos) {
        // @ Objetivo
        // Obtener un array con la suma de productos comprados con su precio coste medio del array que recibimos.
        // @ Parametros:
        // $productos -> Es un array que puede trae :Array
        //(
        //    [idalbpro] => int
        //    [idArticulo] => int
        //    [totalUnidades] => float
        //    [costeSiva] =>float
        //    [coste_medio] => float
        //    [num_compras] => int
        //    [total_linea] => float
        //)
        
       
        $totalProductos=0;
        $totalLineas = 0;
        /* $cdetalleArray = $this->ObtenerIdsArray($LineasProductos,'cdetalle');
        array_multisort($cdetalleArray, SORT_ASC, $LineasProductos); */

        $Productos = []; // inicializa tabla que aparece como resumen productos
        foreach ($LineasProductos as $producto) {			
            $id_producto = $producto['idArticulo'];
            // Eliminamos propiedad de idalbpro ya que no es necesario.
            unset($producto['idalbpro']);
            if(array_key_exists($id_producto, $Productos) == false){ // busca el indice. Si no existe lo crea con $producto
                $Productos[$id_producto] = $producto;
                $Productos[$id_producto]['costeSiva'] = $producto['costeSiva'];
                $Productos[$id_producto]['coste_medio'] = 'KO';
                if ($producto['coste_medio'] === 'OK'){
                    $Productos[$id_producto]['coste_medio'] = 'OK';
                }
                $Productos[$id_producto]['totalUnidades'] = $producto['totalUnidades'];
                $Productos[$id_producto]['num_compras'] = 1;
                if ($producto['num_compras'] > 0) {
                    $Productos[$id_producto]['num_compras'] =$producto['num_compras'];
                }
            } else {  // Si ya existe suma las unidades y calcula el precio medio
                $total_producto = $producto['totalUnidades'] * $producto['costeSiva'];  
                if($Productos[$id_producto]['costeSiva'] !== $producto['costeSiva']){
                    $Productos[$id_producto]['coste_medio'] = 'OK';
                    $suma = $Productos[$id_producto]['totalUnidades'] + $producto['totalUnidades'];
                    if ( $suma != 0){
                        $Productos[$id_producto]['costeSiva'] = ($Productos[$id_producto]['total_linea'] + $total_producto) / $suma;
                    }
                }				
                $Productos[$id_producto]['totalUnidades'] += $producto['totalUnidades'];
                if ($producto['num_compras'] > 0) {
                    $Productos[$id_producto]['num_compras'] =  $Productos[$id_producto]['num_compras'] + $producto['num_compras'];
                } else {
                    $Productos[$id_producto]['num_compras'] += 1;
                }
            }
            $Productos[$id_producto]['total_linea'] = $Productos[$id_producto]['totalUnidades'] * $Productos[$id_producto]['costeSiva'];
        }
        // Una vez terminado, Volvemos a recorrer el array para quitar indice que pusimos como el idArticulo.
        $respuesta = [];
        foreach ($Productos  as $producto){
            $respuesta[] = $producto;
        }
        return $respuesta;

    }

    public function ObtenerIdsArray($datos,$campo){
        // @ Objetivo 
        // Obtener un array con los datos de un campo determinado.
        $valores = [];
        foreach ($datos as $dato){
            $valores[] = $dato[$campo];
        }
        return $valores;

    }

    public function OpcionesInformes($id,$opcion){
        // @ Objetivo:
        // Obtener datos necesarios para realizar los filtros necesarios, segun el informe y opcion recibidad.
        // @ Parametros:
        //  $id = int()
        //  $opcion = int()
        // @ Devolvemos
        // Devolvemos un array con los datos necesarios para poder realizar el filtro.

        // Creamos array con todos los informes y opciones posibles,
        
        return $respuesta;
    }


}

?>
