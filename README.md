# Relazione Tecnica – Fase 1: Progettazione del Database

**EventsMaster:**  
**Studente: Bosco Mattia**  
**Classe: 5°C-IT**  
**Data:10/12/2025**  

---
---

## Introduzione

Il progetto EventsMaster nasce con l’obiettivo di realizzare una web application dedicata alla gestione completa della compravendita di biglietti per eventi. L’applicazione deve permettere di organizzare eventi strutturati, distribuirli nel tempo e nello spazio, associarvi intrattenitori, organizzatori e location, e infine consentire agli utenti di acquistare uno o più biglietti scegliendo settore e tipologia, con un prezzo che varia dinamicamente in base a diversi fattori.
Il cuore del progetto è il database, che deve essere progettato in modo solido, coerente e facilmente estendibile. In questa prima fase si affronta quindi la progettazione concettuale e logica del database, partendo dall’analisi dei requisiti fino alla definizione dello schema ER e dello schema relazionale. A progettazione conclusa si passa alla scrittura delle istruzioni DDL per la creazione delle tabelle e delle relative chiavi, seguita dal DML per il popolamento del database tramite un dump di dati. L’attenzione è posta non solo sulla correttezza formale dello schema, ma anche sulla sua aderenza a uno scenario reale di utilizzo e sulla capacità di gestire relazioni complesse senza ridondanze inutili.

---
---

## Analisi dei Requisiti
<!-- Descrizione dei requisiti funzionali e non funzionali, attori, dati necessari, vincoli -->

---
---

## Il Database

### Diagramma ER

```mermaid
---
config:
  layout: elk
id: 8b44d5e3-d22f-4c22-987a-96261e3777f4
---
erDiagram

    MANIFESTAZIONE {
        int id pk
        string Nome 
    }

    INTRATTENITORE {
        int id  pk
        string Nome
        string Mestiere
    }

    RECENSIONE {
        string Messaggio "nullable"
        int Voto
    }

    TEMPO {
        date OraI pk
        date OraF pk
    }

    %% L'entità che segue è solo a scopo grafico/funzionale
    PARTECIPAZIONE_TEMPO {}

    EVENTO {
        int id pk
        string Nome
        float PrezzoNoMod
        date Data
        time OraI
        time OraF
        string TipoEvento
    }

    ORGANIZZATORE {
        int id pk
        string Nome
        string Cognome
        string Ruolo
    }

    LOCATION {
        int id pk
        string Nome
        %% Indirizzo (
        string Stato
        string Regione
        int CAP
        string Città
        string civico "nullable"
        %% )
    }

    BIGLIETTO {
        int id pk
        bool Validato
        string Nome
        string Cognome
        enum sesso
        image qr-code
    }

    TIPO {
        string nome pk
        float ModificatorePrezzo
    }

    SETTORE {
        int id pk
        int Posti
        float MoltiplicatorePrezzo
    }


    ORDINE {
        int id pk
        enum Metodo
    }

    UTENTE {
        string id pk
        string Nome
        string Cognome
        string Email uk
    }

    %%  Da e verso EVENTO[
    TEMPO }o--|| PARTECIPAZIONE_TEMPO : coinvolge
    INTRATTENITORE }o--|| PARTECIPAZIONE_TEMPO : partecipa
    EVENTO }o--|| PARTECIPAZIONE_TEMPO : associato
    
    EVENTO }|--o| MANIFESTAZIONE : "fa parte"
    
    RECENSIONE }o--|| EVENTO : "appartiene"

    ORGANIZZATORE }|--o{ EVENTO : "organizza"

    EVENTO }o--|| LOCATION : "si trova"

    BIGLIETTO }o--||EVENTO : "del"
    %%  ]

    %%  Da e verso UTENTE[
    UTENTE ||--o{ ORDINE : "paga"
    RECENSIONE }o--|| UTENTE : "fatta da"
    %%  ]
    
    %% Rimanenti da e verso BIGLIETTO[
    BIGLIETTO }o--|| TIPO : "classe"
    ORDINE ||--|{ BIGLIETTO   : "di"
    BIGLIETTO }o--|| SETTORE : "il posto si trova"
    %% attributi relazione: prezzo, posto(fila, numero)
    %%  ]

    %% Rimanenti[
    SETTORE }|--|| LOCATION : "appartiene"
    %% ]
```

---
<!-- Schema relazionale logico  -->
### LO SCHEMA RELAZIONALE

---

>#### MANIFESTAZIONI

- id **PK**
- Nome

```md
Manifestazione è caratterizzata da un identificatore univoco e da un nome, la manifestazione non è altro che un insieme di eventi.
```

>#### MANIFESTAZIONE_EVENTI

- *idManifestazione* (Manifestazione-->id)
- *idEvento* (Evento-->id)
- **PK(idManifestazione, idEvento)**

```md
Tabella ausiliara che rappresenta il programma di una manifestazione.
```

>#### EVENTI

