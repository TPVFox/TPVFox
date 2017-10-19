<div>
<nav class="col-sm-2" id="myScrollspy">
	<a class="text-ritght" href="./">Volver Atrás</a>
</nav>
<h2>Fichero header.</h2>
<p> El fichero header es el encargado de mostrar los items que puede ver el usuario.</p>

<h4>¿Cosas que debemos saber de este fichero?</h4>
<p>A este fichero llegamos despues de cargar head, donde cargamos ficheros y conexiones , comprobamos si hay session y usuarios.</p>
<p>La variable $TPVsession nos indica:</p>
<pre>
<b>print_r($TPVsession);</b>
<code>
Array
(
    [SessionTpv] => Array
        (
            [estado] => Correcto / Erroneo
        )

)
</code>
</pre>
<p> Solo nos indica mas cosas si te acabas de loguear.</p>
<p> La variable $_SESSION una vez logueado:</p>
<pre>
<b>	print_r($_SESSION);</b>
<code>
Array
(
    [estadoTpv] => Correcto
    [N_Pagina_Abiertas] => 2
    [usuarioTpv] => Array
        (
            [login] => monse
            [nombre] => Monse
            [id] => 2
            [group_id] => 0
        )

    [tiendaTpv] => Array
        (
            [idTienda] => 1
            [razonsocial] => Alejandro Grueiro Pazos
            [telefono] => 665 607 239
            [direccion] => Paseo Matutino, 12.- 1B
            [NombreComercial] => Vapeagrow
            [nif] => 32661089P
            [ano] => 2017
            [estado] => Activo
        )

)
</code>
</pre>
</div>
