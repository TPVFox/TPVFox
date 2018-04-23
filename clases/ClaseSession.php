<?php
/* Propiedades en minuscula.
 * Metodos en UpperCamelCase
 * */
$rutaCompleta = $RutaServidor.$HostNombre;
include ($rutaCompleta.'/clases/ClaseConexion.php');

class ClaseSession extends ClaseConexion{
	public $BDTpv ; // (object) Conexion a Base de datos tpv , tiene que se a tpv ya que controla session de ese equipo.
	private $session ; // (array) Datos de $_SESSION que controlamos.
	public $Tienda ; // (array) Contiene array con los datos tienda de la session.
	public $Usuario; // (array) Contiene array con los datos del Usuario de la session.
	
	public function __construct()
	{
		parent::__construct();
		$this->BDTpv	= parent::getConexion();
		$this->comprobarEstado($this->ruta_proyecto); 
	}
	
	public function GetSession(){
		// Objetivo devolver la session
		return $this->session;
	}
	
	
	public function comprobarEstado(){
		// @ Objetivo :
		// Comprobar si hay session para la aplicacion abierta y si el usuario es correcto.
		// @ Parametros:
		//		$rutaCompleta -> Ruta completa.
		$rutaCompleta = $this->ruta_proyecto;
		$BDTpv = $this->BDTpv;
		$resultado = array();
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
			$resultado['SessionTpv']['estado'] = $_SESSION['estadoTpv'];
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
			if (isset($_POST['usr']) && isset($_POST['pwd'])){
				// tenemos datos en post para comprobar.
				$datos = $this->comprobarUser($BDTpv,$_POST['usr'],$_POST['pwd']);
				$resultado['usuario'] = $datos;
				// Ahora obtenemos datos tienda
				$datos = $this->comprobarTienda($BDTpv);
				$resultado['tienda'] = $datos;
			}
		}
		// Comprobación si todo es correcto.. 
		//~ $comprobar = $this->controlSession($rutaCompleta,$resultado); 
		$comprobar = $this->controlSession($resultado);
		$resultado['SessionTpv']['estado'] = $comprobar;
		if($numeroPaginas >0){
			// Solo cambiamos estado si el numeroPaginas es superior a 0
			$_SESSION['estadoTpv'] = $resultado['SessionTpv']['estado'];
		}
		
		$this->session = $_SESSION;
	}
	
	//comparar usuario y password con bbdd
	function comprobarUser($BDTpv,$usuario,$pwd){
		// Esto comprobamos que los datos metidos en el formulario son correctos.
		$resultado = array();
		$BDTpv = $this->BDTpv;
		$encriptada = md5($pwd);// Encriptamos contraseña puesta en formulario.
		$sql = 'SELECT password,nombre,id,group_id FROM usuarios WHERE username="'.$usuario.'"';
		$res = $BDTpv->query($sql);
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$resultado['consulta'] = $sql;
			$resultado['error'] = $BDTpv->error_list;
			$_SESSION['estadoTpv']= 'ErrorConsulta';
			return $resultado;
		} 
		$pwdBD = $res->fetch_row();
			
		if ($encriptada === $pwdBD[0]){
			// Quiere decir que usuario y password son correcto.
			$resultado['login']=$usuario;
			$resultado['nombre']= $pwdBD[1];
			$resultado['id']= $pwdBD[2];
			$resultado['group_id']= $pwdBD[3];
			// Antes de continuar tenemos que saber si tiene registro indice, 
			// ya que si no tiene indices error al cobrar pero no se ve.
			$sql = 'SELECT * FROM indices WHERE idUsuario="'.$resultado['id'].'"';
			$res = $BDTpv->query($sql);
			if (mysqli_error($BDTpv)){
				$resultado['consulta'] = $sql;
				$resultado['error'] = $BDTpv->error_list;
				$_SESSION['estadoTpv']= 'ErrorConsulta';
				return $resultado;
			} else {
				// Ahora comprobamos que tenga registro
				if ($res->num_rows === 1){
					// Existe registro en tabla indice.
					$_SESSION['estadoTpv']= 'Correcto';
					$_SESSION['usuarioTpv']= $resultado;
				} else {
					$_SESSION['estadoTpv']= 'ErrorIndiceUsuario';
					$_SESSION['indice'] = $res->num_rows;

				}
			}
		} else {
			$_SESSION['estadoTpv']= 'ErrorLogin';
		}
		return $resultado;
	 } 
	 
	 function comprobarTienda($BDTpv){
		$resultado = array();
		$sql = 'SELECT idTienda,razonsocial,telefono,direccion,NombreComercial,nif,ano,estado FROM tiendas WHERE estado="activo"';
		$res = $BDTpv->query($sql);
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$resultado['consulta'] = $sql;
			$resultado['error'] = $BDTpv->error_list;
			return $resultado;
		} 
		$datos = $res->fetch_assoc();
		$resultado = $datos;
		$_SESSION['tiendaTpv']= $resultado; 
				 
		return $resultado;
	 }
	 
	 function controlSession($Estado){
		// Aqui venimos siempre, no debemos hacer consultas, solo poner estado
		// Al iniciar session, cuando estamos logueados.
		// El objetivo es comprobar que los parametros session esten correctos.
		if (count($Estado) === 0 ){
			// Obtenemos datos de session
			$Estado['tienda'] = $_SESSION['tiendaTpv'];
			$Estado['usuario'] = $_SESSION['usuarioTpv'];
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
	
	
	function cerrarSession(){
		//~ session_start();
		session_unset();
		session_destroy();
		// NO puedo hacer header si ya envie informacion de imprimir, por lo que lo descarto.
		//header('Location:./../../index.php');
		
		
	}
	 
}
?>
