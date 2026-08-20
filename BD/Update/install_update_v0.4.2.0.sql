CREATE OR REPLACE SQL SECURITY INVOKER VIEW vw_jerarquias_familias AS
WITH RECURSIVE ArbolFamilias AS (
    -- 1. CASO BASE: Nivel 1 (Los Departamentos)
    SELECT
        idFamilia,
        familiaNombre,
        familiaPadre,
        1 AS nivel,
        idFamilia AS idN1,          -- Ella misma es su N1
        CAST(NULL AS UNSIGNED) AS idN2, -- No tiene N2 aún
        CAST(familiaNombre AS CHAR(500)) AS ruta
    FROM familias
    WHERE familiaPadre = 0 OR familiaPadre IS NULL

    UNION ALL

    -- 2. CASO RECURSIVO: Niveles 2, 3, 4...
    SELECT
        f.idFamilia,
        f.familiaNombre,
        f.familiaPadre,
        af.nivel + 1,
        af.idN1,                    -- Hereda el N1 del padre siempre
        CASE
            WHEN af.nivel = 1 THEN f.idFamilia -- Si el padre es N1, ella es el N2
            ELSE af.idN2                       -- Si no, hereda el N2 que ya traía el padre
        END,
        CONCAT(af.ruta, ' > ', f.familiaNombre)
    FROM familias f
    INNER JOIN ArbolFamilias af ON f.familiaPadre = af.idFamilia
)
-- 3. RESULTADO FINAL DE LA VISTA
SELECT
    idFamilia,
    nivel,
    familiaNombre,
    idN1,
    idN2,
    familiaPadre,
    ruta
FROM ArbolFamilias
ORDER BY ruta;
