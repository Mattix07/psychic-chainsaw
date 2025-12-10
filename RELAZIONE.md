# Relazione Tecnica – Fase 1: Progettazione del Database

**EventsMaster:**  
**Studente: Bosco Mattia**  
**Classe: 5°C-IT**  
**Data:10/12/2025**  

---

## Indice
<!-- L’indice sarà generato automaticamente nel documento ODT/PDF -->

---

## Introduzione
<!-- Introduzione generale al progetto e agli obiettivi -->

---

## Analisi dei Requisiti
<!-- Descrizione dei requisiti funzionali e non funzionali, attori, dati necessari, vincoli -->

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
        int id pk
        string Messaggio "nullable"
        int Voto
    }

    TEMPO {
        date OraI pk
        date OraF pk
    }

    PARTECIPAZIONE_TEMPO {}

    EVENTO {
        int id pk
        string Nome
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
        int CAP
        string civico "nullable"
        string Indirizzo
        string Città
        string Regione
        string Stato
    }

    BIGLIETTO {
        int matricola pk
        string Nome
        string Cognome
        enum sesso "M/F"
        image qr-code
        date Emissione
        bool Validato
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
        date Data
        float Totale
        enum Metodo "PayPal, Gpay, Wallet"
    }

    UTENTE {
        string email pk
        string Nome
        string Cognome
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
### Lo schema relazionale
<!--TODO: schema logico-->

#### MANIFESTAZIONE

**MANIFESTAZIONE**(  

- id **PK**,  
- Nome  
)

---

#### INTRATTENITORE

**INTRATTENITORE**(  

- id **PK**,  
- Nome,  
- Mestiere  
)

---

## RECENSIONE

**RECENSIONE**(  

- id **PK**,  
- Messaggio *(NULL)*,  
- Voto,  
- EventoID **FK → EVENTO(id)**,  
- UtenteEmail **FK → UTENTE(email)**  
)

---

## TEMPO

**TEMPO**(  

- OraI **PK**,  
- OraF **PK**  
)

---

## PARTECIPAZIONE_TEMPO  

(Relazione tripla EVENTO – INTRATTENITORE – TEMPO)

**PARTECIPAZIONE_TEMPO**(  

- EventoID **FK → EVENTO(id)**,  
- IntrattenitoreID **FK → INTRATTENITORE(id)**,  
- OraI **FK → TEMPO(OraI)**,  
- OraF **FK → TEMPO(OraF)**,  
- **PK(EventoID, IntrattenitoreID, OraI, OraF)**  
)

---

## EVENTO

**EVENTO**(  

- id **PK**,  
- Nome,  
- Data,  
- OraI,  
- OraF,  
- TipoEvento,  
- ManifestazioneID **FK → MANIFESTAZIONE(id)**,  
- LocationID **FK → LOCATION(id)**,  
- OrganizzatoreID **FK → ORGANIZZATORE(id)**  
)

---

## ORGANIZZATORE

**ORGANIZZATORE**(  

- id **PK**,  
- Nome,  
- Cognome,  
- Ruolo  
)

---

## LOCATION

**LOCATION**(  

- id **PK**,  
- Nome,  
- CAP,  
- civico *(NULL)*,  
- Indirizzo,  
- Città,  
- Regione,  
- Stato  
)

---

## BIGLIETTO

**BIGLIETTO**(  

- matricola **PK**,  
- Nome,  
- Cognome,  
- sesso **ENUM('M', 'F')**,  
- qr-code,  
- Emissione,  
- Validato,  
- EventoID **FK → EVENTO(id)**,  
- OrdineID **FK → ORDINE(id)**,  
- TipoNome **FK → TIPO(nome)**,  
- SettoreID **FK → SETTORE(id)**  
)

---

## TIPO

**TIPO**(  

- nome **PK**,  
- ModificatorePrezzo  
)

---

## SETTORE

**SETTORE**(  

- id **PK**,  
- Posti,  
- MoltiplicatorePrezzo,  
- LocationID **FK → LOCATION(id)**  
)

---

## ORDINE

**ORDINE**(  

- id **PK**,  
- Data,  
- Totale,  
- Metodo **ENUM('PayPal', 'Gpay', 'Wallet')**,  
- UtenteEmail **FK → UTENTE(email)**  
)

---

## UTENTE

**UTENTE**(  

- email **PK**,  
- Nome,  
- Cognome  
)
