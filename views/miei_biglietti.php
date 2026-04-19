<?php
/**
 * I Miei Biglietti - Biglietti acquistati
 *
 * Mostra tutti i biglietti acquistati dall'utente, divisi tra:
 * - Eventi futuri (ancora da venire)
 * - Eventi passati (storico)
 */
require_once __DIR__ . '/../models/Biglietto.php';

// Recupera biglietti futuri e passati
$biglietti = getBigliettiUtenteFuturi($pdo, $_SESSION['user_id']);
$bigliettiPassati = getBigliettiUtentePassati($pdo, $_SESSION['user_id']);
?>

<!-- QR Code Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<style>
/* Modal biglietto */
.ticket-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    padding: 1rem;
}

.ticket-modal-overlay.active {
    display: flex;
}

.ticket-modal {
    background: #fff;
    border-radius: 16px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.ticket-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #f3f4f6;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: #6b7280;
    transition: all 0.2s;
    z-index: 10;
}

.ticket-modal-close:hover {
    background: #e5e7eb;
    color: #1f2937;
}

/* Biglietto stampabile */
.printable-ticket {
    padding: 2rem;
}

.ticket-event-image {
    width: 100%;
    max-height: 200px;
    overflow: hidden;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.ticket-event-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
}

.ticket-event-header {
    text-align: center;
    padding-bottom: 1.5rem;
    border-bottom: 2px dashed #e5e7eb;
    margin-bottom: 1.5rem;
}

.ticket-event-header h2 {
    font-size: 1.5rem;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.ticket-event-header .event-date {
    color: var(--primary);
    font-weight: 600;
    font-size: 1.1rem;
}

.ticket-event-header .event-location {
    color: #6b7280;
    font-size: 0.95rem;
    margin-top: 0.25rem;
}

.ticket-qr-container {
    display: flex;
    justify-content: center;
    margin: 1.5rem 0;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
}

.ticket-qr-container canvas,
.ticket-qr-container img {
    border-radius: 8px;
}

.ticket-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.ticket-info-item {
    background: #f9fafb;
    padding: 0.75rem 1rem;
    border-radius: 8px;
}

.ticket-info-item label {
    display: block;
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.ticket-info-item span {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.95rem;
}

.ticket-info-item.full-width {
    grid-column: 1 / -1;
}

.ticket-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.875rem;
}

.ticket-status.valid {
    background: #d1fae5;
    color: #065f46;
}

.ticket-status.used {
    background: #fee2e2;
    color: #991b1b;
}

.ticket-status.expired {
    background: #f3f4f6;
    color: #6b7280;
}

.ticket-footer-info {
    text-align: center;
    padding-top: 1.5rem;
    border-top: 2px dashed #e5e7eb;
    margin-top: 1rem;
}

.ticket-id-display {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    color: #6b7280;
    background: #f3f4f6;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    display: inline-block;
}

/* Pulsante stampa */
.ticket-print-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 1rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    margin-top: 1.5rem;
    transition: all 0.2s;
}

.ticket-print-btn:hover {
    background: var(--primary-dark, #4338ca);
    transform: translateY(-1px);
}

/* Card cliccabile */
.ticket-card {
    cursor: pointer;
    transition: all 0.2s;
}

.ticket-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
}

.ticket-card:active {
    transform: translateY(-2px);
}

/* Stili per stampa — non usati direttamente (si stampa via finestra separata) */
</style>

