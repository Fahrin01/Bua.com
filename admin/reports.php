<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

// Requests by status
$statusCounts = $db->query('SELECT status, COUNT(*) AS cnt FROM hiring_requests GROUP BY status')->fetchAll();

// Maids by area
$areaCounts = $db->query('
    SELECT a.area_name, COUNT(m.id) AS cnt FROM areas a
    LEFT JOIN maids m ON m.area_id = a.id
    GROUP BY a.id ORDER BY cnt DESC
')->fetchAll();

// Top rated maids
$topMaids = $db->query('
    SELECT m.name, AVG(r.overall_rating) AS avg_rating, COUNT(r.id) AS review_count
    FROM maids m JOIN reviews r ON r.maid_id = m.id AND r.status = "visible"
    GROUP BY m.id
    HAVING review_count > 0
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT 5
')->fetchAll();

// Monthly requests (last 6 months)
$monthly = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM hiring_requests
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY ym ORDER BY ym
")->fetchAll();

$activityLogs = $db->query('
    SELECT al.*, u.name AS user_name FROM activity_logs al
    LEFT JOIN users u ON u.id = al.user_id
    ORDER BY al.created_at DESC LIMIT 30
')->fetchAll();

$maxAreaCount = max(1, ...array_map(fn($r) => (int) $r['cnt'], $areaCounts ?: [['cnt'=>1]]));

$activePage = 'reports';
$pageTitle = 'Reports - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Reports &amp; Statistics</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="panel">
                    <div class="panel-title">Hiring Requests by Status</div>
                    <?php foreach ($statusCounts as $s): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge <?= statusBadgeClass($s['status']) ?>"><?= clean($s['status']) ?></span>
                            <strong><?= (int) $s['cnt'] ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($statusCounts)): ?><p class="text-muted mb-0">No data yet.</p><?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel-title">Maids by Area</div>
                    <?php foreach ($areaCounts as $a): ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small"><span><?= clean($a['area_name']) ?></span><span><?= (int) $a['cnt'] ?></span></div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar" style="width:<?= ((int) $a['cnt'] / $maxAreaCount) * 100 ?>%;background:#1e88e5;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="panel">
                    <div class="panel-title">Top Rated Maids</div>
                    <?php if (empty($topMaids)): ?>
                        <p class="text-muted mb-0">No reviews yet.</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>Maid</th><th>Rating</th><th>Reviews</th></tr></thead>
                        <tbody>
                        <?php foreach ($topMaids as $m): ?>
                            <tr>
                                <td><?= clean($m['name']) ?></td>
                                <td><?= starRatingHtml((float) $m['avg_rating']) ?></td>
                                <td><?= (int) $m['review_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel-title">Monthly Hiring Requests (Last 6 Months)</div>
                    <?php if (empty($monthly)): ?>
                        <p class="text-muted mb-0">No data yet.</p>
                    <?php else:
                        $maxMonthly = max(1, ...array_map(fn($r) => (int) $r['cnt'], $monthly)); ?>
                        <?php foreach ($monthly as $m): ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small"><span><?= date('M Y', strtotime($m['ym'] . '-01')) ?></span><span><?= (int) $m['cnt'] ?></span></div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar" style="width:<?= ((int) $m['cnt'] / $maxMonthly) * 100 ?>%;background:#0b3350;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-title">Activity Logs</div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>User</th><th>Action</th><th>Description</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($activityLogs as $l): ?>
                        <tr>
                            <td><?= clean($l['user_name'] ?? 'System') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= clean($l['action']) ?></span></td>
                            <td><?= clean($l['description']) ?></td>
                            <td class="text-muted small"><?= timeAgo($l['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
