-- ============================================================
-- SCHEMA COMPLETO DATABASE 5cit_eventsMaster
-- ============================================================

DROP DATABASE IF EXISTS 5cit_eventsMaster;
CREATE DATABASE 5cit_eventsMaster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE 5cit_eventsMaster;

-- ============================================================
-- TABELLE UTENTI
-- ============================================================

CREATE TABLE Utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Cognome VARCHAR(100) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    ruolo ENUM('admin', 'mod', 'promoter', 'user') DEFAULT 'user',
    verificato TINYINT(1) DEFAULT 0,
    Avatar MEDIUMBLOB DEFAULT NULL,
    DataRegistrazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    email_verification_token VARCHAR(100) DEFAULT NULL,
    INDEX idx_email (Email),
    INDEX idx_ruolo (ruolo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABELLE LOCATION E STRUTTURE
-- ============================================================

CREATE TABLE Locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(255) NOT NULL,
    Indirizzo VARCHAR(255),
    Citta VARCHAR(100),
    CAP VARCHAR(10),
    Regione VARCHAR(100),
    Capienza INT DEFAULT 0,
    INDEX idx_citta (Citta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Settori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Fila VARCHAR(10),
    Posto INT,
    idLocation INT NOT NULL,
    MoltiplicatorePrezzo DECIMAL(5,2) DEFAULT 1.00,
    PostiDisponibili INT DEFAULT 0,
    FOREIGN KEY (idLocation) REFERENCES Locations(id) ON DELETE CASCADE,
    INDEX idx_location (idLocation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABELLE EVENTI
-- ============================================================

CREATE TABLE Manifestazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(255) NOT NULL,
    Descrizione TEXT,
    DataInizio DATE,
    DataFine DATE,
    INDEX idx_date (DataInizio, DataFine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Eventi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(255) NOT NULL,
    Data DATE NOT NULL,
    OraI TIME,
    OraF TIME,
    Programma TEXT,
    PrezzoNoMod DECIMAL(10,2) NOT NULL,
    idLocation INT NOT NULL,
    idManifestazione INT DEFAULT NULL,
    Immagine VARCHAR(500) DEFAULT NULL,
    Categoria ENUM('concerti','teatro','sport','comedy','cinema','famiglia') DEFAULT 'famiglia',
    FOREIGN KEY (idLocation) REFERENCES Locations(id) ON DELETE CASCADE,
    FOREIGN KEY (idManifestazione) REFERENCES Manifestazioni(id) ON DELETE SET NULL,
    INDEX idx_data (Data),
    INDEX idx_location (idLocation),
    INDEX idx_manifestazione (idManifestazione),
    INDEX idx_categoria (Categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE EventiSettori (
    idEvento INT NOT NULL,
    idSettore INT NOT NULL,
    PRIMARY KEY (idEvento, idSettore),
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idSettore) REFERENCES Settori(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Intrattenitore (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(255) NOT NULL,
    Categoria VARCHAR(100) DEFAULT NULL,
    INDEX idx_categoria (Categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Evento_Intrattenitore (
    idEvento INT NOT NULL,
    idIntrattenitore INT NOT NULL,
    PRIMARY KEY (idEvento, idIntrattenitore),
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idIntrattenitore) REFERENCES Intrattenitore(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABELLE BIGLIETTI E ORDINI
-- ============================================================

CREATE TABLE Tipo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    ModificatorePrezzo DECIMAL(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Biglietti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100),
    Cognome VARCHAR(100),
    Sesso ENUM('M', 'F', 'Altro') DEFAULT 'Altro',
    idEvento INT NOT NULL,
    idClasse VARCHAR(50) DEFAULT 'Standard',
    Stato ENUM('carrello', 'acquistato', 'validato') DEFAULT 'carrello',
    idUtente INT DEFAULT NULL,
    DataCarrello DATETIME DEFAULT CURRENT_TIMESTAMP,
    QRCode VARCHAR(255) UNIQUE,
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE SET NULL,
    INDEX idx_stato (Stato),
    INDEX idx_utente (idUtente),
    INDEX idx_evento (idEvento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Settore_Biglietti (
    idBiglietto INT NOT NULL,
    idSettore INT NOT NULL,
    PRIMARY KEY (idBiglietto),
    FOREIGN KEY (idBiglietto) REFERENCES Biglietti(id) ON DELETE CASCADE,
    FOREIGN KEY (idSettore) REFERENCES Settori(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Ordini (
    id INT AUTO_INCREMENT PRIMARY KEY,
    MetodoPagamento VARCHAR(50),
    DataOrdine DATETIME DEFAULT CURRENT_TIMESTAMP,
    Totale DECIMAL(10,2) DEFAULT 0.00,
    INDEX idx_data (DataOrdine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Ordine_Biglietti (
    idOrdine INT NOT NULL,
    idBiglietto INT NOT NULL,
    PRIMARY KEY (idOrdine, idBiglietto),
    FOREIGN KEY (idOrdine) REFERENCES Ordini(id) ON DELETE CASCADE,
    FOREIGN KEY (idBiglietto) REFERENCES Biglietti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Utente_Ordini (
    idUtente INT NOT NULL,
    idOrdine INT NOT NULL,
    PRIMARY KEY (idUtente, idOrdine),
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (idOrdine) REFERENCES Ordini(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABELLE RECENSIONI
-- ============================================================

CREATE TABLE Recensioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idEvento INT NOT NULL,
    idUtente INT NOT NULL,
    Voto INT NOT NULL CHECK (Voto BETWEEN 1 AND 5),
    Commento TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_recensione (idEvento, idUtente),
    INDEX idx_evento (idEvento),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABELLE PERMESSI E COLLABORAZIONI
-- ============================================================

CREATE TABLE CreatoriEventi (
    idEvento INT NOT NULL,
    idUtente INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idEvento, idUtente),
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE CreatoriLocations (
    idLocation INT NOT NULL,
    idUtente INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idLocation, idUtente),
    FOREIGN KEY (idLocation) REFERENCES Locations(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE CreatoriManifestazioni (
    idManifestazione INT NOT NULL,
    idUtente INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idManifestazione, idUtente),
    FOREIGN KEY (idManifestazione) REFERENCES Manifestazioni(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE CollaboratoriEventi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idEvento INT NOT NULL,
    idUtente INT NOT NULL,
    invitato_da INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    token VARCHAR(100) UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (invitato_da) REFERENCES Utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_collaborazione (idEvento, idUtente),
    INDEX idx_token (token),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABELLE NOTIFICHE
-- ============================================================

CREATE TABLE Notifiche (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    destinatario_id INT NOT NULL,
    mittente_id INT DEFAULT NULL,
    oggetto VARCHAR(255),
    messaggio TEXT,
    email_inviata TINYINT(1) DEFAULT 0,
    letta TINYINT(1) DEFAULT 0,
    metadata TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destinatario_id) REFERENCES Utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (mittente_id) REFERENCES Utenti(id) ON DELETE SET NULL,
    INDEX idx_destinatario (destinatario_id),
    INDEX idx_letta (letta),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
