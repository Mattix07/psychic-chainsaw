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
INSERT INTO Manifestazioni (Nome) VALUES
('Estate in Piazza'),
('Festival della Musica'),
('Notte Bianca'),
('Fiera dell Arte'),
('Cinema sotto le Stelle');

-- =====================================================
-- 2. LOCATIONS
-- =====================================================
INSERT INTO Locations (Nome, Stato, Regione, CAP, Città, civico) VALUES
('Piazza Centrale', 'Italia', 'Lombardia', 20100, 'Milano', '10A'),
('Arena del Mare', 'Italia', 'Liguria', 16100, 'Genova', NULL),
('Teatro Comunale', 'Italia', 'Toscana', 50100, 'Firenze', '5'),
('Parco delle Cascine', 'Italia', 'Toscana', 50144, 'Firenze', NULL),
('Stadio Olimpico', 'Italia', 'Lazio', 00135, 'Roma', '12');

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
INSERT INTO Intrattenitori (Nome, Mestiere) VALUES
('Luca Rossi','Cantante'),
('DJ Nova','DJ'),
('Compagnia Teatro Vivo','Teatro'),
('Marco Bianchi','Comico'),
('Sara Verdi','Cantante'),
('Ensemble Jazz','Musica Jazz'),
('Clown Allegro','Comico'),
('Orchestra Sinfonica','Orchestra'),
('DJ Flash','DJ'),
('Teatro Luna','Teatro');

-- =====================================================
-- 5. UTENTI
-- =====================================================
INSERT INTO Utenti (Nome, Cognome, Email) VALUES
('Mario','Rossi','mario.rossi@example.com'),
('Luisa','Bianchi','luisa.bianchi@example.com'),
('Paolo','Verdi','paolo.verdi@example.com'),
('Anna','Gialli','anna.gialli@example.com'),
('Giovanni','Neri','giovanni.neri@example.com'),
('Chiara','Blu','chiara.blu@example.com'),
('Francesco','Rosa','francesco.rosa@example.com'),
('Laura','Viola','laura.viola@example.com'),
('Stefano','Arancio','stefano.arancio@example.com'),
('Martina','Celeste','martina.celeste@example.com');

-- =====================================================
-- 6. ORGANIZZATORI
-- =====================================================
INSERT INTO Organizzatori (Nome, Cognome, Ruolo) VALUES
('Giulia','Neri','Responsabile'),
('Marco','Bianchi','Assistente'),
('Luca','Verdi','Logistica'),
('Anna','Rossi','Marketing'),
('Paolo','Blu','Sponsor');

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
INSERT INTO Eventi (idManifestazione, idLocation, Nome, PrezzoNoMod, Data, OraI, OraF, Programma) VALUES
(1,1,'Concerto Rock',20.00,'2025-06-21','21:00:00','23:00:00','Band principali'),
(1,2,'DJ Set Serale',15.00,'2025-06-22','22:00:00','00:00:00','DJ famosi'),
(1,3,'Spettacolo Teatrale',12.00,'2025-06-23','20:30:00','22:30:00','Commedia teatrale'),
(2,1,'Festival Jazz',25.00,'2025-07-01','19:00:00','22:00:00','Jazz band internazionali'),
(2,2,'Concerto Pop',30.00,'2025-07-02','21:00:00','23:30:00','Pop stars'),
(2,3,'Opera Lirica',35.00,'2025-07-03','18:00:00','21:00:00','Opera classica'),
(3,4,'Notte Bianca Live',18.00,'2025-07-10','20:00:00','00:00:00','Musica e spettacoli'),
(3,5,'Cinema sotto le Stelle',10.00,'2025-07-11','21:00:00','23:00:00','Film all’aperto'),
(3,1,'Stand-up Comedy',12.00,'2025-07-12','21:30:00','23:00:00','Comici locali');

-- =====================================================
-- 9. ESIBIZIONI
-- =====================================================
INSERT INTO Esibizioni (idEvento, idIntrattenitore, OraI, OraF) VALUES
(1,1,'21:00:00','23:00:00'),
(2,2,'22:00:00','00:00:00'),
(3,3,'20:30:00','22:30:00'),
(4,6,'19:00:00','22:00:00'),
(5,5,'21:00:00','23:30:00');

-- =====================================================
-- 10. RECENSIONI
-- =====================================================
INSERT INTO Recensioni (idEvento, idUtente, Voto, Messaggio) VALUES
(1,1,5,'Concerto fantastico!'),
(1,2,4,'Bella atmosfera'),
(2,3,3,NULL),
(3,4,5,'Molto divertente'),
(4,5,4,'Interessante'),
(5,6,5,'Perfetto');

-- =====================================================
-- 11. SETTORI
-- =====================================================
INSERT INTO Settori (idLocation, Posti, MoltiplicatorePrezzo) VALUES
(1,100,1.00),
(2,50,1.20),
(3,80,1.50);

-- =====================================================
-- 12. BIGLIETTI
-- =====================================================
INSERT INTO Biglietti (idEvento, idClasse, Nome, Cognome, Sesso, QRcode) VALUES
(1,'Standard','Mario','Rossi','M',X'1234'),
(1,'VIP','Luisa','Bianchi','F',X'5678'),
(2,'Standard','Paolo','Verdi','M',X'9ABC'),
(3,'Premium','Anna','Gialli','F',X'DEF0');

-- =====================================================
-- 13. SETTORE_BIGLIETTI
-- =====================================================
INSERT INTO Settore_Biglietti (idSettore, idBiglietto, Fila, Numero) VALUES
(1,1,'A',1),
(1,2,'A',2),
(2,3,'B',1),
(3,4,'C',1);

-- =====================================================
-- 14. ORDINI
-- =====================================================
INSERT INTO Ordini (Metodo) VALUES
('Carta'),
('PayPal'),
('Bonifico');

-- =====================================================
-- 15. ORDINE_BIGLIETTI
-- =====================================================
INSERT INTO Ordine_Biglietti (idOrdine, idBiglietto) VALUES
(1,1),
(1,2),
(2,3),
(3,4);

-- =====================================================
-- 16. UTENTE_ORDINI
-- =====================================================
INSERT INTO Utente_Ordini (idUtente, idOrdine) VALUES
(1,1),
(2,1),
(3,2),
(4,3);

-- =====================================================
-- 17. ORGANIZZATORI_EVENTO
-- =====================================================
INSERT INTO Organizzatori_Evento (idEvento, idOrganizzatore) VALUES
(1,1),
(2,2),
(3,1),
(4,3),
(5,4);

-- =====================================================
-- FINE DUMP ESTESO ORDINATO
-- =====================================================
