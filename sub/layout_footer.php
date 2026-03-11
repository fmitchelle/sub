</div> <!-- .container -->

<!-- Bottom Nav -->
<nav class="bottom-nav">
  <?php $cp = basename($_SERVER['PHP_SELF']); ?>
  
  <a href="dashboard.php" class="bottom-nav-item <?php echo $cp == 'dashboard.php' ? 'active' : ''; ?>">
    <i class="bi bi-house-door fs-5"></i>
    <span>داشبورد</span>
  </a>
  
  <a href="manual.php" class="bottom-nav-item <?php echo $cp == 'manual.php' ? 'active' : ''; ?>">
    <i class="bi bi-pencil-square fs-5"></i>
    <span>دستی</span>
  </a>

  <a href="sources.php" class="bottom-nav-item <?php echo $cp == 'sources.php' ? 'active' : ''; ?>">
    <i class="bi bi-globe fs-5"></i>
    <span>منابع</span>
  </a>

  <a href="clients.php" class="bottom-nav-item <?php echo $cp == 'clients.php' ? 'active' : ''; ?>">
    <i class="bi bi-people fs-5"></i>
    <span>کاربران</span>
  </a>

  <a href="servers.php" class="bottom-nav-item <?php echo $cp == 'servers.php' ? 'active' : ''; ?>">
    <i class="bi bi-hdd-network fs-5"></i>
    <span>سرورها</span>
  </a>

  <a href="settings.php" class="bottom-nav-item <?php echo $cp == 'settings.php' ? 'active' : ''; ?>">
    <i class="bi bi-gear fs-5"></i>
    <span>تنظیمات</span>
  </a>
</nav>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyText(id){
  var el = document.getElementById(id);
  el.select();
  document.execCommand('copy');
  alert('کپی شد!');
}
</script>
</body>
</html>
