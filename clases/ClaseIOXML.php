<?php

class ClaseIOXML
{
    protected string $rutaArchivo;
    protected ?string $xsdArchivo; // XSD opcional
    protected ?SimpleXMLElement $xml = null; // XML cargado o generado

    public function __construct(string $rutaArchivo, ?string $xsdArchivo = null)
    {
        $this->rutaArchivo = $rutaArchivo;
        $this->xsdArchivo = $xsdArchivo;
    }

    /**
     * Cargar el XML desde la ruta del servidor
     * Si se definió un XSD, valida automáticamente
     */
    public function cargar(): SimpleXMLElement
    {
        if (!file_exists($this->rutaArchivo)) {
            throw new Exception("Archivo XML no encontrado: {$this->rutaArchivo}");
        }

        libxml_use_internal_errors(true);
        $this->xml = simplexml_load_file($this->rutaArchivo);

        if ($this->xml === false) {
            $errores = libxml_get_errors();
            libxml_clear_errors();
            $msg = "";
            foreach ($errores as $error) {
                $msg .= $error->message . "; ";
            }
            throw new Exception("Error al leer XML: {$msg}");
        }

        // Validar XSD si está definido
        if ($this->xsdArchivo) {
            $this->validarXSD();
        }

        return $this->xml;
    }

    /**
     * Guardar el XML cargado en la ruta del servidor
     */
    public function guardar($xml = null): bool
    {
        if ($xml !== null) {
            $this->setXML($xml);
        }
        if (!$this->xml) {
            throw new Exception("No hay XML cargado para guardar.");
        }

        $directorio = dirname($this->rutaArchivo);
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // Validar contra XSD si está definido
        if ($this->xsdArchivo) {
            $this->validarXSD();
        }

        // Guardar el XML en el archivo
        $resultado = $this->xml->asXML($this->rutaArchivo);
        if ($resultado === false) {
            throw new Exception("Error al guardar XML en: {$this->rutaArchivo}");
        }
        return true;
    }

    /**
     * Validar el XML cargado contra el XSD
     */
    public function validarXSD(): bool
    {
        if (!$this->xml) {
            throw new Exception("No hay XML cargado para validar.");
        }
        if (!$this->xsdArchivo || !file_exists($this->xsdArchivo)) {
            throw new Exception("Archivo XSD no encontrado: {$this->xsdArchivo}");
        }

        $doc = new DOMDocument();
        $doc->loadXML($this->xml->asXML());

        if (!$doc->schemaValidate($this->xsdArchivo)) {
            throw new Exception("XML no cumple con el XSD: {$this->xsdArchivo}");
        }

        return true;
    }

    /**
     * Cambiar la ruta del archivo
     */
    public function setRutaArchivo(string $ruta): void
    {
        $this->rutaArchivo = $ruta;
    }

    /**
     * Definir o cambiar el XSD
     */
    public function setXSD(string $xsdArchivo): void
    {
        $this->xsdArchivo = $xsdArchivo;
    }
    /**
     * Definir o cambiar el XML en memoria
     */
    public function setXML(SimpleXMLElement $xml): void
    {
        $this->xml = $xml;
    }


    /**
     * Obtener la ruta actual del archivo
     */
    public function getRutaArchivo(): string
    {
        return $this->rutaArchivo;
    }

    /**
     * Obtener la ruta del XSD actual
     */
    public function getXSD(): ?string
    {
        return $this->xsdArchivo;
    }

    /**
     * Obtener el XML cargado en memoria
     */
    public function getXML(): ?SimpleXMLElement
    {
        return $this->xml;
    }

    /**
     * Crear instancia a partir de un archivo subido por el usuario
     */
    public static function desdeSubida(string $inputName, ?string $xsd = null, string $directorioDestino = __DIR__ . '/uploads/'): ClaseIOXML
    {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("No se ha subido ningún archivo válido con el input '{$inputName}'.");
        }

        $tmpName = $_FILES[$inputName]['tmp_name'];
        $nombreArchivo = basename($_FILES[$inputName]['name']);

        if (!file_exists($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        $rutaFinal = $directorioDestino . $nombreArchivo;
        move_uploaded_file($tmpName, $rutaFinal);

        return new self($rutaFinal, $xsd);
    }

    /**
     * Enviar el XML al navegador para que se descargue en el PC del usuario
     */
    public function descargar(?string $nombreDescarga = null): void
    {
        if (!$this->xml) {
            throw new Exception("No hay XML cargado para descargar.");
        }

        if (!$nombreDescarga) {
            $nombreDescarga = basename($this->rutaArchivo);
        }

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
        header('Content-Length: ' . strlen($this->xml->asXML()));
        echo $this->xml->asXML();
        exit;
    }
}
