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
    echo json_encode(['success'=>$success, 'message'=>$message]); 
    exit;
}

function shell($cmd){
    exec($cmd." 2>&1", $output, $code);
    return [$code, implode("\n", $output)];
}

function validateCSRF() {
    if (!isset($_POST['csrf']) || !isset($_SESSION['csrf_token'])) {
        respond(false, 'Invalid CSRF token');
    }
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf'])) {
        respond(false, 'Invalid CSRF token');
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function secureMySQLDump($user, $pass, $db, $outputFile) {
    // Create temporary config file to avoid password in process list
    $cnfFile = sys_get_temp_dir() . '/mysql_' . uniqid() . '.cnf';
    $cnfContent = "[client]\nuser={$user}\npassword={$pass}\n";
    file_put_contents($cnfFile, $cnfContent);
    chmod($cnfFile, 0600);
    
    $cmd = "mysqldump --defaults-extra-file=" . escapeshellarg($cnfFile) . " " . escapeshellarg($db) . " > " . escapeshellarg($outputFile);
    list($code, $output) = shell($cmd);
    
    unlink($cnfFile); // Clean up temp file
    return [$code, $output];
}

function secureMySQLImport($user, $pass, $db, $inputFile) {
    // Create temporary config file to avoid password in process list
    $cnfFile = sys_get_temp_dir() . '/mysql_' . uniqid() . '.cnf';
    $cnfContent = "[client]\nuser={$user}\npassword={$pass}\n";
    file_put_contents($cnfFile, $cnfContent);
    chmod($cnfFile, 0600);
    
    $cmd = "mysql --defaults-extra-file=" . escapeshellarg($cnfFile) . " " . escapeshellarg($db) . " < " . escapeshellarg($inputFile);
    list($code, $output) = shell($cmd);
    
    unlink($cnfFile); // Clean up temp file
    return [$code, $output];
}

function createBackup($user, $pass, $db, $dumpDir) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = "{$dumpDir}/backup_{$db}_{$timestamp}.sql";
    list($code, $output) = secureMySQLDump($user, $pass, $db, $backupFile);
    
    if ($code !== 0) {
        logMessage("Backup failed for $db: $output");
        return [false, $output];
    }
    
    logMessage("Backup created: $backupFile");
    return [true, $backupFile];
}

// Progress logging functions
function initProgress($taskId) {
    $progressFile = sys_get_temp_dir() . "/sync_progress_{$taskId}.log";
    file_put_contents($progressFile, '');
    return $progressFile;
}

function logProgress($taskId, $message) {
    $progressFile = sys_get_temp_dir() . "/sync_progress_{$taskId}.log";
    $timestamp = date('H:i:s');
    file_put_contents($progressFile, "[$timestamp] $message\n", FILE_APPEND);
    logMessage($message); // Also log to main log
}

function getProgress($taskId) {
    $progressFile = sys_get_temp_dir() . "/sync_progress_{$taskId}.log";
    if (file_exists($progressFile)) {
        return file_get_contents($progressFile);
    }
    return '';
}

function clearProgress($taskId) {
    $progressFile = sys_get_temp_dir() . "/sync_progress_{$taskId}.log";
    if (file_exists($progressFile)) {
        unlink($progressFile);
    }
}

