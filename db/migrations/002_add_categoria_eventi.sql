-- =====================================================
-- Migrazione: Aggiunta colonna Categoria a Eventi
-- =====================================================

-- Aggiungi la colonna Categoria se non esiste
ALTER TABLE Eventi
ADD COLUMN IF NOT EXISTS Categoria ENUM('concerti','teatro','sport','eventi') DEFAULT 'eventi';

-- Cambia Locandina da IMAGE a BLOB (IMAGE non e supportato in MySQL)
ALTER TABLE Eventi
MODIFY COLUMN Locandina BLOB;

-- Aggiorna eventi esistenti basandosi sul nome della manifestazione
UPDATE Eventi e
JOIN Manifestazioni m ON e.idManifestazione = m.id
SET e.Categoria = CASE
    WHEN m.Nome LIKE '%Musica%' OR m.Nome LIKE '%Jazz%' OR m.Nome LIKE '%Rock%' OR m.Nome LIKE '%Sanremo%' THEN 'concerti'
    WHEN m.Nome LIKE '%Teatro%' OR m.Nome LIKE '%Opera%' OR m.Nome LIKE '%Ravenna%' OR m.Nome LIKE '%Taormina%' THEN 'teatro'
    WHEN m.Nome LIKE '%Serie A%' OR m.Nome LIKE '%Champions%' OR m.Nome LIKE '%Coppa%' THEN 'sport'
    ELSE 'eventi'
END
WHERE e.Categoria IS NULL OR e.Categoria = 'eventi';
