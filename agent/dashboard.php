<?php
require_once __DIR__ . '/../config/functions.php';
requireAgent();
$db = Database::getConnection();
$aid = currentUserId();

$stmt = $db->prepare('SELECT COUNT(*) FROM maids WHERE agent_id = ?');
$stmt->execute([$aid]);
$totalMaids = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM maids m JOIN maid_verification v ON v.maid_id = m.id WHERE m.agent_id = ? AND v.verification_status = "VERIFIED"');
$stmt->execute([$aid]);
$verifiedMaids = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM maids m JOIN maid_verification v ON v.maid_id = m.id WHERE m.agent_id = ? AND v.verification_status IN ("PENDING","UNDER_REVIEW")');
$stmt->execute([$aid]);
$pendingMaids = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM maids WHERE agent_id = ? AND availability = "available"');
$stmt->execute([$aid]);
$availableMaids = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM maids WHERE agent_id = ? AND current_households > 0');
$stmt->execute([$aid]);
$workingMaids = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT m.*, a.area_name, v.verification_status FROM maids m JOIN areas a ON a.id = m.area_id JOIN maid_verification v ON v.maid_id = m.id WHERE m.agent_id = ? ORDER BY m.created_at DESC LIMIT 5');
$stmt->execute([$aid]);
$recentMaids = $stmt->fetchAll();

$recentNotifs = array_slice(getNotifications($aid), 0, 5);

$activePage = 'dashboard';
$pageTitle = 'Agent Dashboard - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1>Welcome, <?= clean(currentUserName()) ?></h1>
            <a href="add-maid.php" class="btn btn-brand btn-sm"><i class="fa-solid fa-user-plus"></i> Add Maid</a>
        </div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="row g-3 mb-4">
            <div class="col-md col-6">
                <div class="stat-card"><div class="stat-icon" style="background:#1e88e5;"><i class="fa-solid fa-people-group"></i></div>
                    <div><p class="stat-value"><?= $totalMaids ?></p><p class="stat-label">Total Maids</p></div></div>
            </div>
            <div class="col-md col-6">
                <div class="stat-card"><div class="stat-icon" style="background:#2f9e57;"><i class="fa-solid fa-circle-check"></i></div>
                    <div><p class="stat-value"><?= $verifiedMaids ?></p><p class="stat-label">Verified</p></div></div>
            </div>
            <div class="col-md col-6">
                <div class="stat-card"><div class="stat-icon" style="background:#c0392b;"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div><p class="stat-value"><?= $pendingMaids ?></p><p class="stat-label">Pending</p></div></div>
            </div>
            <div class="col-md col-6">
                <div class="stat-card"><div class="stat-icon" style="background:#0b3350;"><i class="fa-solid fa-calendar-check"></i></div>
                    <div><p class="stat-value"><?= $availableMaids ?></p><p class="stat-label">Available</p></div></div>
            </div>
            <div class="col-md col-6">
                <div class="stat-card"><div class="stat-icon" style="background:#0d6ca6;"><i class="fa-solid fa-briefcase"></i></div>
                    <div><p class="stat-value"><?= $workingMaids ?></p><p class="stat-label">Currently Working</p></div></div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="panel-title mb-0">Recently Added Maids</div>
                        <a href="my-maids.php" class="small">View All</a>
                    </div>
                    <?php if (empty($recentMaids)): ?>
                        <p class="text-muted mb-0">You haven't added any maids yet. <a href="add-maid.php">Add your first maid</a></p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Name</th><th>Area</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($recentMaids as $m): ?>
                                <tr>
                                    <td><?= clean($m['name']) ?></td>
                                    <td><?= clean($m['area_name']) ?></td>
                                    <td><?= verificationBadge($m['verification_status']) ?></td>
                                    <td><a href="maid-details.php?id=<?= (int) $m['id'] ?>" class="btn btn-sm btn-outline-dark">View</a></td>
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
