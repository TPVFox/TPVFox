# Tests de tpvfox

Suite de PHPUnit para **caracterizar el comportamiento actual** de tpvfox antes
de corregir vulnerabilidades: red de seguridad que permite validar que la
funcionalidad sigue igual tras los cambios.

## Requisitos

- El dev shell del proyecto (Nix + direnv): al hacer `cd` a la carpeta se cargan
  PHP 8.2, Composer y el cliente de BD (via `../../shell.nix`).
- Docker para la base de datos de test.

## Puesta en marcha

```bash
# 1) Base de datos de test (MariaDB con el esquema cargado, efímera en tmpfs)
docker compose -f docker-compose.test.yml up -d

# 2) Dependencias de test (una vez)
composer install

# 3) Ejecutar la suite
composer test          # o: ./vendor/bin/phpunit --testdox

# Al terminar
docker compose -f docker-compose.test.yml down
```

`tests/bootstrap.php` genera `configuracion.php` (gitignored) apuntando a la BD
de test y prepara el entorno legacy (globals `$RutaServidor`/`$HostNombre`,
`include_path`). No hace falta configurar nada a mano.

## Estructura

- `tests/Unit/` — tests sin BD.
  - `ModeloSqlTest` — SQL de la capa de datos (`claseModeloP`); incluye escapado de valores (SQLi).
  - `RecalculoTotalesTest` — cálculo de totales/desglose del ticket (`recalculoTotales`).
  - `AuthGuardTest` — guard de autenticación de los endpoints AJAX.
  - `TareasGuardCoberturaTest` — todos los `tareas.php` exigen sesión (salvo cron).
  - `OrderByTest` — el ORDER BY de la paginación rechaza campos inyectables.
- `tests/Integration/` — tests contra la BD de test (transacción revertida por test).
  - `LoginTest` — credenciales (`ClaseSession::comprobarUser`): admin/admin, bcrypt, y no-bypass por inyección UNION.
  - `ClienteCrudTest` — ciclo crear→leer→modificar→borrar por la capa de datos.
  - `BusquedaClienteTest` — búsqueda por id/nombre (clase `Cliente`), incluye no-inyectable.

Funciones puras atrapadas en ficheros con includes de efectos colaterales (p.ej.
`recalculoTotales`) se extraen aisladas con `tpvfox_load_function` (ver bootstrap).

## Notas sobre el código legacy

- Algunos tests documentan **a propósito** comportamiento vulnerable (p.ej. el
  SQL sin escapar). Cuando se corrija la vulnerabilidad, esos tests se
  actualizarán para exigir el comportamiento seguro: sirven de red de seguridad
  y de test rojo del arreglo.
- `ClaseSession.php` incluye `ClaseConexion.php` con `include` (plano) mientras
  la capa de datos usa `include_once`; por eso los tests de `ClaseSession` corren
  en proceso aislado (`#[RunTestsInSeparateProcesses]`).
