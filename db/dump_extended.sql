-- =====================================================
-- DUMP ESTESO – EventsMaster
-- Contiene molti piu eventi, manifestazioni, utenti
-- =====================================================

-- Pulizia tabelle
DELETE FROM Utente_Ordini;
DELETE FROM Ordine_Biglietti;
DELETE FROM Settore_Biglietti;
DELETE FROM Biglietti;
DELETE FROM Ordini;
DELETE FROM Recensioni;
DELETE FROM Esibizioni;
DELETE FROM Organizzatori_Evento;
DELETE FROM Eventi;
DELETE FROM Tempi;
DELETE FROM Intrattenitori;
DELETE FROM Organizzatori;
DELETE FROM Settori;
DELETE FROM Tipo;
DELETE FROM Locations;
DELETE FROM Utenti;
DELETE FROM Manifestazioni;

-- Reset auto increment se presente
ALTER TABLE Utenti AUTO_INCREMENT = 1;
ALTER TABLE Eventi AUTO_INCREMENT = 1;
ALTER TABLE Biglietti AUTO_INCREMENT = 1;
ALTER TABLE Ordini AUTO_INCREMENT = 1;

-- =====================================================
-- 1. MANIFESTAZIONI (20)
-- =====================================================
INSERT INTO Manifestazioni (id, Nome) VALUES
(1, 'Estate in Piazza'),
(2, 'Festival della Musica Italiana'),
(3, 'Notte Bianca Milano'),
(4, 'Roma Rock Festival'),
(5, 'Cinema sotto le Stelle'),
(6, 'Jazz & Wine Festival'),
(7, 'Teatro d\'Estate'),
(8, 'Serie A 2025/2026'),
(9, 'Champions League'),
(10, 'Coppa Italia'),
(11, 'Festival di Sanremo'),
(12, 'Lucca Comics & Games'),
(13, 'Milano Fashion Week'),
(14, 'Firenze Rocks'),
(15, 'Umbria Jazz'),
(16, 'Opera Festival Verona'),
(17, 'Ravenna Festival'),
(18, 'Taormina Arte'),
(19, 'Festival della Letteratura'),
(20, 'Concerto del Primo Maggio');

-- =====================================================
-- 2. LOCATIONS (30)
-- =====================================================
INSERT INTO Locations (id, Nome, Stato, Regione, CAP, Citta, civico) VALUES
(1, 'Piazza Duomo', 'Italia', 'Lombardia', 20100, 'Milano', '1'),
(2, 'Arena di Verona', 'Italia', 'Veneto', 37121, 'Verona', '28'),
(3, 'Teatro alla Scala', 'Italia', 'Lombardia', 20121, 'Milano', '2'),
(4, 'Stadio San Siro', 'Italia', 'Lombardia', 20151, 'Milano', '50'),
(5, 'Stadio Olimpico', 'Italia', 'Lazio', 00135, 'Roma', '1'),
(6, 'Piazza San Marco', 'Italia', 'Veneto', 30124, 'Venezia', NULL),
(7, 'Parco delle Cascine', 'Italia', 'Toscana', 50144, 'Firenze', NULL),
(8, 'Teatro Ariston', 'Italia', 'Liguria', 18038, 'Sanremo', '29'),
(9, 'Auditorium Parco della Musica', 'Italia', 'Lazio', 00196, 'Roma', '18'),
(10, 'Teatro La Fenice', 'Italia', 'Veneto', 30124, 'Venezia', '1965'),
(11, 'Arena Flegrea', 'Italia', 'Campania', 80125, 'Napoli', NULL),
(12, 'Stadio Diego Armando Maradona', 'Italia', 'Campania', 80125, 'Napoli', '1'),
(13, 'Teatro Antico', 'Italia', 'Sicilia', 98039, 'Taormina', '1'),
(14, 'Piazza Maggiore', 'Italia', 'Emilia-Romagna', 40124, 'Bologna', NULL),
(15, 'Palazzo dei Congressi', 'Italia', 'Lazio', 00144, 'Roma', '1'),
(16, 'Allianz Stadium', 'Italia', 'Piemonte', 10151, 'Torino', '80'),
(17, 'Unipol Arena', 'Italia', 'Emilia-Romagna', 40013, 'Bologna', '1'),
(18, 'Mediolanum Forum', 'Italia', 'Lombardia', 20091, 'Assago', '8'),
(19, 'Pala Alpitour', 'Italia', 'Piemonte', 10141, 'Torino', '1'),
(20, 'Teatro Greco', 'Italia', 'Sicilia', 96100, 'Siracusa', NULL),
(21, 'Piazza del Campo', 'Italia', 'Toscana', 53100, 'Siena', NULL),
(22, 'Circo Massimo', 'Italia', 'Lazio', 00186, 'Roma', NULL),
(23, 'Ippodromo San Siro', 'Italia', 'Lombardia', 20151, 'Milano', '31'),
(24, 'Stadio Artemio Franchi', 'Italia', 'Toscana', 50137, 'Firenze', '4'),
(25, 'Teatro Petruzzelli', 'Italia', 'Puglia', 70122, 'Bari', '72'),
(26, 'Marina di Ravenna', 'Italia', 'Emilia-Romagna', 48122, 'Ravenna', NULL),
(27, 'Piazza Plebiscito', 'Italia', 'Campania', 80132, 'Napoli', NULL),
(28, 'Fiera di Bologna', 'Italia', 'Emilia-Romagna', 40127, 'Bologna', '18'),
(29, 'Autodromo di Monza', 'Italia', 'Lombardia', 20900, 'Monza', NULL),
(30, 'Foro Italico', 'Italia', 'Lazio', 00135, 'Roma', NULL);

