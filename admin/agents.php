<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId > 0 && in_array($action, ['activate','deactivate'], true)) {
        $newStatus = $action === 'activate' ? 'active' : 'inactive';
        $stmt = $db->prepare('UPDATE users SET status = ? WHERE id = ? AND role = "agent"');
        $stmt->execute([$newStatus, $userId]);
        logActivity(currentUserId(), 'UPDATE_AGENT_STATUS', "Admin set agent #$userId to $newStatus");
        flash('success', 'Agent status updated.');
    }
    redirectTo('agents.php');
}

$stmt = $db->query('
    SELECT u.*, (SELECT COUNT(*) FROM maids m WHERE m.agent_id = u.id) AS maid_count
    FROM users u WHERE u.role = "agent" ORDER BY u.created_at DESC
');
$agents = $stmt->fetchAll();

$activePage = 'agents';
$pageTitle = 'Manage Agents - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1>Manage Agents</h1>
            <a href="add-agent.php" class="btn btn-brand btn-sm"><i class="fa-solid fa-plus"></i> Add Agent</a>
        </div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Maids Registered</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($agents as $a): ?>
                        <tr>
                            <td><?= clean($a['name']) ?></td>
                            <td><?= clean($a['email']) ?></td>
                            <td><?= clean($a['phone']) ?></td>
                            <td><?= (int) $a['maid_count'] ?></td>
                            <td><span class="badge <?= $a['status']==='active'?'bg-success':'bg-secondary' ?>"><?= ucfirst($a['status']) ?></span></td>
                            <td class="d-flex gap-1">
                                <a href="edit-agent.php?id=<?= (int) $a['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $a['id'] ?>">
                                    <?php if ($a['status'] === 'active'): ?>
                                        <input type="hidden" name="action" value="deactivate">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Deactivate this agent?')">Deactivate</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="activate">
                                        <button class="btn btn-sm btn-outline-success">Activate</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
