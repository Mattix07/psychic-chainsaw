<?php
/**
 * Dettaglio singolo evento
 *
 * Pagina completa per la visualizzazione di un evento con tutte le informazioni
 * necessarie per l'acquisto dei biglietti.
 *
 * Struttura:
 * - Hero Section: immagine di sfondo, titolo, data/ora, location, rating medio
 * - Info Cards: prezzo base, location, data, orario
 * - Programma: descrizione dettagliata dell'evento (se presente)
 * - Intrattenitori: lista artisti con mestiere e orario esibizione
 * - Recensioni: lista recensioni utenti + form per aggiungerne una
 * - Sidebar: selettore biglietti con calcolo prezzo dinamico
 * - Eventi Correlati: altri eventi della stessa manifestazione
 *
 * Il prezzo finale viene calcolato in JavaScript considerando:
 * - Prezzo base dell'evento
 * - Modificatore del tipo biglietto (Standard, VIP, ecc.)
 * - Moltiplicatore del settore
 * - Quantità selezionata
 */
require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/../models/Biglietto.php';
require_once __DIR__ . '/../models/Evento.php';

// Dati evento passati dal controller via sessione
$evento = $_SESSION['evento_corrente'] ?? null;
$intrattenitori = $_SESSION['intrattenitori_evento'] ?? [];
$recensioni = $_SESSION['recensioni_evento'] ?? [];
$mediaVoti = $_SESSION['media_voti'] ?? null;

if (!$evento) {
    echo '<div class="no-data-container"><p class="no-data">Evento non trovato.</p></div>';
    return;
}

// Dati per il selettore biglietti
$settori = getSettoriByLocation($pdo, $evento['idLocation']);
$tipi = getAllTipiBiglietto($pdo);

// Eventi correlati: altri eventi della stessa manifestazione (escludendo quello corrente)
$eventiCorrelati = [];
if (!empty($evento['idManifestazione'])) {
    $eventiCorrelati = getEventiByManifestazione($pdo, $evento['idManifestazione']);
    $eventiCorrelati = array_filter($eventiCorrelati, fn($e) => $e['id'] !== $evento['id']);
}
?>

<!--
    HERO SECTION
    Banner a tutto schermo con immagine evento, overlay gradient e info principali.
    Mostra: manifestazione, titolo, data/ora, location, rating medio.
-->
<section class="evento-hero">
    <div class="evento-hero-bg" style="background-image: url('<?= e($evento['Immagine'] ?? 'public/img/placeholder.jpg') ?>')"></div>
    <div class="evento-hero-overlay"></div>
    <div class="evento-hero-content">
        <div class="evento-hero-info">
            <?php if ($evento['ManifestazioneName']): ?>
            <span class="evento-badge"><?= e($evento['ManifestazioneName']) ?></span>
            <?php endif; ?>
            <h1><?= e($evento['Nome']) ?></h1>
            <div class="evento-meta">
                <span><i class="far fa-calendar"></i> <?= formatDate($evento['Data']) ?></span>
                <span><i class="far fa-clock"></i> <?= formatTime($evento['OraI']) ?> - <?= formatTime($evento['OraF']) ?></span>
                <span><i class="fas fa-map-marker-alt"></i> <?= e($evento['LocationName']) ?></span>
            </div>
            <?php if ($mediaVoti): ?>
            <div class="evento-rating">
                <span class="stars">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fa<?= $i <= round($mediaVoti) ? 's' : 'r' ?> fa-star"></i>
                    <?php endfor; ?>
                </span>
                <span class="rating-text"><?= number_format($mediaVoti, 1) ?>/5 (<?= count($recensioni) ?> recensioni)</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!--
    CONTENUTO PRINCIPALE
    Layout a due colonne: main (info, intrattenitori, recensioni) + sidebar (acquisto).
