<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

$maidId = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare('
    SELECT m.*, a.area_name, v.verification_status
    FROM maids m
    JOIN areas a ON a.id = m.area_id
    JOIN maid_verification v ON v.maid_id = m.id
    WHERE m.id = ? AND m.status = "active"
');
$stmt->execute([$maidId]);
$maid = $stmt->fetch();

if (!$maid) {
    flash('danger', 'Maid profile not found.');
    redirectTo('search-maids.php');
}

$stmt = $db->prepare('SELECT s.service_name FROM maid_services ms JOIN services s ON s.id = ms.service_id WHERE ms.maid_id = ?');
$stmt->execute([$maidId]);
$maidServices = array_column($stmt->fetchAll(), 'service_name');

$rating = getMaidAverageRating($maidId);

$stmt = $db->prepare('SELECT * FROM work_history WHERE maid_id = ? ORDER BY start_date DESC');
$stmt->execute([$maidId]);
$workHistory = $stmt->fetchAll();

$stmt = $db->prepare('
    SELECT r.*, u.name AS customer_name
    FROM reviews r JOIN users u ON u.id = r.customer_id
    WHERE r.maid_id = ? AND r.status = "visible"
    ORDER BY r.created_at DESC
');
$stmt->execute([$maidId]);
$reviews = $stmt->fetchAll();

$favStmt = $db->prepare('SELECT id FROM favorites WHERE customer_id = ? AND maid_id = ?');
$favStmt->execute([$uid, $maidId]);
$isFav = (bool) $favStmt->fetch();

$activePage = 'search';
$pageTitle = clean($maid['name']) . ' - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Maid Profile</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="panel text-center">
                    <img src="<?= maidPhotoUrl($maid['photo']) ?>" class="rounded-4 mb-3" style="width:100%;height:260px;object-fit:cover;" alt="<?= clean($maid['name']) ?>">
                    <h4 class="mb-1"><?= clean($maid['name']) ?></h4>
                    <p class="text-muted mb-2"><i class="fa-solid fa-location-dot"></i> <?= clean($maid['area_name']) ?></p>
                    <div class="mb-2"><?= verificationBadge($maid['verification_status']) ?></div>
                    <div class="review-stars mb-1"><?= starRatingHtml($rating['average']) ?></div>
                    <p class="text-muted small"><?= $rating['average'] ?> average (<?= $rating['total'] ?> reviews)</p>

                    <div class="d-flex gap-2 mt-3">
                        <a href="request-maid.php?maid_id=<?= (int) $maid['id'] ?>" class="btn btn-brand flex-grow-1">Request This Maid</a>
                    </div>
                    <form method="post" action="favorites.php" class="mt-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="maid_id" value="<?= (int) $maid['id'] ?>">
                        <input type="hidden" name="action" value="<?= $isFav ? 'remove' : 'add' ?>">
                        <input type="hidden" name="redirect" value="maid-profile.php?id=<?= (int) $maid['id'] ?>">
                        <button type="submit" class="btn btn-outline-dark w-100">
                            <i class="fa-<?= $isFav ? 'solid' : 'regular' ?> fa-heart"></i> <?= $isFav ? 'Remove from Favorites' : 'Add to Favorites' ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="panel">
                    <div class="panel-title">Overview</div>
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Experience:</strong><br><?= (int) $maid['experience_years'] ?> years</div>
                        <div class="col-md-4"><strong>Expected Salary:</strong><br><?= formatMoney((float) $maid['expected_salary']) ?>/month</div>
                        <div class="col-md-4"><strong>Working Type:</strong><br><?= ucwords(str_replace('_',' ',$maid['working_type'])) ?></div>
                        <div class="col-md-4"><strong>Preferred Time:</strong><br><?= clean($maid['preferred_time'] ?: 'Flexible') ?></div>
                        <div class="col-md-4"><strong>Current Households:</strong><br><?= (int) $maid['current_households'] ?></div>
                        <div class="col-md-4"><strong>Availability:</strong><br><span class="badge <?= $maid['availability']==='available'?'bg-success':'bg-secondary' ?>"><?= ucfirst($maid['availability']) ?></span></div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title">Services Offered</div>
                    <?php foreach ($maidServices as $s): ?>
                        <span class="badge bg-light text-dark border me-2 mb-2 p-2"><?= clean($s) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($workHistory)): ?>
                <div class="panel">
                    <div class="panel-title">Work History</div>
                    <?php foreach ($workHistory as $w): ?>
                        <div class="note-item">
                            <div class="note-meta"><?= clean($w['area']) ?> &middot; <?= ucwords(str_replace('_',' ',$w['working_type'])) ?> &middot;
                            <?= $w['start_date'] ? date('M Y', strtotime($w['start_date'])) : '' ?> - <?= $w['end_date'] ? date('M Y', strtotime($w['end_date'])) : 'Present' ?></div>
                            <?= clean($w['notes']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="panel">
                    <div class="panel-title">Reviews (<?= count($reviews) ?>)</div>
                    <?php if (empty($reviews)): ?>
                        <p class="text-muted mb-0">No reviews yet.</p>
                    <?php else: foreach ($reviews as $r): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="review-stars"><?= starRatingHtml((float) $r['overall_rating']) ?></div>
                            <p class="mb-1"><?= clean($r['comment']) ?></p>
                            <p class="text-muted small mb-0"><?= clean($r['customer_name']) ?> &middot; <?= timeAgo($r['created_at']) ?></p>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
