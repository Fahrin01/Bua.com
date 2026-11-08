<?php
require_once __DIR__ . '/../config/functions.php';
requireAgent();
$db = Database::getConnection();
$aid = currentUserId();

$maidId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM maids WHERE id = ? AND agent_id = ?');
$stmt->execute([$maidId, $aid]);
$maid = $stmt->fetch();

if (!$maid) {
    flash('danger', 'Maid not found or you do not have access to edit this record.');
    redirectTo('my-maids.php');
}

$areas = $db->query('SELECT * FROM areas WHERE status="active" ORDER BY area_name')->fetchAll();
$services = $db->query('SELECT * FROM services WHERE status="active" ORDER BY service_name')->fetchAll();

$stmt = $db->prepare('SELECT service_id FROM maid_services WHERE maid_id = ?');
$stmt->execute([$maidId]);
$currentServices = array_column($stmt->fetchAll(), 'service_id');

$stmt = $db->prepare('SELECT * FROM maid_references WHERE maid_id = ? LIMIT 1');
$stmt->execute([$maidId]);
$reference = $stmt->fetch() ?: [];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $areaId = (int) ($_POST['area_id'] ?? 0);
    $district = trim($_POST['permanent_district'] ?? '');
    $experience = (int) ($_POST['experience_years'] ?? 0);
    $salary = (float) ($_POST['expected_salary'] ?? 0);
    $workingType = $_POST['working_type'] ?? 'full_time';
    $preferredTime = trim($_POST['preferred_time'] ?? '');
    $households = (int) ($_POST['current_households'] ?? 0);
    $availability = $_POST['availability'] ?? 'available';
    $serviceIds = $_POST['services'] ?? [];

    $refName = trim($_POST['reference_name'] ?? '');
    $refArea = trim($_POST['reference_area'] ?? '');
    $refPhone = trim($_POST['reference_phone'] ?? '');
    $refRelation = trim($_POST['relationship'] ?? '');

    if ($name === '' || strlen($name) < 2) $errors[] = 'Please enter the maid\'s name.';
    if (!isValidPhone($phone)) $errors[] = 'Please enter a valid phone number.';
    if ($areaId <= 0) $errors[] = 'Please select an area.';
    if (empty($serviceIds)) $errors[] = 'Please select at least one service.';

    $newPhoto = $maid['photo'];
    if (!empty($_FILES['photo']['name'])) {
        $upload = handleMaidPhotoUpload($_FILES['photo']);
        if (!$upload['success']) {
            $errors[] = $upload['error'];
        } else {
            $newPhoto = $upload['filename'];
        }
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('
                UPDATE maids SET name=?, phone=?, photo=?, area_id=?, permanent_district=?, experience_years=?,
                    expected_salary=?, working_type=?, preferred_time=?, current_households=?, availability=?
                WHERE id = ? AND agent_id = ?
            ');
            $stmt->execute([$name, $phone, $newPhoto, $areaId, $district, $experience, $salary, $workingType, $preferredTime, $households, $availability, $maidId, $aid]);

            $db->prepare('DELETE FROM maid_services WHERE maid_id = ?')->execute([$maidId]);
            $svcStmt = $db->prepare('INSERT INTO maid_services (maid_id, service_id) VALUES (?, ?)');
            foreach ($serviceIds as $sid) {
                $svcStmt->execute([$maidId, (int) $sid]);
            }

            if ($refPhone !== '') {
                if (!empty($reference)) {
                    $stmt = $db->prepare('UPDATE maid_references SET reference_name=?, reference_area=?, reference_phone=?, relationship=? WHERE maid_id = ?');
                    $stmt->execute([$refName, $refArea, $refPhone, $refRelation, $maidId]);
                } else {
                    $stmt = $db->prepare('INSERT INTO maid_references (maid_id, reference_name, reference_area, reference_phone, relationship) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$maidId, $refName, $refArea, $refPhone, $refRelation]);
                }
            }

            $db->commit();
            logActivity($aid, 'UPDATE_MAID', "Agent updated maid #$maidId ($name)");
            flash('success', 'Maid information updated successfully.');
            redirectTo('maid-details.php?id=' . $maidId);
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Something went wrong while saving. Please try again.';
        }
    }
}

$activePage = 'my-maids';
$pageTitle = 'Edit ' . clean($maid['name']) . ' - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Edit Maid: <?= clean($maid['name']) ?></h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $maid['id'] ?>">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="panel text-center">
                        <img id="photoPreview" src="<?= maidPhotoUrl($maid['photo']) ?>" class="rounded-4 mb-3" style="width:100%;height:220px;object-fit:cover;">
                        <label class="form-label">Replace Photo (optional)</label>
                        <input type="file" name="photo" id="photo" accept=".jpg,.jpeg,.png,.webp" class="form-control">
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="panel">
                        <div class="panel-title">Basic Information</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" required value="<?= clean($maid['name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" required value="<?= clean($maid['phone']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Area</label>
                                <select name="area_id" class="form-select" required>
                                    <?php foreach ($areas as $a): ?>
                                        <option value="<?= (int) $a['id'] ?>" <?= $maid['area_id'] == $a['id'] ? 'selected' : '' ?>><?= clean($a['area_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Permanent District</label>
                                <input type="text" name="permanent_district" class="form-control" value="<?= clean($maid['permanent_district']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-title">Work Details</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Experience (years)</label>
                        <input type="number" name="experience_years" class="form-control" min="0" value="<?= (int) $maid['experience_years'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expected Salary (৳)</label>
                        <input type="number" name="expected_salary" class="form-control" min="0" value="<?= (float) $maid['expected_salary'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Working Type</label>
                        <select name="working_type" class="form-select">
                            <?php foreach (['full_time'=>'Full Time','part_time'=>'Part Time','live_in'=>'Live-in','hourly'=>'Hourly'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $maid['working_type'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Availability</label>
                        <select name="availability" class="form-select">
                            <?php foreach (['available'=>'Available','busy'=>'Busy','unavailable'=>'Unavailable'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $maid['availability'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Preferred Time</label>
                        <input type="text" name="preferred_time" class="form-control" value="<?= clean($maid['preferred_time']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Current Households</label>
                        <input type="number" name="current_households" class="form-control" min="0" value="<?= (int) $maid['current_households'] ?>">
                    </div>
                </div>
                <label class="form-label mt-3">Services Offered</label>
                <div class="row">
                    <?php foreach ($services as $s): ?>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="services[]" value="<?= (int) $s['id'] ?>" id="svc<?= $s['id'] ?>"
                                    <?= in_array($s['id'], $currentServices) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="svc<?= $s['id'] ?>"><?= clean($s['service_name']) ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-title">Reference (private)</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Reference Name</label>
                        <input type="text" name="reference_name" class="form-control" value="<?= clean($reference['reference_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference Area</label>
                        <input type="text" name="reference_area" class="form-control" value="<?= clean($reference['reference_area'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference Phone</label>
                        <input type="text" name="reference_phone" class="form-control" value="<?= clean($reference['reference_phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="relationship" class="form-control" value="<?= clean($reference['relationship'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-brand px-5">Update Maid</button>
        </form>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
