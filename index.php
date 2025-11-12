<?php
session_start();
$env = parse_ini_file(__DIR__ . '/.env');

function e($s) { return htmlspecialchars($s, ENT_QUOTES); }
$logged = isset($_SESSION['user']);
$role = $_SESSION['role'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>DB Sync Panel</title>
  <style>
    body{font-family:Inter,system-ui,sans-serif;background:#f4f7fb;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh;}
    .card{background:white;padding:24px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.08);width:900px;}
    h2{margin-top:0;font-size:20px;}
    input,button,select{padding:10px;border:1px solid #ccc;border-radius:8px;font-size:14px;}
    button{background:#2563eb;color:white;cursor:pointer;border:none;font-weight:500;}
    button:hover{opacity:.9;}
    button:disabled{opacity:.5;cursor:not-allowed;}
    .log{background:#111;color:#eee;padding:12px;border-radius:8px;height:240px;overflow:auto;font-size:12px;white-space:pre-wrap;font-family:monospace;}
    .row{margin-bottom:12px;display:flex;gap:10px;align-items:center;}
    .small{font-size:13px;color:#666;}
    .error{color:#dc2626;background:#fee;padding:8px;border-radius:6px;margin:10px 0;}
    .success{color:#16a34a;background:#f0fdf4;padding:8px;border-radius:6px;margin:10px 0;}
    .spinner{display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin .6s linear infinite;margin-left:8px;}
    @keyframes spin{to{transform:rotate(360deg);}}
    a{color:#2563eb;text-decoration:none;}
    a:hover{text-decoration:underline;}
  </style>
</head>
<body>
<div class="card">
  <h2>Database Sync Panel</h2>

<?php if(!$logged): ?>
  <div id="loginError" style="display:none;" class="error"></div>
  <div class="row">
    <input id="username" placeholder="username" autocomplete="username">
    <input id="password" type="password" placeholder="password" autocomplete="current-password">
    <button id="loginBtn" onclick="login()">Login</button>
  </div>
  <div class="small">Use admin / goldy / piyush / tejas credentials from .env</div>
<?php else: ?>
  <div class="small">
    Logged in as <strong><?=e($_SESSION['user'])?></strong> (role: <?=e($role)?>)
    <a href="#" onclick="logout(); return false;" style="margin-left:10px;">Logout</a>
  </div>
  <hr>

  <?php if($role === 'admin'): ?>
    <h3>Admin Actions</h3>
    <div class="row">
      <select id="userToMain">
        <option value="goldy">Goldy → Main</option>
        <option value="piyush">Piyush → Main</option>
        <option value="tejas">Tejas → Main</option>
      </select>
      <button id="toMainBtn" onclick="sync('toMain')">Dump to Main</button>
    </div>
    <div class="row">
      <select id="mainToUser">
        <option value="goldy">Main → Goldy</option>
        <option value="piyush">Main → Piyush</option>
        <option value="tejas">Main → Tejas</option>
      </select>
      <button id="toUserBtn" onclick="sync('toUser')">Dump to User</button>
    </div>
  <?php else: ?>
    <h3>User Actions</h3>
    <div class="row">
      <button id="mainToMineBtn" onclick="sync('mainToMine')">Sync from Main → My DB</button>
      <button id="downloadBtn" onclick="downloadMyDB()">Download My DB SQL</button>
    </div>
  <?php endif; ?>

  <div id="statusMsg" style="display:none;"></div>
  <h4 style="margin-bottom:8px;">Log Output:</h4>
  <div class="log" id="log">No operations yet...</div>
<?php endif; ?>
</div>

<script>
let csrfToken = '';

// Fetch CSRF token when authenticated
async function fetchCSRFToken() {
  try {
    let res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=getToken'
    });
    let data = await res.json();
    if (data.success) {
      csrfToken = data.message;
    }
  } catch(e) {
    console.error('Failed to fetch CSRF token:', e);
  }
}

<?php if($logged): ?>
// Fetch CSRF token on page load if logged in
fetchCSRFToken();
<?php endif; ?>

function showError(msg) {
  let el = document.getElementById('loginError');
  if (el) {
    el.textContent = msg;
    el.style.display = 'block';
  }
}

function showStatus(msg, isSuccess) {
  let el = document.getElementById('statusMsg');
  if (el) {
    el.textContent = msg;
    el.className = isSuccess ? 'success' : 'error';
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 5000);
  }
}

async function login() {
  let btn = document.getElementById('loginBtn');
  let username = document.getElementById('username').value.trim();
  let password = document.getElementById('password').value.trim();
  
  if (!username || !password) {
    showError('Please enter username and password');
    return;
  }
  
  btn.disabled = true;
  btn.innerHTML = 'Logging in...<span class="spinner"></span>';
  
  try {
    let res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=login&username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password)
    });
    let data = await res.json();
    
    if (data.success) {
      location.reload();
    } else {
      showError(data.message);
      btn.disabled = false;
      btn.innerHTML = 'Login';
    }
  } catch(e) {
    showError('Login failed: ' + e.message);
    btn.disabled = false;
    btn.innerHTML = 'Login';
  }
}

function logout() {
  if (confirm('Are you sure you want to logout?')) {
    // Destroy session via server
    fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=logout'
    }).finally(() => {
      location.reload();
    });
  }
}

function downloadMyDB() {
  // Create a form and submit it to trigger file download
  let form = document.createElement('form');
  form.method = 'POST';
  form.action = 'actions.php';
  form.style.display = 'none';
  
  let actionInput = document.createElement('input');
  actionInput.name = 'action';
  actionInput.value = 'download';
  form.appendChild(actionInput);
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
  
  showStatus('Preparing your database download...', true);
}

async function sync(type) {
  let btnId = type === 'toMain' ? 'toMainBtn' : (type === 'toUser' ? 'toUserBtn' : 'mainToMineBtn');
  let btn = document.getElementById(btnId);
  let log = document.getElementById('log');
  
  let body = {action: type, csrf: csrfToken};
  if (type === 'toMain') body.user = document.getElementById('userToMain').value;
  if (type === 'toUser') body.user = document.getElementById('mainToUser').value;
  
  let formData = new URLSearchParams(body).toString();
  
  btn.disabled = true;
  let originalText = btn.innerHTML;
  btn.innerHTML = 'Processing...<span class="spinner"></span>';
  log.textContent = 'Starting sync operation...\n';
  
  try {
    let res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: formData
    });
    let data = await res.json();
    
    log.textContent = data.message;
    showStatus(data.success ? 'Operation completed successfully!' : 'Operation failed', data.success);
    
    // Refresh CSRF token after operation
    if (data.success) {
      await fetchCSRFToken();
    }
  } catch(e) {
    log.textContent = 'Error: ' + e.message;
    showStatus('Operation failed: ' + e.message, false);
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}

// Allow Enter key to submit login
document.addEventListener('DOMContentLoaded', function() {
  let passwordField = document.getElementById('password');
  if (passwordField) {
    passwordField.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        login();
      }
    });
  }
});
</script>
</body>
</html>
