<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo '<h1>Login Debug</h1>';

// 1. Check DB connection
echo '<h2>1. Database connectie</h2>';
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_NAME'] ?? 'gwin'
    );
    $pdo = new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo '<p style="color:green">DB connectie OK</p>';
    echo '<p>Host: ' . ($_ENV['DB_HOST'] ?? 'localhost') . ', DB: ' . ($_ENV['DB_NAME'] ?? 'gwin') . '</p>';
} catch (Exception $e) {
    echo '<p style="color:red">DB FOUT: ' . htmlspecialchars($e->getMessage()) . '</p>';
    die();
}

// 2. Check users table
echo '<h2>2. Users tabel</h2>';
try {
    $stmt = $pdo->query('SELECT id, name, email, role, is_active, LEFT(password_hash, 20) as hash_start FROM users');
    $users = $stmt->fetchAll();
    if (empty($users)) {
        echo '<p style="color:red">GEEN USERS GEVONDEN - seeds niet gedraaid!</p>';
    } else {
        echo '<table border="1" cellpadding="5"><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Hash start</th></tr>';
        foreach ($users as $u) {
            echo "<tr><td>{$u['id']}</td><td>{$u['name']}</td><td>{$u['email']}</td><td>{$u['role']}</td><td>{$u['is_active']}</td><td>{$u['hash_start']}...</td></tr>";
        }
        echo '</table>';
    }
} catch (Exception $e) {
    echo '<p style="color:red">TABEL FOUT: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// 3. Check password verification
echo '<h2>3. Wachtwoord verificatie</h2>';
if (!empty($users)) {
    $user = $users[0];
    $fullHash = $pdo->query("SELECT password_hash FROM users WHERE id = {$user['id']}")->fetch()['password_hash'];
    echo '<p>Volledige hash: <code>' . htmlspecialchars($fullHash) . '</code></p>';
    echo '<p>password_verify("admin123", hash): ' . (password_verify('admin123', $fullHash) ? '<span style="color:green">OK</span>' : '<span style="color:red">MISLUKT</span>') . '</p>';
    echo '<p>password_verify("password", hash): ' . (password_verify('password', $fullHash) ? '<span style="color:green">OK</span>' : '<span style="color:red">MISLUKT</span>') . '</p>';
}

// 4. Check CSRF
echo '<h2>4. POST route check</h2>';
echo '<p>De login POST gaat naar <code>/admin/login</code>. Check of CSRF token correct meegestuurd wordt.</p>';

// 5. Check .env values
echo '<h2>5. ENV waardes</h2>';
echo '<p>APP_DEBUG: ' . ($_ENV['APP_DEBUG'] ?? 'NIET GEZET') . '</p>';
echo '<p>DB_HOST: ' . ($_ENV['DB_HOST'] ?? 'NIET GEZET') . '</p>';
echo '<p>DB_NAME: ' . ($_ENV['DB_NAME'] ?? 'NIET GEZET') . '</p>';
echo '<p>DB_USER: ' . ($_ENV['DB_USER'] ?? 'NIET GEZET') . '</p>';
echo '<p>DB_PASS: ' . (empty($_ENV['DB_PASS']) ? 'LEEG' : '***SET***') . '</p>';
