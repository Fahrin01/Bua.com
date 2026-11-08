<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;

$where = 'hr.customer_id = ?';
$params = [$uid];
if ($statusFilter !== '') {
    $where .= ' AND hr.status = ?';
    $params[] = $statusFilter;
}

$stmt = $db->prepare("SELECT COUNT(*) FROM hiring_requests hr WHERE $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$sql = "SELECT hr.*, m.name AS maid_name, m.photo, a.area_name, s.service_name
        FROM hiring_requests hr
        JOIN maids m ON m.id = hr.maid_id
        JOIN areas a ON a.id = m.area_id
        JOIN services s ON s.id = hr.service_id
        WHERE $where
        ORDER BY hr.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p); }
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i++, $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll();

$statuses = ['PENDING','CONTACTING_MAID','MAID_CONSIDERING','ACCEPTED','REJECTED','HIRED','COMPLETED','CANCELLED'];

$activePage = 'requests';
$pageTitle = 'My Requests - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>My Requests</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel">
            <form method="get" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if (empty($requests)): ?>
                <p class="text-muted mb-0">No hiring requests found. <a href="search-maids.php">Find a maid to hire</a></p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>Maid</th><th>Service</th><th>Area</th><th>Start Date</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>#<?= (int) $r['id'] ?></td>
                            <td><?= clean($r['maid_name']) ?></td>
                            <td><?= clean($r['service_name']) ?></td>
                            <td><?= clean($r['area_name']) ?></td>
                            <td><?= $r['start_date'] ? date('d M Y', strtotime($r['start_date'])) : '-' ?></td>
                            <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= clean($r['status']) ?></span></td>
                            <td><a href="request-details.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-dark">Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $qs = $_GET; unset($qs['page']);
            echo renderPagination($pg['currentPage'], $pg['totalPages'], 'my-requests.php?' . http_build_query($qs));
            ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
