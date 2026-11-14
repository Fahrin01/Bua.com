<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'add') {
        $name = trim($_POST['area_name'] ?? '');
        if ($name !== '') {
            try {
                $stmt = $db->prepare('INSERT INTO areas (area_name, status) VALUES (?, "active")');
                $stmt->execute([$name]);
                flash('success', 'Area added.');
            } catch (PDOException $e) {
                flash('danger', 'This area already exists.');
            }
        }
    } elseif ($formType === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? 'active';
        $stmt = $db->prepare('UPDATE areas SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $id]);
        flash('success', 'Area status updated.');
    } elseif ($formType === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['area_name'] ?? '');
        if ($id > 0 && $name !== '') {
            $stmt = $db->prepare('UPDATE areas SET area_name = ? WHERE id = ?');
            $stmt->execute([$name, $id]);
            flash('success', 'Area updated.');
        }
    }
    redirectTo('areas.php');
}

$areas = $db->query('
    SELECT a.*, (SELECT COUNT(*) FROM maids m WHERE m.area_id = a.id) AS maid_count
    FROM areas a ORDER BY a.area_name
')->fetchAll();

$activePage = 'areas';
$pageTitle = 'Manage Areas - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Manage Areas</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel" style="max-width:500px;">
            <div class="panel-title">Add New Area</div>
            <form method="post" class="d-flex gap-2">
                <?= csrfField() ?>
                <input type="hidden" name="form_type" value="add">
                <input type="text" name="area_name" class="form-control" placeholder="e.g. Rampura" required>
                <button type="submit" class="btn btn-brand">Add</button>
            </form>
        </div>

        <div class="panel">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Area Name</th><th>Maids</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($areas as $a): ?>
                        <tr>
                            <td>
                                <form method="post" class="d-flex gap-2 align-items-center">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="form_type" value="edit">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <input type="text" name="area_name" class="form-control form-control-sm" value="<?= clean($a['area_name']) ?>" style="max-width:200px;">
                                    <button type="submit" class="btn btn-sm btn-outline-dark">Save</button>
                                </form>
                            </td>
                            <td><?= (int) $a['maid_count'] ?></td>
                            <td><span class="badge <?= $a['status']==='active'?'bg-success':'bg-secondary' ?>"><?= ucfirst($a['status']) ?></span></td>
                            <td>
                                <form method="post" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="form_type" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <?php if ($a['status'] === 'active'): ?>
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
