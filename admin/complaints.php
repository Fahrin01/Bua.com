<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $complaintId = (int) ($_POST['complaint_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');

    $validStatuses = ['PENDING','UNDER_REVIEW','RESOLVED','REJECTED'];
    if ($complaintId > 0 && in_array($status, $validStatuses, true)) {
        $stmt = $db->prepare('UPDATE complaints SET status = ?, admin_note = ? WHERE id = ?');
        $stmt->execute([$status, $adminNote, $complaintId]);
        logActivity(currentUserId(), 'UPDATE_COMPLAINT', "Admin set complaint #$complaintId to $status");
        flash('success', 'Complaint updated.');
    }
    redirectTo('complaints.php');
}

$statusFilter = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($statusFilter !== '') { $where .= ' AND c.status = ?'; $params[] = $statusFilter; }

$stmt = $db->prepare("
    SELECT c.*, u.name AS customer_name, m.name AS maid_name
    FROM complaints c
    JOIN users u ON u.id = c.customer_id
    JOIN maids m ON m.id = c.maid_id
    WHERE $where
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$activePage = 'complaints';
$pageTitle = 'Manage Complaints - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Manage Complaints</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel">
            <form method="get" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach (['PENDING','UNDER_REVIEW','RESOLVED','REJECTED'] as $s): ?>
                            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php foreach ($complaints as $c): ?>
        <div class="panel">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong><?= clean($c['maid_name']) ?></strong> — <?= clean($c['reason']) ?>
                    <p class="text-muted small mb-0">By <?= clean($c['customer_name']) ?> &middot; <?= timeAgo($c['created_at']) ?></p>
                </div>
                <span class="badge <?= statusBadgeClass($c['status']) ?> fs-6"><?= clean($c['status']) ?></span>
            </div>
            <p><?= clean($c['description']) ?></p>
            <form method="post" class="row g-2">
                <?= csrfField() ?>
                <input type="hidden" name="complaint_id" value="<?= (int) $c['id'] ?>">
                <div class="col-md-4">
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach (['PENDING','UNDER_REVIEW','RESOLVED','REJECTED'] as $s): ?>
                            <option value="<?= $s ?>" <?= $c['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" name="admin_note" class="form-control form-control-sm" placeholder="Admin note" value="<?= clean($c['admin_note'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-brand w-100">Update</button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
        <?php if (empty($complaints)): ?>
            <div class="panel text-center text-muted">No complaints found.</div>
        <?php endif; ?>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
