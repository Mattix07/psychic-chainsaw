<?php
/**
 * Database Schema Configuration
 *
 * Questo file centralizza tutti i nomi di tabelle e colonne del database.
 * Se lo schema cambia, basta modificare qui anziché cercare in tutto il progetto.
 *
 * Uso: require_once __DIR__ . '/../config/database_schema.php';
 */

// ============================================================
// NOMI TABELLE
// ============================================================

define('TABLE_UTENTI', 'Utenti');
define('TABLE_LOCATIONS', 'Locations');
define('TABLE_SETTORI', 'Settori');
define('TABLE_MANIFESTAZIONI', 'Manifestazioni');
define('TABLE_EVENTI', 'Eventi');
define('TABLE_EVENTI_SETTORI', 'EventiSettori');
define('TABLE_INTRATTENITORE', 'Intrattenitore');
define('TABLE_EVENTO_INTRATTENITORE', 'Evento_Intrattenitore');
define('TABLE_TIPO', 'Tipo');
define('TABLE_BIGLIETTI', 'Biglietti');
define('TABLE_SETTORE_BIGLIETTI', 'Settore_Biglietti');
define('TABLE_ORDINI', 'Ordini');
define('TABLE_ORDINE_BIGLIETTI', 'Ordine_Biglietti');
define('TABLE_UTENTE_ORDINI', 'Utente_Ordini');
define('TABLE_RECENSIONI', 'Recensioni');
define('TABLE_CREATORI_EVENTI', 'CreatoriEventi');
define('TABLE_CREATORI_LOCATIONS', 'CreatoriLocations');
define('TABLE_CREATORI_MANIFESTAZIONI', 'CreatoriManifestazioni');
define('TABLE_COLLABORATORI_EVENTI', 'CollaboratoriEventi');
define('TABLE_NOTIFICHE', 'Notifiche');

// ============================================================
// COLONNE TABELLA UTENTI
// ============================================================

define('COL_UTENTI_ID', 'id');
define('COL_UTENTI_NOME', 'Nome');
define('COL_UTENTI_COGNOME', 'Cognome');
define('COL_UTENTI_EMAIL', 'Email');
define('COL_UTENTI_PASSWORD', 'Password');
define('COL_UTENTI_RUOLO', 'ruolo');
define('COL_UTENTI_VERIFICATO', 'verificato');
define('COL_UTENTI_AVATAR', 'Avatar');
define('COL_UTENTI_DATA_REGISTRAZIONE', 'DataRegistrazione');
define('COL_UTENTI_RESET_TOKEN', 'reset_token');
define('COL_UTENTI_RESET_TOKEN_EXPIRY', 'reset_token_expiry');
define('COL_UTENTI_EMAIL_VERIFICATION_TOKEN', 'email_verification_token');

// ============================================================
// COLONNE TABELLA LOCATIONS
// ============================================================

define('COL_LOCATIONS_ID', 'id');
define('COL_LOCATIONS_NOME', 'Nome');
define('COL_LOCATIONS_INDIRIZZO', 'Indirizzo');
define('COL_LOCATIONS_CITTA', 'Citta');
define('COL_LOCATIONS_CAP', 'CAP');
define('COL_LOCATIONS_REGIONE', 'Regione');
define('COL_LOCATIONS_CAPIENZA', 'Capienza');

// ============================================================
// COLONNE TABELLA SETTORI
// ============================================================

define('COL_SETTORI_ID', 'id');
define('COL_SETTORI_NOME', 'Nome');
define('COL_SETTORI_FILA', 'Fila');
define('COL_SETTORI_POSTO', 'Posto');
define('COL_SETTORI_ID_LOCATION', 'idLocation');
define('COL_SETTORI_MOLTIPLICATORE_PREZZO', 'MoltiplicatorePrezzo');
define('COL_SETTORI_POSTI_DISPONIBILI', 'PostiDisponibili');

// ============================================================
// COLONNE TABELLA MANIFESTAZIONI
// ============================================================