<div class="profile-page">
    <div class="page-header">
        <h1><i class="fas fa-ticket-alt"></i> I Miei Biglietti</h1>
        <p class="subtitle">Clicca su un biglietto per visualizzarlo e stamparlo</p>
    </div>

    <?php if (empty($biglietti) && empty($bigliettiPassati)): ?>
        <div class="no-data-container">
            <i class="fas fa-ticket-alt"></i>
            <p>Non hai ancora acquistato biglietti.</p>
            <a href="index.php?action=list_eventi" class="btn btn-primary">Esplora gli eventi</a>
        </div>
    <?php else: ?>

        <?php if (!empty($biglietti)): ?>
        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Eventi futuri</h2>
        <div class="tickets-grid">
            <?php foreach ($biglietti as $b): ?>
                <div class="ticket-card" onclick="openTicketModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>)">
                    <div class="ticket-header">
                        <span class="ticket-type"><?= e($b['idClasse']) ?></span>
                        <?php if (!empty($b['idSettore'])): ?>
                        <span class="ticket-sector">Settore <?= $b['idSettore'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ticket-body">
                        <h3><?= e($b['EventoNome']) ?></h3>
                        <div class="ticket-details">
                            <p><i class="fas fa-user"></i> <?= e($b['Nome'] . ' ' . $b['Cognome']) ?></p>
                            <p><i class="fas fa-calendar"></i> <?= formatDate($b['Data']) ?></p>
                            <p><i class="fas fa-clock"></i> <?= formatTime($b['OraI']) ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?= e($b['LocationName']) ?></p>
                        </div>
                    </div>
                    <div class="ticket-footer">
                        <span class="ticket-id">ID: <?= $b['id'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php elseif (empty($biglietti) && !empty($bigliettiPassati)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Non hai biglietti per eventi futuri.
        </div>
        <?php endif; ?>

        <?php if (!empty($bigliettiPassati)): ?>
        <h2 class="section-title" style="margin-top: 2rem;"><i class="fas fa-history"></i> Eventi passati</h2>
        <div class="tickets-grid">
            <?php foreach ($bigliettiPassati as $b): ?>
                <div class="ticket-card past" onclick="openTicketModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>)">
                    <div class="ticket-header">
                        <span class="ticket-type"><?= e($b['idClasse']) ?></span>
                        <?php if ($b['Stato'] === 'validato'): ?>
                            <span class="ticket-sector" style="background:#d1fae5;color:#065f46;">Utilizzato</span>
                        <?php else: ?>
                            <span class="ticket-sector" style="background:#f3f4f6;color:#6b7280;">Non usufruito</span>
                        <?php endif; ?>
                    </div>
                    <div class="ticket-body">
                        <h3><?= e($b['EventoNome']) ?></h3>
                        <div class="ticket-details">
                            <p><i class="fas fa-user"></i> <?= e($b['Nome'] . ' ' . $b['Cognome']) ?></p>
                            <p><i class="fas fa-calendar"></i> <?= formatDate($b['Data']) ?></p>
                            <p><i class="fas fa-clock"></i> <?= formatTime($b['OraI']) ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?= e($b['LocationName']) ?></p>
                        </div>
                    </div>
                    <div class="ticket-footer">
                        <span class="ticket-id">ID: <?= $b['id'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Modal Biglietto -->
<div class="ticket-modal-overlay" id="ticketModal">
    <div class="ticket-modal">
        <button class="ticket-modal-close" onclick="closeTicketModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="printable-ticket" id="printableTicket">
            <!-- Foto evento -->
            <div class="ticket-event-image">
                <img id="modalEventImage" src="" alt="Evento">
            </div>

            <div class="ticket-event-header">
                <h2 id="modalEventName"></h2>
                <div class="event-date" id="modalEventDate"></div>
                <div class="event-location" id="modalEventLocation"></div>
            </div>

            <div class="ticket-qr-container">
                <div id="qrcodeContainer"></div>
            </div>

            <div class="ticket-info-grid">
                <div class="ticket-info-item full-width">
                    <label>Intestatario</label>
                    <span id="modalHolder"></span>
                </div>
                <div class="ticket-info-item">
                    <label>Tipologia</label>
                    <span id="modalType"></span>
                </div>
                <div class="ticket-info-item">
                    <label>Stato</label>
                    <span id="modalStatus"></span>
                </div>
                <div class="ticket-info-item" id="sectorInfo" style="display: none;">
                    <label>Settore</label>
                    <span id="modalSector"></span>
                </div>
                <div class="ticket-info-item" id="seatInfo" style="display: none;">
                    <label>Posto</label>
                    <span id="modalSeat"></span>
                </div>
            </div>

            <div class="ticket-footer-info">
                <div class="ticket-id-display" id="modalTicketId"></div>
            </div>
        </div>

        <div style="padding: 0 2rem 2rem;">
            <button class="ticket-print-btn" onclick="printTicket()">
                <i class="fas fa-print"></i> Stampa Biglietto (PDF)
            </button>
        </div>

        <!-- F11: Documento identità -->
        <div id="documentoSection" style="padding: 0 2rem 2rem; display:none;">
            <hr style="margin-bottom:1rem;">
            <h4 style="margin-bottom:.75rem;"><i class="fas fa-id-card"></i> Documento d'identità</h4>
            <div id="documentoPreview" style="margin-bottom:.75rem; display:none;">
                <img id="documentoImg" src="" alt="Documento" style="max-width:100%; border-radius:8px; border:1px solid var(--border-color);">
            </div>
            <form id="documentoForm" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="idBiglietto" id="docBigliettoId">
                <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.5rem;">
                    <select name="tipo" id="docTipo" class="form-control" style="flex:1; min-width:140px;">
                        <option value="ci">Carta d'identità</option>
                        <option value="passaporto">Passaporto</option>
                        <option value="patente">Patente</option>
                    </select>
                    <label class="btn btn-secondary" style="cursor:pointer; margin:0;">
                        <i class="fas fa-upload"></i> Scegli file
                        <input type="file" name="documento" id="docFile" accept="image/jpeg,image/png" style="display:none;" onchange="previewDocumento(this)">
                    </label>
                </div>
                <button type="button" id="docUploadBtn" class="btn btn-primary btn-sm" onclick="uploadDocumento()" style="display:none;">
                    <i class="fas fa-save"></i> Salva documento
                </button>
                <small class="form-hint">Max 5MB, JPG/PNG. Visibile solo a te e allo staff.</small>
            </form>
        </div>
    </div>
</div>

<script>
let currentQRCode = null;

function openTicketModal(ticket) {
    const modal = document.getElementById('ticketModal');

    // Popola immagine evento
    const eventImage = document.getElementById('modalEventImage');
    if (ticket.EventoImmagine) {
        eventImage.src = 'uploads/events/' + ticket.EventoImmagine;
        eventImage.style.display = 'block';
    } else {
        eventImage.parentElement.style.display = 'none';
    }

    // Popola i dati
    document.getElementById('modalEventName').textContent = ticket.EventoNome || '';
    document.getElementById('modalEventDate').textContent = formatDateJS(ticket.Data) + ' - ' + formatTimeJS(ticket.OraI);
    document.getElementById('modalEventLocation').textContent = ticket.LocationName || '';
    document.getElementById('modalHolder').textContent = (ticket.Nome || '') + ' ' + (ticket.Cognome || '');
    document.getElementById('modalType').textContent = ticket.idClasse || '';

    // Stato biglietto (F10)
    const today = new Date().toISOString().split('T')[0];
    const statusContainer = document.getElementById('modalStatus');
    if (ticket.Stato === 'validato') {
        statusContainer.innerHTML = '<span class="ticket-status used"><i class="fas fa-check-circle"></i> Utilizzato</span>';
    } else if (ticket.Data && ticket.Data < today) {
        statusContainer.innerHTML = '<span class="ticket-status expired"><i class="fas fa-clock"></i> Non usufruito</span>';
    } else {
        statusContainer.innerHTML = '<span class="ticket-status valid"><i class="fas fa-ticket-alt"></i> Da utilizzare</span>';
    }

    // Settore e posto
    const sectorInfo = document.getElementById('sectorInfo');
    const seatInfo = document.getElementById('seatInfo');

    if (ticket.idSettore) {
        sectorInfo.style.display = 'block';
        document.getElementById('modalSector').textContent = 'Settore ' + ticket.idSettore;
    } else {
        sectorInfo.style.display = 'none';
    }

    if (ticket.Fila && ticket.PostoNumero) {
        seatInfo.style.display = 'block';
        document.getElementById('modalSeat').textContent = 'Fila ' + ticket.Fila + ' - Posto ' + ticket.PostoNumero;
    } else {
        seatInfo.style.display = 'none';
    }

    // ID biglietto
    document.getElementById('modalTicketId').textContent = 'ID: ' + ticket.id;

    // Genera QR Code
    const qrContainer = document.getElementById('qrcodeContainer');
    qrContainer.innerHTML = '';

    // Costruisci stringa QR: idBiglietto - idUtente - idOrdine - idEvento - usato - nome cognome - settore - posto
    const qrData = [
        ticket.id || '',
        ticket.idUtente || '',
        ticket.idOrdine || '',
        ticket.idEvento || '',
        isUsed ? 'USATO' : 'VALIDO',
        (ticket.Nome || '') + ' ' + (ticket.Cognome || ''),
        ticket.idSettore ? ('Settore ' + ticket.idSettore) : 'N/A',
        (ticket.Fila && ticket.PostoNumero) ? ('Fila ' + ticket.Fila + ' Posto ' + ticket.PostoNumero) : 'N/A'
    ].join(' - ');

    currentQRCode = new QRCode(qrContainer, {
        text: qrData,
        width: 200,
        height: 200,
        colorDark: '#1f2937',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });

    // Sezione documento (solo biglietti acquistati)
    const docSection = document.getElementById('documentoSection');
    document.getElementById('docBigliettoId').value = ticket.id;
    document.getElementById('docUploadBtn').style.display = 'none';
    document.getElementById('documentoPreview').style.display = 'none';

    if (ticket.Stato === 'acquistato') {
        docSection.style.display = 'block';
        if (ticket.documento_foto) {
            document.getElementById('documentoImg').src = 'index.php?action=get_documento_biglietto&idBiglietto=' + ticket.id;
            document.getElementById('documentoPreview').style.display = 'block';
        }
        if (ticket.documento_tipo) {
            document.getElementById('docTipo').value = ticket.documento_tipo;
        }
    } else {
        docSection.style.display = 'none';
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function previewDocumento(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('documentoImg').src = e.target.result;
            document.getElementById('documentoPreview').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('docUploadBtn').style.display = 'inline-flex';
    }
}

async function uploadDocumento() {
    const form = document.getElementById('documentoForm');
    const formData = new FormData(form);
    formData.append('action', 'upload_documento_biglietto');

    const btn = document.getElementById('docUploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Caricamento...';

    try {
        const res = await fetch('index.php?action=upload_documento_biglietto', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Salvato!';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-save"></i> Salva documento';
                btn.disabled = false;
            }, 2000);
        } else {
            alert(data.error || 'Errore durante il caricamento');
            btn.innerHTML = '<i class="fas fa-save"></i> Salva documento';
            btn.disabled = false;
        }
    } catch (e) {
        alert('Errore di rete');
        btn.innerHTML = '<i class="fas fa-save"></i> Salva documento';
        btn.disabled = false;
    }
}

function closeTicketModal() {
    const modal = document.getElementById('ticketModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function printTicket() {
    const content = document.getElementById('printableTicket').innerHTML;
    const win = window.open('', '_blank', 'width=600,height=800');
    win.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Biglietto</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@page { size: A4; margin: 12mm; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: white; }
.printable-ticket { padding: 1.5rem; }
.ticket-event-image { width: 100%; max-height: 140px; overflow: hidden; border-radius: 8px; margin-bottom: 1rem; }
.ticket-event-image img { width: 100%; height: 140px; object-fit: cover; }
.ticket-event-header { text-align: center; padding-bottom: 1rem; border-bottom: 2px dashed #e5e7eb; margin-bottom: 1rem; }
.ticket-event-header h2 { font-size: 1.3rem; color: #1f2937; margin-bottom: 0.3rem; }
.event-date { color: #4f46e5; font-weight: 600; font-size: 1rem; }
.event-location { color: #6b7280; font-size: 0.9rem; margin-top: 0.2rem; }
.ticket-qr-container { display: flex; justify-content: center; margin: 1rem 0; padding: 0.75rem; background: #f9fafb; border-radius: 8px; }
.ticket-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem; }
.ticket-info-item { background: #f9fafb; border: 1px solid #e5e7eb; padding: 0.5rem 0.75rem; border-radius: 6px; }
.ticket-info-item.full-width { grid-column: 1 / -1; }
.ticket-info-item label { display: block; font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.15rem; }
.ticket-info-item span { font-weight: 600; color: #1f2937; font-size: 0.9rem; }
.ticket-status { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.75rem; border-radius: 9999px; font-weight: 600; font-size: 0.8rem; }
.ticket-status.valid { background: #d1fae5; color: #065f46; }
.ticket-status.used { background: #fee2e2; color: #991b1b; }
.ticket-footer-info { text-align: center; padding-top: 1rem; border-top: 2px dashed #e5e7eb; margin-top: 0.5rem; }
.ticket-id-display { font-family: 'Courier New', monospace; font-size: 0.8rem; color: #6b7280; background: #f3f4f6; padding: 0.4rem 0.75rem; border-radius: 4px; display: inline-block; }
</style>
</head>
<body>
<div class="printable-ticket">${content}</div>
<script>
window.onload = function() {
    // Attendi che QR sia renderizzato nel DOM originale, poi stampa
    setTimeout(function() {
        // Copia i canvas QR dall'originale (già generato)
        var srcCanvas = opener.document.querySelector('#qrcodeContainer canvas');
        var destCanvas = document.querySelector('#qrcodeContainer canvas');
        if (srcCanvas && !destCanvas) {
            var img = document.createElement('img');
            img.src = srcCanvas.toDataURL();
            img.style.width = '160px';
            img.style.height = '160px';
            var container = document.getElementById('qrcodeContainer');
            if (container) { container.innerHTML = ''; container.appendChild(img); }
        }
        window.print();
        window.close();
    }, 300);
};
<\/script>
</body>
</html>`);
    win.document.close();
}

// Chiudi modal cliccando fuori
document.getElementById('ticketModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTicketModal();
    }
});

// Chiudi con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTicketModal();
    }
});

// Formattazione date in JS
function formatDateJS(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    return date.toLocaleDateString('it-IT', options);
}

function formatTimeJS(timeStr) {
    if (!timeStr) return '';
    return timeStr.substring(0, 5);
}
</script>
