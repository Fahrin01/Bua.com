<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();
$adminId = currentUserId();

$requestId = (int) ($_GET['id'] ?? $_POST['request_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['form_type'] ?? '';

    $stmt = $db->prepare('
        SELECT hr.*, m.name AS maid_name, u.id AS customer_id FROM hiring_requests hr
        JOIN maids m ON m.id = hr.maid_id
        JOIN users u ON u.id = hr.customer_id
        WHERE hr.id = ?
    ');
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();

    if ($req) {
        if ($action === 'status') {
            $newStatus = $_POST['status'] ?? '';
            $validStatuses = ['PENDING','CONTACTING_MAID','MAID_CONSIDERING','ACCEPTED','REJECTED','HIRED','COMPLETED','CANCELLED'];
            if (in_array($newStatus, $validStatuses, true)) {
                $stmt = $db->prepare('UPDATE hiring_requests SET status = ?, assigned_admin = ? WHERE id = ?');
                $stmt->execute([$newStatus, $adminId, $requestId]);

                $customerMessages = [
                    'PENDING' => 'Your hiring request has been submitted. Our admin will contact the maid.',
                    'CONTACTING_MAID' => 'Our admin is currently contacting the maid regarding your request.',
                    'MAID_CONSIDERING' => 'The maid is considering your request. We will update you shortly.',
                    'ACCEPTED' => 'The maid is available for your requested work.',
                    'REJECTED' => 'Unfortunately, the maid is not available.',
                    'HIRED' => 'Your hiring request has been confirmed.',
                    'COMPLETED' => 'Your hiring has been completed. You can now submit a review.',
                    'CANCELLED' => 'Your hiring request has been cancelled.',
                ];
                $notifType = 'request_' . strtolower($newStatus);
                createNotification((int) $req['customer_id'], 'Hiring Request Update', $customerMessages[$newStatus] ?? 'Your request status was updated.', $notifType, $requestId);

                logActivity($adminId, 'UPDATE_STATUS', "Admin updated request #$requestId status to $newStatus");
                flash('success', 'Request status updated to ' . $newStatus . '.');
            }
        } elseif ($action === 'note') {
            $note = trim($_POST['note'] ?? '');
            if ($note !== '') {
                $stmt = $db->prepare('INSERT INTO admin_notes (request_id, admin_id, note) VALUES (?, ?, ?)');
                $stmt->execute([$requestId, $adminId, $note]);
                flash('success', 'Note added.');
            }
        }
    }
    redirectTo('request-details.php?id=' . $requestId);
}

$stmt = $db->prepare('
    SELECT hr.*, u.name AS customer_name, u.email AS customer_email,
           m.name AS maid_name, m.phone AS maid_phone, m.photo, a.area_name, s.service_name
    FROM hiring_requests hr
    JOIN users u ON u.id = hr.customer_id
    JOIN maids m ON m.id = hr.maid_id
    JOIN areas a ON a.id = m.area_id
    JOIN services s ON s.id = hr.service_id
    WHERE hr.id = ?
');
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    flash('danger', 'Request not found.');
    redirectTo('hiring-requests.php');
}

$stmt = $db->prepare('
    SELECT an.*, u.name AS admin_name FROM admin_notes an
    JOIN users u ON u.id = an.admin_id
    WHERE an.request_id = ?
    ORDER BY an.created_at DESC
');
$stmt->execute([$requestId]);
$notes = $stmt->fetchAll();

$statuses = ['PENDING','CONTACTING_MAID','MAID_CONSIDERING','ACCEPTED','REJECTED','HIRED','COMPLETED','CANCELLED'];

$activePage = 'hiring-requests';
$pageTitle = 'Request #' . $requestId . ' - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1>Request #<?= (int) $request['id'] ?></h1>
            <a href="hiring-requests.php" class="btn btn-outline-dark btn-sm">Back to List</a>
        </div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="panel-title">Request Information</div>
                        <span class="badge <?= statusBadgeClass($request['status']) ?> fs-6"><?= clean($request['status']) ?></span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Customer:</strong><br><?= clean($request['customer_name']) ?> (<?= clean($request['customer_email']) ?>)</div>
                        <div class="col-md-6"><strong>Customer Phone:</strong><br><?= clean($request['customer_phone']) ?></div>
                        <div class="col-md-6"><strong>Maid:</strong><br><?= clean($request['maid_name']) ?></div>
                        <div class="col-md-6"><strong>Maid Phone:</strong><br><span class="badge bg-dark"><?= clean($request['maid_phone']) ?></span></div>
                        <div class="col-md-6"><strong>Area:</strong><br><?= clean($request['area_name']) ?></div>
                        <div class="col-md-6"><strong>Service:</strong><br><?= clean($request['service_name']) ?></div>
                        <div class="col-md-6"><strong>Preferred Time:</strong><br><?= clean($request['preferred_time'] ?: '-') ?></div>
                        <div class="col-md-6"><strong>Salary:</strong><br><?= $request['expected_salary'] ? formatMoney((float)$request['expected_salary']) : '-' ?></div>
                        <div class="col-md-6"><strong>Start Date:</strong><br><?= $request['start_date'] ? date('d M Y', strtotime($request['start_date'])) : '-' ?></div>
                        <div class="col-md-6"><strong>Customer Address:</strong><br><?= clean($request['customer_address']) ?></div>
                        <div class="col-12"><strong>Customer Note:</strong><br><?= clean($request['customer_note'] ?: '-') ?></div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title">Admin Notes History</div>
                    <form method="post" class="mb-3">
                        <?= csrfField() ?>
                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                        <input type="hidden" name="form_type" value="note">
                        <textarea name="note" class="form-control mb-2" rows="2" placeholder='e.g. "Called maid on 07 September. She is available from next Monday. Expected salary ৳7,000."' required></textarea>
                        <button type="submit" class="btn btn-sm btn-outline-dark">Add Note</button>
                    </form>
                    <?php if (empty($notes)): ?>
                        <p class="text-muted mb-0">No notes yet.</p>
                    <?php else: foreach ($notes as $n): ?>
                        <div class="note-item">
                            <div class="note-meta"><?= clean($n['admin_name']) ?> &middot; <?= timeAgo($n['created_at']) ?></div>
                            <?= nl2br(clean($n['note'])) ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="panel text-center">
                    <img src="<?= maidPhotoUrl($request['photo']) ?>" class="rounded-4 mb-3" style="width:100%;height:180px;object-fit:cover;">
                    <h6><?= clean($request['maid_name']) ?></h6>
                    <a href="tel:<?= clean($request['maid_phone']) ?>" class="btn btn-brand btn-sm w-100 mb-2"><i class="fa-solid fa-phone"></i> Contact Maid</a>
                    <a href="maid-details.php?id=<?= (int) $request['maid_id'] ?>" class="btn btn-outline-dark btn-sm w-100">View Maid Profile</a>
                </div>

                <div class="panel">
                    <div class="panel-title">Update Status</div>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                        <input type="hidden" name="form_type" value="status">
                        <select name="status" class="form-select mb-3">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s ?>" <?= $request['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-brand w-100">Update Status</button>
                    </form>

                    <hr>
                    <p class="small fw-semibold mb-2">Quick Actions</p>
                    <div class="d-grid gap-2">
                        <?php foreach ([
                            'CONTACTING_MAID' => 'Mark Contacting',
                            'MAID_CONSIDERING' => 'Mark Considering',
                            'ACCEPTED' => 'Accept',
                            'REJECTED' => 'Reject',
                            'HIRED' => 'Mark Hired',
                            'COMPLETED' => 'Mark Completed',
                            'CANCELLED' => 'Cancel',
                        ] as $statusVal => $label): ?>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                            <input type="hidden" name="form_type" value="status">
                            <input type="hidden" name="status" value="<?= $statusVal ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100"><?= $label ?></button>
                        </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
