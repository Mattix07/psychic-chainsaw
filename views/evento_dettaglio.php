<?php
/**
 * Dettaglio singolo evento
 * Layout moderno con hero image, aggiunta al carrello semplificata
 */
require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/../models/Biglietto.php';
require_once __DIR__ . '/../models/Evento.php';

$evento = $_SESSION['evento_corrente'] ?? null;
$intrattenitori = $_SESSION['intrattenitori_evento'] ?? [];
$recensioni = $_SESSION['recensioni_evento'] ?? [];
$mediaVoti = $_SESSION['media_voti'] ?? null;

if (!$evento) {
    echo '<div class="no-data-container"><p class="no-data">Evento non trovato.</p></div>';
    return;
}

$settori = getSettoriByLocation($pdo, $evento['idLocation']);
$tipi = getAllTipiBiglietto($pdo);

// Carica eventi correlati della stessa manifestazione
$eventiCorrelati = [];
if (!empty($evento['idManifestazione'])) {
    $eventiCorrelati = getEventiByManifestazione($pdo, $evento['idManifestazione']);
    // Rimuovi l'evento corrente dalla lista
    $eventiCorrelati = array_filter($eventiCorrelati, fn($e) => $e['id'] !== $evento['id']);
}
?>

<!-- HERO SECTION -->
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

<!-- CONTENUTO PRINCIPALE -->
<div class="evento-content">
    <div class="evento-main">
        <!-- Info Evento -->
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

        <!-- Intrattenitori -->
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

        <!-- Recensioni -->
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

    <!-- SIDEBAR - Acquisto Biglietti -->
    <aside class="evento-sidebar">
        <div class="ticket-selector">
            <h3><i class="fas fa-ticket-alt"></i> Acquista Biglietti</h3>

            <div class="ticket-price-display">
                <span class="label">A partire da</span>
                <span class="price"><?= formatPrice($evento['PrezzoNoMod']) ?></span>
            </div>

            <form id="add-to-cart-form" class="ticket-form">
                <input type="hidden" name="idEvento" value="<?= $evento['id'] ?>">
                <input type="hidden" name="eventoNome" value="<?= e($evento['Nome']) ?>">
                <input type="hidden" name="eventoData" value="<?= $evento['Data'] ?>">
                <input type="hidden" name="prezzoBase" value="<?= $evento['PrezzoNoMod'] ?>">

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
                        <input type="number" id="quantita" name="quantita" value="1" min="1" max="10" readonly>
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                </div>

                <div class="ticket-total">
                    <span class="label">Totale</span>
                    <span class="total-price" id="totalPrice"><?= formatPrice($evento['PrezzoNoMod']) ?></span>
                </div>

                <button type="button" class="btn btn-primary btn-block btn-large" onclick="addEventToCart()">
                    <i class="fas fa-cart-plus"></i> Aggiungi al Carrello
                </button>
            </form>

            <p class="ticket-note">
                <i class="fas fa-info-circle"></i>
                I dati del partecipante verranno richiesti al momento del checkout.
            </p>
        </div>
    </aside>
</div>

<!-- EVENTI CORRELATI -->
<?php if (!empty($eventiCorrelati)): ?>
<section class="related-events">
    <div class="row-header">
        <h2><i class="fas fa-calendar-alt"></i> Altri eventi di <?= e($evento['ManifestazioneName']) ?></h2>
    </div>
    <div class="carousel-wrapper">
        <button class="carousel-btn prev" onclick="scrollCarousel(this, -1)">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="carousel-row">
            <?php foreach ($eventiCorrelati as $e): ?>
            <article class="event-card" onclick="window.location='index.php?action=view_evento&id=<?= $e['id'] ?>'">
                <div class="card-poster">
                    <img src="<?= e($e['Immagine'] ?? 'public/img/placeholder.jpg') ?>" alt="<?= e($e['Nome']) ?>">
                    <div class="card-overlay">
                        <span class="card-date"><?= formatDate($e['Data']) ?></span>
                        <div class="card-actions">
                            <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); window.location='index.php?action=view_evento&id=<?= $e['id'] ?>'">
                                <i class="fas fa-eye"></i> Dettagli
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-info">
                    <h3><?= e($e['Nome']) ?></h3>
                    <p class="card-location"><i class="fas fa-map-marker-alt"></i> <?= e($e['LocationName']) ?></p>
                    <p class="card-price"><?= formatPrice($e['PrezzoNoMod']) ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <button class="carousel-btn next" onclick="scrollCarousel(this, 1)">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</section>
<?php endif; ?>

<script>
// Calcolo prezzo dinamico
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

function changeQty(delta) {
    const input = document.getElementById('quantita');
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(10, val));
    input.value = val;
    updatePrice();
}

function addEventToCart() {
    const form = document.getElementById('add-to-cart-form');
    const formData = new FormData(form);

    const tipoSelect = document.getElementById('idClasse');
    const settoreSelect = document.getElementById('idSettore');
    const quantita = parseInt(document.getElementById('quantita').value);

    const prezzoBase = parseFloat(formData.get('prezzoBase'));
    const modPrezzo = parseFloat(tipoSelect.options[tipoSelect.selectedIndex].dataset.mod) || 0;
    const multPrezzo = parseFloat(settoreSelect.options[settoreSelect.selectedIndex].dataset.mult) || 1;
    const prezzoUnitario = (prezzoBase + modPrezzo) * multPrezzo;

    // Aggiungi n biglietti al carrello
    for (let i = 0; i < quantita; i++) {
        const item = {
            id: Date.now() + '_' + i,
            idEvento: formData.get('idEvento'),
            eventoNome: formData.get('eventoNome'),
            eventoData: formData.get('eventoData'),
            idClasse: formData.get('idClasse'),
            idSettore: formData.get('idSettore'),
            settoreNome: settoreSelect.options[settoreSelect.selectedIndex].text,
            prezzo: prezzoUnitario,
            // Dati partecipante da compilare al checkout
            nome: '',
            cognome: '',
            sesso: ''
        };

        if (typeof window.addToCart === 'function') {
            window.addToCart(item);
        } else {
            // Fallback se la funzione non è disponibile
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            cart.push(item);
            localStorage.setItem('cart', JSON.stringify(cart));
        }
    }

    // Mostra feedback
    showToast(quantita + ' bigliett' + (quantita > 1 ? 'i aggiunti' : 'o aggiunto') + ' al carrello!', 'success');

    // Aggiorna counter carrello
    if (typeof window.updateCartCount === 'function') {
        window.updateCartCount();
    }
}

// Inizializza listeners
document.getElementById('idClasse').addEventListener('change', updatePrice);
document.getElementById('idSettore').addEventListener('change', updatePrice);

// Toast notification
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
