<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

$maidId = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare('
    SELECT m.*, a.area_name, u.name AS agent_name, u.phone AS agent_phone
    FROM maids m JOIN areas a ON a.id = m.area_id JOIN users u ON u.id = m.agent_id
    WHERE m.id = ?
');
$stmt->execute([$maidId]);
$maid = $stmt->fetch();

if (!$maid) {
    flash('danger', 'Maid not found.');
    redirectTo('maids.php');
}

$stmt = $db->prepare('SELECT * FROM maid_verification WHERE maid_id = ?');
$stmt->execute([$maidId]);
$verification = $stmt->fetch();

$stmt = $db->prepare('SELECT s.service_name FROM maid_services ms JOIN services s ON s.id = ms.service_id WHERE ms.maid_id = ?');
$stmt->execute([$maidId]);
$maidServices = array_column($stmt->fetchAll(), 'service_name');

$stmt = $db->prepare('SELECT * FROM maid_references WHERE maid_id = ?');
$stmt->execute([$maidId]);
$references = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM work_history WHERE maid_id = ? ORDER BY start_date DESC');
$stmt->execute([$maidId]);
$workHistory = $stmt->fetchAll();

$stmt = $db->prepare('SELECT r.*, u.name AS customer_name FROM reviews r JOIN users u ON u.id = r.customer_id WHERE r.maid_id = ? ORDER BY r.created_at DESC');
$stmt->execute([$maidId]);
$reviews = $stmt->fetchAll();

$stmt = $db->prepare('SELECT c.*, u.name AS customer_name FROM complaints c JOIN users u ON u.id = c.customer_id WHERE c.maid_id = ? ORDER BY c.created_at DESC');
$stmt->execute([$maidId]);
$complaints = $stmt->fetchAll();

$rating = getMaidAverageRating($maidId);

$activePage = 'maids';
$pageTitle = clean($maid['name']) . ' - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1><?= clean($maid['name']) ?></h1>
            <a href="verification.php?maid_id=<?= (int) $maid['id'] ?>" class="btn btn-brand btn-sm">Go to Verification</a>
        </div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="panel text-center">
                    <img src="<?= maidPhotoUrl($maid['photo']) ?>" class="rounded-4 mb-3" style="width:100%;height:230px;object-fit:cover;">
                    <h5><?= clean($maid['name']) ?></h5>
                    <p class="text-muted"><i class="fa-solid fa-location-dot"></i> <?= clean($maid['area_name']) ?></p>
                    <?= verificationBadge($verification['verification_status']) ?>
                    <div class="review-stars mt-2"><?= starRatingHtml($rating['average']) ?></div>
                    <p class="text-muted small"><?= $rating['average'] ?> (<?= $rating['total'] ?> reviews)</p>
                    <hr>
                    <p class="small text-muted mb-0">Registered by agent:</p>
                    <p class="fw-semibold"><?= clean($maid['agent_name']) ?> (<?= clean($maid['agent_phone']) ?>)</p>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="panel">
                    <div class="panel-title">Details</div>
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Phone:</strong><br><?= clean($maid['phone']) ?></div>
                        <div class="col-md-4"><strong>Permanent District:</strong><br><?= clean($maid['permanent_district'] ?: '-') ?></div>
                        <div class="col-md-4"><strong>Experience:</strong><br><?= (int) $maid['experience_years'] ?> years</div>
                        <div class="col-md-4"><strong>Expected Salary:</strong><br><?= formatMoney((float) $maid['expected_salary']) ?></div>
                        <div class="col-md-4"><strong>Working Type:</strong><br><?= ucwords(str_replace('_',' ',$maid['working_type'])) ?></div>
                        <div class="col-md-4"><strong>Availability:</strong><br><?= ucfirst($maid['availability']) ?></div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title">Services</div>
                    <?php foreach ($maidServices as $s): ?>
                        <span class="badge bg-light text-dark border me-2 mb-2 p-2"><?= clean($s) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="panel">
                    <div class="panel-title">Verification Data (Admin-only, sensitive)</div>
                    <div class="row g-2">
                        <div class="col-md-6"><strong>NID Number:</strong> <?= clean($verification['nid_number'] ?: 'Not provided') ?></div>
                        <div class="col-md-6"><strong>Verified By:</strong> <?= $verification['verified_by'] ? 'Admin #' . (int) $verification['verified_by'] : '-' ?></div>
                    </div>
                    <div class="row g-2 mt-1 small">
                        <div class="col-6">NID Verified: <?= $verification['nid_verified'] ? '✅' : '❌' ?></div>
                        <div class="col-6">Reference Verified: <?= $verification['reference_verified'] ? '✅' : '❌' ?></div>
                        <div class="col-6">Agent Verified: <?= $verification['agent_verified'] ? '✅' : '❌' ?></div>
                        <div class="col-6">Admin Verified: <?= $verification['admin_verified'] ? '✅' : '❌' ?></div>
                    </div>
                    <?php if ($verification['verification_notes']): ?>
                        <div class="note-item mt-3"><?= clean($verification['verification_notes']) ?></div>
                    <?php endif; ?>
                    <?php foreach ($references as $r): ?>
                        <hr>
                        <p class="small mb-1"><strong>Reference:</strong> <?= clean($r['reference_name']) ?> (<?= clean($r['relationship']) ?>) &middot; <?= clean($r['reference_phone']) ?> &middot; <?= clean($r['reference_area']) ?></p>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($workHistory)): ?>
                <div class="panel">
                    <div class="panel-title">Work History</div>
                    <?php foreach ($workHistory as $w): ?>
                        <div class="note-item">
                            <?= clean($w['area']) ?> &middot; <?= ucwords(str_replace('_',' ',$w['working_type'])) ?> &middot;
                            <?= $w['start_date'] ? date('M Y', strtotime($w['start_date'])) : '' ?> - <?= $w['end_date'] ? date('M Y', strtotime($w['end_date'])) : 'Present' ?><br>
                            <?= clean($w['notes']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="panel">
                    <div class="panel-title">Reviews (<?= count($reviews) ?>)</div>
                    <?php foreach ($reviews as $r): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <div class="review-stars"><?= starRatingHtml((float) $r['overall_rating']) ?></div>
                            <?= clean($r['comment']) ?> <span class="text-muted small">- <?= clean($r['customer_name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($reviews)): ?><p class="text-muted mb-0">No reviews yet.</p><?php endif; ?>
                </div>

                <?php if (!empty($complaints)): ?>
                <div class="panel">
                    <div class="panel-title">Complaints (<?= count($complaints) ?>)</div>
                    <?php foreach ($complaints as $c): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <span class="badge <?= statusBadgeClass($c['status']) ?>"><?= clean($c['status']) ?></span>
                            <strong><?= clean($c['reason']) ?></strong> - <?= clean($c['description']) ?>
                            <span class="text-muted small">(<?= clean($c['customer_name']) ?>)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
