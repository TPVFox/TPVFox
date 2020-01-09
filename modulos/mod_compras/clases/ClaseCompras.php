<?php
// Clase base para modulo de compras.
include_once $URLCom.'/clases/articulos.php';

class ClaseCompras
{
	public $db; //(Objeto) Es la conexion;
	public $affected_rows; // Numero filas que afecto la consulta, se guarda cuando hacemos una consulta.
    public $insert_id; // id del registro insertado. ( ojo.. como sabe que el campo es id)
	public function consulta($sql){
		// Realizamos la consulta.
		$db = $this->db;
		$smt = $db->query($sql);
		if ($smt) {
			$respuesta = $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
		}
        // Guardamos la filas afectadas.
        $this->affected_rows = $db->affected_rows;
        $this->insert_id = $db->insert_id;
        return $respuesta;
	}
     
	public function __construct($conexion){
		$this->db = $conexion;
	}
	
	public function htmlPendientes(){
		
	}
	public function sumarIvaBases($from_where){
		//Función para sumar los ivas de un pedido
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase '.$from_where);
		if ($result = $smt->fetch_assoc () ){
			$sumaIvasBases=$result;
		}
		return $sumaIvasBases;
	}

    public function deleteRegistrosTabla($tabla,$where=''){
        //@ Objetivo:
        // Eliminar registros de una tabla y si indicamos where, pues eliminamos solo los registros de la condicion,.
        //@ Parametros:
        //   $tabla-> (string) nombre de la tabla.
        //   $where-> (string) completo where y lo queramos indicar..
        $sql = 'DELETE FROM '.$tabla.' '.$where;
        $smt=$this->consulta($sql);
        return $smt;

    }
    
	public function SelectUnResult($tabla, $where){
		$db=$this->db;
		$sql='SELECT * from '.$tabla.' where '.$where;
		$smt=$this->consulta($sql);
        $resultado  = array();
        if (gettype($smt)==='array'){
			$resultado['error']=$smt['error'];
			$resultado['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$resultado=$result;
			}
		}
        return $resultado;
	}
    
