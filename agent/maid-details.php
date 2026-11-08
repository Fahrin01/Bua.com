<?php
require_once __DIR__ . '/../config/functions.php';
requireAgent();
$db = Database::getConnection();
$aid = currentUserId();

$maidId = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare('
    SELECT m.*, a.area_name FROM maids m JOIN areas a ON a.id = m.area_id
    WHERE m.id = ? AND m.agent_id = ?
');
$stmt->execute([$maidId, $aid]);
$maid = $stmt->fetch();

if (!$maid) {
    flash('danger', 'Maid not found or you do not have access to this record.');
    redirectTo('my-maids.php');
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

$rating = getMaidAverageRating($maidId);

$activePage = 'my-maids';
$pageTitle = clean($maid['name']) . ' - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1><?= clean($maid['name']) ?></h1>
            <a href="edit-maid.php?id=<?= (int) $maid['id'] ?>" class="btn btn-brand btn-sm">Edit</a>
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
                        <div class="col-md-4"><strong>Preferred Time:</strong><br><?= clean($maid['preferred_time'] ?: '-') ?></div>
                        <div class="col-md-4"><strong>Current Households:</strong><br><?= (int) $maid['current_households'] ?></div>
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
                    <div class="panel-title">Verification Status (Internal)</div>
                    <div class="row g-2 small">
                        <div class="col-6">NID Verified: <?= $verification['nid_verified'] ? '✅' : '❌' ?></div>
                        <div class="col-6">Reference Verified: <?= $verification['reference_verified'] ? '✅' : '❌' ?></div>
                        <div class="col-6">Agent Verified: <?= $verification['agent_verified'] ? '✅' : '❌' ?></div>
                        <div class="col-6">Admin Verified: <?= $verification['admin_verified'] ? '✅' : '❌' ?></div>
                    </div>
                    <?php if ($verification['verification_notes']): ?>
                        <div class="note-item mt-3"><?= clean($verification['verification_notes']) ?></div>
                    <?php endif; ?>
                    <p class="text-muted small mt-2 mb-0"><i class="fa-solid fa-circle-info"></i> Only Admin can grant final verification approval.</p>
                </div>

                <?php if (!empty($references)): ?>
                <div class="panel">
                    <div class="panel-title">Reference (Private)</div>
                    <?php foreach ($references as $r): ?>
                        <div class="row g-2 small mb-2">
                            <div class="col-md-3"><strong>Name:</strong> <?= clean($r['reference_name']) ?></div>
                            <div class="col-md-3"><strong>Area:</strong> <?= clean($r['reference_area']) ?></div>
                            <div class="col-md-3"><strong>Phone:</strong> <?= clean($r['reference_phone']) ?></div>
                            <div class="col-md-3"><strong>Relation:</strong> <?= clean($r['relationship']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
