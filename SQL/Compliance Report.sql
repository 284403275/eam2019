SELECT
	AUFK.AUFNR AS "Work Order"
    --,mcycle.ABNUM AS "Call Number"
    ,CASE WHEN AUFK.VAPLZ IN ('TAB_P', 'FMG_P', 'MIC_P', 'IC', 'WTR_P', 'ELEC_P', 'BS', 'TGM') THEN
        CASE WHEN (mcycle."Planned Date" != '00000000' AND AFKO.GETRI != '00000000') THEN
        CASE 
            WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(mcycle."Planned Date" ,'YYYYMMDD'))) > CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END 
                THEN '1'
            WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(mcycle."Planned Date" ,'YYYYMMDD'))) * -1 > CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END
                THEN '1'
            ELSE NULL
            END
        END ELSE
            CASE WHEN AFKO.GETRI BETWEEN AFKO.GSTRP AND AFKO.GLTRP THEN NULL ELSE '1' END
            END AS "OOC"
	,AUFK.KTEXT AS "Description"
	,CASE AUFK.AUART 
        WHEN '8F01' THEN 'CM'
        WHEN '8F02' THEN 'PM'
        WHEN '8F03' THEN 'ST'
        WHEN '8F06' THEN 'OPS'
        END
        AS "Order Type"
    ,ILOA.EQFNR AS "Tag"
	,CASE AUFK.VAPLZ
            WHEN 'TAB_P' THEN 'FMT'
            WHEN 'FMG_P' THEN 'FMT'
            WHEN 'MIC_P' THEN 'CMT'
            WHEN 'IC' THEN 'IC'
            WHEN 'WTR_P' THEN 'UPW'
            WHEN 'ELEC_P' THEN 'ELEC'
            WHEN 'GAS_P' THEN 'GAS'
            WHEN 'CHEM_P' THEN 'CHEM'
            WHEN 'SLUR_P' THEN 'SLURY'
            WHEN 'ALOP_P' THEN 'AL OP'
            WHEN 'TGM' THEN 'TGM'
            WHEN 'BS' THEN 'BS'
            ELSE 'Unknown'
        END  AS "Work Center"
	,AUFK.ERNAM AS "Entered By"
	,AUFK.ERDAT AS "Created On"
	,AFKO.FTRMI AS "Released On"
	,AFKO.GSTRS AS "Basic Start Date"
	,AFKO.GLTRP AS "Basic Finish Date"
	,AFKO.GETRI AS "Confirmed Finish Date"
	,AUFK.ZZ_COMPLSTART_DATE AS "Compliance Start"
    ,CASE WHEN (mcycle."Planned Date" != '00000000') THEN to_char(to_date(mcycle."Planned Date",'YYYYMMDD') - CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END , 'YYYYMMDD') END AS "M Compliance Start"
    ,mcycle."Planned Date"
    ,CASE WHEN (mcycle."Planned Date" != '00000000') THEN to_char(to_date(mcycle."Planned Date",'YYYYMMDD') + CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END , 'YYYYMMDD') END AS "M Compliance End"
	,AUFK.ZZ_COMPLEND_DATE AS "Compliance End"
	,AUFK.LOEKZ AS "Deletion Flag"
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
        --AND TJ02T.TXT04 NOT IN ('DLFL', 'CNF', 'CLSD')
    ) AS "System Status"
    ,mcycle."Cycle"
    ,mcycle."Manual Call"
    
FROM AUFK
JOIN AFIH ON AFIH.AUFNR = AUFK.AUFNR
JOIN AFKO ON AFKO.AUFNR = AFIH.AUFNR
JOIN CRHD ON CRHD.OBJID = AFIH.GEWRK
JOIN ILOA ON ILOA.ILOAN = AFIH.ILOAN
FULL OUTER JOIN (
    SELECT 
        MANDA AS "Manual Call"
        ,WARPL
        ,ABNUM
        ,NPLDA AS "Planned Date"
        ,ROUND(MAX(ZYKZT) / 3600 / 24, 2) AS "Cycle"
    FROM MHIS GROUP BY WARPL, ABNUM, MANDA, NPLDA
) mcycle ON mcycle.WARPL = AFIH.WARPL AND mcycle.ABNUM = AFIH.ABNUM


WHERE (AFKO.GETRI >= '20190304' AND AFKO.GETRI <= '20190310')
AND AUFK.AUART = '8F02'
--AND AUFK.AUFNR = '000800293210'
--FULL OUTER JOIN ( SELECT maintenance_key, planned_date, MAX(cycle) AS cycle FROM vw_maintenance_sched GROUP BY maintenance_key, planned_date ) AS mcycle ON mcycle.maintenance_key = s1.maintenance_key
--AND AUFK.VAPLZ = 'IC'
;