-- =====================================================
-- 3. TEMPI
-- =====================================================
INSERT INTO Tempi (OraI, OraF) VALUES
('09:00:00','11:00:00'),
('10:00:00','12:00:00'),
('11:00:00','13:00:00'),
('14:00:00','16:00:00'),
('15:00:00','17:00:00'),
('16:00:00','18:00:00'),
('17:00:00','19:00:00'),
('18:00:00','20:00:00'),
('18:30:00','20:30:00'),
('19:00:00','21:00:00'),
('19:30:00','21:30:00'),
('20:00:00','22:00:00'),
('20:30:00','22:30:00'),
('21:00:00','23:00:00'),
('21:30:00','23:30:00'),
('22:00:00','00:00:00'),
('22:30:00','00:30:00'),
('23:00:00','01:00:00');

-- =====================================================
-- 4. INTRATTENITORI (50)
-- =====================================================
INSERT INTO Intrattenitori (id, Nome, Mestiere) VALUES
(1,'Maneskin','Rock Band'),
(2,'Blanco','Cantante'),
(3,'Mahmood','Cantante'),
(4,'Elodie','Cantante'),
(5,'Ultimo','Cantante'),
(6,'Cesare Cremonini','Cantante'),
(7,'Ligabue','Cantante Rock'),
(8,'Vasco Rossi','Cantante Rock'),
(9,'Jovanotti','Cantante'),
(10,'Laura Pausini','Cantante'),
(11,'Eros Ramazzotti','Cantante'),
(12,'Tiziano Ferro','Cantante'),
(13,'Marco Mengoni','Cantante'),
(14,'Annalisa','Cantante'),
(15,'Gianni Morandi','Cantante'),
(16,'Emma Marrone','Cantante'),
(17,'Achille Lauro','Cantante'),
(18,'Sangiovanni','Cantante'),
(19,'Mr. Rain','Cantante'),
(20,'Pinguini Tattici Nucleari','Band'),
(21,'Negramaro','Band'),
(22,'Modà','Band'),
(23,'The Kolors','Band'),
(24,'Orchestra Sinfonica di Milano','Orchestra'),
(25,'Orchestra dell\'Arena di Verona','Orchestra'),
(26,'Riccardo Muti','Direttore d\'Orchestra'),
(27,'Roberto Bolle','Ballerino'),
(28,'Compagnia Aterballetto','Danza'),
(29,'Ficarra e Picone','Comici'),
(30,'Checco Zalone','Comico'),
(31,'Luca e Paolo','Comici'),
(32,'Virginia Raffaele','Comica'),
(33,'Maurizio Battista','Comico'),
(34,'Biagio Antonacci','Cantante'),
(35,'Nek','Cantante'),
(36,'Max Pezzali','Cantante'),
(37,'DJ Albertino','DJ'),
(38,'Bob Sinclar','DJ'),
(39,'David Guetta','DJ'),
(40,'Martin Garrix','DJ'),
(41,'Shakespeare Company','Teatro'),
(42,'Teatro Stabile di Torino','Teatro'),
(43,'Compagnia della Rancia','Musical'),
(44,'Arturo Brachetti','Illusionista'),
(45,'Cirque du Soleil','Circo'),
(46,'Blue Man Group','Performance'),
(47,'Stomp','Performance'),
(48,'Il Volo','Trio'),
(49,'Andrea Bocelli','Tenore'),
(50,'Giovanni Allevi','Pianista');

