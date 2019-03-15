select
    jcds.*
from equi
join jcds on jcds.OBJNR = equi.OBJNR
where equi.EQUNR = '000000002000012018'
;