	public function SelectVariosResult($tabla, $where){
        $respuesta = array();
        $db=$this->db;
		$sql='SELECT * from '.$tabla.' where '.$where;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
            // Devuelve array con error y consulta
			$respuesta=$smt;
		}else{
			while ( $result = $smt->fetch_assoc () ) {
				array_push($respuesta,$result);
			}
		}
        return $respuesta;
	}

    public function recalculoTotales($productos,$campo_estado = 'estado') {
        // @ Objetivo
        //  Recalcular los totales y desglose de los productos recibidos
        // @ Parametro:
        // 	$productos (array) de objetos.
        //  $campo_estado -> (string) por compatibilidad de versiones anteriores
        $respuesta = array();
        $desglose = array();
        $subivas = 0;
        $subtotal = 0;
        foreach ($productos as $product){
            // Comprobamos que producto es un objeto
            if ( gettype($product) !== 'object' ){
                // Lo convertimos en objeto, por si recibimos array.
                $product = (object)$product;
            }
            if ($product->$campo_estado === 'Activo'){
                // Solo se añade calcula los que el estado sea Activo.
                $iva = $product->iva;
                $iva_decimal = $product->iva/100; // No hace falta para operar.
                if (!isset($product->importe)){
                    // Por comtabilidad con versiones anterires.
                    $importe = $product->ncant*$product->ultimoCoste;
                } else {
                    $importe = $product->importe;
                }
                if (isset($desglose[$iva])){
                    $desglose[$iva]['base'] = number_format($desglose[$iva]['base'] + $importe,3, '.', '');
                    $desglose[$iva]['iva'] = number_format($desglose[$iva]['iva']+ ($importe*$iva_decimal),3, '.', '');
                }else{
                    $desglose[$iva]['base'] = number_format((float)$importe,3, '.', '');
                    $desglose[$iva]['iva'] = number_format((float)$importe*$iva_decimal, 3, '.', '');
                }
                $desglose[$iva]['BaseYiva'] = number_format((float)$desglose[$iva]['base']+$desglose[$iva]['iva'], 3, '.', '');	
            }			
        }
        foreach($desglose as $tipoIva=>$des){
            $subivas = $subivas+$desglose[$tipoIva]['iva'];
            $subtotal = $subtotal +$desglose[$tipoIva]['BaseYiva'];
        }	
        $respuesta['desglose'] = $desglose;
        $respuesta['subivas'] = $subivas;
        $respuesta['total'] = $subtotal;
        return $respuesta;
    }

    public function comprobarHistoricoCoste($productos, $dedonde, $numDoc,  $idProveedor, $fecha, $idUsuario){
        // Objetivo:
        //  Añade coste de ese articulo de ese proveedor.
        //  Añado a historico costes si fuera necesario,ver NOTA ***
        // [NOTA ***]
        // Se añade registro a tabla historico_precios, cuando al buscar el producto en la tabla articulosProveedores de ese
        // proveedor  la fecha actualizacion es menos a la fecha que traemos como parametro.
        $errores=array();
        $BDTpv= $this->db;
        $CArt=new Articulos($BDTpv);
        $datos=array(
                    'dedonde'=>$dedonde,
                    'numDoc'=>$numDoc,
                    'tipo'=>"compras"
                );
        $productos = json_decode($productos, true);
        if (count($productos)>0){
            foreach ($productos as $producto){
                $buscar=$CArt->buscarReferencia($producto['idArticulo'], $idProveedor);
                if (isset($buscar['error'])){
                        array_push($errores,$this->montarAdvertencia('danger',
                                            'Error en $CArt->buscarReferencia. <br>'
                                            .$buscar['error'].'<br>'
                                            .$buscar['consulta']
                                            )
                                );
                }else{
                    if (isset($producto['CosteAnt'])){
                        // Cuando existe precios coste anterior, se modifica coste en articulosproveedor
                        // pero solo si la fechaActualizacion es menor a fecha (parametro)
                        $datosNuevos=array(
                            'coste'=>$producto['ultimoCoste'],
                            'idArticulo'=>$producto['idArticulo'],
                            'idProveedor'=>$idProveedor,
                            'fecha'=>$fecha,
                            'estado'=>"activo"
                        );
                        if (isset($buscar['fechaActualizacion'])){
                            if ($buscar['fechaActualizacion'] > $fecha){
                                array_push($errores,$this->montarAdvertencia('warning',
                                            'La fecha de la tabla articulos proveedor es mayor que la del producto'
                                            .$producto['idArticulo']
                                            )
                                );
                            }else{
                                $mod=$CArt->modificarCosteProveedorArticulo($datosNuevos);
                                if (isset($mod['error'])){
                                    array_push($errores,$this->montarAdvertencia('danger',
                                            'Error en $CAart->modificarCosteProveedorArticulo. <br/>'
                                            .$mod['error'].'<br/>'
                                            .$mod['consulta']
                                            )
                                    );
                                }
                            }
                        }
                        if ( count($errores) > 0 ) {
                            // Solo añado al historico si no hay errores.
                            $datos['idArticulo']=$producto['idArticulo'];
                            $datos['antes']=$producto['CosteAnt'];
                            $datos['nuevo']=$producto['ultimoCoste'];
                            $datos['estado']="Pendiente";
                            $datos['idUsuario']=$idUsuario;
                            $nuevoHistorico=$CArt->addHistorico($datos);
                            if (isset($nuevoHistorico['error'])){
                                array_push($errores,$this->montarAdvertencia('danger',
                                                'Error en $CArt->addHistorico. <br/>'
                                                .$nuevoHistorico['error'].'<br/>'
                                                .$nuevoHistorico['consulta']
                                                )
                                        );
                            }
                        }
                    }else{
                        // Cuando no existe en tabla articulosProveedores el producto para ese proveedor.
                        // [PENDIENTE]
                        // Pienso que debemos añadir al historico tambien ese precio, para revisarlo.
                        if (!isset($buscar['idArticulo'])){
                            $datosNuevos=array(
                                'coste'=>$producto['ultimoCoste'],
                                'idArticulo'=>$producto['idArticulo'],
                                'idProveedor'=>$idProveedor,
                                'fecha'=>$fecha,
                                'estado'=>"activo",
                                'refProveedor'=>""
                            );	
                            $add=$CArt->addArticulosProveedores($datosNuevos);
                            if (isset($add['error'])){
                                array_push($errores,$this->montarAdvertencia('danger',
                                            'Error en $CArt->addHistorico. <br/>'
                                            .$add['error'].'<br/>'
                                            .$add['consulta']
                                            )
                                    );
                            }
                        }
                    }
                }
            }
        }else{
            array_push($errores,$this->montarAdvertencia('danger',
                                            'Error no tiene productos'
                                            )
                                    );
        }
        return $errores;
    }



    // ------------------- METODOS COMUNES ----------------------  //
    // -  Al final de cada clase suelo poner aquellos metodos   -  //
    // - que considero que puede ser añadimos algun controlador -  //
    // - comun del core, ya que pienso son necesarios para mas  -  //
    // - modulos.                                                  //
    // ----------------------------------------------------------  //

    public function montarAdvertencia($tipo,$mensaje,$html='KO'){
        // @ Objetivo:
        // Montar array para error/advertencia , tb podemos devolver el html
        // @ Parametros
        //  $tipo -> (string) Indica tipo error/advertencia puede ser : danger,warning,success y info
        //  $mensaje -> puede ser string o array. Este ultimos es comodo por ejemplo en las cosultas.
        //  $html -> (string) Indicamos si queremos que devuelva html en vez del array.
        // @ Devolvemos
        //  Array ( tipo, mensaje ) o html con advertencia o error.
        $advertencia = array ( 'tipo'    =>$tipo,
                          'mensaje' => $mensaje
                        );
        if ($html === 'OK'){
            $advertencia = '<div class="alert alert-'.$tipo.'">'
                          . '<strong>'.$tipo.' </strong><br/> ';
                    if (is_array($mensaje)){
                        $p = print_r($mensaje,TRUE);
                        $advertencia .= '<pre>'.$p.'</pre>';
                    } else {
                        $advertencia .= $mensaje;
                    }
                    $advertencia .= '</div>';

        }
                        
        return $advertencia;
    }
    
}
?>
