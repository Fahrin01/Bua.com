<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $maidId = (int) ($_POST['maid_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($maidId > 0 && in_array($action, ['activate','deactivate'], true)) {
        $newStatus = $action === 'activate' ? 'active' : 'inactive';
        $stmt = $db->prepare('UPDATE maids SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $maidId]);
        logActivity(currentUserId(), 'UPDATE_MAID_STATUS', "Admin set maid #$maidId to $newStatus");
        flash('success', 'Maid status updated.');
    }
    redirectTo('maids.php');
}

$q = trim($_GET['q'] ?? '');
$areaFilter = (int) ($_GET['area'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$areas = $db->query('SELECT * FROM areas ORDER BY area_name')->fetchAll();

$where = '1=1';
$params = [];
if ($q !== '') { $where .= ' AND m.name LIKE ?'; $params[] = "%$q%"; }
if ($areaFilter > 0) { $where .= ' AND m.area_id = ?'; $params[] = $areaFilter; }

$stmt = $db->prepare("SELECT COUNT(*) FROM maids m WHERE $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$sql = "SELECT m.*, a.area_name, u.name AS agent_name, v.verification_status
        FROM maids m
        JOIN areas a ON a.id = m.area_id
        JOIN users u ON u.id = m.agent_id
        JOIN maid_verification v ON v.maid_id = m.id
        WHERE $where ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p); }
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i++, $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$maids = $stmt->fetchAll();

$activePage = 'maids';
$pageTitle = 'Manage Maids - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Manage Maids</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel">
            <form method="get" class="row g-2 mb-3">
                <div class="col-md-5"><input type="text" name="q" class="form-control" placeholder="Search by name" value="<?= clean($q) ?>"></div>
                <div class="col-md-4">
                    <select name="area" class="form-select">
                        <option value="">All Areas</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= (int) $a['id'] ?>" <?= $areaFilter == $a['id'] ? 'selected' : '' ?>><?= clean($a['area_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><button class="btn btn-outline-dark w-100">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Photo</th><th>Name</th><th>Agent</th><th>Area</th><th>Verification</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($maids as $m): ?>
                        <tr>
                            <td><img src="<?= maidPhotoUrl($m['photo']) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:8px;"></td>
                            <td><?= clean($m['name']) ?></td>
                            <td><?= clean($m['agent_name']) ?></td>
                            <td><?= clean($m['area_name']) ?></td>
                            <td><?= verificationBadge($m['verification_status']) ?></td>
                            <td><span class="badge <?= $m['status']==='active'?'bg-success':'bg-secondary' ?>"><?= ucfirst($m['status']) ?></span></td>
                            <td class="d-flex gap-1">
                                <a href="maid-details.php?id=<?= (int) $m['id'] ?>" class="btn btn-sm btn-outline-dark">View</a>
                                <form method="post" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="maid_id" value="<?= (int) $m['id'] ?>">
                                    <?php if ($m['status'] === 'active'): ?>
                                        <input type="hidden" name="action" value="deactivate">
                                        <button class="btn btn-sm btn-outline-danger">Deactivate</button>
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
            echo renderPagination($pg['currentPage'], $pg['totalPages'], 'maids.php?' . http_build_query($qs));
            ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
