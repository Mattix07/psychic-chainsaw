-- =====================================================
-- DUMP DI TEST ESTESO – EventsMaster
-- =====================================================

-- Pulizia tabelle (ordine corretto per FK)
DELETE FROM Utente_Ordini;
DELETE FROM Ordine_Biglietti;
DELETE FROM Biglietti;
DELETE FROM Settore_Biglietti;
DELETE FROM Ordini;
DELETE FROM Recensioni;
DELETE FROM Esibizioni;
DELETE FROM Tempi;
DELETE FROM Eventi;
DELETE FROM Intrattenitori;
DELETE FROM Organizzatori_Evento;
DELETE FROM Organizzatori;
DELETE FROM Settori;
DELETE FROM Tipo;
DELETE FROM Locations;
DELETE FROM Utenti;
DELETE FROM Manifestazioni;

-- =====================================================
-- DUMP ESTESO ORDINATO – EventsMaster
-- =====================================================

-- =====================================================
-- 1. MANIFESTAZIONI
-- =====================================================
INSERT INTO Manifestazioni (id, Nome) VALUES
(0001, 'Estate in Piazza'),
(0002, 'Festival della Musica'),
(0003,'Notte Bianca'),
(0004, 'Fiera dell Arte'),
(0005, 'Cinema sotto le Stelle');

-- =====================================================
-- 2. LOCATIONS
-- =====================================================
INSERT INTO Locations (id, Nome, Stato, Regione, CAP, Città, civico) VALUES
(0001, 'Piazza Centrale', 'Italia', 'Lombardia', 20100, 'Milano', '10A'),
(0002, 'Arena del Mare', 'Italia', 'Liguria', 16100, 'Genova', NULL),
(0003, 'Teatro Comunale', 'Italia', 'Toscana', 50100, 'Firenze', '5'),
(0004, 'Parco delle Cascine', 'Italia', 'Toscana', 50144, 'Firenze', NULL),
(0005, 'Stadio Olimpico', 'Italia', 'Lazio', 00135, 'Roma', '12');

-- =====================================================
-- 3. TEMPI
-- =====================================================
INSERT INTO Tempi (OraI, OraF) VALUES
('18:00:00','20:00:00'),
('19:00:00','21:00:00'),
('20:30:00','22:30:00'),
('21:00:00','23:00:00'),
('22:00:00','00:00:00');

-- =====================================================
-- 4. INTRATTENITORI
-- =====================================================
INSERT INTO Intrattenitori (id, Nome, Mestiere) VALUES
(0001,'Luca Rossi','Cantante'),
(0002,'DJ Nova','DJ'),
(0003,'Compagnia Teatro Vivo','Teatro'),
(0004,'Marco Bianchi','Comico'),
(0005,'Sara Verdi','Cantante'),
(0006,'Ensemble Jazz','Musica Jazz'),
(0007,'Clown Allegro','Comico'),
(0008,'Orchestra Sinfonica','Orchestra'),
(0009,'DJ Flash','DJ'),
(0010,'Teatro Luna','Teatro');

-- =====================================================
-- 5. UTENTI
-- =====================================================
INSERT INTO Utenti (id, Nome, Cognome, Email) VALUES
(0001,'Mario','Rossi','mario.rossi@example.com'),
(0002,'Luisa','Bianchi','luisa.bianchi@example.com'),
(0003,'Paolo','Verdi','paolo.verdi@example.com'),
(0004,'Anna','Gialli','anna.gialli@example.com'),
(0005,'Giovanni','Neri','giovanni.neri@example.com'),
(0006,'Chiara','Blu','chiara.blu@example.com'),
(0007,'Francesco','Rosa','francesco.rosa@example.com'),
(0008,'Laura','Viola','laura.viola@example.com'),
(0009,'Stefano','Arancio','stefano.arancio@example.com'),
(0010,'Martina','Celeste','martina.celeste@example.com');

-- =====================================================
-- 6. ORGANIZZATORI
-- =====================================================
INSERT INTO Organizzatori (id, Nome, Cognome, Ruolo) VALUES
(0001,'Giulia','Neri','Responsabile'),
(0002,'Marco','Bianchi','Assistente'),
(0003,'Luca','Verdi','Logistica'),
(0004,'Anna','Rossi','Marketing'),
(0005,'Paolo','Blu','Sponsor');

-- =====================================================
-- 7. TIPO
-- =====================================================
INSERT INTO Tipo (nome, ModificatorePrezzo) VALUES
('Standard',0.00),
('VIP',50.00),
('Premium',100.00);