function pushToGitHub($filePath, $commitMessage, $taskId = null) {
    global $env;
    
    if ($taskId) logProgress($taskId, "🔄 Preparing to push to GitHub...");
    
    if (!isset($env['GITHUB_TOKEN']) || !isset($env['GITHUB_REPO'])) {
        logMessage("GitHub push skipped: Missing GITHUB_TOKEN or GITHUB_REPO in .env");
        return [false, "GitHub credentials not configured"];
    }
    
    $token = trim($env['GITHUB_TOKEN'], "'\"");
    $repo = trim($env['GITHUB_REPO'], "'\"");
    $branch = trim($env['GIT_BRANCH'] ?? 'main', "'\"");
    $email = trim($env['GIT_EMAIL'] ?? 'sync-bot@example.com', "'\"");
    $name = trim($env['GIT_NAME'] ?? 'DB Sync Bot', "'\"");
    
    // Check if file exists
    if (!file_exists($filePath)) {
        $error = "File not found: $filePath";
        logMessage("GitHub push failed: $error");
        return [false, $error];
    }
    
    // Use the main project directory
    $projectDir = __DIR__;
    $fileName = basename($filePath);
    
    if ($taskId) logProgress($taskId, "📦 Staging files for commit...");
    
    // Simple git push - work with existing repo, no config changes
    $remoteUrl = "https://{$token}@github.com/{$repo}.git";
    $commands = [
        "cd " . escapeshellarg($projectDir) . " 2>&1",
        "git add -A 2>&1",
        "git diff --cached --quiet || git commit -m " . escapeshellarg($commitMessage) . " 2>&1",
        "git push " . escapeshellarg($remoteUrl) . " HEAD:{$branch} 2>&1"
    ];
    
    $fullCmd = implode(" && ", $commands);
    if ($taskId) logProgress($taskId, "⬆️ Pushing to remote repository...");
    
    list($code, $output) = shell($fullCmd);
    
    logMessage("Git push command output (code: $code): $output");
    
    // Check if push was successful
    if ($code === 0 || 
        strpos($output, 'up-to-date') !== false || 
        strpos($output, 'Everything up-to-date') !== false ||
        strpos($output, 'branch') !== false && strpos($output, 'set up to track') !== false) {
        logMessage("GitHub push successful for sync: $fileName");
        if ($taskId) logProgress($taskId, "✅ Successfully pushed to GitHub!");
        return [true, "Pushed to GitHub repository"];
    } else {
        logMessage("GitHub push failed with code $code: $output");
        if ($taskId) logProgress($taskId, "⚠️ GitHub push failed: " . substr($output, 0, 100));
        return [false, "Push failed (code: $code): " . substr($output, 0, 200)];
    }
}

$action = $_POST['action'] ?? '';

// Handle logout
if ($action === 'logout') {
    session_destroy();
    respond(true, 'Logged out successfully');
}

// Generate CSRF token for session
if ($action === 'getToken') {
    respond(true, generateCSRFToken());
}

// Get progress logs for task
if ($action === 'getProgress') {
    $taskId = $_POST['taskId'] ?? '';
    if (!$taskId) respond(false, 'Task ID required');
    
    $progress = getProgress($taskId);
    respond(true, $progress);
}

if ($action === 'login') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    // Rate limiting check (simple)
    $loginAttempts = $_SESSION['login_attempts'] ?? 0;
    $lastAttempt = $_SESSION['last_attempt'] ?? 0;
    
    if ($loginAttempts >= 5 && (time() - $lastAttempt) < 300) {
        respond(false, 'Too many login attempts. Please try again in 5 minutes.');
    }

    $valid = [
        'admin'  => $env['ADMIN_PASS'],
        'goldy'  => $env['GOLDY_PASS'],
        'piyush' => $env['PIYUSH_PASS'],
        'tejas'  => $env['TEJAS_PASS'],
    ];

    if (isset($valid[$user]) && hash_equals($valid[$user], $pass)) {
        $_SESSION['user'] = $user;
        $_SESSION['role'] = ($user === 'admin') ? 'admin' : 'user';
        $_SESSION['login_attempts'] = 0;
        generateCSRFToken(); // Generate token for authenticated session
        logMessage("Login success: $user");
        respond(true, 'Login successful');
    } else {
        $_SESSION['login_attempts'] = $loginAttempts + 1;
        $_SESSION['last_attempt'] = time();
        logMessage("Login failed for: $user");
        respond(false, 'Invalid credentials');
    }
}

