<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

$maidId = (int) ($_GET['maid_id'] ?? $_POST['maid_id'] ?? 0);
$stmt = $db->prepare('SELECT m.*, a.area_name FROM maids m JOIN areas a ON a.id = m.area_id WHERE m.id = ? AND m.status = "active"');
$stmt->execute([$maidId]);
$maid = $stmt->fetch();

if (!$maid) {
    flash('danger', 'Maid not found.');
    redirectTo('search-maids.php');
}

$stmt = $db->prepare('SELECT s.id, s.service_name FROM maid_services ms JOIN services s ON s.id = ms.service_id WHERE ms.maid_id = ?');
$stmt->execute([$maidId]);
$maidServices = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $preferredTime = trim($_POST['preferred_time'] ?? '');
    $expectedSalary = (float) ($_POST['expected_salary'] ?? 0);
    $startDate = trim($_POST['start_date'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $customerAddress = trim($_POST['customer_address'] ?? '');
    $customerNote = trim($_POST['customer_note'] ?? '');

    if ($serviceId <= 0) $errors[] = 'Please select a service.';
    if (!isValidPhone($customerPhone)) $errors[] = 'Please enter a valid contact phone number.';
    if ($customerAddress === '') $errors[] = 'Please enter your address.';
    if ($startDate === '' || strtotime($startDate) < strtotime(date('Y-m-d'))) $errors[] = 'Please choose a valid future start date.';

    if (empty($errors)) {
        $stmt = $db->prepare('
            INSERT INTO hiring_requests
            (customer_id, maid_id, service_id, preferred_time, expected_salary, start_date, customer_phone, customer_address, customer_note, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "PENDING")
        ');
        $stmt->execute([$uid, $maidId, $serviceId, $preferredTime, $expectedSalary ?: null, $startDate, $customerPhone, $customerAddress, $customerNote]);
        $requestId = (int) $db->lastInsertId();

        logActivity($uid, 'HIRING_REQUEST', "Customer submitted hiring request #$requestId for maid #$maidId");
        createNotification($uid, 'Request Submitted', 'Your hiring request has been submitted. Our admin will contact the maid.', 'request_submitted', $requestId);
        notifyRole('admin', 'New Hiring Request', 'A new hiring request (#' . $requestId . ') has been submitted for ' . $maid['name'] . '.', 'new_request', $requestId);

        flash('success', 'Your hiring request has been submitted successfully!');
        redirectTo('request-details.php?id=' . $requestId);
    }
}

$activePage = 'search';
$pageTitle = 'Request Maid - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Request This Maid</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="panel text-center">
                    <img src="<?= maidPhotoUrl($maid['photo']) ?>" class="rounded-4 mb-3" style="width:100%;height:220px;object-fit:cover;" alt="">
                    <h5><?= clean($maid['name']) ?></h5>
                    <p class="text-muted"><i class="fa-solid fa-location-dot"></i> <?= clean($maid['area_name']) ?></p>
                    <p class="mb-0"><?= formatMoney((float) $maid['expected_salary']) ?>/month expected</p>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="panel">
                    <form method="post" class="needs-validation" novalidate>
                        <?= csrfField() ?>
                        <input type="hidden" name="maid_id" value="<?= (int) $maid['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Service Needed</label>
                            <select name="service_id" class="form-select" required>
                                <option value="">Select a service</option>
                                <?php foreach ($maidServices as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= clean($s['service_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preferred Time</label>
                                <input type="text" name="preferred_time" class="form-control" placeholder="e.g. Morning, 8am-12pm">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expected Salary (৳)</label>
                                <input type="number" name="expected_salary" class="form-control" value="<?= (float) $maid['expected_salary'] ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Your Contact Phone</label>
                            <input type="text" name="customer_phone" class="form-control" placeholder="01XXXXXXXXX" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Your Address</label>
                            <input type="text" name="customer_address" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Additional Note</label>
                            <textarea name="customer_note" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="alert alert-info small"><i class="fa-solid fa-circle-info"></i> Your request goes only to our admin team. We'll contact the maid by phone and update you on progress.</div>
                        <button type="submit" class="btn btn-brand px-4">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
