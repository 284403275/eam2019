SELECT 
AUFK.OBJNR AS "object"
, AFIH.ILART AS "activity"
, AFIH.PRIOK AS "priority"
, ILOA.EQFNR AS "tag"
, ILOA.ABCKZ AS "abc"
, AUFK.KTEXT AS "description"
, AUFK.AUFNR AS "order"
, AUFK.AUART AS "order_type"
, AFKO.GLTRP AS "basic_finish_date"
, CRHD.ARBPL AS "work_center"
, AFKO.GETRI AS "actual_finish_date"
, AUFK.ZZ_COMPLSTART_DATE AS "compliance_start"
, AUFK.ZZ_COMPLEND_DATE AS "compliance_end"
, FLOOR(SYSDATE - TO_DATE(AUFK.ERDAT, 'YYYYMMDD')) AS "days_open"
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
    
, AFIH.WARPL AS "maintenance_plan"
, AFIH.WAPOS AS "maintenance_item"
--, MPLA.STRAT AS "maintenance_strategy"
, AFIH.ABNUM AS "call_number"
--, MPOS.PLNAL AS "counter"

--, AFRU.RUECK AS "confirmation_link"
--, AFVC.VORNR AS "operation_number"
--, AFVC.LTXA1 AS "operation_description"
--, AFRU.RMZHL AS "confirmation_count"
--, AUFK.AUFNR AS "order"
--, AUFK.AUART AS "order_type"
--, AFIH.ILART AS "activity"
--, AFVV.DAUNO AS "planned_work"
--, CASE AFRU.STOKZ WHEN 'X' THEN AFRU.ISMNW * -1 ELSE AFRU.ISMNW END AS "actual_work"
--, AFRU.ISMNE AS "actual_work_uom"
--, AFRU.LTXA1 AS "confirmation_text"
--, AFRU.ERNAM AS "entered_by"
--, AFRU.ERSDA AS "entered_date"
--, AFRU.ERZET AS "entered_time"
--, AFRU.ISDD AS "start_date"
--, AFRU.ISDZ AS "start_time"
--, AFRU.IEDD AS "finish_date"
--, AFRU.IEDZ AS "finish_time"
--, AFRU.ARBID AS "object_id"
--, TO_CHAR(TRIM(LEADING 0 FROM AFRU.PERNR)) AS "personnel_no"
--, AFRU.BUDAT AS "posting_date"
--, AFRU.AUERU AS "final_confirmation"
/*
, (
    SELECT
        SUM(AFVV.DAUNO)
        FROM AFVC
        JOIN AFVV ON AFVV.AUFPL = AFVC.AUFPL AND AFVV.APLZL = AFVC.APLZL
        WHERE AFVC.AUFPL = AFKO.AUFPL
) AS "planned_work"
*/
from AUFK

inner join AFIH on AFIH.AUFNR = AUFK.AUFNR
inner join AFKO on AFKO.AUFNR = AFIH.AUFNR
inner join CRHD on CRHD.OBJID = AFIH.GEWRK
inner join ILOA on ILOA.ILOAN = AFIH.ILOAN

--join MPLA on MPLA.WARPL = AFIH.WARPL
--join MPOS on MPOS.WAPOS = AFIH.WAPOS
--join PLPO on PLPO.PLNTY = MPOS.PLNTY AND PLPO.PLNNR = MPOS.PLNNR


--full outer join AFVC on AFVC.AUFPL = AFKO.AUFPL
--full outer join AFRU on AFRU.RUECK = AFVC.RUECK
--full outer join AFVV on AFVV.AUFPL = AFVC.AUFPL AND AFVV.APLZL = AFVC.APLZL


where AUFK.AUART not in ('8O01') and AUFK.LOEKZ != 'X'
--AND AFRU.IEDD = '20180103'
--AND AUFK.AUFNR = '000800182636'
AND ((AFKO.GETRI BETWEEN '20180211' AND '20180211' OR AFKO.GLTRP BETWEEN '20180211' AND '20180211'))
-- OR AFRU.ERSDA BETWEEN '20180211' AND '20180211'
 
--and AFRU.ERSDA BETWEEN '20170101' AND '20171231' --and AFRU.ERNAM = 'KSEATON'
--and AFRU.IEDD BETWEEN '20180205' AND '20180205' --and AFRU.ERNAM = 'KSEATON'
--and CRHD.ARBPL = 'FMG_P'

AND AUFK.AUART IN ('8F02')

--and AFIH.WARPL = '100000000086'
;