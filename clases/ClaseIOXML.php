<?php

class ClaseIOXML
{
    protected string $rutaArchivo;
    protected ?string $xsdArchivo; // XSD opcional

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
        $xml = simplexml_load_file($this->rutaArchivo);

        if ($xml === false) {
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
            $this->validarXSD($this->xsdArchivo);
        }

        return $xml;
    }

    /**
     * Guardar un objeto SimpleXMLElement en la ruta del servidor
     */
    public function guardar(SimpleXMLElement $xml): bool
    {
        $directorio = dirname($this->rutaArchivo);

        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // Antes de guardar, validar contra XSD si está definido
        if ($this->xsdArchivo) {
            $this->validarXSD($this->xsdArchivo);
        }

        return $xml->asXML($this->rutaArchivo) !== false;
    }

    /**
     * Validar XML contra un XSD
     */
    public function validarXSD(string $xsdArchivo): bool
    {
        if (!file_exists($this->rutaArchivo)) {
            throw new Exception("Archivo XML no encontrado: {$this->rutaArchivo}");
        }
        if (!file_exists($xsdArchivo)) {
            throw new Exception("Archivo XSD no encontrado: {$xsdArchivo}");
        }

        $doc = new DOMDocument();
        $doc->load($this->rutaArchivo);

        if (!$doc->schemaValidate($xsdArchivo)) {
            throw new Exception("XML no cumple con el XSD: {$xsdArchivo}");
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
        if (!file_exists($this->rutaArchivo)) {
            throw new Exception("Archivo XML no encontrado: {$this->rutaArchivo}");
        }

        if (!$nombreDescarga) {
            $nombreDescarga = basename($this->rutaArchivo);
        }

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
        header('Content-Length: ' . filesize($this->rutaArchivo));
        readfile($this->rutaArchivo);
        exit; // termina la ejecución para que no se envíe nada más
    }
}
