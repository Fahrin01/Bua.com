<?php
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    $role = currentRole();
    redirectTo(baseUrl() . "/$role/dashboard.php");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Your account has been ' . $user['status'] . '. Please contact support.';
        } else {
            loginUser($user);
            logActivity((int) $user['id'], 'LOGIN', $user['role'] . ' logged in');
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirectTo(baseUrl() . '/' . $user['role'] . '/dashboard.php');
        }
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $errors[] = 'You are not authorized to view that page. Please log in with the correct account.';
}

$pageTitle = 'Login - BUA.COM';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand"><a href="<?= baseUrl() ?>/index.php" class="text-dark">BUA<span>.COM</span></a></div>
        <h2 class="text-center">Login</h2>
        <p class="sub text-center">Customer, Agent, or Admin — sign in below.</p>

        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?= clean($e) ?></div>
        <?php endforeach; ?>
        <?php include __DIR__ . '/includes/alerts.php'; ?>

        <form method="post" class="needs-validation" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required value="<?= clean($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2 mt-2">Login</button>
        </form>
        <p class="text-center mt-3 mb-0">Don't have an account? <a href="<?= baseUrl() ?>/register.php">Register as Customer</a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
