<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();
$db = Database::getConnection();

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $old = compact('name', 'email', 'phone');

    if ($name === '' || strlen($name) < 2) $errors[] = 'Please enter the agent\'s name.';
    if (!isValidEmail($email)) $errors[] = 'Please enter a valid email.';
    if (!isValidPhone($phone)) $errors[] = 'Please enter a valid phone number.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'An account with this email already exists.';
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, "agent", "active")');
        $stmt->execute([$name, $email, $phone, $hashed]);
        $newId = (int) $db->lastInsertId();

        logActivity(currentUserId(), 'CREATE_AGENT', "Admin created agent #$newId ($name)");
        flash('success', "Agent $name has been created.");
        redirectTo('agents.php');
    }
}

$activePage = 'agents';
$pageTitle = 'Add Agent - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Add New Agent</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <div class="panel" style="max-width:560px;">
            <form method="post" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= clean($old['name']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= clean($old['email']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX" required value="<?= clean($old['phone']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Temporary Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" required>
                </div>
                <button type="submit" class="btn btn-brand px-4">Create Agent</button>
            </form>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
