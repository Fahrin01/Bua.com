<nav class="navbar navbar-expand-lg navbar-dark bua-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= baseUrl() ?>/index.php">BUA<span class="brand-accent">.COM</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link" href="<?= baseUrl() ?>/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= baseUrl() ?>/customer/search-maids.php">Find a Maid</a></li>
        <?php if (isLoggedIn() && currentRole() === 'customer'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= baseUrl() ?>/customer/dashboard.php">Dashboard</a></li>
          <li class="nav-item">
            <a class="nav-link position-relative" href="<?= baseUrl() ?>/customer/notifications.php">
              <i class="fa-regular fa-bell"></i>
              <?php $unread = getUnreadNotificationCount(currentUserId()); if ($unread > 0): ?>
                <span class="notif-dot"><?= $unread ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="<?= baseUrl() ?>/logout.php">Logout</a></li>
        <?php elseif (isLoggedIn() && currentRole() === 'agent'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= baseUrl() ?>/agent/dashboard.php">Agent Panel</a></li>
          <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="<?= baseUrl() ?>/logout.php">Logout</a></li>
        <?php elseif (isLoggedIn() && currentRole() === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= baseUrl() ?>/admin/dashboard.php">Admin Panel</a></li>
          <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="<?= baseUrl() ?>/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= baseUrl() ?>/login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-brand btn-sm ms-lg-2" href="<?= baseUrl() ?>/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
