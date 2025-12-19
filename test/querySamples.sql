-- 1.Elenco di una manifestazione
select m.nome, e.nome, e.OraI, e.OraF 
from eventi e, manifestazioni m 
where e.idManifestazione=m.id and m.nome = "[nome di una manifestazione]"
order by e.OraI asc;

-- 2.Programma di un evento