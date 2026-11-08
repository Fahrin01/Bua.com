<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name === '' || strlen($name) < 2) $errors[] = 'Please enter your full name.';
        if (!isValidPhone($phone)) $errors[] = 'Please enter a valid phone number.';

        if (empty($errors)) {
            $stmt = $db->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?');
            $stmt->execute([$name, $phone, $uid]);
            $_SESSION['name'] = $name;
            flash('success', 'Profile updated successfully.');
            redirectTo('profile.php');
        }
    } elseif ($formType === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hashed, $uid]);
            flash('success', 'Password changed successfully.');
            redirectTo('profile.php');
        }
    }
}

$activePage = 'profile';
$pageTitle = 'My Profile - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>My Profile</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="panel">
                    <div class="panel-title">Profile Information</div>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="form_type" value="profile">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= clean($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email (cannot be changed)</label>
                            <input type="email" class="form-control" value="<?= clean($user['email']) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= clean($user['phone']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-brand px-4">Save Changes</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="panel">
                    <div class="panel-title">Change Password</div>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="form_type" value="password">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                        </div>
                        <button type="submit" class="btn btn-outline-dark px-4">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
