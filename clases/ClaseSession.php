<?php
/* Propiedades en minuscula.
 * Metodos en UpperCamelCase
 * */
$rutaCompleta = $RutaServidor.$HostNombre;
include ($rutaCompleta.'/clases/ClaseConexion.php');
include_once ('ClasePermisos.php');
class ClaseSession extends ClaseConexion{
	public $BDTpv ; 					// (object) Conexion a BD tpv.
	private $session ;					// (array) Datos de $_SESSION actuales.
	public $Tienda = array(); 			// (array) Datos tienda de principal.
	public $Usuario; 					// (array) Contiene array con los datos del Usuario de la session.
    public $TiendaWeb = array();		// (array) Obtenemos los datos tienda web activa  ( solo puede haber una activa )
    public $comprobaciones = array(); 	// (array) Errores o advertencias.
    public $permisos;                   // (Objeto) Es objeto de la clase de permisos
	public function __construct()
	{
		parent::__construct();
		$this->BDTpv	= parent::getConexion();
		$this->comprobarEstado(); 
        $this->permisos =new ClasePermisos($this->Usuario, $this->BDTpv);
	}
	
	public function GetSession(){
		// Objetivo devolver la session
		return $this->session;
	}
	
	public function GetComprobaciones(){	
		// Obtener las comprobaciones, errores, advertencias o informacion
		return $this->comprobaciones;
	}
	public function GetTienda(){	
		// Obtener las comprobaciones, errores, advertencias o informacion
		return $this->Tienda;
	}
	
	
	public function comprobarEstado(){
		// @ Objetivo :
		// Comprobar si hay session para la tpvFox y si es correcta.
		$rutaCompleta = $this->ruta_proyecto;
		$BDTpv = $this->BDTpv;
		// --------------  Iniciamos session si no esta iniciada. --------------------- //
		if (!isset($_SESSION)){
			// Hay que tener en cuenta que la session no tenemos porque iniciar nosotros, 
			// otra api del servidor la puede abrir, por eso no debemos reiniciarla nunca
			// si no esta abierta.
			session_start();
		} 
		if (!isset($_SESSION['estadoTpv']) || $_SESSION['estadoTpv']=== 'SinActivar'){
			// Entramos al iniciar sesion o si esta SinActivar.			
			$_SESSION['estadoTpv']= 'SinActivar'; // Ponemos por defecto sessión inactiva.
			// Ahora comprobamos si es primera vez entra o no.
			if (!isset($_SESSION['N_Pagina_Abiertas'])){
				$_SESSION['N_Pagina_Abiertas'] = 0 ;
			}
		} 
		// --------------  Ya tenemos session abierta ahoa comprobamos su estado --------------  //
		$numeroPaginas = (isset($_SESSION['N_intentos_acceso']) ? $_SESSION['N_intentos_acceso'] : 0);
		if ($_SESSION['estadoTpv'] != 'Correcto' || $numeroPaginas > 0 ){
			// El estado de la session no es correcto y ya tenemos session (N_Pagina_Abiertas) + 1.
			// Esto puede suceder cuando:
			//    - Refrescamos formulario de Acceso de Usuario.
			//    - Acaba de enviar el formulario.
			//    - Hubo un error en los datos de acceso.
			
			//  Comprobamos tienda si hay principal y activa, tb obtenemos propiedad de tienda web activa
			$this->comprobarTienda($BDTpv);
			
			// Si hay post comprobamos si son correctos los datos.. 
			if (isset($_POST['usr']) && isset($_POST['pwd'])){
				$this->comprobarUser($_POST['usr'],$_POST['pwd']);
			}
		}
		// Comprobación si todo es correcto.. 
		$comprobar = $this->controlSession(); 
		if($numeroPaginas >0){
			// Solo cambiamos estado si el numeroPaginas es superior a 0
			$_SESSION['estadoTpv'] = $comprobar ;//resultado['SessionTpv']['estado'];
		}
		
		$this->session = $_SESSION;
	}
	
