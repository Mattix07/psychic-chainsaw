-- Migration: Aggiunta ruoli utente e campi per email verification/password reset
-- Eseguire questo script per aggiornare il database

-- Aggiungi campo ruolo alla tabella Utenti
ALTER TABLE Utenti
ADD COLUMN IF NOT EXISTS ruolo ENUM('user', 'promoter', 'mod', 'admin') DEFAULT 'user' AFTER Email,
ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS verification_token VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS token_expiry DATETIME NULL,
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS reset_expiry DATETIME NULL,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Crea un utente admin di default (password: admin123)
INSERT INTO Utenti (Nome, Cognome, Email, Password, ruolo, email_verified)
VALUES ('Admin', 'EventsMaster', 'admin@eventsmaster.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1)
ON DUPLICATE KEY UPDATE ruolo = 'admin';

-- Crea indici per performance
CREATE INDEX IF NOT EXISTS idx_utenti_ruolo ON Utenti(ruolo);
CREATE INDEX IF NOT EXISTS idx_utenti_email_verified ON Utenti(email_verified);
CREATE INDEX IF NOT EXISTS idx_utenti_verification_token ON Utenti(verification_token);
CREATE INDEX IF NOT EXISTS idx_utenti_reset_token ON Utenti(reset_token);
