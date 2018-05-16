<?php 


class PluginClasePaginacion {
	public $PagActual 			= 1  ; // (int) Pagina actual
	public $CantidadRegistros	= 0 ; // (int) El total de registros de la consulta.
	public $LinkBase; 				// (string) Indica la ruta donde estamos.
	public $ArrayTPg 			= array('inicio'=>'Inicio','actual'=>'Actual','ultima'=>'Ultima'); // Para texto predeterminado.
	public $TotalPaginas 		= 1; // (int) Por defecto 1
	public $LimitePagina 		= 40 ; // (int) Por defecto , valor indicar registros por pagina.
	public $desde				= 0  ; // (int) El que indica desde donde se busca.
	public $info ; 					// (string) Html para mostrar informacion de cuantos registros encontrados y cuantos muestra.
	public $arrayBusqueda		= array(); // (array) con palabras que buscamos.
	public $Busqueda 			= ''; // (string) lo que se busco.
	public $filtroWhere			= ''; // (string) Es where para hacer la consulta
	public $limitConsulta		= ''; // (string) Es limite si lo hubiera.
	public $Paginas				= array(); // (array) Donde tendremos los numeros de la paginas previas y siguientes.

	public function __construct($fichero) {
		$this->LinkBase = './'.basename($fichero).'?';
		if (isset($_GET['pagina'])){
			$this->PagActual = $_GET['pagina'];
			if ($this->PagActual > 1){
				$this->desde = ($this->PagActual-1)*$this->LimitePagina;
			}
		}
		if (isset($_GET['buscar'])) {  
			//recibo un string con 1 o mas palabras
			if ($_GET['buscar'] !==''){
				$this->Busqueda = $_GET['buscar'];
				$this->arrayBusqueda = explode(' ',$_GET['buscar']); 
			}
		} 
	}


	public function InformacionPaginado(){
		// Objetivo es mostrar informacion de la cantidad registros encontrados (ya filtrados) y mostramos Npagina/TotalPagina
		$html = '<div>Encontrados '.$this->CantidadRegistros.'</div>';
		return $html;
		
	}


	public function CuantasPaginas(){
		if ($this->CantidadRegistros >0){
			$this->TotalPaginas = $this->CantidadRegistros/$this->LimitePagina;
			
			if ($this->TotalPaginas > 1){
				$this->limitConsulta= " LIMIT ".$this->LimitePagina." OFFSET ".$this->desde;
				
			}
			if ( $this->CantidadRegistros % $this->LimitePagina){
				// Sumamos una pagina ya que no da justo.
				$this->TotalPaginas = $this->TotalPaginas+1;
			}
			$this->TotalPaginas = (int)$this->TotalPaginas;
			$this->ObtenerPaginasPrevSigui();
		}
	}
	
	public function GetPagActual(){
		return $this->PagActual;
	}
	
	public function GetBusqueda(){
		$respuesta = $this->Busqueda;
		return $respuesta;
	}
	
	public function GetLinkBase(){
		return $this->LinkBase;
	}
	
	public function GetLimite(){
		return $this->LimitePagina;
	}
	
	public function GetDesde(){
		return $this->desde;
	}
	
	public function GetFiltroWhere(){
		return $this->filtroWhere;
	}
	
	public function GetLimitConsulta(){
		return $this->limitConsulta;
	}
	
	public function GetPaginas(){
		return $this->Paginas;
	}
	
