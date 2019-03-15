SELECT 
    IFLOT.TPLNR AS "FLOC"
    ,IFLOTX.PLTXT AS "System"
    ,CRHD.ARBPL AS "work_center"
    ,(
        SELECT
            LISTAGG(TJ30T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ30T.TXT04)
        FROM JEST
        JOIN JSTO ON JSTO.OBJNR = JEST.OBJNR
        JOIN TJ30T ON JSTO.STSMA = TJ30T.STSMA AND JEST.STAT = TJ30T.ESTAT
        WHERE JEST.OBJNR = IFLOT.OBJNR AND JEST.INACT != 'X' AND TJ30T.SPRAS = 'E'
    ) AS "User Status"
    ,(
        SELECT
            LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = IFLOT.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
    ) AS "System Status"
FROM IFLOT

JOIN IFLOTX ON IFLOTX.TPLNR = IFLOT.TPLNR
LEFT JOIN CRHD ON CRHD.OBJID = IFLOT.LGWID
JOIN ILOA ON ILOA.ILOAN = IFLOT.ILOAN

WHERE IFLOT.MANDT = '210' AND ILOA.SWERK = '8000'

--WHERE IFLOT.TPLMA = '81F-A1-FIRE' --IFLOT.FLTYP = '8' AND IFLOT.MANDT = '210'
--AND IFLOT.TPLNR = '81F-FA'
/*
AND REGEXP_LIKE(IFLOT.TPLNR, '^[a-zA-Z0-9]{3}-[a-zA-Z0-9]{2}-[a-zA-Z0-9]{3,4}$')
AND (
    NOT EXISTS (
        SELECT TJ02T.TXT04
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = IFLOT.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
        AND TJ02T.TXT04 IN ('DLFL', 'INAC')
    )
)
*/
;