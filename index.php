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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Sync Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }
    
    body::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
      background-size: 50px 50px;
      animation: moveBackground 20s linear infinite;
    }
    
    @keyframes moveBackground {
      0% { transform: translate(0, 0); }
      100% { transform: translate(50px, 50px); }
    }
    
    .container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      padding: 40px;
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
      width: 100%;
      max-width: 950px;
      position: relative;
      z-index: 1;
      animation: slideUp 0.6s ease-out;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .header {
      text-align: center;
      margin-bottom: 35px;
      position: relative;
    }
    
    .header::after {
      content: '';
      position: absolute;
      bottom: -15px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background: linear-gradient(90deg, #667eea, #764ba2);
      border-radius: 2px;
    }
    
    h2 {
      font-size: 32px;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 8px;
    }
    
    .subtitle {
      font-size: 14px;
      color: #64748b;
      font-weight: 500;
    }
    
    .login-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
      max-width: 400px;
      margin: 0 auto;
    }
    
    .input-group {
      position: relative;
    }
    
    input[type="text"],
    input[type="password"],
    select {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 15px;
      font-family: 'Inter', sans-serif;
      transition: all 0.3s ease;
      background: #fff;
      font-weight: 500;
    }
    
    input:focus, select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }
    
    button {
      padding: 14px 28px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Inter', sans-serif;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
      position: relative;
      overflow: hidden;
    }
    
    button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
    }
    
    button:hover::before {
      left: 100%;
    }
    
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }
    
    button:active {
      transform: translateY(0);
    }
    
    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none !important;
    }
    
    .user-info {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
      border-radius: 12px;
      margin-bottom: 30px;
      border: 1px solid rgba(102, 126, 234, 0.2);
    }
    
    .user-badge {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 16px;
    }
    
    .user-details {
      display: flex;
      flex-direction: column;
    }
    
    .username {
      font-weight: 600;
      font-size: 15px;
      color: #1e293b;
    }
    
    .role {
      font-size: 13px;
      color: #64748b;
      font-weight: 500;
    }
    
    .logout-btn {
      padding: 8px 16px;
      font-size: 13px;
      background: #fff;
      color: #667eea;
      border: 2px solid #667eea;
      box-shadow: none;
    }
    
    .logout-btn:hover {
      background: #667eea;
      color: #fff;
    }
    
    .section {
      margin-bottom: 25px;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .section-title::before {
      content: '';
      width: 4px;
      height: 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 2px;
    }
    
    .action-row {
      display: flex;
      gap: 12px;
      margin-bottom: 12px;
      align-items: stretch;
    }
    
    .action-row select {
      flex: 1;
    }
    
    .action-row button {
      flex-shrink: 0;
    }
    
    .log-container {
      margin-top: 30px;
    }
    
    .log-box {
      background: #1e293b;
      color: #e2e8f0;
      padding: 20px;
      border-radius: 12px;
      height: 280px;
      overflow-y: auto;
      font-size: 13px;
      font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Courier New', monospace;
      white-space: pre-wrap;
      line-height: 1.6;
      box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .log-box::-webkit-scrollbar {
      width: 8px;
    }
    
    .log-box::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 4px;
    }
    
    .log-box::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 4px;
    }
    
    .log-box::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.3);
    }
    
    .hint {
      text-align: center;
      font-size: 13px;
      color: #64748b;
      margin-top: 12px;
      font-weight: 500;
    }
    
    /* Toast System */
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 12px;
      max-width: 400px;
    }
    
    .toast {
      background: #fff;
      padding: 16px 20px;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideIn 0.3s ease-out;
      border-left: 4px solid;
      min-width: 320px;
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(100px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    
    .toast.removing {
      animation: slideOut 0.3s ease-out forwards;
    }
    
    @keyframes slideOut {
      to {
        opacity: 0;
        transform: translateX(100px);
      }
    }
    
    .toast.success {
      border-left-color: #10b981;
    }
    
    .toast.error {
      border-left-color: #ef4444;
    }
    
    .toast.info {
      border-left-color: #3b82f6;
    }
    
    .toast-icon {
      flex-shrink: 0;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
    }
    
    .toast.success .toast-icon {
      background: #d1fae5;
      color: #10b981;
    }
    
    .toast.error .toast-icon {
      background: #fee2e2;
      color: #ef4444;
    }
    
    .toast.info .toast-icon {
      background: #dbeafe;
      color: #3b82f6;
    }
    
    .toast-content {
      flex: 1;
    }
    
    .toast-title {
      font-weight: 600;
      font-size: 14px;
      color: #1e293b;
      margin-bottom: 2px;
    }
    
    .toast-message {
      font-size: 13px;
      color: #64748b;
      line-height: 1.4;
    }
    
    .toast-close {
      flex-shrink: 0;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #f1f5f9;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      font-size: 16px;
      transition: all 0.2s;
    }
    
    .toast-close:hover {
      background: #e2e8f0;
      color: #1e293b;
    }
    
    /* Loading Spinner */
    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
      margin-left: 8px;
      vertical-align: middle;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
      z-index: 9998;
      display: none;
      align-items: center;
      justify-content: center;
    }
    
    .loading-overlay.active {
      display: flex;
    }
    
    .loading-content {
      background: #fff;
      padding: 30px 40px;
      border-radius: 16px;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 4px solid #e2e8f0;
      border-top-color: #667eea;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 0 auto 20px;
    }
    
    .loading-text {
      font-size: 16px;
      font-weight: 600;
      color: #1e293b;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 30px 20px;
      }
      
      h2 {
        font-size: 24px;
      }
      
      .action-row {
        flex-direction: column;
      }
      
      .toast-container {
        left: 20px;
        right: 20px;
        max-width: none;
      }
      
      .toast {
        min-width: auto;
      }
    }
  </style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
  <div class="loading-content">
    <div class="loading-spinner"></div>
    <div class="loading-text">Processing...</div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<div class="container">
  <div class="header">
    <h2>Database Sync Panel</h2>
    <div class="subtitle">Secure database synchronization system</div>
  </div>

