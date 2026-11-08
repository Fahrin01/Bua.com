<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

$prefMaidId = (int) ($_GET['maid_id'] ?? 0);
$prefRequestId = (int) ($_GET['request_id'] ?? 0);
$errors = [];

// Maids the customer has hired (so the dropdown only shows relevant ones)
$stmt = $db->prepare('
    SELECT DISTINCT m.id, m.name, hr.id AS request_id
    FROM hiring_requests hr
    JOIN maids m ON m.id = hr.maid_id
    WHERE hr.customer_id = ?
    ORDER BY hr.created_at DESC
');
$stmt->execute([$uid]);
$hiredMaids = $stmt->fetchAll();

$reasons = ['Misconduct','False information','Work abandonment','Payment dispute','Unprofessional behavior','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $maidId = (int) ($_POST['maid_id'] ?? 0);
    $requestId = (int) ($_POST['request_id'] ?? 0) ?: null;
    $reason = $_POST['reason'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if ($maidId <= 0) $errors[] = 'Please select a maid.';
    if (!in_array($reason, $reasons, true)) $errors[] = 'Please select a valid reason.';
    if (strlen($description) < 10) $errors[] = 'Please describe the issue in more detail (at least 10 characters).';

    if (empty($errors)) {
        $stmt = $db->prepare('
            INSERT INTO complaints (customer_id, maid_id, request_id, reason, description, status)
            VALUES (?, ?, ?, ?, ?, "PENDING")
        ');
        $stmt->execute([$uid, $maidId, $requestId, $reason, $description]);
        $complaintId = (int) $db->lastInsertId();

        notifyRole('admin', 'New Complaint', 'A new complaint (#' . $complaintId . ') has been submitted.', 'new_complaint');
        flash('success', 'Your complaint has been submitted. Our admin team will review it.');
        redirectTo('complaints.php');
    }
}

$stmt = $db->prepare('
    SELECT c.*, m.name AS maid_name FROM complaints c
    JOIN maids m ON m.id = c.maid_id
    WHERE c.customer_id = ?
    ORDER BY c.created_at DESC
');
$stmt->execute([$uid]);
$myComplaints = $stmt->fetchAll();

$activePage = 'complaints';
$pageTitle = 'Complaints - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Complaints</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <div class="panel">
            <div class="panel-title">Submit a Complaint</div>
            <form method="post">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Maid</label>
                    <select name="maid_id" id="complaintMaidSelect" class="form-select" required>
                        <option value="">Select a maid you hired</option>
                        <?php foreach ($hiredMaids as $hm): ?>
                            <option value="<?= (int) $hm['id'] ?>" data-request="<?= (int) $hm['request_id'] ?>" <?= $prefMaidId === (int) $hm['id'] ? 'selected' : '' ?>><?= clean($hm['name']) ?> (Request #<?= (int) $hm['request_id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="request_id" id="complaintRequestId" value="<?= $prefRequestId ?: '' ?>">
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <select name="reason" class="form-select" required>
                        <option value="">Select a reason</option>
                        <?php foreach ($reasons as $r): ?>
                            <option value="<?= $r ?>"><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4" required placeholder="Describe what happened..."></textarea>
                </div>
                <button type="submit" class="btn btn-brand px-4">Submit Complaint</button>
            </form>
        </div>

        <div class="panel">
            <div class="panel-title">My Complaints</div>
            <?php if (empty($myComplaints)): ?>
                <p class="text-muted mb-0">You haven't submitted any complaints.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Maid</th><th>Reason</th><th>Status</th><th>Admin Note</th><th>Submitted</th></tr></thead>
                    <tbody>
                    <?php foreach ($myComplaints as $c): ?>
                        <tr>
                            <td><?= clean($c['maid_name']) ?></td>
                            <td><?= clean($c['reason']) ?></td>
                            <td><span class="badge <?= statusBadgeClass($c['status']) ?>"><?= clean($c['status']) ?></span></td>
                            <td><?= clean($c['admin_note'] ?: '-') ?></td>
                            <td><?= timeAgo($c['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
document.getElementById('complaintMaidSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('complaintRequestId').value = opt.dataset.request || '';
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
