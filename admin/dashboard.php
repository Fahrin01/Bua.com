<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

$stats = [];
$stats['customers'] = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$stats['agents'] = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='agent'")->fetchColumn();
$stats['maids'] = (int) $db->query("SELECT COUNT(*) FROM maids")->fetchColumn();
$stats['verified'] = (int) $db->query("SELECT COUNT(*) FROM maid_verification WHERE verification_status='VERIFIED'")->fetchColumn();
$stats['pending_verification'] = (int) $db->query("SELECT COUNT(*) FROM maid_verification WHERE verification_status IN ('PENDING','UNDER_REVIEW')")->fetchColumn();
$stats['available'] = (int) $db->query("SELECT COUNT(*) FROM maids WHERE availability='available'")->fetchColumn();
$stats['active_requests'] = (int) $db->query("SELECT COUNT(*) FROM hiring_requests WHERE status NOT IN ('COMPLETED','REJECTED','CANCELLED')")->fetchColumn();
$stats['completed_hires'] = (int) $db->query("SELECT COUNT(*) FROM hiring_requests WHERE status='COMPLETED'")->fetchColumn();
$stats['pending_complaints'] = (int) $db->query("SELECT COUNT(*) FROM complaints WHERE status IN ('PENDING','UNDER_REVIEW')")->fetchColumn();

$recentRequests = $db->query('
    SELECT hr.*, u.name AS customer_name, m.name AS maid_name
    FROM hiring_requests hr
    JOIN users u ON u.id = hr.customer_id
    JOIN maids m ON m.id = hr.maid_id
    ORDER BY hr.created_at DESC LIMIT 6
')->fetchAll();

$recentActivity = $db->query('
    SELECT al.*, u.name AS user_name FROM activity_logs al
    LEFT JOIN users u ON u.id = al.user_id
    ORDER BY al.created_at DESC LIMIT 8
')->fetchAll();

$activePage = 'dashboard';
$pageTitle = 'Admin Dashboard - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Admin Overview</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon" style="background:#1e88e5;"><i class="fa-solid fa-users"></i></div><div><p class="stat-value"><?= $stats['customers'] ?></p><p class="stat-label">Customers</p></div></div></div>
            <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon" style="background:#0b3350;"><i class="fa-solid fa-user-tie"></i></div><div><p class="stat-value"><?= $stats['agents'] ?></p><p class="stat-label">Agents</p></div></div></div>
            <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon" style="background:#0d6ca6;"><i class="fa-solid fa-people-group"></i></div><div><p class="stat-value"><?= $stats['maids'] ?></p><p class="stat-label">Total Maids</p></div></div></div>
            <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon" style="background:#2f9e57;"><i class="fa-solid fa-circle-check"></i></div><div><p class="stat-value"><?= $stats['verified'] ?></p><p class="stat-label">Verified Maids</p></div></div></div>
            <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon" style="background:#c0392b;"><i class="fa-solid fa-hourglass-half"></i></div><div><p class="stat-value"><?= $stats['pending_verification'] ?></p><p class="stat-label">Pending Verification</p></div></div></div>
            <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon" style="background:#2f9e57;"><i class="fa-solid fa-calendar-check"></i></div><div><p class="stat-value"><?= $stats['available'] ?></p><p class="stat-label">Available Maids</p></div></div></div>
            <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon" style="background:#1e88e5;"><i class="fa-solid fa-handshake"></i></div><div><p class="stat-value"><?= $stats['active_requests'] ?></p><p class="stat-label">Active Requests</p></div></div></div>
            <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon" style="background:#0b3350;"><i class="fa-solid fa-house-circle-check"></i></div><div><p class="stat-value"><?= $stats['completed_hires'] ?></p><p class="stat-label">Completed Hires</p></div></div></div>
            <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon" style="background:#c0392b;"><i class="fa-solid fa-triangle-exclamation"></i></div><div><p class="stat-value"><?= $stats['pending_complaints'] ?></p><p class="stat-label">Pending Complaints</p></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="panel-title mb-0">Recent Hiring Requests</div>
                        <a href="hiring-requests.php" class="small">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>#</th><th>Customer</th><th>Maid</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($recentRequests as $r): ?>
                                <tr>
                                    <td>#<?= (int) $r['id'] ?></td>
                                    <td><?= clean($r['customer_name']) ?></td>
                                    <td><?= clean($r['maid_name']) ?></td>
                                    <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= clean($r['status']) ?></span></td>
                                    <td><a href="request-details.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-dark">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="panel">
                    <div class="panel-title">Recent Activity</div>
                    <?php foreach ($recentActivity as $a): ?>
                        <div class="note-item">
                            <div class="note-meta"><?= clean($a['user_name'] ?? 'System') ?> &middot; <?= timeAgo($a['created_at']) ?></div>
                            <strong><?= clean($a['action']) ?></strong> — <?= clean($a['description']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