	function comprobarUser($usuario,$pwd){
		// Objetivo
		// Comprobar que los datos metidos en el formulario acceso son correctos.
		$BDTpv = $this->BDTpv;
		$encriptada = md5($pwd);// Encriptamos contraseña puesta en formulario.
		$sql = 'SELECT password,nombre,id,group_id FROM usuarios WHERE username="'.$usuario.'"';
		$res = $BDTpv->query($sql);
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$this->SetComprobaciones(array('tipo'=>'danger',
											 'mensaje' => 'Error en la consulta de usuario:'.$sql,
											 'dato'	  => $BDTpv->error_list
											)
										);
			$_SESSION['estadoTpv']= 'ErrorConsulta';
		} else {
			$pwdBD = $res->fetch_assoc();
			$pwdBD['login'] = $usuario; 	
			if ($encriptada === $pwdBD['password']){
				// Quiere decir que usuario y password son correcto.
				// Comprobamos si tiene registro indice el usuario. 
				$sql = 'SELECT * FROM indices WHERE idUsuario="'.$pwdBD['id'].'"';
				$res = $BDTpv->query($sql);
				if (mysqli_error($BDTpv)){
					$this->SetComprobaciones(array('tipo'=>'danger',
												 'mensaje' => 'Error en la consulta de tienda:'.$sql,
												 'dato'	  => $BDTpv->error_list
												)
											);
					$_SESSION['estadoTpv']= 'Error';
				} else {
					// Ahora comprobamos que tenga registro
					if ($res->num_rows === 1){
						// Existe registro en tabla indice.
						$_SESSION['estadoTpv']= 'Correcto';
						// Elimino de resultado password ya que no lo necesitamos guardar en session.
						unset($pwdBD['password']);
						$_SESSION['usuarioTpv']= $pwdBD;

					} else {
						$_SESSION['estadoTpv']= 'Error';
						$this->SetComprobaciones(array('tipo'=>'danger',
												 'mensaje' => 'Error al obtener indice de usuario:'.$res->num_rows,
												 'dato'	  => $BDTpv->error_list
												)
											);
						
						$_SESSION['indice'] = $res->num_rows;

					}
				}
			} else {
				$_SESSION['estadoTpv']= 'Error';
				$this->SetComprobaciones(array('tipo'=>'warning',
											 'mensaje' => 'Usuario o contraseña incorrecta',
											 'dato'	  => ''
											)
										);
			}
		}
		return ;
	 } 
	 
	 function comprobarTienda($BDTpv){
		$resultado = array();
		$sql = 'SELECT tipoTienda,idTienda,razonsocial,telefono,direccion,NombreComercial,nif,ano,estado FROM tiendas WHERE estado="activo"';
		$res = $BDTpv->query($sql);
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$this->SetComprobaciones(array('tipo'=>'danger',
											 'mensaje' => 'Error en la consulta de tienda:'.$sql,
											 'dato'	  => $BDTpv->error_list
											)
										);
		} else {
			if ($res->num_rows > 0){
				while ($item = $res->fetch_assoc()){
					if ($item['tipoTienda'] === 'principal'){
						if (count($this->Tienda) ===0 ){
							$this->Tienda = $item; // La tienda principal .
						}
						if (!isset($_SESSION['tiendaTpv'])){
							$_SESSION['tiendaTpv']= $item; 
						} else {
							if (serialize ($this->Tienda) !== serialize ($item)){
								// Si no es la misma tienda generamos un error, ya es que hay mas de una.
								$_SESSION['estadoTpv']= 'Error';
								$this->SetComprobaciones(array('tipo'=>'danger',
												 'mensaje' => 'Hay mas de una tienda principal como activo'
															.' ponte en contacto con administrador',
												 'dato'	  => 'Encontrados '.$res->num_rows.' tienda principal:'.$sql
												)
											);
								
							}
						}
					}
				}
			}
		}
		return ;
	 }
	 
	 function controlSession(){
		// Objetivo:
		// Esto se ejecuta siempre para comprobar que es correcta la session.
		// No debemos hacer consultas
		
		if (isset($_SESSION['tiendaTpv'])){
			$Estado['tienda'] = $_SESSION['tiendaTpv'];
		}
		if (isset( $_SESSION['usuarioTpv'])){
			$Estado['usuario'] = $_SESSION['usuarioTpv'];
            $this->Usuario=$_SESSION['usuarioTpv'];
		}else{
            $this->Usuario=array('id'=>0, 'group_id'=>0,'login' =>'invitado');
        }
		$control = 0;
		// Comprobamos que exista los parametros de la session. 
		// la variable control no puede sumar se mayor 0
		$control = $control + (isset($Estado['usuario']['login']) ? 0 : 1);
		$control = $control + (isset($Estado['usuario']['nombre']) ? 0 : 1);
		$control = $control + (isset($Estado['tienda']['razonsocial']) ? 0 : 1);
		$control = $control + (isset($Estado['tienda']['idTienda']) ? 0 : 1);
		if ( $control > 0){
			// Algo no esta bien
			
			
			return 'Erroneo'; // Aunque se puede gestionar distintos errores o situaciones.
		}
		// Devolvemos string si es correo o no.
		return 'Correcto';
	}
	
	public function SetComprobaciones($error){
		// Objetivo 
		// Añadir al array una comprobacion.
		if (gettype($error) === 'array'){
			// Es un array , ahora deberíamos comprobar que el tipo es corecto...:-)
			// De momento no lo hago..
			array_push($this->comprobaciones,$error);
		}
		
	}
	
	function cerrarSession(){
		//~ session_start();
		session_unset();
		session_destroy();
		// NO puedo hacer header si ya envie informacion de imprimir, por lo que lo descarto.
		//header('Location:./../../index.php');
		
		
	}
	 
}
?>
