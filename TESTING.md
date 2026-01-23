# Checklist Testing - EventsMaster

## Setup Iniziale

- [ ] Database migration eseguita con successo
- [ ] Tutte le nuove tabelle presenti nel database
- [ ] File di configurazione aggiornati
- [ ] Route aggiunte a index.php
- [ ] Avatar predefinito presente in `public/img/default-avatar.png`

## 1. Sistema Autenticazione

### User Normale
- [ ] Registrazione nuovo utente funziona
- [ ] Login con credenziali corrette funziona
- [ ] Login con credenziali errate mostra errore
- [ ] Logout funziona correttamente
- [ ] Sessione persiste tra page reload

### Ruoli
- [ ] Admin può accedere a pannello admin
- [ ] Mod può accedere a pannello mod
- [ ] Promoter può accedere a pannello promoter
- [ ] User normale vede home standard
- [ ] Ruoli non autorizzati vengono bloccati

## 2. Gestione Eventi

### Visualizzazione
- [ ] Lista eventi mostra tutti gli eventi futuri
- [ ] Dettaglio evento mostra tutte le informazioni
- [ ] Filtro per categoria funziona
- [ ] Ricerca eventi funziona
- [ ] Eventi passati non appaiono nella lista principale

### Creazione/Modifica (Promoter)
- [ ] Promoter può creare nuovo evento
- [ ] Selezione location funziona
- [ ] Selezione manifestazione funziona
- [ ] Selezione settori disponibili funziona
- [ ] Calcolo automatico biglietti disponibili corretto
- [ ] Upload locandina funziona
- [ ] Evento creato appare nel pannello promoter
- [ ] Creatore evento viene registrato correttamente

### Permessi Modifica
- [ ] Admin può modificare qualsiasi evento
- [ ] Mod può modificare qualsiasi evento
- [ ] Promoter può modificare solo i propri eventi
- [ ] Promoter può modificare eventi a cui collabora
- [ ] User normale NON può modificare eventi

### Collaborazione
- [ ] Promoter può invitare altri promoter
- [ ] Email invito viene salvata nel database
- [ ] Link accettazione funziona
- [ ] Link rifiuto funziona
- [ ] Collaboratore accettato può modificare evento
- [ ] Creatore riceve notifica quando evento viene modificato
- [ ] Admin/Mod possono modificare senza essere invitati

## 3. Carrello e Acquisto

### Aggiunta al Carrello
- [ ] Aggiunta biglietto al carrello funziona
- [ ] Quantità multiple funzionano
- [ ] Selezione settore funziona
- [ ] Selezione tipo biglietto funziona
- [ ] Prezzo calcolato correttamente: (PrezzoBase + ModTipo) * MoltSettore
- [ ] Posto assegnato automaticamente nel settore corretto

### Verifica Disponibilità
- [ ] Controllo disponibilità prima dell'aggiunta
- [ ] Errore se biglietti esauriti
- [ ] Numero massimo calcolato in base ai settori selezionati

### Carrello Persistente
- [ ] Carrello user loggato persiste nel database
- [ ] Carrello user non loggato persiste in localStorage
- [ ] Count carrello aggiornato correttamente
- [ ] Carrello sopravvive al logout/login

### Checkout
- [ ] User non loggato viene reindirizzato a login
- [ ] Promoter/Mod/Admin NON possono acquistare
- [ ] Form dati biglietto funziona
- [ ] Modifica tipo biglietto funziona
- [ ] **Modifica settore funziona**
- [ ] **Prezzo si aggiorna quando cambia settore**
- [ ] **Pop-up conferma prima di modificare**
- [ ] **Pop-up conferma prima di eliminare**
- [ ] Eliminazione singolo biglietto funziona
- [ ] Eliminazione gruppo biglietti funziona
- [ ] Compilazione tutti i campi obbligatori validata
- [ ] Completamento ordine funziona
- [ ] Biglietti passano da 'carrello' a 'acquistato'

## 4. I Miei Biglietti

### Visualizzazione
- [ ] Lista biglietti futuri funziona
- [ ] Lista biglietti passati funziona
- [ ] Biglietti mostrano evento corretto
- [ ] Biglietti mostrano settore e posto
- [ ] Biglietti mostrano QR code

### QR Code
- [ ] QR code contiene: idBiglietto, idUtente, idOrdine, idEvento
- [ ] QR code contiene stato (VALIDO/USATO)
- [ ] QR code contiene nome e cognome
- [ ] QR code contiene settore e posto
- [ ] QR code leggibile da scanner

### PDF/Stampa
- [ ] Modal biglietto si apre al click
- [ ] Foto evento visibile nel modal
- [ ] Pulsante stampa funziona
- [ ] PDF generato contiene tutti i dati
- [ ] Layout PDF corretto

## 5. Storico Ordini

- [ ] Lista ordini mostra tutti gli ordini utente
- [ ] Dettaglio ordine mostra tutti i biglietti
- [ ] Totale ordine corretto
- [ ] Data ordine corretta
- [ ] Metodo pagamento visualizzato
- [ ] Biglietti di eventi eliminati rimangono visibili

