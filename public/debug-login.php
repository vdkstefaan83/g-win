<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

session_start();

echo '<h1>Login Debug v2</h1>';

// Test the EXACT same code path as the login controller
echo '<h2>1. Database via Core\Database</h2>';
try {
    $db = Core\Database::getInstance();
    echo '<p style="color:green">Core\Database OK</p>';
} catch (Exception $e) {
    echo '<p style="color:red">Core\Database FOUT: ' . htmlspecialchars($e->getMessage()) . '</p>';
    die();
}

// Test User model
echo '<h2>2. User model query</h2>';
try {
    $userModel = new App\Models\User();
    $user = $userModel->findByEmail('admin@g-win.be');
    echo '<pre>' . print_r($user, true) . '</pre>';
} catch (Exception $e) {
    echo '<p style="color:red">User model FOUT: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Test password
echo '<h2>3. Password check</h2>';
if ($user) {
    echo '<p>password_verify("admin123"): ' . (password_verify('admin123', $user['password_hash']) ? '<b style="color:green">OK</b>' : '<b style="color:red">MISLUKT</b>') . '</p>';
} else {
    echo '<p style="color:red">Geen user gevonden!</p>';
}

// Test session
echo '<h2>4. Session</h2>';
echo '<p>Session ID: ' . session_id() . '</p>';
echo '<p>CSRF token in session: ' . ($_SESSION['csrf_token'] ?? 'NIET GEZET') . '</p>';

// Test actual login flow simulation
echo '<h2>5. Simuleer login</h2>';
if ($user && $user['is_active'] && password_verify('admin123', $user['password_hash'])) {
    echo '<p style="color:green">Login zou SLAGEN met admin@g-win.be / admin123</p>';
} else {
    echo '<p style="color:red">Login zou MISLUKKEN</p>';
    echo '<p>user: ' . ($user ? 'gevonden' : 'NIET gevonden') . '</p>';
    echo '<p>is_active: ' . ($user['is_active'] ?? 'n/a') . '</p>';
}

// Check what form actually posts
echo '<h2>6. Test login form</h2>';
echo '<p>Probeer hieronder in te loggen - dit POST naar dit zelfde script:</p>';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<pre>POST data: ' . print_r($_POST, true) . '</pre>';
    $testUser = $userModel->findByEmail($_POST['email'] ?? '');
    echo '<p>User gevonden: ' . ($testUser ? 'JA' : 'NEE') . '</p>';
    if ($testUser) {
        echo '<p>password_verify: ' . (password_verify($_POST['password'] ?? '', $testUser['password_hash']) ? '<b style="color:green">OK</b>' : '<b style="color:red">MISLUKT</b>') . '</p>';
    }
}
?>
<form method="POST">
    <input type="text" name="email" value="admin@g-win.be" style="padding:8px;margin:4px;border:1px solid #ccc">
    <input type="text" name="password" value="admin123" style="padding:8px;margin:4px;border:1px solid #ccc">
    <button type="submit" style="padding:8px 16px;background:#333;color:#fff;border:none;cursor:pointer">Test Login</button>
</form>
