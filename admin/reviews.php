<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($reviewId > 0 && $action === 'hide') {
        $db->prepare('UPDATE reviews SET status = "hidden" WHERE id = ?')->execute([$reviewId]);
        flash('success', 'Review hidden from public profile.');
    } elseif ($reviewId > 0 && $action === 'show') {
        $db->prepare('UPDATE reviews SET status = "visible" WHERE id = ?')->execute([$reviewId]);
        flash('success', 'Review is now visible.');
    } elseif ($reviewId > 0 && $action === 'delete') {
        $db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$reviewId]);
        flash('info', 'Review deleted.');
    }
    logActivity(currentUserId(), 'MODERATE_REVIEW', "Admin performed '$action' on review #$reviewId");
    redirectTo('reviews.php');
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$total = (int) $db->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
$pg = paginate($total, $perPage, $page);

$stmt = $db->prepare('
    SELECT r.*, u.name AS customer_name, m.name AS maid_name
    FROM reviews r
    JOIN users u ON u.id = r.customer_id
    JOIN maids m ON m.id = r.maid_id
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
');
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

$activePage = 'reviews';
$pageTitle = 'Manage Reviews - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Manage Reviews</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel">
            <?php if (empty($reviews)): ?>
                <p class="text-muted mb-0">No reviews yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Customer</th><th>Maid</th><th>Rating</th><th>Comment</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($reviews as $r): ?>
                        <tr>
                            <td><?= clean($r['customer_name']) ?></td>
                            <td><?= clean($r['maid_name']) ?></td>
                            <td><?= starRatingHtml((float) $r['overall_rating']) ?></td>
                            <td style="max-width:250px;"><?= clean($r['comment']) ?></td>
                            <td><span class="badge <?= $r['status']==='visible'?'bg-success':'bg-secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
                            <td class="d-flex gap-1">
                                <form method="post">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
                                    <?php if ($r['status'] === 'visible'): ?>
                                        <input type="hidden" name="action" value="hide">
                                        <button class="btn btn-sm btn-outline-secondary">Hide</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="show">
                                        <button class="btn btn-sm btn-outline-success">Show</button>
                                    <?php endif; ?>
                                </form>
                                <form method="post" onsubmit="return confirm('Delete this review permanently?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php echo renderPagination($pg['currentPage'], $pg['totalPages'], 'reviews.php'); ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