if (!isset($_SESSION['user'])) respond(false, 'Not authenticated');

// Handle download request
if ($action === 'download') {
    $role = $_SESSION['role'];
    $user = $_SESSION['user'];
    
    $dumpDir = $env['DUMP_DIR'];
    $map = [
        'goldy'  => $env['DB_GOLDY'],
        'piyush' => $env['DB_PIYUSH'],
        'tejas'  => $env['DB_TEJAS']
    ];
    
    // Users can only download their own DB
    if ($role !== 'user' || !isset($map[$user])) {
        respond(false, 'Unauthorized');
    }
    
    $myDB = $map[$user];
    $filename = "{$user}_database_" . date('Y-m-d_H-i-s') . ".sql";
    $filepath = "{$dumpDir}/{$filename}";
    
    // Create fresh dump
    list($code, $output) = secureMySQLDump($env['DB_CLI_USER'], $env['DB_CLI_PASS'], $myDB, $filepath);
    
    if ($code !== 0) {
        logMessage("Download dump failed for $user: $output");
        respond(false, "Failed to create dump: $output");
    }
    
    // Send file for download
    if (file_exists($filepath)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache');
        readfile($filepath);
        
        logMessage("User $user downloaded their database");
        unlink($filepath); // Clean up after download
        exit;
    } else {
        respond(false, 'Dump file not found');
    }
}

// Validate CSRF for all authenticated actions
if ($action !== 'login' && $action !== 'getToken' && $action !== 'download') {
    validateCSRF();
}

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

// Validate database credentials before operations
function testDBConnection($user, $pass, $db) {
    $cnfFile = sys_get_temp_dir() . '/mysql_test_' . uniqid() . '.cnf';
    $cnfContent = "[client]\nuser={$user}\npassword={$pass}\n";
    file_put_contents($cnfFile, $cnfContent);
    chmod($cnfFile, 0600);
    
    $cmd = "mysql --defaults-extra-file=" . escapeshellarg($cnfFile) . " -e 'SELECT 1' " . escapeshellarg($db) . " 2>&1";
    list($code, $output) = shell($cmd);
    unlink($cnfFile);
    
    return $code === 0;
}

