<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

$requestId = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare('
    SELECT hr.*, m.name AS maid_name, m.photo, a.area_name, s.service_name
    FROM hiring_requests hr
    JOIN maids m ON m.id = hr.maid_id
    JOIN areas a ON a.id = m.area_id
    JOIN services s ON s.id = hr.service_id
    WHERE hr.id = ? AND hr.customer_id = ?
');
$stmt->execute([$requestId, $uid]);
$request = $stmt->fetch();

if (!$request) {
    flash('danger', 'Request not found.');
    redirectTo('my-requests.php');
}

$stmt = $db->prepare('SELECT id FROM reviews WHERE request_id = ?');
$stmt->execute([$requestId]);
$hasReview = (bool) $stmt->fetch();

$statusTimeline = [
    'PENDING' => 'Request submitted, awaiting admin action.',
    'CONTACTING_MAID' => 'Our admin is currently contacting the maid.',
    'MAID_CONSIDERING' => 'The maid is considering the offer.',
    'ACCEPTED' => 'The maid is available for your requested work.',
    'REJECTED' => 'Unfortunately, the maid is not available.',
    'HIRED' => 'Your hiring request has been confirmed.',
    'COMPLETED' => 'Hiring completed. You can submit a review.',
    'CANCELLED' => 'This request has been cancelled.',
];

$activePage = 'requests';
$pageTitle = 'Request #' . $requestId . ' - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1>Request #<?= (int) $request['id'] ?></h1>
            <a href="my-requests.php" class="btn btn-outline-dark btn-sm">Back to Requests</a>
        </div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="panel-title">Request Details</div>
                        <span class="badge <?= statusBadgeClass($request['status']) ?> fs-6"><?= clean($request['status']) ?></span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Maid:</strong><br><?= clean($request['maid_name']) ?></div>
                        <div class="col-md-6"><strong>Area:</strong><br><?= clean($request['area_name']) ?></div>
                        <div class="col-md-6"><strong>Service:</strong><br><?= clean($request['service_name']) ?></div>
                        <div class="col-md-6"><strong>Preferred Time:</strong><br><?= clean($request['preferred_time'] ?: '-') ?></div>
                        <div class="col-md-6"><strong>Expected Salary:</strong><br><?= $request['expected_salary'] ? formatMoney((float)$request['expected_salary']) : '-' ?></div>
                        <div class="col-md-6"><strong>Start Date:</strong><br><?= $request['start_date'] ? date('d M Y', strtotime($request['start_date'])) : '-' ?></div>
                        <div class="col-md-6"><strong>Your Phone:</strong><br><?= clean($request['customer_phone']) ?></div>
                        <div class="col-md-6"><strong>Your Address:</strong><br><?= clean($request['customer_address']) ?></div>
                        <div class="col-12"><strong>Your Note:</strong><br><?= clean($request['customer_note'] ?: '-') ?></div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title">Status Update</div>
                    <div class="alert alert-info mb-0"><i class="fa-solid fa-circle-info"></i> <?= clean($statusTimeline[$request['status']] ?? '') ?></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="panel text-center">
                    <img src="<?= maidPhotoUrl($request['photo']) ?>" class="rounded-4 mb-3" style="width:100%;height:180px;object-fit:cover;">
                    <h6><?= clean($request['maid_name']) ?></h6>
                    <a href="maid-profile.php?id=<?= (int) $request['maid_id'] ?>" class="btn btn-outline-dark btn-sm w-100 mb-2">View Profile</a>

                    <?php if ($request['status'] === 'COMPLETED' && !$hasReview): ?>
                        <a href="reviews.php?request_id=<?= (int) $request['id'] ?>" class="btn btn-brand btn-sm w-100 mb-2">Leave a Review</a>
                    <?php elseif ($hasReview): ?>
                        <div class="alert alert-success small mb-2">You've already reviewed this hire.</div>
                    <?php endif; ?>

                    <a href="complaints.php?maid_id=<?= (int) $request['maid_id'] ?>&request_id=<?= (int) $request['id'] ?>" class="btn btn-outline-danger btn-sm w-100">Submit a Complaint</a>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
