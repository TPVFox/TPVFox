// Sesión caducada en una pantalla abierta.
// tareas.php responde 401 cuando la petición llega sin sesión. En lugar de seguir como si se hubiera
// guardado, se recarga la página: sin sesión, la cabecera común enseña el formulario de acceso.
$(document).ajaxError(function (evento, peticion, ajustes) {
	if (peticion.status === 401) {
		console.log('Petición rechazada por sesión caducada: ' + ajustes.url + '. Se recarga la página para volver a entrar.');
		window.location.reload();
	}
});
