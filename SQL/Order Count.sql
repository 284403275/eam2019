SELECT
    --TO_CHAR(TO_DATE(AUFK.ERDAT, 'YYYYMMDD'), 'YYYY-MM') AS "date"
    TO_CHAR(TO_DATE(AFKO.GETRI, 'YYYYMMDD'), 'YYYY-MM') AS "date"
    --,AUFK.AUART
    ,COUNT(AUFK.AUFNR) AS "count"
FROM AUFK

inner join AFIH on AFIH.AUFNR = AUFK.AUFNR
inner join AFKO on AFKO.AUFNR = AFIH.AUFNR

WHERE AUFK.AUART NOT IN ('8O01')
AND AUFK.WERKS = '8000'

--GROUP BY TO_CHAR(TO_DATE(AUFK.ERDAT, 'YYYYMMDD'), 'YYYY-MM')--, AUFK.AUART

GROUP BY TO_CHAR(TO_DATE(AFKO.GETRI, 'YYYYMMDD'), 'YYYY-MM')

ORDER BY TO_CHAR(TO_DATE(AFKO.GETRI, 'YYYYMMDD'), 'YYYY-MM')
;