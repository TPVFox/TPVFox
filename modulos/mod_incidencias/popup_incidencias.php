<?php 

function modalIncidencia($usuario, $datos, $fecha, $dedonde, $estado){
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
$html.='<input type="text" name="inci_estado" id="inci_estado" value="'.$estado.'" readonly="">';
$html.='</div>';
$html.='</div>';
$html.='<div class="col-md-12">';
$html.='<label>Mensaje:</label>';
$html.='<input type="text" name="inci_mensaje" id="inci_mensaje"  size="60" >';
$html.='</div>';
$html.='<div class="col-md-12">';
$html.='<label>Datos:</label>';
$html.='<input type="text" name="inci_datos" id="inci_datos" value='."'".$datos."'".' readonly="">';
$html.='</div>';
$html.='<div class="modal-footer">';
$html.='<a href="" onclick="enviarIncidencia();" >Guardar</a>';
$html.='</div>';
return $html;
	
}
function addIncidencia($usuario, $fecha, $dedonde, $datos, $estado, $mensaje, $BDTpv){
	$sql='INSERT INTO modulo_incidencia (fecha_creacion, id_usuario, dedonde, mensaje, datos, estado) VALUES ("'.$fecha.'", '.$usuario.', '."'".$dedonde."'".', '."'".$mensaje."'".', '."'".$datos."'".', '."'".$estado."'".')';
	$res = $BDTpv->query($sql);
	$id=$BDTpv->insert_id;
	$sql2='UPDATE modulo_incidencia SET num_incidencia='.$id.' WHERE id='.$id;
	$res1 = $BDTpv->query($sql2);
	$respuesta['sql']=$sql;
	$respuesta['id']=$id;
	return $respuesta;
}

?>
