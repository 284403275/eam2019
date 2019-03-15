SELECT
    AFIH.IWERK AS "plant"
    ,AFIH.AUFNR AS "order"
    ,AUFK.AUART AS "order_type"
    ,AFIH.PRIOK AS "priority"
    ,ILOA.ABCKZ AS "abc"
    ,AUFK.KTEXT AS "order_description"
    ,AFVV.FSAVD AS "start_date"
    ,AFVV.FSAVZ AS "start_time"
    ,AFVV.
    ,AFIH.EQUNR AS "equipment"
    ,AFVC.STEUS AS "control_key"
    ,AUFK.VAPLZ AS "main_work_center"
    ,AFIH.INGPR AS "planner_group"
    ,AFIH.WARPL AS "maintenance_plan"
    ,AFIH.IPHAS AS "maintenance_phase" -- 0: Outstanding, 2: Released, 3: TECO, 4: Deletion Set, 5: Historical?, 6: CMPL
    ,AFIH.ILART AS "activity_type_code"
    ,AFKO.GSTRP AS "basic_start_date"
    ,AFKO.GLTRP AS "basic_finish_date"
    ,AFKO.GETRI AS "confirmed_finish_date"
    ,CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'YYYY') ELSE NULL END AS "finish_year"
    ,CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'ww') ELSE NULL END AS "finish_ww"
    ,FLOOR(SYSDATE - TO_DATE(AUFK.ERDAT, 'YYYYMMDD')) AS "days_open"
        
    ,AUFK.ERNAM AS "entered_by"
    ,AUFK.ERDAT AS "created_on_date"
    ,AUFK.ERFZEIT AS "created_on_time"
    ,AUFK.AEDAT AS "changed_on_date"
    ,AUFK.AEZEIT AS "changed_on_time"
    
    ,AUFK.KOSTV AS "cost_center"
    ,AUFK.PHAS0 AS "created_flag"
    ,AUFK.PHAS1 AS "released_flag"
    ,AUFK.PHAS2 AS "teco_flag"
    ,AUFK.PHAS3 AS "closed_flag"
    ,AUFK.PDAT1 AS "planned_release_date"
    ,AUFK.PDAT2 AS "planned_completion_date"
    ,AUFK.IDAT1 AS "release_date"
    ,AUFK.IDAT2 AS "completion_date"
    ,AUFK.LOEKZ AS "deletion_flag" 

    ,ILOA.EQFNR AS "tag_id"
    --,EQKT.EQKTX AS "equipment_description"
    ,ILOA.TPLNR AS "floc"
    ,IFLOTX.PLTXT AS "floc_description"


    , (
        SELECT
			LISTAGG(TJ30T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ30T.TXT04)
		FROM JEST
		JOIN JSTO ON JSTO.OBJNR = JEST.OBJNR
		JOIN TJ30T ON JSTO.STSMA = TJ30T.STSMA AND JEST.STAT = TJ30T.ESTAT
		WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ30T.SPRAS = 'E'
    ) AS "user_status"
    ,(
        SELECT
			LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)
		FROM JEST
		JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
		WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
    ) AS "system_status"


    ,AFVV.IEDD AS "operation_finish_date"
    ,AFVV.IEDZ AS "operation_finish_time"

FROM AFIH

JOIN AFKO ON AFKO.AUFNR = AFIH.AUFNR
JOIN AUFK ON AUFK.AUFNR = AFIH.AUFNR
--FULL OUTER JOIN EQKT ON EQKT.EQUNR = AFIH.EQUNR
FULL OUTER JOIN ILOA ON ILOA.ILOAN = AFIH.ILOAN
JOIN AFVC ON AFVC.AUFPL = AFKO.AUFPL
JOIN AFVV ON AFVV.AUFPL = AFVC.AUFPL AND AFVV.APLZL = AFVC.APLZL
JOIN CRHD ON CRHD.OBJID = AFVC.ARBID
JOIN IFLOTX ON IFLOTX.TPLNR = ILOA.TPLNR
--JOIN CRHD ON CRHD.OBJID = AFIH.GEWRK

WHERE /*AUFK.AUART NOT IN ('8O01') AND*/ AUFK.LOEKZ != 'X' AND AFIH.IWERK = '8000'
AND AUFK.AUART IN ('8F01', '8F02', '8F03')
AND AUFK.LOEKZ != 'X'
AND AFIH.AUFNR = '000800265975'
--AND AUFK.ZZ_COMPLEND_DATE < '20180919' AND AUFK.ZZ_COMPLEND_DATE != '00000000'
--CNF date
--AND AFKO.GETRI = '20180719'

--Basic finish date
--AND AFKO.GLTRP BETWEEN '20180801' AND '20180830'

--Basic start date
--AND AFKO.GSTRP BETWEEN '20181013' AND '20181014'

--Compliance
--AND (AFKO.GETRI >= '20180701' AND AFKO.GETRI <= '20181231')
--Vendor PMC

--AND AUFK.VAPLZ IN ('ALOP_P', 'CHEM_P', 'GAS_P', 'SLUR_P')
--GF PMG
--AND AUFK.VAPLZ IN ('MIC_P', 'FMG_P', 'ELEC_P', 'WTR_P', 'IC', 'TAB_P')
--AND AUFK.AUART IN ('8F01')
--AND AUFK.VAPLZ IN ('FMG_P')


--Backlog

--AND (AFKO.GLTRP < '20180306' OR AFKO.GLTRP IS NULL)
--AND AUFK.AUART IN ('8F01', '8F02', '8F03', '8F06')

/*
System status selection
*/
/*
AND 
(
    
    NOT EXISTS (
        SELECT TJ02T.TXT04
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
        AND TJ02T.TXT04 IN ('DLFL', 'TECO', 'CLSD', 'CNF')
    )
    */
    /*
    AND
    EXISTS (
        SELECT TJ02T.TXT04
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
        AND TJ02T.TXT04 IN ('REL')
    )
    */
--)

/*
User status selection
*/
/*
AND 
(
    
    NOT EXISTS (
        SELECT TJ30T.TXT04
        FROM JEST
        JOIN JSTO ON JSTO.OBJNR = JEST.OBJNR
		JOIN TJ30T ON JSTO.STSMA = TJ30T.STSMA AND JEST.STAT = TJ30T.ESTAT
		WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ30T.SPRAS = 'E'
        AND TJ30T.TXT04 IN ('CNCL')
    )
    /*
    AND
    EXISTS (
        SELECT TJ30T.TXT04
        FROM JEST
        JOIN JSTO ON JSTO.OBJNR = JEST.OBJNR
		JOIN TJ30T ON JSTO.STSMA = TJ30T.STSMA AND JEST.STAT = TJ30T.ESTAT
		WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ30T.SPRAS = 'E'
        AND TJ30T.TXT04 IN ('HOLD')
    )
    */
    
--)
;