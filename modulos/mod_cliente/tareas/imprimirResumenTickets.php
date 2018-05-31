<?php 

include_once ($RutaServidor . $HostNombre."/modulos/mod_cliente/clases/ClaseCliente.php");
$Cliente= new ClaseCliente($BDTpv);
$Tienda = $_SESSION['tiendaTpv'];
$resultado['tienda']=$Tienda;
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
			}else{
				$datosCliente=$datosCliente['datos'][0];
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
				
				$cabecera='<p></p><font size="20">Super Oliva </font><br>
					<font size="12">'.$Tienda['razonsocial'].'</font><br>'.
					'<font size="12">'.$Tienda['direccion'].'</font><br>'.
					'<font size="9"><b>NIF: </b>'.$Tienda['nif'].'</font><br>'.
					'<font size="9"><b>Teléfono: </b>'.$Tienda['telefono'].'</font><br>'.
					'<font size="17">'.$textoCabecera.' número '.$numero.' con Fecha '.$fecha.'</font>'.
					'<hr>'.
					'<font size="20">'.$datosCliente['Nombre'].'</font><br>'.
					'<table><tr><td><font size="12">'.$datosCliente['razonsocial'].'</font></td>
					<td><font>Dirección de entrega :</font></td></tr>'.
					'<tr><td><font size="9"><b>NIF: </b>'.$datosCliente['nif'].'</font></td>
					<td><font size="9">'.$datosCliente['direccion'].'</font></td></tr>'.
					'<tr><td><font size="9"><b>Teléfono: </b>'.$datosCliente['telefono'].'</font></td>
					<td><font size="9">Código Postal: </font></td></tr>'.
					'<tr><td><font size="9">email: '.$datosCliente['email'].'</font></td><td></td></tr></table>'.
					'<table WIDTH="80%" border="1px"><tr>
						<td>Referencia</td>
						<td WIDTH="50%">Descripción del producto</td>
						<td>Unid/Peso</td>
						<td>Precio</td>
						<td>Importe</td>
						<td>IVA</td>
						</tr></table>';
						
						
						
			
			
			
			$cabecera=$cabecera;
			$html="";
			$nombreTmp="Resumen.pdf";
			require_once('../../lib/tcpdf/tcpdf.php');
			include ('../../clases/imprimir.php');
			include('../../controllers/planImprimir.php');
			$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
			$resultado=$ficheroCompleto;
				
				
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
