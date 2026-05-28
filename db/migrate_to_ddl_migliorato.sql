-- ============================================================
-- Migrazione dati al DDL migliorato
-- Eseguire DOPO aver applicato ddl_migliorato.sql
-- ============================================================

USE `5cit_eventsmaster`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- tipo
-- ----------------------------
-- ModificatorePrezzo >= 0 per constraint; Ridotto/Studenti = 0 (nessun supplemento)
INSERT INTO `tipo` (`id`, `nome`, `ModificatorePrezzo`) VALUES
(1, 'Standard',  0.00),
(2, 'VIP',      50.00),
(3, 'Premium', 100.00),
(4, 'Ridotto',   0.00),
(5, 'Studenti',  0.00);

-- ----------------------------
-- utenti (password = "password" per tutti)
-- ----------------------------
INSERT INTO `utenti` (`id`, `Nome`, `Cognome`, `Email`, `Password`, `ruolo`, `verificato`, `verificato_at`, `DataRegistrazione`) VALUES
(1, 'Admin',      'Sistema',  'admin@eventsmaster.it',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',    1, NOW(), '2026-01-23 11:10:44'),
(2, 'Moderatore', 'Staff',    'mod@eventsmaster.it',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mod',      1, NOW(), '2026-01-23 11:10:44'),
(3, 'Promoter',   'Eventi',   'promoter@eventsmaster.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'promoter', 1, NOW(), '2026-01-23 11:10:44'),
(4, 'Mattia',     'Bosco',    'user@eventsmaster.it',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',     1, NOW(), '2026-01-23 11:10:44');

-- ----------------------------
-- locations (idCreatore = 3 = promoter)
-- ----------------------------
INSERT INTO `locations` (`id`, `Nome`, `Indirizzo`, `Citta`, `CAP`, `Regione`, `Capienza`, `idCreatore`) VALUES
(1,  'Stadio San Siro',                'Piazzale Angelo Moratti',           'Milano',  '20151', 'Lombardia', 80000, 3),
(2,  'Stadio Olimpico',                'Viale dei Gladiatori',              'Roma',    '00135', 'Lazio',     70000, 3),
(3,  'Allianz Stadium',                'Corso Gaetano Scirea 50',           'Torino',  '10151', 'Piemonte',  41500, 3),
(4,  'Mediolanum Forum',               'Via G. di Vittorio 6',              'Assago',  '20090', 'Lombardia', 12700, 3),
(5,  'PalaAlpitour',                   'Via Filadelfia 88',                 'Torino',  '10134', 'Piemonte',  12300, 3),
(6,  'Palazzo dello Sport',            'Piazzale Pier Luigi Nervi',         'Roma',    '00144', 'Lazio',     11000, 3),
(7,  'Teatro alla Scala',              'Via Filodrammatici 2',              'Milano',  '20121', 'Lombardia',  2030, 3),
(8,  'Teatro dell''Opera',             'Piazza Beniamino Gigli 1',          'Roma',    '00184', 'Lazio',      1600, 3),
(9,  'Teatro Regio',                   'Piazza Castello 215',               'Torino',  '10124', 'Piemonte',   1500, 3),
(10, 'Alcatraz',                       'Via Valtellina 25',                 'Milano',  '20159', 'Lombardia',  3000, 3),
(11, 'Fabrique',                       'Via Fantoli 9',                     'Milano',  '20138', 'Lombardia',  1500, 3),
(12, 'Atlantico Live',                 'Viale dell''Oceano Atlantico 271',  'Roma',    '00144', 'Lazio',      2000, 3),
(13, 'Ippodromo San Siro',             'Piazzale dello Sport 16',           'Milano',  '20151', 'Lombardia', 15000, 3),
(14, 'Auditorium Parco della Musica',  'Viale Pietro de Coubertin 30',      'Roma',    '00196', 'Lazio',     21000, 3),
(15, 'Arena di Verona',                'Piazza Bra 1',                      'Verona',  '37121', 'Veneto',    15000, 3);

-- ----------------------------
-- manifestazioni (idCreatore = 3 = promoter)
-- ----------------------------
INSERT INTO `manifestazioni` (`id`, `Nome`, `Descrizione`, `DataInizio`, `DataFine`, `idCreatore`) VALUES
(1, 'Rock in Italy Festival 2026', 'Festival internazionale di musica rock', '2026-06-15', '2026-06-17', 3),
(2, 'Opera Estate 2026',           'Stagione estiva operistica',             '2026-07-01', '2026-08-31', 3),
(3, 'Milano Music Week',           'Settimana della musica a Milano',        '2026-05-20', '2026-05-27', 3),
(4, 'Jazz & Wine Festival',        'Festival jazz con degustazioni',         '2026-09-10', '2026-09-15', 3),
(5, 'Teatro Contemporaneo',        'Rassegna teatro contemporaneo',          '2026-04-01', '2026-05-30', 3);

-- ----------------------------
-- settori (NumFile/PostiPerFila ricavati da PostiTotali approssimativamente)
-- ----------------------------
INSERT INTO `settori` (`id`, `Nome`, `NumFile`, `PostiPerFila`, `idLocation`, `MoltiplicatorePrezzo`, `PostiTotali`) VALUES
(1,  'Tribuna Arancio', 80, 100, 1,  1.50,  8000),
(2,  'Curva Sud',      120, 100, 1,  1.00, 12000),
(3,  'Curva Nord',     120, 100, 1,  1.00, 12000),
(4,  'Tribuna Rossa',   50, 100, 1,  2.00,  5000),
(5,  'Parterre',        40,  50, 4,  2.00,  2000),
(6,  'Tribuna',         50, 100, 4,  1.50,  5000),
(7,  'Gradinata',       57, 100, 4,  1.00,  5700),
(8,  'Platea',          20,  30, 7,  3.00,   600),
(9,  'Palchi',          20,  40, 7,  2.50,   800),
(10, 'Galleria',        21,  30, 7,  1.50,   630),
(11, 'Pista',          100,  20, 10, 1.00,  2000),
(12, 'Balconata',       50,  20, 10, 1.30,  1000),
(13, 'Gradinata',      100,  80, 15, 1.00,  8000),
(14, 'Poltronissime',   50,  60, 15, 2.50,  3000),
(15, 'Tribuna',         50,  80, 15, 1.50,  4000);

-- ----------------------------
-- intrattenitori (rinominato da intrattenitore)
-- ----------------------------
INSERT INTO `intrattenitori` (`id`, `Nome`, `Categoria`) VALUES
(1,  'Måneskin',                       'Band'),
(2,  'Jovanotti',                      'Cantante'),
(3,  'Vasco Rossi',                    'Cantante'),
(4,  'Ligabue',                        'Cantante'),
(5,  'Negramaro',                      'Band'),
(6,  'Subsonica',                      'Band'),
(7,  'Riccardo Muti',                  'Direttore d''Orchestra'),
(8,  'Orchestra Sinfonica Nazionale',  'Orchestra'),
(9,  'Ludovico Einaudi',               'Pianista'),
(10, 'Paolo Fresu',                    'Trombettista'),
(11, 'Stefano Bollani',                'Pianista'),
(12, 'Compagnia Teatro Franco Parenti','Compagnia Teatrale'),
(13, 'Alessandro Gassman',             'Attore'),
(14, 'Paola Cortellesi',               'Attrice'),
(15, 'Maurizio Crozza',                'Comico'),
(16, 'Luca Bizzarri & Paolo Kessisoglu','Duo Comico');

-- ----------------------------
-- eventi (idCreatore = 3 = promoter)
-- ----------------------------
INSERT INTO `eventi` (`id`, `Nome`, `Data`, `OraI`, `OraF`, `PrezzoNoMod`, `idLocation`, `idManifestazione`, `Categoria`, `idCreatore`) VALUES
(1,  'Måneskin - Rush! World Tour',             '2026-06-15', '21:00:00', '23:30:00',  65.00, 1,  1,    'concerti',  3),
(2,  'Jovanotti - Il Disco del Sole Tour',      '2026-07-12', '20:30:00', '23:00:00',  55.00, 4,  NULL, 'concerti',  3),
(3,  'Vasco Rossi - Vasco Live 2026',           '2026-09-05', '20:00:00', '23:30:00',  80.00, 1,  NULL, 'concerti',  3),
(4,  'Negramaro - Unplugged Tour',              '2026-05-18', '21:00:00', '23:00:00',  45.00, 10, 3,    'concerti',  3),
(5,  'Subsonica Live 2026',                     '2026-06-20', '21:30:00', '23:30:00',  40.00, 11, NULL, 'concerti',  3),
(6,  'La Traviata - Arena di Verona',           '2026-07-15', '21:15:00', '23:45:00', 120.00, 15, 2,    'teatro',    3),
(7,  'Il Barbiere di Siviglia',                 '2026-08-02', '21:00:00', '23:15:00',  95.00, 7,  2,    'teatro',    3),
(8,  'Ludovico Einaudi - Elements Tour',        '2026-05-25', '20:30:00', '22:30:00',  60.00, 14, 3,    'concerti',  3),
(9,  'Orchestra Sinfonica - Beethoven Night',   '2026-06-10', '20:00:00', '22:30:00',  50.00, 8,  NULL, 'concerti',  3),
(10, 'Paolo Fresu Quintet',                     '2026-09-12', '21:00:00', '23:00:00',  35.00, 14, 4,    'concerti',  3),
(11, 'Stefano Bollani Solo Piano',              '2026-09-14', '21:30:00', '23:00:00',  42.00, 7,  4,    'famiglia',  3),
(12, 'Sei Personaggi in Cerca d''Autore',       '2026-04-15', '20:30:00', '22:30:00',  38.00, 9,  5,    'teatro',    3),
(13, 'La Metamorfosi',                          '2026-05-08', '21:00:00', '22:45:00',  45.00, 7,  5,    'teatro',    3),
(14, 'Perfetti Sconosciuti - Il Teatro',        '2026-06-03', '21:00:00', '23:00:00',  40.00, 9,  5,    'teatro',    3),
(15, 'Maurizio Crozza Live',                    '2026-05-22', '21:00:00', '23:00:00',  35.00, 12, NULL, 'comedy',    3),
(16, 'Luca e Paolo - Chiedimi se sono di turno','2026-06-28', '21:30:00', '23:30:00',  32.00, 11, NULL, 'comedy',    3),
(17, 'Inter vs Juventus - Serie A',             '2026-04-18', '20:45:00', '22:45:00',  70.00, 1,  NULL, 'sport',     3),
(18, 'Roma vs Lazio - Derby',                   '2026-05-10', '18:00:00', '20:00:00',  65.00, 2,  NULL, 'sport',     3),
(19, 'Notte Bianca Milano',                     '2026-06-21', '20:00:00', NULL,         0.00, 13, 3,    'famiglia',  3),
(20, 'Festival del Cinema all''Aperto',         '2026-07-20', '21:30:00', '23:30:00',   8.00, 14, NULL, 'cinema',    3),
(21, 'Il Re Leone - Musical',                   '2026-08-15', '15:00:00', '17:30:00',  55.00, 4,  NULL, 'famiglia',  3),
(23, 'suca',                                    '2026-02-07', '20:00:00', '23:00:00', 999.99, 5,  NULL, 'famiglia',  3);

-- ----------------------------
-- biglietti (idClasse string → idTipo int FK; Standard=1)
-- ----------------------------
INSERT INTO `biglietti` (`id`, `Nome`, `Cognome`, `Sesso`, `idEvento`, `idTipo`, `Stato`, `idUtente`, `DataCarrello`, `QRCode`) VALUES
(1, NULL, NULL, 'Altro', 18, 1, 'carrello', 4, '2026-01-25 18:53:27', '356db1810e38480360045e06a6644865c516d763cf15b8e34820cbe9aa700f3e'),
(2, NULL, NULL, 'Altro', 18, 1, 'carrello', 4, '2026-01-25 18:53:31', 'cef846f8bd268588210a7dd2b704127f7213c748f08e3998689ea13728ce98f1'),
(3, NULL, NULL, 'Altro', 18, 1, 'carrello', 4, '2026-01-25 18:56:53', '9d287d2818962c2b2dc32ee63641633d1f9374be145590d95960410078e71218'),
(4, NULL, NULL, 'Altro', 18, 1, 'carrello', 4, '2026-01-25 18:56:59', '491a89e67d2ad23c5785914e5b7d02a3bc2938e8af1d22dfe68ad9fc3c649972'),
(6, NULL, NULL, 'Altro', 18, 1, 'carrello', 4, '2026-01-25 19:01:04', '0e5f3f02a9d14ef3be2c5482bb479fbd026b15aeedc0970ffa12f1840b00aa9f'),
(7, NULL, NULL, 'Altro', 18, 1, 'carrello', 4, '2026-01-25 19:01:17', 'ce8775f51e501bfae451c17d9560ed954f15e2d1915c5911a322862b33296761');

-- ----------------------------
-- eventisettori (PostiDisponibili da vecchi settori.PostiDisponibili)
-- ----------------------------
INSERT INTO `eventisettori` (`idEvento`, `idSettore`, `PostiDisponibili`) VALUES
(1,  1,  8000),
(1,  2,  12000),
(1,  3,  12000),
(1,  4,  5000),
(2,  5,  2000),
(2,  6,  5000),
(2,  7,  5700),
(3,  1,  8000),
(3,  2,  12000),
(3,  3,  12000),
(3,  4,  5000),
(4,  11, 2000),
(4,  12, 1000),
(6,  13, 8000),
(6,  14, 3000),
(6,  15, 4000),
(7,  8,  600),
(7,  9,  800),
(7,  10, 630),
(8,  13, 8000),
(11, 3,  12000),
(11, 7,  5700),
(11, 10, 630),
(11, 11, 2000),
(11, 12, 1000),
(11, 13, 8000),
(11, 15, 4000),
(17, 1,  8000),
(17, 2,  12000),
(17, 3,  12000),
(17, 4,  5000);

-- ----------------------------
-- evento_intrattenitori (rinominato da evento_intrattenitore)
-- ----------------------------
INSERT INTO `evento_intrattenitori` (`idEvento`, `idIntrattenitore`) VALUES
(1,  1),
(2,  2),
(3,  3),
(4,  5),
(5,  6),
(6,  7),
(6,  8),
(7,  8),
(8,  9),
(9,  8),
(10, 10),
(11, 11),
(12, 12),
(13, 13),
(14, 14),
(15, 15),
(16, 16);

SET FOREIGN_KEY_CHECKS=1;
