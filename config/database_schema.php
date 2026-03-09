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

define('TABLE_UTENTI',                 'utenti');
define('TABLE_LOCATIONS',              'locations');
define('TABLE_SETTORI',                'settori');
define('TABLE_MANIFESTAZIONI',         'manifestazioni');
define('TABLE_EVENTI',                 'eventi');
define('TABLE_EVENTI_SETTORI',         'eventisettori');
define('TABLE_INTRATTENITORI',         'intrattenitori');
define('TABLE_EVENTO_INTRATTENITORI',  'evento_intrattenitori');
define('TABLE_TIPO',                   'tipo');
define('TABLE_BIGLIETTI',              'biglietti');
define('TABLE_SETTORE_BIGLIETTI',      'settore_biglietti');
define('TABLE_ORDINI',                 'ordini');
define('TABLE_ORDINE_BIGLIETTI',       'ordine_biglietti');
define('TABLE_RECENSIONI',             'recensioni');
define('TABLE_COLLABORATORI_EVENTI',   'collaboratorieventi');
define('TABLE_NOTIFICHE',              'notifiche');

// Alias per compatibilità con codice che usa i vecchi nomi delle costanti
define('TABLE_INTRATTENITORE',         TABLE_INTRATTENITORI);
define('TABLE_EVENTO_INTRATTENITORE',  TABLE_EVENTO_INTRATTENITORI);

// ============================================================
// COLONNE TABELLA UTENTI
// ============================================================

define('COL_UTENTI_ID',                              'id');
define('COL_UTENTI_NOME',                            'Nome');
define('COL_UTENTI_COGNOME',                         'Cognome');
define('COL_UTENTI_EMAIL',                           'Email');
define('COL_UTENTI_PASSWORD',                        'Password');
define('COL_UTENTI_RUOLO',                           'ruolo');
define('COL_UTENTI_VERIFICATO',                      'verificato');
define('COL_UTENTI_VERIFICATO_AT',                   'verificato_at');
define('COL_UTENTI_AVATAR',                          'Avatar');
define('COL_UTENTI_DATA_REGISTRAZIONE',              'DataRegistrazione');
define('COL_UTENTI_RESET_TOKEN',                     'reset_token');
define('COL_UTENTI_RESET_TOKEN_EXPIRY',              'reset_token_expiry');
define('COL_UTENTI_EMAIL_VERIFICATION_TOKEN',        'email_verification_token');
define('COL_UTENTI_EMAIL_VERIFICATION_TOKEN_EXPIRY', 'email_verification_token_expiry');
define('COL_UTENTI_DELETED_AT',                      'deleted_at');

// ============================================================
// COLONNE TABELLA LOCATIONS
// ============================================================

define('COL_LOCATIONS_ID',          'id');
define('COL_LOCATIONS_NOME',        'Nome');
define('COL_LOCATIONS_INDIRIZZO',   'Indirizzo');
define('COL_LOCATIONS_CITTA',       'Citta');
define('COL_LOCATIONS_CAP',         'CAP');
define('COL_LOCATIONS_REGIONE',     'Regione');
define('COL_LOCATIONS_CAPIENZA',    'Capienza');
define('COL_LOCATIONS_LAT',         'Lat');
define('COL_LOCATIONS_LNG',         'Lng');
define('COL_LOCATIONS_ID_CREATORE', 'idCreatore');

// ============================================================
// COLONNE TABELLA SETTORI
// ============================================================

define('COL_SETTORI_ID',                   'id');
define('COL_SETTORI_NOME',                 'Nome');
define('COL_SETTORI_NUM_FILE',             'NumFile');
define('COL_SETTORI_POSTI_PER_FILA',       'PostiPerFila');
define('COL_SETTORI_ID_LOCATION',          'idLocation');
define('COL_SETTORI_MOLTIPLICATORE_PREZZO','MoltiplicatorePrezzo');
define('COL_SETTORI_POSTI_TOTALI',         'PostiTotali');

