<?php
//@Objetivo:
			//Insertar un nuevo importe a una factura
			//@Proceso:
			//Primero se buscan los importes que tiene ya esa factura, si tiene elimina el registro
			 $importe=$_POST['importe'];
			 $fecha=$_POST['fecha'];
			 $idFactura=$_POST['idTemporal'];
			 $formaPago=$_POST['forma'];
			 $referencia=$_POST['referencia'];
			 $total=$_POST['total'];
			 $idReal=$_POST['idReal'];
			 $arrayPrincipal=array();
			 $error=0;
			 $bandera=$importe;
			 $respuesta=array();
			 $importesReal=array();
			 $importesReal=$CFac->importesFactura($idReal);
			 if (isset($importesReal['error'])){
				$respuesta['error']=$importesReal['error'];
				$respuesta['consulta']=$importesReal['consulta'];
			}
			 $respuesta['importeReal']=$importesReal;
			 if(count($importesReal)>0){
				$importesReal=modificarArraysImportes($importesReal, $total);
				$importesTemporal=json_encode($importesReal);
				$eliminarReal=$CFac->eliminarRealImportes($idReal);
				if (isset($eliminarReal['error'])){
					$respuesta['error']=$eliminarReal['error'];
					$respuesta['consulta']=$eliminarReal['consulta'];
				}
				$respuesta['impTemporal']=$importesTemporal;
			 }else{
				 $importesTemporal=$CFac->importesTemporal($idFactura);
				if (isset($importesTemporal['error'])){
					$respuesta['error']=$importesTemporal['error'];
					$respuesta['consulta']=$importesTemporal['consulta'];
				}else{
				 $importesTemporal=$importesTemporal['FacCobros'];
				 $bandera=$importe;
				}
			 }
			 if ($importesTemporal){
				$importes=json_decode($importesTemporal, true);
				$respuesta['importes']= $importes;
				 foreach ($importes as $import){
					 $bandera=$bandera+(string)$import['importe'];
					 array_push($arrayPrincipal, $import);
				 }
				 if ($bandera>$total){
					 $respuesta['mensaje']=1;
					 $error=1;
				 }
				 $respuesta['bandera']=$bandera;
			 }
			 if ($error==0){
				$pendiente=$total-$bandera;
				$nuevo=array();
				$nuevo['importe']=$importe;
				$nuevo['fecha']=$fecha;
				$nuevo['forma']=$formaPago;
				$nuevo['referencia']=$referencia;
				$nuevo['pendiente']=$pendiente;
				$respuesta['nuevo']=$nuevo;
				array_push($arrayPrincipal, $nuevo);
				$jsonImporte=json_encode($arrayPrincipal);
				$modImportes=$CFac->modificarImportesTemporal($idFactura, $jsonImporte);
				if (isset($modImportes['error'])){
					$respuesta['error']=$modImportes['error'];
					$respuesta['consulta']=$modImportes['consulta'];
				}
				$html=htmlImporteFactura($nuevo, $BDTpv);
				$respuesta['html']=$html['html'];
			}
?>
