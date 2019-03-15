SELECT
    QMEL.QMNUM AS "notification"
    ,QMEL.QMART AS "type"
    ,ILOA.EQFNR AS "Sort Field"
    ,ILOA.TPLNR AS "FLOC"
    ,QMEL.QMDAB AS "complete_by"
    ,QMEL.QMZAB AS "complete_by_time"
    ,QMEL.QMNAM AS "created_by"
    ,QMEL.ERDAT AS "created_at"
    ,QMEL.ERZEIT AS "created_at_time"
    ,QMEL.QMTXT AS "description"
    ,QMEL.PHASE AS "phase"
    ,QMEL.AUFNR AS "order"
    ,CASE WHEN QMEL.PRIOK = ' ' THEN NULL ELSE QMEL.PRIOK END AS "priority"
    ,CRHD.ARBPL AS "work_center_raw"
    ,CASE CRHD.ARBPL
            WHEN 'TAB_P' THEN 'FMT'
            WHEN 'FMG_P' THEN 'FMT'
            WHEN 'MIC_P' THEN 'CMT'
            WHEN 'IC' THEN 'IC'
            WHEN 'WTR_P' THEN 'WWT/WTR'
            WHEN 'ELEC_P' THEN 'ELEC'
            WHEN 'GAS_P' THEN 'GAS'
            WHEN 'CHEM_P' THEN 'CHEM'
            WHEN 'SLUR_P' THEN 'SLURY'
            WHEN 'ALOP_P' THEN 'AL OP'
            WHEN 'BS' THEN 'BS'
            WHEN 'GAS' THEN 'GAS'
            WHEN 'O-LIT' THEN 'Ops Litho'
            ELSE 'Unknown'
        END AS "work_center"
    ,QMIH.MSAUS AS "breakdown_flag"
    ,QMIH.AUSZT AS "breakdown_duration"
    ,QMEL.QMGRP AS "coding_group"
    ,QMEL.QMCOD AS "coding"
    ,(SELECT 
        KURZTEXT
        FROM QPCT
        WHERE QPCT.CODEGRUPPE = QMEL.QMGRP AND QPCT.CODE = QMEL.QMCOD AND QPCT.SPRACHE = 'E'
    ) AS "code_text"
    ,QMFE.FENUM AS "item_number"
    ,QMFE.OTGRP AS "part_code_group"
    ,QMFE.OTEIL AS "part_code"
    ,(SELECT 
        KURZTEXT
        FROM QPCT
        WHERE QPCT.CODEGRUPPE = QMFE.OTGRP AND QPCT.CODE = QMFE.OTEIL AND QPCT.SPRACHE = 'E'
    ) AS "part_text"
    ,QMFE.FEGRP AS "damage_code_group"
    ,QMFE.FETXT AS "user_damage_text"
    ,(SELECT 
        KURZTEXT
        FROM QPCT
        WHERE QPCT.CODEGRUPPE = QMFE.FEGRP AND QPCT.CODE = QMFE.FECOD AND QPCT.SPRACHE = 'E'
    ) AS "damage_text"
    ,QMFE.FECOD AS "damage_code"
    ,QMUR.URGRP AS "cause_code_group"
    ,QMUR.URCOD AS "cause_code"
    ,(SELECT 
        KURZTEXT
        FROM QPCT
        WHERE QPCT.CODEGRUPPE = QMUR.URGRP AND QPCT.CODE = QMUR.URCOD AND QPCT.SPRACHE = 'E'
    ) AS "cause_text"
    
    --,QMMA.MNGRP AS "activity_group"
    --,QMMA.FENUM AS "for_item_number"
    --,QMMA.MNCOD AS "activity_code"
    --,QMMA.MATXT AS "activity_text"
FROM QMEL
    JOIN CRHD ON CRHD.OBJID = QMEL.ARBPL
    JOIN QMIH ON QMIH.QMNUM = QMEL.QMNUM
    JOIN ILOA ON ILOA.ILOAN = QMIH.ILOAN
    FULL OUTER JOIN QMFE ON QMFE.QMNUM = QMEL.QMNUM
    FULL OUTER JOIN QMUR ON QMUR.QMNUM = QMFE.QMNUM AND QMUR.FENUM = QMFE.FENUM

WHERE QMEL.QMNUM = '003000109182'
--AND QMEL.QMART = '81'

;