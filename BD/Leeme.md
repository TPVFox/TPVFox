# Base de datos — esquema, parches y migraciones

El esquema de numeración de versiones (X.Y.Z), el uso de `git describe` y la nomenclatura de los
parches están en el manual técnico, en [Cómo versionamos en TpvFox][versionado]. Este fichero no
los repite: cubre lo que ocurre en la base de datos.

La regla del manual sigue siendo válida: se aplican los parches posteriores a la versión actual.
El esquema base no la sustituye, la abrevia — hay un estado 0 definido por línea, así que se puede
partir de él en lugar de reproducir toda la cadena. Los dos caminos llegan al mismo sitio.

**Documentación relacionada**

- [Manual técnico de TpvFox][manual] — índice
- [Cómo versionamos en TpvFox][versionado]
- [Presentación e instalación de TpvFox][instalacion]

## Estructura

| Directorio | Contenido |
| --- | --- |
| `BDtpv/` | Esquemas base. Cada uno es una foto completa de la estructura en un punto concreto, sin datos |
| `Update/` | Parches incrementales, `install_update_vX.Y.Z.sql` |

## Líneas de versión y su estado 0

Cada línea mayor arranca con un parche que la abre. Cuando existe esquema base, se puede partir de
él en lugar de reproducir la cadena anterior.

| Línea | Se abre con | Esquema base |
| :-: | --- | --- |
| 0.0 | *(origen)* | — |
| 0.2 | `install_update_v0.2.0.sql` | — |
| 0.3 | `install_update_v0.3.0.sql` | `tpvfox_V_0_3-1.sql` |
| 0.4 | `install_update_v0.4.0.0.sql` | `tpvfox_V_0_4-2.84.sql` |

La 0.1 no figura como línea: tiene un único parche (`install_update_v0.1.13.sql`) y se pasó casi
directo a la 0.2. Ese parche pertenece al tramo previo a 0.2.

**No hay versión mínima soportada**: cualquier base es alcanzable, porque las líneas sin esquema
base se recorren por la cadena de parches completa. El esquema base es un atajo, no un requisito.

Emitir uno para una línea que no lo tiene solo compensa si hay instalaciones vivas ahí y la cadena
se hace larga. Mientras no las haya, sería mantener un fichero que nadie usa.

> `reiniciar_BD.sql` **no es un esquema base**. Vacía los datos de una base ya creada — 64
> `TRUNCATE` y 4 `INSERT` de siembra — y presupone la estructura.

## Esquema base

Contiene la estructura completa: tablas, columnas, índices, claves foráneas y vistas. Define el
estado 0 de una línea y evita reproducir la cadena de parches que lleva hasta él.

El de la línea actual es `BDtpv/tpvfox_V_0_4-2.84.sql`.

Se genera siempre desde una base real, nunca a mano:

```bash
mysqldump -u USUARIO -p --no-data --skip-dump-date \
          --routines --triggers --events BASE \
  | sed -E 's/DEFINER=`[^`]+`@`[^`]+` //g' > salida.sql
```

Cada opción está por un motivo:

| Opción | Motivo |
| --- | --- |
| `--no-data` | Solo estructura |
| `--skip-dump-date` | Sin fecha en la cabecera; así un `diff` entre dos volcados muestra solo cambios reales |
| `--routines --triggers --events` | Incluye vistas, procedimientos y disparadores. Sin esto las vistas no viajan |
| `sed` sobre `DEFINER` | Elimina el usuario definidor. Si se deja, el fichero solo importa como ese usuario y las vistas fallan donde no exista |

La cabecera de versión y el historial de cambios del fichero anterior se conservan al regenerar.
El volcado no los trae.

## Parches

Nomenclatura y criterio de versión: ver [Cómo versionamos en TpvFox][versionado].

Cada línea tiene un esquema base que define su estado 0. Partiendo de él solo se aplican los
parches posteriores: los anteriores ya están incluidos, y repetirlos sería trabajo hecho dos veces
y, en los que tocan datos, corrupción.

Los parches se conservan todos. Uno anterior al esquema base de su línea no desaparece: deja de
estar en el camino de ejecución de quien parte de ese esquema, y sigue disponible para quien venga
de más atrás.

Un parche operativo debe poder ejecutarse dos veces sin romper nada:

```sql
ALTER TABLE x ADD COLUMN IF NOT EXISTS y ...;
CREATE TABLE IF NOT EXISTS ...;
CREATE OR REPLACE VIEW ...;
```

Los que modifican datos (`UPDATE`, `INSERT`, `DELETE`) **no son repetibles por naturaleza**. Deben
condicionarse o marcarse como no repetibles, y en ese caso la protección es el registro en la
tabla `migraciones`.

## Vistas

Se crean siempre con `SQL SECURITY INVOKER`:

```sql
CREATE OR REPLACE SQL SECURITY INVOKER VIEW nombre AS ...
```

Sin esa cláusula la vista queda ligada al usuario que la creó, y deja de funcionar en cualquier
instalación donde ese usuario no exista.

## Tabla `migraciones`

Registra qué se ha aplicado en cada base.

| Columna | Contenido |
| --- | --- |
| `version` | Número de versión, con cada segmento a 3 dígitos para que el orden numérico coincida con el semántico: `0.4.2.84` → `4002084` |
| `migration_name` | Nombre del fichero |
| `start_time` | Inicio |
| `end_time` | Fin. Si está vacío, la migración no terminó |
| `breakpoint` | Marca de parada manual |

Una base sin filas en esta tabla **no está sin migrar: está sin registrar**. Para esas hay que
determinar el estado comparando su estructura contra el esquema base.

Esta tabla resuelve la limitación que el manual técnico deja anotada al final del artículo de
versionado: saber en qué versión está una copia sin depender de un repositorio activo. El dato
deja de inferirse y pasa a estar en la propia base.

## Instalación y actualización son dos procesos distintos

**Instalación — base nueva.** Carga el esquema base vigente y registra en `migraciones` todo lo
que ese esquema ya incluye. **No ejecuta parches**: el esquema base ya los contiene, y ejecutar los
que tocan datos dejaría valores que no corresponden.

**Actualización — base existente.** Lee `migraciones` y aplica solo los parches pendientes, en
orden. Si la base está por debajo del esquema base vigente, no se actualiza con los parches de la
línea actual: primero hay que llevarla al esquema base correspondiente.

Ninguno de los dos hace el trabajo del otro.

## Nombres en minúscula

Las bases y las tablas se nombran en minúscula.

> Nota original: «Guardamos la BD sin datos. Me encontré con el problema que a la hora de importar,
> las mayúsculas en tablas y bases de datos no lo hace correctamente. Por eso el motivo del cambio
> de nombres de BD y tablas.»

El motivo es que el tratamiento de mayúsculas en identificadores depende del sistema de ficheros y
de la configuración del servidor. En minúscula el comportamiento es el mismo en todos.

[manual]: https://ayuda.svigo.es/index.php/portafolios/83-tpvfox-gestion-de-empresa/95-manual-tecnico-tpvfox
[versionado]: https://ayuda.svigo.es/index.php/portafolios/83-tpvfox-gestion-de-empresa/95-manual-tecnico-tpvfox/264-como-versionamos-en-tpvfox
[instalacion]: https://ayuda.svigo.es/index.php/portafolios/83-tpvfox-gestion-de-empresa/247-presentacion-y-instalacion-de-tpvfox
