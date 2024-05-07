-- ------ ESTE FICHERO ES RECOMENDABLE ANTES EJECUTARLO DEBEMOS TENER UNA COPIA DE SEGURIDAD BASES DATOS   -------
/* Este fichero es creado para ejecutar ante del fichero restricciones.sql que es la creacion relaciones entre las tablas
   Recomendables hacer los select primero, para ver que tablas pueden darte problemas y intentar resolverlas.
 */
 

-- PARA HACER EN CASO DE QUE FALLE Y HACERLO MANUAL:

UPDATE `facprolinea` SET idalbpro=null WHERE idalbpro=0; -- Cuando hay lineas facturas sin albaranes