// Alias per compatibilità con codice vecchio
define('COL_SETTORI_FILA',             COL_SETTORI_NUM_FILE);
define('COL_SETTORI_POSTO',            COL_SETTORI_POSTI_PER_FILA);
define('COL_SETTORI_POSTI_DISPONIBILI','PostiTotali');

// ============================================================
// COLONNE TABELLA MANIFESTAZIONI
// ============================================================

define('COL_MANIFESTAZIONI_ID',           'id');
define('COL_MANIFESTAZIONI_NOME',         'Nome');
define('COL_MANIFESTAZIONI_DESCRIZIONE',  'Descrizione');
define('COL_MANIFESTAZIONI_DATA_INIZIO',  'DataInizio');
define('COL_MANIFESTAZIONI_DATA_FINE',    'DataFine');
define('COL_MANIFESTAZIONI_ID_CREATORE',  'idCreatore');

// ============================================================
// COLONNE TABELLA EVENTI
// ============================================================

define('COL_EVENTI_ID',              'id');
define('COL_EVENTI_NOME',            'Nome');
define('COL_EVENTI_DATA',            'Data');
define('COL_EVENTI_ORA_INIZIO',      'OraI');
define('COL_EVENTI_ORA_FINE',        'OraF');
define('COL_EVENTI_PROGRAMMA',       'Programma');
define('COL_EVENTI_PREZZO_NO_MOD',   'PrezzoNoMod');
define('COL_EVENTI_ID_LOCATION',     'idLocation');
define('COL_EVENTI_ID_MANIFESTAZIONE','idManifestazione');
define('COL_EVENTI_IMMAGINE',        'Immagine');
define('COL_EVENTI_CATEGORIA',       'Categoria');
define('COL_EVENTI_ID_CREATORE',     'idCreatore');

// ============================================================
// COLONNE TABELLA INTRATTENITORI
// ============================================================

define('COL_INTRATTENITORE_ID',        'id');
define('COL_INTRATTENITORE_NOME',      'Nome');
define('COL_INTRATTENITORE_CATEGORIA', 'Categoria');

// ============================================================
// COLONNE TABELLA TIPO (Tipo Biglietto)
// ============================================================

define('COL_TIPO_ID',                  'id');
define('COL_TIPO_NOME',                'nome');
define('COL_TIPO_MODIFICATORE_PREZZO', 'ModificatorePrezzo');

// ============================================================
// COLONNE TABELLA BIGLIETTI
// ============================================================

define('COL_BIGLIETTI_ID',            'id');
define('COL_BIGLIETTI_NOME',          'Nome');
define('COL_BIGLIETTI_COGNOME',       'Cognome');
define('COL_BIGLIETTI_SESSO',         'Sesso');
define('COL_BIGLIETTI_ID_EVENTO',     'idEvento');
define('COL_BIGLIETTI_ID_TIPO',       'idTipo');
define('COL_BIGLIETTI_STATO',         'Stato');
define('COL_BIGLIETTI_ID_UTENTE',     'idUtente');
define('COL_BIGLIETTI_DATA_CARRELLO', 'DataCarrello');
define('COL_BIGLIETTI_DATA_ACQUISTO', 'DataAcquisto');
define('COL_BIGLIETTI_QRCODE',        'QRCode');

// Alias vecchio nome per compatibilità
define('COL_BIGLIETTI_ID_CLASSE', COL_BIGLIETTI_ID_TIPO);

// ============================================================
// COLONNE TABELLA SETTORE_BIGLIETTI
// ============================================================

define('COL_SETTORE_BIGLIETTI_ID_BIGLIETTO', 'idBiglietto');
define('COL_SETTORE_BIGLIETTI_ID_SETTORE',   'idSettore');
define('COL_SETTORE_BIGLIETTI_FILA',         'Fila');
define('COL_SETTORE_BIGLIETTI_NUMERO',       'NumPosto');

// ============================================================
// COLONNE TABELLA ORDINI
// ============================================================

define('COL_ORDINI_ID',                'id');
define('COL_ORDINI_ID_UTENTE',         'idUtente');
define('COL_ORDINI_METODO_PAGAMENTO',  'MetodoPagamento');
define('COL_ORDINI_DATA_ORDINE',       'DataOrdine');
define('COL_ORDINI_TOTALE',            'Totale');
define('COL_ORDINI_STATO',             'stato');

