<?php
// Puerta de sesión de los tareas.php de cada módulo.
// Se incluye después de inicial.php, que abre la sesión y deja $_SESSION['estadoTpv'].

function sesionCorrecta()
{
	// @ Objetivo:
	// Saber si la petición llega con una sesión iniciada.
	// @ Devolvemos:
	// (bool) true si el usuario entró con su usuario y contraseña.
	$respuesta = false;
	if (isset($_SESSION['estadoTpv']) && $_SESSION['estadoTpv'] === 'Correcto') {
		$respuesta = true;
	}
	return $respuesta;
}

function exigirSesion()
{
	// @ Objetivo:
	// Cortar la petición si no hay sesión iniciada, antes de que tareas.php haga nada.
	// Responde 401 con el error en JSON, como el resto de respuestas de tareas.php.
	if (!sesionCorrecta()) {
		http_response_code(401);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => 'No autenticado'));
		exit;
	}
}
