<?php 
include_once './../../inicial.php';

$pulsado = $_POST['pulsado'];
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_proveedor/funciones.php';
include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
include_once ($URLCom ."/modulos/mod_proveedor/clases/ClaseProveedor.php");
include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
$NCArticulo = new ClaseProductos($BDTpv);
$CProveedor= new ClaseProveedor($BDTpv);
$CIncidencia=new ClaseIncidencia($BDTpv);
$respuesta=array();
switch ($pulsado) {
	case 'abririncidencia':
		$dedonde=$_POST['dedonde'];
		$usuario=$_POST['usuario'];
		$idReal=0;
		if(isset($_POST['idReal'])){
			$idReal=$_POST['idReal'];
		}
		
		$configuracion=$_POST['configuracion'];
		$numInicidencia=0;
		$tipo="mod_proveedor";
		$datos=array(
		'vista'=>$dedonde,
		'idReal'=>$idReal
		);
		$datos=json_encode($datos);
		$estado="No resuelto";
		$html=$CIncidencia->htmlModalIncidencia($datos, $dedonde, $configuracion, $estado, $numIncidencia);
		$respuesta['html']=$html;
		$respuesta['datos']=$datos;
	
	break;
	
	case 'nuevaIncidencia':
		$usuario= $_POST['usuario'];
		$fecha= $_POST['fecha'];
		$datos= $_POST['datos'];
	
		$dedonde="mod_proveedores";
		$estado= $_POST['estado'];
		$mensaje= $_POST['mensaje'];
		$usuarioSelect=0;
		if(isset($_POST['usuarioSelec'])){
		$usuarioSelect=$_POST['usuarioSelec'];
		}
		if($usuarioSelect>0){
			$datos=json_decode($datos);
			
			$datos->usuarioSelec=$usuarioSelect;
			$datos=json_encode($datos);
		}
		$numInicidencia=0;
		if($mensaje){
			$nuevo=$CIncidencia->addIncidencia($dedonde, $datos, $mensaje, $estado, $numInicidencia);
			$respuesta=$nuevo;
		}
	
	
	break;
	case 'imprimirResumenAlbaran':
        include_once $URLCom.'/modulos/mod_proveedor/Tareas/imprimirResumenAlbaran.php';
		$respuesta=$resultado;
	break;
    case 'imprimirListadoProductos':
       
        include_once $URLCom.'/modulos/mod_proveedor/Tareas/imprimirListadoProductos.php';
		$respuesta=$resultado;
    break;

	case 'obtenerEstadoProductoWeb';
		// Objetivo es obtener el estado de los productos que enviemos a la web.
		// @ Parametros:
		//      ids_productos = (array) ids de la los productos de tpv.
		//      id_web = (int) con el id de la tienda web.
		$ids_productos  = $_POST['ids_productos'];
		$id_tiendaWeb   = $_POST['id_tiendaWeb'];
		// @ Devolvemos:
		// array con los ids_productos y si estado
		$ObjVirtuemart = $NCArticulo->SetPlugin('ClaseVirtuemart');         // Creo el objeto de plugin Virtuemart.
		// Lo  ideal sería mandar solo una petición ya que así no saturamos la red...
		// pero de momento lo dejo..
		foreach ($ids_productos as $key=>$idProducto){
			$producto=$NCArticulo->GetProducto($idProducto);
			$idVirtuemart = 0;
			foreach ($producto['ref_tiendas'] as $ref){
				// Debemos comprobar que es la referencia de la tienda web.. FALTA
				if ($ref['idVirtuemart'] >0) {
					$idVirtuemart = $ref['idVirtuemart'];
					// Creamos fecha_modificado con 1 hora mas, para que controlar si hacemos consulta
					$f = '000-00-00 00:00';
					if ($ref['fechaModificacion'] != null){
						$f = $ref['fechaModificacion'];
					}
					$fecha_modificado = new DateTime($f);
					$fecha_modificado->modify('+1 hours');
					$ahora =new DateTime();

					$estado =$ref['estado'];
					
				}
			}
		
			if ($idVirtuemart > 0) {
				if ($ahora > $fecha_modificado ){
					$datosWebCompletos=$ObjVirtuemart->datosCompletosTiendaWeb($idVirtuemart,$producto['iva'],$producto['idArticulo'],$id_tiendaWeb);
					if (isset($datosWebCompletos['datosWeb']['estado'])) {
						$estado = $datosWebCompletos['datosWeb']['estado'];
						// Ahora guardamos estado tienda en local para que podamos contralar si consultamos o no nuevamente.    
						$cambiarEstado=$NCArticulo->modificarEstadoWeb($producto['idArticulo'], $datosWebCompletos['datosWeb']['estado'], $id_tiendaWeb);
					} else {
						$estado ='Error';
					}

					
			} else {
				
					if ($estado == 'Publicado') {
						$estado = '1';
					} else {
						$estado = '0';
					}
				}
			} else {
					$estado ='NoExiste';
			}
			$respuesta[$key]= array(
					'estado'=> $estado,
					'idArticulo' => $idProducto
					);
			
			
		}
	break;






}
echo json_encode($respuesta);
return $respuesta;
?>
