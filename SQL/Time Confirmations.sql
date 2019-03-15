SELECT
	--AFRU.RUECK AS "Confirmation Link",
    AFIH.AUFNR AS "work_order"
    ,AFRU.VORNR AS "operation_num"
	--AFRU.RMZHL AS "Confirmation Count",
	,AUFK.AUART AS "order_type"
	,CASE AFRU.STOKZ WHEN 'X' THEN AFRU.ISMNW * -1 ELSE AFRU.ISMNW END AS "actual_work"
    ,CASE WHEN AFIH.AUFNR IS NULL THEN '' ELSE AFIH.AUFNR END || CASE WHEN AFIH.WARPL = ' ' THEN '' ELSE AFIH.WARPL END || CASE WHEN AFIH.WAPOS = ' ' THEN '' ELSE AFIH.WAPOS END || AFIH.ABNUM || AFRU.VORNR AS "key"
	,AFRU.ISMNE AS "actual_work_uom"
    ,AFIH.WARPL AS "maintenance_plan"
    ,AFIH.WAPOS AS "maintenance_item"
    ,AFIH.ABNUM AS "call_number"
    ,AFIH.ILART AS "activity_type"
	,AFRU.ERNAM AS "entered_by"
    ,AFRU.PERNR AS "entered_for"
    ,CRHD.ARBPL AS "work_center"
	,AFRU.ERSDA AS "entered_date"
	,AFRU.ERZET AS "entered_time"
    ,AFRU.AUERU AS "is_final"
    ,AFKO.GETRI AS "order_finish_date"
    ,AFKO.GEUZI AS "order_finish_time"
    ,AFRU.IEDD AS "operation_finish_date"
    ,AFRU.IEDZ AS "operation_finish_time"
	--,AFRU.ISDD AS "Start Date"
	--,AFRU.ISDZ AS "Start Time"
	--,AFRU.IEDD AS "Finish Date"
	--,AFRU.IEDZ AS "Finish Time"
	--AFRU.ARBID AS "Object ID",
	--AFRU.BUDAT AS "Posting Date",
    ,AFRU.STOKZ AS "is_reversed"
    ,AFRU.LTXA1 AS "confirmation_text"

FROM AFRU
JOIN AFVC ON AFVC.RUECK = AFRU.RUECK
JOIN AFKO ON AFKO.AUFPL = AFVC.AUFPL
JOIN AUFK ON AUFK.AUFNR = AFKO.AUFNR
JOIN AFIH ON AFIH.AUFNR = AUFK.AUFNR
JOIN AFVV ON AFVV.AUFPL = AFVC.AUFPL AND AFVV.APLZL = AFVC.APLZL
JOIN CRHD ON CRHD.OBJID = AFRU.ARBID

WHERE AFRU.ERSDA BETWEEN '20181014' AND '20181020'
AND AUFK.AUART IN ('8F01', '8F02', '8F03', '8F06')

--AND AFIH.WARPL = '100000002299' AND AFIH.WAPOS = '0000000000004710'
--AND AFIH.AUFNR = '00080036'
--AND CRHD.ARBPL = 'ELEC_P'
--AND AFRU.IEDD != AFRU.ERSDA
;