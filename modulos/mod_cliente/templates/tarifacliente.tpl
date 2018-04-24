<!DOCTYPE html>
<html>
    <head>

        <meta name="language" content="es">
        <meta charset="UTF-8">
        <link rel="stylesheet" href="<tag:HostNombre />/css/bootstrap.min.css" type="text/css">
        <link rel="stylesheet" href="<tag:HostNombre />/css/template.css" type="text/css">

        <script src="<tag:HostNombre />/jquery/jquery-2.2.5-pre.min.js"></script>
        <script src="<tag:HostNombre />/css/bootstrap.min.js"></script>

    <header>
        <!-- Debería generar un fichero de php que se cargue automaticamente el menu -->
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
                        <span class="sr-only">Desplegar navegación</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">TpvFox</a>
                </div>
                <div class="collapse navbar-collapse navbar-ex1-collapse">
                    <ul class="nav navbar-nav navbar-left ">
                        <li><a href="/tpvfox/index.php">Home</a></li>
                        <li><a href="/tpvfox/modulos/mod_producto/ListaProductos.php">Productos</a></li>
                        <li><a href="/tpvfox/modulos/mod_cliente/ListaClientes.php">Clientes</a></li>
                        <li><a href="/tpvfox/modulos/mod_proveedor/ListaProveedores.php">Proveedores</a></li>
                        <li><a href="/tpvfox/modulos/mod_cierres/ListaCierres.php">Cierres</a></li>
                        <li><a href="/tpvfox/estatico">Documentacion</a></li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">Compras
                                <span class="caret"></span></a>
                            <ul class="dropdown-menu">

                                <li><a href="/tpvfox/modulos/mod_compras/pedidosListado.php">Pedidos</a></li>
                                <li><a href="/tpvfox/modulos/mod_compras/albaranesListado.php">Albaranes</a></li>
                                <li><a href="/tpvfox/modulos/mod_compras/facturasListado.php">Facturas</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">Ventas
                                <span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li><a href="/tpvfox/modulos/mod_tpv/tpv.php">Tickets</a></li>
                                <li><a href="/tpvfox/modulos/mod_venta/pedidosListado.php">Pedidos</a></li>
                                <li><a href="/tpvfox/modulos/mod_venta/albaranesListado.php">Albaranes</a></li>
                                <li><a href="/tpvfox/modulos/mod_venta/facturasListado.php">Facturas</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">Sistema
                                <span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li><a href="/tpvfox/modulos/mod_importar_sppg/Importar_sppg.php">Importar SPPG</a></li>
                                <li><a href="/tpvfox/modulos/mod_importar_virtuemart/Importar_virtuemart.php">Importar Virtuemart</a></li>
                                <li><a href="/tpvfox/modulos/mod_usuario/ListaUsuarios.php">Usuarios</a></li>
                                <li><a href="/tpvfox/modulos/mod_tienda/ListaTiendas.php">Tiendas</a></li>
                                <li><a href="/tpvfox/modulos/mod_copia_seguridad/CopiaSeguridad.php">Copia Seguridad</a></li>
                            </ul>
                        </li>

                    </ul>

                    <div class="nav navbar-nav navbar-right">

                        <span class="glyphicon glyphicon-user"></span>admin					
                        <a href="/tpvfox/plugins/controlUser/modalUsuario.php?tipo=cerrar">Cerrar</a>
                    </div>
                    <div class="nav navbar-nav navbar-right" style="margin-right:50px">
                        <div id="tienda">Alimentaria Longueicarp SL</div>

                    </div>
                </div>

            </div>
        </nav>
        <!-- Fin de menu -->
    </header>
    <script>
        // Declaramos variables globales
        var checkID = [];
    </script> 
    <!-- Cargamos fuciones de modulo. -->
    <script src="<tag:HostNombre />/modulos/mod_cliente/funciones.js"></script>
    <script src="<tag:HostNombre />/controllers/global.js"></script> 

</head>