<?php if(!$logged): ?>
  <div class="login-form">
    <div id="loginError" style="display:none;" class="error"></div>
    <div class="input-group">
      <input id="username" type="text" placeholder="Username" autocomplete="username">
    </div>
    <div class="input-group">
      <input id="password" type="password" placeholder="Password" autocomplete="current-password">
    </div>
    <button id="loginBtn" onclick="login()">Sign In</button>
    <div class="hint">Use admin / goldy / piyush / tejas credentials</div>
  </div>
<?php else: ?>
  <div class="user-info">
    <div class="user-badge">
      <div class="avatar"><?=strtoupper(substr($_SESSION['user'], 0, 1))?></div>
      <div class="user-details">
        <div class="username"><?=e($_SESSION['user'])?></div>
        <div class="role"><?=e($role)?> account</div>
      </div>
    </div>
    <button class="logout-btn" onclick="logout()">Logout</button>
  </div>

  <?php if($role === 'admin'): ?>
    <div class="section">
      <div class="section-title">Admin Actions</div>
      <div class="action-row">
        <select id="userToMain">
          <option value="goldy">Goldy → Main Database</option>
          <option value="piyush">Piyush → Main Database</option>
          <option value="tejas">Tejas → Main Database</option>
        </select>
        <button id="toMainBtn" onclick="sync('toMain')">Sync to Main</button>
      </div>
      <div class="action-row">
        <select id="mainToUser">
          <option value="goldy">Main Database → Goldy</option>
          <option value="piyush">Main Database → Piyush</option>
          <option value="tejas">Main Database → Tejas</option>
        </select>
        <button id="toUserBtn" onclick="sync('toUser')">Sync to User</button>
      </div>
    </div>
  <?php else: ?>
    <div class="section">
      <div class="section-title">User Actions</div>
      <div class="action-row">
        <button id="mainToMineBtn" onclick="sync('mainToMine')" style="flex:1;">🔄 Sync from Main Database</button>
        <button id="downloadBtn" onclick="downloadMyDB()" style="flex:1;">📥 Download My Database</button>
      </div>
    </div>
  <?php endif; ?>

  <div class="log-container">
    <div class="section-title">Activity Log</div>
    <div class="log-box" id="log">Waiting for operations...</div>
  </div>
<?php endif; ?>
</div>

<script>
let csrfToken = '';

// Toast Notification System
function showToast(title, message, type = 'info') {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  
  const icons = {
    success: '✓',
    error: '✕',
    info: 'ℹ'
  };
  
  toast.innerHTML = `
    <div class="toast-icon">${icons[type] || icons.info}</div>
    <div class="toast-content">
      <div class="toast-title">${title}</div>
      <div class="toast-message">${message}</div>
    </div>
    <button class="toast-close" onclick="removeToast(this)">×</button>
  `;
  
  container.appendChild(toast);
  
  // Auto remove after 5 seconds
  setTimeout(() => {
    removeToast(toast.querySelector('.toast-close'));
  }, 5000);
}

