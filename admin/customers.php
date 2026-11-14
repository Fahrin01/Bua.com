<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId > 0 && in_array($action, ['activate','suspend'], true)) {
        $newStatus = $action === 'activate' ? 'active' : 'suspended';
        $stmt = $db->prepare('UPDATE users SET status = ? WHERE id = ? AND role = "customer"');
        $stmt->execute([$newStatus, $userId]);
        logActivity(currentUserId(), 'UPDATE_CUSTOMER_STATUS', "Admin set customer #$userId to $newStatus");
        flash('success', 'Customer status updated.');
    }
    redirectTo('customers.php');
}

$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$where = 'role = "customer"';
$params = [];
if ($q !== '') { $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)'; $params = ["%$q%","%$q%","%$q%"]; }

$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$sql = "SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p); }
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i++, $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll();

$activePage = 'customers';
$pageTitle = 'Manage Customers - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Manage Customers</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel">
            <form method="get" class="row g-2 mb-3">
                <div class="col-md-6"><input type="text" name="q" class="form-control" placeholder="Search name, email, or phone" value="<?= clean($q) ?>"></div>
                <div class="col-md-2"><button class="btn btn-outline-dark w-100">Search</button></div>
            </form>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><?= clean($c['name']) ?></td>
                            <td><?= clean($c['email']) ?></td>
                            <td><?= clean($c['phone']) ?></td>
                            <td><span class="badge <?= $c['status']==='active'?'bg-success':'bg-danger' ?>"><?= ucfirst($c['status']) ?></span></td>
                            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <form method="post" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $c['id'] ?>">
                                    <?php if ($c['status'] === 'active'): ?>
                                        <input type="hidden" name="action" value="suspend">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Suspend this customer?')">Suspend</button>
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
            <?php
            $qs = $_GET; unset($qs['page']);
            echo renderPagination($pg['currentPage'], $pg['totalPages'], 'customers.php?' . http_build_query($qs));
            ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
