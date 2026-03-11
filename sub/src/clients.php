<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

$settings = load_json('settings.json', ["subscription_token" => ""]);
$token = $settings['subscription_token'] ?? '';

$page_title = 'مدیریت کاربران';
include __DIR__ . '/layout_header.php';
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="app-card p-3 p-lg-4">
      <div class="fw-bold mb-2">افزودن کاربر</div>
      <div class="muted mb-3">ساخت اکانت جدید برای اشتراک.</div>
      <form id="addClientForm">
        <div class="mb-2">
          <label class="form-label">نام کاربری</label>
          <input class="form-control" name="name" placeholder="مثلاً: ali" required>
        </div>
        <div class="mb-3">
          <label class="form-label">محدودیت حجم (GB)</label>
          <input type="number" step="0.1" class="form-control ltr" name="limit" placeholder="0 = نامحدود" required>
        </div>
        <button class="btn btn-accent w-100 fw-bold" type="submit">افزودن</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="app-card p-3 p-lg-4">
      <div class="fw-bold mb-3">لیست کاربران</div>
      <div class="table-responsive">
        <table class="table table-dark table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>نام</th>
              <th>لینک اشتراک</th>
              <th>محدودیت</th>
              <th>تاریخ ساخت</th>
              <th class="text-end">عملیات</th>
            </tr>
          </thead>
          <tbody id="clientTableBody">
             <!-- JS loads here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const subToken = "<?php echo h($token); ?>";
const baseUrl = window.location.origin + window.location.pathname.replace('clients.php', 'sub.php');

async function loadClients() {
    const res = await fetch('api.php?action=client_list');
    const data = await res.json();
    const tbody = document.getElementById('clientTableBody');
    tbody.innerHTML = '';
    
    if(!data.clients || data.clients.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center muted py-3">کاربری یافت نشد.</td></tr>';
        return;
    }
    
    data.clients.forEach(c => {
        const limit = c.limit_gb > 0 ? c.limit_gb + ' GB' : '∞';
        const link = baseUrl + '?user=' + encodeURIComponent(c.username) + (subToken ? '&token=' + encodeURIComponent(subToken) : '');
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${c.username}</td>
            <td>
                <button class="btn btn-sm btn-ghost" onclick="copyToClip('${link}')">
                    <i class="bi bi-copy"></i> کپی
                </button>
            </td>
            <td>${limit}</td>
            <td class="small muted">${c.created_at.split('T')[0]}</td>
            <td class="text-end">
                <button class="btn btn-outline-danger btn-sm" onclick="deleteClient('${c.id}')">حذف</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function copyToClip(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('لینک کپی شد!');
    }, (err) => {
        alert('خطا در کپی: ' + err);
    });
}

document.getElementById('addClientForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
        const res = await fetch('api.php?action=client_add', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if(data.status === 'ok') {
            e.target.reset();
            loadClients();
        } else {
            alert('خطا: ' + data.message);
        }
    } catch(err) {
        alert('خطای شبکه');
    }
});

async function deleteClient(id) {
    if(!confirm('حذف شود؟')) return;
    const fd = new FormData();
    fd.append('id', id);
    await fetch('api.php?action=client_delete', { method: 'POST', body: fd });
    loadClients();
}

loadClients();
</script>

<?php include __DIR__ . '/layout_footer.php'; ?>
