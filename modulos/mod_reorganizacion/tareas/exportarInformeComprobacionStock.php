<?php

// @ Objetivo
// Descargar el informe final del ejercicio anterior, a partir de la composición que
// el propio navegador devuelve: es la misma que ya se pintó en pantalla tras la
// admisión, sin estado en servidor entre los dos pasos.
//
// Que no haya estado en servidor es lo que obliga a comprobarla al volver. Nada de
// lo que llega aquí lo ha establecido este sistema en esta petición: llega el
// resultado entero y llega el resumen con que salió, y solo comparándolos se
// distingue el resultado que se calculó de cualquier otra cosa con su misma forma.
// Sin esa comparación, el informe —que se archiva y que nadie vuelve a contrastar—
// se escribiría con lo que llegara.

$emision = new ClaseComprobacionStockEmision();

$composicion = json_decode(isset($_POST['composicion']) ? $_POST['composicion'] : '', true);
$admisible = $emision->composicionAdmisible(
    $composicion,
    isset($_POST['resumen']) ? $_POST['resumen'] : null
);

if (!$admisible['ok']) {
    // La respuesta de una descarga es el fichero, así que un motivo solo se puede
    // decir aquí negándose a producirlo. Se responde en texto y no como fichero
    // porque lo que hay que entregar es el motivo, no un informe con un motivo
    // dentro que se archivaría igual que uno bueno.
    http_response_code(422);
    header('Content-Type: text/plain; charset=utf-8');
    echo $admisible['motivo'];
    exit;
}

// El informe se compone en memoria y se entrega. No pasa por fichero temporal: esa
// escritura puede fallar sin que la descarga se entere, y entonces se entregaría un
// documento vacío con nombre de informe.
$contenido = $emision->contenidoDelInforme($composicion);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="informe_comprobacion_' . (int) $composicion['contexto']['ano'] . '.csv"');
header('Content-Length: ' . strlen($contenido));
echo $contenido;
exit;
