<?php
// @ Objetivo
// El HTML de la pantalla de aplicar una relación: la elección del proveedor, la relación y el
// periodo —que se rellena con los meses o los días con entradas—, la versión del despiece que se
// aplica, el ajuste del reparto en un
// deslizador y el proveedor de traspaso, que solo se ve con un periodo de diciembre o de enero;
// los avisos; y el resultado, que pinta aplicarRelacion.js con lo que devuelve el cálculo.
// @ Parametros (de AplicarRelacion.php)
//      $avisos, $especiales, $relacionesAplicables, $proveedores y $puedePreparar.
?>
<div class="container">
    <div class="row">
        <div class="col-md-12 text-center">
            <h2>Aplicar relación</h2>
        </div>
        <div class="col-md-12">
            <p>Aplica un despiece que rige a lo que entró de su producto en un periodo: calcula el escandallo y deja preparado un albarán de compras, que se revisa y se guarda en «Albaranes».</p>

            <div id="avisosPantallaAplicacion">
                <?php foreach ($avisos as $aviso) { ?>
                    <div class="alert alert-<?php echo htmlspecialchars($aviso['tipo'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($aviso['mensaje'], ENT_QUOTES); ?></div>
                <?php } ?>
            </div>

            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="idProveedorAplicacion">Proveedor especial</label>
                    <select id="idProveedorAplicacion" class="form-control" onchange="cambiarDatoAplicacion('el proveedor')">
                        <option value="">Elige un proveedor</option>
                        <?php foreach ($especiales as $proveedor) { ?>
                            <option value="<?php echo (int) $proveedor['idProveedor']; ?>"><?php echo htmlspecialchars($proveedor['nombrecomercial'], ENT_QUOTES); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="numRelacionAplicacion">Relación</label>
                    <select id="numRelacionAplicacion" class="form-control" onchange="cambiarRelacionAplicacion()">
                        <option value="">Elige una relación</option>
                        <?php foreach ($relacionesAplicables as $relacion) {
                            $nombre = $relacion['entrada'];
                            if ($relacion['nombre'] !== null && $relacion['nombre'] !== '') {
                                $nombre = $relacion['nombre'];
                            } ?>
                            <option value="<?php echo (int) $relacion['numRelacion']; ?>"><?php echo htmlspecialchars($nombre . ' (' . $relacion['tipo'] . ')', ENT_QUOTES); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label for="tipoPeriodoAplicacion">Periodo</label>
                    <select id="tipoPeriodoAplicacion" class="form-control" onchange="cambiarTipoPeriodoAplicacion()">
                        <option value="mes">Un mes</option>
                        <option value="lote">Un lote (un día)</option>
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label for="periodoAplicacion">Mes o día</label>
                    <select id="periodoAplicacion" class="form-control" onchange="cambiarPeriodoAplicacion()" disabled>
                        <option value="">Elige primero el despiece</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="versionAplicacion">Versión del despiece</label>
                    <select id="versionAplicacion" class="form-control" onchange="cambiarDatoAplicacion('la versión')">
                        <option value="periodo">La que regía en el periodo</option>
                        <option value="vigente">La vigente hoy</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <p class="help-block" style="margin-top: 25px;">Con la primera se usan las proporciones del despiece en la fecha del albarán. Elige la vigente para aplicar un despiece nuevo a datos anteriores a su alta, o para quedarte con su versión final después de varios cambios. La pantalla dice qué versión se ha usado.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="ajustePesoAplicacion">Reparto del coste</label>
                    <input type="range" id="ajustePesoAplicacion" class="form-control" min="0" max="2" step="0.05" value="1" list="marcasAjusteAplicacion" oninput="cambiarAjusteAplicacion()">
                    <datalist id="marcasAjusteAplicacion">
                        <option value="0"></option>
                        <option value="1"></option>
                        <option value="2"></option>
                    </datalist>
                    <p class="help-block">
                        Peso: <strong id="ajustePesoTexto">1,00</strong> · Valor de mercado: <strong id="ajusteValorTexto">1,00</strong>.
                        Del 0 al 2: a la izquierda, todo el coste se reparte según el precio de mercado de las piezas; a la derecha, según sus kilos, igual por kg para todas. En el centro, la mitad por cada uno.
                    </p>
                </div>
                <div id="bloqueTraspasoAplicacion" class="col-md-5 form-group" style="display: none;">
                    <label for="idProveedorTraspasoAplicacion">Proveedor del cierre y la apertura del ejercicio</label>
                    <select id="idProveedorTraspasoAplicacion" class="form-control" onchange="cambiarDatoAplicacion('el proveedor de traspaso')">
                        <option value="">Ninguno</option>
                        <?php foreach ($proveedores as $proveedor) { ?>
                            <option value="<?php echo (int) $proveedor['idProveedor']; ?>"><?php echo htmlspecialchars($proveedor['nombrecomercial'], ENT_QUOTES); ?></option>
                        <?php } ?>
                    </select>
                    <p class="help-block">El periodo incluye el 31 de diciembre o el 1 de enero. Si el cierre o la apertura del ejercicio se hicieron con un proveedor, elígelo: sus albaranes de esos días no cuentan como movimientos del producto. Si no, déjalo en «Ninguno».</p>
                </div>
                <div class="col-md-3 form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button id="btnCalcularAplicacion" type="button" class="btn btn-default" onclick="calcularAplicacion()">
                            <span class="glyphicon glyphicon-refresh"></span> Calcular
                        </button>
                        <?php if ($puedePreparar) { ?>
                            <button id="btnPrepararAplicacion" type="button" class="btn btn-primary" onclick="prepararAlbaranAplicacion()" disabled>
                                <span class="glyphicon glyphicon-save"></span> Preparar albarán
                            </button>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div id="avisosAplicacion"></div>
            <div id="datosAplicacion"></div>
            <table id="escandalloAplicacion" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Pieza</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Precio de mercado</th>
                        <th>De dónde sale el precio</th>
                        <th>Coste por unidad</th>
                        <th>Coste</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
