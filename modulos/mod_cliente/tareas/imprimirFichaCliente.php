<?php 


//@Objetivo: Generar el documento de impresión ficha cliente


//~ include_once ($URLCom ."/modulos/mod_cliente/clases/ClaseCliente.php");
$Cliente= new ClaseCliente($BDTpv);
$idCliente=$_POST['idCliente'];
$Tienda = $_SESSION['tiendaTpv'];
$resultado['tienda']=$Tienda;
error_log(json_encode($resultado));
$datos = array(
                'Nombre'        =>"",
                'razonsocial'   =>"",
                'nif'           =>"",
                'direccion'     =>"",
                'telefono'      =>"",
                'movil'         =>"",
                'fax'           =>"",
                'email'         =>""
            );
if($idCliente>0){
    $datosCliente=$Cliente->getCliente($idCliente);
    $datos = $datosCliente['datos'][0];        
}
	
	$textolegal='<b>Protección de datos personales</b><br/>
    En relación con los datos personales,le informamos que el responsable del tratamiento somos nosotros unicamente.<br/>
    La finalidad principal para recabar los datos es para la gestion de la relación comercial.<br/>
    Nosotros no cederemos los datos personales a terceros que no pertenezca al grupo de '.$resultado['tienda']['NombreComercial'].' , los términos establecidos en la POLÍTICA DE PRIVACIDAD publicada en el apartado de la pagina WEB.<br/>
    El titular dispone de los derechos de acceso, rectificación , supresión y derecho al olivdo, oposición, limitación del tratamiento y portabilidad.<br/>
    El titular que podrá ejercitar dicho derecho acreditando su identidad, mediante una comunicación escrita al Delegado de Protección de Datos designado, a través de su dirección de '.$resultado['tienda']['direccion'].'.';
	$cabecera= 
    '<table>
    <tr><td style="text-align:center;"><font size="18"><b>'.$resultado['tienda']['NombreComercial'].'</b></font><br></td></tr>
    <tr><td><font size="15">FICHA DE CLIENTE </font></td></tr>
    </table>
    <table cellpadding="5" cellspacing="3" border="1px">
    <tr>
        <td WIDTH="20%">Nombre</td><td WIDTH="80%">' . $datos['Nombre'] . '</td>
    </tr>
    <tr>
        <td WIDTH="20%">Razón social:</td><td WIDTH="80%">'. $datos['razonsocial'] . '</td>
    </tr>
    <tr>
        <td WIDTH="20%">NIF:</td><td WIDTH="80%"> '. $datos['nif'] . '</td>
    </tr>
    <tr>
        <td WIDTH="20%">Dirección:</td><td WIDTH="80%">'. $datos['direccion'] . '</td>
    </tr>
    <tr>
        <td WIDTH="20%">Teléfono:</td><td WIDTH="80%">'. $datos['telefono'] . '</td>
    </tr>
    <tr>
        <td WIDTH="20%">Móvil:</td><td WIDTH="80%">'.$datos['movil'] . '</td>
    </tr>
    <tr>
        <td WIDTH="20%">Fax:</td><td WIDTH="80%">' . $datos['fax'] .'</td>
    </tr>
    <tr>
        <td WIDTH="20%">Email:</td><td WIDTH="80%">'. $datos['email']. '</td>
    </tr>
    </table>
';
	
	$html ='
       <br></br>
    <br></br>
    <font>Firma Cliente</font>
    <br></br>
    <br></br>
    <br></br>
    <br></br>
    <br></br>
     <br></br>
    <br></br>
    <table cellpadding="5" cellspacing="3" border="1px">
     <tr>
        <td WIDTH="5%"></td><td WIDTH="95%"><font size="8">Acepta que le enviemos cupones, promociones y informacion nuevos productos, a traves de los medios comunicación que nos indicó.</font> </td>
    </tr>
    </table>
    <br></br>
    <br></br>
    
    <font size="8">'. $textolegal .'</font><br>';
    

    $nombreTmp="FichaCliente.pdf";
    //~ require_once('../../lib/tcpdf/tcpdf.php');
    require_once ('../../clases/imprimir.php');
    include_once('../../controllers/planImprimir.php');
    $ficheroCompleto=$rutatmp.'/'.$nombreTmp;
    $resultado=$ficheroCompleto;


?>
