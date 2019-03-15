SELECT DISTINCT
	AUFK.AUFNR AS "Work Order"
    ,AFVC.VORNR AS "op_num"
    ,mcycle."Cycle"
	,AUFK.KTEXT AS "Description"
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
    ,CASE CRHD.ARBPL
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
        END  AS "op_work_center"
    ,AFIH.WARPL AS "maintenance_plan"
    ,mcycle.ABNUM AS "Call Number"
    ,AFVV.ARBEI AS "planned_work"
    ,AFVC.ANZZL AS "planned_capacity"
    ,AFVV.DAUNO AS "duration"
    ,AFVV.ARBEI * AFVC.ANZZL AS "total_planned_work"
    ,AFVV.ISMNW AS "actual_work"
    ,COUNT(DISTINCT AFRU.PERNR) OVER (PARTITION BY AFRU.RUECK) AS "actual_capacity"
    ,COUNT(DISTINCT AFRU.PERNR) OVER (PARTITION BY AUFK.AUFNR) AS "total_capacity"
    --,SUM(AFVV.ISMNW) OVER (PARTITION BY AFIH.AUFNR) AS "actual_work"
    --,SUM(AFVV.DAUNO) OVER (PARTITION BY AFIH.AUFNR) AS "planned_work"
    --,MAX(AFVC.ANZZL) OVER (PARTITION BY AFIH.AUFNR) AS "planned_capacity"
    --,SUM(DISTINCT AFRU.PERNR) AS "actual_capacity"
    ,CASE WHEN AUFK.VAPLZ IN ('TAB_P', 'FMG_P', 'MIC_P', 'IC', 'WTR_P', 'ELEC_P', 'BS', 'TGM') THEN
        CASE WHEN (mcycle."Planned Date" != '00000000' AND AFKO.GETRI != '00000000') THEN
        CASE 
            WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(mcycle."Planned Date" ,'YYYYMMDD'))) > CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END 
                THEN 'No'
            WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(mcycle."Planned Date" ,'YYYYMMDD'))) * -1 > CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END
                THEN 'No'
            END else 'Yes'
        END ELSE
            CASE WHEN AFKO.GETRI BETWEEN AFKO.GSTRP AND AFKO.GLTRP THEN 'Yes' ELSE 'No' END
            END AS "on_time"
    /*
    ,CASE WHEN AUFK.VAPLZ IN ('TAB_P', 'FMG_P', 'MIC_P', 'IC', 'WTR_P', 'ELEC_P', 'BS', 'TGM') THEN
        CASE WHEN (mcycle."Planned Date" != '00000000' AND AFKO.GETRI != '00000000') THEN
        CASE 
            WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(mcycle."Planned Date" ,'YYYYMMDD'))) > CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END 
                THEN 'No'
            WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(mcycle."Planned Date" ,'YYYYMMDD'))) * -1 > CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END
                THEN 'No'
            ELSE 'Yes'
            END
        END ELSE
            CASE WHEN AFKO.GETRI BETWEEN AFKO.GSTRP AND AFKO.GLTRP THEN 'Yes' ELSE 'No' END
            END AS "on_time"
            */
    ,CASE WHEN AFVV.ARBEI > 0 AND AFVV.ISMNW > 0 THEN
        ROUND((1 - ABS((AFVV.ISMNW - AFVV.ARBEI) / ((AFVV.ARBEI + (5 / AFVV.ARBEI) + AFVV.ISMNW)))) * 100, 2) ELSE 0 END AS "in_time"
    /* 
    ,(
        SELECT DISTINCT COUNT(AFRU.PERNR)
            FROM AFRU
            WHERE AFRU.RUECK = AFVC.RUECK
        ) AS "actual_capacity"
        */
	--,AFKO.GSTRS AS "Basic Start Date"
	--,AFKO.GLTRP AS "Basic Finish Date"
	--,AFKO.GETRI AS "Confirmed Finish Date"
    --,CASE WHEN (mcycle."Planned Date" != '00000000') THEN to_char(to_date(mcycle."Planned Date",'YYYYMMDD') - CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END , 'YYYYMMDD') END AS "M Compliance Start"
    --,mcycle."Planned Date"
    --,CASE WHEN (mcycle."Planned Date" != '00000000') THEN to_char(to_date(mcycle."Planned Date",'YYYYMMDD') + CASE WHEN mcycle."Cycle" <= 7 THEN 3 ELSE CEIL(mcycle."Cycle" * .1) END , 'YYYYMMDD') END AS "M Compliance End"
/*
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
    */
FROM AUFK
JOIN AFIH ON AFIH.AUFNR = AUFK.AUFNR
JOIN AFKO ON AFKO.AUFNR = AFIH.AUFNR
JOIN AFVC ON AFVC.AUFPL = AFKO.AUFPL
JOIN AFVV ON AFVV.AUFPL = AFVC.AUFPL AND AFVV.APLZL = AFVC.APLZL
JOIN AFRU ON AFRU.RUECK = AFVC.RUECK
JOIN CRHD ON CRHD.OBJID = AFVC.ARBID
FULL OUTER JOIN ILOA ON ILOA.ILOAN = AFIH.ILOAN

FULL OUTER JOIN (
    SELECT 
        MANDA AS "Manual Call"
        ,WARPL
        ,ABNUM
        ,NPLDA AS "Planned Date"
        ,ROUND(MAX(ZYKZT) / 3600 / 24, 2) AS "Cycle"
    FROM MHIS GROUP BY WARPL, ABNUM, MANDA, NPLDA
) mcycle ON mcycle.WARPL = AFIH.WARPL AND mcycle.ABNUM = AFIH.ABNUM


WHERE (AFKO.GETRI >= '20190225' AND AFKO.GETRI <= '20190303')
AND AUFK.AUART = '8F02'
AND AUFK.VAPLZ = 'IC'
--AND AUFK.AUFNR = '000800370751'
--WHERE AFIH.WARPL = '100000000738'
--AND AUFK.AUFNR = '000800293210'
--FULL OUTER JOIN ( SELECT maintenance_key, planned_date, MAX(cycle) AS cycle FROM vw_maintenance_sched GROUP BY maintenance_key, planned_date ) AS mcycle ON mcycle.maintenance_key = s1.maintenance_key

;