define('COL_MANIFESTAZIONI_ID', 'id');
define('COL_MANIFESTAZIONI_NOME', 'Nome');
define('COL_MANIFESTAZIONI_DESCRIZIONE', 'Descrizione');
define('COL_MANIFESTAZIONI_DATA_INIZIO', 'DataInizio');
define('COL_MANIFESTAZIONI_DATA_FINE', 'DataFine');

// ============================================================
// COLONNE TABELLA EVENTI
// ============================================================

define('COL_EVENTI_ID', 'id');
define('COL_EVENTI_NOME', 'Nome');
define('COL_EVENTI_DATA', 'Data');
define('COL_EVENTI_ORA_INIZIO', 'OraI');
define('COL_EVENTI_ORA_FINE', 'OraF');
define('COL_EVENTI_PROGRAMMA', 'Programma');
define('COL_EVENTI_PREZZO_NO_MOD', 'PrezzoNoMod');
define('COL_EVENTI_ID_LOCATION', 'idLocation');
define('COL_EVENTI_ID_MANIFESTAZIONE', 'idManifestazione');
define('COL_EVENTI_IMMAGINE', 'Immagine');
define('COL_EVENTI_CATEGORIA', 'Categoria');

// ============================================================
// COLONNE TABELLA INTRATTENITORE
// ============================================================

define('COL_INTRATTENITORE_ID', 'id');
define('COL_INTRATTENITORE_NOME', 'Nome');
define('COL_INTRATTENITORE_CATEGORIA', 'Categoria');

// ============================================================
// COLONNE TABELLA TIPO (Tipo Biglietto)
// ============================================================

define('COL_TIPO_ID', 'id');
define('COL_TIPO_NOME', 'nome');
define('COL_TIPO_MODIFICATORE_PREZZO', 'ModificatorePrezzo');

// ============================================================
// COLONNE TABELLA BIGLIETTI
// ============================================================

define('COL_BIGLIETTI_ID', 'id');
define('COL_BIGLIETTI_NOME', 'Nome');
define('COL_BIGLIETTI_COGNOME', 'Cognome');
define('COL_BIGLIETTI_SESSO', 'Sesso');
define('COL_BIGLIETTI_ID_EVENTO', 'idEvento');
define('COL_BIGLIETTI_ID_CLASSE', 'idClasse');
define('COL_BIGLIETTI_STATO', 'Stato');
define('COL_BIGLIETTI_ID_UTENTE', 'idUtente');
define('COL_BIGLIETTI_DATA_CARRELLO', 'DataCarrello');
define('COL_BIGLIETTI_QRCODE', 'QRCode');

// ============================================================
// COLONNE TABELLA SETTORE_BIGLIETTI
// ============================================================

define('COL_SETTORE_BIGLIETTI_ID_BIGLIETTO', 'idBiglietto');
define('COL_SETTORE_BIGLIETTI_ID_SETTORE', 'idSettore');
define('COL_SETTORE_BIGLIETTI_FILA', 'Fila');
define('COL_SETTORE_BIGLIETTI_NUMERO', 'Numero');

// ============================================================
// COLONNE TABELLA ORDINI
// ============================================================

define('COL_ORDINI_ID', 'id');
define('COL_ORDINI_METODO_PAGAMENTO', 'MetodoPagamento');
define('COL_ORDINI_DATA_ORDINE', 'DataOrdine');
define('COL_ORDINI_TOTALE', 'Totale');

// ============================================================
// COLONNE TABELLA RECENSIONI
// ============================================================

define('COL_RECENSIONI_ID', 'id');
define('COL_RECENSIONI_ID_EVENTO', 'idEvento');
define('COL_RECENSIONI_ID_UTENTE', 'idUtente');
define('COL_RECENSIONI_VOTO', 'Voto');
define('COL_RECENSIONI_COMMENTO', 'Commento');
define('COL_RECENSIONI_CREATED_AT', 'created_at');

// ============================================================
// COLONNE TABELLA COLLABORATORI_EVENTI
// ============================================================

