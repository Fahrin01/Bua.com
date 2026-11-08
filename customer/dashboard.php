<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

$stmt = $db->prepare('SELECT COUNT(*) FROM hiring_requests WHERE customer_id = ?');
$stmt->execute([$uid]);
$totalRequests = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM hiring_requests WHERE customer_id = ? AND status IN ('PENDING','CONTACTING_MAID','MAID_CONSIDERING','ACCEPTED','HIRED')");
$stmt->execute([$uid]);
$activeRequests = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM hiring_requests WHERE customer_id = ? AND status = 'COMPLETED'");
$stmt->execute([$uid]);
$completedHires = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM favorites WHERE customer_id = ?');
$stmt->execute([$uid]);
$favCount = (int) $stmt->fetchColumn();

$recentNotifs = array_slice(getNotifications($uid), 0, 5);

$stmt = $db->prepare('
    SELECT hr.*, m.name AS maid_name, m.photo, a.area_name
    FROM hiring_requests hr
    JOIN maids m ON m.id = hr.maid_id
    JOIN areas a ON a.id = m.area_id
    WHERE hr.customer_id = ?
    ORDER BY hr.created_at DESC LIMIT 5
');
$stmt->execute([$uid]);
$recentRequests = $stmt->fetchAll();

$activePage = 'dashboard';
$pageTitle = 'Customer Dashboard - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1>Welcome, <?= clean(currentUserName()) ?></h1>
        </div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card"><div class="stat-icon" style="background:#1e88e5;"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div><p class="stat-value"><?= $totalRequests ?></p><p class="stat-label">Total Requests</p></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card"><div class="stat-icon" style="background:#2f9e57;"><i class="fa-solid fa-bolt"></i></div>
                    <div><p class="stat-value"><?= $activeRequests ?></p><p class="stat-label">Active Requests</p></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card"><div class="stat-icon" style="background:#0b3350;"><i class="fa-solid fa-house-circle-check"></i></div>
                    <div><p class="stat-value"><?= $completedHires ?></p><p class="stat-label">Completed Hires</p></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card"><div class="stat-icon" style="background:#c0392b;"><i class="fa-solid fa-heart"></i></div>
                    <div><p class="stat-value"><?= $favCount ?></p><p class="stat-label">Favorites</p></div></div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="panel-title mb-0">Recent Requests</div>
                        <a href="my-requests.php" class="small">View All</a>
                    </div>
                    <?php if (empty($recentRequests)): ?>
                        <p class="text-muted mb-0">You haven't made any hiring requests yet. <a href="search-maids.php">Find a maid</a></p>
                    <?php else: ?>
                        <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Maid</th><th>Area</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($recentRequests as $r): ?>
                                <tr>
                                    <td><?= clean($r['maid_name']) ?></td>
                                    <td><?= clean($r['area_name']) ?></td>
                                    <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= clean($r['status']) ?></span></td>
                                    <td><a href="request-details.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-dark">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="panel-title mb-0">Recent Notifications</div>
                        <a href="notifications.php" class="small">View All</a>
                    </div>
                    <?php if (empty($recentNotifs)): ?>
                        <p class="text-muted mb-0">No notifications yet.</p>
                    <?php else: foreach ($recentNotifs as $n): ?>
                        <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" style="border-radius:8px;">
                            <div class="notif-icon"><i class="fa-solid fa-bell"></i></div>
                            <div>
                                <div class="fw-semibold"><?= clean($n['title']) ?></div>
                                <div class="small text-muted"><?= clean($n['message']) ?></div>
                                <div class="small text-muted"><?= timeAgo($n['created_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