-- =====================================================
-- 8. EVENTI
-- =====================================================
INSERT INTO Eventi (id, idManifestazione, idLocation, Nome, PrezzoNoMod, Data, OraI, OraF, Programma) VALUES
(0001,0001,0001,'Concerto Rock',20.00,'2025-06-21','21:00:00','23:00:00','Band principali'),
(0002,0001,0002,'DJ Set Serale',15.00,'2025-06-22','22:00:00','00:00:00','DJ famosi'),
(0003,0001,0003,'Spettacolo Teatrale',12.00,'2025-06-23','20:30:00','22:30:00','Commedia teatrale'),
(0004,0002,0001,'Festival Jazz',25.00,'2025-07-01','19:00:00','22:00:00','Jazz band internazionali'),
(0005,0002,0002,'Concerto Pop',30.00,'2025-07-02','21:00:00','23:30:00','Pop stars'),
(0006,0002,0003,'Opera Lirica',35.00,'2025-07-03','18:00:00','21:00:00','Opera classica'),
(0007,0003,0004,'Notte Bianca Live',18.00,'2025-07-10','20:00:00','00:00:00','Musica e spettacoli'),
(0008,0003,0005,'Cinema sotto le Stelle',10.00,'2025-07-11','21:00:00','23:00:00','Film all’aperto'),
(0009,0003,0001,'Stand-up Comedy',12.00,'2025-07-12','21:30:00','23:00:00','Comici locali');

-- =====================================================
-- 9. ESIBIZIONI
-- =====================================================
INSERT INTO Esibizioni (idEvento, idIntrattenitore, OraI, OraF) VALUES
(0001,0001,'21:00:00','23:00:00'),
(0002,0002,'22:00:00','00:00:00'),
(0003,0003,'20:30:00','22:30:00'),
(0004,0006,'19:00:00','21:00:00'),
(0005,0005,'21:00:00','23:00:00');

-- =====================================================
-- 10. RECENSIONI
-- =====================================================
INSERT INTO Recensioni (idEvento, idUtente, Voto, Messaggio) VALUES
(0001,0001,0005,'Concerto fantastico!'),
(0001,0002,0004,'Bella atmosfera'),
(0002,0003,0003,NULL),
(0003,0004,0005,'Molto divertente'),
(0004,0005,0004,'Interessante'),
(0005,0006,0005,'Perfetto');

-- =====================================================
-- 11. SETTORI
-- =====================================================
INSERT INTO Settori (id, idLocation, Posti, MoltiplicatorePrezzo) VALUES
(0001,0001,100,1.00),
(0002,0002,50,1.20),
(0003,0003,80,1.50);

-- =====================================================
-- 12. BIGLIETTI
-- =====================================================
INSERT INTO Biglietti (id, idEvento, idClasse, Nome, Cognome, Sesso, QRcode) VALUES
(0001,0001,'Standard','Mario','Rossi','M',X'1234'),
(0002,0001,'VIP','Luisa','Bianchi','F',X'5678'),
(0003,0002,'Standard','Paolo','Verdi','M',X'9ABC'),
(0004,0003,'Premium','Anna','Gialli','F',X'DEF0');

-- =====================================================
-- 13. SETTORE_BIGLIETTI
-- =====================================================
INSERT INTO Settore_Biglietti (idSettore, idBiglietto, Fila, Numero) VALUES
(0001,0001,'A',01),
(0001,0002,'A',22),
(0002,0003,'B',45),
(0003,0004,'C',12);

-- =====================================================
-- 14. ORDINI
-- =====================================================
INSERT INTO Ordini (id, Metodo) VALUES
(0001,'Carta'),
(0002,'PayPal'),
(0003,'Bonifico');

-- =====================================================
-- 15. ORDINE_BIGLIETTI
-- =====================================================
INSERT INTO Ordine_Biglietti (idOrdine, idBiglietto) VALUES
(0001,0001),
(0001,0002),
(0002,0003),
(0003,0004);

-- =====================================================
-- 16. UTENTE_ORDINI
-- =====================================================
INSERT INTO Utente_Ordini (idUtente, idOrdine) VALUES
(0001,0001),
(0002,0001),
(0003,0002),
(0004,0003);

-- =====================================================
-- 17. ORGANIZZATORI_EVENTO
-- =====================================================
INSERT INTO Organizzatori_Evento (idEvento, idOrganizzatore) VALUES
(0001,0001),
(0002,0002),
(0003,0001),
(0004,0003),
(0005,0004);

-- =====================================================
-- FINE DUMP ESTESO ORDINATO
-- =====================================================