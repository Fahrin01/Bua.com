<?php
// Expects $activePage (string key) to be set before include
$activePage = $activePage ?? '';
$role = currentRole();

function navItem(string $key, string $active, string $href, string $icon, string $label): string
{
    $isActive = $key === $active ? ' active' : '';
    return '<a href="' . $href . '" class="sidebar-link' . $isActive . '"><i class="fa-solid ' . $icon . '"></i> <span>' . $label . '</span></a>';
}

$b = baseUrl();
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-brand">
        <a href="<?= $b ?>/index.php">BUA<span class="brand-accent">.COM</span></a>
    </div>
    <div class="sidebar-user">
        <div class="sidebar-avatar"><?= strtoupper(substr(currentUserName() ?? 'U', 0, 1)) ?></div>
        <div>
            <div class="sidebar-username"><?= clean(currentUserName() ?? '') ?></div>
            <div class="sidebar-role"><?= ucfirst($role ?? '') ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
    <?php if ($role === 'customer'): ?>
        <?= navItem('dashboard', $activePage, "$b/customer/dashboard.php", 'fa-gauge', 'Dashboard') ?>
        <?= navItem('search', $activePage, "$b/customer/search-maids.php", 'fa-magnifying-glass', 'Find Maid') ?>
        <?= navItem('requests', $activePage, "$b/customer/my-requests.php", 'fa-clipboard-list', 'My Requests') ?>
        <?= navItem('favorites', $activePage, "$b/customer/favorites.php", 'fa-heart', 'Favorites') ?>
        <?= navItem('notifications', $activePage, "$b/customer/notifications.php", 'fa-bell', 'Notifications') ?>
        <?= navItem('reviews', $activePage, "$b/customer/reviews.php", 'fa-star', 'Reviews') ?>
        <?= navItem('complaints', $activePage, "$b/customer/complaints.php", 'fa-triangle-exclamation', 'Complaints') ?>
        <?= navItem('profile', $activePage, "$b/customer/profile.php", 'fa-user', 'Profile') ?>
    <?php elseif ($role === 'agent'): ?>
        <?= navItem('dashboard', $activePage, "$b/agent/dashboard.php", 'fa-gauge', 'Dashboard') ?>
        <?= navItem('add-maid', $activePage, "$b/agent/add-maid.php", 'fa-user-plus', 'Add Maid') ?>
        <?= navItem('my-maids', $activePage, "$b/agent/my-maids.php", 'fa-people-group', 'My Maids') ?>
        <?= navItem('verification', $activePage, "$b/agent/verification.php", 'fa-id-card', 'Verification') ?>
        <?= navItem('notifications', $activePage, "$b/agent/notifications.php", 'fa-bell', 'Notifications') ?>
    <?php elseif ($role === 'admin'): ?>
        <?= navItem('dashboard', $activePage, "$b/admin/dashboard.php", 'fa-gauge', 'Dashboard') ?>
        <?= navItem('customers', $activePage, "$b/admin/customers.php", 'fa-users', 'Customers') ?>
        <?= navItem('agents', $activePage, "$b/admin/agents.php", 'fa-user-tie', 'Agents') ?>
        <?= navItem('maids', $activePage, "$b/admin/maids.php", 'fa-people-group', 'Maids') ?>
        <?= navItem('verification', $activePage, "$b/admin/verification.php", 'fa-id-card', 'Verification') ?>
        <?= navItem('hiring-requests', $activePage, "$b/admin/hiring-requests.php", 'fa-handshake', 'Hiring Requests') ?>
        <?= navItem('reviews', $activePage, "$b/admin/reviews.php", 'fa-star', 'Reviews') ?>
        <?= navItem('complaints', $activePage, "$b/admin/complaints.php", 'fa-triangle-exclamation', 'Complaints') ?>
        <?= navItem('areas', $activePage, "$b/admin/areas.php", 'fa-map-location-dot', 'Areas') ?>
        <?= navItem('services', $activePage, "$b/admin/services.php", 'fa-list-check', 'Services') ?>
        <?= navItem('notifications', $activePage, "$b/admin/notifications.php", 'fa-bell', 'Notifications') ?>
        <?= navItem('reports', $activePage, "$b/admin/reports.php", 'fa-chart-line', 'Reports') ?>
    <?php endif; ?>
        <a href="<?= $b ?>/logout.php" class="sidebar-link sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
    </nav>
</aside>