switch($action){

    case 'toMain':
        if ($role !== 'admin') respond(false, 'Unauthorized');
        $target = $_POST['user'] ?? '';
        if (!isset($map[$target])) respond(false, 'Invalid user');
        
        $taskId = uniqid('sync_', true);
        initProgress($taskId);
        
        $fromDB = $map[$target];
        $toDB = $main;
        $dump = "$dumpDir/{$fromDB}_to_main.sql";

        logProgress($taskId, "🚀 Starting sync operation: {$fromDB} → {$toDB}");
        
        // Test connection
        logProgress($taskId, "🔍 Validating database connections...");
        if (!testDBConnection($cliUser, $cliPass, $fromDB)) {
            logProgress($taskId, "❌ Cannot connect to source database: $fromDB");
            respond(false, "Cannot connect to source database: $fromDB");
        }
        if (!testDBConnection($cliUser, $cliPass, $toDB)) {
            logProgress($taskId, "❌ Cannot connect to target database: $toDB");
            respond(false, "Cannot connect to target database: $toDB");
        }
        logProgress($taskId, "✅ Database connections validated");

        // Create backup of target database
        logProgress($taskId, "📦 Creating backup of target database...");
        list($backupSuccess, $backupResult) = createBackup($cliUser, $cliPass, $toDB, $dumpDir);
        if (!$backupSuccess) {
            logProgress($taskId, "❌ Backup failed: $backupResult");
            respond(false, "Backup failed: $backupResult");
        }
        logProgress($taskId, "✅ Backup created: " . basename($backupResult));

        // Dump source database
        logProgress($taskId, "📤 Dumping source database ($fromDB)...");
        [$code1, $out1] = secureMySQLDump($cliUser, $cliPass, $fromDB, $dump);
        if ($code1 !== 0) {
            logProgress($taskId, "❌ Dump failed: $out1");
            logMessage("Dump failed: $fromDB → $out1");
            respond(false, "Dump failed: $out1");
        }
        logProgress($taskId, "✅ Database dumped successfully");

        // Import to target database
        logProgress($taskId, "📥 Importing to target database ($toDB)...");
        [$code2, $out2] = secureMySQLImport($cliUser, $cliPass, $toDB, $dump);
        if ($code2 !== 0) {
            logProgress($taskId, "❌ Import failed: $out2");
            logMessage("Import failed: $toDB → $out2");
            respond(false, "Import failed: $out2\nBackup available at: $backupResult");
        }
        logProgress($taskId, "✅ Data imported successfully");

        logMessage("Admin synced $fromDB → $toDB (backup: $backupResult)");
        
        // Push to GitHub
        $gitMsg = "Admin sync: {$fromDB} → {$toDB} at " . date('Y-m-d H:i:s');
        list($gitSuccess, $gitOutput) = pushToGitHub($dump, $gitMsg, $taskId);
        $gitStatus = $gitSuccess ? "\n✓ Pushed to GitHub" : "\n⚠ GitHub push failed: $gitOutput";
        
        logProgress($taskId, "🎉 Sync operation completed successfully!");
        clearProgress($taskId);
        
        respond(true, "✓ Successfully dumped $fromDB → $toDB\n✓ Backup created: " . basename($backupResult) . "{$gitStatus}");
        break;

    case 'toUser':
        if ($role !== 'admin') respond(false, 'Unauthorized');
        $target = $_POST['user'] ?? '';
        if (!isset($map[$target])) respond(false, 'Invalid user');
        
        $taskId = uniqid('sync_', true);
        initProgress($taskId);
        
        $fromDB = $main;
        $toDB = $map[$target];
        $dump = "$dumpDir/main_to_{$target}.sql";

        logProgress($taskId, "🚀 Starting sync operation: main → {$toDB}");
        
        // Test connection
        logProgress($taskId, "🔍 Validating database connections...");
        if (!testDBConnection($cliUser, $cliPass, $fromDB)) {
            logProgress($taskId, "❌ Cannot connect to source database: $fromDB");
            respond(false, "Cannot connect to source database: $fromDB");
        }
        if (!testDBConnection($cliUser, $cliPass, $toDB)) {
            logProgress($taskId, "❌ Cannot connect to target database: $toDB");
            respond(false, "Cannot connect to target database: $toDB");
        }
        logProgress($taskId, "✅ Database connections validated");

        // Create backup of target database
        logProgress($taskId, "📦 Creating backup of target database...");
        list($backupSuccess, $backupResult) = createBackup($cliUser, $cliPass, $toDB, $dumpDir);
        if (!$backupSuccess) {
            logProgress($taskId, "❌ Backup failed: $backupResult");
            respond(false, "Backup failed: $backupResult");
        }
        logProgress($taskId, "✅ Backup created: " . basename($backupResult));

        // Dump source database
        logProgress($taskId, "📤 Dumping source database ($fromDB)...");
        [$code1, $out1] = secureMySQLDump($cliUser, $cliPass, $fromDB, $dump);
        if ($code1 !== 0) {
            logProgress($taskId, "❌ Dump failed: $out1");
            logMessage("Dump failed: $fromDB → $out1");
            respond(false, "Dump failed: $out1");
        }
        logProgress($taskId, "✅ Database dumped successfully");

        // Import to target database
        logProgress($taskId, "📥 Importing to target database ($toDB)...");
        [$code2, $out2] = secureMySQLImport($cliUser, $cliPass, $toDB, $dump);
        if ($code2 !== 0) {
            logProgress($taskId, "❌ Import failed: $out2");
            logMessage("Import failed: $toDB → $out2");
            respond(false, "Import failed: $out2\nBackup available at: $backupResult");
        }
        logProgress($taskId, "✅ Data imported successfully");

        logMessage("Admin synced main → $toDB (backup: $backupResult)");
        
        // Push to GitHub
        $gitMsg = "Admin sync: main → {$toDB} at " . date('Y-m-d H:i:s');
        list($gitSuccess, $gitOutput) = pushToGitHub($dump, $gitMsg, $taskId);
        $gitStatus = $gitSuccess ? "\n✓ Pushed to GitHub" : "\n⚠ GitHub push failed: $gitOutput";
        
        logProgress($taskId, "🎉 Sync operation completed successfully!");
        clearProgress($taskId);
        
        respond(true, "✓ Successfully dumped main → $toDB\n✓ Backup created: " . basename($backupResult) . "{$gitStatus}");
        break;

    case 'mainToMine':
        if ($role !== 'user') respond(false, 'Unauthorized');
        $myDB = $map[$user];
        $dump = "$dumpDir/main_to_{$user}.sql";
        
        $taskId = uniqid('sync_', true);
        initProgress($taskId);

        logProgress($taskId, "🚀 Starting sync operation: main → {$myDB}");
        
        // Test connection
        logProgress($taskId, "🔍 Validating database connections...");
        if (!testDBConnection($cliUser, $cliPass, $main)) {
            logProgress($taskId, "❌ Cannot connect to main database");
            respond(false, "Cannot connect to main database");
        }
        if (!testDBConnection($cliUser, $cliPass, $myDB)) {
            logProgress($taskId, "❌ Cannot connect to your database: $myDB");
            respond(false, "Cannot connect to your database: $myDB");
        }
        logProgress($taskId, "✅ Database connections validated");

        // Create backup of user database
        logProgress($taskId, "📦 Creating backup of your database...");
        list($backupSuccess, $backupResult) = createBackup($cliUser, $cliPass, $myDB, $dumpDir);
        if (!$backupSuccess) {
            logProgress($taskId, "❌ Backup failed: $backupResult");
            respond(false, "Backup failed: $backupResult");
        }
        logProgress($taskId, "✅ Backup created: " . basename($backupResult));

        // Dump main database
        logProgress($taskId, "📤 Dumping main database...");
        [$code1, $out1] = secureMySQLDump($cliUser, $cliPass, $main, $dump);
        if ($code1 !== 0) {
            logProgress($taskId, "❌ Dump failed: $out1");
            logMessage("Dump failed: main → $out1");
            respond(false, "Dump failed: $out1");
        }
        logProgress($taskId, "✅ Database dumped successfully");

        // Import to user database
        logProgress($taskId, "📥 Importing to your database ($myDB)...");
        [$code2, $out2] = secureMySQLImport($cliUser, $cliPass, $myDB, $dump);
        if ($code2 !== 0) {
            logProgress($taskId, "❌ Import failed: $out2");
            logMessage("Import failed: $myDB → $out2");
            respond(false, "Import failed: $out2\nBackup available at: $backupResult");
        }
        logProgress($taskId, "✅ Data imported successfully");

        logMessage("User $user synced main → $myDB (backup: $backupResult)");
        
        // Push to GitHub
        $gitMsg = "User {$user} sync: main → {$myDB} at " . date('Y-m-d H:i:s');
        list($gitSuccess, $gitOutput) = pushToGitHub($dump, $gitMsg, $taskId);
        $gitStatus = $gitSuccess ? "\n✓ Pushed to GitHub" : "\n⚠ GitHub push failed: $gitOutput";
        
        logProgress($taskId, "🎉 Sync operation completed successfully!");
        clearProgress($taskId);
        
        respond(true, "✓ Successfully synced main → $myDB\n✓ Backup created: " . basename($backupResult) . "{$gitStatus}");
        break;

    default:
        respond(false, 'Invalid action');
}

