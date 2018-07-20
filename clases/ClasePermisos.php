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
        $sql='SELECT * from permisos where idUsuario='.$this->usuario['id'].' ORDER BY modulo , vista, accion asc ';
        $res = $BDTpv->query($sql);
        if($res->num_rows>0){
          $resultadoPrincipal=array();
			while ( $result = $res->fetch_assoc () ) {
				array_push($resultadoPrincipal,$result);
			}
			 $respuesta['resultado']=$resultadoPrincipal;
        }else{
            $this->InicializarPermisosUsuario();
            $res = $BDTpv->query($sql);
            $resultadoPrincipal=array();
            while ( $result = $res->fetch_assoc () ) {
				array_push($resultadoPrincipal,$result);
			}
			 $respuesta['resultado']=$resultadoPrincipal;
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
    
    public function comprobarPermisos($nivel, $permisos){
        $this->obtenerRutaProyecto();
        foreach ($permisos['resultado'] as $permiso){
           if($permiso['modulo']==$nivel['modulo'] & $permiso['vista']==$nivel['vista']){
                if(is_file($this->RutaModulos.'/'.$nivel['modulo'].'/acces.xml')){
                    $permisoUsuario=$permiso['permiso'];
                    break;
                }else{
                    $permisoUsuario=1;
                    break;
                }
            }else{
                $permisoUsuario=1;
            }
        }
        return $permisoUsuario;
    }
    
    //~ public function cargarPermisosUsuario($permisosUsuario){
        //~ $BDTpv = $this->BDTpv;
        //~ $sql='SELECT * from permisos where idUsuario='.$idUsuario;
        //~ $resultadoPrincipal=array();
        //~ while ( $result = $res->fetch_assoc () ) {
				//~ array_push($resultadoPrincipal,$result);
        //~ }
        //~ return $resultadoPrincipal;
    //~ }
    
    public function modificarPermisoUsuario($datos, $permiso, $usuario){
        $BDTpv = $this->BDTpv;
        if($datos['vista']==""){
            $vista="IS NULL";
        }else{
            $vista='="'.$datos['vista'].'"';
        }
        if($datos['accion']==""){
            $accion="IS NULL";
        }else{
            $accion='="'.$datos['accion'].'"';
        }
        $sql='UPDATE `permisos` SET permiso='.$permiso.' where idUsuario='.$usuario.' and modulo="'.$datos['modulo'].'" and vista '.$vista.' and accion '.$accion.'';
        $res = $BDTpv->query($sql);
        return $sql;
    }
    
    public function getAccion($accion){
        //recibe la accion para poder sacar el permiso de esa accion en en el modulo
        $permisos=$this->permisos['resultado'];
       
        $ruta=str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['PHP_SELF']);
       
        $vista=basename($ruta);
        
        $rutas=explode('/', dirname($ruta));
        
        $modulo=end($rutas);
        
        $perm="";
        foreach ($permisos as $permiso){
            if($permiso['modulo']==$modulo && $permiso['vista']==$vista && $permiso['accion']==$accion){
                $perm=$permiso['permiso'];
                
                break;
            }
        }
        
        return $perm;
        
        
    }
    
	
}


?>
