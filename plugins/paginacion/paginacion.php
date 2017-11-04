<?php 
// =========       inicio de Paginado     ===================  //
// Funcion para crear array de paginacion.
// Explicacion de los datos que necesitamos:
// $PagActual
// $CantidadRegistros
// $LimitePagina  // Ya a tengo creada;
// $LinkBase Donde estamos...Esto no lo mandamos de momento...
// $OtrosParametros  ( Otroas parametros queremos enviar por get.)
// El objetivo:
// Crear un array que traiga la enumeración de las paginas y sus links correspondiente.
// Mostrando como mucho 5 pag. al inicio y 5 pagina al final.
// Y en el medio 5 paginas.
//
// Esta funcion no realiza ninguna peticion al servidor, ya que la funcion recibe todos los datos que necesita.
// Estructura array de paginas:
	// paginas{
	//		actual:
	//		inicio: 
	//		ultima:
	//		
	//		next->
	//			[id]
	//		previo->
	//			[id]
	// 			
function paginado ($PagActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros='') {
	// Asignar variables
	$paginas = array();
	$ArrayTPg = array();
	
	// Array para texto de paginas.
	$ArrayTPg = array('inicio'=>'Inicio','actual'=>'Actual','ultima'=>'Ultima');
	$TotalPaginas = 1 ; // Valor por defecto.
	$resto= 0;
	if ($CantidadRegistros > $LimitePagina ) {
	// Si hay mas 50 , realizamos paginación.
		$TotalPaginas = $CantidadRegistros / $LimitePagina;
		$paginas['Division'] = $TotalPaginas;
	}
	// Ahora creamos array paginas.
	$paginas['Actual'] = $PagActual; 
	$paginas['Ultima'] = round($TotalPaginas,0);   // Redondeo al alza...
	if ($paginas['Ultima'] < $TotalPaginas){
		// Añadimos incrementamos pagina Ultima en una.
		$paginas['Ultima'] =$paginas['Ultima'] + 1;
	}
	$paginas['inicio'] = 1;
	
	switch ($paginas['Actual']) {
		case 1:
		$paginaInicio = $paginas['Actual'];
		break;
		case $TotalPaginas:
		$paginas['Ultima'] = $paginas['Actual'];
		break;
		
	}
	// Ahora monstamos las paginas previas.
	if ($paginas['Actual'] > $paginas['inicio']) {
		$difPg= $paginas['Actual'] - $paginas['inicio'];
		if ($difPg >6 ){
			// Quiere decir que hay mas 5 paginas hasta llegar al inicio
			$difPg = 5; // Máximo de paginas previas a mostrar.		
		}
		// Array anteriores
		$x= 0;
		for ($i = 1; $i <= $difPg; $i++) {
			// Comprobamos que no vamos anotar la pagina inicio , que no hace falta.
			if (($paginas['Actual']-($i)) > $paginas['inicio']) {
			$paginas['previo'][$i] = $paginas['Actual']-($i);
			$x++;
			}
		}
		// Ahora añadimos previos intermedios si la diferencias entre el ultimo previo y pagina inicio es mayor 10
		$PrevBloques = 0;
		if (isset($paginas['previo'])){
			if ($paginas['previo'][$x]){
				$PrevBloques= round((($paginas['previo'][$x]- $paginas['inicio']) /4), 0, PHP_ROUND_HALF_UP);
				$paginas['PrevBloquesPrevio'] = $PrevBloques;

			}
			if (($PrevBloques)>=3){
				$UltimoPrevio = $paginas['previo'][$x];
				for ($i = 1; $i < 4; $i++) {
				$x++;
				$paginas['previo'][$x]= $UltimoPrevio - ($i*$PrevBloques);
				}
			}  
		}	
	}
	
	if ($paginas['Actual'] < $paginas['Ultima']) {
		$difPg= $paginas['Ultima']- $paginas['Actual'];
		if ($difPg > 6 ){
			$difPg = 5; // Su hay mas 5, solo muestra 6
			 
		}
		// Array siguientes
		$x= 0;
		for ($i = 1; $i <= $difPg; $i++) {
			// Comprobamos que no vamos añadir la pagina ultima, ya que esta no hace falta.
			if ($paginas['Actual']+$i != $paginas['Ultima']) {
				$paginas['next'][$i] = $paginas['Actual']+ $i  ;
				$x++;
			} 
		}
		
		// Ahora añadimos next intermedios si la diferencias entre el ultimo next y pagina ultima es mayor 10
		$PrevBloques = 0;
		if ($paginas['next'][$x]){
			$PrevBloques= round((($paginas['Ultima']- $paginas['next'][$x]) /4),0, PHP_ROUND_HALF_UP);
			$paginas['PrevBloquesNext'] = $PrevBloques;
		}
		$i= 1;
		if (($PrevBloques) > 3 ){
				$UltimoNext = $paginas['next'][$x];
				for ($i = 1; $i < 4; $i++) {
					$x++;
					$paginas['next'][$x]= $UltimoNext + ($i*$PrevBloques);
				}
			}  
		
	}
	// Ya tenemos Array de paginas , faltaría las paginas intermedias previas y next.
	// Estas la añadimos al montar html.
	

	// Montamos HTML para mostrar...
	$htmlPG =  '<ul class="pagination">';
	$Linkpg = '<li><a href="'.$LinkBase.'buscar='.$OtrosParametros.'&pagina=';
	// Pagina inicio 
	if ($paginas['Actual'] == $paginas['inicio']){
		$htmlPG = $htmlPG.'<li class="active"><a>'.$ArrayTPg['inicio'].'</a></li>';
	} else {
		$htmlPG = $htmlPG.$Linkpg.$paginas['inicio'].'">'.$ArrayTPg['inicio'].'</a></li>';
	}
	
	// Paginas anteriores (previos)
	if (isset($paginas['previo'])){
		// El orden es al reves, de la creacion 
		$previo = $paginas['previo'];
		sort($previo); // Ordenamo ... 
		$ordenInverso = $previo;
		foreach ($ordenInverso as $pagina) {
			$htmlPG = $htmlPG.$Linkpg.$pagina.'">'.$pagina.'</a></li>';
		}
		
	}
	// Pagina actual ()
	if ($paginas['Actual'] != 1 and $paginas['Actual'] != $paginas['Ultima'] ){
	// Pagina actual distinta a inicio....
	$htmlPG = $htmlPG.'<li class="active"><a>'.$paginas['Actual'].'</a></li>';
	}
	// Pagina siguientes.
	$x= 0;
	if (isset($paginas['next'])){
		foreach ($paginas['next'] as $paginaF	) {
			$x++ ;
			$pref= '';
			if ($x>5){
			// Marque el salto..
			$pref = "&gt;"; //'>';	
			}
			$htmlPG = $htmlPG.$Linkpg.$paginaF.'">'.$pref.$paginaF.'</a></li>';
		}
	}
	//~ $controlError .= '-PaginaF:'.$paginaF;
	// Mostramos ultima pagina, si no se mostro en previo.
	if ( $paginas['Actual'] == $paginas['Ultima']){
		$htmlPG = $htmlPG.'<li class="active"><a>'.$ArrayTPg['ultima'].'</a></li>';
	} else{
		$htmlPG = $htmlPG.$Linkpg.$paginas['Ultima'].'">'.$ArrayTPg['ultima'].'</a></li>';
	}
	$htmlPG = $htmlPG. '</ul>';
	// Mostramos errores
	//~ echo $controlError;
	
	// =========       Fin paginado      ===================  //
	//~ return $paginas;
	return $htmlPG;

}
?>
