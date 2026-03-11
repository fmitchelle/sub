<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

$servers = load_json('servers.json', []);
$total = count($servers);
$page_title = 'لیست سرورهای فعال';

include __DIR__ . '/layout_header.php';
?>
<div class="row g-3 g-lg-4">
  <div class="col-lg-3 d-none d-lg-block">
    <?php include __DIR__ . '/sidebar.php'; ?>
  </div>

  <div class="col-lg-9">
    <div class="app-card p-3 p-lg-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
           <div class="fw-bold fs-5">سرورهای خروجی</div>
           <div class="muted small">این سرورها در لینک اشتراک وجود دارند.</div>
        </div>
        <span class="badge-soft"><?php echo $total; ?> Live</span>
      </div>

      <div class="mb-3">
         <input class="form-control" id="search" placeholder="جستجو..." oninput="filterRows()">
      </div>
      
      <div class="table-responsive">
        <table class="table table-dark table-sm align-middle mb-0" id="srvTable">
          <thead>
            <tr>
              <th>منبع</th>
              <th>نام</th>
              <th>لینک</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$total): ?>
              <tr><td colspan="3" class="muted text-center py-4">خالی است. دکمه بروزرسانی را در داشبورد بزن.</td></tr>
            <?php else: ?>
              <?php foreach ($servers as $s): ?>
                <tr>
                   <td>
                     <?php if(($s['origin'] ?? '') === 'manual'): ?>
                        <span class="badge bg-warning text-dark">دستی</span>
                     <?php else: ?>
                        <span class="badge bg-secondary">Auto</span>
                     <?php endif; ?>
                   </td>
                  <td class="fw-semibold"><?php echo h($s['name'] ?? 'Server'); ?></td>
                  <td class="mono small" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?php echo h($s['uri'] ?? ''); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function filterRows(){
  const q = (document.getElementById('search').value || '').toLowerCase().trim();
  const rows = document.querySelectorAll('#srvTable tbody tr');
  rows.forEach(r => {
    const t = r.textContent.toLowerCase();
    r.style.display = t.includes(q) ? '' : 'none';
  });
}
</script>
<?php include __DIR__ . '/layout_footer.php'; ?>