## 6. Recensioni

### Visibilità
- [ ] Recensioni visibili solo per eventi passati
- [ ] Recensioni visibili solo per 2 settimane post evento
- [ ] Dopo 2 settimane recensioni non più visibili
- [ ] Media voti calcolata correttamente

### Creazione
- [ ] User può recensire solo eventi per cui ha biglietto
- [ ] User può recensire solo se evento passato
- [ ] User può recensire solo nelle 2 settimane post evento
- [ ] User NON può recensire due volte stesso evento
- [ ] Voto da 1 a 5 validato
- [ ] Messaggio opzionale funziona

### Moderazione
- [ ] Mod può eliminare recensioni
- [ ] Admin può eliminare recensioni
- [ ] User normale NON può eliminare recensioni altrui
- [ ] Eliminazione recensione funziona

## 7. Avatar Utente

### Upload
- [ ] Form upload funziona
- [ ] Solo immagini accettate (JPG, PNG, GIF)
- [ ] File troppo grande (>2MB) rifiutato
- [ ] Immagine ridimensionata se > 1024x1024
- [ ] Avatar salvato nel database
- [ ] Avatar visualizzato correttamente

### Visualizzazione
- [ ] Avatar mostrato in header
- [ ] Avatar mostrato in profilo
- [ ] Avatar mostrato in recensioni
- [ ] Avatar predefinito mostrato se non caricato
- [ ] Cache avatar funziona

### Eliminazione
- [ ] Eliminazione avatar funziona
- [ ] Torna ad avatar predefinito

## 8. Funzionalità Admin

### Eliminazione Biglietti
- [ ] Admin può eliminare tutti i biglietti di un evento
- [ ] Conferma richiesta prima dell'eliminazione
- [ ] Count biglietti eliminati corretto

### Eliminazione Account
- [ ] Admin può eliminare utenti non-admin
- [ ] Admin NON può eliminare altri admin
- [ ] Admin NON può auto-eliminarsi
- [ ] Conferma richiesta

### Eliminazione Location
- [ ] Admin può eliminare location senza eventi
- [ ] Errore se location ha eventi associati
- [ ] Conferma richiesta

### Eliminazione Manifestazione
- [ ] Admin può eliminare manifestazione senza eventi
- [ ] Errore se manifestazione ha eventi associati
- [ ] Conferma richiesta

### Eliminazione Evento
- [ ] Admin può eliminare qualsiasi evento
- [ ] Biglietti acquistati rimangono in storico
- [ ] Conferma richiesta

### Verifica Account
- [ ] Admin/Mod può vedere account non verificati
- [ ] Admin/Mod può verificare account
- [ ] Notifica email inviata a utente verificato
- [ ] Account verificato può accedere a tutte le funzioni

## 9. Funzionalità Locations e Manifestazioni

### Creazione
- [ ] Admin può creare location
- [ ] Mod può creare location
- [ ] Promoter può creare location
- [ ] Creatore location registrato correttamente

- [ ] Admin può creare manifestazione
- [ ] Mod può creare manifestazione
- [ ] Promoter può creare manifestazione
- [ ] Creatore manifestazione registrato correttamente

### Modifica
- [ ] Admin può modificare qualsiasi location/manifestazione
- [ ] Mod può modificare qualsiasi location/manifestazione
- [ ] Promoter può modificare solo proprie location/manifestazioni

### Visibilità
- [ ] Tutte le location visibili a tutti i promoter
- [ ] Tutte le manifestazioni visibili a tutti i promoter

## 10. Settori

### Gestione
- [ ] Settori associati a location
- [ ] Selezione settori per evento funziona
- [ ] Biglietti massimi calcolati da settori selezionati
- [ ] Posti assegnati correttamente nel settore

### Prezzi
- [ ] Moltiplicatore settore applicato correttamente
- [ ] Prezzo finale = (Base + ModTipo) * MoltSettore
- [ ] Cambio settore ricalcola prezzo
- [ ] Totale carrello corretto

## 11. Notifiche Email

### Salvataggio
- [ ] Tutte le notifiche salvate in tabella Notifiche
- [ ] Metadata JSON corretto
- [ ] Timestamp corretto

### Contenuto
- [ ] Email modifica evento contiene modifiche
- [ ] Email invito collaborazione contiene link
- [ ] Email verifica account corretta

### Invio (se abilitato)
- [ ] Email realmente inviate se EmailService($pdo, true)
- [ ] Header email corretti
- [ ] Template HTML visualizzato correttamente

## 12. Cron Job Auto-Eliminazione

### Funzionamento
- [ ] Script eseguibile da command line
- [ ] NON eseguibile da browser
- [ ] Log creato in `logs/cron_delete_events.log`
- [ ] Eventi con data > 2 settimane eliminati
- [ ] Eventi recenti NON eliminati