- id **PK**
- *idManifestazione* (Manifestazione-->id)
- *idlocation* (Location-->id)
- Nome
- PrezzoNoMod
- Data
- OraI
- OraF

```md
Evento è caratterizzata da un identificatore univoco proprio e da quelli di manifestazione e location, oltra alle chiavi si vuole registrare il nome, la data in cui avviene con annesse ora di inizio e fine in modo da poter poi costruire il programma. In più all'evento è attribuito un prezzo di partenza che verrà poi modificato in base al settore scelto e dalla classe del biglietto.
```

>#### INTRATTENITORI

- id **PK**
- Nome
- Mestiere

```md
Un intrattenitore può essere un singolo come un gruppo infatti nel campo nome ci va quello d'arte. l'attributo mestiere indica il tipo di intrattenitore: comico, cantante, mago,...
```

>#### TEMPI

- OraI **PK**
- OraF **PK**

```md
Si usa l'entità tempi per non avere la stessa esibizione ripetuta in più fasce orarie
```

>#### ESIBIZIONI

- *idIntrattenitore* (Intrattenitore-->id)
- *idEvento* (Evento-->id)
- *OraI* (Tempo-->OraI)
- *OraF* (Tempo-->OraF)
- **PK(idEvento, idIntrattenitore, OraI, OraF)**

```md
Un esibizione non è altro che la relazione tra una fascia oraria, un intrattenitore ed un evento.
```

>#### RECENSIONI

- *idEvento* (Evento-->id)
- *idUtente* (Utente-->id)
- Voto
- Messaggio ***NULL***
- **PK(idEvento, IdUtente)**

```md
La recensione è identificata dall'utente che la fa e dall'evento che viene recensito, una recensione è composta inoltro da una valutazione (obbligatoria) e da un messaggio (opzionale).
```

>#### ORGANIZZATORI

- id **PK**
- Nome
- Cognome
- Ruolo

```md
L'organizzatore è caratterizzato da nome e cognome più il ruolo che ricopre nell'organizzazione dell'evento.
```

>#### ORGANIZZATORI_EVENTO

- *idEvento* (Evento-->id)
- *idOrganizzatore* (Organizzatore-->id)
- **PK(idEvento, idOrganizzatore)**

```md
Tabella ausiliaria che rappresenta un evento con tutti gli organizzatori del caso o, nel caso contrario tutti gli eventi a cui un organizzatore ha lavorato
```

>#### LOCATIONS

- id **PK**
- Nome
- Indirizzo
  - Stato
  - Regione
  - CAP
  - Città
  - civico ***NULL***
  
```md
La location, identificata da un id univoco, è caratterizzata dal nome e dal luogo, il civico non è obbligatorio dato che non sempre c'è.
```

>#### SETTORI

- id **PK**
- *idLocation* (Location-->id)
- Posti
- MoltiplicatorePrezzo

```md
Il settore è un'entità debole della location e ha con se il numero di posti e un moltiplicatore di prezzo per modificare il prezzo di partenza.
```

>#### BIGLIETTI

- id **PK**
- *idEvento* (Evento-->id)
- *idClasse* (Tipo-->id)
- Check
- Nome
- Cognome
- Sesso
- QR-code

```md
Il biglietto, identificato da un suo codice univoco più quello di evento e della classe dello stesso, ha inoltre come attributi nome e cognome della persona a appartiene, il sesso di questa persona e un qr-code che lo rappresenta digitalmente.
```

>#### SETTORE_BIGLIETTI

- *idSettore* (Settore-->id)
- *idBiglietto* (Biglietto-->id)
- Posto
  - Fila
  - Numero
- **PK(idSettore, idBiglietto)**

```md
Tabella ausiliaria che permette di associare un biglietto ad un settore e definire quindi il posto associato al biglietto.
```

>#### ORDINI

- id **PK**
- Metodo

```md
L'ordine definisce il metodo di pagamento usato da un utente per pagare n biglietti
```

>#### ORDINE_BIGLIETTI

- *idOrdine* (Ordine-->id)
- *idBiglietto* (Biglietto-->id)
- **PK(idOrdine, idBiglietto)**

```md
Tabella ausiliara che associa n biglietti ad un ordine.
```

>#### UTENTE

- id **PK**
- Nome
- Cognome
- Email **UK**

```md
L'utente è quello che ha fatto un ordine o una recensione, da non confondere da una persona, quest'ultima infatti non è un entità nel db ma più che altro un campo calcolato dal biglietto. L'utente oltre a nome cognome e id ha anche una mail associata in modo da poter essere aggiunto alla newsletter.
```

>#### UTENTE_ORDINI

- *idOrdine* (Ordine-->id)
- *idUtente* (Utente-->id)
- **PK(idOrdine, idUtente)**

```md
Tabella ausiliaria che lega più ordini ad un utente
```

>#### TIPO

- nome **PK**,  
- ModificatorePrezzo

```md
Tipo è la classe del biglietto (standard, ridotto, vip,...), infatti ha un modificatore di prezzo oltre al nome (id).
```

---
