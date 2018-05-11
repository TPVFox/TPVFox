<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';

/**
 * Description of ClaseFamilias
 *
 * @author alagoro
 */
class ClaseFamilias extends Modelo {

    protected $tabla = 'familias';

    protected function cuentaHijos($padres){
        $nuestros = $padres;
        $sql = 'SELECT count(idFamilia) as contador '
                . ' FROM familias as FAM '
                . ' WHERE FAM.familiaPadre = ';
        foreach($padres as $indice => $padre) {           
          $resultado = $this->consulta($sql.$padre['idFamilia']);
          $nuestros[$indice]['hijos'] = $resultado['datos'][0]['contador'];
        }
        
        return $nuestros;
    }

    public function leer($idfamilia) {
        $sql = 'SELECT FAM.*'
                . ' FROM familias as FAM '
                . ' WHERE FAM.idFamilia =' . $idfamilia;
        $resultado = $this->consulta($sql);
            $resultado['datos'] = $this->cuentaHijos($resultado['datos']);
        return $resultado;
    }

    public function leerUnPadre($idpadre) {
        $sql = 'SELECT FAM.*, FAMPAD.familiaNombre as nombrepadre '
                . ' FROM familias as FAM '
                . ' LEFT OUTER JOIN familias as FAMPAD'
                . ' ON (FAM.familiaPadre=FAMPAD.idFamilia)'
                . ' WHERE FAM.familiaPadre =' . $idpadre
                . ' ORDER BY FAM.familiaNombre';
        $resultado = $this->consulta($sql);
            $resultado['datos'] = $this->cuentaHijos($resultado['datos']);
        return $resultado;
    }

    public function grabar($datos) {
        return $this->insert($datos);
    }
}

/* Para update
 * 
 * ALTER TABLE `familias` CHANGE `idFamilia` `idFamilia` INT(11) NOT NULL AUTO_INCREMENT;
 */

/*
 *                                 <input class="form-control" id='nombrePropietario' 
                                       value="{$fsc->getPropietarioNave('Nombrecompleto')}"/>

    $('#nombrePropietario').autocomplete({
        serviceUrl: 'index.php?page=propietarios',
        paramName: 'searchbyname',
        minChars: 2,
        onSelect: function (suggestion) {
            if (suggestion) {
                $('#nombrePropietario').val(suggestion.data.Nombrecompleto);
                $('#idPropietario').val(suggestion.data.ID);
                $('#telefono1Propietario').val(suggestion.data.Telefono1);
            }
        }
    });



    private function ajax_nombre_search() {
        $this->template = FALSE;
        $resultado = json_encode(['suggestions' => []]);
        $this->propietarios = $this->propietario->filtrar(['Nombrecompleto LIKE "%' . $this->propietario->filterByName . '%"']);
        if ($this->propietarios) {
            $json = [];
            foreach ($this->propietarios as $propietario) {
                $json[] = ['value' => $propietario->Nombrecompleto, 'data' => $propietario];
            }
            $resultado = json_encode(['suggestions' => $json]);
        }
        echo $resultado;
    }


 */
