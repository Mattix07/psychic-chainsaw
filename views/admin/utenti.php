<?php
/**
 * Admin - Gestione Utenti
 */
$utenti = $_SESSION['admin_utenti'] ?? [];
$filter = $_SESSION['admin_filter'] ?? 'all';
?>

<div class="admin-page">
    <div class="admin-header">
        <div>
            <a href="index.php?action=admin_dashboard" class="back-link"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <h1><i class="fas fa-users-cog"></i> Gestione Utenti</h1>
        </div>
    </div>

    <!-- Filters -->
    <div class="admin-filters">
        <a href="index.php?action=admin_users&role=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">Tutti</a>
        <a href="index.php?action=admin_users&role=admin" class="filter-btn <?= $filter === 'admin' ? 'active' : '' ?>">Admin</a>
        <a href="index.php?action=admin_users&role=mod" class="filter-btn <?= $filter === 'mod' ? 'active' : '' ?>">Moderatori</a>
        <a href="index.php?action=admin_users&role=promoter" class="filter-btn <?= $filter === 'promoter' ? 'active' : '' ?>">Promoter</a>
        <a href="index.php?action=admin_users&role=user" class="filter-btn <?= $filter === 'user' ? 'active' : '' ?>">Utenti</a>
    </div>

    <!-- Users Table -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Ruolo</th>
                    <th>Verificato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utenti as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar-sm"><?= strtoupper(substr($u['Nome'], 0, 1)) ?></div>
                            <span><?= e($u['Nome'] . ' ' . $u['Cognome']) ?></span>
                        </div>
                    </td>
                    <td><?= e($u['Email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= $u['ruolo'] ?? 'user' ?>">
                            <?= ucfirst($u['ruolo'] ?? 'user') ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($u['email_verified'])): ?>
                            <span class="status-badge status-success"><i class="fas fa-check"></i></span>
                        <?php else: ?>
                            <span class="status-badge status-warning"><i class="fas fa-clock"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                        <div class="action-buttons">
                            <form method="post" action="index.php" class="inline-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="admin_update_role">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="role" class="role-select" onchange="this.form.submit()">
                                    <option value="user" <?= ($u['ruolo'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="promoter" <?= ($u['ruolo'] ?? '') === 'promoter' ? 'selected' : '' ?>>Promoter</option>
                                    <option value="mod" <?= ($u['ruolo'] ?? '') === 'mod' ? 'selected' : '' ?>>Mod</option>
                                    <option value="admin" <?= ($u['ruolo'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </form>
                            <form method="post" action="index.php" class="inline-form" onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="admin_delete_user">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-icon btn-danger" title="Elimina">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                            <span class="text-muted">Tu</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
