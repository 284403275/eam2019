SELECT
    --A."cycle"
    --,AFKO.GETRI AS "confirmed_finish_date"
    CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'IYYY Q') ELSE NULL END AS "finish_year"
    --,CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'iw') ELSE NULL END AS "finish_ww"
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
        END  AS "main_work_center"
    ,COUNT(AUFK.VAPLZ) AS "total"
    
    ,SUM(CASE WHEN (A.NPLDA != '00000000' AND AFKO.GETRI != '00000000') THEN
        CASE 
            WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(A.NPLDA ,'YYYYMMDD'))) > CASE WHEN A."cycle" <= 7 THEN 3 ELSE CEIL(A."cycle" * .1) END 
                THEN '1'
            WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(A.NPLDA ,'YYYYMMDD'))) * -1 > CASE WHEN A."cycle" <= 7 THEN 3 ELSE CEIL(A."cycle" * .1) END
                THEN '1'
            ELSE NULL
            END
        END) AS "ooc"
        
    ,SUM(CASE WHEN AFKO.GETRI BETWEEN AFKO.GSTRP AND AFKO.GLTRP THEN NULL ELSE 1 END) AS "v_ooc"
FROM AFIH

JOIN AFKO ON AFKO.AUFNR = AFIH.AUFNR
JOIN AUFK ON AUFK.AUFNR = AFIH.AUFNR
FULL OUTER JOIN (
    SELECT
        WARPL
        ,NPLDA
        ,ABNUM
        ,MAX(ROUND(ZYKZT / 3600 / 24, 2)) AS "cycle"
    FROM MHIS
    GROUP BY WARPL, NPLDA, ABNUM
) "A" ON A.WARPL = AFIH.WARPL AND A.ABNUM = AFIH.ABNUM

WHERE AUFK.AUART IN ('8F02') AND AUFK.LOEKZ != 'X' AND AFKO.GETRI != '00000000' AND CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'YYYY') ELSE NULL END > 2016
--AND to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'iw') != to_char(CURRENT_DATE,'iw')
--AND AUFK.VAPLZ IN ('CHEM_P', 'SLUR_P')

GROUP BY 
    CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'IYYY Q') ELSE NULL END
    --,CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'iw') ELSE NULL END
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
        END
        

ORDER BY CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'IYYY Q') ELSE NULL END
    --,CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'iw') ELSE NULL END
;