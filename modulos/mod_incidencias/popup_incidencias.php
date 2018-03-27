<?php 
$usuario=$_POST['usuario'];
$datos=$_POST['datos'];
$fecha=$_POST['fecha'];
$dedonde=$_POST['dedonde'];
$estado=$_POST['estado'];
$mensaje="";
?>
<input type="text" name="inci_usuario" id="inci_usuario" value="<?php echo $usuario;?>" readonly="">
<input type="date" name="inci_fecha" id="inci_fecha" value="<?php echo $fecha;?>" readonly="">
<input type="text" name="inci_dedonde" id="inci_dedonde" value="<?php echo $dedonde;?>" readonly="">
<input type="text" name="inci_estado" id="inci_estado" value="<?php echo $estado;?>" readonly="">
<input type="text" name="inci_mensaje" id="inci_mensaje" value="<?php echo $mensaje;?>">
<input type="text" name="inci_datos" id="inci_datos" value="<?php echo $datos;?>">
<a href="" onclick="enviarIncidencia();" >Guardar</a>
<?php



?>
