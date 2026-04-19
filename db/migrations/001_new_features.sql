-- ============================================================
-- Migrazione 001: Nuove funzionalità (F3, F6, F7, F8, F11, F12, F13, F14, F15)
-- Da eseguire sia in locale che sul server di produzione
-- ============================================================

-- F3: is_owner in collaboratorieventi
ALTER TABLE collaboratorieventi
    ADD COLUMN IF NOT EXISTS is_owner TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

-- F8: stato/moderazione recensioni
ALTER TABLE recensioni
    ADD COLUMN IF NOT EXISTS stato ENUM('visibile','nascosta','segnalata') NOT NULL DEFAULT 'visibile' AFTER testo,
    ADD COLUMN IF NOT EXISTS moderata_da INT NULL,
    ADD COLUMN IF NOT EXISTS moderata_at DATETIME NULL;

-- Evita errore se la FK esiste già
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'recensioni'
      AND CONSTRAINT_NAME = 'fk_recensioni_moderata_da'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE recensioni ADD CONSTRAINT fk_recensioni_moderata_da FOREIGN KEY (moderata_da) REFERENCES utenti(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- F11: documento identità biglietti
ALTER TABLE biglietti
    ADD COLUMN IF NOT EXISTS documento_foto MEDIUMBLOB NULL,
    ADD COLUMN IF NOT EXISTS documento_tipo ENUM('ci','passaporto','patente') NULL,
    ADD COLUMN IF NOT EXISTS documento_verificato TINYINT(1) NOT NULL DEFAULT 0;

-- F12: stato verifica documento
ALTER TABLE biglietti
    ADD COLUMN IF NOT EXISTS documento_verifica_stato ENUM('nessuno','caricato','verificato','rifiutato') NOT NULL DEFAULT 'nessuno';

-- F15: ruolo artista
ALTER TABLE utenti
    MODIFY COLUMN ruolo ENUM('admin','mod','promoter','artista','user') NOT NULL DEFAULT 'user';

-- F15: colonne aggiuntive intrattenitori
ALTER TABLE intrattenitori
    ADD COLUMN IF NOT EXISTS idUtente INT NULL,
    ADD COLUMN IF NOT EXISTS bio TEXT NULL,
    ADD COLUMN IF NOT EXISTS foto MEDIUMBLOB NULL,
    ADD COLUMN IF NOT EXISTS social_links JSON NULL;

-- Aggiungi UNIQUE e FK solo se non esistono
SET @uq_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'intrattenitori'
      AND CONSTRAINT_NAME = 'uq_intrattenitori_utente'
);
SET @sql = IF(@uq_exists = 0,
    'ALTER TABLE intrattenitori ADD UNIQUE KEY uq_intrattenitori_utente (idUtente)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk2_exists = (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'intrattenitori'
      AND CONSTRAINT_NAME = 'fk_intrattenitori_utente'
);
SET @sql = IF(@fk2_exists = 0,
    'ALTER TABLE intrattenitori ADD CONSTRAINT fk_intrattenitori_utente FOREIGN KEY (idUtente) REFERENCES utenti(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- F15: tabella richieste ruolo artista
CREATE TABLE IF NOT EXISTS artista_claims (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    idUtente      INT NOT NULL,
    idIntrattenitore INT NOT NULL,
    stato         ENUM('pending','approvata','rifiutata') NOT NULL DEFAULT 'pending',
    messaggio     TEXT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    gestita_da    INT NULL,
    gestita_at    DATETIME NULL,
    CONSTRAINT fk_claims_utente          FOREIGN KEY (idUtente)          REFERENCES utenti(id)          ON DELETE CASCADE,
    CONSTRAINT fk_claims_intrattenitore  FOREIGN KEY (idIntrattenitore)  REFERENCES intrattenitori(id)  ON DELETE CASCADE,
    CONSTRAINT fk_claims_gestita_da      FOREIGN KEY (gestita_da)        REFERENCES utenti(id)          ON DELETE SET NULL
);

-- F1: SVG piantina location
ALTER TABLE locations
    ADD COLUMN IF NOT EXISTS svg_path TEXT NULL COMMENT 'SVG piantina location';

ALTER TABLE settore_biglietti
    ADD COLUMN IF NOT EXISTS svg_seat_id VARCHAR(20) NULL COMMENT 'ID elemento SVG (es. A1, B3)';
