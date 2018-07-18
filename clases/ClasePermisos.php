<?php 

class ClasePermisos{
    public $permisos=array(); //todos los permisos
    public $BDTpv;
    public $usuario=array();
    public $ruta;
    public $RutaServidor;
    public $HostNombre;
    public $RutaModulos;
    
    public function __construct($Usuario, $conexion)
	{   
        $this->BDTpv=$conexion;
        $this->usuario=$Usuario;
        $this->permisos=$this->getPermisosUsuario();
       $this->obtenerRutaProyecto();
       $this->ObtenerDir();
    }
    
    public function getPermisosUsuario(){
        $respuesta=array();
        $BDTpv = $this->BDTpv;
        $sql='SELECT * from permisos where idUsuario='.$this->usuario['id'];
        $res = $BDTpv->query($sql);
        //~ $pwdBD = $res->fetch_assoc();
        if($res->num_rows>0){
          //~ $respuesta['resultado']=$res->fetch_all(MYSQLI_ASSOC);
          $resultadoPrincipal=array();
			while ( $result = $res->fetch_assoc () ) {
				array_push($resultadoPrincipal,$result);
			}
			 $respuesta['resultado']=$resultadoPrincipal;
        }else{
            $respuesta['permisos']=$this->InicializarPermisosUsuario();
            
        }
        return $respuesta;
    }
    
    public function InicializarPermisosUsuario(){
        //buscar todas las carpetas de modulo , buscar los acces de cada modulo y hacer insert de usuario con los permisos del acces
        //si tiene grupo 9 crearlo pero todos los permisos true
        $this->obtenerRutaProyecto();
        $this->ObtenerDir();
        $modulos=$this->modulos;
        foreach($modulos as $modulo){
            if(is_file($this->RutaModulos.'/'.$modulo.'/acces.xml')){
                $xml=simplexml_load_file($this->RutaModulos.'/'.$modulo.'/acces.xml');
                if($this->usuario['group_id']==9){
                    $xml=$this->ModificarPermisos($xml);
                }
                $this->insertarPermisos($xml);
                
            }
        }
        return $xml;
        
   }
   public function ModificarPermisos($xml){
           $xml['permiso']=1;
           foreach ($xml->vista as $vista){
               $vista['permiso']=1;
               foreach ($vista->accion as $accion){
                   $accion['permiso']=1;
               }
           }
       return $xml;
      
   }
   public function insertarPermisos($xml){
        $respuesta=array();
        $BDTpv = $this->BDTpv;
        $sql='INSERT INTO permisos (idUsuario, modulo, permiso) VALUES ('.$this->usuario['id'].', "'.$xml['nombre'].'",
                '.$xml['permiso'].')';
        $res = $BDTpv->query($sql);
        foreach ($xml->vista as $vista){
             $sql2='INSERT INTO permisos(idUsuario, modulo, vista, permiso) VALUES ('.$this->usuario['id'].', 
                    "'.$xml['nombre'].'", "'.$vista['nombre'].'", '.$vista['permiso'].')';
             $res = $BDTpv->query($sql2);
             foreach($vista->accion as $accion){
                 $sql3='INSERT INTO permisos (idUsuario, modulo, vista, accion, permiso) VALUES ('.$this->usuario['id'].', "'.$xml['nombre'].'",
                  "'.$vista['nombre'].'", "'.$accion['nombre'].'", '.$accion['permiso'].')';
                   $res = $BDTpv->query($sql3);
             }
             
        }
       
        return $sql;
   }
   //~ public function obtenerModulos(){
       
   //~ }
   
   public function obtenerRutaProyecto(){
		// Objectivo
		// Obtener rutas del servidor y del proyecto.
		$this->ruta 			=  __DIR__; // Sabemos el directorio donde esta fichero plugins
		$this->RutaServidor 	= $_SERVER['DOCUMENT_ROOT']; // Sabemos donde esta el servidor.
		$RutaProyectoCompleta 	= str_replace('clases','', __DIR__);
		$this->HostNombre		= str_replace($this->RutaServidor,'',$RutaProyectoCompleta);
        $this->RutaModulos=$this->RutaServidor.$this->HostNombre.'modulos';
		
	}
    public function ObtenerDir(){
		// Objetivo scanear directorio y cuales son directorios
		$respuesta = array();
		$scans = scandir($this->RutaModulos);
		foreach ( $scans as $scan){
			$ruta_completa = $this->RutaModulos.'/'.$scan;
			if (filetype($ruta_completa) === 'dir'){
				if (($scan === '.') || ($scan === '..')){ 
					// Descartamos los directorios . y ..
				} else {	
					$respuesta[] =$scan;
				}
			}
		}
        $this->modulos=$respuesta;
	}
	
}


?>
