<?php
/**
 * Dashboard Amministratore
 *
 * Pannello di controllo principale per utenti con ruolo 'admin'.
 * Fornisce una panoramica del sistema e accesso rapido alle funzionalità.
 *
 * Sezioni:
 * - Stats Cards: contatori per utenti, eventi totali/futuri, ordini
 * - Azioni Rapide: link a gestione utenti, eventi, creazione evento
 * - Utenti per Ruolo: breakdown utenti per ruolo (admin, mod, promoter, user)
 *
 * Accessibile solo agli admin (verificato dal controller).
 */
$stats = $_SESSION['admin_stats'] ?? [];
$utentiCount = $stats['utenti'] ?? [];
?>

<div class="admin-page">
    <div class="admin-header">
        <h1><i class="fas fa-shield-alt"></i> Dashboard Admin</h1>
        <p class="subtitle">Pannello di controllo amministrazione</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= array_sum($utentiCount) ?></span>
                <span class="stat-label">Utenti Totali</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['eventi_totali'] ?? 0 ?></span>
                <span class="stat-label">Eventi Totali</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['eventi_futuri'] ?? 0 ?></span>
                <span class="stat-label">Eventi Futuri</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['ordini_totali'] ?? 0 ?></span>
                <span class="stat-label">Ordini Totali</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="admin-section">
        <h2><i class="fas fa-bolt"></i> Azioni Rapide</h2>
        <div class="quick-actions">
            <a href="index.php?action=admin_users" class="action-card">
                <i class="fas fa-users-cog"></i>
                <span>Gestisci Utenti</span>
            </a>
            <a href="index.php?action=admin_events" class="action-card">
                <i class="fas fa-calendar-alt"></i>
                <span>Gestisci Eventi</span>
            </a>
            <a href="index.php?action=admin_create_event" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <span>Nuovo Evento</span>
            </a>
        </div>
    </div>

    <!-- Users by Role -->
    <div class="admin-section">
        <h2><i class="fas fa-user-tag"></i> Utenti per Ruolo</h2>
        <div class="role-stats">
            <div class="role-stat">
                <span class="role-badge role-admin">Admin</span>
                <span class="role-count"><?= $utentiCount['admin'] ?? 0 ?></span>
            </div>
            <div class="role-stat">
                <span class="role-badge role-mod">Moderatori</span>
                <span class="role-count"><?= $utentiCount['mod'] ?? 0 ?></span>
            </div>
            <div class="role-stat">
                <span class="role-badge role-promoter">Promoter</span>
                <span class="role-count"><?= $utentiCount['promoter'] ?? 0 ?></span>
            </div>
            <div class="role-stat">
                <span class="role-badge role-user">Utenti</span>
                <span class="role-count"><?= $utentiCount['user'] ?? 0 ?></span>
            </div>
        </div>
    </div>
</div>
