DROP DATABASE IF EXISTS `5cit_eventsmaster`;
CREATE DATABASE `5cit_eventsmaster` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
USE `5cit_eventsmaster`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- tipo  (deve precedere biglietti per la FK)
-- ----------------------------
CREATE TABLE `tipo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `ModificatorePrezzo` decimal(6,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`),
  CONSTRAINT `tipo_chk_1` CHECK (`ModificatorePrezzo` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- utenti  (deve precedere quasi tutto)
-- ----------------------------
CREATE TABLE `utenti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Cognome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruolo` enum('admin','mod','promoter','user') COLLATE utf8mb4_general_ci DEFAULT 'user',
  `verificato` tinyint(1) DEFAULT '0',
  `verificato_at` datetime DEFAULT NULL,
  `Avatar` mediumblob DEFAULT NULL,
  `DataRegistrazione` datetime DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `email_verification_token` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_verification_token_expiry` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `reset_token` (`reset_token`),
  KEY `idx_ruolo` (`ruolo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- locations
-- ----------------------------
CREATE TABLE `locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Indirizzo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Citta` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `CAP` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Regione` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Capienza` int DEFAULT NULL,  -- limite fisico massimo: SUM(settori.PostiTotali) non deve superarlo
  `Lat` decimal(10,8) DEFAULT NULL,
  `Lng` decimal(11,8) DEFAULT NULL,
  `idCreatore` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_citta` (`Citta`),
  KEY `idx_creatore` (`idCreatore`),
  CONSTRAINT `locations_chk_1` CHECK (`Capienza` IS NULL OR `Capienza` > 0),
  CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`idCreatore`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- manifestazioni
-- ----------------------------
CREATE TABLE `manifestazioni` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Descrizione` text COLLATE utf8mb4_general_ci,
  `DataInizio` date DEFAULT NULL,
  `DataFine` date DEFAULT NULL,
  `Immagine` mediumblob DEFAULT NULL,
  `idCreatore` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`DataInizio`,`DataFine`),
  KEY `idx_creatore` (`idCreatore`),
  CONSTRAINT `manifestazioni_chk_1` CHECK (`DataFine` IS NULL OR `DataFine` >= `DataInizio`),
  CONSTRAINT `manifestazioni_ibfk_1` FOREIGN KEY (`idCreatore`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- settori
-- ----------------------------
CREATE TABLE `settori` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `NumFile` smallint DEFAULT NULL,
  `PostiPerFila` smallint DEFAULT NULL,
  `idLocation` int NOT NULL,
  `MoltiplicatorePrezzo` decimal(5,2) DEFAULT '1.00',
  `PostiTotali` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_location` (`idLocation`),
  CONSTRAINT `settori_ibfk_1` FOREIGN KEY (`idLocation`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `settori_chk_1` CHECK (`MoltiplicatorePrezzo` > 0),
  CONSTRAINT `settori_chk_2` CHECK (`PostiTotali` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- intrattenitori  (rinominato da intrattenitore)
-- ----------------------------
CREATE TABLE `intrattenitori` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Categoria` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`Categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- eventi
-- ----------------------------
CREATE TABLE `eventi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Data` date NOT NULL,
  `OraI` time DEFAULT NULL,
  `OraF` time DEFAULT NULL,
  `Programma` text COLLATE utf8mb4_general_ci,
  `PrezzoNoMod` decimal(7,2) NOT NULL,
  `idLocation` int NOT NULL,
  `idManifestazione` int DEFAULT NULL,
  `Immagine` mediumblob DEFAULT NULL,
  `Categoria` enum('concerti','teatro','sport','comedy','cinema','famiglia','eventi') COLLATE utf8mb4_general_ci DEFAULT 'eventi',
  `idCreatore` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_data` (`Data`),
  KEY `idx_location` (`idLocation`),
  KEY `idx_manifestazione` (`idManifestazione`),
  KEY `idx_categoria` (`Categoria`),
  KEY `idx_creatore` (`idCreatore`),
  CONSTRAINT `eventi_ibfk_1` FOREIGN KEY (`idLocation`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `eventi_ibfk_2` FOREIGN KEY (`idManifestazione`) REFERENCES `manifestazioni` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eventi_ibfk_3` FOREIGN KEY (`idCreatore`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `eventi_chk_1` CHECK (`PrezzoNoMod` >= 0),
  CONSTRAINT `eventi_chk_2` CHECK (`OraF` IS NULL OR `OraF` > `OraI`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- biglietti
-- ----------------------------
CREATE TABLE `biglietti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Cognome` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Sesso` enum('M','F','Altro') COLLATE utf8mb4_general_ci DEFAULT 'Altro',
  `idEvento` int NOT NULL,
  `idTipo` int NOT NULL DEFAULT '1',
  `Stato` enum('carrello','acquistato','validato') COLLATE utf8mb4_general_ci DEFAULT 'carrello',
  `idUtente` int NOT NULL,
  `DataCarrello` datetime DEFAULT CURRENT_TIMESTAMP,
  `DataAcquisto` datetime DEFAULT NULL,
  `QRCode` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `QRCode` (`QRCode`),
  KEY `idx_stato` (`Stato`),
  KEY `idx_utente_stato` (`idUtente`,`Stato`),
  KEY `idx_evento_stato` (`idEvento`,`Stato`),
  CONSTRAINT `biglietti_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `biglietti_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `biglietti_ibfk_3` FOREIGN KEY (`idTipo`) REFERENCES `tipo` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- ordini
-- ----------------------------
CREATE TABLE `ordini` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idUtente` int NOT NULL,
  `MetodoPagamento` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DataOrdine` datetime DEFAULT CURRENT_TIMESTAMP,
  `Totale` decimal(8,2) NOT NULL DEFAULT '0.00',
  `stato` enum('pending','completato','rimborsato') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `idx_data` (`DataOrdine`),
  KEY `idx_utente` (`idUtente`),
  CONSTRAINT `ordini_ibfk_1` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ordini_chk_1` CHECK (`Totale` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- collaboratorieventi  (il creatore unico è eventi.idCreatore / locations.idCreatore / manifestazioni.idCreatore)
-- ----------------------------
CREATE TABLE `collaboratorieventi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idEvento` int NOT NULL,
  `idUtente` int NOT NULL,
  `invitato_da` int DEFAULT NULL,
  `status` enum('pending','accepted','declined','revoked') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `token` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_collaborazione` (`idEvento`,`idUtente`),
  UNIQUE KEY `token` (`token`),
  KEY `idUtente` (`idUtente`),
  KEY `invitato_da` (`invitato_da`),
  KEY `idx_status` (`status`),
  CONSTRAINT `collaboratorieventi_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collaboratorieventi_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collaboratorieventi_ibfk_3` FOREIGN KEY (`invitato_da`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- eventisettori
-- ----------------------------
CREATE TABLE `eventisettori` (
  `idEvento` int NOT NULL,
  `idSettore` int NOT NULL,
  `PostiDisponibili` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`idEvento`,`idSettore`),
  KEY `idSettore` (`idSettore`),
  CONSTRAINT `eventisettori_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eventisettori_ibfk_2` FOREIGN KEY (`idSettore`) REFERENCES `settori` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `eventisettori_chk_1` CHECK (`PostiDisponibili` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- evento_intrattenitori  (rinominato, riferisce intrattenitori)
-- ----------------------------
CREATE TABLE `evento_intrattenitori` (
  `idEvento` int NOT NULL,
  `idIntrattenitore` int NOT NULL,
  `OraInizio` time DEFAULT NULL,
  `OraFine` time DEFAULT NULL,
  `Ordine` tinyint DEFAULT NULL,
  PRIMARY KEY (`idEvento`,`idIntrattenitore`),
  KEY `idIntrattenitore` (`idIntrattenitore`),
  CONSTRAINT `evento_intrattenitori_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evento_intrattenitori_ibfk_2` FOREIGN KEY (`idIntrattenitore`) REFERENCES `intrattenitori` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- notifiche
-- ----------------------------
CREATE TABLE `notifiche` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `destinatario_id` int NOT NULL,
  `mittente_id` int DEFAULT NULL,
  `oggetto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `messaggio` text COLLATE utf8mb4_general_ci,
  `email_inviata` tinyint(1) DEFAULT '0',
  `letta` tinyint(1) DEFAULT '0',
  `letta_at` datetime DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mittente_id` (`mittente_id`),
  KEY `idx_destinatario_letta` (`destinatario_id`,`letta`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `notifiche_ibfk_1` FOREIGN KEY (`destinatario_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifiche_ibfk_2` FOREIGN KEY (`mittente_id`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- ordine_biglietti
-- ----------------------------
CREATE TABLE `ordine_biglietti` (
  `idOrdine` int NOT NULL,
  `idBiglietto` int NOT NULL,
  PRIMARY KEY (`idOrdine`,`idBiglietto`),
  UNIQUE KEY `idBiglietto` (`idBiglietto`),
  CONSTRAINT `ordine_biglietti_ibfk_1` FOREIGN KEY (`idOrdine`) REFERENCES `ordini` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ordine_biglietti_ibfk_2` FOREIGN KEY (`idBiglietto`) REFERENCES `biglietti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- recensioni
-- ----------------------------
CREATE TABLE `recensioni` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idEvento` int NOT NULL,
  `idUtente` int DEFAULT NULL,
  `Voto` tinyint NOT NULL,
  `Commento` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recensione` (`idEvento`,`idUtente`),
  KEY `idUtente` (`idUtente`),
  KEY `idx_evento` (`idEvento`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `recensioni_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recensioni_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recensioni_chk_1` CHECK ((`Voto` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- settore_biglietti
-- ----------------------------
CREATE TABLE `settore_biglietti` (
  `idBiglietto` int NOT NULL,
  `idSettore` int NOT NULL,
  `Fila` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `NumPosto` smallint DEFAULT NULL,
  PRIMARY KEY (`idBiglietto`),
  UNIQUE KEY `unique_posto` (`idSettore`,`Fila`,`NumPosto`),
  CONSTRAINT `settore_biglietti_ibfk_1` FOREIGN KEY (`idBiglietto`) REFERENCES `biglietti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `settore_biglietti_ibfk_2` FOREIGN KEY (`idSettore`) REFERENCES `settori` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
