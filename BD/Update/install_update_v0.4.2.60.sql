CREATE VIEW vw_resumenClientesFacturas AS
SELECT
    c.idClientes AS idCliente,
    c.nif,
    YEAR(f.Fecha) AS ejercicio,

    SUM(CASE WHEN QUARTER(f.Fecha) = 1 THEN f.total ELSE 0 END) AS q1,
    SUM(CASE WHEN QUARTER(f.Fecha) = 1 THEN f.totalIva ELSE 0 END) AS q1Iva,

    SUM(CASE WHEN QUARTER(f.Fecha) = 2 THEN f.total ELSE 0 END) AS q2,
    SUM(CASE WHEN QUARTER(f.Fecha) = 2 THEN f.totalIva ELSE 0 END) AS q2Iva,

    SUM(CASE WHEN QUARTER(f.Fecha) = 3 THEN f.total ELSE 0 END) AS q3,
    SUM(CASE WHEN QUARTER(f.Fecha) = 3 THEN f.totalIva ELSE 0 END) AS q3Iva,

    SUM(CASE WHEN QUARTER(f.Fecha) = 4 THEN f.total ELSE 0 END) AS q4,
    SUM(CASE WHEN QUARTER(f.Fecha) = 4 THEN f.totalIva ELSE 0 END) AS q4Iva,

    SUM(f.totalIva) AS totalIva,
    SUM(f.total) AS total

FROM (
    SELECT
        fac.id,
        fac.idCliente,
        fac.Fecha,
        fac.total,
        SUM(fiva.importeIva) AS totalIva
    FROM facclit fac
    JOIN faccliIva fiva ON fiva.idfaccli = fac.id
    WHERE fac.estado <> 'Sin Guardar'
    GROUP BY fac.id, fac.idCliente, fac.Fecha, fac.total
) f
JOIN clientes c ON c.idClientes = f.idCliente
GROUP BY c.idClientes, YEAR(f.Fecha);

CREATE VIEW vw_resumenClientesTickets AS
SELECT
    c.idClientes AS idCliente,
    c.nif,
    YEAR(t.Fecha) AS ejercicio,

    SUM(CASE WHEN QUARTER(t.Fecha) = 1 THEN t.total ELSE 0 END) AS q1,
    SUM(CASE WHEN QUARTER(t.Fecha) = 1 THEN t.totalIva ELSE 0 END) AS q1Iva,

    SUM(CASE WHEN QUARTER(t.Fecha) = 2 THEN t.total ELSE 0 END) AS q2,
    SUM(CASE WHEN QUARTER(t.Fecha) = 2 THEN t.totalIva ELSE 0 END) AS q2Iva,

    SUM(CASE WHEN QUARTER(t.Fecha) = 3 THEN t.total ELSE 0 END) AS q3,
    SUM(CASE WHEN QUARTER(t.Fecha) = 3 THEN t.totalIva ELSE 0 END) AS q3Iva,

    SUM(CASE WHEN QUARTER(t.Fecha) = 4 THEN t.total ELSE 0 END) AS q4,
    SUM(CASE WHEN QUARTER(t.Fecha) = 4 THEN t.totalIva ELSE 0 END) AS q4Iva,

    SUM(t.total) AS total,
    SUM(t.totalIva) AS totalIva

FROM (
    SELECT
        tick.id,
        tick.idCliente,
        tick.idTienda,
        tick.Fecha,
        tick.total,
        SUM(tiva.importeIva) AS totalIva
    FROM ticketst tick
    JOIN ticketstIva tiva ON tiva.idticketst = tick.id
    WHERE tick.estado = 'Cerrado'
    GROUP BY tick.id, tick.idCliente, tick.Fecha, tick.total
) t
JOIN clientes c ON c.idClientes = t.idCliente
GROUP BY c.idClientes, YEAR(t.Fecha);

CREATE VIEW vw_resumenProveedoresFacturas AS
SELECT
    p.idProveedor AS idProveedor,
    p.nif,
    YEAR(f.Fecha) AS ejercicio,

    SUM(CASE WHEN QUARTER(f.Fecha) = 1 THEN f.total ELSE 0 END) AS q1,
    SUM(CASE WHEN QUARTER(f.Fecha) = 1 THEN f.totalIva ELSE 0 END) AS q1Iva,

    SUM(CASE WHEN QUARTER(f.Fecha) = 2 THEN f.total ELSE 0 END) AS q2,
    SUM(CASE WHEN QUARTER(f.Fecha) = 2 THEN f.totalIva ELSE 0 END) AS q2Iva,

    SUM(CASE WHEN QUARTER(f.Fecha) = 3 THEN f.total ELSE 0 END) AS q3,
    SUM(CASE WHEN QUARTER(f.Fecha) = 3 THEN f.totalIva ELSE 0 END) AS q3Iva,

    SUM(CASE WHEN QUARTER(f.Fecha) = 4 THEN f.total ELSE 0 END) AS q4,
    SUM(CASE WHEN QUARTER(f.Fecha) = 4 THEN f.totalIva ELSE 0 END) AS q4Iva,

    SUM(f.total) AS total,
    SUM(f.totalIva) AS totalIva

FROM (
    SELECT
        fac.id,
        fac.idProveedor,
        fac.Fecha,
        fac.total,
        SUM(fiva.importeIva) AS totalIva
    FROM facprot fac
    JOIN facproIva fiva ON fiva.idfacpro = fac.id
    WHERE fac.estado <> 'Sin Guardar'
    GROUP BY fac.id, fac.idProveedor, fac.Fecha, fac.total
) f
JOIN proveedores p ON p.idProveedor = f.idProveedor
GROUP BY p.idProveedor, YEAR(f.Fecha);