-- =====================================================
-- 5. UTENTI (50) - con password e ruoli
-- =====================================================
INSERT INTO Utenti (id, Nome, Cognome, Email, Password, ruolo, email_verified) VALUES
(1,'Admin','Sistema','admin@eventsmaster.it','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin', 1),
(2,'Moderatore','Uno','mod1@eventsmaster.it','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','mod', 1),
(3,'Promoter','Eventi','promoter@eventsmaster.it','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','promoter', 1),
(4,'Mario','Rossi','mario.rossi@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(5,'Luisa','Bianchi','luisa.bianchi@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(6,'Paolo','Verdi','paolo.verdi@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(7,'Anna','Gialli','anna.gialli@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(8,'Giovanni','Neri','giovanni.neri@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(9,'Chiara','Blu','chiara.blu@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(10,'Francesco','Rosa','francesco.rosa@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(11,'Laura','Viola','laura.viola@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(12,'Stefano','Arancio','stefano.arancio@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(13,'Martina','Celeste','martina.celeste@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(14,'Luca','Ferrari','luca.ferrari@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(15,'Giulia','Colombo','giulia.colombo@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(16,'Marco','Fontana','marco.fontana@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(17,'Elena','Rizzo','elena.rizzo@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(18,'Andrea','Gallo','andrea.gallo@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(19,'Sara','Conti','sara.conti@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(20,'Davide','Leone','davide.leone@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(21,'Alessia','Costa','alessia.costa@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(22,'Roberto','Greco','roberto.greco@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(23,'Valentina','Mancini','valentina.mancini@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(24,'Simone','Barbieri','simone.barbieri@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(25,'Federica','Pellegrini','federica.p@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(26,'Alessandro','Rinaldi','alessandro.r@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(27,'Silvia','Caruso','silvia.caruso@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(28,'Matteo','De Luca','matteo.deluca@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(29,'Cristina','Santoro','cristina.s@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(30,'Fabio','Marini','fabio.marini@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(31,'Elisa','Grasso','elisa.grasso@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(32,'Nicola','Valentini','nicola.v@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(33,'Monica','Fabbri','monica.fabbri@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(34,'Riccardo','Monti','riccardo.m@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(35,'Barbara','Cattaneo','barbara.c@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(36,'Promoter','Milano','promoter.mi@eventsmaster.it','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','promoter', 1),
(37,'Promoter','Roma','promoter.rm@eventsmaster.it','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','promoter', 1),
(38,'Moderatore','Due','mod2@eventsmaster.it','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','mod', 1),
(39,'Test','User','test@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1),
(40,'Demo','Account','demo@eventsmaster.it','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user', 1);
-- Password per tutti: "password"

-- =====================================================
-- 6. ORGANIZZATORI (15)
-- =====================================================
INSERT INTO Organizzatori (id, Nome, Cognome, Ruolo) VALUES
(1,'Giulia','Neri','Direttore Artistico'),
(2,'Marco','Bianchi','Event Manager'),
(3,'Luca','Verdi','Responsabile Logistica'),
(4,'Anna','Rossi','Marketing Manager'),
(5,'Paolo','Blu','Responsabile Sponsor'),
(6,'Sofia','Marino','PR Manager'),
(7,'Alessandro','Romano','Direttore Tecnico'),
(8,'Chiara','Galli','Responsabile Sicurezza'),
(9,'Matteo','Ricci','Stage Manager'),
(10,'Francesca','Moretti','Coordinatrice Artisti'),
(11,'Giuseppe','Barbieri','Responsabile Audio'),
(12,'Elena','Santini','Responsabile Luci'),
(13,'Antonio','Ferrara','Catering Manager'),
(14,'Lucia','Colombo','Ticketing Manager'),
(15,'Roberto','Costa','Direttore Generale');

-- =====================================================
-- 7. TIPO (classi biglietto)
-- =====================================================
INSERT INTO Tipo (nome, ModificatorePrezzo) VALUES
('Standard', 0.00),
('VIP', 50.00),
('Premium', 100.00),
('Gold', 150.00),
('Platinum', 250.00),
('Meet & Greet', 300.00);

-- =====================================================
-- 8. SETTORI (per ogni location)
-- =====================================================
INSERT INTO Settori (id, idLocation, Posti, MoltiplicatorePrezzo) VALUES
-- Piazza Duomo Milano
(1, 1, 5000, 1.00),
(2, 1, 1000, 1.50),
-- Arena di Verona
(3, 2, 12000, 1.00),
(4, 2, 3000, 1.30),
(5, 2, 500, 2.00),
-- Teatro alla Scala
(6, 3, 1500, 1.20),
(7, 3, 500, 2.00),
(8, 3, 100, 3.00),
-- Stadio San Siro
(9, 4, 50000, 1.00),
(10, 4, 15000, 1.30),
(11, 4, 5000, 1.80),
-- Stadio Olimpico Roma
(12, 5, 60000, 1.00),
(13, 5, 20000, 1.30),
(14, 5, 8000, 1.80),
-- Mediolanum Forum
(15, 18, 12000, 1.00),
(16, 18, 4000, 1.40),
(17, 18, 1000, 2.00),
-- Pala Alpitour
(18, 19, 15000, 1.00),
(19, 19, 5000, 1.40),
-- Teatro Ariston
(20, 8, 1500, 1.50),
(21, 8, 300, 2.50);

-- =====================================================
-- 9. EVENTI (80+ eventi)
-- =====================================================
INSERT INTO Eventi (id, idManifestazione, idLocation, Nome, PrezzoNoMod, Data, OraI, OraF, Programma, Categoria) VALUES
-- CONCERTI (categoria: concerti)
(1, 2, 4, 'Maneskin - Rush! World Tour', 55.00, '2026-02-15', '21:00:00', '23:30:00', 'Il tour mondiale dei Maneskin fa tappa a Milano', 'concerti'),
(2, 2, 5, 'Maneskin - Rush! World Tour Roma', 55.00, '2026-02-18', '21:00:00', '23:30:00', 'Il tour mondiale dei Maneskin fa tappa a Roma', 'concerti'),
(3, 14, 7, 'Ultimo - Stadi 2026', 45.00, '2026-06-20', '21:00:00', '23:30:00', 'Ultimo torna negli stadi con il nuovo album', 'concerti'),
(4, 14, 4, 'Ultimo - Stadi 2026 Milano', 45.00, '2026-06-25', '21:00:00', '23:30:00', 'Ultimo a San Siro', 'concerti'),
(5, 4, 22, 'Vasco Rossi - Non Stop Live', 60.00, '2026-06-11', '21:00:00', '00:00:00', 'Vasco non si ferma mai', 'concerti'),
(6, 4, 22, 'Vasco Rossi - Non Stop Live 2', 60.00, '2026-06-12', '21:00:00', '00:00:00', 'Seconda data romana', 'concerti'),
(7, 2, 18, 'Cesare Cremonini - Live 2026', 50.00, '2026-03-10', '21:00:00', '23:00:00', 'Cremonini in concerto', 'concerti'),
(8, 2, 19, 'Cesare Cremonini - Live Torino', 50.00, '2026-03-15', '21:00:00', '23:00:00', 'Cremonini a Torino', 'concerti'),
(9, 2, 18, 'Ligabue - La Notte di Certe Notti', 48.00, '2026-04-20', '21:00:00', '23:30:00', 'Liga celebra 30 anni di carriera', 'concerti'),
(10, 14, 7, 'Pinguini Tattici Nucleari - Summer Tour', 38.00, '2026-07-05', '21:00:00', '23:00:00', 'I Pinguini in estate', 'concerti'),
(11, 2, 11, 'Elodie - This Is Elodie', 42.00, '2026-05-10', '21:00:00', '23:00:00', 'Elodie live a Napoli', 'concerti'),
(12, 2, 17, 'Annalisa - Sinceramente Tour', 40.00, '2026-04-05', '21:00:00', '23:00:00', 'Annalisa dopo Sanremo', 'concerti'),
(13, 2, 18, 'Marco Mengoni - Materia Tour', 45.00, '2026-03-20', '21:00:00', '23:00:00', 'Mengoni live', 'concerti'),
(14, 15, 9, 'Umbria Jazz - Opening Night', 35.00, '2026-07-12', '20:30:00', '23:00:00', 'Serata inaugurale con artisti internazionali', 'concerti'),
(15, 15, 9, 'Umbria Jazz - Piano Solo', 40.00, '2026-07-14', '21:00:00', '23:00:00', 'Giovanni Allevi in solo', 'concerti'),
(16, 2, 2, 'Andrea Bocelli - Arena di Verona', 80.00, '2026-08-01', '21:00:00', '23:30:00', 'Il tenore piu amato nel mondo', 'concerti'),
(17, 2, 2, 'Il Volo - Arena di Verona', 55.00, '2026-08-05', '21:00:00', '23:00:00', 'Il trio lirico-pop', 'concerti'),
(18, 6, 26, 'Jazz & Wine Night', 25.00, '2026-07-20', '19:00:00', '23:00:00', 'Jazz e degustazione vini', 'concerti'),
(19, 2, 1, 'Radio Italia Live', 0.00, '2026-05-28', '20:00:00', '00:00:00', 'Concerto gratuito in Piazza Duomo', 'concerti'),
(20, 20, 22, 'Concerto del Primo Maggio', 0.00, '2026-05-01', '15:00:00', '00:00:00', 'Concerto gratuito a Roma', 'concerti'),

-- TEATRO E OPERA (categoria: teatro)
(21, 16, 2, 'Aida - Opera Festival', 90.00, '2026-07-25', '21:00:00', '00:00:00', 'Opera verdiana allArena', 'teatro'),
(22, 16, 2, 'Carmen - Opera Festival', 85.00, '2026-07-28', '21:00:00', '23:30:00', 'Carmen di Bizet', 'teatro'),
(23, 16, 2, 'La Traviata - Opera Festival', 90.00, '2026-08-02', '21:00:00', '23:30:00', 'Capolavoro di Verdi', 'teatro'),
(24, 7, 3, 'Il Lago dei Cigni - Scala', 120.00, '2026-02-14', '20:00:00', '23:00:00', 'Balletto classico con Roberto Bolle', 'teatro'),
(25, 7, 3, 'Don Giovanni - Mozart', 100.00, '2026-03-01', '19:30:00', '22:30:00', 'Opera mozartiana', 'teatro'),
(26, 7, 10, 'La Boheme - La Fenice', 95.00, '2026-02-20', '20:00:00', '22:30:00', 'Puccini a Venezia', 'teatro'),
(27, 17, 26, 'Ravenna Festival Opening', 50.00, '2026-06-01', '21:00:00', '23:00:00', 'Serata inaugurale', 'teatro'),
(28, 18, 13, 'Taormina Film Fest', 30.00, '2026-06-15', '21:00:00', '00:00:00', 'Cinema al Teatro Antico', 'teatro'),
(29, 7, 25, 'Rigoletto - Petruzzelli', 75.00, '2026-04-10', '20:30:00', '23:00:00', 'Opera al Sud', 'teatro'),
(30, 7, 3, 'Tosca - Scala', 110.00, '2026-05-05', '20:00:00', '23:00:00', 'Puccini alla Scala', 'teatro'),
(31, 7, 20, 'Antigone - Teatro Greco', 35.00, '2026-05-20', '21:00:00', '23:00:00', 'Tragedia greca', 'teatro'),
(32, 7, 42, 'Notre Dame de Paris', 55.00, '2026-03-25', '21:00:00', '23:30:00', 'Il musical di Cocciante', 'teatro'),
(33, 7, 18, 'Mamma Mia! Musical', 50.00, '2026-04-15', '21:00:00', '23:30:00', 'Il musical degli ABBA', 'teatro'),
(34, 7, 3, 'Romeo e Giulietta - Balletto', 90.00, '2026-06-10', '20:30:00', '23:00:00', 'Balletto romantico', 'teatro'),

-- SPORT - CALCIO (categoria: sport)
(35, 8, 4, 'Milan vs Inter - Derby', 80.00, '2026-02-22', '20:45:00', '22:45:00', 'Derby della Madonnina', 'sport'),
(36, 8, 4, 'Milan vs Juventus', 70.00, '2026-03-08', '20:45:00', '22:45:00', 'Big match Serie A', 'sport'),
(37, 8, 5, 'Roma vs Lazio - Derby', 75.00, '2026-02-01', '18:00:00', '20:00:00', 'Derby della Capitale', 'sport'),
(38, 8, 5, 'Roma vs Napoli', 60.00, '2026-03-15', '20:45:00', '22:45:00', 'Serie A', 'sport'),
(39, 8, 16, 'Juventus vs Milan', 70.00, '2026-04-05', '20:45:00', '22:45:00', 'Scontro diretto', 'sport'),
(40, 8, 12, 'Napoli vs Inter', 65.00, '2026-04-12', '20:45:00', '22:45:00', 'Serie A', 'sport'),
(41, 9, 4, 'Milan vs Real Madrid - UCL', 120.00, '2026-03-12', '21:00:00', '23:00:00', 'Champions League', 'sport'),
(42, 9, 5, 'Roma vs Liverpool - UCL', 110.00, '2026-03-19', '21:00:00', '23:00:00', 'Champions League', 'sport'),
(43, 10, 5, 'Finale Coppa Italia', 90.00, '2026-05-15', '21:00:00', '23:00:00', 'Finale allo Stadio Olimpico', 'sport'),
(44, 8, 24, 'Fiorentina vs Milan', 55.00, '2026-02-28', '15:00:00', '17:00:00', 'Serie A', 'sport'),

-- ALTRI EVENTI (categoria: eventi o altro)
(45, 12, 28, 'Lucca Comics - Day 1', 25.00, '2026-10-29', '09:00:00', '20:00:00', 'Primo giorno del festival', 'eventi'),
(46, 12, 28, 'Lucca Comics - Day 2', 25.00, '2026-10-30', '09:00:00', '20:00:00', 'Secondo giorno', 'eventi'),
(47, 12, 28, 'Lucca Comics - Day 3', 25.00, '2026-10-31', '09:00:00', '20:00:00', 'Halloween a Lucca', 'eventi'),
(48, 12, 28, 'Lucca Comics - Day 4', 25.00, '2026-11-01', '09:00:00', '20:00:00', 'Ultimo giorno', 'eventi'),
(49, 13, 1, 'Milano Fashion Week Opening', 150.00, '2026-09-20', '19:00:00', '22:00:00', 'Serata inaugurale', 'eventi'),
(50, 13, 1, 'Armani Show', 200.00, '2026-09-22', '20:00:00', '22:00:00', 'Sfilata Armani', 'eventi'),
(51, 19, 14, 'Festival Letteratura', 15.00, '2026-09-05', '10:00:00', '18:00:00', 'Incontri con autori', 'eventi'),
(52, 5, 22, 'Cinema all\'aperto - Interstellar', 8.00, '2026-07-10', '21:30:00', '00:00:00', 'Film sotto le stelle', 'eventi'),
(53, 5, 7, 'Cinema all\'aperto - Inception', 8.00, '2026-07-15', '21:30:00', '00:00:00', 'Nolan a Firenze', 'eventi'),
(54, 3, 1, 'Notte Bianca Milano 2026', 0.00, '2026-06-21', '18:00:00', '06:00:00', 'La citta che non dorme', 'eventi'),
(55, 1, 1, 'Estate in Piazza Opening', 0.00, '2026-06-15', '18:00:00', '00:00:00', 'Inaugurazione estate milanese', 'eventi'),

-- CONCERTI IMMINENTI (Gennaio-Febbraio 2026)
(56, 11, 8, 'Sanremo 2026 - Serata Finale', 250.00, '2026-02-08', '20:30:00', '01:00:00', 'Finale del Festival', 'concerti'),
(57, 11, 8, 'Sanremo 2026 - Serata 4', 180.00, '2026-02-07', '20:30:00', '00:30:00', 'Quarta serata cover', 'concerti'),
(58, 11, 8, 'Sanremo 2026 - Serata 3', 180.00, '2026-02-06', '20:30:00', '00:30:00', 'Terza serata', 'concerti'),
(59, 11, 8, 'Sanremo 2026 - Serata 2', 180.00, '2026-02-05', '20:30:00', '00:30:00', 'Seconda serata', 'concerti'),
(60, 11, 8, 'Sanremo 2026 - Serata 1', 180.00, '2026-02-04', '20:30:00', '00:30:00', 'Prima serata', 'concerti'),

-- EVENTI PASSATI (per recensioni)
(61, 2, 18, 'Ultimo - Forum 2025', 45.00, '2025-12-20', '21:00:00', '23:00:00', 'Concerto natalizio', 'concerti'),
(62, 2, 17, 'Maneskin - Bologna 2025', 50.00, '2025-12-15', '21:00:00', '23:30:00', 'Ultimo concerto 2025', 'concerti'),
(63, 7, 3, 'Nutcracker - Scala 2025', 100.00, '2025-12-26', '20:00:00', '22:30:00', 'Lo Schiaccianoci', 'teatro'),

-- COMICI E SPETTACOLI
(64, 1, 18, 'Checco Zalone Live', 40.00, '2026-04-01', '21:00:00', '23:00:00', 'Checco torna sul palco', 'teatro'),
(65, 1, 9, 'Ficarra e Picone Show', 35.00, '2026-03-28', '21:00:00', '23:00:00', 'Il duo comico siciliano', 'teatro'),
(66, 1, 17, 'Virginia Raffaele - One Woman Show', 38.00, '2026-05-08', '21:00:00', '23:00:00', 'Le sue imitazioni', 'teatro'),

-- DJ SET E CLUBBING
(67, 3, 23, 'David Guetta @ Milano', 45.00, '2026-07-18', '22:00:00', '04:00:00', 'Il dj francese a Milano', 'concerti'),
(68, 3, 23, 'Martin Garrix @ Milano', 42.00, '2026-07-25', '22:00:00', '04:00:00', 'EDM night', 'concerti'),
(69, 1, 11, 'Bob Sinclar @ Napoli', 35.00, '2026-08-10', '22:00:00', '04:00:00', 'Summer party', 'concerti'),

-- ALTRI SPORT
(70, 8, 29, 'Formula 1 - GP Monza', 250.00, '2026-09-06', '14:00:00', '17:00:00', 'Gran Premio dItalia', 'sport'),
(71, 8, 30, 'Internazionali Tennis Roma', 60.00, '2026-05-10', '11:00:00', '21:00:00', 'ATP Masters 1000', 'sport'),
(72, 8, 30, 'Internazionali Tennis - Finale', 150.00, '2026-05-17', '16:00:00', '20:00:00', 'Finale maschile', 'sport'),

-- CONCERTI ESTIVI EXTRA
(73, 14, 7, 'Negramaro - Contatto Tour', 42.00, '2026-07-08', '21:00:00', '23:30:00', 'Negramaro live', 'concerti'),
(74, 14, 7, 'Max Pezzali - Max Forever', 45.00, '2026-07-12', '21:00:00', '23:00:00', 'Gli 883 per sempre', 'concerti'),
(75, 2, 2, 'Eros Ramazzotti - Arena Tour', 65.00, '2026-08-15', '21:00:00', '23:30:00', 'Eros all\'Arena', 'concerti'),
(76, 2, 2, 'Laura Pausini - World Tour', 70.00, '2026-08-20', '21:00:00', '23:30:00', 'La Pausini torna in Italia', 'concerti'),
(77, 2, 18, 'Tiziano Ferro - TZN Tour', 55.00, '2026-06-05', '21:00:00', '23:30:00', 'Tiziano live', 'concerti'),

-- TEATRO EXTRA
(78, 7, 3, 'Cirque du Soleil - Corteo', 85.00, '2026-04-25', '20:30:00', '23:00:00', 'Lo spettacolo itinerante', 'teatro'),
(79, 7, 18, 'Blue Man Group', 55.00, '2026-05-15', '21:00:00', '23:00:00', 'Performance unica', 'teatro'),
(80, 7, 9, 'Stomp - Out Loud', 45.00, '2026-05-22', '21:00:00', '23:00:00', 'Ritmo e percussioni', 'teatro');

-- =====================================================
-- 10. ESIBIZIONI
-- =====================================================
INSERT INTO Esibizioni (idEvento, idIntrattenitore, OraI, OraF) VALUES
(1, 1, '21:00:00', '23:00:00'),
(2, 1, '21:00:00', '23:00:00'),
(3, 5, '21:00:00', '23:00:00'),
(4, 5, '21:00:00', '23:00:00'),
(5, 8, '21:00:00', '23:00:00'),
(6, 8, '21:00:00', '23:00:00'),
(7, 6, '21:00:00', '23:00:00'),
(8, 6, '21:00:00', '23:00:00'),
(9, 7, '21:00:00', '23:00:00'),
(10, 20, '21:00:00', '23:00:00'),
(11, 4, '21:00:00', '23:00:00'),
(12, 14, '21:00:00', '23:00:00'),
(13, 13, '21:00:00', '23:00:00'),
(15, 50, '21:00:00', '23:00:00'),
(16, 49, '21:00:00', '23:00:00'),
(17, 48, '21:00:00', '23:00:00'),
(24, 27, '20:00:00', '22:00:00'),
(64, 30, '21:00:00', '23:00:00'),
(65, 29, '21:00:00', '23:00:00'),
(66, 32, '21:00:00', '23:00:00'),
(67, 39, '22:00:00', '00:00:00'),
(68, 40, '22:00:00', '00:00:00'),
(69, 38, '22:00:00', '00:00:00'),
(73, 21, '21:00:00', '23:00:00'),
(74, 36, '21:00:00', '23:00:00'),
(75, 11, '21:00:00', '23:00:00'),
(76, 10, '21:00:00', '23:00:00'),
(77, 12, '21:00:00', '23:00:00'),
(78, 45, '20:30:00', '22:30:00'),
(79, 46, '21:00:00', '23:00:00'),
(80, 47, '21:00:00', '23:00:00');

-- =====================================================
-- 11. RECENSIONI (per eventi passati)
-- =====================================================
INSERT INTO Recensioni (idEvento, idUtente, Voto, Messaggio) VALUES
(61, 4, 5, 'Concerto incredibile! Ultimo sempre al top!'),
(61, 5, 5, 'Atmosfera magica, voce pazzesca'),
(61, 6, 4, 'Bello, ma troppa gente'),
(61, 7, 5, 'Il miglior concerto della mia vita'),
(62, 8, 5, 'I Maneskin spaccano sempre!'),
(62, 9, 5, 'Energia pura sul palco'),
(62, 10, 4, 'Ottimo concerto, audio un po alto'),
(63, 11, 5, 'Spettacolo meraviglioso'),
(63, 12, 5, 'Roberto Bolle e sublime'),
(63, 13, 4, 'Bellissimo ma posto un po scomodo');

-- =====================================================
-- 12. ORGANIZZATORI_EVENTO
-- =====================================================
INSERT INTO Organizzatori_Evento (idEvento, idOrganizzatore) VALUES
(1, 1), (1, 2), (1, 7),
(2, 1), (2, 2),
(3, 1), (3, 4),
(4, 1), (4, 4),
(5, 15), (5, 7), (5, 8),
(6, 15), (6, 7),
(21, 1), (21, 10),
(22, 1), (22, 10),
(35, 3), (35, 8),
(56, 1), (56, 4), (56, 6);

-- =====================================================
-- 13. BIGLIETTI DI ESEMPIO
-- =====================================================
INSERT INTO Biglietti (id, idEvento, idClasse, Nome, Cognome, Sesso, QRcode, `Check`) VALUES
(1, 61, 'Standard', 'Mario', 'Rossi', 'M', X'A1B2C3D4E5F60001', 1),
(2, 61, 'VIP', 'Luisa', 'Bianchi', 'F', X'A1B2C3D4E5F60002', 1),
(3, 62, 'Standard', 'Paolo', 'Verdi', 'M', X'A1B2C3D4E5F60003', 1),
(4, 62, 'Premium', 'Anna', 'Gialli', 'F', X'A1B2C3D4E5F60004', 1),
(5, 63, 'Gold', 'Giovanni', 'Neri', 'M', X'A1B2C3D4E5F60005', 1),
(6, 1, 'Standard', 'Mario', 'Rossi', 'M', X'A1B2C3D4E5F60006', 0),
(7, 1, 'VIP', 'Luisa', 'Bianchi', 'F', X'A1B2C3D4E5F60007', 0),
(8, 3, 'Premium', 'Paolo', 'Verdi', 'M', X'A1B2C3D4E5F60008', 0),
(9, 35, 'Standard', 'Anna', 'Gialli', 'F', X'A1B2C3D4E5F60009', 0),
(10, 56, 'Platinum', 'Chiara', 'Blu', 'F', X'A1B2C3D4E5F60010', 0);

-- =====================================================
-- 14. SETTORE_BIGLIETTI
-- =====================================================
INSERT INTO Settore_Biglietti (idSettore, idBiglietto, Fila, Numero) VALUES
(15, 1, 'A', 1),
(17, 2, 'VIP', 1),
(15, 3, 'B', 15),
(16, 4, 'C', 20),
(8, 5, 'PALCO', 5),
(9, 6, 'Z', 100),
(11, 7, 'VIP', 10),
(9, 8, 'A', 50),
(9, 9, 'CURVA', 500),
(20, 10, 'PLATEA', 1);

-- =====================================================
-- 15. ORDINI
-- =====================================================
INSERT INTO Ordini (id, Metodo) VALUES
(1, 'Carta'),
(2, 'PayPal'),
(3, 'Carta'),
(4, 'Bonifico'),
(5, 'PayPal');

-- =====================================================
-- 16. ORDINE_BIGLIETTI
-- =====================================================
INSERT INTO Ordine_Biglietti (idOrdine, idBiglietto) VALUES
(1, 1), (1, 2),
(2, 3), (2, 4),
(3, 5),
(4, 6), (4, 7),
(5, 8), (5, 9), (5, 10);

-- =====================================================
-- 17. UTENTE_ORDINI
-- =====================================================
INSERT INTO Utente_Ordini (idUtente, idOrdine) VALUES
(4, 1),
(5, 1),
(6, 2),
(7, 2),
(8, 3),
(4, 4),
(5, 4),
(6, 5),
(9, 5);

-- =====================================================
-- FINE DUMP ESTESO
-- =====================================================
