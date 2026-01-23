-- ============================================================
-- DATI PRODUZIONE - 5cit_eventsMaster
-- ============================================================
-- Eseguire DOPO schema_completo.sql
-- ============================================================

USE 5cit_eventsMaster;

-- ============================================================
-- UTENTI DI TEST (password: password123)
-- ============================================================

INSERT INTO Utenti (Nome, Cognome, Email, Password, ruolo, verificato) VALUES
('Admin', 'Sistema', 'admin@eventsmaster.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('Moderatore', 'Staff', 'mod@eventsmaster.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mod', 1),
('Promoter', 'Eventi', 'promoter@eventsmaster.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'promoter', 1),
('Mario', 'Rossi', 'user@eventsmaster.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1);

-- ============================================================
-- TIPI BIGLIETTO
-- ============================================================

INSERT INTO Tipo (nome, ModificatorePrezzo) VALUES
('Standard', 0.00),
('VIP', 50.00),
('Premium', 100.00),
('Ridotto', -10.00),
('Under 18', -15.00);

-- ============================================================
-- LOCATIONS
-- ============================================================

INSERT INTO Locations (Nome, Indirizzo, Citta, CAP, Regione, Capienza) VALUES
('Stadio San Siro', 'Piazzale Angelo Moratti', 'Milano', '20151', 'Lombardia', 80000),
('Stadio Olimpico', 'Viale dei Gladiatori', 'Roma', '00135', 'Lazio', 70000),
('Allianz Stadium', 'Corso Gaetano Scirea 50', 'Torino', '10151', 'Piemonte', 41500),
('Mediolanum Forum', 'Via G. di Vittorio 6', 'Assago', '20090', 'Lombardia', 12700),
('PalaAlpitour', 'Via Filadelfia 88', 'Torino', '10134', 'Piemonte', 12300),
('Palazzo dello Sport', 'Piazzale Pier Luigi Nervi', 'Roma', '00144', 'Lazio', 11000),
('Teatro alla Scala', 'Via Filodrammatici 2', 'Milano', '20121', 'Lombardia', 2030),
('Teatro dell\'Opera', 'Piazza Beniamino Gigli 1', 'Roma', '00184', 'Lazio', 1600),
('Teatro Regio', 'Piazza Castello 215', 'Torino', '10124', 'Piemonte', 1500),
('Alcatraz', 'Via Valtellina 25', 'Milano', '20159', 'Lombardia', 3000),
('Fabrique', 'Via Fantoli 9', 'Milano', '20138', 'Lombardia', 1500),
('Atlantico Live', 'Viale dell\'Oceano Atlantico 271', 'Roma', '00144', 'Lazio', 2000),
('Ippodromo San Siro', 'Piazzale dello Sport 16', 'Milano', '20151', 'Lombardia', 15000),
('Auditorium Parco della Musica', 'Viale Pietro de Coubertin 30', 'Roma', '00196', 'Lazio', 21000),
('Arena di Verona', 'Piazza Bra 1', 'Verona', '37121', 'Veneto', 15000);

-- ============================================================
-- SETTORI
-- ============================================================

INSERT INTO Settori (Nome, Fila, Posto, idLocation, MoltiplicatorePrezzo, PostiDisponibili) VALUES
('Tribuna Arancio', 'A', 1, 1, 1.5, 8000),
('Curva Sud', 'B', 1, 1, 1.0, 12000),
('Curva Nord', 'C', 1, 1, 1.0, 12000),
('Tribuna Rossa', 'D', 1, 1, 2.0, 5000),
('Parterre', 'A', 1, 4, 2.0, 2000),
('Tribuna', 'B', 1, 4, 1.5, 5000),
('Gradinata', 'C', 1, 4, 1.0, 5700),
('Platea', 'A', 1, 7, 3.0, 600),
('Palchi', 'B', 1, 7, 2.5, 800),
('Galleria', 'C', 1, 7, 1.5, 630),
('Pista', 'A', 1, 10, 1.0, 2000),
('Balconata', 'B', 1, 10, 1.3, 1000),
('Gradinata', 'A', 1, 15, 1.0, 8000),
('Poltronissime', 'B', 1, 15, 2.5, 3000),
('Tribuna', 'C', 1, 15, 1.5, 4000);

-- ============================================================
-- MANIFESTAZIONI
-- ============================================================

INSERT INTO Manifestazioni (Nome, Descrizione, DataInizio, DataFine) VALUES
('Rock in Italy Festival 2026', 'Festival internazionale di musica rock', '2026-06-15', '2026-06-17'),
('Opera Estate 2026', 'Stagione estiva operistica', '2026-07-01', '2026-08-31'),
('Milano Music Week', 'Settimana della musica a Milano', '2026-05-20', '2026-05-27'),
('Jazz & Wine Festival', 'Festival jazz con degustazioni', '2026-09-10', '2026-09-15'),
('Teatro Contemporaneo', 'Rassegna teatro contemporaneo', '2026-04-01', '2026-05-30');

-- ============================================================
-- INTRATTENITORI
-- ============================================================

INSERT INTO Intrattenitore (Nome, Categoria) VALUES
('Måneskin', 'Band'),
('Jovanotti', 'Cantante'),
('Vasco Rossi', 'Cantante'),
('Ligabue', 'Cantante'),
('Negramaro', 'Band'),
('Subsonica', 'Band'),
('Riccardo Muti', 'Direttore d\'Orchestra'),
('Orchestra Sinfonica Nazionale', 'Orchestra'),
('Ludovico Einaudi', 'Pianista'),
('Paolo Fresu', 'Trombettista'),
('Stefano Bollani', 'Pianista'),
('Compagnia Teatro Franco Parenti', 'Compagnia Teatrale'),
('Alessandro Gassman', 'Attore'),
('Paola Cortellesi', 'Attrice'),
('Maurizio Crozza', 'Comico'),
('Luca Bizzarri & Paolo Kessisoglu', 'Duo Comico');

-- ============================================================
-- EVENTI
-- ============================================================

INSERT INTO Eventi (Nome, Data, OraI, OraF, Programma, PrezzoNoMod, idLocation, idManifestazione) VALUES
('Måneskin - Rush! World Tour', '2026-06-15', '21:00', '23:30', 'Concerto della band romana nel tour mondiale. Opening act e special guests.', 65.00, 1, 1),
('Jovanotti - Il Disco del Sole Tour', '2026-07-12', '20:30', '23:00', 'Lorenzo torna live con il nuovo album. Show pirotecnico con ospiti a sorpresa.', 55.00, 4, NULL),
('Vasco Rossi - Vasco Live 2026', '2026-09-05', '20:00', '23:30', 'Il Blasco torna a San Siro per una notte indimenticabile.', 80.00, 1, NULL),
('Negramaro - Unplugged Tour', '2026-05-18', '21:00', '23:00', 'Versione acustica dei grandi successi della band salentina.', 45.00, 10, 3),
('Subsonica Live 2026', '2026-06-20', '21:30', '23:30', 'Elettronica e rock in un mix esplosivo dal vivo.', 40.00, 11, NULL),
('La Traviata - Arena di Verona', '2026-07-15', '21:15', '23:45', 'Il capolavoro di Verdi diretto dal M° Riccardo Muti. Regia moderna con proiezioni.', 120.00, 15, 2),
('Il Barbiere di Siviglia', '2026-08-02', '21:00', '23:15', 'Commedia lirica di Rossini alla Scala. Cast internazionale.', 95.00, 7, 2),
('Ludovico Einaudi - Elements Tour', '2026-05-25', '20:30', '22:30', 'Il pianista italiano presenta il suo ultimo lavoro in un concerto suggestivo.', 60.00, 14, 3),
('Orchestra Sinfonica - Beethoven Night', '2026-06-10', '20:00', '22:30', 'Sinfonie 5 e 9 di Beethoven. Direttore ospite internazionale.', 50.00, 8, NULL),
('Paolo Fresu Quintet', '2026-09-12', '21:00', '23:00', 'Jazz mediterraneo con il trombettista sardo e il suo quintetto.', 35.00, 14, 4),
('Stefano Bollani Solo Piano', '2026-09-14', '21:30', '23:00', 'Improvvisazioni jazz tra classico e contemporaneo.', 42.00, 7, 4),
('Sei Personaggi in Cerca d\'Autore', '2026-04-15', '20:30', '22:30', 'Capolavoro di Pirandello con regia contemporanea di Emma Dante.', 38.00, 9, 5),
('La Metamorfosi', '2026-05-08', '21:00', '22:45', 'Adattamento teatrale del romanzo di Kafka. Con Alessandro Gassman.', 45.00, 7, 5),
('Perfetti Sconosciuti - Il Teatro', '2026-06-03', '21:00', '23:00', 'Versione teatrale del celebre film. Con Paola Cortellesi.', 40.00, 9, 5),
('Maurizio Crozza Live', '2026-05-22', '21:00', '23:00', 'Satira e imitazioni del maestro Crozza. Diretta anche in streaming.', 35.00, 12, NULL),
('Luca e Paolo - Chiedimi se sono di turno', '2026-06-28', '21:30', '23:30', 'Il duo comico genovese con lo show del momento.', 32.00, 11, NULL),
('Inter vs Juventus - Serie A', '2026-04-18', '20:45', '22:45', 'Derby d\'Italia. Match valido per il campionato di Serie A.', 70.00, 1, NULL),
('Roma vs Lazio - Derby', '2026-05-10', '18:00', '20:00', 'Stracittadina romana. Atmosfera rovente all\'Olimpico.', 65.00, 2, NULL),
('Notte Bianca Milano', '2026-06-21', '20:00', '06:00', 'Musica, arte e cultura per tutta la notte in città. Ingresso gratuito con registrazione.', 0.00, 13, 3),
('Festival del Cinema all\'Aperto', '2026-07-20', '21:30', '23:30', 'Proiezioni di film italiani e internazionali sotto le stelle.', 8.00, 14, NULL),
('Il Re Leone - Musical', '2026-08-15', '15:00', '17:30', 'Il celebre musical Disney adatto a tutta la famiglia.', 55.00, 4, NULL),
('Cirque du Soleil - TOTEM', '2026-09-20', '19:00', '21:00', 'Spettacolo acrobatico del famoso circo canadese.', 85.00, 4, NULL);

-- ============================================================
-- EVENTO-INTRATTENITORE
-- ============================================================

INSERT INTO Evento_Intrattenitore (idEvento, idIntrattenitore) VALUES
(1, 1), (2, 2), (3, 3), (4, 5), (5, 6),
(6, 7), (6, 8), (7, 8), (8, 9), (9, 8),
(10, 10), (11, 11), (12, 12), (13, 13), (14, 14),
(15, 15), (16, 16);

-- ============================================================
-- CREATORI EVENTI (Promoter)
-- ============================================================

INSERT INTO CreatoriEventi (idEvento, idUtente) VALUES
(1, 3), (2, 3), (4, 3), (5, 3), (8, 3), (15, 3), (16, 3);

-- ============================================================
-- EVENTI-SETTORI
-- ============================================================

INSERT INTO EventiSettori (idEvento, idSettore) VALUES
(1, 1), (1, 2), (1, 3), (1, 4),
(2, 5), (2, 6), (2, 7),
(3, 1), (3, 2), (3, 3), (3, 4),
(4, 11), (4, 12),
(6, 13), (6, 14), (6, 15),
(7, 8), (7, 9), (7, 10),
(8, 13),
(17, 1), (17, 2), (17, 3), (17, 4);
