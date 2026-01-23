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
</div>
