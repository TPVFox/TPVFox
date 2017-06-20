
<?php
/*	
 * */


class Recambio
{
	function ObtenerRecambios($BDRecambios,$LimitePagina ,$desde,$filtro)
    {
		$recambios = array();
		$rango= '';
		if ($LimitePagina > 0 ){
			$rango .= " LIMIT ".$LimitePagina." OFFSET ".$desde;
		} 
		$consulta = "Select * from RecambiosTemporal ".$filtro.$rango;
		$ResRecambios = $BDRecambios->query($consulta);
		$recambios['NItems'] = $ResRecambios->num_rows;
		$i = 0;
		
		while ($recambio = $ResRecambios->fetch_assoc()) {
			$recambios['items'][$i]['id']= $recambio['id'];
			$recambios['items'][$i]['Descripcion']= $recambio['Descripcion'];
			$recambios['items'][$i]['coste']= $recambio['coste'];
			$recambios['items'][$i]['margen']= $recambio['margen'];
			$recambios['items'][$i]['pvp']= $recambio['pvp'];
			$recambios['items'][$i]['IDFabricante']= $recambio['IDFabricante'];
			// No siempre existe ... 
			if ( isset($recambio['RefFabricanteCru']) ) {
			$recambios['items'][$i]['RefFabricanteCru']= $recambio['RefFabricanteCru'];
			}
			$consulta1 = 'SELECT virtuemart_product_id,published FROM `virtuemart_products` WHERE ltrim(`product_sku`)="'.$recambio['id'].'" and ltrim(`product_gtin`)="'.$recambio['RefFabricanteCru'].'"';
			
			$IDVirtuemart = $BDRecambios->query($consulta1);
			$NumItems = $IDVirtuemart->num_rows;
			if ($IDVirtuemart->num_rows ==1) {
				while ($row = $IDVirtuemart->fetch_assoc()) {
					$recambios['items'][$i]['IDWeb']=  $row['virtuemart_product_id'] ;
					$recambios['items'][$i]['publicada']=  $row['published'] ;

				}
			} else {
				if ($IDVirtuemart->num_rows >1){
					$recambios['items'][$i]['IDWeb']='Error';
				}
			}
			
			$recambios['items'][$i]['Consulta'] = $consulta1;
			$recambios['items'][$i]['NumItems'] = $NumItems;

			$i = $i+1;
		}
		$recambios ['consulta'] = $consulta;
		return $recambios;
    }
    
    
    
    function CrearVistaRecambios($BDRecambios,$nombreVista)
    {
		$recambios = array();
		$consulta = "CREATE or REPLACE VIEW ".$nombreVista." AS SELECT R.id, Descripcion, coste, margen, pvp, IDFabricante, RC.RefFabricanteCru FROM recambios R JOIN referenciascruzadas RC ON RC.RecambioID = R.id";
		
		
		//~ 
		//~ $consulta = "CREATE or REPLACE VIEW ".$nombreVista." AS SELECT R.id, Descripcion, coste, margen, pvp, IDFabricante, RC.RefFabricanteCru, VP.virtuemart_product_id FROM recambios R JOIN referenciascruzadas RC ON RC.RecambioID = R.id LEFT JOIN virtuemart_products VP ON VP.product_sku = R.id ";
        
        //~ $consulta = "CREATE or REPLACE VIEW ".$nombreVista." AS SELECT R.id, Descripcion, coste, margen, pvp, IDFabricante, RC.RefFabricanteCru, VP.virtuemart_product_id FROM recambios R JOIN virtuemart_products VP ON VP.product_sku = R.id LEFT JOIN referenciascruzadas RC ON RC.RecambioID = R.id";
        
        
		$ResRecambios = $BDRecambios->query($consulta);
		 if ($ResRecambios == true){
			$recambios['conexion'] = 'Correcto';
			} else {
			$recambios['conexion'] = 'Error ';
			$recambios['error'] = mysqli_error($BDRecambios);
				
			return $recambios;
			// No continuamos..
		}
		$recambios['consulta'] = $consulta;

		return $recambios ;
    }
    
    function BusquedaIDUnico($BDRecambios,$id,$tabla)
    {
		$consulta = 'SELECT * FROM `'.$tabla.'` WHERE '.$id;
		$Resultado = $BDRecambios->query($consulta);
		 if ($Resultado == true){
			$resultado['conexion'] = 'Correcto,consulta todas familias';
			} else {
			$resultado['conexion'] = 'Error '.mysqli_error($BDRecambios);	 
			return $resultado;
			// No continuamos..
		}
		return $Resultado ;
    }
    
    function UnicoRegistro ($BDRecambios,$id,$tabla) {
		$consulta = 'SELECT * FROM `'. $tabla.'` WHERE '.$id;
		$QueryUnico = $BDRecambios->query($consulta);
		if (mysqli_error($BDRecambios)) {
			$fila = $QueryUnico;
		} else {
			$fila = $QueryUnico->fetch_assoc();
		}
		$fila['consulta'] = $consulta;
		return $fila ;
	}
    
    function CrucesVehiculos($BDVehiculos,$idVersiones) {
		$resultado = array();
		$LidVersiones = implode(',',$idVersiones);
		$ConsultaMultitabla= "SELECT v.`id`,v.`idMarca`,Marca.nombre as Nmarca, v.`idModelo`,m.nombre as Nmodelo, v.`nombre` as Nversion, v.`idTipo`, v.`idCombustible`,c.nombre as Ncombustible, v.`fecha_inicial`, v.`fecha_final`, v.`kw`, v.`cv`, v.`cm3`, v.`ncilindros` 
FROM (((`vehiculo_versiones` as v LEFT JOIN `vehiculo_modelos` as m ON v.idModelo = m.`id`) LEFT JOIN `vehiculo_marcas` as Marca ON v.idMarca = Marca.id) LEFT JOIN `vehiculo_combustibles` as c ON c.id = v.idCombustible) WHERE v.id in (".$LidVersiones.") order by Marca.nombre,m.nombre,v.nombre,v.fecha_inicial ASC";
		$resultados = $BDVehiculos->query($ConsultaMultitabla);
		if ($resultados){
			while ($vehiculo = $resultados->fetch_assoc()) {
				$resultado[] = $vehiculo;
			}
		} else {
			$resultado['consulta'] = $ConsultaMultitabla;
			$resultado['Error'] = 'Error en consulta o no existe cruce';
		}
		
		
		
		
		return $resultado ;
	}
	
	
}


?>
