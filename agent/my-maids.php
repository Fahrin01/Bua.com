<?php
require_once __DIR__ . '/../config/functions.php';
requireAgent();
$db = Database::getConnection();
$aid = currentUserId();

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['verification'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where = 'm.agent_id = ?';
$params = [$aid];
if ($q !== '') { $where .= ' AND m.name LIKE ?'; $params[] = "%$q%"; }
if ($statusFilter !== '') { $where .= ' AND v.verification_status = ?'; $params[] = $statusFilter; }

$stmt = $db->prepare("SELECT COUNT(*) FROM maids m JOIN maid_verification v ON v.maid_id = m.id WHERE $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$sql = "SELECT m.*, a.area_name, v.verification_status
        FROM maids m JOIN areas a ON a.id = m.area_id JOIN maid_verification v ON v.maid_id = m.id
        WHERE $where ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p); }
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i++, $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$maids = $stmt->fetchAll();

$activePage = 'my-maids';
$pageTitle = 'My Maids - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1>My Maids</h1>
            <a href="add-maid.php" class="btn btn-brand btn-sm"><i class="fa-solid fa-user-plus"></i> Add Maid</a>
        </div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel">
            <form method="get" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input type="text" name="q" class="form-control" placeholder="Search by name" value="<?= clean($q) ?>">
                </div>
                <div class="col-md-4">
                    <select name="verification" class="form-select">
                        <option value="">All Verification Status</option>
                        <?php foreach (['PENDING','UNDER_REVIEW','VERIFIED','REJECTED'] as $s): ?>
                            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-dark w-100">Filter</button>
                </div>
            </form>

            <?php if (empty($maids)): ?>
                <p class="text-muted mb-0">No maids found.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Photo</th><th>Name</th><th>Area</th><th>Salary</th><th>Availability</th><th>Verification</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($maids as $m): ?>
                        <tr>
                            <td><img src="<?= maidPhotoUrl($m['photo']) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:8px;"></td>
                            <td><?= clean($m['name']) ?></td>
                            <td><?= clean($m['area_name']) ?></td>
                            <td><?= formatMoney((float) $m['expected_salary']) ?></td>
                            <td><span class="badge <?= $m['availability']==='available'?'bg-success':($m['availability']==='busy'?'bg-warning text-dark':'bg-secondary') ?>"><?= ucfirst($m['availability']) ?></span></td>
                            <td><?= verificationBadge($m['verification_status']) ?></td>
                            <td class="d-flex gap-1">
                                <a href="maid-details.php?id=<?= (int) $m['id'] ?>" class="btn btn-sm btn-outline-dark">View</a>
                                <a href="edit-maid.php?id=<?= (int) $m['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $qs = $_GET; unset($qs['page']);
            echo renderPagination($pg['currentPage'], $pg['totalPages'], 'my-maids.php?' . http_build_query($qs));
            ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
