<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();
$adminId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $maidId = (int) ($_POST['maid_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['verification_notes'] ?? '');

    $stmt = $db->prepare('SELECT m.name, m.agent_id FROM maids m WHERE m.id = ?');
    $stmt->execute([$maidId]);
    $maid = $stmt->fetch();

    if ($maid) {
        if ($action === 'approve') {
            $stmt = $db->prepare('
                UPDATE maid_verification
                SET admin_verified = 1, verification_status = "VERIFIED", verification_notes = ?, verified_by = ?, verified_at = NOW()
                WHERE maid_id = ?
            ');
            $stmt->execute([$notes, $adminId, $maidId]);
            createNotification($maid['agent_id'], 'Maid Approved', $maid['name'] . ' has been verified and approved by admin.', 'maid_approved');
            logActivity($adminId, 'APPROVE_VERIFICATION', "Admin approved maid #$maidId");
            flash('success', $maid['name'] . ' has been verified and approved.');
        } elseif ($action === 'reject') {
            $stmt = $db->prepare('
                UPDATE maid_verification
                SET admin_verified = 0, verification_status = "REJECTED", verification_notes = ?, verified_by = ?, verified_at = NOW()
                WHERE maid_id = ?
            ');
            $stmt->execute([$notes, $adminId, $maidId]);
            createNotification($maid['agent_id'], 'Maid Verification Rejected', $maid['name'] . '\'s verification was rejected: ' . $notes, 'maid_rejected');
            logActivity($adminId, 'REJECT_VERIFICATION', "Admin rejected maid #$maidId");
            flash('info', $maid['name'] . '\'s verification has been rejected.');
        } elseif ($action === 'under_review') {
            $stmt = $db->prepare('UPDATE maid_verification SET verification_status = "UNDER_REVIEW", verification_notes = ? WHERE maid_id = ?');
            $stmt->execute([$notes, $maidId]);
            createNotification($maid['agent_id'], 'Verification Issue', $maid['name'] . ' needs further verification: ' . $notes, 'verification_issue');
            flash('info', 'Marked as under review, agent notified.');
        }
    }
    redirectTo('verification.php');
}

$statusFilter = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($statusFilter !== '') { $where .= ' AND v.verification_status = ?'; $params[] = $statusFilter; }

$stmt = $db->prepare("
    SELECT m.id, m.name, m.photo, a.area_name, u.name AS agent_name, v.*
    FROM maids m
    JOIN areas a ON a.id = m.area_id
    JOIN users u ON u.id = m.agent_id
    JOIN maid_verification v ON v.maid_id = m.id
    WHERE $where
    ORDER BY FIELD(v.verification_status,'UNDER_REVIEW','PENDING','REJECTED','VERIFIED'), m.created_at DESC
");
$stmt->execute($params);
$maids = $stmt->fetchAll();

$activePage = 'verification';
$pageTitle = 'Maid Verification - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Maid Verification</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel">
            <form method="get" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach (['PENDING','UNDER_REVIEW','VERIFIED','REJECTED'] as $s): ?>
                            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php foreach ($maids as $m): ?>
        <div class="panel">
            <div class="row align-items-start">
                <div class="col-md-2 text-center mb-3">
                    <img src="<?= maidPhotoUrl($m['photo']) ?>" style="width:70px;height:70px;object-fit:cover;border-radius:12px;">
                </div>
                <div class="col-md-3 mb-3">
                    <strong><?= clean($m['name']) ?></strong><br>
                    <span class="text-muted small"><?= clean($m['area_name']) ?> &middot; Agent: <?= clean($m['agent_name']) ?></span><br>
                    <?= verificationBadge($m['verification_status']) ?>
                    <p class="small mt-2 mb-0">NID: <?= $m['nid_verified'] ? '✅' : '❌' ?> | Reference: <?= $m['reference_verified'] ? '✅' : '❌' ?> | Agent: <?= $m['agent_verified'] ? '✅' : '❌' ?></p>
                </div>
                <div class="col-md-7">
                    <form method="post" class="row g-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="maid_id" value="<?= (int) $m['id'] ?>">
                        <div class="col-12">
                            <textarea name="verification_notes" class="form-control form-control-sm" rows="2" placeholder="Verification notes (e.g. called maid, confirmed identity)"><?= clean($m['verification_notes'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                            <button type="submit" name="action" value="under_review" class="btn btn-sm btn-warning">Mark Under Review</button>
                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                            <a href="maid-details.php?id=<?= (int) $m['id'] ?>" class="btn btn-sm btn-outline-dark">Full Details</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($maids)): ?>
            <div class="panel text-center text-muted">No maids found for this filter.</div>
        <?php endif; ?>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
