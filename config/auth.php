<?php
/**
 * BUA.COM - Authentication & Authorization
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/database.php';

/** Determine site base path for redirects, works whether app sits in a subfolder or not */
function baseUrl(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Strip trailing role folder (customer/agent/admin) to get project root
    $root = preg_replace('#/(customer|agent|admin)$#', '', $scriptDir);
    return rtrim($root, '/');
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

function currentUserName(): ?string
{
    return $_SESSION['name'] ?? null;
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function redirectTo(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirectTo(baseUrl() . '/login.php');
    }
}

function requireCustomer(): void
{
    requireLogin();
    if (currentRole() !== 'customer') {
        redirectTo(baseUrl() . '/login.php?error=unauthorized');
    }
}

function requireAgent(): void
{
    requireLogin();
    if (currentRole() !== 'agent') {
        redirectTo(baseUrl() . '/login.php?error=unauthorized');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (currentRole() !== 'admin') {
        redirectTo(baseUrl() . '/login.php?error=unauthorized');
    }
}

/** CSRF protection */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrf()) {
        die('Invalid CSRF token. Please go back and try again.');
    }
}
