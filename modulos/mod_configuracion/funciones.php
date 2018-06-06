<?php 
include '../../configuracion.php';
function abrirModal($id, $dedonde, $BDTpv){
		switch($dedonde){
			case 'iva':
				$html=crearModalIva($id, $BDTpv);
			break;
			case 'forma':
				$html=crearModalForma($id, $BDTpv);
			break;
			case 'vencimiento':
				$html=crearModalVencimiento($id, $BDTpv);
			break;
		
	}
	return $html;
}
function crearModalIva($id, $BDTpv){
	$iva=new ClaseIva($BDTpv);
	if($id==0){
		
	}else{
		$datos=$iva->getDatos($id);
		$datos=$datos['datos'][0];
		$html['datos']=$datos;
	}
	$html['html']='<div class="col-md-12">
			<div class="col-md-4">
			<label>Id:</label><input type="text" value="'.$datos['idIva'].'" readonly="readonly" size="3">
			</div>
			<div class="col-md-8">
			<label>Descripción:</label><input type="text" value="'.$datos['descripcionIva'].'">
			</div>
			</div>
		<div class="col-md-12">
			<div class="col-md-4">
			<label>Iva:</label> <input type="text" value="'.$datos['iva'].'" size="5">%
			</div>
			<div class="col-md-8">
			<label>Recargo:</label> <input type="text" value="'.$datos['recargo'].'" size="5">
			</div>
		</div>
		<div class="col-md-12">
		<p></p>
		</div>
		<div class="text-left">
			<a class="btn btn-primary" onclick="modificarTabla('."'".'iva'."'".')" >Guardar</a>
		</div>
		</div>';
	
	return $html;
}

function crearModalForma($id, $BDTpv){
	$formas=new ClaseFormasPago($BDTpv);
	if($id==0){
		
	}else{
		$datos=$formas->getDatos($id);
		$datos=$datos['datos'][0];
		$html['datos']=$datos;
	}
	$html['html']='<div class="col-md-12">
			<div class="col-md-4">
			<label>Id:</label><input type="text" value="'.$datos['id'].'" readonly="readonly" size="3">
			</div>
			<div class="col-md-8">
			<label>Descripción:</label><input type="text" value="'.$datos['descripcion'].'">
			</div>
			</div>
		<div class="col-md-12">
		<p></p>
		</div>
		<div class="text-left">
			<a class="btn btn-primary" onclick="modificarTabla('."'".'forma'."'".')" >Guardar</a>
		</div>
		</div>';
	
	return $html;
}
function crearModalVencimiento($id, $BDTpv){
	$Vencimiento=new ClaseVencimiento($BDTpv);
	if($id==0){
		
	}else{
		$datos=$Vencimiento->getDatos($id);
		$datos=$datos['datos'][0];
		$html['datos']=$datos;
	}
	$html['html']='<div class="col-md-12">
			<div class="col-md-4">
			<label>Id:</label><input type="text" value="'.$datos['id'].'" readonly="readonly" size="3">
			</div>
			<div class="col-md-8">
			<label>Descripción:</label><input type="text" value="'.$datos['descripcion'].'">
			</div>
			</div>
		<div class="col-md-12">
		<p></p>
		</div>
		<div class="text-left">
			<a class="btn btn-primary" onclick="modificarTabla('."'".'vencimiento'."'".')" >Guardar</a>
		</div>
		</div>';
	
	return $html;
}
?>
