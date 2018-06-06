<?php 


function abrirModal($id, $dedonde){
		switch($dedonde){
			case 'iva':
				$html=crearModalIva($id);
			break;
			case 'forma':
				$html=crearModalForma($id);
			break;
			case 'vencimiento':
				$html=crearModalVencimiento($id);
			break;
		
	}
}
function crearModalIva($id){
	$iva=new ClaseIva($BDTpv);
	if($id==0){
		
	}else{
		
	}
}

?>
