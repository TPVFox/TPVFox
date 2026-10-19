<?php

// La entrada del módulo lleva al listado de productos relacionados. La ruta va completa: se
// llega aquí también por la carpeta sin barra final, y una relativa saldría del módulo.
header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/ListaRelaciones.php');
