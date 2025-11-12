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
    input,button,select{padding:10px;border:1px solid #ccc;border-radius:8px;}
    button{background:#2563eb;color:white;cursor:pointer;}
    button:hover{opacity:.9;}
    .log{background:#111;color:#eee;padding:12px;border-radius:8px;height:240px;overflow:auto;font-size:12px;}
    .row{margin-bottom:12px;display:flex;gap:10px;}
    .small{font-size:13px;color:#666;}
  </style>
</head>
<body>
<div class="card">
  <h2>Database Sync Panel</h2>

<?php if(!$logged): ?>
  <div class="row">
    <input id="username" placeholder="username">
    <input id="password" type="password" placeholder="password">
    <button onclick="login()">Login</button>
  </div>
  <div class="small">Use admin / goldy / piyush / tejas credentials from .env</div>
<?php else: ?>
  <div class="small">
    Logged in as <strong><?=e($_SESSION['user'])?></strong> (role: <?=e($role)?>)
    <a href="?logout=1" style="margin-left:10px;">Logout</a>
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
      <button onclick="sync('toMain')">Dump to Main</button>
    </div>
    <div class="row">
      <select id="mainToUser">
        <option value="goldy">Main → Goldy</option>
        <option value="piyush">Main → Piyush</option>
        <option value="tejas">Main → Tejas</option>
      </select>
      <button onclick="sync('toUser')">Dump to User</button>
    </div>
  <?php else: ?>
    <h3>User Actions</h3>
    <div class="row">
      <button onclick="sync('mainToMine')">Sync from Main → My DB</button>
    </div>
  <?php endif; ?>

  <div class="log" id="log"></div>
<?php endif; ?>
</div>

<script>
async function login(){
  let username=document.getElementById('username').value.trim();
  let password=document.getElementById('password').value.trim();
  let res=await fetch('actions.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=login&username='+encodeURIComponent(username)+'&password='+encodeURIComponent(password)
  });
  let data=await res.json();
  if(data.success) location.reload();
  else alert(data.message);
}

async function sync(type){
  let body={action:type};
  if(type==='toMain') body.user=document.getElementById('userToMain').value;
  if(type==='toUser') body.user=document.getElementById('mainToUser').value;
  let formData=new URLSearchParams(body).toString();
  let res=await fetch('actions.php',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:formData
  });
  let data=await res.json();
  document.getElementById('log').innerText=data.message;
}

<?php if(isset($_GET['logout'])){ session_destroy(); header("Location: index.php"); exit; } ?>
</script>
</body>
</html>
