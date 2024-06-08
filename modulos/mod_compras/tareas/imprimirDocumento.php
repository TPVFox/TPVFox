<?php
//@Objetivo:
//Imprimir un documento , dependiendo de donde venga se pone el nombre y envía todos los datos  
//a la función montarHTMLimprimir que lo que realiza es simplemente montar el html una parte copn la cabecera y 
//otra con el cuerpo del documento
//debajo cargamos las clases de imprimir y la plantilla una vez generada y lista la plantilla devolvemos la ruta
//para así desde javascript poder abrirla
$id=$_POST['id'];
$dedonde=$_POST['dedonde'];
$idTienda=$_POST['idTienda'];
$nombreTmp=$dedonde."compras.pdf";
$Ctienda=new ClaseTienda();
$datosTienda=$Ctienda->obtenerUnaTienda($idTienda)['datos']['0'];
$def_dedonde = array(
    'factura' => array( 'numero'=>'Numfacpro',
                        'texto' =>'Factura',
                        'clase' =>$CFac,
                        ),
    'albaran' => array( 'numero'=>'Numalbpro',
                        'texto' =>'Albaran',
                        'clase' =>$CAlb,
                        ),
    'pedido' => array( 'numero'=>'Numpedpro',
                        'texto' =>'Pedido',
                        'clase' =>$CPed,
                        ),
);
foreach ($def_dedonde as $key=>$def){
    if($dedonde === $key){
        $clase = $def['clase'];
        $metodoDatos = 'datos'.$def['texto'];
        $metodoProductos = 'Productos'.$def['texto'];
        $campo=$def['numero'];
        $texto = $def['texto'].' de Proveedor';
        // Ahora obtenemos datos y productos con los metodos de la clase de donde venga (factura, albaran, pedido)
        $datos=$clase->$metodoDatos($id);
        $productosAdjuntos=$clase->$metodoProductos($id);
        $numero=$datos[$campo];
        $SuNumero = '';
        if (isset($datos['Su_numero'])){
            $SuNumero = $datos['Su_numero'];
        }
    }
}

if ($dedonde=="factura"){
    $SuNumero = $datos['Su_num_factura'];
    $adjuntos=$CFac->albaranesFactura($id);
    if ($adjuntos){
         $modifAdjunto=modificarArrayAdjunto($adjuntos, $BDTpv, "factura");
         $adjuntos=json_decode(json_encode($modifAdjunto), true);
    }
    $alb_html=[];
    foreach ($adjuntos as $adjunto){ 
            $total=0;
            $totalSiva=0;
            $suNumero="";
        if(isset($adjunto['total'])){
            $total=$adjunto['total'];
        }
        if(isset($adjunto['totalSiva'])){
            $totalSiva=$adjunto['totalSiva'];
        }
        $date_adjunto =date_create($adjunto['fecha']);
        $fecha_adjunto=date_format($date_adjunto,'d-m-Y');
        $alb_html[]='<tr><td colspan="2"><b><font size="9">Nun Alb:'.$adjunto['NumAdjunto'].'</font></b></td><td><b><font size="9">'
                .$fecha_adjunto.'</font></b></td>'
                .' <td colspan="2"><b><font size="9">Total sin iva : '.$totalSiva.'</font></b></td>'
                .'<td colspan="2"><b><font size="9">Total con iva : '.$total.'</font></b></td></tr>';
    }
    $alb_html=array_reverse($alb_html);
}

if ($dedonde=="albaran"){
    $adjuntos=$CAlb->PedidosAlbaranes($id);
    if ($adjuntos){
         $modifAdjunto=modificarArrayAdjunto($adjuntos, $BDTpv, "albaran");
         $adjuntos=json_decode(json_encode($modifAdjunto), true);
    }
    $alb_html=[];
    foreach ($adjuntos as $adjunto){ 
            $total=0;
            $totalSiva=0;
            $suNumero="";
        if(isset($adjunto['total'])){
            $total=$adjunto['total'];
        }
        if(isset($adjunto['totalSiva'])){
            $totalSiva=$adjunto['totalSiva'];
        }
        $date_adjunto =date_create($adjunto['fecha']);
        $fecha_adjunto=date_format($date_adjunto,'d-m-Y');
        $alb_html[]='<tr><td colspan="2"><b><font size="9">Pedido:'.$adjunto['NumAdjunto'].'</font></b></td><td><b><font size="9">'
                .$fecha_adjunto.'</font></b></td>'
                .' <td colspan="2"><b><font size="9">Total sin iva : '.$totalSiva.'</font></b></td>'
                .'<td colspan="2"><b><font size="9">Total con iva : '.$total.'</font></b></td></tr>';
    }
    $alb_html=array_reverse($alb_html);
}

