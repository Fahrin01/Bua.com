<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    markNotificationRead($id, currentUserId());
}
redirectTo('notifications.php');
