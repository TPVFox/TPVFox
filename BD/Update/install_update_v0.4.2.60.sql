CREATE OR REPLACE SQL SECURITY INVOKER VIEW vw_resumenClientesFacturas AS
SELECT
    c.idClientes AS idCliente,
    c.nif,
    f.ejercicio,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalFactura ELSE 0 END) AS q1,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalIva ELSE 0 END) AS q1Iva,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalFactura ELSE 0 END) AS q2,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalIva ELSE 0 END) AS q2Iva,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalFactura ELSE 0 END) AS q3,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalIva ELSE 0 END) AS q3Iva,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalFactura ELSE 0 END) AS q4,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalIva ELSE 0 END) AS q4Iva,
    SUM(f.totalIva) AS totalIva,
    SUM(f.totalFactura) AS total
FROM (
    SELECT
        fac.idCliente,
        YEAR(fac.Fecha) AS ejercicio,
        QUARTER(fac.Fecha) AS trimestre,
        fac.total AS totalFactura,
        SUM(fiva.importeIva) AS totalIva
    FROM facclit fac
    JOIN faccliIva fiva ON fiva.idfaccli = fac.id
    WHERE fac.estado <> 'Sin Guardar'
    GROUP BY fac.id -- Agrupamos por ID de factura para obtener su IVA total
) f
JOIN clientes c ON c.idClientes = f.idCliente
GROUP BY c.idClientes, c.nif, f.ejercicio;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW vw_resumenClientesTickets AS
SELECT
    c.idClientes AS idCliente,
    c.nif,
    t.ejercicio,
    SUM(CASE WHEN t.trimestre = 1 THEN t.totalTicket ELSE 0 END) AS q1,
    SUM(CASE WHEN t.trimestre = 1 THEN t.totalIva ELSE 0 END) AS q1Iva,
    SUM(CASE WHEN t.trimestre = 2 THEN t.totalTicket ELSE 0 END) AS q2,
    SUM(CASE WHEN t.trimestre = 2 THEN t.totalIva ELSE 0 END) AS q2Iva,
    SUM(CASE WHEN t.trimestre = 3 THEN t.totalTicket ELSE 0 END) AS q3,
    SUM(CASE WHEN t.trimestre = 3 THEN t.totalIva ELSE 0 END) AS q3Iva,
    SUM(CASE WHEN t.trimestre = 4 THEN t.totalTicket ELSE 0 END) AS q4,
    SUM(CASE WHEN t.trimestre = 4 THEN t.totalIva ELSE 0 END) AS q4Iva,
    SUM(t.totalIva) AS totalIva,
    SUM(t.totalTicket) AS total
FROM (
    SELECT
        tick.idCliente,
        YEAR(tick.Fecha) AS ejercicio,
        QUARTER(tick.Fecha) AS trimestre,
        tick.total AS totalTicket,
        SUM(tiva.importeIva) AS totalIva
    FROM ticketst tick
    JOIN ticketstIva tiva ON tiva.idticketst = tick.id
    WHERE tick.estado = 'Cerrado'
    GROUP BY tick.id
) t
JOIN clientes c ON c.idClientes = t.idCliente
GROUP BY c.idClientes, c.nif, t.ejercicio;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW vw_resumenProveedoresFacturas AS
SELECT
    p.idProveedor,
    p.nif,
    f.ejercicio,
    -- Agregación por trimestres
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalFactura ELSE 0 END) AS q1,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalIva ELSE 0 END) AS q1Iva,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalFactura ELSE 0 END) AS q2,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalIva ELSE 0 END) AS q2Iva,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalFactura ELSE 0 END) AS q3,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalIva ELSE 0 END) AS q3Iva,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalFactura ELSE 0 END) AS q4,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalIva ELSE 0 END) AS q4Iva,
    -- Totales anuales
    SUM(f.totalIva) AS totalIva,
    SUM(f.totalFactura) AS total
FROM (
    -- Subconsulta para calcular el IVA por factura primero
    SELECT
        fac.idProveedor,
        YEAR(fac.Fecha) AS ejercicio,
        QUARTER(fac.Fecha) AS trimestre,
        fac.total AS totalFactura,
        SUM(fiva.importeIva) AS totalIva
    FROM facprot fac
    JOIN facproIva fiva ON fiva.idfacpro = fac.id
    WHERE fac.estado <> 'Sin Guardar'
    GROUP BY fac.id -- Agrupamos por ID de factura para tener el IVA total de cada una
) f
JOIN proveedores p ON p.idProveedor = f.idProveedor
GROUP BY p.idProveedor, p.nif, f.ejercicio;
