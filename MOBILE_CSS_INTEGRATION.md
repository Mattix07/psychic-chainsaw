# Integrazione CSS Mobile

## File Creato

È stato creato il file `public/css/mobile.css` con tutte le ottimizzazioni responsive.

## Come Integrarlo

### Opzione 1: Inclusione nel file principale

Se esiste un file `public/css/main.css` o simile che viene incluso in tutte le pagine, aggiungi alla fine:

```css
/* Import mobile styles */
@import url('mobile.css');
```

### Opzione 2: Link diretto nell'HTML

In ogni file HTML/PHP dove includi i CSS (probabilmente in un header comune o all'inizio di ogni view), aggiungi dopo gli altri CSS:

```html
<link rel="stylesheet" href="public/css/main.css">
<link rel="stylesheet" href="public/css/components.css">
<link rel="stylesheet" href="public/css/forms.css">
<!-- AGGIUNGI QUESTO -->
<link rel="stylesheet" href="public/css/mobile.css">
```

### Opzione 3: Includere nel file esistente

Se non c'è un sistema di template condiviso, copia il contenuto di `mobile.css` e incollalo alla fine del tuo CSS principale.

## Meta Tag Viewport

Assicurati che TUTTE le pagine abbiano questo meta tag nell'`<head>`:

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
```

Senza questo tag, i media queries non funzioneranno correttamente su mobile.

## Verifica Funzionamento

1. Apri il sito in Chrome
2. Premi F12 (Dev Tools)
3. Clicca l'icona "Toggle device toolbar" (o Ctrl+Shift+M)
4. Seleziona "iPhone 12 Pro" o simile
5. Ricarica la pagina
6. Verifica che:
   - Font siano più piccoli
   - Pulsanti siano larghi 100%
   - Card siano più compatte
   - Input abbiano padding aumentato

## Classi Utility Disponibili

Il file mobile.css include anche classi utility per controllo responsive:

```html
<!-- Nascondi su mobile -->
<div class="hide-mobile">Questa scritta non apparirà su smartphone</div>

<!-- Mostra solo su mobile -->
<div class="show-mobile">Questa scritta appare solo su smartphone</div>

<!-- Centra testo su mobile -->
<h2 class="text-center-mobile">Centrato solo su mobile</h2>

<!-- Full width su mobile -->
<button class="full-width-mobile">Pulsante largo su mobile</button>

<!-- Stack verticale su mobile -->
<div class="stack-mobile">
    <!-- Gli elementi flex diventeranno verticali su mobile -->
</div>
```

## Breakpoints Utilizzati

- **Mobile**: < 768px
- **Small Mobile**: < 480px
- **Tablet**: 768px - 1024px
- **Landscape Phone**: altezza < 500px
- **Desktop**: > 1024px

## Test Cross-Device

Testa su:
- [ ] iPhone (Safari iOS)
- [ ] Android (Chrome)
- [ ] Tablet iPad
- [ ] Desktop Chrome
- [ ] Desktop Firefox
- [ ] Desktop Edge

## Problemi Comuni

### Font troppo piccoli su iPhone
Assicurati che input/select abbiano `font-size: 16px` minimo, altrimenti iOS farà zoom automatico.

### Layout non responsive
Verifica che il meta viewport sia presente.

### Pulsanti troppo piccoli
I pulsanti devono essere minimo 44x44px per essere touch-friendly.

### Immagini che escono dal container
Usa `max-width: 100%; height: auto;` sulle immagini.

## Performance Mobile

Le ottimizzazioni incluse:
- Font ottimizzati per leggibilità mobile
- Touch targets > 44px
- Rimozione hover effects su touch
- Print styles per biglietti
- Reduced motion per accessibilità
- High contrast mode support
