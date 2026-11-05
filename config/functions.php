<?php
/**
 * BUA.COM - Reusable Functions
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

/* ---------------------------------------------------------
   SANITIZATION / VALIDATION
--------------------------------------------------------- */
function clean(?string $value): string
{
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function isValidEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidPhone(string $phone): bool
{
    return (bool) preg_match('/^01[0-9]{9}$/', $phone);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ---------------------------------------------------------
   ACTIVITY LOG
--------------------------------------------------------- */
function logActivity(?int $userId, string $action, string $description = ''): void
{
    $db = Database::getConnection();
    $stmt = $db->prepare('INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $action, $description]);
}

/* ---------------------------------------------------------
   NOTIFICATIONS
--------------------------------------------------------- */
function createNotification(int $userId, string $title, string $message, string $type = 'general', ?int $relatedRequestId = null): void
{
    $db = Database::getConnection();
    $stmt = $db->prepare('INSERT INTO notifications (user_id, title, message, type, related_request_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $type, $relatedRequestId]);
}

function getUnreadNotificationCount(int $userId): int
{
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function getNotifications(int $userId, int $limit = 50): array
{
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function markNotificationRead(int $notificationId, int $userId): void
{
    $db = Database::getConnection();
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$notificationId, $userId]);
}

function markAllNotificationsRead(int $userId): void
{
    $db = Database::getConnection();
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$userId]);
}

/* Notify all users of a given role (e.g. all admins) */
function notifyRole(string $role, string $title, string $message, string $type = 'general', ?int $relatedRequestId = null): void
{
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT id FROM users WHERE role = ? AND status = "active"');
    $stmt->execute([$role]);
    foreach ($stmt->fetchAll() as $row) {
        createNotification((int) $row['id'], $title, $message, $type, $relatedRequestId);
    }
}

/* ---------------------------------------------------------
   VERIFICATION BADGE (never expose sensitive fields)
--------------------------------------------------------- */
function verificationBadge(string $status): string
{
    if ($status === 'VERIFIED') {
        return '<span class="badge-verified"><i class="fa-solid fa-circle-check"></i> Verified</span>';
    }
    return '<span class="badge-pending"><i class="fa-solid fa-circle-exclamation"></i> Pending Verification</span>';
}

/* ---------------------------------------------------------
   FILE UPLOAD (maid photo)
--------------------------------------------------------- */
function handleMaidPhotoUpload(array $file): array
{
    // Returns ['success' => bool, 'filename' => string|null, 'error' => string|null]
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null]; // photo optional
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Upload error occurred.'];
    }

    $maxSize = 3 * 1024 * 1024; // 3 MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'filename' => null, 'error' => 'Image must be smaller than 3MB.'];
    }

    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mime, $allowedMime)) {
        return ['success' => false, 'filename' => null, 'error' => 'Only JPG, PNG, and WEBP images are allowed.'];
    }

    // Double-check with getimagesize to ensure it's really an image
    if (@getimagesize($file['tmp_name']) === false) {
        return ['success' => false, 'filename' => null, 'error' => 'Invalid image file.'];
    }

    $ext = $allowedMime[$mime];
    $secureName = 'maid_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $uploadDir = __DIR__ . '/../assets/uploads/maids/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $destination = $uploadDir . $secureName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'filename' => null, 'error' => 'Failed to save uploaded image.'];
    }

    return ['success' => true, 'filename' => $secureName, 'error' => null];
}

function maidPhotoUrl(?string $photo): string
{
    if (empty($photo)) {
        return baseUrl() . '/assets/images/default-maid.png';
    }
    return baseUrl() . '/assets/uploads/maids/' . rawurlencode($photo);
}

/* ---------------------------------------------------------
   RATINGS
--------------------------------------------------------- */
function getMaidAverageRating(int $maidId): array
{
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT AVG(overall_rating) AS avg_rating, COUNT(*) AS total
                           FROM reviews WHERE maid_id = ? AND status = "visible"');
    $stmt->execute([$maidId]);
    $row = $stmt->fetch();
    return [
        'average' => $row['avg_rating'] ? round((float) $row['avg_rating'], 1) : 0.0,
        'total'   => (int) $row['total'],
    ];
}

function starRatingHtml(float $rating): string
{
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    $html = str_repeat('<i class="fa-solid fa-star"></i>', (int) $full);
    if ($half) $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
    $html .= str_repeat('<i class="fa-regular fa-star"></i>', (int) $empty);
    return $html;
}

/* ---------------------------------------------------------
   PAGINATION
--------------------------------------------------------- */
function paginate(int $totalRows, int $perPage, int $currentPage): array
{
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return compact('totalPages', 'currentPage', 'offset');
}

function renderPagination(int $currentPage, int $totalPages, string $baseQuery): string
{
    if ($totalPages <= 1) return '';
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $sep = str_contains($baseQuery, '?') ? '&' : '?';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseQuery . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

/* ---------------------------------------------------------
   STATUS BADGES
--------------------------------------------------------- */
function statusBadgeClass(string $status): string
{
    return match ($status) {
        'PENDING' => 'bg-secondary',
        'CONTACTING_MAID' => 'bg-info text-dark',
        'MAID_CONSIDERING' => 'bg-warning text-dark',
        'ACCEPTED' => 'bg-primary',
        'HIRED' => 'bg-success',
        'COMPLETED' => 'bg-dark',
        'REJECTED', 'CANCELLED' => 'bg-danger',
        'VERIFIED' => 'bg-success',
        'UNDER_REVIEW' => 'bg-warning text-dark',
        'RESOLVED' => 'bg-success',
        default => 'bg-secondary',
    };
}

function formatMoney(float $amount): string
{
    return '৳' . number_format($amount, 0);
}

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M Y', strtotime($datetime));
}
