<?php 

function modalIncidencia($usuario, $datos, $fecha, $dedonde, $estado, $numIncidencia, $configuracion, $BDTpv){
	$select="Si";
	$usuDeft=1;
	foreach($configuracion as $config){
		switch($config['descripcion']){
			case 'mostrar_Select':
				$select=$config['valor'];
			case 'usuario_Defecto':
				$usuDeft=$config['valor'];
		}
	}
	if ($usuDeft>=0){
		$sql='select * from usuarios';
		$smt = $BDTpv->query($sql);
		$usuariosSelect=array();
				while ( $result = $smt->fetch_assoc () ) {
					array_push($usuariosSelect,$result);
				}
	}
	$datosPrincipales=json_decode($datos);
$html="";
$html.='<div class="col-md-12">';
$html.='<div class="col-md-6">';
$html.='<label>Usuario:</label>';
$html.='<input type="text" name="inci_usuario" id="inci_usuario" value="'.$usuario.'" readonly="">';
$html.='</div>';
$html.='<div class="col-md-6">';
$html.='<label>Fecha:</label>';
$html.='<input type="date" name="inci_fecha" id="inci_fecha" value="'.$fecha.'" readonly="">';
$html.='</div>';
$html.='</div>';
$html.='<div class="col-md-12">';
$html.='<div class="col-md-6">';
$html.='<label>Dedonde:</label>';
$html.='<input type="text" name="inci_dedonde" id="inci_dedonde" value="'.$dedonde.'" readonly="">';
$html.='</div>';
$html.='<div class="col-md-6">';
$html.='<label>Estado:</label>';
if ($datosPrincipales->dedonde=="incidencia"){
	$html.='<select name="inci_estado" id="inci_estado">';
		$html.='<option value=0 selected>No resuelto</option>';
		$html.='<option value=1 selected>Resuelto</option>';
		$html.='<option value=2 selected>Pendiente</option>';
	$html.='</select>';
	
}else{
$html.='<input type="text" name="inci_estado" id="inci_estado" value="'.$estado.'" readonly="">';
}
$html.='</div>';
$html.='</div>';
$html.='<div class="col-md-12">';
$html.='<label>Mensaje:</label>';
$html.='<input type="text" name="inci_mensaje" id="inci_mensaje"  size="60" >';
$html.='</div>';
$html.='<div class="col-md-12">';
$html.='<div class="col-md-6">';
if($select=="Si"){
	$html.='<label>Seleccionar usuario:</label>';
	$html.='<select name="usuarioSelec" id="usuarioSelec">';
	foreach ($usuariosSelect as $usu){
		if ($usu['id']==$usuDeft){
			$html.='<option value='.$usu['id'].' selected>'.$usu['username'].'</option>';
		}else{
			$html.='<option value='.$usu['id'].' >'.$usu['username'].'</option>';
		}
	}
	$html.='</select>';
}
$html.='</div>';
$html.='<div class="col-md-6">';
$html.='<label>Datos:</label>';
$html.='<input type="text" name="inci_datos" id="inci_datos" value='."'".$datos."'".' readonly="">';
$html.='</div>';
$html.='</div>';
$html.='<input type="hidden" name="numIncidencia" id="numIncidencia" value='.$numIncidencia.'>';
$html.='<div class="modal-footer">';
$html.='<a href="" onclick="enviarIncidencia();" >Guardar</a>';
$html.='</div>';
return $html;
	
}
function addIncidencia($usuario, $fecha, $dedonde, $datos, $estado, $mensaje, $BDTpv, $numIncidencia){
	switch($estado){
		case '0':
			$estado="No resuelto";
			break;
		case '1':
			$estado="Resuelto";
			break;
		case '2':
			$estado="Pendiente";
			break;
		default:
			$estado="No resuelto";
			break;
		
	}
	$sql='INSERT INTO modulo_incidencia (fecha_creacion, id_usuario, dedonde, mensaje, datos, estado) VALUES ("'.$fecha.'", '.$usuario.', '."'".$dedonde."'".', '."'".$mensaje."'".', '."'".$datos."'".', '."'".$estado."'".')';
	$res = $BDTpv->query($sql);
	$id=$BDTpv->insert_id;
	if($numIncidencia>0){
		$num=$numIncidencia;
	}else{
		$num=$id;
	}
	$sql2='UPDATE modulo_incidencia SET num_incidencia='.$num.' WHERE id='.$id;
	$res1 = $BDTpv->query($sql2);
	$respuesta['sql']=$sql;
	$respuesta['id']=$id;
	return $respuesta;
}

?>
