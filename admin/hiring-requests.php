<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

$statusFilter = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where = '1=1';
$params = [];
if ($statusFilter !== '') { $where .= ' AND hr.status = ?'; $params[] = $statusFilter; }
if ($q !== '') { $where .= ' AND (u.name LIKE ? OR m.name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

$stmt = $db->prepare("
    SELECT COUNT(*) FROM hiring_requests hr
    JOIN users u ON u.id = hr.customer_id
    JOIN maids m ON m.id = hr.maid_id
    WHERE $where
");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$sql = "
    SELECT hr.*, u.name AS customer_name, u.phone AS customer_phone_acct, m.name AS maid_name, s.service_name
    FROM hiring_requests hr
    JOIN users u ON u.id = hr.customer_id
    JOIN maids m ON m.id = hr.maid_id
    JOIN services s ON s.id = hr.service_id
    WHERE $where
    ORDER BY hr.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p); }
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i++, $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll();

$statuses = ['PENDING','CONTACTING_MAID','MAID_CONSIDERING','ACCEPTED','REJECTED','HIRED','COMPLETED','CANCELLED'];

$activePage = 'hiring-requests';
$pageTitle = 'Hiring Requests - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Hiring Requests</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel">
            <form method="get" class="row g-2 mb-3">
                <div class="col-md-5"><input type="text" name="q" class="form-control" placeholder="Search customer or maid" value="<?= clean($q) ?>"></div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><button class="btn btn-outline-dark w-100">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>Customer</th><th>Maid</th><th>Service</th><th>Start Date</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>#<?= (int) $r['id'] ?></td>
                            <td><?= clean($r['customer_name']) ?></td>
                            <td><?= clean($r['maid_name']) ?></td>
                            <td><?= clean($r['service_name']) ?></td>
                            <td><?= $r['start_date'] ? date('d M Y', strtotime($r['start_date'])) : '-' ?></td>
                            <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= clean($r['status']) ?></span></td>
                            <td><a href="request-details.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-brand">Manage</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $qs = $_GET; unset($qs['page']);
            echo renderPagination($pg['currentPage'], $pg['totalPages'], 'hiring-requests.php?' . http_build_query($qs));
            ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
