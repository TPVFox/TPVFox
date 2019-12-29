<?php
// Clase base para modulo de compras.

class ClaseCompras
{
	public $db; //(Objeto) Es la conexion;
	public $affected_rows; // Propiedad que guardamos cuando hacemos una consulta, para indicar filas afectadas.
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
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$resultado=$result;
			}
			return $resultado;
		}
	}
    
	public function SelectVariosResult($tabla, $where){
        $respuesta = array();
        $db=$this->db;
		$sql='SELECT * from '.$tabla.' where '.$where;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
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


    
}
?>
