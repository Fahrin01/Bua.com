<?php
require_once __DIR__ . '/../config/functions.php';
requireAgent();
$db = Database::getConnection();
$aid = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $maidId = (int) ($_POST['maid_id'] ?? 0);

    // Ensure this maid belongs to the agent
    $stmt = $db->prepare('SELECT id, name FROM maids WHERE id = ? AND agent_id = ?');
    $stmt->execute([$maidId, $aid]);
    $maid = $stmt->fetch();

    if ($maid) {
        $nidVerified = isset($_POST['nid_verified']) ? 1 : 0;
        $refVerified = isset($_POST['reference_verified']) ? 1 : 0;
        $notes = trim($_POST['verification_notes'] ?? '');

        $newStatus = ($nidVerified && $refVerified) ? 'UNDER_REVIEW' : 'PENDING';

        $stmt = $db->prepare('
            UPDATE maid_verification
            SET nid_verified = ?, reference_verified = ?, verification_notes = ?, verification_status = ?
            WHERE maid_id = ?
        ');
        $stmt->execute([$nidVerified, $refVerified, $notes, $newStatus, $maidId]);

        logActivity($aid, 'UPDATE_VERIFICATION', "Agent updated verification checks for maid #$maidId");
        notifyRole('admin', 'Verification Update', $maid['name'] . ' verification checks updated by agent. Ready for admin review.', 'verification_pending');

        flash('success', 'Verification information updated. Awaiting admin approval.');
    }
    redirectTo('verification.php');
}

$stmt = $db->prepare('
    SELECT m.id, m.name, m.photo, a.area_name, v.*
    FROM maids m
    JOIN areas a ON a.id = m.area_id
    JOIN maid_verification v ON v.maid_id = m.id
    WHERE m.agent_id = ?
    ORDER BY (v.verification_status = "VERIFIED"), v.verification_status, m.created_at DESC
');
$stmt->execute([$aid]);
$maids = $stmt->fetchAll();

$activePage = 'verification';
$pageTitle = 'Verification - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Maid Verification</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>
        <p class="text-muted">Confirm identity and reference checks here. Final approval is granted only by Admin.</p>

        <?php foreach ($maids as $m): ?>
        <div class="panel">
            <div class="row align-items-center">
                <div class="col-md-2 text-center mb-3 mb-md-0">
                    <img src="<?= maidPhotoUrl($m['photo']) ?>" style="width:70px;height:70px;object-fit:cover;border-radius:12px;">
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <strong><?= clean($m['name']) ?></strong><br>
                    <span class="text-muted small"><?= clean($m['area_name']) ?></span><br>
                    <?= verificationBadge($m['verification_status']) ?>
                </div>
                <div class="col-md-7">
                    <form method="post" class="row g-2 align-items-start">
                        <?= csrfField() ?>
                        <input type="hidden" name="maid_id" value="<?= (int) $m['id'] ?>">
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="nid_verified" id="nid<?= $m['id'] ?>" <?= $m['nid_verified'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="nid<?= $m['id'] ?>">NID Verified</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reference_verified" id="ref<?= $m['id'] ?>" <?= $m['reference_verified'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ref<?= $m['id'] ?>">Reference Verified</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <textarea name="verification_notes" class="form-control form-control-sm" rows="2" placeholder="Verification notes..."><?= clean($m['verification_notes'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-outline-dark">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($maids)): ?>
            <div class="panel text-center text-muted">You have no registered maids yet.</div>
        <?php endif; ?>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
