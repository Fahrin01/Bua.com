<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

$agentId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM users WHERE id = ? AND role = "agent"');
$stmt->execute([$agentId]);
$agent = $stmt->fetch();

if (!$agent) {
    flash('danger', 'Agent not found.');
    redirectTo('agents.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $newPassword = $_POST['new_password'] ?? '';

    if ($name === '' || strlen($name) < 2) $errors[] = 'Please enter the agent\'s name.';
    if (!isValidPhone($phone)) $errors[] = 'Please enter a valid phone number.';
    if (!in_array($status, ['active','inactive'], true)) $errors[] = 'Invalid status.';

    if (empty($errors)) {
        if ($newPassword !== '' && strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
    }

    if (empty($errors)) {
        if ($newPassword !== '') {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET name=?, phone=?, status=?, password=? WHERE id=?');
            $stmt->execute([$name, $phone, $status, $hashed, $agentId]);
        } else {
            $stmt = $db->prepare('UPDATE users SET name=?, phone=?, status=? WHERE id=?');
            $stmt->execute([$name, $phone, $status, $agentId]);
        }
        logActivity(currentUserId(), 'UPDATE_AGENT', "Admin updated agent #$agentId");
        flash('success', 'Agent updated successfully.');
        redirectTo('agents.php');
    }
}

$activePage = 'agents';
$pageTitle = 'Edit Agent - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Edit Agent: <?= clean($agent['name']) ?></h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <div class="panel" style="max-width:560px;">
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int) $agent['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= clean($agent['name']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email (cannot be changed)</label>
                    <input type="email" class="form-control" value="<?= clean($agent['email']) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" required value="<?= clean($agent['phone']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $agent['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $agent['status']==='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reset Password (optional)</label>
                    <input type="password" name="new_password" class="form-control" minlength="6" placeholder="Leave blank to keep current password">
                </div>
                <button type="submit" class="btn btn-brand px-4">Save Changes</button>
            </form>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
