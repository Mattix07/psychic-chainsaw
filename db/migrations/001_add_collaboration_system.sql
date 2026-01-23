-- =====================================================
-- MIGRATION 001: Sistema Collaborazione e Permessi
-- =====================================================

USE 5cit_eventsMaster;

-- Tabella per tracciare chi ha creato eventi/locations/manifestazioni
CREATE TABLE IF NOT EXISTS CreatoriEventi (
    idEvento INT NOT NULL,
    idUtente INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idEvento, idUtente),
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS CreatoriLocations (
    idLocation INT NOT NULL,
    idUtente INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idLocation, idUtente),
    FOREIGN KEY (idLocation) REFERENCES Locations(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS CreatoriManifestazioni (
    idManifestazione INT NOT NULL,
    idUtente INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idManifestazione, idUtente),
    FOREIGN KEY (idManifestazione) REFERENCES Manifestazioni(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabella per collaboratori di eventi
CREATE TABLE IF NOT EXISTS CollaboratoriEventi (
    idEvento INT NOT NULL,
    idUtente INT NOT NULL,
    invitato_da INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idEvento, idUtente),
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (invitato_da) REFERENCES Utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabella per notifiche email
CREATE TABLE IF NOT EXISTS Notifiche (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('modifica_evento', 'invito_collaborazione', 'verifica_account', 'altro') NOT NULL,
    destinatario_id INT NOT NULL,
    mittente_id INT NULL,
    oggetto VARCHAR(255) NOT NULL,
    messaggio TEXT NOT NULL,
    email_inviata TINYINT(1) DEFAULT 0,
    letta TINYINT(1) DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destinatario_id) REFERENCES Utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (mittente_id) REFERENCES Utenti(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabella per settori disponibili per evento
CREATE TABLE IF NOT EXISTS EventiSettori (
    idEvento INT NOT NULL,
    idSettore INT NOT NULL,
    PRIMARY KEY (idEvento, idSettore),
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idSettore) REFERENCES Settori(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Aggiungi campo avatar agli utenti
ALTER TABLE Utenti ADD COLUMN IF NOT EXISTS Avatar MEDIUMBLOB NULL;

-- Aggiungi campo created_at alle Recensioni per gestire il periodo di 2 settimane
ALTER TABLE Recensioni ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Aggiungi campo Stato ai Biglietti per gestire carrello
ALTER TABLE Biglietti ADD COLUMN IF NOT EXISTS Stato ENUM('carrello', 'acquistato') DEFAULT 'acquistato';
ALTER TABLE Biglietti ADD COLUMN IF NOT EXISTS idUtente INT NULL;
ALTER TABLE Biglietti ADD COLUMN IF NOT EXISTS DataCarrello TIMESTAMP NULL;

-- Aggiungi FK per idUtente nei Biglietti se non esiste
ALTER TABLE Biglietti ADD CONSTRAINT fk_biglietti_utente
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE SET NULL;

SELECT 'Migration 001 completata con successo!' AS Messaggio;
