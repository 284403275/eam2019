SELECT * FROM (
SELECT
    MHIS.WARPL AS "Maintenance Plan",
    MPOS.AUART AS "Order Type",
    ROUND(MHIS.ZYKZT / 3600 / 24, 2) AS Cycle,
    AFIH.AUFNR AS "Work Order",
    MPLA.HORIZ AS Horizon,
    CASE MHIS.NPLDA WHEN '00000000' THEN NULL WHEN ' ' THEN NULL WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(MHIS.NPLDA, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Planned Date",
    CASE MHIS.HORDA WHEN '00000000' THEN NULL WHEN ' ' THEN NULL  WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(MHIS.HORDA, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Call Date",
    CASE AFKO.GSTRP WHEN '00000000' THEN NULL WHEN ' ' THEN NULL  WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(AFKO.GSTRP, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Basic Start Date",
    CASE AFKO.GLTRP WHEN '00000000' THEN NULL WHEN ' ' THEN NULL  WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(AFKO.GLTRP, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Basic Finish Date",
    CASE AFKO.GETRI WHEN '00000000' THEN NULL WHEN ' ' THEN NULL  WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(AFKO.GETRI, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Confirmed Finish Date",
    MPLA.CALL_CONFIRM AS "Completion Required",
    MPOS.NO_AUFRELKZ AS "No Auto Release",
    MPLA.ABRHO AS "Scheduling Period",
    CASE WHEN MPLA.HUNIT = '10' THEN 'Days' ELSE 'Years' END AS "Scheduling Period Unit",
    MPLA.WPTXT AS "Maintenance Plan Text",
    MPOS.PSTXT AS "Maintenance Item Text",
    MPLA.STRAT AS "Maintenance Strategy",
    MHIS.ABNUM AS "Call Number",
    TO_CHAR(NULL) AS "Package",
    TO_CHAR(NULL) AS "Package Text",
    MPOS.EQUNR AS "Equipment Number",
    ILOA.EQFNR AS "Sort Field",
    ILOA.TPLNR AS "FLOC",
    IFLOTX.PLTXT AS "FLOC Text",
    CRHD.ARBPL AS "Work Center",
    op.ARBPL AS "Op Work Center",
    PLPO.VORNR AS "Operation Number",
    PLPO.LTXA1 AS "Operation Description",
    PLPO.TPLNR AS "Operation Floc",
    PLPO.EQUNR AS "Operation Equipment",
    --opp.EQFNR AS "Operation Sort Field",
    PLPO.ANZZL AS "Capacity",
    PLPO.DAUNO AS "Work",
    PLPO.ARBEI AS "Total Work Estimate",
    PLPO.ARBEH AS "Unit for Work"
    
    ,(
        SELECT
            LISTAGG(TJ30T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ30T.TXT04)
        FROM JEST
        JOIN JSTO ON JSTO.OBJNR = JEST.OBJNR
        JOIN TJ30T ON JSTO.STSMA = TJ30T.STSMA AND JEST.STAT = TJ30T.ESTAT
        WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ30T.SPRAS = 'E'
    ) AS "User Status"
    ,(
        SELECT
            LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
    ) AS "System Status"
    ,(
        SELECT
            LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = AFVC.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
    ) AS "Opperation System Status"
    
    FROM MHIS
    
    JOIN MPLA ON MPLA.WARPL = MHIS.WARPL AND LENGTH(MPLA.STRAT) = 1
    LEFT JOIN MPOS ON MPOS.WARPL = MHIS.WARPL
    LEFT JOIN PLKO ON MPOS.PLNNR = PLKO.PLNNR AND MPOS.PLNAL = PLKO.PLNAL
    LEFT JOIN PLAS ON PLAS.PLNNR = PLKO.PLNNR AND PLAS.PLNAL = PLKO.PLNAL AND PLAS.PLNTY = 'A' AND PLAS.LOEKZ <> 'X'
    LEFT JOIN PLPO ON PLPO.PLNTY = PLAS.PLNTY AND PLPO.PLNNR = PLAS.PLNNR AND PLPO.PLNKN = PLAS.PLNKN AND PLPO.ZAEHL = PLAS.ZAEHL
    LEFT JOIN EQUZ ON EQUZ.EQUNR = PLPO.EQUNR AND EQUZ.DATBI = '99991231' AND EQUZ.MANDT = '210'
    LEFT JOIN ILOA ON ILOA.ILOAN = MPOS.ILOAN
    LEFT JOIN ILOA opp ON opp.ILOAN = EQUZ.ILOAN
    LEFT JOIN IFLOTX ON IFLOTX.TPLNR = ILOA.TPLNR
    LEFT JOIN CRHD ON CRHD.OBJID = MPOS.GEWRK
    LEFT JOIN CRHD op ON op.OBJID = PLPO.ARBID
    LEFT JOIN AFIH ON AFIH.WARPL = MPOS.WARPL AND AFIH.WAPOS = MPOS.WAPOS AND AFIH.ABNUM = MHIS.ABNUM
    LEFT JOIN AFKO ON AFKO.AUFNR = AFIH.AUFNR
    LEFT JOIN AUFK ON AFIH.AUFNR = AUFK.AUFNR
    LEFT JOIN AFVC ON AFVC.AUFPL = AFKO.AUFPL AND AFVC.VORNR = PLPO.VORNR
    LEFT JOIN AFVV ON AFVV.AUFPL = AFVC.AUFPL AND AFVV.APLZL = AFVC.APLZL

    WHERE
(
    NOT EXISTS (
        SELECT TJ02T.TXT04
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = MPLA.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
        AND TJ02T.TXT04 IN ('INAC', 'DLFL')
    )
)
UNION
SELECT
    MHIS.WARPL AS "Maintenance Plan",
    MPOS.AUART AS "Order Type",
    ROUND(MHIS.ZYKZT / 3600 / 24, 2) AS Cycle,
    AFIH.AUFNR AS "Work Order",
    MPLA.HORIZ AS Horizon,
    CASE MHIS.NPLDA WHEN '00000000' THEN NULL WHEN ' ' THEN NULL WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(MHIS.NPLDA, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Planned Date",
    CASE MHIS.HORDA WHEN '00000000' THEN NULL WHEN ' ' THEN NULL  WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(MHIS.HORDA, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Call Date",
    CASE AFKO.GSTRP WHEN '00000000' THEN NULL WHEN ' ' THEN NULL  WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(AFKO.GSTRP, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Basic Start Date",
    CASE AFKO.GLTRP WHEN '00000000' THEN NULL WHEN ' ' THEN NULL  WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(AFKO.GLTRP, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Basic Finish Date",
    CASE AFKO.GETRI WHEN '00000000' THEN NULL WHEN ' ' THEN NULL  WHEN NULL THEN NULL ELSE TO_CHAR(TO_DATE(AFKO.GETRI, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Confirmed Finish Date",
    MPLA.CALL_CONFIRM AS "Completion Required",
    MPOS.NO_AUFRELKZ AS "No Auto Release",
    MPLA.ABRHO AS "Scheduling Period",
    CASE WHEN MPLA.HUNIT = '10' THEN 'Days' ELSE 'Years' END AS "Scheduling Period Unit",
    MPLA.WPTXT AS "Maintenance Plan Text",
    MPOS.PSTXT AS "Maintenance Item Text",
    MPLA.STRAT AS "Maintenance Strategy",
    MHIS.ABNUM AS "Call Number",
    T351X.PAKET AS "Package",
    T351X.KTEX1 AS "Package Text",
    MPOS.EQUNR AS "Equipment Number",
    ILOA.EQFNR AS "Sort Field",
    ILOA.TPLNR AS "FLOC",
    IFLOTX.PLTXT AS "FLOC Text",
    CRHD.ARBPL AS "Work Center",
    op.ARBPL AS "Op Work Center",
    PLPO.VORNR AS "Operation Number",
    PLPO.LTXA1 AS "Operation Description",
    PLPO.TPLNR AS "Operation Floc",
    PLPO.EQUNR AS "Operation Equipment",
    --opp.EQFNR AS "Operation Sort Field",
    PLPO.ANZZL AS "Capacity",
    PLPO.DAUNO AS "Work",
    PLPO.ARBEI AS "Total Work Estimate",
    PLPO.ARBEH AS "Unit for Work"
    ,(
        SELECT
            LISTAGG(TJ30T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ30T.TXT04)
        FROM JEST
        JOIN JSTO ON JSTO.OBJNR = JEST.OBJNR
        JOIN TJ30T ON JSTO.STSMA = TJ30T.STSMA AND JEST.STAT = TJ30T.ESTAT
        WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ30T.SPRAS = 'E'
    ) AS "User Status"
    ,(
        SELECT
            LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
    ) AS "System Status"
    ,(
        SELECT
            LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = AFVC.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
    ) AS "Opperation System Status"
    
    FROM MHIS
    
	JOIN MPLA ON MHIS.WARPL = MPLA.WARPL AND LENGTH(MPLA.STRAT) > 1
	INNER JOIN MPOS ON MPOS.WARPL = MHIS.WARPL
	LEFT JOIN T351X ON T351X.STRAT = MPLA.STRAT AND T351X.PAKET = MHIS.ZAEHL
	LEFT JOIN PLWP ON PLWP.PLNNR = MPOS.PLNNR AND PLWP.PAKET = MHIS.ZAEHL
	LEFT JOIN PLPO ON PLPO.PLNNR = PLWP.PLNNR AND PLPO.PLNKN = PLWP.PLNKN
    LEFT JOIN EQUZ ON EQUZ.EQUNR = PLPO.EQUNR AND EQUZ.DATBI = '99991231' AND EQUZ.MANDT = '210'
	LEFT JOIN CRHD ON CRHD.OBJID = MPOS.GEWRK
	LEFT JOIN ILOA ON ILOA.ILOAN = MPOS.ILOAN
    LEFT JOIN ILOA opp ON opp.ILOAN = EQUZ.ILOAN
	LEFT JOIN IFLOTX ON IFLOTX.TPLNR = ILOA.TPLNR
	LEFT JOIN CRHD op ON op.OBJID = PLPO.ARBID
	LEFT JOIN AFIH ON AFIH.WARPL = MPOS.WARPL AND AFIH.WAPOS = MPOS.WAPOS AND AFIH.ABNUM = MHIS.ABNUM
	LEFT JOIN AFKO ON AFKO.AUFNR = AFIH.AUFNR
	LEFT JOIN AUFK ON AFIH.AUFNR = AUFK.AUFNR
	LEFT JOIN AFVC ON AFVC.AUFPL = AFKO.AUFPL AND AFVC.VORNR = PLPO.VORNR
	LEFT JOIN AFVV ON AFVV.AUFPL = AFVC.AUFPL AND AFVV.APLZL = AFVC.APLZL
    
    WHERE 
(
    NOT EXISTS (
        SELECT TJ02T.TXT04
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = MPLA.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
        AND TJ02T.TXT04 IN ('INAC', 'DLFL')
    )
)
)
WHERE "Planned Date" BETWEEN '2019-01-01' AND '2019-12-31'
--OR "Basic Start Date" BETWEEN '2018-09-09' AND '2018-09-15')
--WHERE "Maintenance Plan" = '100000001168'
--AND "Confirmed Finish Date" IS NOT NULL
--AND "Order Type" IN ('ZF02')
AND "Order Type" IN ('8F03','8F02','8F01','8F06')
--AND "Work Center" IN ('TGM')
--AND ("Work Center" IN ('FMG_P', 'TAB_P') OR "Op Work Center" IN ('FMG_P', 'TAB_P'))
/*
WHERE "Work Order" IN (
  '000800236078',
  '000800237220',
  '000800236544',
  '000800237623',
  '000800236566',
  '000800239270',
  '000800238785'
)
*/
;