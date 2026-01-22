<?php
/**
 * Pagina Checkout
 *
 * Mostra i biglietti nel carrello come card interattive stile carrello.
 * Ogni biglietto richiede nome e cognome dell'intestatario.
 * Il primo biglietto di ogni evento ha i dati utente di default.
 * I biglietti senza dati sono visualizzati in grigio.
 *
 * Funzionalita:
 * - Eliminazione biglietti (singolo/gruppo/selezionati)
 * - Modifica tipo biglietti
 * - Persistenza dati al refresh
 * - Caricamento carrello da DB per utenti loggati
 */
$userNome = $_SESSION['user_nome'] ?? '';
$userCognome = $_SESSION['user_cognome'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
$isLoggedIn = isLoggedIn();

// Carica tipi biglietto per i modal di modifica
require_once __DIR__ . '/../models/Biglietto.php';
$tipi = getAllTipiBiglietto($pdo);
?>

<div class="checkout-page">
    <div class="page-header">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Continua lo shopping</a>
        <h1><i class="fas fa-shopping-cart"></i> Checkout</h1>
    </div>

    <div class="checkout-container">
        <!-- Lista biglietti -->
        <div class="checkout-tickets-section">
            <div class="section-header">
                <h2><i class="fas fa-ticket-alt"></i> I tuoi biglietti</h2>
                <span class="ticket-counter" id="ticketCounter">0 biglietti</span>
            </div>
            <p class="section-hint"><i class="fas fa-info-circle"></i> Inserisci i dati dell'intestatario per ogni biglietto</p>

            <div id="checkoutTickets" class="checkout-tickets">
                <!-- Popolato via JavaScript -->
            </div>

            <div id="checkoutEmpty" class="no-data-container" style="display: none;">
                <i class="fas fa-shopping-cart"></i>
                <p>Il tuo carrello è vuoto</p>
                <a href="index.php" class="btn btn-primary">Scopri gli eventi</a>
            </div>
        </div>

        <!-- Riepilogo e pagamento -->
        <div class="checkout-sidebar" id="checkoutSidebar">
            <form id="checkoutForm" method="post" action="index.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="acquista">
                <input type="hidden" name="cart_data" id="cartDataInput">

                <!-- Metodo di pagamento -->
                <div class="checkout-section">
                    <h3><i class="fas fa-credit-card"></i> Pagamento</h3>

                    <div class="payment-methods">
                        <label class="payment-method">
                            <input type="radio" name="metodo" value="Carta di credito" checked>
                            <span class="payment-method-content">
                                <i class="fas fa-credit-card"></i>
                                <span>Carta di credito</span>
                            </span>
                        </label>
                        <label class="payment-method">
                            <input type="radio" name="metodo" value="PayPal">
                            <span class="payment-method-content">
                                <i class="fab fa-paypal"></i>
                                <span>PayPal</span>
                            </span>
                        </label>
                        <label class="payment-method">
                            <input type="radio" name="metodo" value="Bonifico">
                            <span class="payment-method-content">
                                <i class="fas fa-university"></i>
                                <span>Bonifico</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Email conferma -->
                <div class="checkout-section">
                    <h3><i class="fas fa-envelope"></i> Email conferma</h3>
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="email" name="email" value="<?= e($userEmail) ?>" required placeholder="Email per la conferma">
                    </div>
                </div>

                <!-- Riepilogo totale -->
                <div class="checkout-summary">
                    <div class="summary-row">
                        <span>Biglietti</span>
                        <span id="ticketCount">0</span>
                    </div>
                    <div class="summary-row">
                        <span>Subtotale</span>
                        <span id="checkoutSubtotal">0,00 &euro;</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Totale</span>
                        <span id="checkoutTotal">0,00 &euro;</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="checkoutSubmit" disabled>
                    <i class="fas fa-lock"></i> Conferma e paga
                </button>

                <div class="checkout-warning" id="checkoutWarning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Compila i dati di tutti i biglietti</span>
                </div>

                <p class="checkout-disclaimer">
                    <i class="fas fa-shield-alt"></i> Pagamento sicuro e protetto
                </p>
            </form>
        </div>
    </div>
</div>

<!-- Modal Azioni Gruppo -->
<div id="actionModal" class="checkout-modal" style="display: none;">
    <div class="checkout-modal-overlay" onclick="closeActionModal()"></div>
    <div class="checkout-modal-content">
        <div class="checkout-modal-header">
            <h3 id="modalTitle">Seleziona opzione</h3>
            <button type="button" class="modal-close" onclick="closeActionModal()">&times;</button>
        </div>
        <div class="checkout-modal-body" id="modalBody">
            <!-- Contenuto dinamico -->
        </div>
    </div>
</div>

<!-- Modal Modifica Tipo e Settore -->
<div id="editModal" class="checkout-modal" style="display: none;">
    <div class="checkout-modal-overlay" onclick="closeEditModal()"></div>
    <div class="checkout-modal-content">
        <div class="checkout-modal-header">
            <h3 id="editModalTitle">Modifica biglietto</h3>
            <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="checkout-modal-body">
            <div class="form-group">
                <label>Tipo Biglietto</label>
                <select id="editTipo" class="form-control">
                    <?php foreach ($tipi as $tipo): ?>
                    <option value="<?= e($tipo['nome']) ?>" data-mod="<?= $tipo['ModificatorePrezzo'] ?>">
                        <?= e($tipo['nome']) ?> (+<?= formatPrice($tipo['ModificatorePrezzo']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Settore</label>
                <select id="editSettore" class="form-control">
                    <option value="">Caricamento settori...</option>
                </select>
                <small class="form-hint" id="settoreHint"></small>
            </div>
            <div class="checkout-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="confirmEdit()">Applica</button>
            </div>
        </div>
    </div>
</div>

<script>
// Dati tipi per i calcoli
const tipiData = <?= json_encode(array_map(fn($t) => ['nome' => $t['nome'], 'mod' => (float)$t['ModificatorePrezzo']], $tipi)) ?>;
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';

document.addEventListener('DOMContentLoaded', async function() {
    const CHECKOUT_STORAGE_KEY = 'em_checkout_data';
    const CART_STORAGE_KEY = 'em_cart';

    // Carica carrello: da server se loggato, altrimenti da localStorage
    let cart = [];
    let serverCart = null;

    if (isLoggedIn) {
        try {
            const response = await fetch('index.php?action=cart_get');
            const data = await response.json();
            serverCart = data.cart || [];
            // Converti formato server a formato checkout
            cart = serverCart.map(item => ({
                eventoId: item.idEvento,
                eventName: item.eventoNome,
                eventDate: item.eventoData,
                tipoId: item.idClasse,
                ticketType: item.idClasse,
                price: item.prezzo,
                prezzoBase: item.prezzoBase,
                image: item.image,
                quantity: 1,
                // Dati biglietto dal DB
                bigliettoId: item.id,
                nome: item.nome || '',
                cognome: item.cognome || '',
                sesso: item.sesso || 'Altro',
                // Info settore
                idSettore: item.idSettore || null,
                fila: item.fila || null,
                postoNumero: item.postoNumero || null,
                moltiplicatoreSettore: item.moltiplicatoreSettore || 1
            }));
        } catch (e) {
            console.error('Errore caricamento carrello:', e);
            cart = [];
        }
    } else {
        cart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || '[]');
    }

    const checkoutTickets = document.getElementById('checkoutTickets');
    const checkoutEmpty = document.getElementById('checkoutEmpty');
    const checkoutSidebar = document.getElementById('checkoutSidebar');
    const cartDataInput = document.getElementById('cartDataInput');
    const checkoutSubtotal = document.getElementById('checkoutSubtotal');
    const checkoutTotal = document.getElementById('checkoutTotal');
    const ticketCount = document.getElementById('ticketCount');
    const ticketCounter = document.getElementById('ticketCounter');
    const checkoutSubmit = document.getElementById('checkoutSubmit');
    const checkoutWarning = document.getElementById('checkoutWarning');

    const defaultNome = '<?= e($userNome) ?>';
    const defaultCognome = '<?= e($userCognome) ?>';

    let tickets = [];
    let currentAction = null;
    let currentGroupKey = null;
    let selectedTicketIndices = [];
    let settoriCache = {}; // Cache settori per evento

    // Carica settori per un evento
    async function loadSettoriForEvento(eventoId) {
        if (settoriCache[eventoId]) {
            return settoriCache[eventoId];
        }
        try {
            const response = await fetch(`index.php?action=get_settori&idEvento=${eventoId}`);
            const data = await response.json();
            settoriCache[eventoId] = data.settori || [];
            return settoriCache[eventoId];
        } catch (e) {
            console.error('Errore caricamento settori:', e);
            return [];
        }
    }

    // Popola il select dei settori
    async function populateSettoriSelect(eventoId, currentSettoreId) {
        const select = document.getElementById('editSettore');
        const hint = document.getElementById('settoreHint');
        select.innerHTML = '<option value="">Caricamento...</option>';

        const settori = await loadSettoriForEvento(eventoId);

        if (settori.length === 0) {
            select.innerHTML = '<option value="">Nessun settore disponibile</option>';
            hint.textContent = '';
            return;
        }

        select.innerHTML = settori.map(s => {
            const selected = s.id == currentSettoreId ? 'selected' : '';
            const label = `Settore ${s.id} (x${s.moltiplicatore.toFixed(2)})`;
            return `<option value="${s.id}" data-mult="${s.moltiplicatore}" ${selected}>${label}</option>`;
        }).join('');

        hint.textContent = 'Il cambio di settore ricalcolerà il prezzo';
    }

    // Aggiorna settore sul server
    async function updateSettoreOnServer(bigliettoId, idSettore) {
        if (!isLoggedIn || !bigliettoId) return false;
        try {
            const formData = new FormData();
            formData.append('idBiglietto', bigliettoId);
            formData.append('idSettore', idSettore);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('index.php?action=cart_update_settore', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            return data.success;
        } catch (e) {
            console.error('Errore aggiornamento settore:', e);
            return false;
        }
    }

    function saveCheckoutData() {
        const data = tickets.map(t => ({
            eventoId: t.eventoId,
            tipoId: t.tipoId,
            ticketIndex: t.ticketIndex,
            nome: t.nome,
            cognome: t.cognome
        }));
        localStorage.setItem(CHECKOUT_STORAGE_KEY, JSON.stringify(data));
    }

    function loadCheckoutData() {
        try {
            return JSON.parse(localStorage.getItem(CHECKOUT_STORAGE_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    async function saveCart() {
        if (!isLoggedIn) {
            localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
        }
        if (typeof window.updateCartCount === 'function') {
            window.updateCartCount();
        }
    }

    // Rimuove biglietto dal server
    async function removeFromServer(bigliettoId) {
        if (!isLoggedIn || !bigliettoId) return false;
        try {
            const formData = new FormData();
            formData.append('idBiglietto', bigliettoId);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('index.php?action=cart_remove', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            return data.success;
        } catch (e) {
            console.error('Errore rimozione dal server:', e);
            return false;
        }
    }

    // Aggiorna dati biglietto sul server
    async function updateTicketOnServer(bigliettoId, nome, cognome, sesso) {
        if (!isLoggedIn || !bigliettoId) return false;
        try {
            const formData = new FormData();
            formData.append('idBiglietto', bigliettoId);
            formData.append('nome', nome);
            formData.append('cognome', cognome);
            formData.append('sesso', sesso || 'Altro');
            formData.append('csrf_token', csrfToken);

            const response = await fetch('index.php?action=cart_update', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            return data.success;
        } catch (e) {
            console.error('Errore aggiornamento sul server:', e);
            return false;
        }
    }

    // Aggiorna tipo biglietto sul server
    async function updateTicketTypeOnServer(bigliettoId, idClasse) {
        if (!isLoggedIn || !bigliettoId) return false;
        try {
            const formData = new FormData();
            formData.append('idBiglietto', bigliettoId);
            formData.append('idClasse', idClasse);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('index.php?action=cart_update', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            return data.success;
        } catch (e) {
            console.error('Errore aggiornamento tipo sul server:', e);
            return false;
        }
    }

    function formatPrice(price) {
        return parseFloat(price).toFixed(2).replace('.', ',') + ' \u20AC';
    }

    function showEmpty() {
        checkoutTickets.style.display = 'none';
        checkoutEmpty.style.display = 'flex';
        checkoutSidebar.style.display = 'none';
        localStorage.removeItem(CHECKOUT_STORAGE_KEY);
    }

    if (cart.length === 0) {
        showEmpty();
        return;
    }

    const savedData = loadCheckoutData();

    function buildTickets() {
        tickets = [];
        let ticketIndex = 0;
        const eventFirstTicket = {};

        if (isLoggedIn) {
            // Per utenti loggati: ogni elemento del carrello e gia un biglietto singolo
            cart.forEach((item, cartIndex) => {
                const isFirst = !eventFirstTicket[item.eventoId];
                if (isFirst) eventFirstTicket[item.eventoId] = true;

                // Usa i dati salvati nel DB, oppure default per il primo biglietto
                const nome = item.nome || (isFirst ? defaultNome : '');
                const cognome = item.cognome || (isFirst ? defaultCognome : '');

                tickets.push({
                    ...item,
                    ticketIndex: ticketIndex,
                    cartIndex: cartIndex,
                    subIndex: 0,
                    nome: nome,
                    cognome: cognome,
                    isComplete: !!(nome && cognome)
                });
                ticketIndex++;
            });
        } else {
            // Per utenti non loggati: usa localStorage
            cart.forEach((item, cartIndex) => {
                const qty = item.quantity || 1;
                for (let i = 0; i < qty; i++) {
                    const isFirst = !eventFirstTicket[item.eventoId];
                    if (isFirst) eventFirstTicket[item.eventoId] = true;

                    const saved = savedData.find(s =>
                        s.eventoId === item.eventoId &&
                        s.tipoId === item.tipoId &&
                        s.ticketIndex === ticketIndex
                    );

                    const nome = saved ? saved.nome : (isFirst ? defaultNome : '');
                    const cognome = saved ? saved.cognome : (isFirst ? defaultCognome : '');

                    tickets.push({
                        ...item,
                        ticketIndex: ticketIndex,
                        cartIndex: cartIndex,
                        subIndex: i,
                        nome: nome,
                        cognome: cognome,
                        isComplete: !!(nome && cognome)
                    });
                    ticketIndex++;
                }
            });
        }
    }

    buildTickets();

    function getGroupedTickets() {
        const grouped = {};
        tickets.forEach((ticket, idx) => {
            const key = `${ticket.eventoId}-${ticket.tipoId}`;
            if (!grouped[key]) {
                grouped[key] = {
                    eventName: ticket.eventName,
                    eventDate: ticket.eventDate,
                    image: ticket.image,
                    eventoId: ticket.eventoId,
                    tipoId: ticket.tipoId,
                    ticketType: ticket.ticketType,
                    price: ticket.price,
                    prezzoBase: ticket.prezzoBase || ticket.price,
                    tickets: []
                };
            }
            grouped[key].tickets.push({ ...ticket, globalIndex: idx });
        });
        return grouped;
    }

    function renderTickets() {
        if (tickets.length === 0) {
            showEmpty();
            return;
        }

        const groupedByEvent = getGroupedTickets();

        let html = '';
        Object.entries(groupedByEvent).forEach(([key, group]) => {
            const groupTickets = group.tickets;
            const completeInGroup = groupTickets.filter(t => t.isComplete).length;
            const allComplete = completeInGroup === groupTickets.length;
            const isMultiple = groupTickets.length > 1;

            if (isMultiple) {
                html += `
                <div class="checkout-group ${allComplete ? 'complete' : ''}" data-group-key="${key}">
                    <div class="checkout-group-header">
                        <div class="checkout-group-image">
                            <img src="${group.image || 'img/placeholder.jpg'}" alt="${group.eventName}"
                                 onerror="this.src='https://picsum.photos/80/80?random=${group.eventoId}'">
                        </div>
                        <div class="checkout-group-info">
                            <h3>${group.eventName}</h3>
                            <p><i class="fas fa-calendar"></i> ${group.eventDate}</p>
                            <p><i class="fas fa-tag"></i> ${group.ticketType}</p>
                        </div>
                        <div class="checkout-group-actions">
                            <button type="button" class="group-action-btn" onclick="openGroupAction('edit', '${key}')" title="Modifica tipo">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="group-action-btn danger" onclick="openGroupAction('delete', '${key}')" title="Elimina">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="checkout-group-counter">
                            <span class="group-badge ${allComplete ? 'complete' : ''}">${completeInGroup}/${groupTickets.length}</span>
                        </div>
                    </div>
                    <div class="checkout-group-tickets">
                `;
                groupTickets.forEach((ticket, i) => {
                    html += renderTicketCard(ticket, i + 1, true);
                });
                html += `
                    </div>
                </div>`;
            } else {
                html += renderTicketCard(groupTickets[0], null, false);
            }
        });

        checkoutTickets.innerHTML = html;

        document.querySelectorAll('.checkout-input').forEach(input => {
            input.addEventListener('input', handleInputChange);
            input.addEventListener('focus', handleInputFocus);
            input.addEventListener('blur', handleInputBlur);
        });

        updateTotals();
        updateFormState();
    }

    function renderTicketCard(ticket, number, isGrouped) {
        const idx = ticket.globalIndex;

        return `
        <div class="checkout-card ${ticket.isComplete ? 'complete' : 'incomplete'} ${isGrouped ? 'grouped' : ''}" data-index="${idx}">
            <div class="checkout-card-status">
                ${ticket.isComplete
                    ? '<span class="status-badge complete"><i class="fas fa-check"></i></span>'
                    : '<span class="status-badge incomplete"><i class="fas fa-user-edit"></i></span>'
                }
            </div>
            ${!isGrouped ? `
            <div class="checkout-card-image">
                <img src="${ticket.image || 'img/placeholder.jpg'}" alt="${ticket.eventName}"
                     onerror="this.src='https://picsum.photos/100/130?random=${ticket.eventoId + idx}'">
            </div>` : ''}
            <div class="checkout-card-body">
                ${isGrouped ? `<div class="ticket-number">Biglietto #${number}</div>` : `
                <div class="checkout-card-info">
                    <h4>${ticket.eventName}</h4>
                    <p class="checkout-card-type"><i class="fas fa-tag"></i> ${ticket.ticketType}</p>
                    <p class="checkout-card-date"><i class="fas fa-calendar"></i> ${ticket.eventDate}</p>
                </div>`}
                <div class="checkout-card-form">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text"
                               class="checkout-input"
                               placeholder="Nome intestatario"
                               value="${ticket.nome}"
                               data-field="nome"
                               data-index="${idx}">
                    </div>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text"
                               class="checkout-input"
                               placeholder="Cognome intestatario"
                               value="${ticket.cognome}"
                               data-field="cognome"
                               data-index="${idx}">
                    </div>
                </div>
            </div>
            ${!isGrouped ? `
            <div class="checkout-card-actions">
                <button type="button" class="card-action-btn" onclick="openSingleEdit(${idx})" title="Modifica tipo">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="card-action-btn danger" onclick="deleteSingleTicket(${idx})" title="Elimina">
                    <i class="fas fa-trash"></i>
                </button>
            </div>` : `
            <div class="checkout-card-actions grouped">
                <button type="button" class="card-action-btn mini danger" onclick="deleteSingleFromGroup(${idx})" title="Rimuovi">
                    <i class="fas fa-times"></i>
                </button>
            </div>`}
            <div class="checkout-card-price">
                <span class="price-value">${formatPrice(ticket.price)}</span>
            </div>
        </div>`;
    }

    // Debounce per salvare sul server
    let saveTimeout = null;

    function handleInputChange(e) {
        const idx = parseInt(e.target.dataset.index);
        const field = e.target.dataset.field;
        tickets[idx][field] = e.target.value.trim();
        tickets[idx].isComplete = tickets[idx].nome && tickets[idx].cognome;

        const card = e.target.closest('.checkout-card');
        card.classList.toggle('complete', tickets[idx].isComplete);
        card.classList.toggle('incomplete', !tickets[idx].isComplete);

        const statusBadge = card.querySelector('.status-badge');
        if (tickets[idx].isComplete) {
            statusBadge.className = 'status-badge complete';
            statusBadge.innerHTML = '<i class="fas fa-check"></i>';
        } else {
            statusBadge.className = 'status-badge incomplete';
            statusBadge.innerHTML = '<i class="fas fa-user-edit"></i>';
        }

        saveCheckoutData();
        updateFormState();
        updateGroupBadges();

        // Per utenti loggati, salva sul server con debounce
        if (isLoggedIn && tickets[idx].bigliettoId) {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                updateTicketOnServer(
                    tickets[idx].bigliettoId,
                    tickets[idx].nome,
                    tickets[idx].cognome,
                    tickets[idx].sesso || 'Altro'
                );
            }, 500);
        }
    }

    function updateGroupBadges() {
        const grouped = getGroupedTickets();
        document.querySelectorAll('.checkout-group').forEach(groupEl => {
            const key = groupEl.dataset.groupKey;
            if (grouped[key]) {
                const g = grouped[key];
                const complete = g.tickets.filter(t => tickets[t.globalIndex].isComplete).length;
                const badge = groupEl.querySelector('.group-badge');
                if (badge) {
                    badge.textContent = `${complete}/${g.tickets.length}`;
                    badge.classList.toggle('complete', complete === g.tickets.length);
                }
                groupEl.classList.toggle('complete', complete === g.tickets.length);
            }
        });
    }

    function handleInputFocus(e) {
        e.target.closest('.checkout-card').classList.add('focused');
    }

    function handleInputBlur(e) {
        e.target.closest('.checkout-card').classList.remove('focused');
    }

    function updateFormState() {
        const allComplete = tickets.every(t => t.isComplete);
        const completeCount = tickets.filter(t => t.isComplete).length;

        checkoutSubmit.disabled = !allComplete;
        checkoutWarning.style.display = allComplete ? 'none' : 'flex';

        ticketCounter.innerHTML = `<span class="complete-count">${completeCount}</span> / ${tickets.length} completati`;
        cartDataInput.value = JSON.stringify(tickets);
    }

    function updateTotals() {
        let total = 0;
        tickets.forEach(t => total += t.price || 0);

        ticketCount.textContent = tickets.length;
        checkoutSubtotal.textContent = formatPrice(total);
        checkoutTotal.textContent = formatPrice(total);
    }

    async function rebuildAndRender() {
        if (isLoggedIn) {
            try {
                const response = await fetch('index.php?action=cart_get');
                const data = await response.json();
                const serverCart = data.cart || [];
                cart = serverCart.map(item => ({
                    eventoId: item.idEvento,
                    eventName: item.eventoNome,
                    eventDate: item.eventoData,
                    tipoId: item.idClasse,
                    ticketType: item.idClasse,
                    price: item.prezzo,
                    prezzoBase: item.prezzoBase,
                    image: item.image,
                    quantity: 1,
                    bigliettoId: item.id,
                    nome: item.nome || '',
                    cognome: item.cognome || '',
                    sesso: item.sesso || 'Altro',
                    idSettore: item.idSettore || null,
                    moltiplicatoreSettore: item.moltiplicatoreSettore || 1
                }));
            } catch (e) {
                console.error('Errore ricaricamento carrello:', e);
            }
        } else {
            cart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || '[]');
        }
        buildTickets();
        renderTickets();
    }

    // === AZIONI SINGOLO BIGLIETTO ===

    window.deleteSingleTicket = async function(idx) {
        const ticket = tickets[idx];
        if (!confirm(`Eliminare il biglietto per "${ticket.eventName}"?`)) return;
        await removeTicketByIndex(idx);
    };

    window.deleteSingleFromGroup = async function(idx) {
        await removeTicketByIndex(idx);
    };

    window.openSingleEdit = async function(idx) {
        selectedTicketIndices = [idx];
        currentGroupKey = null;
        const ticket = tickets[idx];
        document.getElementById('editTipo').value = ticket.ticketType;
        document.getElementById('editModalTitle').textContent = 'Modifica biglietto';
        document.getElementById('editModal').style.display = 'flex';

        // Carica settori per l'evento
        await populateSettoriSelect(ticket.eventoId, ticket.idSettore);
    };

    async function removeTicketByIndex(idx) {
        const ticket = tickets[idx];

        if (isLoggedIn && ticket.bigliettoId) {
            // Rimuovi dal server
            const success = await removeFromServer(ticket.bigliettoId);
            if (success) {
                // Rimuovi dall'array locale
                cart.splice(ticket.cartIndex, 1);
            }
        } else {
            // Comportamento localStorage
            const cartItem = cart[ticket.cartIndex];
            if (cartItem) {
                if (cartItem.quantity && cartItem.quantity > 1) {
                    cartItem.quantity--;
                } else {
                    cart.splice(ticket.cartIndex, 1);
                }
                saveCart();
            }
        }

        rebuildAndRender();
    }

    // === AZIONI GRUPPO ===

    window.openGroupAction = function(action, groupKey) {
        currentAction = action;
        currentGroupKey = groupKey;

        const grouped = getGroupedTickets();
        const group = grouped[groupKey];
        if (!group) return;

        const count = group.tickets.length;
        const actionText = action === 'delete' ? 'eliminare' : 'modificare';
        const title = action === 'delete' ? 'Elimina biglietti' : 'Modifica biglietti';

        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalBody').innerHTML = `
            <p class="modal-desc">Vuoi ${actionText} i biglietti per "<strong>${group.eventName}</strong>"?</p>
            <div class="modal-options">
                <button type="button" class="modal-option-btn" onclick="applyToSingle()">
                    <i class="fas fa-user"></i>
                    <span>Solo uno</span>
                    <small>Seleziona quale</small>
                </button>
                <button type="button" class="modal-option-btn primary" onclick="applyToAll()">
                    <i class="fas fa-users"></i>
                    <span>Tutti (${count})</span>
                    <small>Applica a tutti</small>
                </button>
                <button type="button" class="modal-option-btn" onclick="applyToSelected()">
                    <i class="fas fa-check-square"></i>
                    <span>Seleziona</span>
                    <small>Scegli quali</small>
                </button>
            </div>
        `;
        document.getElementById('actionModal').style.display = 'flex';
    };

    window.closeActionModal = function() {
        document.getElementById('actionModal').style.display = 'none';
    };

    window.closeEditModal = function() {
        document.getElementById('editModal').style.display = 'none';
    };

    window.applyToAll = function() {
        const grouped = getGroupedTickets();
        const group = grouped[currentGroupKey];
        selectedTicketIndices = group.tickets.map(t => t.globalIndex);
        closeActionModal();
        executeCurrentAction();
    };

    window.applyToSingle = function() {
        const grouped = getGroupedTickets();
        const group = grouped[currentGroupKey];

        document.getElementById('modalBody').innerHTML = `
            <p class="modal-desc">Seleziona il biglietto:</p>
            <div class="ticket-select-list">
                ${group.tickets.map((t, i) => `
                    <label class="ticket-select-item">
                        <input type="radio" name="selectTicket" value="${t.globalIndex}">
                        <span class="ticket-select-label">
                            <strong>Biglietto #${i + 1}</strong>
                            ${t.nome ? `<span class="ticket-select-name">${t.nome} ${t.cognome}</span>` : '<span class="ticket-select-empty">Non compilato</span>'}
                        </span>
                    </label>
                `).join('')}
            </div>
            <div class="checkout-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeActionModal()">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="confirmSingleSelect()">Conferma</button>
            </div>
        `;
    };

    window.confirmSingleSelect = function() {
        const selected = document.querySelector('input[name="selectTicket"]:checked');
        if (!selected) {
            alert('Seleziona un biglietto');
            return;
        }
        selectedTicketIndices = [parseInt(selected.value)];
        closeActionModal();
        executeCurrentAction();
    };

    window.applyToSelected = function() {
        const grouped = getGroupedTickets();
        const group = grouped[currentGroupKey];

        document.getElementById('modalBody').innerHTML = `
            <p class="modal-desc">Seleziona i biglietti:</p>
            <div class="ticket-select-list">
                ${group.tickets.map((t, i) => `
                    <label class="ticket-select-item">
                        <input type="checkbox" name="selectTickets" value="${t.globalIndex}">
                        <span class="ticket-select-label">
                            <strong>Biglietto #${i + 1}</strong>
                            ${t.nome ? `<span class="ticket-select-name">${t.nome} ${t.cognome}</span>` : '<span class="ticket-select-empty">Non compilato</span>'}
                        </span>
                    </label>
                `).join('')}
            </div>
            <div class="checkout-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeActionModal()">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="confirmMultiSelect()">Conferma</button>
            </div>
        `;
    };

    window.confirmMultiSelect = function() {
        const selected = document.querySelectorAll('input[name="selectTickets"]:checked');
        if (selected.length === 0) {
            alert('Seleziona almeno un biglietto');
            return;
        }
        selectedTicketIndices = Array.from(selected).map(el => parseInt(el.value));
        closeActionModal();
        executeCurrentAction();
    };

    function executeCurrentAction() {
        if (currentAction === 'delete') {
            executeDelete();
        } else if (currentAction === 'edit') {
            openEditForSelected();
        }
    }

    async function executeDelete() {
        // Ordina decrescente per non invalidare indici durante rimozione
        const sortedIndices = [...selectedTicketIndices].sort((a, b) => b - a);

        if (isLoggedIn) {
            // Rimuovi dal server
            for (const idx of sortedIndices) {
                const ticket = tickets[idx];
                if (ticket && ticket.bigliettoId) {
                    await removeFromServer(ticket.bigliettoId);
                }
            }
            // Ricarica carrello dal server
            try {
                const response = await fetch('index.php?action=cart_get');
                const data = await response.json();
                const serverCart = data.cart || [];
                cart = serverCart.map(item => ({
                    eventoId: item.idEvento,
                    eventName: item.eventoNome,
                    eventDate: item.eventoData,
                    tipoId: item.idClasse,
                    ticketType: item.idClasse,
                    price: item.prezzo,
                    prezzoBase: item.prezzoBase,
                    image: item.image,
                    quantity: 1,
                    bigliettoId: item.id,
                    nome: item.nome || '',
                    cognome: item.cognome || '',
                    sesso: item.sesso || 'Altro',
                    idSettore: item.idSettore || null,
                    moltiplicatoreSettore: item.moltiplicatoreSettore || 1
                }));
            } catch (e) {
                console.error('Errore ricaricamento carrello:', e);
            }
        } else {
            sortedIndices.forEach(idx => {
                const ticket = tickets[idx];
                if (ticket && cart[ticket.cartIndex]) {
                    const cartItem = cart[ticket.cartIndex];
                    if (cartItem.quantity && cartItem.quantity > 1) {
                        cartItem.quantity--;
                    } else {
                        cart.splice(ticket.cartIndex, 1);
                    }
                }
            });
            saveCart();
        }

        rebuildAndRender();
    }

    async function openEditForSelected() {
        if (selectedTicketIndices.length === 0) return;
        const ticket = tickets[selectedTicketIndices[0]];
        document.getElementById('editTipo').value = ticket.ticketType;
        document.getElementById('editModalTitle').textContent =
            selectedTicketIndices.length > 1 ? `Modifica ${selectedTicketIndices.length} biglietti` : 'Modifica biglietto';
        document.getElementById('editModal').style.display = 'flex';

        // Carica settori per l'evento
        await populateSettoriSelect(ticket.eventoId, ticket.idSettore);
    }

    window.confirmEdit = async function() {
        const newTipo = document.getElementById('editTipo').value;
        const tipoInfo = tipiData.find(t => t.nome === newTipo);
        const settoreSelect = document.getElementById('editSettore');
        const newSettoreId = settoreSelect.value ? parseInt(settoreSelect.value) : null;
        const newSettoreMult = settoreSelect.selectedOptions[0]?.dataset.mult ? parseFloat(settoreSelect.selectedOptions[0].dataset.mult) : 1;

        for (const idx of selectedTicketIndices) {
            const ticket = tickets[idx];
            if (!ticket) continue;

            // Salva vecchio settore per confronto
            const oldSettoreId = ticket.idSettore;

            // Aggiorna sul server se loggato
            if (isLoggedIn && ticket.bigliettoId) {
                // Aggiorna tipo biglietto
                await updateTicketTypeOnServer(ticket.bigliettoId, newTipo);
                // Aggiorna settore se selezionato e diverso
                if (newSettoreId && newSettoreId !== oldSettoreId) {
                    await updateSettoreOnServer(ticket.bigliettoId, newSettoreId);
                }
            } else {
                // Per utenti non loggati, calcola prezzo localmente
                const prezzoBase = ticket.prezzoBase || 0;
                const modificatoreTipo = tipoInfo?.mod || 0;
                const newPrice = (prezzoBase + modificatoreTipo) * newSettoreMult;

                ticket.ticketType = newTipo;
                ticket.tipoId = newTipo;
                ticket.price = newPrice;
                ticket.idSettore = newSettoreId;
                ticket.moltiplicatoreSettore = newSettoreMult;

                const cartItem = cart[ticket.cartIndex];
                if (cartItem) {
                    cartItem.ticketType = newTipo;
                    cartItem.tipoId = newTipo;
                    cartItem.price = newPrice;
                    cartItem.idSettore = newSettoreId;
                    cartItem.moltiplicatoreSettore = newSettoreMult;
                }
            }
        }

        saveCart();
        closeEditModal();
        rebuildAndRender();
    };

    // Init
    renderTickets();

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        if (checkoutSubmit.disabled) {
            e.preventDefault();
            return false;
        }
        // Per utenti loggati, i dati biglietti sono gia nel DB
        // Aggiungi gli ID dei biglietti al form
        if (isLoggedIn) {
            const bigliettiIds = tickets.map(t => t.bigliettoId).filter(id => id);
            cartDataInput.value = JSON.stringify({
                fromServer: true,
                bigliettiIds: bigliettiIds,
                tickets: tickets.map(t => ({
                    bigliettoId: t.bigliettoId,
                    nome: t.nome,
                    cognome: t.cognome,
                    sesso: t.sesso || 'Altro'
                }))
            });
        }
        localStorage.removeItem(CHECKOUT_STORAGE_KEY);
        localStorage.removeItem(CART_STORAGE_KEY);
    });
});
</script>
