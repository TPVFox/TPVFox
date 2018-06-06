<?php 

class ClaseIncidencia{
	public $num_incidencia  = 0 ; // El num_incidencia que vamos.
	
	public function __construct($conexion){
		$this->db = $conexion;
	
		$sql = 'SELECT MAX(num_incidencia)  as num_reg FROM modulo_incidencia';
		$respuesta = $this->consulta($sql);
		$this->num_incidencia = $respuesta->fetch_object()->num_reg;
		
	}
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		if ($smt) {
			return $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	}
	
	public function GetNumRows(){
		return $this->num_incidencia;
	}
	public function todasIncidenciasLimite($limite){
		//@Objetivo : 
		//Listar todas las incidencias con el límite del páginado
		//Devuelve errores en caso de que el sql muestre un error
		$respuesta = array();
		$db=$this->db;
		$sql='SELECT a.id as id , a.`num_incidencia` as num_incidencia ,
		 a.fecha_creacion as fecha , a.dedonde as dedonde, 
		 a.estado as estado, b.nombre as nombre , a.mensaje as mensaje 
		 from modulo_incidencia as a INNER JOIN usuarios as b 
		 on a.id_usuario=b.id  where a.id in  (select max(id) 
		 from modulo_incidencia GROUP by num_incidencia) '.$limite;
		 $smt=$this->consulta($sql);
		 if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$sql;
				
		}else{
			while ( $result = $smt->fetch_assoc () ) {
				$respuesta['NItems'][] = $result;
			}
		}
		$respuesta['consulta']=$sql;
		return $respuesta;
	}
	public function datosIncidencia($idIncidencia){
		//Objetivo:
		//Mostrar los datos de un id de incidencia
		//Muestra los errores de sql
		$db=$this->db;
		$sql='select * from modulo_incidencia where id='.$idIncidencia;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$incidencia=$result;
			}
			return $incidencia;	
		}
	}
	public function incidenciasNumero($numeroIncidencia){
		// @ Objetivo
		// Mostrar todos los registro de una misma incidencia, ya que un num_inciencia pruede tener varios registros
		$db=$this->db;
		$sql='select a.* ,  b.username from modulo_incidencia as a inner JOIN   
		usuarios as b on a.id_usuario=b.id where num_incidencia='.$numeroIncidencia;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$incidenciaPrincipal=array();
				while ( $result = $smt->fetch_assoc () ) {
					array_push($incidenciaPrincipal,$result);
				}
				return $incidenciaPrincipal;
		}
	}
	
	public function addIncidencia( $dedonde, $datos, $mensaje, $estado, $numIncidencia){
		// Objetivo es añadir una nueva incidencia o la respuesta de una incidencia.	
		$db = $this->db;
		$this->obtenerUsuarioActual();
		$usuario = $this->id_usuario;
		switch($estado){
			case '0':
				$estado="No resuelto";
				break;
			case '1':
				$estado="Resuelto";
				break;
			case '2':
				$estado="Pendiente";
				break;
			default:
				$estado="No resuelto";
				break;
			
		}
		if($numIncidencia>0){
			$num=$numIncidencia;
		}else{
			$num=$this->num_incidencia+1;
			$this->num_incidencia = $num;
		}
		$sql='INSERT INTO modulo_incidencia (num_incidencia,fecha_creacion, id_usuario, dedonde, mensaje, datos, estado) VALUES ('.$num.',NOW(), '.$usuario.', '."'".$dedonde."'".', '."'".$mensaje."'".', '."'".$datos."'".', '."'".$estado."'".')';
		$res = $db->query($sql);
		if ($res) {
			$respuesta['id']=$db->insert_id;
			// Hubo resultados
		} else {
			// Quiere decir que hubo error en la consulta.
			$respuesta['error'] = array ( 'tipo'=>'danger',
						 'mensaje' =>'Error al insertar en tabla Articulos '.json_encode($db->connect_errno),
						 'dato' => $sqlArticulo
					);
		}
		$respuesta['sql']=$sql;
		return $respuesta;
	}
	
	public function obtenerUsuarioActual(){
		// Objetivo:
		// Obtener el usuario actual y guardar en el objeto la propiedad id_usuario.
		$Usuario= $_SESSION['usuarioTpv'];
		$this->id_usuario = $Usuario['id'];
		return $Usuario;
	}
	
	
	public function htmlModalIncidencia($datos, $dedonde, $configuracion, $estado, $numIncidencia){
		// @ Objetivo
		// 	Mostrar el contenido del modal de incidencias
		$db = $this->db;
		$this->obtenerUsuarioActual();
		$usuario = $this->id_usuario;
		$select="Si";
		$usuDeft=1;
		foreach($configuracion as $config){
			switch($config['descripcion']){
				case 'mostrar_Select':
					$select=$config['valor'];
				case 'usuario_Defecto':
					$usuDeft=$config['valor'];
			}
		}
		if ($usuDeft>=0){
			$sql='select * from usuarios';
			$smt = $db->query($sql);
			$usuariosSelect=array();
					while ( $result = $smt->fetch_assoc () ) {
						array_push($usuariosSelect,$result);
					}
		}
		$datosPrincipales=json_decode($datos);
		$html="";
		$html.='<div class="col-md-12">'
					.'<div class="col-md-6">'
						.'<label>Fecha:</label>'
						.'<input type="date" name="inci_fecha" id="inci_fecha" value="'.date("Y-m-d H:i:s").'" readonly="">'
					.'</div>'
					.'<div class="col-md-6">'
						.'<label>Dedonde:</label>'
						.'<input type="text" name="inci_dedonde" id="inci_dedonde" value="'.$dedonde.'" readonly="">'
					.'</div>'
			.'</div>'
			.'<div class="col-md-12">'
				
				.'<div class="col-md-6">'
				.'<label>Estado:</label>';
				if ($numIncidencia > 0){
					// Si hay numero incidencia se puede cambiar estado... 
					$html.='<select name="inci_estado" id="inci_estado">'
					.'<option value=0 selected>No resuelto</option>'
					.'<option value=1 selected>Resuelto</option>'
					.'<option value=2 selected>Pendiente</option>'
					.'</select>';
				}else{
					$html.='<input type="text" name="inci_estado" id="inci_estado" value="'.$estado.'" readonly="">';
				}
		$html.='</div>'
			.'<div class="col-md-6">';
				if($select=="Si"){
					$html.='<label>Seleccionar usuario que envias:</label>'
					.'<select name="usuarioSelec" id="usuarioSelec">';
					foreach ($usuariosSelect as $usu){
						if ($usu['id']==$usuDeft){
							$html.='<option value='.$usu['id'].' selected>'.$usu['username'].'</option>';
						}else{
							$html.='<option value='.$usu['id'].' >'.$usu['username'].'</option>';
						}
					}
					$html.='</select>';
				}
		$html.='</div>'
			.'</div>'
			.'<div class="col-md-12">'
			.'<label>Mensaje:</label>'
			.'<textarea  rows="4" cols="60" name="inci_mensaje" id="inci_mensaje"></textarea>'
			.'</div>'
			.'<div class="col-md-12">'
				.'<label>Datos:</label>'
				.'<textarea  rows="4" cols="60" name="inci_datos"  readonly id="inci_datos">'.$datos.'</textarea>'
			.'</div>'
			.'<div class="text-right">'
			.'<a class="btn btn-primary" onclick="enviarIncidencia();" >Guardar</a>'
			.'<input type="hidden" name="numIncidencia" id="numIncidencia" value='.$numIncidencia.'>'
			.'<input type="hidden" name="inci_usuario" id="inci_usuario" value="'.$usuario.'">'
			.'</div>';
	return $html;
		
	}
	
}

?>