-->
<div class="evento-content">
    <div class="evento-main">
        <!-- Info Evento: card con prezzo, location, data, orario + programma -->
        <section class="evento-section">
            <h2><i class="fas fa-info-circle"></i> Informazioni</h2>
            <div class="info-cards">
                <div class="info-card">
                    <i class="fas fa-euro-sign"></i>
                    <div>
                        <span class="label">Prezzo Base</span>
                        <span class="value"><?= formatPrice($evento['PrezzoNoMod']) ?></span>
                    </div>
                </div>
                <div class="info-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <span class="label">Location</span>
                        <span class="value"><?= e($evento['LocationName']) ?></span>
                    </div>
                </div>
                <div class="info-card">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <span class="label">Data</span>
                        <span class="value"><?= formatDate($evento['Data']) ?></span>
                    </div>
                </div>
                <div class="info-card">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="label">Orario</span>
                        <span class="value"><?= formatTime($evento['OraI']) ?> - <?= formatTime($evento['OraF']) ?></span>
                    </div>
                </div>
            </div>

            <?php if ($evento['Programma']): ?>
            <div class="programma-box">
                <h3><i class="fas fa-list-ul"></i> Programma</h3>
                <p><?= nl2br(e($evento['Programma'])) ?></p>
            </div>
            <?php endif; ?>
        </section>

        <!--
            Intrattenitori
            Lista degli artisti/performer che partecipano all'evento.
            Dati dalla tabella Esibizioni che collega Intrattenitori a Eventi.
        -->
        <?php if (!empty($intrattenitori)): ?>
        <section class="evento-section">
            <h2><i class="fas fa-users"></i> Artisti & Intrattenitori</h2>
            <div class="intrattenitori-grid">
                <?php foreach ($intrattenitori as $i): ?>
                <div class="intrattenitore-card">
                    <div class="intrattenitore-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="intrattenitore-info">
                        <h4><?= e($i['Nome']) ?></h4>
                        <span class="mestiere"><?= e($i['Mestiere']) ?></span>
                        <span class="orario"><i class="far fa-clock"></i> <?= formatTime($i['OraI']) ?> - <?= formatTime($i['OraF']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!--
            Recensioni
            Lista delle recensioni esistenti + form per aggiungerne una nuova.
            Il form appare solo se l'utente è loggato e non ha già recensito.
        -->
        <section class="evento-section">
            <h2><i class="fas fa-star"></i> Recensioni</h2>
            <?php if (empty($recensioni)): ?>
                <p class="no-data">Nessuna recensione ancora. Sii il primo a lasciarne una!</p>
            <?php else: ?>
                <div class="recensioni-list">
                    <?php foreach ($recensioni as $r): ?>
                    <div class="recensione-card">
                        <div class="recensione-header">
                            <div class="recensione-user">
                                <div class="user-avatar"><?= strtoupper(substr($r['Nome'], 0, 1) . substr($r['Cognome'], 0, 1)) ?></div>
                                <span class="user-name"><?= e($r['Nome']) ?> <?= e($r['Cognome']) ?></span>
                            </div>
                            <div class="recensione-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa<?= $i <= $r['Voto'] ? 's' : 'r' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if ($r['Messaggio']): ?>
                        <p class="recensione-testo"><?= nl2br(e($r['Messaggio'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (isLoggedIn() && !hasRecensito($pdo, $evento['id'], $_SESSION['user_id'])): ?>
            <div class="aggiungi-recensione">
                <h3>Lascia una Recensione</h3>
                <form method="post" action="index.php" class="recensione-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_recensione">
                    <input type="hidden" name="idEvento" value="<?= $evento['id'] ?>">

                    <div class="star-rating-input">
                        <label>Il tuo voto:</label>
                        <div class="star-select">
                            <?php for($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="voto" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                            <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="messaggio">Commento (opzionale):</label>
                        <textarea id="messaggio" name="messaggio" rows="3" placeholder="Condividi la tua esperienza..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Invia Recensione
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <!--
        SIDEBAR - Acquisto Biglietti
        Form per selezionare tipo biglietto, settore e quantità.
        Il prezzo totale viene calcolato in tempo reale via JavaScript.
        I dati del partecipante vengono richiesti al checkout.
    -->
    <?php
    // Calcola disponibilità biglietti
    $disponibili = getBigliettiDisponibili($pdo, $evento['id']);
    $esaurito = $disponibili !== null && $disponibili <= 0;
    ?>
    <aside class="evento-sidebar">
        <div class="ticket-selector">
            <h3><i class="fas fa-ticket-alt"></i> Acquista Biglietti</h3>

            <?php if ($esaurito): ?>
            <div class="ticket-soldout">
                <i class="fas fa-ban"></i>
                <span>Biglietti esauriti</span>
            </div>
            <?php else: ?>

            <div class="ticket-price-display">
                <span class="label">A partire da</span>
                <span class="price"><?= formatPrice($evento['PrezzoNoMod']) ?></span>
            </div>

            <?php if ($disponibili !== null): ?>
            <div class="ticket-availability">
                <i class="fas fa-ticket-alt"></i>
                <span id="ticketAvailability"><?= $disponibili ?> biglietti disponibili</span>
            </div>
            <?php endif; ?>

            <form id="add-to-cart-form" class="ticket-form">
                <input type="hidden" name="idEvento" value="<?= $evento['id'] ?>">
                <input type="hidden" name="eventoNome" value="<?= e($evento['Nome']) ?>">
                <input type="hidden" name="eventoData" value="<?= $evento['Data'] ?>">
                <input type="hidden" name="prezzoBase" value="<?= $evento['PrezzoNoMod'] ?>">
                <input type="hidden" name="maxBiglietti" value="<?= $disponibili ?? '' ?>">

                <div class="form-group">
                    <label for="idClasse">Tipo Biglietto</label>
                    <select id="idClasse" name="idClasse" required>
                        <?php foreach ($tipi as $tipo): ?>
                        <option value="<?= e($tipo['nome']) ?>" data-mod="<?= $tipo['ModificatorePrezzo'] ?>">
                            <?= e($tipo['nome']) ?> (+<?= formatPrice($tipo['ModificatorePrezzo']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="idSettore">Settore</label>
                    <select id="idSettore" name="idSettore" required>
                        <?php foreach ($settori as $s): ?>
                        <option value="<?= $s['id'] ?>" data-mult="<?= $s['MoltiplicatorePrezzo'] ?>" data-posti="<?= $s['Posti'] ?>">
                            Settore <?= $s['id'] ?> - x<?= $s['MoltiplicatorePrezzo'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Quantità</label>
                    <div class="quantity-selector">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">-</button>
                        <input type="number" id="quantita" name="quantita" value="1" min="1" max="<?= $disponibili ? min(10, $disponibili) : 10 ?>" readonly>
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                </div>

                <div class="ticket-total">
                    <span class="label">Totale</span>
                    <span class="total-price" id="totalPrice"><?= formatPrice($evento['PrezzoNoMod']) ?></span>
                </div>

                <button type="button" class="btn btn-primary btn-block btn-large" onclick="addEventToCart()" id="addToCartBtn">
                    <i class="fas fa-cart-plus"></i> Aggiungi al Carrello
                </button>
            </form>

            <p class="ticket-note">
                <i class="fas fa-info-circle"></i>
                I dati del partecipante verranno richiesti al momento del checkout.
            </p>
            <?php endif; ?>
        </div>
    </aside>
</div>

<!--
    EVENTI CORRELATI
    Carousel con altri eventi della stessa manifestazione.
    Utile per cross-selling e navigazione tra date di un tour/festival.
-->
<?php if (!empty($eventiCorrelati)): ?>
<section class="row-section">
    <div class="row-header">
        <h2><i class="fas fa-calendar-alt"></i> Altri eventi di <?= e($evento['ManifestazioneName']) ?></h2>
    </div>
    <div class="carousel-container">
        <button class="carousel-nav prev" data-carousel="featured"><i class="fas fa-chevron-left"></i></button>
        <div class="carousel" id="featured">
            <?php foreach ($eventiCorrelati as $evento): ?>
            <article class="event-card large" onclick="window.location='index.php?action=view_evento&id=<?= $evento['id'] ?>'">
                <div class="event-card-poster">
                    <img src="img/events/<?= $evento['id'] ?>.jpg"
                         alt="<?= e($evento['Nome']) ?>"
                         onerror="this.src='https://picsum.photos/400/600?random=<?= $evento['id'] ?>'">
                    <span class="event-card-badge">In vendita</span>
                    <div class="event-card-overlay">
                        <div class="event-card-actions">
                            <button class="card-action-btn primary" onclick="event.stopPropagation(); addToCart(<?= $evento['id'] ?>, 1, '<?= e($evento['Nome']) ?>', 'Standard', <?= $evento['PrezzoNoMod'] ?>, '<?= formatDate($evento['Data']) ?>', 'img/events/<?= $evento['id'] ?>.jpg')"><i class="fas fa-cart-plus"></i></button>
                            <button class="card-action-btn"><i class="fas fa-heart"></i></button>
                        </div>
                    </div>
                </div>
                <div class="event-card-info">
                    <h3 class="event-card-title"><?= e($evento['Nome']) ?></h3>
                    <div class="event-card-meta">
                        <span class="event-card-date"><?= formatDate($evento['Data']) ?></span>
                        <span><?= e($evento['LocationName']) ?></span>
                        <span class="event-card-price">da <?= formatPrice($evento['PrezzoNoMod']) ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <button class="carousel-nav next" data-carousel="featured"><i class="fas fa-chevron-right"></i></button>
    </div>
</section>
<?php endif; ?>

<script>
/**
 * Calcola e aggiorna il prezzo totale in base alle selezioni.
 * Formula: (prezzoBase + modificatoreTipo) * moltiplicatoreSettore * quantità
 */
function updatePrice() {
    const form = document.getElementById('add-to-cart-form');
    const prezzoBase = parseFloat(form.querySelector('[name="prezzoBase"]').value);
    const tipoSelect = document.getElementById('idClasse');
    const settoreSelect = document.getElementById('idSettore');
    const quantita = parseInt(document.getElementById('quantita').value);

    const modPrezzo = parseFloat(tipoSelect.options[tipoSelect.selectedIndex].dataset.mod) || 0;
    const multPrezzo = parseFloat(settoreSelect.options[settoreSelect.selectedIndex].dataset.mult) || 1;

    const prezzoUnitario = (prezzoBase + modPrezzo) * multPrezzo;
    const totale = prezzoUnitario * quantita;

    document.getElementById('totalPrice').textContent = '€' + totale.toFixed(2);
}

/**
 * Modifica la quantità di biglietti (min 1, max 10)
 * @param {number} delta - Incremento (+1) o decremento (-1)
 */
function changeQty(delta) {
    const input = document.getElementById('quantita');
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(10, val));
    input.value = val;
    updatePrice();
}

/**
 * Aggiunge i biglietti selezionati al carrello.
 * Usa le API server se l'utente è loggato, localStorage altrimenti.
 */
async function addEventToCart() {
    const form = document.getElementById('add-to-cart-form');
    const formData = new FormData(form);
    const btn = document.getElementById('addToCartBtn');

    const tipoSelect = document.getElementById('idClasse');
    const settoreSelect = document.getElementById('idSettore');
    const quantita = parseInt(document.getElementById('quantita').value);

    const prezzoBase = parseFloat(formData.get('prezzoBase'));
    const modPrezzo = parseFloat(tipoSelect.options[tipoSelect.selectedIndex].dataset.mod) || 0;
    const multPrezzo = parseFloat(settoreSelect.options[settoreSelect.selectedIndex].dataset.mult) || 1;
    const prezzoUnitario = (prezzoBase + modPrezzo) * multPrezzo;

    // Disabilita bottone durante l'operazione
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aggiunta...';

    const item = {
        eventoId: formData.get('idEvento'),
        idEvento: formData.get('idEvento'),
        eventName: formData.get('eventoNome'),
        eventoNome: formData.get('eventoNome'),
        eventDate: formData.get('eventoData'),
        eventoData: formData.get('eventoData'),
        tipoId: formData.get('idClasse'),
        idClasse: formData.get('idClasse'),
        ticketType: formData.get('idClasse'),
        idSettore: formData.get('idSettore'),
        settoreNome: settoreSelect.options[settoreSelect.selectedIndex].text,
        price: prezzoUnitario,
        prezzo: prezzoUnitario,
        quantity: quantita
    };

    try {
        const result = await window.addToCart(item);

        if (result) {
            // Aggiorna disponibilità mostrata
            const availabilityEl = document.getElementById('ticketAvailability');
            if (availabilityEl && typeof Cart !== 'undefined' && Cart.isLoggedIn()) {
                const availability = await Cart.checkAvailability(item.idEvento);
                if (!availability.illimitati) {
                    availabilityEl.textContent = availability.rimanenti + ' biglietti disponibili';

                    // Aggiorna max quantità
                    const qtyInput = document.getElementById('quantita');
                    qtyInput.max = Math.min(10, availability.rimanenti);
                    if (parseInt(qtyInput.value) > availability.rimanenti) {
                        qtyInput.value = Math.max(1, availability.rimanenti);
                        updatePrice();
                    }
                }
            }
        }
    } catch (e) {
        console.error('Error adding to cart:', e);
        showToast('Errore durante l\'aggiunta al carrello', 'error');
    }

    // Riabilita bottone
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-cart-plus"></i> Aggiungi al Carrello';
}

// Aggiorna prezzo quando cambiano tipo o settore
document.getElementById('idClasse').addEventListener('change', updatePrice);
document.getElementById('idSettore').addEventListener('change', updatePrice);

/**
 * Mostra una notifica toast temporanea
 * @param {string} message - Messaggio da mostrare
 * @param {string} type - Tipo: 'info', 'success', 'error'
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>
