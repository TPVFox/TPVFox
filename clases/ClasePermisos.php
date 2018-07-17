<?php 

class ClasePermisos{
    public $permisos=array(); //todos los permisos
    public $BDTpv;
    
    public function __construct($idUsuario, $conexion)
	{   
        $this->BDTpv=$conexion;
        $this->permisos=$this->getPermisosUsuario($idUsuario);
        
    }
    
    public function getPermisosUsuario($idUsuario){
        $respuesta=array();
        $BDTpv = $this->BDTpv;
        $sql='SELECT * from permisos where idUsuario='.$idUsuario;
        $res = $BDTpv->query($sql);
        //~ $pwdBD = $res->fetch_assoc();
        if($res->num_rows>0){
          
        }else{
            
        }
    }
}


?>