<body>

    <div class="container">
        <h2 class="text-center"> Tarifa de Cliente </h2>

        <a  href="ListaClientes.php">Volver Atrás</a>

        <input type="text" style="display:none;" name="idTemporal" value=0>
        <div class="row" >
            <div class="col-md-8">
                <div class="form-group">
                    <label>Cliente:</label>
                    <input type="text" id="id_cliente" name="idCliente" 
                           data-obj= "cajaIdCliente" value="<tag:cliente.id />" size="2" placeholder='id' >
                    <input type="text" id="Cliente" name="Cliente" data-obj= "cajaCliente" 
                           placeholder="Nombre de cliente" value="<tag:cliente.nombre />" size="60" >
                </div>
            </div>
        </div>
        <!-- Tabla de lineas de productos -->
        <div class="row">
            <table id="tabla" class="table table-striped" >
                <thead>
                    <tr>
                        <th>Id Articulo</th>
                        <th>Referencia</th>
                        <th>Cod Barras</th>
                        <th>Descripcion</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="Row0" style=>  
                        <td><input id="idArticulo" type="text" name="idArticulo" 
                                   class="fox-buscar"
                                   placeholder="idArticulo" data-obj= "cajaidArticulo" 
                                   size="13" value=""  onkeydown="controlEventos(event)" />
                        </td>
                        <td><input id="Referencia" type="text" name="Referencia" 
                                   class="fox-buscar"
                                   placeholder="Referencia" data-obj="cajaReferencia" 
                                   size="13" value="" onkeydown="controlEventos(event)" />
                        </td>
                        <td><input id="Codbarras" type="text" name="Codbarras" 
                                   class="fox-buscar"
                                   placeholder="Codbarras" data-obj= "cajaCodBarras" 
                                   size="13" value="" data-objeto="cajaCodBarras" 
                                   onkeydown="controlEventos(event)" />
                        </td>
                        <td><input id="Descripcion" type="text" name="Descripcion" 
                                   class="fox-buscar"
                                   placeholder="Descripcion" data-obj="cajaDescripcion" 
                                   size="20" value="" onkeydown="controlEventos(event)" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="row">
            <div id="formulario" style="display: none">                   
                <table>
                    <tr>
                        <td>L</td>
                        <td><input type="text" placeholder="id" name="idArticulo" id="inputIdArticulo" /></td>
                        <td><input type="text" disabled name="descripcion" id="inputDescripcion" /></td>
                        <td><input type="text" placeholder="Precio sin iva" name="precioSiva" id="inputPrecioSin" /></td>
                        <td><input type="text" placeholder="% iva" name="ivaArticulo" id="inputIVA" /></td>
                        <td><input type="text" placeholder="precio con iva" name="precioCiva" id="inputPrecioCon" /></td>
                        <td><button id="btn-grabar-tc" class="btn btn-primary btn-sm">grabar</button> 
                            <button id="btn-cancelar-tc" class="btn btn-danger btn-sm">cancelar</button></td>
                    </tr>
                </table>
                <input type="hidden"  name="idClientes" id="idClientes" />
            </div>
        </div>

        <div class="row">
            <table id="tabla" class="table table-striped" >
                <thead>
                    <tr>
                        <th>L</th>
                        <th>Id Articulo</th>
                        <th>Descripcion</th>
                        <th>Precio S/IVA</th>
                        <th>% IVA</th>
                        <th>Precio C/IVA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    
                <loop:tarifa>
                    <tr>
                        <td>L</td>
                        <td><tag:tarifa[].idArticulo /></td>
                    <td><tag:tarifa[].descripcion /></td>
                    <td><tag:tarifa[].pvpSiva /></td>
                    <td><tag:tarifa[].ivaArticulo /></td>
                    <td><tag:tarifa[].pvpCiva /></td>
                    <td></td>
                    </tr>
                </loop:tarifa>
                </tbody>
            </table>
        </div>

    </div>


    <!-- Modal -->
    <div id="busquedaModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header btn-primary">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h3 class="modal-title text-center">	Titulo Provisorio...</h3>
                </div>
                <div class="modal-body">
                    <p>Some text in the modal.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>
    <script src="/tpvfox/modulos/mod_cliente/funciones.js"></script>
    <script src="/tpvfox/controllers/global.js"></script> 
    <!--    <script src="/tpvfox/lib/js/teclado.js"></script> -->
    <script src="/tpvfox/modulos/mod_cliente/tarifacliente.js"></script>

</body>
</html>








</div>
</body>
</html>
