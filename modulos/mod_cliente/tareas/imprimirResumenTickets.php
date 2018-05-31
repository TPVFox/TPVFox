<?php 

include_once ($RutaServidor . $HostNombre."/modulos/mod_cliente/clases/ClaseCliente.php");
$Cliente= new ClaseCliente($BDTpv);

$idCliente=$_POST['idCliente'];
$fechaInicial=$_POST['fechaInicial'];
$fechaFinal=$_POST['fechaFinal'];
$errores=array();
if(isset($idCliente)){
			$datosCliente=$Cliente->getCliente($idCliente);
			if(isset($datosCliente['error'])){
				 $resultado['error']=array ( 'tipo'=>'DANGER!',
								 'dato' => $datosCliente['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error en sql'
								 );
			}
		}else{
			$resultado['error']=array ( 'tipo'=>'DANGER!',
								 'dato' => '',
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error no se ha enviado el id del cliente'
								 );
		}
if(isset($_POST['fechaInicial']) & isset($_POST['fechaFinal'])){
			$fechaIni=$_POST['fechaInicial'];
			$fechaFin=$_POST['fechaFinal'];
			if($fechaIni<>"" & $fechaFin<>""){
				$fechaInicial =date_format(date_create($fechaIni), 'Y-m-d');
				$fechaFinal =date_format(date_create($fechaFin), 'Y-m-d');
			}
			$resultado['post']=$fechaInicial;
			$arrayNums=$Cliente->ticketClienteFechas($idCliente, $fechaInicial, $fechaFinal);
			if(isset($arrayNums['error'])){
				$resultado['error']=array ( 'tipo'=>'DANGER!',
								 'dato' => $arrayNums['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error de sql'
								 );
			}else{
				$resultado['array']=$arrayNums;
				//Aquí meter el html para imprimir
			}
		}else{
			$resultado['error']=array ( 'tipo'=>'DANGER!',
								 'dato' => '',
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error no se han enviado corectamente las fechas'
								 );
			
		}
?>
