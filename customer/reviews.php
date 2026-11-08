<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

$requestId = (int) ($_GET['request_id'] ?? $_POST['request_id'] ?? 0);
$errors = [];
$requestToReview = null;

if ($requestId > 0) {
    $stmt = $db->prepare('
        SELECT hr.*, m.name AS maid_name FROM hiring_requests hr
        JOIN maids m ON m.id = hr.maid_id
        WHERE hr.id = ? AND hr.customer_id = ? AND hr.status = "COMPLETED"
    ');
    $stmt->execute([$requestId, $uid]);
    $requestToReview = $stmt->fetch();

    if ($requestToReview) {
        $stmt = $db->prepare('SELECT id FROM reviews WHERE request_id = ?');
        $stmt->execute([$requestId]);
        if ($stmt->fetch()) {
            flash('info', 'You have already submitted a review for this hiring.');
            redirectTo('request-details.php?id=' . $requestId);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $requestToReview) {
    requireCsrf();
    $overall = (float) ($_POST['overall_rating'] ?? 0);
    $quality = (float) ($_POST['work_quality'] ?? 0);
    $behavior = (float) ($_POST['behavior_rating'] ?? 0);
    $punctuality = (float) ($_POST['punctuality_rating'] ?? 0);
    $reliability = (float) ($_POST['reliability_rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    foreach (['overall' => $overall, 'quality' => $quality, 'behavior' => $behavior, 'punctuality' => $punctuality, 'reliability' => $reliability] as $k => $v) {
        if ($v < 1 || $v > 5) $errors[] = 'Please provide all ratings (1 to 5 stars).';
    }

    if (empty($errors)) {
        // Prevent duplicate reviews for the same hiring request (also enforced by unique DB constraint)
        try {
            $stmt = $db->prepare('
                INSERT INTO reviews (customer_id, maid_id, request_id, overall_rating, work_quality, behavior_rating, punctuality_rating, reliability_rating, comment)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$uid, $requestToReview['maid_id'], $requestId, $overall, $quality, $behavior, $punctuality, $reliability, $comment]);
            notifyRole('admin', 'New Review', 'A new review was submitted for ' . $requestToReview['maid_name'] . '.', 'new_review', $requestId);
            flash('success', 'Thank you! Your review has been submitted.');
            redirectTo('request-details.php?id=' . $requestId);
        } catch (PDOException $e) {
            $errors[] = 'You have already reviewed this hiring request.';
        }
    }
}

// Customer's review history
$stmt = $db->prepare('
    SELECT r.*, m.name AS maid_name FROM reviews r
    JOIN maids m ON m.id = r.maid_id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
');
$stmt->execute([$uid]);
$myReviews = $stmt->fetchAll();

$activePage = 'reviews';
$pageTitle = 'Reviews - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Reviews</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <?php if ($requestToReview): ?>
        <div class="panel">
            <div class="panel-title">Review your hiring of <?= clean($requestToReview['maid_name']) ?></div>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="request_id" value="<?= (int) $requestId ?>">

                <?php
                $ratingFields = [
                    'overall_rating' => 'Overall Rating',
                    'work_quality' => 'Work Quality',
                    'behavior_rating' => 'Behavior',
                    'punctuality_rating' => 'Punctuality',
                    'reliability_rating' => 'Reliability',
                ];
                foreach ($ratingFields as $field => $label): ?>
                    <div class="mb-3">
                        <label class="form-label d-block"><?= $label ?></label>
                        <div class="star-input fs-3 text-warning" data-target="<?= $field ?>_input" style="cursor:pointer;">
                            <?php for ($i = 0; $i < 5; $i++): ?><i class="fa-regular fa-star"></i><?php endfor; ?>
                        </div>
                        <input type="hidden" id="<?= $field ?>_input" name="<?= $field ?>" value="0" required>
                    </div>
                <?php endforeach; ?>

                <div class="mb-3">
                    <label class="form-label">Your Comment</label>
                    <textarea name="comment" class="form-control" rows="3" placeholder="Share your experience..."></textarea>
                </div>
                <button type="submit" class="btn btn-brand px-4">Submit Review</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-title">My Submitted Reviews</div>
            <?php if (empty($myReviews)): ?>
                <p class="text-muted mb-0">You haven't submitted any reviews yet.</p>
            <?php else: foreach ($myReviews as $r): ?>
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <strong><?= clean($r['maid_name']) ?></strong>
                        <span class="text-muted small"><?= timeAgo($r['created_at']) ?></span>
                    </div>
                    <div class="review-stars"><?= starRatingHtml((float) $r['overall_rating']) ?></div>
                    <p class="mb-0"><?= clean($r['comment']) ?></p>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
