<?php
include_once $URLCom . '/clases/ClaseIOXML.php';

/**
 * Gestiona la seleccion de productos por usuario usando un fichero XML en cache.
 * Fichero: cache/{idUsuario}_seleccion.xml
 */
class ClaseSeleccionProductos extends ClaseIOXML
{
    private int $idUsuario;

    public function __construct(int $idUsuario, string $dirCache)
    {
        $this->idUsuario = $idUsuario;
        $ruta = $dirCache . '/' . $idUsuario . '_seleccion.xml';
        parent::__construct($ruta);
    }

    /**
     * Devuelve array con los idArticulo seleccionados.
     */
    public function getIds(): array
    {
        if (!file_exists($this->getRutaArchivo())) {
            return [];
        }
        $xml = $this->cargar();
        $ids = [];
        foreach ($xml->item as $item) {
            $ids[] = (int) $item['idArticulo'];
        }
        return $ids;
    }

    /**
     * Devuelve cuantos productos hay seleccionados.
     */
    public function contar(): int
    {
        return count($this->getIds());
    }

    /**
     * Agrega un idArticulo a la seleccion. Si ya existe no lo duplica.
     */
    public function agregar(int $idArticulo): bool
    {
        $xml = $this->_cargarOCrear();
        // Comprobamos que no exista ya
        foreach ($xml->item as $item) {
            if ((int) $item['idArticulo'] === $idArticulo) {
                return true; // Ya existe
            }
        }
        $nuevo = $xml->addChild('item');
        $nuevo->addAttribute('idArticulo', (string) $idArticulo);
        return $this->guardar($xml);
    }

    /**
     * Quita un idArticulo de la seleccion.
     */
    public function quitar(int $idArticulo): bool
    {
        if (!file_exists($this->getRutaArchivo())) {
            return true;
        }
        $xml = $this->cargar();
        $dom = dom_import_simplexml($xml);
        foreach ($xml->item as $item) {
            if ((int) $item['idArticulo'] === $idArticulo) {
                $nodo = dom_import_simplexml($item);
                $nodo->parentNode->removeChild($nodo);
                break;
            }
        }
        return $this->guardar($xml);
    }

    /**
     * Elimina el fichero de cache (limpia toda la seleccion).
     */
    public function limpiar(): bool
    {
        $ruta = $this->getRutaArchivo();
        if (file_exists($ruta)) {
            return unlink($ruta);
        }
        return true;
    }

    // Carga el XML si existe, o crea uno nuevo vacio.
    private function _cargarOCrear(): SimpleXMLElement
    {
        if (file_exists($this->getRutaArchivo())) {
            return $this->cargar();
        }
        $xml = new SimpleXMLElement('<seleccion/>');
        $xml->addAttribute('usuario', (string) $this->idUsuario);
        $this->setXML($xml);
        return $xml;
    }
}
