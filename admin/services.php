<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'add') {
        $name = trim($_POST['service_name'] ?? '');
        if ($name !== '') {
            try {
                $stmt = $db->prepare('INSERT INTO services (service_name, status) VALUES (?, "active")');
                $stmt->execute([$name]);
                flash('success', 'Service added.');
            } catch (PDOException $e) {
                flash('danger', 'This service already exists.');
            }
        }
    } elseif ($formType === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? 'active';
        $stmt = $db->prepare('UPDATE services SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $id]);
        flash('success', 'Service status updated.');
    } elseif ($formType === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['service_name'] ?? '');
        if ($id > 0 && $name !== '') {
            $stmt = $db->prepare('UPDATE services SET service_name = ? WHERE id = ?');
            $stmt->execute([$name, $id]);
            flash('success', 'Service updated.');
        }
    }
    redirectTo('services.php');
}

$services = $db->query('
    SELECT s.*, (SELECT COUNT(*) FROM maid_services ms WHERE ms.service_id = s.id) AS maid_count
    FROM services s ORDER BY s.service_name
')->fetchAll();

$activePage = 'services';
$pageTitle = 'Manage Services - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Manage Services</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel" style="max-width:500px;">
            <div class="panel-title">Add New Service</div>
            <form method="post" class="d-flex gap-2">
                <?= csrfField() ?>
                <input type="hidden" name="form_type" value="add">
                <input type="text" name="service_name" class="form-control" placeholder="e.g. Gardening" required>
                <button type="submit" class="btn btn-brand">Add</button>
            </form>
        </div>

        <div class="panel">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Service Name</th><th>Maids Offering</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr>
                            <td>
                                <form method="post" class="d-flex gap-2 align-items-center">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="form_type" value="edit">
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                    <input type="text" name="service_name" class="form-control form-control-sm" value="<?= clean($s['service_name']) ?>" style="max-width:220px;">
                                    <button type="submit" class="btn btn-sm btn-outline-dark">Save</button>
                                </form>
                            </td>
                            <td><?= (int) $s['maid_count'] ?></td>
                            <td><span class="badge <?= $s['status']==='active'?'bg-success':'bg-secondary' ?>"><?= ucfirst($s['status']) ?></span></td>
                            <td>
                                <form method="post" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="form_type" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                    <?php if ($s['status'] === 'active'): ?>
                                        <input type="hidden" name="new_status" value="inactive">
                                        <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="active">
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