function removeToast(btn) {
  const toast = btn.closest('.toast');
  toast.classList.add('removing');
  setTimeout(() => toast.remove(), 300);
}

// Loading overlay
function showLoading(text = 'Processing...') {
  const overlay = document.getElementById('loadingOverlay');
  overlay.querySelector('.loading-text').textContent = text;
  overlay.classList.add('active');
}

function hideLoading() {
  document.getElementById('loadingOverlay').classList.remove('active');
}

// Fetch CSRF token
async function fetchCSRFToken() {
  try {
    const res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=getToken'
    });
    const data = await res.json();
    if (data.success) {
      csrfToken = data.message;
    }
  } catch(e) {
    console.error('Failed to fetch CSRF token:', e);
  }
}

<?php if($logged): ?>
// Fetch CSRF token on page load
fetchCSRFToken();
<?php endif; ?>

// Login function
async function login() {
  const btn = document.getElementById('loginBtn');
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();
  
  if (!username || !password) {
    showToast('Validation Error', 'Please enter username and password', 'error');
    return;
  }
  
  btn.disabled = true;
  btn.innerHTML = 'Signing in...<span class="spinner"></span>';
  showLoading('Authenticating...');
  
  try {
    const res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=login&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
    });
    
    const data = await res.json();
    
    if (data.success) {
      showToast('Success!', 'Login successful, redirecting...', 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      hideLoading();
      showToast('Login Failed', data.message, 'error');
      btn.disabled = false;
      btn.innerHTML = 'Sign In';
    }
  } catch(e) {
    hideLoading();
    showToast('Error', 'Login request failed: ' + e.message, 'error');
    btn.disabled = false;
    btn.innerHTML = 'Sign In';
  }
}

// Logout function
function logout() {
  if (!confirm('Are you sure you want to logout?')) return;
  
  showLoading('Logging out...');
  fetch('actions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=logout'
  }).finally(() => {
    showToast('Logged Out', 'You have been logged out successfully', 'success');
    setTimeout(() => location.reload(), 500);
  });
}

// Download function
function downloadMyDB() {
  showToast('Download Started', 'Preparing your database export...', 'info');
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'actions.php';
  form.style.display = 'none';
  
  const actionInput = document.createElement('input');
  actionInput.name = 'action';
  actionInput.value = 'download';
  form.appendChild(actionInput);
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}

// Sync function with proper AJAX
async function sync(type) {
  const btnId = type === 'toMain' ? 'toMainBtn' : (type === 'toUser' ? 'toUserBtn' : 'mainToMineBtn');
  const btn = document.getElementById(btnId);
  const log = document.getElementById('log');
  
  if (!csrfToken) {
    showToast('Error', 'Security token missing. Please refresh the page.', 'error');
    return;
  }
  
  const body = {action: type, csrf: csrfToken};
  if (type === 'toMain') body.user = document.getElementById('userToMain').value;
  if (type === 'toUser') body.user = document.getElementById('mainToUser').value;
  
  const formData = new URLSearchParams(body).toString();
  
  // Disable button and show loading
  btn.disabled = true;
  const originalText = btn.innerHTML;
  btn.innerHTML = btn.innerHTML.replace(/🔄|📥/, '') + '<span class="spinner"></span>';
  
  showLoading('Synchronizing database...');
  log.textContent = `[${new Date().toLocaleTimeString()}] Starting sync operation...\n`;
  
  try {
    const res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: formData
    });
    
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }
    
    const data = await res.json();
    
    hideLoading();
    
    // Update log with timestamp
    const timestamp = new Date().toLocaleTimeString();
    log.textContent = `[${timestamp}] ${data.message}`;
    
    // Scroll log to bottom
    log.scrollTop = log.scrollHeight;
    
    if (data.success) {
      showToast('Sync Successful!', 'Database synchronization completed successfully', 'success');
      
      // Refresh CSRF token after successful operation
      await fetchCSRFToken();
    } else {
      showToast('Sync Failed', data.message, 'error');
    }
  } catch(e) {
    hideLoading();
    const errorMsg = `Error: ${e.message}`;
    log.textContent = `[${new Date().toLocaleTimeString()}] ${errorMsg}`;
    showToast('Request Failed', errorMsg, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}

// Enter key support for login
document.addEventListener('DOMContentLoaded', function() {
  const passwordField = document.getElementById('password');
  const usernameField = document.getElementById('username');
  
  if (passwordField) {
    passwordField.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') login();
    });
  }
  
  if (usernameField) {
    usernameField.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') document.getElementById('password').focus();
    });
  }
});
</script>
</body>
</html>