### Dati Preservati
- [ ] Biglietti acquistati rimangono in ordini
- [ ] Storico ordini intatto
- [ ] Biglietti carrello eliminati
- [ ] Recensioni eliminate con evento

### Log
- [ ] Timestamp ogni operazione
- [ ] Eventi eliminati loggati
- [ ] Errori loggati
- [ ] Transaction rollback in caso errore

## 13. Interfaccia Mobile

### Responsive Design
- [ ] Layout si adatta a smartphone (< 768px)
- [ ] Font leggibili su piccolo schermo
- [ ] Pulsanti abbastanza grandi (min 44x44px)
- [ ] Card eventi compatte
- [ ] Form utilizzabili

### Orientamento
- [ ] Portrait mode funziona
- [ ] Landscape mode funziona
- [ ] Header si adatta

### Touch
- [ ] Tutti i pulsanti cliccabili
- [ ] Scroll fluido
- [ ] Input non causano zoom automatico (iOS)

## 14. Sicurezza

### CSRF
- [ ] Token CSRF generato per ogni sessione
- [ ] Token validato in tutte le operazioni POST
- [ ] Token rigenerato dopo login

### Autenticazione
- [ ] Password hashate con bcrypt
- [ ] Sessioni sicure
- [ ] Logout pulisce sessione

### Autorizzazione
- [ ] Ruoli verificati prima di ogni operazione
- [ ] User non può accedere a funzioni admin
- [ ] User non può modificare dati altrui

### Input Validation
- [ ] SQL injection prevenuta (prepared statements)
- [ ] XSS prevenuta (htmlspecialchars)
- [ ] Upload file validati (tipo, size)
- [ ] Dati sanitizzati

## 15. Performance

### Database
- [ ] Query ottimizzate con JOIN
- [ ] Index presenti su foreign key
- [ ] Paginazione implementata dove serve

### Cache
- [ ] Avatar cacheati browser
- [ ] Static assets cacheati
- [ ] Session data minimizzata

### Load Time
- [ ] Home page carica < 2 secondi
- [ ] Lista eventi carica < 2 secondi
- [ ] Dettaglio evento carica < 1 secondo

## 16. Cross-Browser Compatibility

- [ ] Chrome desktop funziona
- [ ] Firefox desktop funziona
- [ ] Safari desktop funziona
- [ ] Edge desktop funziona
- [ ] Chrome mobile funziona
- [ ] Safari iOS funziona

## 17. Scenari d'Uso Completi

### Scenario 1: Acquisto Biglietto
1. [ ] User si registra
2. [ ] User verifica email (o admin verifica)
3. [ ] User cerca evento
4. [ ] User seleziona evento
5. [ ] User sceglie settore e tipo
6. [ ] User aggiunge al carrello
7. [ ] User va al checkout
8. [ ] User compila dati biglietti
9. [ ] User completa ordine
10. [ ] Biglietto appare in "I miei biglietti"
11. [ ] QR code generato correttamente
12. [ ] User può stampare/salvare PDF

### Scenario 2: Organizzazione Evento
1. [ ] Promoter crea location
2. [ ] Promoter definisce settori location
3. [ ] Promoter crea manifestazione
4. [ ] Promoter crea evento
5. [ ] Promoter seleziona settori disponibili
6. [ ] Promoter invita collaboratore
7. [ ] Collaboratore accetta invito
8. [ ] Collaboratore modifica evento
9. [ ] Promoter riceve notifica modifica
10. [ ] Admin verifica evento
11. [ ] Evento pubblicato
12. [ ] User acquista biglietto

### Scenario 3: Moderazione
1. [ ] User lascia recensione inappropriata
2. [ ] Mod vede recensione
3. [ ] Mod elimina recensione
4. [ ] Recensione sparisce
5. [ ] User viene notificato (opzionale)

### Scenario 4: Pulizia Automatica
1. [ ] Evento si svolge
2. [ ] Passano 15 giorni
3. [ ] Cron job esegue
4. [ ] Evento eliminato
5. [ ] Biglietti acquistati ancora in storico
6. [ ] Biglietti carrello eliminati

## Note per il Testing

- Testare con dati realistici (non solo 1-2 record)
- Testare edge cases (0 risultati, 1000+ risultati)
- Testare concorrenza (più user contemporaneamente)
- Testare su connessione lenta
- Testare con diversi ruoli simultaneamente
- Monitorare log errori PHP
- Monitorare log cron job
- Verificare integrità database dopo ogni test

## Bug Comuni da Verificare

- [ ] Prezzi arrotondati correttamente (2 decimali)
- [ ] Date formattate correttamente
- [ ] Timezone consistente
- [ ] Encoding UTF-8 ovunque
- [ ] Special characters gestiti (à, è, €, ecc.)
- [ ] File upload paths corretti (Windows vs Linux)
- [ ] Session timeout gestito
- [ ] Memory limit non superato con upload grandi
- [ ] SQL NULL values gestiti correttamente
- [ ] Empty arrays non causano errori
