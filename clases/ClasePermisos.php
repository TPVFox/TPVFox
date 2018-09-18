<?php 

class ClasePermisos{
    public $permisos=array(); //todos los permisos
    public $BDTpv;
    public $usuario=array();//Datos del usuario
    public $ruta;//Ruta del directorio
    public $RutaServidor;//Ruta del servidor
    public $HostNombre;//Nombre del host
    public $RutaModulos;//Rura de los modulos
    public $RutaPlugin;//Ruta del plugin
    public $plugins=array();//Array con los plugins que tiene el proyecto
    
    public function __construct($Usuario, $conexion)
	{   
        $this->BDTpv=$conexion;
        $this->usuario=$Usuario;
        $this->permisos=$this->getPermisosUsuario($Usuario);
        $this->obtenerRutaProyecto();
        $this->ObtenerDir();
    }
   
    public function getPermisosUsuario($Usuario){
        // @ Objetivo:
        // Obtener array con los permisos de un usuario.
        // @ Parametro
        //     $Usuario -> array() Datos del usuario. ( no haría falt enviarlo, porque esta como propiedad
        //                  // pero así podemos utilizarla para obtener los permisos de un usuario.
        $respuesta=array();
        $BDTpv = $this->BDTpv;
        $this->usuario=$Usuario;
        $this->InicializarPermisosUsuario();
        $sql='SELECT * from permisos where idUsuario='.$Usuario['id'].' ORDER BY modulo , vista, accion asc ';
     
        $res = $BDTpv->query($sql);
        $resultadoPrincipal=array();
        while ( $result = $res->fetch_assoc () ) {
            array_push($resultadoPrincipal,$result);
        }
        $respuesta['resultado']=$resultadoPrincipal;
        return $respuesta;
    }
    
    public function InicializarPermisosUsuario(){
        //@Objetivo: Inicializar los permisos de un usuario, si es del grupo
        //9 quiere decir que es un administrador entonces modificamos los el xml para que el permiso sea siempre 1
       
        $this->obtenerRutaProyecto();
        $this->ObtenerDir();
        $modulos=$this->modulos;
        foreach($modulos as $modulo){//Recorrer todos los modulos
           
            if(is_file($this->RutaModulos.'/'.$modulo.'/acces.xml')){//Si en  el modulo existe el archivo acces
                $xml=simplexml_load_file($this->RutaModulos.'/'.$modulo.'/acces.xml');//lo cargamos
               if(isset($this->usuario['group_id'])){
                    if($this->usuario['group_id']==9){//Si el usuario es del grupo 9 moficamos el xml para que todos los permisos 
                                                    //sean 1
                    $xml=$this->ModificarPermisos($xml);
                }
               }
               
                $this->insertarPermisos($xml);//Cuando esté el xml listo insertamos los permisos
            }
            if (file_exists($this->RutaPlugin.'/'.$modulo)){
                
               $this->ObtenerPlugins($modulo);
               
                foreach ($this->plugins as $plugin){
                     if(is_file($this->RutaPlugin.'/'.$modulo.'/'.$plugin.'/acces.xml')){
                        $xml=simplexml_load_file($this->RutaPlugin.'/'.$modulo.'/'.$plugin.'/acces.xml');//lo cargamos
                   
                         if(isset($this->usuario['group_id'])){
                                if($this->usuario['group_id']==9){//Si el usuario es del grupo 9 moficamos el xml para que todos los permisos 
                                                            //sean 1
                                $xml=$this->ModificarPermisos($xml);
                                }
                        }
               
                        $this->insertarPermisos($xml);//Cuando esté el xml listo insertamos los permisos
                    }
                }
                
            }
          
           
        }
        return $xml;
        
   }
   public function ModificarPermisos($xml){
       //@Objetivo: recorrer el xml para que los permisos de todo el documento sea igual a 1
           $xml['permiso']=1;
           foreach ($xml->vista as $vista){
               $vista['permiso']=1;
               foreach ($vista->accion as $accion){
                   $accion['permiso']=1;
               }
           }
       return $xml; //Devolvemos el xml con los permisos cambiados
      
   }
   public function insertarPermisos($xml){
       //Objetivo: inserta los permisos a un usuario
       //Como esta acción la hacemos siempre comprobamos que el ese permisos expecifico existe o no 
       //Si no existe lo creamos
       //Este proceso se hace en todos los aparatados dql xml (modulos, vistas, acciones).
        $respuesta=array();
        $BDTpv = $this->BDTpv;
        $sql='SELECT id FROM permisos WHERE idUsuario='.$this->usuario['id'].' and modulo="'.$xml['nombre'].'" and vista IS NULL and accion IS NULL';
       
        $res = $BDTpv->query($sql);  
        
        if($res->num_rows==0){  
          
            $sql='INSERT INTO permisos (idUsuario, modulo, permiso) VALUES ('.$this->usuario['id'].', "'.$xml['nombre'].'",'.$xml['permiso'].')';
                    
            $res = $BDTpv->query($sql);
        }
        
            foreach ($xml->vista as $vista){
                $sql2='SELECT id FROM permisos WHERE idUsuario='.$this->usuario['id'].' and modulo="'.$xml['nombre'].'" and vista ="'.$vista['nombre'].'" and accion IS NULL';
                $res = $BDTpv->query($sql2);    
                if($res->num_rows==0){ 
                  
                 $sql2='INSERT INTO permisos(idUsuario, modulo, vista, permiso) VALUES ('.$this->usuario['id'].', 
                        "'.$xml['nombre'].'", "'.$vista['nombre'].'", '.$vista['permiso'].')';
                       
                 $res = $BDTpv->query($sql2);
                }
                 foreach($vista->accion as $accion){
                     $sql3='SELECT id FROM permisos WHERE idUsuario='.$this->usuario['id'].' and modulo="'.$xml['nombre'].'"  and vista ="'.$vista['nombre'].'" and accion ="'.$accion['nombre'].'"' ;
                    $res = $BDTpv->query($sql3);  
                       if($res->num_rows==0){ 
                               
                        $sql3='INSERT INTO permisos (idUsuario, modulo, vista, accion, permiso) VALUES ('.$this->usuario['id'].', "'.$xml['nombre'].'", "'.$vista['nombre'].'", "'.$accion['nombre'].'", '.$accion['permiso'].')';

                        $res = $BDTpv->query($sql3);
                    }
                 }
                 
            }