$date=date_create($datos['Fecha']);
$datosProveedor=$CProveedores->buscarProveedorId($datos['idProveedor']);
$productosDEF=modificarArrayProductos($productosAdjuntos);
$Datostotales = recalculoTotales($productosDEF);  
$fecha="";
if (isset ($date)){
    $fecha=date_format($date,'d-m-Y');
}
$cabecera=  '<table>
                <tr>
                    <td><font size="20">'.$texto.'</font></td>
                    <td><font size="9"><b>Número:</b>'.$numero.' <b>Su Numero:</b>'.$SuNumero.'<br><b>Fecha:</b>'.$fecha.'</font></td>
                </tr>
            </table>'.
            '<hr style="color:black ; cap:0;join:0;dash:1;phase:0;"/>'.
            '<table>
                <tr>
                    <td>'.
                        '<font size="12">'.$datosTienda['NombreComercial'].'</font><br>'.
                        '<font size="9">'.$datosTienda['razonsocial'].'</font><br>'.
                        '<font size="9"><b>Direccion:</b>'.$datosTienda['direccion'].'</font><br>'.
                        '<font size="9"><b>NIF: </b>'.$datosTienda['nif'].'</font><br>'.
                        '<font size="9"><b>Teléfono: </b>'.$datosTienda['telefono'].'</font><br>'.
                    '</td>'.
                    '<td>'.
                        '<font size="9"><b>Datos de Proveedor:</b></font><br>'.
                        '<font size="12">'.$datosProveedor['nombrecomercial'].'</font><br>'.
                        '<font size="9">'.$datosProveedor['razonsocial'].'</font><br>'.
                        '<font size="9"><b>Direccion:</b>'.$datosProveedor['direccion'].'</font><br>'.
                        '<font size="9"><b>NIF: </b>'.$datosProveedor['nif'].'</font><br>'.
                        '<font size="9"><b>Teléfono: </b>'.$datosProveedor['telefono'].'</font><br>'.
                    '</td>'.
                '</tr>'.
            '</table>'.
            '<table WIDTH="100%" border="1px" ALIGN="center">'.
                '<tr>'.
                    '<td WIDTH="5%"><font size="9"><b>Linea</b></font></td>'.
                    '<td WIDTH="10%"><font size="9"><b>ID</b></font></td>'.
                    '<td WIDTH="17%"><font size="9"><b>Su Referencia</b></font></td>'.
                    '<td WIDTH="50%"><font size="9"><b>Descripción del producto</b></font></td>'.
                    '<td WIDTH="7%"><b><font size="9">Cant.</font></b></td>'.
                    '<td WIDTH="8%"><b><font size="9">Precio</font></b></td>'.
                    '<td WIDTH="8%"><b><font size="9">Importe</font></b></td>'.
                    '<td WIDTH="5%"><b><font size="9">IVA</font></b></td>'.
                '</tr>'.
            '</table>';
$html ='<table WIDTH="100%" border="1px">';
$i=0;
$numAdjunto=0;
$numAdjuntoProd=0;
$productosDEF= $productosAdjuntos;
foreach($productosDEF as $producto){
    if($dedonde=="factura"){
        $numAdjuntoProd=$producto['idalbpro'];
    }
    if($dedonde=="albaran"){
        $numAdjuntoProd=$producto['idpedpro'];
    }
    if($numAdjuntoProd<>$numAdjunto){
        if(isset($alb_html[$i])){
        $html .= $alb_html[$i];
        $numAdjunto=$numAdjuntoProd;
    }
        $i++;
    }
    if ($producto['estadoLinea']=='Activo'){
        $refPro="";
       if (strlen($producto['ref_prov'])>0){
            $refPro=$producto['ref_prov'];
        }
        $importe=$producto['ncant']*$producto['costeSiva'];
    
        $html .='<tr><td ALIGN="center" WIDTH="5%"><font size="8">'.$producto['nfila'].'</font></td>'
        .'<td WIDTH="10%"><font size="8"> &nbsp;'.$producto['idArticulo'].'</font></td>'
        .'<td WIDTH="17%"><font size="8"> &nbsp;'.$refPro.'</font></td>'
        .'<td WIDTH="50%"><font size="8"> &nbsp;'.$producto['cdetalle'].'</font></td>'
        .'<td ALIGN="right" WIDTH="7%"><font size="8">'.number_format($producto['ncant'],2).'  &nbsp;&nbsp;</font></td>'
        .'<td ALIGN="right" WIDTH="8%"><font size="8">'.number_format($producto['costeSiva'],2).'  &nbsp;&nbsp;</font></td>'
        .'<td ALIGN="right" WIDTH="8%"><font size="8">'.number_format($importe,2).'  &nbsp;&nbsp;</font></td>'
        .'<td ALIGN="right" WIDTH="5%"><font size="8">'.number_format($producto['iva'],0).'%  &nbsp;&nbsp;</font></td>'
        .'</tr>';
    }
}

$html .=<<<EOD
</table><br><br><br><br>
<table WIDTH="70%" border="1px"><tr><th>Tipo</th><th>Base</th><th>IVA</th></tr>
EOD;
if (isset($Datostotales)){
    // Montamos ivas y bases
    foreach ($Datostotales['desglose'] as  $iva => $basesYivas){
        $base= number_format($basesYivas['base'],2);
        $importe_iva = number_format($basesYivas['iva'],2);
        $html.=<<<EOD
<tr><td ALIGN="right">$iva% &nbsp;</td><td ALIGN="right">$base &nbsp;</td><td ALIGN="right">$importe_iva &nbsp;</td></tr>
EOD;
    }
}
$html .='</table>';
$html .='<p align="right"> TOTAL: ';
$html .=(isset($Datostotales['total']) ? '<font size="20">'.number_format($Datostotales['total'],2).'</font>' : '');
$html .='</p>';
