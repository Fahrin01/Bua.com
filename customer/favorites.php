<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

// Handle add/remove POST from other pages (search, profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $maidId = (int) ($_POST['maid_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($maidId > 0 && $action === 'add') {
        $stmt = $db->prepare('INSERT IGNORE INTO favorites (customer_id, maid_id) VALUES (?, ?)');
        $stmt->execute([$uid, $maidId]);
        flash('success', 'Added to favorites.');
    } elseif ($maidId > 0 && $action === 'remove') {
        $stmt = $db->prepare('DELETE FROM favorites WHERE customer_id = ? AND maid_id = ?');
        $stmt->execute([$uid, $maidId]);
        flash('info', 'Removed from favorites.');
    }

    $redirect = $_POST['redirect'] ?? 'favorites.php';
    redirectTo($redirect);
}

$stmt = $db->prepare('
    SELECT m.*, a.area_name, v.verification_status,
        (SELECT AVG(overall_rating) FROM reviews WHERE maid_id = m.id AND status="visible") AS avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE maid_id = m.id AND status="visible") AS review_count
    FROM favorites f
    JOIN maids m ON m.id = f.maid_id
    JOIN areas a ON a.id = m.area_id
    JOIN maid_verification v ON v.maid_id = m.id
    WHERE f.customer_id = ?
    ORDER BY f.created_at DESC
');
$stmt->execute([$uid]);
$favorites = $stmt->fetchAll();

$activePage = 'favorites';
$pageTitle = 'Favorites - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>My Favorites</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <?php if (empty($favorites)): ?>
            <div class="panel text-center text-muted">You haven't added any favorites yet. <a href="search-maids.php">Browse maids</a></div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favorites as $m):
                $avg = $m['avg_rating'] ? round((float) $m['avg_rating'], 1) : 0.0; ?>
                <div class="col-md-4">
                    <div class="maid-card">
                        <img src="<?= maidPhotoUrl($m['photo']) ?>" class="maid-photo" alt="<?= clean($m['name']) ?>">
                        <div class="maid-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="maid-card-name"><?= clean($m['name']) ?></div>
                                    <div class="maid-card-area"><i class="fa-solid fa-location-dot"></i> <?= clean($m['area_name']) ?></div>
                                </div>
                                <?= verificationBadge($m['verification_status']) ?>
                            </div>
                            <div class="review-stars small"><?= starRatingHtml($avg) ?> <span class="text-muted">(<?= (int) $m['review_count'] ?>)</span></div>
                            <div class="d-flex gap-2 mt-3">
                                <a href="maid-profile.php?id=<?= (int) $m['id'] ?>" class="btn btn-brand btn-sm flex-grow-1">View Profile</a>
                                <form method="post">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="maid_id" value="<?= (int) $m['id'] ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="fav-btn active"><i class="fa-solid fa-heart"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
