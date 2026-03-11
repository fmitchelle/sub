<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
  <div class="d-flex align-items-center gap-3 mb-3">
    <div class="dot"></div>
    <div>
      <div class="fw-bold"><?php echo h($settings['title'] ?? 'Subscription Panel'); ?></div>
      <div class="muted small">مدیریت حرفه‌ای Subscription</div>
    </div>
  </div>

  <a class="navpill <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
    <span class="badge-soft"><i class="bi bi-house-door"></i></span>
    <div>
      <div class="fw-semibold">داشبورد</div>
    </div>
  </a>

  <a class="navpill <?php echo $current_page == 'manual.php' ? 'active' : ''; ?>" href="manual.php">
    <span class="badge-soft"><i class="bi bi-pencil-square"></i></span>
    <div>
      <div class="fw-semibold">دستی</div>
    </div>
  </a>
  
  <a class="navpill <?php echo $current_page == 'sources.php' ? 'active' : ''; ?>" href="sources.php">
    <span class="badge-soft"><i class="bi bi-globe"></i></span>
    <div>
      <div class="fw-semibold">منابع</div>
    </div>
  </a>

  <a class="navpill <?php echo $current_page == 'clients.php' ? 'active' : ''; ?>" href="clients.php">
    <span class="badge-soft"><i class="bi bi-people"></i></span>
    <div>
      <div class="fw-semibold">کاربران</div>
    </div>
  </a>
  
  <a class="navpill <?php echo $current_page == 'servers.php' ? 'active' : ''; ?>" href="servers.php">
    <span class="badge-soft"><i class="bi bi-hdd-network"></i></span>
    <div>
      <div class="fw-semibold">سرورها</div>
    </div>
  </a>

  <a class="navpill <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
    <span class="badge-soft"><i class="bi bi-gear"></i></span>
    <div>
      <div class="fw-semibold">تنظیمات</div>
    </div>
  </a>

  <div class="mt-auto pt-3 border-top border-secondary border-opacity-10">
      <a class="navpill text-danger" href="logout.php">
        <span class="badge-soft bg-danger bg-opacity-10 text-danger"><i class="bi bi-box-arrow-right"></i></span>
        <div>خروج</div>
      </a>
  </div>
</div>