	public function obtenerRutaProyecto(){
		// Objectivo
		// Obtener rutas del servidor y del proyecto.
		$ruta 			=  __DIR__; // Sabemos el directorio donde esta fichero plugins
		$this->RutaServidor 	= $_SERVER['DOCUMENT_ROOT']; // Sabemos donde esta el servidor.
		$RutaProyectoCompleta 	= str_replace('plugins/paginacion','', $ruta);
		$this->HostNombre		= str_replace($this->RutaServidor,'',$RutaProyectoCompleta);
		
	}
	
	
	public function ObtenerPaginasPrevSigui(){
		// Ahora monstamos las paginas previas.
		$paginas = array();
		$paginas['actual'] = $this->PagActual;
		$paginas['inicio'] = 1;
		$paginas['ultima'] = $this->TotalPaginas;
		// Preparamos paginas previos.
		if ($paginas['actual'] > $paginas['inicio']) {
			$difPg= $paginas['actual'] - $paginas['inicio'];
			if ($difPg >6 ){
				// Quiere decir que hay mas 5 paginas hasta llegar al inicio
				$difPg = 5; // Máximo de paginas previas a mostrar.		
			}
			// Array anteriores
			$x= 0;
			for ($i = 1; $i <= $difPg; $i++) {
				// Comprobamos que no vamos anotar la pagina inicio , que no hace falta.
				if (($paginas['actual']-($i)) > $paginas['inicio']) {
				$paginas['previo'][$i] = $paginas['actual']-($i);
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
		
		
		if ($paginas['actual'] < $paginas['ultima']) {
			$difPg= $paginas['ultima']- $paginas['actual'];
			if ($difPg > 6 ){
				$difPg = 5; // Su hay mas 5, solo muestra 6
				 
			}
			// Array siguientes
			$x= 0;
			for ($i = 1; $i <= $difPg; $i++) {
				// Comprobamos que no vamos añadir la pagina ultima, ya que esta no hace falta.
				if ($paginas['actual']+$i !== $paginas['ultima']) {
					$paginas['next'][$i] = $paginas['actual']+ $i  ;
					$x++;
				} 
			}
			
			// Ahora añadimos next intermedios si la diferencias entre el ultimo next y pagina ultima es mayor 10
			$PrevBloques = 0;
			if (isset($paginas['next'])){
				if ($paginas['next'][$x]){
					$PrevBloques= round((($paginas['ultima']- $paginas['next'][$x]) /4),0, PHP_ROUND_HALF_UP);
					$paginas['PrevBloquesNext'] = $PrevBloques;
				}
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
		
		$this->Paginas = $paginas;
		
	}
	
	public function htmlPaginado(){
		$htmlPG = '';
		if ($this->TotalPaginas >1){
			$ArrayTPg= $this->ArrayTPg;
			$paginas = $this->Paginas;
			$Linkpg = '<li><a href="'.$this->LinkBase;
			if( $this->Busqueda !== ''){
				$Linkpg	.= 'buscar='.$this->Busqueda.'&pagina=';
			} else {
				$Linkpg .= 'pagina=';
			}
			
			//~ $Linkpg	.='pagina=';
			// Montamos HTML para mostrar...
			$htmlPG =  '<ul class="pagination">';
			// Pagina inicio 
			if ($paginas['actual'] == $paginas['inicio']){
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
			if ($paginas['actual'] != 1 and $paginas['actual'] != $paginas['ultima'] ){
			// Pagina actual distinta a inicio....
			$htmlPG = $htmlPG.'<li class="active"><a>'.$paginas['actual'].'</a></li>';
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
			if ( $paginas['actual'] == $paginas['ultima']){
				$htmlPG = $htmlPG.'<li class="active"><a>'.$ArrayTPg['ultima'].'</a></li>';
			} else{
				$htmlPG = $htmlPG.$Linkpg.$paginas['ultima'].'">'.$ArrayTPg['ultima'].'</a></li>';
			}
			$htmlPG = $htmlPG. '</ul>';
		}
		return $htmlPG;
	}
	
	
	public function SetCamposControler($controler,$campos){
		$this->controler = $controler;
		$this->campos = $campos;
		if ($this->Busqueda !==''){
			$this->SetFiltroWhere();
		} 
	}
	
	public function SetFiltroWhere(){
		$controler =$this->controler;
		$campos = $this->campos;
		$this->filtroWhere = 'WHERE ('.$controler->ConstructorLike($campos,$this->Busqueda).')';
		
	}
	
	public function SetCantidadRegistros($totalRegistros){
		$this->CantidadRegistros = $totalRegistros;
		// Calculamos el total paginas.
		$this->CuantasPaginas();
	}
	
}






?>