        return $sql;
   }
  
   
   public function obtenerRutaProyecto(){
		// Objectivo
		// Obtener rutas del servidor y del proyecto.
		$this->ruta 			=  __DIR__; // Sabemos el directorio donde esta fichero plugins
		$this->RutaServidor 	= $_SERVER['DOCUMENT_ROOT']; // Sabemos donde esta el servidor.
		$RutaProyectoCompleta 	= str_replace('clases','', __DIR__);
		$this->HostNombre		= str_replace($this->RutaServidor,'',$RutaProyectoCompleta);
        $this->RutaModulos=$this->RutaServidor.$this->HostNombre.'modulos';
        $this->RutaPlugin=$this->RutaServidor.$this->HostNombre.'plugins';
		
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
    public function ObtenerPlugins($modulo){
        $respuesta = array();
        if(file_exists($this->RutaPlugin.'/'.$modulo)){
		$scans = scandir($this->RutaPlugin.'/'.$modulo);
        foreach ( $scans as $scan){
			$ruta_completa = $this->RutaPlugin.'/'.$modulo.'/'.$scan;
           
			if (filetype($ruta_completa) === 'dir'){
				if (($scan === '.') || ($scan === '..')){ 
					// Descartamos los directorios . y ..
				} else {	
					$respuesta[] =$scan;
                      
				}
			}
		}
        $this->plugins=$respuesta;
    }
       
    }
    public function comprobarPermisos($permisos, $modulo, $vista){
        //Objetico: comprobar permisos para el menú superior de la aplicación 
        //Comprobamos el permiso del usuario con el permiso del xml del menu
        $respuesta = array();
        $permisoModulo = 0;
        $permisoVista = 0;
        $Link ='';
        $permiso=0;
        if ($modulo == ''){
            // NO es un modulo, es un link directo ( Home,Documentacion... )
            $permiso = 2 ;
            $link = $vista;
        } else { 
            // Es  un modulo...
            foreach ($permisos['resultado'] as $permiso){
                if($permiso['modulo']==$modulo && $permiso['vista'] == '') {
                    // Este es el permiso para este modulo
                    $permisoModulo = $permiso['permiso'];
                }
                if($permiso['modulo']==$modulo && $permiso['vista'] == $vista) {
                    // Este es el permiso para vista.
                    $permisoVista =  $permiso['permiso'];
                    $link = '/modulos/'.$modulo.'/'.$vista;
                    
                }
            }
            $permiso = $permisoModulo + $permisoVista;

        }
        // Ahora montamos link
        if ($permiso ==2){
            // tiene permiso
            $respuesta['permiso'] = 'Ok';
            $respuesta['link'] = $link;
        }else{
             $respuesta['permiso'] = 'No Ok';
        }
        return $respuesta;
    }
    
        
    public function modificarPermisoUsuario($datos, $permiso, $usuario){
        //Objetivo: Modificar los permisos , modificar los permisos a un usuario, esta tarea sólo la puede 
        //hacer el administrador
        $BDTpv = $this->BDTpv;
        if($datos['vista']==""){//Si vista está vacia tenemos que poner el texto is null 
            $vista="IS NULL";
        }else{
            $vista='="'.$datos['vista'].'"';
        }
        if($datos['accion']==""){//Lo mismo que el if anterior 
            $accion="IS NULL";
        }else{
            $accion='="'.$datos['accion'].'"';
        }
        $sql='UPDATE `permisos` SET permiso='.$permiso.' where idUsuario='.$usuario.' and modulo="'.$datos['modulo'].'" and vista '.$vista.' and accion '.$accion.'';
        $res = $BDTpv->query($sql);
        return $sql;
    }
    
    public function getAccion($accion){
        //OBjetivo: comprobar que la acción que vamos a realizar tenemos permisos o no
        $permisos=$this->permisos['resultado'];
       
        $ruta=str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['PHP_SELF']);//Ruta en la que estamos situados
       
        $vista=basename($ruta);//nos quedamos con la vista.php
        
        $rutas=explode('/', dirname($ruta));
        
        $modulo=end($rutas);//Nombre del modulo en el que estamos
        
        $perm="";
        //recorremos los permisos y devolvemos el permiso que tenemos en el modulo, vista y accion
        foreach ($permisos as $permiso){
            if($permiso['modulo']==$modulo && $permiso['vista']==$vista && $permiso['accion']==$accion){
                $perm=$permiso['permiso'];
                
                break;
            }
        }
        
        return $perm;
        
        
    }
    public function ObtenerDescripcion($nombre, $permiso){
        //@OBjetivo: Obtener la descripcion del nombre que estamos buscando en los acces
         if(is_file($this->RutaModulos.'/'.$permiso['modulo'].'/acces.xml')){//Comprobamos que en el modulo tenemos acces
             $xml=simplexml_load_file($this->RutaModulos.'/'.$permiso['modulo'].'/acces.xml');//Lo guardamos en una variable
             
            if($xml['nombre']==$nombre){//Comprobamos si en <modulo nombre=""> el nombre es el mismo que estamos buscando
                $descripcion=$xml['descripcion'];
            }else{
                foreach ($xml as $doc){//Recorremos las vistas y comprobamos el nombre de la vista 
                 if($doc['nombre']==$nombre){
                    $descripcion=$doc['descripcion'];
                    break;
                 }else{
                     if($doc['nombre']==$permiso['vista']){//Si el nombre de la vista no coincide entonces recorremos las acciones
                         foreach ($doc as $accion){
                             if($accion['nombre']==$nombre){
                                 $descripcion=$accion['descripcion'];
                             }else{
                                 $this->ObtenerPlugins($permiso['modulo']);
                                
                                 foreach ($this->plugins as $plugin){
                                     
                                      if(is_file($this->RutaPlugin.'/'.$permiso['modulo'].'/'.$plugin.'/acces.xml')){
                                          
                                           $xml=simplexml_load_file($this->RutaPlugin.'/'.$permiso['modulo'].'/'.$plugin.'/acces.xml');
                                           
                                           foreach ($xml as $doc){
                                              
                                                if($doc['nombre']==$nombre){
                                                   
                                                    $descripcion=$doc['descripcion'];
                                                    break;
                                                }else{
                                                     foreach ($doc as $accion){
                                                        
                                                         if($accion['nombre']==$nombre){
                                                              
                                                                $descripcion=$accion['descripcion'];
                                                                break;
                                                            }
                                                     }
                                                }
                                           }
                                      }
                                 }
                             
                                
                             }
                         }
                     }
                 }
             }
            }
            
            return $descripcion;
         }
         
    }
    
	
}


?>
