<?php
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    redirectTo(baseUrl() . '/' . currentRole() . '/dashboard.php');
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $old = compact('name', 'email', 'phone');

    if ($name === '' || strlen($name) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!isValidPhone($phone)) {
        $errors[] = 'Please enter a valid Bangladeshi phone number (e.g. 017XXXXXXXX).';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, "customer", "active")');
        $stmt->execute([$name, $email, $phone, $hashed]);
        $userId = (int) $db->lastInsertId();

        logActivity($userId, 'REGISTER', 'New customer registered');
        createNotification($userId, 'Welcome to BUA.COM', 'Your account has been created. Start searching for a verified maid today!', 'welcome');

        $user = ['id' => $userId, 'role' => 'customer', 'name' => $name, 'email' => $email];
        loginUser($user);
        flash('success', 'Registration successful! Welcome, ' . $name . '.');
        redirectTo(baseUrl() . '/customer/dashboard.php');
    }
}

$pageTitle = 'Register - BUA.COM';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand"><a href="<?= baseUrl() ?>/index.php" class="text-dark">BUA<span>.COM</span></a></div>
        <h2 class="text-center">Create an Account</h2>
        <p class="sub text-center">Register as a customer to search and hire verified maids.</p>

        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?= clean($e) ?></div>
        <?php endforeach; ?>

        <form method="post" class="needs-validation" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required value="<?= clean($old['name']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required value="<?= clean($old['email']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX" required value="<?= clean($old['phone']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2 mt-2">Register</button>
        </form>
        <p class="text-center mt-3 mb-0">Already have an account? <a href="<?= baseUrl() ?>/login.php">Login</a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
