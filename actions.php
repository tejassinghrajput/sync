<?php
session_start();
$env = parse_ini_file(__DIR__ . '/.env');

function logMessage($msg) {
    $file = $GLOBALS['env']['LOG_FILE'] ?? 'logs/app.log';
    $dir = dirname($file);
    if (!file_exists($dir)) mkdir($dir, 0777, true);
    file_put_contents($file, date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL, FILE_APPEND);
}
function respond($success, $message) {
    echo json_encode(['success'=>$success, 'message'=>$message]); exit;
}
function shell($cmd){
    exec($cmd." 2>&1", $output, $code);
    return [$code, implode("\n", $output)];
}

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    $valid = [
        'admin'  => $env['ADMIN_PASS'],
        'goldy'  => $env['GOLDY_PASS'],
        'piyush' => $env['PIYUSH_PASS'],
        'tejas'  => $env['TEJAS_PASS'],
    ];

    if (isset($valid[$user]) && $pass === $valid[$user]) {
        $_SESSION['user'] = $user;
        $_SESSION['role'] = ($user === 'admin') ? 'admin' : 'user';
        logMessage("Login success: $user");
        respond(true, 'Login successful');
    } else {
        logMessage("Login failed for: $user");
        respond(false, 'Invalid credentials');
    }
}

if (!isset($_SESSION['user'])) respond(false, 'Not authenticated');
$role = $_SESSION['role'];
$user = $_SESSION['user'];

// DB names
$main = $env['DB_MAIN'];
$map = [
    'goldy'  => $env['DB_GOLDY'],
    'piyush' => $env['DB_PIYUSH'],
    'tejas'  => $env['DB_TEJAS']
];

$cliUser = $env['DB_CLI_USER'];
$cliPass = $env['DB_CLI_PASS'];
$dumpDir = $env['DUMP_DIR'];
if (!file_exists($dumpDir)) mkdir($dumpDir, 0777, true);

switch($action){

    case 'toMain':
        if ($role !== 'admin') respond(false, 'Unauthorized');
        $target = $_POST['user'] ?? '';
        if (!isset($map[$target])) respond(false, 'Invalid user');
        $fromDB = $map[$target];
        $toDB = $main;
        $dump = "$dumpDir/{$fromDB}_to_main.sql";

        [$code1,$out1]=shell("mysqldump -u$cliUser -p$cliPass $fromDB > $dump");
        [$code2,$out2]=shell("mysql -u$cliUser -p$cliPass $toDB < $dump");

        logMessage("Admin synced $fromDB → $toDB");
        respond(true, "Dumped $fromDB → $toDB\n$out1\n$out2");
        break;

    case 'toUser':
        if ($role !== 'admin') respond(false, 'Unauthorized');
        $target = $_POST['user'] ?? '';
        if (!isset($map[$target])) respond(false, 'Invalid user');
        $fromDB = $main;
        $toDB = $map[$target];
        $dump = "$dumpDir/main_to_{$target}.sql";

        [$code1,$out1]=shell("mysqldump -u$cliUser -p$cliPass $fromDB > $dump");
        [$code2,$out2]=shell("mysql -u$cliUser -p$cliPass $toDB < $dump");

        logMessage("Admin synced main → $toDB");
        respond(true, "Dumped main → $toDB\n$out1\n$out2");
        break;

    case 'mainToMine':
        if ($role !== 'user') respond(false, 'Unauthorized');
        $myDB = $map[$user];
        $dump = "$dumpDir/main_to_{$user}.sql";

        [$code1,$out1]=shell("mysqldump -u$cliUser -p$cliPass $main > $dump");
        [$code2,$out2]=shell("mysql -u$cliUser -p$cliPass $myDB < $dump");

        logMessage("User $user synced main → $myDB");
        respond(true, "Synced main → $myDB\n$out1\n$out2");
        break;

    default:
        respond(false, 'Invalid action');
}
