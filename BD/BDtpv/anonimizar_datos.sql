# La idea es sustituir los campos sensibles de una base datos con datos
# para hacer una base datos ejemplo.
# utilizando la instruccion :
# UPDATE `tabla` SET campo = REPLACE(campo,'texto a buscar','nuevo texto')
# Variables que debemos cambiar en cada vez anonimizamos una tabla.
SET @NumeroBuscar=9, @NumeroCambiar=2;
SET @NumeroBuscarDos=7, @NumeroCambiarDos=2;

SET @LetraBuscar="a", @LetraCambiar="f";
SET @LetraBuscarDos="e", @LetraCambiarDos="f";
SET @LetraBuscarTres="o", @LetraCambiarTres="e";

# Tablas y campos a cambiar:
UPDATE `clientes` SET Nombre = REPLACE(nombre,@LetraBuscar,@LetraCambiar);
UPDATE `clientes` SET Nombre = REPLACE(nombre,@LetraBuscarDos,@LetraCambiarDos);

UPDATE `clientes` SET razonsocial = REPLACE(razonsocial,@LetraBuscar,@LetraCambiar);
UPDATE `clientes` SET razonsocial = REPLACE(razonsocial,@LetraBuscarDos,@LetraCambiarDos);

UPDATE `clientes` SET direccion = REPLACE(direccion,@LetraBuscarDos,@LetraCambiarDos);
UPDATE `clientes` SET direccion = REPLACE(direccion,@LetraBuscarTres,@LetraCambiarTres);

UPDATE `clientes` SET nif = REPLACE(nif,@NumeroBuscar,@NumeroCambiar);
UPDATE `clientes` SET telefono = REPLACE(telefono,@NumeroBuscar,@NumeroCambiar);
UPDATE `clientes` SET movil = REPLACE(movil,@NumeroBuscar,@NumeroCambiar);
UPDATE `clientes` SET email = REPLACE(email,@LetraBuscar,@LetraCambiar);
UPDATE `clientes` SET email = REPLACE(email,@LetraBuscarTres,@LetraCambiarTres);

UPDATE `proveedores` SET nombrecomercial = REPLACE(nombrecomercial,@LetraBuscar,@LetraCambiar);
UPDATE `proveedores` SET nombrecomercial = REPLACE(nombrecomercial,@LetraBuscarDos,@LetraCambiarDos);
UPDATE `proveedores` SET nombrecomercial = REPLACE(nombrecomercial,@LetraBuscarTres,@LetraCambiarTres);

UPDATE `proveedores` SET razonsocial = REPLACE(razonsocial,@LetraBuscar,@LetraCambiar);
UPDATE `proveedores` SET razonsocial = REPLACE(razonsocial,@LetraBuscarDos,@LetraCambiarDos);
UPDATE `proveedores` SET razonsocial = REPLACE(razonsocial,@LetraBuscarTres,@LetraCambiarTres);

UPDATE `proveedores` SET nif = REPLACE(nif,@NumeroBuscar,@NumeroCambiar);
UPDATE `proveedores` SET nif = REPLACE(nif,@NumeroBuscarDos,@NumeroCambiarDos);

UPDATE `proveedores` SET telefono = REPLACE(telefono,@NumeroBuscar,@NumeroCambiar);
UPDATE `proveedores` SET telefono = REPLACE(telefono,@NumeroBuscarDos,@NumeroCambiarDos);

UPDATE `proveedores` SET fax = REPLACE(fax,@NumeroBuscar,@NumeroCambiar);
UPDATE `proveedores` SET fax = REPLACE(fax,@NumeroBuscarDos,@NumeroCambiarDos);


UPDATE `proveedores` SET movil = REPLACE(movil,@NumeroBuscar,@NumeroCambiar);
UPDATE `proveedores` SET movil = REPLACE(movil,@NumeroBuscarDos,@NumeroCambiarDos);

UPDATE `proveedores` SET email = REPLACE(email,@LetraBuscar,@LetraCambiar);
UPDATE `proveedores` SET email = REPLACE(email,@LetraBuscarTres,@LetraCambiarTres);


UPDATE `tiendas` SET nif = REPLACE(nif,@NumeroBuscar,@NumeroCambiar);
UPDATE `tiendas` SET nif = REPLACE(nif,@NumeroBuscar,@NumeroCambiar);

UPDATE `tiendas` SET telefono = REPLACE(telefono,@NumeroBuscar,@NumeroCambiar);
UPDATE `tiendas` SET telefono = REPLACE(telefono,@NumeroBuscarDos,@NumeroCambiarDos);

UPDATE `tiendas` SET key_api= "0000";

# Reseteamos contraseña admin
UPDATE `usuarios` SET password = '21232f297a57a5a743894a0e4a801fc3'; 

