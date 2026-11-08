<?php
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    logActivity(currentUserId(), 'LOGOUT', currentRole() . ' logged out');
}
logoutUser();
session_start();
flash('info', 'You have been logged out successfully.');
redirectTo(baseUrl() . '/login.php');
