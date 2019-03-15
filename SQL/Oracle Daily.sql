
--SELECT * FROM
    --(
        --SELECT a.*, ROWNUM rnum FROM
        --(
        
SELECT
	AUFK.AUFNR AS "Order"
    ,AUFK.AEDAT AS "Changed Date"
    ,AUFK.AUART AS "Order Type"
    ,AFIH.PRIOK AS "Priority"
    ,AFIH.ILART AS "Activity Type"
    ,ILOA.EQFNR AS "Tag"
    ,ILOA.ABCKZ AS "ABC"
    ,AUFK.KTEXT AS "Order Description"
    
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
    
    ,AUFK.OBJNR AS "Object"
    ,CASE AFKO.GLTRP WHEN '00000000' THEN NULL ELSE TO_CHAR(TO_DATE(AFKO.GLTRP, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Basic Finish Date"
    ,CASE AUFK.ERDAT WHEN '00000000' THEN NULL ELSE TO_CHAR(TO_DATE(AUFK.ERDAT, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Created On"
    ,AFKO.GLUZP AS "Basic Finish Time"
    ,CRHD.ARBPL AS "Work Center"
    ,CASE AFKO.GETRI WHEN '00000000' THEN NULL ELSE TO_CHAR(TO_DATE(AFKO.GETRI, 'YYYYMMDD'), 'YYYY-MM-DD') END AS "Confirmed Finish Date"
    ,AFKO.GEUZI AS "Confirmed Finish Time"
    ,CASE AUFK.ZZ_COMPLSTART_DATE WHEN '00000000' THEN NULL ELSE TO_DATE(AUFK.ZZ_COMPLSTART_DATE , 'YYYYMMDD') END AS "Compliance Start"
    ,CASE AUFK.ZZ_COMPLEND_DATE WHEN '00000000' THEN NULL ELSE TO_DATE(AUFK.ZZ_COMPLEND_DATE, 'YYYYMMDD') END AS "Compliance End"
    ,FLOOR(SYSDATE - TO_DATE(AUFK.ERDAT, 'YYYYMMDD')) AS "Days Open"
    --,ROWNUM rnk
/*
    ,(
        SELECT ROUND(AVG(AFVC.ANZZL),2) 
        FROM AFVC 
        JOIN AFVV ON AFVV.AUFPL = AFVC.AUFPL AND AFVV.APLZL = AFVC.APLZL 
        WHERE AFVC.AUFPL = AFKO.AUFPL
        ) AS "Avg Capacity"
    ,(
        SELECT SUM(AFVV.DAUNO) 
        FROM AFVC 
        JOIN AFVV ON AFVV.AUFPL = AFVC.AUFPL AND AFVV.APLZL = AFVC.APLZL 
        WHERE AFVC.AUFPL = AFKO.AUFPL
        ) AS "Total Est"
    ,(
        SELECT COUNT(DISTINCT AFRU.PERNR)
        FROM AFRU
        JOIN AFVC ON AFVC.RUECK = AFRU.RUECK
        WHERE AFRU.STOKZ != 'X'
        AND AFVC.AUFPL = AFKO.AUFPL
        
    ) AS "Actual Capacity"
    
    ,(
        SELECT SUM(AFRU.ISMNW)
        FROM AFRU
        JOIN AFVC ON AFVC.RUECK = AFRU.RUECK
        WHERE AFRU.STOKZ != 'X'
        AND AFVC.AUFPL = AFKO.AUFPL
        
    ) AS "Actual Work"
   */
FROM AUFK
JOIN AFIH ON AFIH.AUFNR = AUFK.AUFNR
JOIN AFKO ON AFKO.AUFNR = AFIH.AUFNR
JOIN CRHD ON CRHD.OBJID = AFIH.GEWRK
JOIN ILOA ON ILOA.ILOAN = AFIH.ILOAN


WHERE 
AUFK.AUART NOT IN ('8O01')
AND AUFK.AUART IN ('8F01', '8F02', '8F03')
--AND (AFKO.GLTRP != '00000000' OR AFKO.GLTRP IS NOT NULL)
--AND TO_DATE(AFKO.GLTRP, 'YYYYMMDD') BETWEEN TO_DATE('20170907', 'YYYYMMDD') AND TO_DATE('20170907', 'YYYYMMDD')
--AND (AFKO.GLTRP >= '20171114' AND AFKO.GLTRP <= '20171114')
--AND AFKO.GETRI BETWEEN '20171002' AND '20171008'
AND AUFK.LOEKZ != 'X'
AND AFIH.IWERK = '8000'
--AND CRHD.ARBPL = 'FMG_P'
/*
--Actual Finish Dates/Times
AND CONCAT(AFKO.GETRI, AFKO.GEUZI) >= '20171210170000'
AND CONCAT(AFKO.GETRI, AFKO.GEUZI) <= '20171211235959'
*/


--Basic Finish Dates/Time
--AND CONCAT(AFKO.GLTRP, AFKO.GLUZP) >= '20171211170000'
--AND CONCAT(AFKO.GLTRP, AFKO.GLUZP) <= '20171212235959'
AND CONCAT(AFKO.GLTRP, AFKO.GLUZP) BETWEEN '20180801000000' AND '20180830235959'

--Created On
--AND AUFK.ERDAT >= '20171201'
--AND AUFK.ERDAT <= '20171231'
--AND AUFK.AUART NOT IN ('8O01')
--AND AUFK.AUART IN ('8F01', '8F02')

--AND CRHD.ARBPL IN ('FMG_P', 'TAB_P')
--AND (AFKO.GETRI >= '20171210' AND AFKO.GEUZI >= '170000')
--OR (AFKO.GETRI <= '20171211' AND AFKO.GEUZI <= '235959')
--AND (AFKO.GETRI BETWEEN '20171210' AND '20171211' AND (AFKO.GEUZI >= '170000' AND AFKO.GEUZI <= '235959'))
--AND AFKO.GETRI = '20171128'

--AND AFKO.GLTRP = '20171212'

--AND AUFK.AUART IN ('8F01')
--AND (AFKO.GETRI >= '20171016' AND AFKO.GETRI <= '20171016')
--AND AUFK.AUFNR IN ('000800156638')
--OR AUFK.AUFNR IN ('000800160655', '000800176337', '000800176338', '000800176339', '000800175388', '000800176374', '000800176375', '000800175938', '000800175945', '000800173410', '000800173411', '000800173423', '000800173831', '000800128958', '000800159411', '000800159412', '000800172986', '000800168097', '000800176793', '000800176794', '000800176795', '000800174277', '000800174278', '000800174279', '000800176886', '000800176887', '000800173837', '000800173843', '000800173844', '000800147819', '000800150456', '000800159423', '000800165889', '000800165890', '000800165891', '000800165892', '000800165921', '000800163572', '000800169556', '000800169217', '000800169218', '000800176340', '000800176342', '000800174248', '000800174266', '000800174273', '000800173413', '000800176879', '000800176880', '000800177129', '000800177402', '000800177403', '000800177404', '000800174417', '000800174419', '000800174420', '000800174738', '000800174739', '000800174740', '000800174741', '000800174743', '000800174744', '000800174745', '000800174746', '000800174750', '000800174751', '000800174752', '000800174753', '000800177414', '000800177417', '000800177418', '000800174443', '000800173855', '000800173856', '000800173857', '000800173858', '000800174839', '000800167246', '000800167432', '000800167433', '000800167434', '000800167435', '000800171715', '000800169550', '000800169551', '000800169580', '000800169590', '000800175545', '000800177828', '000800177829', '000800177830', '000800177851', '000800177852', '000800174848', '000800177510', '000800175387', '000800176878', '000800174824', '000800174825', '000800174826', '000800174827', '000800174828', '000800174830', '000800174833', '000800174834', '000800158191', '000800165366', '000800165377', '000800165378', '000800166338', '000800166339', '000800164174', '000800165351', '000800165352', '000800165353', '000800165354', '000800165355', '000800165356', '000800165357', '000800165358', '000800156449', '000800170293', '000800168742', '000800170217', '000800172555', '000800171507', '000800178817', '000800178818', '000800178819', '000800177833', '000800178830', '000800178837', '000800178838', '000800178117', '000800178118', '000800178134', '000800178402', '000800178403', '000800178425', '000800178426', '000800178427', '000800178428', '000800178132', '000800178443', '000800178444', '000800175902', '000800176341', '000800176781', '000800176782', '000800176783', '000800176784', '000800176785', '000800176786', '000800176787', '000800176790', '000800176796', '000800174249', '000800175935', '000800176373', '000800175416', '000800175421', '000800175422', '000800175423', '000800175424', '000800175425', '000800175429', '000800177282', '000800177298', '000800176214', '000800176217', '000800176219', '000800176220', '000800173281', '000800174838', '000800176330')
/*
System status selection
*/

AND 
(
    
    NOT EXISTS (
        SELECT TJ02T.TXT04
        FROM JEST
        JOIN TJ02T ON JEST.STAT = TJ02T.ISTAT
        WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ02T.SPRAS = 'E'
        AND TJ02T.TXT04 IN ('DLFL', 'CNCL')
    )
    
    --, 'CNF', 'TECO', 'CLSD'
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
)

/*
User status selection
*/

--AND 
--(
    /*
    NOT EXISTS (
        SELECT TJ30T.TXT04
        FROM JEST
        JOIN JSTO ON JSTO.OBJNR = JEST.OBJNR
		JOIN TJ30T ON JSTO.STSMA = TJ30T.STSMA AND JEST.STAT = TJ30T.ESTAT
		WHERE JEST.OBJNR = AUFK.OBJNR AND JEST.INACT != 'X' AND TJ30T.SPRAS = 'E'
        AND TJ30T.TXT04 IN ('CNCL')
    )
    
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
   

--) WHERE rnk >= 1 AND ROWNUM <= 100 - 1 + 1

;