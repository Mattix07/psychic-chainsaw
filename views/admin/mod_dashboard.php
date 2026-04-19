<?php
/**
 * Dashboard Moderatore
 *
 * Pannello di controllo per utenti con ruolo 'mod'.
 * I moderatori hanno permessi intermedi tra user e admin.
 *
 * Sezioni:
 * - Stats: contatori eventi e recensioni totali
 * - Azioni Rapide: gestione eventi, creazione nuovo evento
 * - Strumenti Moderatore: riepilogo permessi (cosa può/non può fare)
 *
 * Permessi moderatore:
 * - Può gestire e moderare tutti gli eventi
 * - Può creare nuovi eventi
 * - Può eliminare eventi inappropriati
 * - NON può gestire gli utenti (riservato agli admin)
 */
$stats = $_SESSION['mod_stats'] ?? [];
?>

<div class="admin-page">
    <div class="admin-header">
        <h1><i class="fas fa-user-shield"></i> Dashboard Moderatore</h1>
        <p class="subtitle">Gestisci contenuti e moderazione</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['eventi_totali'] ?? 0 ?></span>
                <span class="stat-label">Eventi</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['recensioni_totali'] ?? 0 ?></span>
                <span class="stat-label">Recensioni</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="admin-section">
        <h2><i class="fas fa-bolt"></i> Azioni Rapide</h2>
        <div class="quick-actions">
            <a href="index.php?action=admin_events" class="action-card">
                <i class="fas fa-calendar-alt"></i>
                <span>Gestisci Eventi</span>
            </a>
            <a href="index.php?action=admin_create_event" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <span>Nuovo Evento</span>
            </a>
            <a href="index.php?action=list_locations" class="action-card">
                <i class="fas fa-map-marker-alt"></i>
                <span>Gestisci Location</span>
            </a>
            <a href="index.php?action=list_manifestazioni" class="action-card">
                <i class="fas fa-calendar-check"></i>
                <span>Gestisci Manifestazioni</span>
            </a>
        </div>
    </div>

    <!-- Mod Tools Info -->
    <div class="admin-section">
        <h2><i class="fas fa-tools"></i> Strumenti Moderatore</h2>
        <div class="info-box">
            <p><i class="fas fa-check-circle"></i> Puoi gestire e moderare tutti gli eventi</p>
            <p><i class="fas fa-check-circle"></i> Puoi creare nuovi eventi</p>
            <p><i class="fas fa-check-circle"></i> Puoi eliminare eventi inappropriati</p>
            <p><i class="fas fa-times-circle text-muted"></i> Non puoi gestire gli utenti (solo Admin)</p>
        </div>
    </div>

    <!-- Moderazione Recensioni (F8) -->
    <div class="admin-section">
        <h2><i class="fas fa-flag"></i> Moderazione Recensioni</h2>
        <div style="margin-bottom:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
            <button class="btn btn-sm btn-secondary active" onclick="loadRecensioni('segnalata',this)">Segnalate</button>
            <button class="btn btn-sm btn-secondary" onclick="loadRecensioni('nascosta',this)">Nascoste</button>
            <button class="btn btn-sm btn-secondary" onclick="loadRecensioni('',this)">Tutte</button>
        </div>
        <div id="recensioniModPanel"><p class="no-data">Clicca un filtro per caricare le recensioni.</p></div>
    </div>

    <script>
    async function loadRecensioni(stato, btn) {
        document.querySelectorAll('.recensioni-filter-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        const panel = document.getElementById('recensioniModPanel');
        panel.innerHTML = '<p class="no-data"><i class="fas fa-spinner fa-spin"></i> Caricamento...</p>';
        try {
            const res = await fetch('index.php?action=get_recensioni_admin' + (stato ? '&stato=' + encodeURIComponent(stato) : ''));
            const data = await res.json();
            if (!data.recensioni || data.recensioni.length === 0) {
                panel.innerHTML = '<p class="no-data">Nessuna recensione trovata.</p>';
                return;
            }
            let html = '<div style="overflow-x:auto;"><table class="admin-table"><thead><tr><th>Autore</th><th>Evento</th><th>Voto</th><th>Commento</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>';
            data.recensioni.forEach(r => {
                const statoColors = {segnalata:'#d97706',nascosta:'#991b1b',visibile:'#065f46'};
                const color = statoColors[r.stato] || '#6b7280';
                const btnAction = r.stato !== 'nascosta'
                    ? `<button class="btn btn-xs btn-danger" onclick="moderaRecensione(${r.id},'hide_recensione')">Nascondi</button>`
                    : `<button class="btn btn-xs btn-success" onclick="moderaRecensione(${r.id},'restore_recensione')">Ripristina</button>`;
                html += `<tr>
                    <td>${r.Nome || ''} ${r.Cognome || ''}</td>
                    <td>${r.EventoNome || ''}</td>
                    <td>${r.Valutazione || r.voto || '—'}/5</td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${r.Testo || r.testo || ''}</td>
                    <td><span style="color:${color};font-weight:600;">${r.stato}</span></td>
                    <td>${btnAction}</td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            panel.innerHTML = html;
        } catch(e) {
            panel.innerHTML = '<p class="no-data">Errore nel caricamento.</p>';
        }
    }

    async function moderaRecensione(id, action) {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('csrf_token', window.EventsMaster?.csrfToken || '');
        fd.append('action', action);
        try {
            const res = await fetch('index.php', { method: 'POST', body: fd });
            await res.json();
        } catch(e) {}
        loadRecensioni('', null);
    }
    </script>
</div>
