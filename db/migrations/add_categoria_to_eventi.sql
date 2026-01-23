-- Aggiunge la colonna Categoria alla tabella Eventi
-- Esegui questo script per aggiornare il database esistente

USE 5cit_eventsMaster;

-- Aggiungi colonna Categoria se non esiste
ALTER TABLE Eventi
ADD COLUMN IF NOT EXISTS Categoria ENUM('concerti','teatro','sport','comedy','cinema','famiglia') DEFAULT 'famiglia' AFTER Immagine;

-- Crea indice per migliorare le performance delle query filtrate per categoria
CREATE INDEX IF NOT EXISTS idx_categoria ON Eventi(Categoria);

-- Aggiorna gli eventi esistenti con una categoria di default
UPDATE Eventi SET Categoria = 'famiglia' WHERE Categoria IS NULL;
