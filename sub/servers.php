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
        <div class="d-flex align-items-center gap-2">
            <div id="bulkActions" class="d-none">
                <button class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                    <span class="material-icons-round fs-6 align-middle">delete</span> حذف
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="bulkRenamePrompt()">
                    <span class="material-icons-round fs-6 align-middle">edit</span> تغییر نام
                </button>
            </div>
            <span class="badge-soft"><?php echo $total; ?> Live</span>
        </div>
      </div>

      <div class="mb-3">
         <input class="form-control" id="search" placeholder="جستجو..." oninput="filterRows()">
      </div>
      
      <div class="table-responsive">
        <table class="table table-dark table-sm align-middle mb-0" id="srvTable">
          <thead>
            <tr>
              <th style="width: 40px">
                  <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll(this)">
              </th>
              <th>وضعیت</th>
              <th>منبع</th>
              <th>نام</th>
              <th>لینک</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$total): ?>
              <tr><td colspan="5" class="muted text-center py-4">خالی است. دکمه بروزرسانی را در داشبورد بزن.</td></tr>
            <?php else: ?>
              <?php foreach ($servers as $s): 
                  $stab = $s['stability'] ?? 100; // default for old data
                  $lat = $s['latency'] ?? 0;
                  
                  $badgeColor = 'success';
                  if ($stab < 50) $badgeColor = 'danger';
                  elseif ($stab < 80) $badgeColor = 'warning';
              ?>
                <tr data-id="<?php echo $s['id']; ?>">
                   <td>
                       <input type="checkbox" class="form-check-input server-checkbox" value="<?php echo $s['id']; ?>" onchange="updateBulkState()">
                   </td>
                   <td>
                       <span class="badge bg-<?php echo $badgeColor; ?>">
                           <?php echo $stab; ?>%
                       </span>
                       <?php if($lat > 0): ?>
                           <span class="small muted ms-1"><?php echo $lat; ?>ms</span>
                       <?php endif; ?>
                   </td>
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

<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-dark">
      <div class="modal-header">
        <h5 class="modal-title">تغییر نام گروهی</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>یک نام جدید وارد کنید. این نام به انتهای کانفیگ‌ها (بعد از #) اضافه می‌شود. سیستم به صورت خودکار شماره‌گذاری می‌کند (مثلاً Name_1, Name_2).</p>
        <input type="text" class="form-control ltr" id="newBulkName" placeholder="Example: MyVPN">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">لغو</button>
        <button type="button" class="btn btn-primary" onclick="bulkRenameExec()">ذخیره</button>
      </div>
    </div>
  </div>
</div>

<script>
function filterRows(){
  const q = (document.getElementById('search').value || '').toLowerCase().trim();
  const rows = document.querySelectorAll('#srvTable tbody tr');
  rows.forEach(r => {
    // Skip checking checkboxes row itself? No, filter applies to rows
    const t = r.textContent.toLowerCase();
    r.style.display = t.includes(q) ? '' : 'none';
  });
}

function toggleSelectAll(el) {
    document.querySelectorAll('.server-checkbox').forEach(cb => {
        // Only select visible rows
        if (cb.closest('tr').style.display !== 'none') {
            cb.checked = el.checked;
        }
    });
    updateBulkState();
}

function updateBulkState() {
    const count = document.querySelectorAll('.server-checkbox:checked').length;
    const div = document.getElementById('bulkActions');
    if (count > 0) {
        div.classList.remove('d-none');
    } else {
        div.classList.add('d-none');
    }
}

function getSelectedIds() {
    const ids = [];
    document.querySelectorAll('.server-checkbox:checked').forEach(cb => ids.push(cb.value));
    return ids;
}

async function bulkDelete() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    if (!confirm('آیا از حذف ' + ids.length + ' سرور انتخاب شده مطمئن هستید؟')) return;

    const fd = new FormData();
    fd.append('ids', ids.join(','));
    
    try {
        const res = await fetch('api.php?action=bulk_delete', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status === 'ok') {
            location.reload();
        } else {
            alert('خطا: ' + data.message);
        }
    } catch (e) {
        alert('خطای شبکه');
    }
}

let renameModal;
function bulkRenamePrompt() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
    renameModal.show();
}

async function bulkRenameExec() {
    const name = document.getElementById('newBulkName').value.trim();
    if (!name) {
        alert('نام را وارد کنید');
        return;
    }
    const ids = getSelectedIds();
    const fd = new FormData();
    fd.append('ids', ids.join(','));
    fd.append('new_name', name);
    
    try {
        const res = await fetch('api.php?action=bulk_rename', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status === 'ok') {
            location.reload();
        } else {
            alert('خطا: ' + data.message);
        }
    } catch (e) {
        alert('خطای شبکه');
    }
}
</script>
<?php include __DIR__ . '/layout_footer.php'; ?>
