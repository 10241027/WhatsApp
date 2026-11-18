<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const UPLOAD_ROOT = APP_ROOT . '/storage/uploads';

function database(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configFile = APP_ROOT . '/config/database.php';
    if (!is_file($configFile)) {
        $configFile = APP_ROOT . '/config/database.example.php';
    }
    $config = require $configFile;
    if (!is_dir(UPLOAD_ROOT)) {
        mkdir(UPLOAD_ROOT, 0775, true);
    }

    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $config['charset']),
        $config['username'],
        $config['password'],
        [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $schema = (string) file_get_contents(APP_ROOT . '/database/schema.sql');
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
        $pdo->exec($statement);
    }

    return $pdo;
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $query = database()->prepare('SELECT id, name, email, phone FROM users WHERE id = ? AND is_verified = 1');
    $query->execute([$_SESSION['user_id']]);
    return $query->fetch() ?: null;
}

function requireUser(): array
{
    $user = currentUser();
    if (!$user) {
        jsonResponse(['error' => 'Silakan masuk untuk melanjutkan.'], 401);
    }
    return $user;
}

function requestData(): array
{
    $content = file_get_contents('php://input');
    $decoded = json_decode($content ?: '', true);
    return is_array($decoded) ? $decoded : $_POST;
}


if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}