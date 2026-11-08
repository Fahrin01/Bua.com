<?php
require_once __DIR__ . '/../config/functions.php';
requireAgent();
$db = Database::getConnection();
$aid = currentUserId();

$areas = $db->query('SELECT * FROM areas WHERE status="active" ORDER BY area_name')->fetchAll();
$services = $db->query('SELECT * FROM services WHERE status="active" ORDER BY service_name')->fetchAll();

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $old = $_POST;
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

    $nidNumber = trim($_POST['nid_number'] ?? '');

    if ($name === '' || strlen($name) < 2) $errors[] = 'Please enter the maid\'s name.';
    if (!isValidPhone($phone)) $errors[] = 'Please enter a valid phone number.';
    if ($areaId <= 0) $errors[] = 'Please select an area.';
    if ($experience < 0 || $experience > 60) $errors[] = 'Please enter valid years of experience.';
    if ($salary <= 0) $errors[] = 'Please enter expected salary.';
    if (empty($serviceIds)) $errors[] = 'Please select at least one service.';

    $photoUpload = ['success' => true, 'filename' => null];
    if (!empty($_FILES['photo']['name'])) {
        $photoUpload = handleMaidPhotoUpload($_FILES['photo']);
        if (!$photoUpload['success']) $errors[] = $photoUpload['error'];
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('
                INSERT INTO maids (agent_id, name, phone, photo, area_id, permanent_district, experience_years, expected_salary, working_type, preferred_time, current_households, availability, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active")
            ');
            $stmt->execute([$aid, $name, $phone, $photoUpload['filename'], $areaId, $district, $experience, $salary, $workingType, $preferredTime, $households, $availability]);
            $maidId = (int) $db->lastInsertId();

            $svcStmt = $db->prepare('INSERT INTO maid_services (maid_id, service_id) VALUES (?, ?)');
            foreach ($serviceIds as $sid) {
                $svcStmt->execute([$maidId, (int) $sid]);
            }

            $stmt = $db->prepare('
                INSERT INTO maid_verification (maid_id, agent_verified, verification_status, nid_number)
                VALUES (?, 1, "PENDING", ?)
            ');
            $stmt->execute([$maidId, $nidNumber ?: null]);

            if ($refName !== '' && $refPhone !== '') {
                $stmt = $db->prepare('
                    INSERT INTO maid_references (maid_id, reference_name, reference_area, reference_phone, relationship)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([$maidId, $refName, $refArea, $refPhone, $refRelation]);
            }

            $db->commit();

            logActivity($aid, 'ADD_MAID', "Agent added maid #$maidId ($name)");
            notifyRole('admin', 'New Maid Awaiting Verification', "$name has been registered by an agent and is awaiting verification.", 'verification_pending');

            flash('success', "$name has been added and is now awaiting admin verification.");
            redirectTo('my-maids.php');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Something went wrong while saving. Please try again.';
        }
    }
}

$activePage = 'add-maid';
$pageTitle = 'Add Maid - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Add New Maid</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?= csrfField() ?>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="panel text-center">
                        <img id="photoPreview" src="<?= baseUrl() ?>/assets/images/default-maid.png" class="rounded-4 mb-3" style="width:100%;height:220px;object-fit:cover;">
                        <label class="form-label">Maid Photo (JPG/PNG/WEBP, max 3MB)</label>
                        <input type="file" name="photo" id="photo" accept=".jpg,.jpeg,.png,.webp" class="form-control">
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="panel">
                        <div class="panel-title">Basic Information</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" required value="<?= clean($old['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX" required value="<?= clean($old['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Area</label>
                                <select name="area_id" class="form-select" required>
                                    <option value="">Select area</option>
                                    <?php foreach ($areas as $a): ?>
                                        <option value="<?= (int) $a['id'] ?>" <?= ($old['area_id'] ?? '') == $a['id'] ? 'selected' : '' ?>><?= clean($a['area_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Permanent District</label>
                                <input type="text" name="permanent_district" class="form-control" value="<?= clean($old['permanent_district'] ?? '') ?>">
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
                        <input type="number" name="experience_years" class="form-control" min="0" required value="<?= clean($old['experience_years'] ?? '0') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expected Salary (৳)</label>
                        <input type="number" name="expected_salary" class="form-control" min="0" required value="<?= clean($old['expected_salary'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Working Type</label>
                        <select name="working_type" class="form-select">
                            <?php foreach (['full_time'=>'Full Time','part_time'=>'Part Time','live_in'=>'Live-in','hourly'=>'Hourly'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= ($old['working_type'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Availability</label>
                        <select name="availability" class="form-select">
                            <?php foreach (['available'=>'Available','busy'=>'Busy','unavailable'=>'Unavailable'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= ($old['availability'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Preferred Time</label>
                        <input type="text" name="preferred_time" class="form-control" placeholder="e.g. Morning" value="<?= clean($old['preferred_time'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Current Households</label>
                        <input type="number" name="current_households" class="form-control" min="0" value="<?= clean($old['current_households'] ?? '0') ?>">
                    </div>
                </div>
                <label class="form-label mt-3">Services Offered</label>
                <div class="row">
                    <?php foreach ($services as $s): ?>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="services[]" value="<?= (int) $s['id'] ?>" id="svc<?= $s['id'] ?>"
                                    <?= isset($old['services']) && in_array($s['id'], $old['services']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="svc<?= $s['id'] ?>"><?= clean($s['service_name']) ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-title">Verification Information</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">NID Number (kept private, never shown publicly)</label>
                        <input type="text" name="nid_number" class="form-control" value="<?= clean($old['nid_number'] ?? '') ?>">
                    </div>
                </div>
                <hr>
                <h6 class="fw-semibold">Reference (optional, kept private)</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Reference Name</label>
                        <input type="text" name="reference_name" class="form-control" value="<?= clean($old['reference_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference Area</label>
                        <input type="text" name="reference_area" class="form-control" value="<?= clean($old['reference_area'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference Phone</label>
                        <input type="text" name="reference_phone" class="form-control" value="<?= clean($old['reference_phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="relationship" class="form-control" placeholder="e.g. Previous employer" value="<?= clean($old['relationship'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-brand px-5">Save Maid</button>
        </form>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