// ============================================================
// COLONNE TABELLA RECENSIONI
// ============================================================

define('COL_RECENSIONI_ID',         'id');
define('COL_RECENSIONI_ID_EVENTO',  'idEvento');
define('COL_RECENSIONI_ID_UTENTE',  'idUtente');
define('COL_RECENSIONI_VOTO',       'Voto');
define('COL_RECENSIONI_COMMENTO',   'Commento');
define('COL_RECENSIONI_CREATED_AT', 'created_at');

// ============================================================
// COLONNE TABELLA COLLABORATORI_EVENTI
// ============================================================

define('COL_COLLABORATORI_EVENTI_ID',          'id');
define('COL_COLLABORATORI_EVENTI_ID_EVENTO',   'idEvento');
define('COL_COLLABORATORI_EVENTI_ID_UTENTE',   'idUtente');
define('COL_COLLABORATORI_EVENTI_INVITATO_DA', 'invitato_da');
define('COL_COLLABORATORI_EVENTI_STATUS',      'status');
define('COL_COLLABORATORI_EVENTI_TOKEN',       'token');
define('COL_COLLABORATORI_EVENTI_TOKEN_EXPIRY','token_expiry');
define('COL_COLLABORATORI_EVENTI_CREATED_AT',  'created_at');
define('COL_COLLABORATORI_EVENTI_UPDATED_AT',  'updated_at');

// ============================================================
// COLONNE TABELLA NOTIFICHE
// ============================================================

define('COL_NOTIFICHE_ID',              'id');
define('COL_NOTIFICHE_TIPO',            'tipo');
define('COL_NOTIFICHE_DESTINATARIO_ID', 'destinatario_id');
define('COL_NOTIFICHE_MITTENTE_ID',     'mittente_id');
define('COL_NOTIFICHE_OGGETTO',         'oggetto');
define('COL_NOTIFICHE_MESSAGGIO',       'messaggio');
define('COL_NOTIFICHE_EMAIL_INVIATA',   'email_inviata');
define('COL_NOTIFICHE_LETTA',           'letta');
define('COL_NOTIFICHE_METADATA',        'metadata');
define('COL_NOTIFICHE_CREATED_AT',      'created_at');

// ============================================================
// COLONNE TABELLA EVENTISETTORI
// ============================================================

define('COL_EVENTISETTORI_ID_EVENTO',          'idEvento');
define('COL_EVENTISETTORI_ID_SETTORE',         'idSettore');
define('COL_EVENTISETTORI_POSTI_DISPONIBILI',  'PostiDisponibili');

// ============================================================
// VALORI ENUM E COSTANTI
// ============================================================

// Stati biglietto
define('STATO_BIGLIETTO_CARRELLO',   'carrello');
define('STATO_BIGLIETTO_ACQUISTATO', 'acquistato');
define('STATO_BIGLIETTO_VALIDATO',   'validato');

// Categorie eventi
define('CATEGORIA_CONCERTI', 'concerti');
define('CATEGORIA_TEATRO',   'teatro');
define('CATEGORIA_SPORT',    'sport');
define('CATEGORIA_COMEDY',   'comedy');
define('CATEGORIA_CINEMA',   'cinema');
define('CATEGORIA_FAMIGLIA', 'famiglia');
define('CATEGORIA_EVENTI',   'eventi');

// Ruoli utente
define('RUOLO_USER',     'user');
define('RUOLO_PROMOTER', 'promoter');
define('RUOLO_MOD',      'mod');
define('RUOLO_ADMIN',    'admin');

// Status collaborazione
define('STATUS_PENDING',  'pending');
define('STATUS_ACCEPTED', 'accepted');
define('STATUS_DECLINED', 'declined');
define('STATUS_REVOKED',  'revoked');

// Sesso
define('SESSO_M',     'M');
define('SESSO_F',     'F');
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
    function buildSelect(string $_table, array $columns, string $alias = ''): string
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
