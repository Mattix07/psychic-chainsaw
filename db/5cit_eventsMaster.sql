
DELETE DATABASE IF EXISTS 5cit_eventsMaster
CREATE DATABASE 5cit_eventsMaster
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE 5cit_eventsMaster;

CREATE TABLE Manifestazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL
);

CREATE TABLE Locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Stato VARCHAR(50) NOT NULL,
    Regione VARCHAR(50) NOT NULL,
    CAP INT NOT NULL,
    Città VARCHAR(50) NOT NULL,
    civico VARCHAR(10) NULL
);

CREATE TABLE Eventi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idManifestazione INT NOT NULL,
    idLocation INT NOT NULL,
    Nome VARCHAR(100) NOT NULL,
    PrezzoNoMod DECIMAL(8,2) NOT NULL,
    Data DATE NOT NULL,
    OraI TIME NOT NULL,
    OraF TIME NOT NULL,
    Programma TEXT,
    FOREIGN KEY (idManifestazione) REFERENCES Manifestazioni(id) ON DELETE CASCADE,
    FOREIGN KEY (idLocation) REFERENCES Locations(id) ON DELETE RESTRICT
);

CREATE TABLE Intrattenitori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Mestiere VARCHAR(100) NOT NULL
);

CREATE TABLE Tempi (
    OraI TIME NOT NULL,
    OraF TIME NOT NULL,
    PRIMARY KEY (OraI, OraF)
);

CREATE TABLE Esibizioni (
    idEvento INT NOT NULL,
    idIntrattenitore INT NOT NULL,
    OraI TIME NOT NULL,
    OraF TIME NOT NULL,
    PRIMARY KEY (idEvento, idIntrattenitore, OraI, OraF),
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idIntrattenitore) REFERENCES Intrattenitori(id) ON DELETE CASCADE,
    FOREIGN KEY (OraI, OraF) REFERENCES Tempi(OraI, OraF)
);

CREATE TABLE Utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Cognome VARCHAR(100) NOT NULL,
    Email VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE Recensioni (
    idEvento INT NOT NULL,
    idUtente INT NOT NULL,
    Voto INT NOT NULL CHECK (Voto BETWEEN 1 AND 5),
    Messaggio TEXT NULL,
    PRIMARY KEY (idEvento, idUtente),
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE
);

CREATE TABLE Organizzatori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Cognome VARCHAR(100) NOT NULL,
    Ruolo VARCHAR(50) NOT NULL
);

CREATE TABLE Organizzatori_Evento (
    idEvento INT NOT NULL,
    idOrganizzatore INT NOT NULL,
    PRIMARY KEY (idEvento, idOrganizzatore),
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idOrganizzatore) REFERENCES Organizzatori(id) ON DELETE CASCADE
);

CREATE TABLE Settori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idLocation INT NOT NULL,
    Posti INT NOT NULL,
    MoltiplicatorePrezzo DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (idLocation) REFERENCES Locations(id) ON DELETE CASCADE
);

CREATE TABLE Tipo (
    nome VARCHAR(50) PRIMARY KEY,
    ModificatorePrezzo DECIMAL(5,2) NOT NULL
);

CREATE TABLE Biglietti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idEvento INT NOT NULL,
    idClasse VARCHAR(50) NOT NULL,
    `Check` BOOLEAN DEFAULT FALSE,
    Nome VARCHAR(100) NOT NULL,
    Cognome VARCHAR(100) NOT NULL,
    Sesso ENUM('M','F','Altro') NOT NULL,
    QRcode BLOB NOT NULL,
    FOREIGN KEY (idEvento) REFERENCES Eventi(id) ON DELETE CASCADE,
    FOREIGN KEY (idClasse) REFERENCES Tipo(nome) ON DELETE RESTRICT
);

CREATE TABLE Settore_Biglietti (
    idSettore INT NOT NULL,
    idBiglietto INT NOT NULL,
    Fila VARCHAR(5) NOT NULL,
    Numero INT NOT NULL,
    PRIMARY KEY (idSettore, idBiglietto),
    FOREIGN KEY (idSettore) REFERENCES Settori(id) ON DELETE CASCADE,
    FOREIGN KEY (idBiglietto) REFERENCES Biglietti(id) ON DELETE CASCADE
);

CREATE TABLE Ordini (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Metodo ENUM('Carta','PayPal','Bonifico') NOT NULL
);

CREATE TABLE Ordine_Biglietti (
    idOrdine INT NOT NULL,
    idBiglietto INT NOT NULL,
    PRIMARY KEY (idOrdine, idBiglietto),
    FOREIGN KEY (idOrdine) REFERENCES Ordini(id) ON DELETE CASCADE,
    FOREIGN KEY (idBiglietto) REFERENCES Biglietti(id) ON DELETE CASCADE
);

CREATE TABLE Utente_Ordini (
    idUtente INT NOT NULL,
    idOrdine INT NOT NULL,
    PRIMARY KEY (idUtente, idOrdine),
    FOREIGN KEY (idUtente) REFERENCES Utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (idOrdine) REFERENCES Ordini(id) ON DELETE CASCADE
);
