<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$uid = currentUserId();

if (isset($_GET['mark_all'])) {
    markAllNotificationsRead($uid);
    redirectTo('notifications.php');
}

$notifications = getNotifications($uid, 100);

$activePage = 'notifications';
$pageTitle = 'Notifications - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1>Notifications</h1>
            <a href="notifications.php?mark_all=1" class="btn btn-outline-dark btn-sm">Mark All as Read</a>
        </div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="panel p-0">
            <?php if (empty($notifications)): ?>
                <p class="text-muted p-4 mb-0 text-center">No notifications yet.</p>
            <?php else: foreach ($notifications as $n): ?>
                <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                    <div class="notif-icon"><i class="fa-solid fa-bell"></i></div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <div class="fw-semibold"><?= clean($n['title']) ?></div>
                            <div class="small text-muted"><?= timeAgo($n['created_at']) ?></div>
                        </div>
                        <div class="text-muted small"><?= clean($n['message']) ?></div>
                        <?php if (!$n['is_read']): ?>
                            <a href="mark-notification.php?id=<?= (int) $n['id'] ?>" class="small">Mark as read</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
