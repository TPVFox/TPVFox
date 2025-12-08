<?php
class ClaseParametros 
{
    private $ficheros;      // Array de ficheros XML
    private $root;          // Objeto SimpleXML raíz
    private $raiz = 'Si';   // Indica si root es el principal
    private $Array_Elementos;
    private $rutaFichero;   // Ruta del fichero principal
    private $dirData;       // Carpeta /data para guardados

    public function __construct($fichero){
        $this->ficheros = [$fichero];
        $this->rutaFichero = $fichero;

        // Carpeta data dentro del módulo
        $this->dirData = dirname($fichero) . '/cache/';

        // Crear directorio si no existe
        if (!is_dir($this->dirData)) {
            mkdir($this->dirData, 0755, true);
        }

        // Nombre del fichero guardado
        $fichero_guardado = $this->dirData . basename($fichero);

        // Si existe versión guardada, se carga
        if (file_exists($fichero_guardado)){
            $this->ficheros = [$fichero_guardado];
            $this->rutaFichero = $fichero_guardado;
        }

        $this->root = $this->crearXml();

        // Cargar includes si existen
        $includes = $this->Xpath('includes/fichero','Valores');
        if (count($includes) > 0){
            foreach ($includes as $Nuevo_fichero){
                $this->root = $this->crearXml($Nuevo_fichero);
            }
        }
    }

    public function crearXml($Nuevo_fichero=''){
        if ($Nuevo_fichero !== '' && !in_array($Nuevo_fichero, $this->ficheros)){
            $this->ficheros[] = $Nuevo_fichero;
        }

        $objetoXml = [];
        $objetoDOM = [];

        foreach ($this->ficheros as $Num_fichero => $fichero) {
            $objetoXml[$Num_fichero] = simplexml_load_file($fichero);
            if (!$objetoXml[$Num_fichero]) {
                echo 'Error al cargar fichero ' . $fichero;
                exit;
            }
            $objetoDOM[$Num_fichero] = dom_import_simplexml($objetoXml[$Num_fichero]);
        }

        if (count($objetoXml) > 1){
            foreach ($this->ficheros as $Num_fichero => $fichero){
                if ($Num_fichero > 0){
                    $domXml = $objetoDOM[0]->ownerDocument->importNode($objetoDOM[$Num_fichero], TRUE);
                    $objetoDOM[0]->appendChild($domXml);
                }
            }
        }

        return $objetoXml[0];
    }

    public function ArrayElementos($elementos){
        $respuesta = [];
        foreach ($this->root->$elementos as $elemento){
            $c = 0;
            foreach($elemento as $key=>$valor){
                $atributos = '';
                if ($valor->attributes()){
                    $atributos = $valor->attributes();
                    foreach ((array)$atributos as $atributo){
                        $array_atributos = $atributo;
                        $array_atributos['valor'] = (string)$valor;
                        $obj_atributos = (object)$array_atributos;
                    }
                }

                if (count($elemento->$key) === 1 && gettype($atributos) ==='string' ){
                    $respuesta[$key]=(string)$valor;
                } else {
                    if (count($elemento->$key) >1){
                        if (gettype($atributos) !=='string'){
                            $respuesta[$key][$c]=$obj_atributos;
                        } else {
                            $respuesta[$key][$c]['valor']=(string)$valor;
                        }
                        $c++;
                    } else {
                        if (gettype($atributos) !=='string'){
                            $respuesta[$key]=$obj_atributos;
                        } else {
                            $respuesta[$key]['valor']=(string)$valor;
                        }
                    }
                }
            }
        }
        return $respuesta;
    }

    public function Xpath($elementos,$tipo_respuesta=''){
        $respuesta = $this->root->xpath($elementos);
        if ($tipo_respuesta === '' || $tipo_respuesta === 'Objetos'){
            return $respuesta;
        } elseif ($tipo_respuesta === 'Valores'){
            return $this->Valor($respuesta);
        } else {
            echo 'Error: tipo_respuesta inválido';
            exit;
        }
    }

    public function Valor($elementos){
        $respuesta = [];
        foreach ($elementos as $elemento){
            $respuesta[] = trim((string)$elemento);
        }
        return $respuesta;
    }

    public function getFicheros(){
        return $this->ficheros;
    }

    public function getRoot(){
        return $this->root;
    }

    public function setRoot($Nuevo_XML){
        $this->root = $Nuevo_XML;
        $this->raiz = 'No';
    }

    public function getNode($xpath){
        $resultado = $this->root->xpath($xpath);
        return !empty($resultado) ? $resultado[0] : null;
    }

    public function setNodeValue($xpath, $valor){
        $nodos = $this->root->xpath($xpath);
        if (!empty($nodos)){
            $nodos[0][0] = $valor;
            return true;
        }
        return false;
    }

    public function setNodeAttribute($xpath, $atributo, $valor){
        $nodos = $this->root->xpath($xpath);
        if (!empty($nodos)){
            $nodos[0][$atributo] = $valor;
            return true;
        }
        return false;
    }

    public function save($fichero = ''){
        if ($fichero === ''){
            $nombre = basename($this->rutaFichero);
            $fichero = $this->dirData . $nombre;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->root->asXML());

        return $dom->save($fichero) !== false;
    }
}
?>