define('COL_COLLABORATORI_EVENTI_ID', 'id');
define('COL_COLLABORATORI_EVENTI_ID_EVENTO', 'idEvento');
define('COL_COLLABORATORI_EVENTI_ID_UTENTE', 'idUtente');
define('COL_COLLABORATORI_EVENTI_INVITATO_DA', 'invitato_da');
define('COL_COLLABORATORI_EVENTI_STATUS', 'status');
define('COL_COLLABORATORI_EVENTI_TOKEN', 'token');
define('COL_COLLABORATORI_EVENTI_CREATED_AT', 'created_at');
define('COL_COLLABORATORI_EVENTI_UPDATED_AT', 'updated_at');

// ============================================================
// COLONNE TABELLA NOTIFICHE
// ============================================================

define('COL_NOTIFICHE_ID', 'id');
define('COL_NOTIFICHE_TIPO', 'tipo');
define('COL_NOTIFICHE_DESTINATARIO_ID', 'destinatario_id');
define('COL_NOTIFICHE_MITTENTE_ID', 'mittente_id');
define('COL_NOTIFICHE_OGGETTO', 'oggetto');
define('COL_NOTIFICHE_MESSAGGIO', 'messaggio');
define('COL_NOTIFICHE_EMAIL_INVIATA', 'email_inviata');
define('COL_NOTIFICHE_LETTA', 'letta');
define('COL_NOTIFICHE_METADATA', 'metadata');
define('COL_NOTIFICHE_CREATED_AT', 'created_at');

// ============================================================
// VALORI ENUM E COSTANTI
// ============================================================

// Stati biglietto
define('STATO_BIGLIETTO_CARRELLO', 'carrello');
define('STATO_BIGLIETTO_ACQUISTATO', 'acquistato');
define('STATO_BIGLIETTO_VALIDATO', 'validato');

// Categorie eventi
define('CATEGORIA_CONCERTI', 'concerti');
define('CATEGORIA_TEATRO', 'teatro');
define('CATEGORIA_SPORT', 'sport');
define('CATEGORIA_COMEDY', 'comedy');
define('CATEGORIA_CINEMA', 'cinema');
define('CATEGORIA_FAMIGLIA', 'famiglia');

// Ruoli utente
define('RUOLO_USER', 'user');
define('RUOLO_PROMOTER', 'promoter');
define('RUOLO_MOD', 'mod');
define('RUOLO_ADMIN', 'admin');

// Status collaborazione
define('STATUS_PENDING', 'pending');
define('STATUS_ACCEPTED', 'accepted');
define('STATUS_DECLINED', 'declined');

// Sesso
define('SESSO_M', 'M');
define('SESSO_F', 'F');
define('SESSO_ALTRO', 'Altro');

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Costruisce una query SELECT con i nomi delle colonne configurati
 *
 * @param string $table Nome tabella (usa le costanti TABLE_*)
 * @param array $columns Array di nomi colonne (usa le costanti COL_*)
 * @param string $alias Alias opzionale per la tabella
 * @return string Parte SELECT della query
 *
 * @example
 * $select = buildSelect(TABLE_UTENTI, [COL_UTENTI_ID, COL_UTENTI_NOME], 'u');
 * // Risultato: "u.id, u.Nome"
 */
if (!function_exists('buildSelect')) {
    function buildSelect(string $table, array $columns, string $alias = ''): string
    {
        $prefix = $alias ? "$alias." : '';
        return implode(', ', array_map(fn($col) => $prefix . $col, $columns));
    }
}

/**
 * Costruisce una condizione WHERE con il nome colonna configurato
 *
 * @param string $column Nome colonna (usa le costanti COL_*)
 * @param string $alias Alias opzionale per la tabella
 * @return string Condizione WHERE
 *
 * @example
 * $where = buildWhere(COL_UTENTI_ID, 'u');
 * Risultato: "u.id = ?"
 */
if (!function_exists('buildWhere')) {
    function buildWhere(string $column, string $alias = ''): string
    {
        $prefix = $alias ? "$alias." : '';
        return $prefix . $column . ' = ?';
    }
}
