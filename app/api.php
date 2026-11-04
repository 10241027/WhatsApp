<?php
declare(strict_types=1);

$db = database();
$action = (string) ($_GET['api'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function requireCsrf(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$provided || !hash_equals((string) $_SESSION['csrf_token'], $provided)) {
        jsonResponse(['error' => 'Sesi keamanan tidak valid. Muat ulang halaman.'], 419);
    }
}

function ownedChat(int $chatId, int $userId): array
{
    $query = database()->prepare('SELECT * FROM chats WHERE id = ? AND user_id = ?');
    $query->execute([$chatId, $userId]);
    $chat = $query->fetch();
    if (!$chat) {
        jsonResponse(['error' => 'Percakapan tidak ditemukan.'], 404);
    }
    return $chat;
}

function messagePayload(array $message): array
{
    if (!empty($message['attachment_path'])) {
        $message['attachment_url'] = 'index.php?api=file&id=' . (int) $message['id'];
    }
    unset($message['attachment_path']);
    return $message;
}

if ($action === 'auth.session' && $method === 'GET') {
    jsonResponse(['user' => currentUser(), 'csrf' => $_SESSION['csrf_token']]);
}

if ($action === 'auth.register' && $method === 'POST') {
    requireCsrf();
    $data = requestData();
    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $phone = trim((string) ($data['phone'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        jsonResponse(['error' => 'Periksa nama, email, dan kata sandi minimal 8 karakter.'], 422);
    }

    $code = (string) random_int(100000, 999999);
    try {
        $insert = $db->prepare('INSERT INTO users (name, email, phone, password_hash, verification_code_hash, verification_expires) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))');
        $insert->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), password_hash($code, PASSWORD_DEFAULT)]);
    } catch (PDOException $error) {
        if ((string) $error->getCode() === '23000') {
            jsonResponse(['error' => 'Email ini sudah terdaftar. Silakan masuk.'], 409);
        }
        throw $error;
    }
    $_SESSION['pending_user_id'] = (int) $db->lastInsertId();
    jsonResponse(['message' => 'Kode verifikasi demo dibuat.', 'email' => $email, 'demo_code' => $code]);
}

if ($action === 'auth.login' && $method === 'POST') {
    requireCsrf();
    $data = requestData();
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $query = $db->prepare('SELECT * FROM users WHERE email = ?');
    $query->execute([$email]);
    $user = $query->fetch();
    if (!$user || !password_verify((string) ($data['password'] ?? ''), $user['password_hash'])) {
        jsonResponse(['error' => 'Email atau kata sandi tidak sesuai.'], 422);
    }
    if (!(int) $user['is_verified']) {
        $_SESSION['pending_user_id'] = (int) $user['id'];
        jsonResponse(['requires_verification' => true, 'email' => $email]);
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    seedChats((int) $user['id']);
    jsonResponse(['user' => currentUser()]);
}

if ($action === 'auth.resend' && $method === 'POST') {
    requireCsrf();
    $data = requestData();
    $userId = (int) ($_SESSION['pending_user_id'] ?? 0);
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $query = $db->prepare('SELECT id FROM users WHERE id = ? AND email = ? AND is_verified = 0');
    $query->execute([$userId, $email]);
    if (!$query->fetch()) {
        jsonResponse(['error' => 'Sesi verifikasi tidak ditemukan. Silakan daftar kembali.'], 404);
    }
    $code = (string) random_int(100000, 999999);
    $update = $db->prepare('UPDATE users SET verification_code_hash = ?, verification_expires = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?');
    $update->execute([password_hash($code, PASSWORD_DEFAULT), $userId]);
    jsonResponse(['message' => 'Kode baru siap digunakan.', 'demo_code' => $code]);
}

if ($action === 'auth.verify' && $method === 'POST') {
    requireCsrf();
    $data = requestData();
    $userId = (int) ($_SESSION['pending_user_id'] ?? 0);
    $query = $db->prepare('SELECT id, verification_code_hash FROM users WHERE id = ? AND is_verified = 0 AND verification_expires > NOW()');
    $query->execute([$userId]);
    $pending = $query->fetch();
    if (!$pending || !password_verify((string) ($data['code'] ?? ''), $pending['verification_code_hash'] ?? '')) {
        jsonResponse(['error' => 'Kode salah atau sudah kedaluwarsa.'], 422);
    }
    $update = $db->prepare('UPDATE users SET is_verified = 1, verification_code_hash = NULL, verification_expires = NULL WHERE id = ?');
    $update->execute([$userId]);
    session_regenerate_id(true);
    unset($_SESSION['pending_user_id']);
    $_SESSION['user_id'] = $userId;
    seedChats($userId);
    jsonResponse(['user' => currentUser()]);
}

if ($action === 'auth.logout' && $method === 'POST') {
    requireCsrf();
    $_SESSION = [];
    session_destroy();
    jsonResponse(['ok' => true]);
}

if ($action === 'file' && $method === 'GET') {
    $user = requireUser();
    $query = $db->prepare('SELECT m.attachment_path, m.attachment_name, m.attachment_mime FROM messages m JOIN chats c ON c.id = m.chat_id WHERE m.id = ? AND c.user_id = ?');
    $query->execute([(int) ($_GET['id'] ?? 0), $user['id']]);
    $file = $query->fetch();
    $path = $file ? UPLOAD_ROOT . '/' . basename((string) $file['attachment_path']) : '';
    if (!$file || !is_file($path)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $file['attachment_mime']);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="' . rawurlencode($file['attachment_name']) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

$user = requireUser();

if ($action === 'chats' && $method === 'GET') {
    $query = $db->prepare("SELECT c.*, (SELECT body FROM messages WHERE chat_id = c.id ORDER BY created_at DESC, id DESC LIMIT 1) AS last_message, (SELECT COUNT(*) FROM messages WHERE chat_id = c.id AND attachment_path IS NOT NULL) AS file_count FROM chats c WHERE c.user_id = ? AND c.archived = 0 ORDER BY c.updated_at DESC, c.id DESC");
    $query->execute([$user['id']]);
    jsonResponse(['chats' => $query->fetchAll()]);
}

if ($action === 'chats' && $method === 'POST') {
    requireCsrf();
    $data = requestData();
    $name = trim((string) ($data['name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        jsonResponse(['error' => 'Nama kontak harus terdiri dari 2 hingga 120 karakter.'], 422);
    }
    $colors = ['#a77458', '#667b91', '#8d745f', '#728b70', '#9c6e78'];
    $insert = $db->prepare('INSERT INTO chats (user_id, contact_name, contact_phone, avatar_color) VALUES (?, ?, ?, ?)');
    $insert->execute([$user['id'], $name, $phone, $colors[array_rand($colors)]]);
    jsonResponse(['chat_id' => (int) $db->lastInsertId()], 201);
}

if ($action === 'chats' && $method === 'PATCH') {
    requireCsrf();
    $data = requestData();
    $chat = ownedChat((int) ($data['id'] ?? 0), (int) $user['id']);
    if (array_key_exists('archived', $data)) {
        $update = $db->prepare('UPDATE chats SET archived = ? WHERE id = ?');
        $update->execute([(int) (bool) $data['archived'], $chat['id']]);
    } else {
        $name = trim((string) ($data['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            jsonResponse(['error' => 'Nama kontak harus terdiri dari 2 hingga 120 karakter.'], 422);
        }
        $update = $db->prepare('UPDATE chats SET contact_name = ? WHERE id = ?');
        $update->execute([$name, $chat['id']]);
    }
    jsonResponse(['ok' => true]);
}

if ($action === 'chats' && $method === 'DELETE') {
    requireCsrf();
    $data = requestData();
    $chat = ownedChat((int) ($data['id'] ?? 0), (int) $user['id']);
    $files = $db->prepare('SELECT attachment_path FROM messages WHERE chat_id = ? AND attachment_path IS NOT NULL');
    $files->execute([$chat['id']]);
    foreach ($files->fetchAll() as $file) {
        $path = UPLOAD_ROOT . '/' . basename($file['attachment_path']);
        if (is_file($path)) {
            unlink($path);
        }
    }
    $delete = $db->prepare('DELETE FROM chats WHERE id = ?');
    $delete->execute([$chat['id']]);
    jsonResponse(['ok' => true]);
}

if ($action === 'messages' && $method === 'GET') {
    $chat = ownedChat((int) ($_GET['chat_id'] ?? 0), (int) $user['id']);
    $query = $db->prepare('SELECT * FROM messages WHERE chat_id = ? ORDER BY created_at, id');
    $query->execute([$chat['id']]);
    jsonResponse(['chat' => $chat, 'messages' => array_map('messagePayload', $query->fetchAll())]);
}

if ($action === 'messages' && $method === 'POST') {
    requireCsrf();
    $chatId = (int) ($_POST['chat_id'] ?? 0);
    $chat = ownedChat($chatId, (int) $user['id']);
    $body = trim((string) ($_POST['body'] ?? ''));
    $file = $_FILES['attachment'] ?? null;
    if ($body === '' && (!$file || $file['error'] === UPLOAD_ERR_NO_FILE)) {
        jsonResponse(['error' => 'Pesan atau file perlu diisi.'], 422);
    }
    $pathName = $fileName = $mime = null;
    $fileSize = null;
    if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 12 * 1024 * 1024) {
            jsonResponse(['error' => 'Ukuran file maksimal 12 MB.'], 422);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
        $safeMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'application/pdf', 'text/plain', 'application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($mime, $safeMimes, true)) {
            jsonResponse(['error' => 'Jenis file ini belum didukung.'], 422);
        }
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp', 'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/ogg' => 'ogv', 'video/quicktime' => 'mov', 'application/pdf' => 'pdf', 'text/plain' => 'txt', 'application/zip' => 'zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'];
        $pathName = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_ROOT . '/' . $pathName)) {
            jsonResponse(['error' => 'File gagal disimpan.'], 500);
        }
        $fileName = basename((string) $file['name']);
        $fileSize = (int) $file['size'];
    }
    $insert = $db->prepare('INSERT INTO messages (chat_id, sender, body, attachment_path, attachment_name, attachment_mime, attachment_size) VALUES (?, \'me\', ?, ?, ?, ?, ?)');
    $insert->execute([$chat['id'], $body, $pathName, $fileName, $mime, $fileSize]);
    $messageId = (int) $db->lastInsertId();
    $db->prepare('UPDATE chats SET updated_at = NOW() WHERE id = ?')->execute([$chat['id']]);
    $query = $db->prepare('SELECT * FROM messages WHERE id = ?');
    $query->execute([$messageId]);
    jsonResponse(['message' => messagePayload($query->fetch())], 201);
}

if ($action === 'messages' && $method === 'PATCH') {
    requireCsrf();
    $data = requestData();
    $query = $db->prepare("SELECT m.id FROM messages m JOIN chats c ON c.id = m.chat_id WHERE m.id = ? AND c.user_id = ? AND m.sender = 'me'");
    $query->execute([(int) ($data['id'] ?? 0), $user['id']]);
    $message = $query->fetch();
    $body = trim((string) ($data['body'] ?? ''));
    if (!$message || $body === '' || mb_strlen($body) > 5000) {
        jsonResponse(['error' => 'Pesan tidak dapat diperbarui.'], 422);
    }
    $update = $db->prepare('UPDATE messages SET body = ?, is_edited = 1 WHERE id = ?');
    $update->execute([$body, $message['id']]);
    jsonResponse(['ok' => true]);
}

if ($action === 'messages' && $method === 'DELETE') {
    requireCsrf();
    $data = requestData();
    $query = $db->prepare("SELECT m.id, m.attachment_path FROM messages m JOIN chats c ON c.id = m.chat_id WHERE m.id = ? AND c.user_id = ? AND m.sender = 'me'");
    $query->execute([(int) ($data['id'] ?? 0), $user['id']]);
    $message = $query->fetch();
    if (!$message) {
        jsonResponse(['error' => 'Pesan tidak ditemukan.'], 404);
    }
    if ($message['attachment_path']) {
        $path = UPLOAD_ROOT . '/' . basename($message['attachment_path']);
        if (is_file($path)) {
            unlink($path);
        }
    }
    $db->prepare('DELETE FROM messages WHERE id = ?')->execute([$message['id']]);
    jsonResponse(['ok' => true]);
}

if ($action === 'backup.export' && $method === 'GET') {
    $chats = $db->prepare('SELECT id, contact_name, contact_phone, avatar_color, archived FROM chats WHERE user_id = ? ORDER BY id');
    $chats->execute([$user['id']]);
    $result = [];
    $attachmentBytes = 0;
    foreach ($chats->fetchAll() as $chat) {
        $messages = $db->prepare('SELECT sender, body, attachment_path, attachment_name, attachment_mime, attachment_size, created_at FROM messages WHERE chat_id = ? ORDER BY id');
        $messages->execute([$chat['id']]);
        $chat['messages'] = $messages->fetchAll();
        foreach ($chat['messages'] as &$message) {
            if ($message['attachment_path']) {
                $path = UPLOAD_ROOT . '/' . basename($message['attachment_path']);
                if (is_file($path)) {
                    $attachmentBytes += filesize($path);
                    if ($attachmentBytes > 25 * 1024 * 1024) {
                        jsonResponse(['error' => 'Cadangan dengan lampiran dibatasi hingga 25 MB.'], 413);
                    }
                    $message['attachment_data'] = base64_encode((string) file_get_contents($path));
                }
            }
            unset($message['attachment_path']);
        }
        unset($message);
        $result[] = $chat;
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="whatsapp-backup-' . date('Y-m-d') . '.json"');
    echo json_encode(['format' => 'whatsapp-backup', 'version' => 1, 'exported_at' => date(DATE_ATOM), 'chats' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'backup.restore' && $method === 'POST') {
    requireCsrf();
    $data = requestData();
    $backup = $data['backup'] ?? null;
    if (!is_array($backup) || ($backup['format'] ?? '') !== 'whatsapp-backup' || !is_array($backup['chats'] ?? null) || count($backup['chats']) > 500) {
        jsonResponse(['error' => 'File cadangan tidak valid.'], 422);
    }
    $db->beginTransaction();
    $createdFiles = [];
    $oldFiles = $db->prepare('SELECT m.attachment_path FROM messages m JOIN chats c ON c.id = m.chat_id WHERE c.user_id = ? AND m.attachment_path IS NOT NULL');
    $oldFiles->execute([$user['id']]);
    $oldPaths = array_column($oldFiles->fetchAll(), 'attachment_path');
    try {
        $delete = $db->prepare('DELETE FROM chats WHERE user_id = ?');
        $delete->execute([$user['id']]);
        $insertChat = $db->prepare('INSERT INTO chats (user_id, contact_name, contact_phone, avatar_color, archived) VALUES (?, ?, ?, ?, ?)');
        $insertMessage = $db->prepare('INSERT INTO messages (chat_id, sender, body, attachment_path, attachment_name, attachment_mime, attachment_size, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp', 'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/ogg' => 'ogv', 'video/quicktime' => 'mov', 'application/pdf' => 'pdf', 'text/plain' => 'txt', 'application/zip' => 'zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'];
        $restoredBytes = 0;
        foreach ($backup['chats'] as $chat) {
            $name = trim((string) ($chat['contact_name'] ?? ''));
            if ($name === '' || !is_array($chat['messages'] ?? [])) {
                throw new InvalidArgumentException('Data percakapan tidak lengkap.');
            }
            $insertChat->execute([$user['id'], mb_substr($name, 0, 120), mb_substr((string) ($chat['contact_phone'] ?? ''), 0, 30), (string) ($chat['avatar_color'] ?? '#5b7c73'), (int) !empty($chat['archived'])]);
            $newChatId = (int) $db->lastInsertId();
            foreach (array_slice($chat['messages'], 0, 5000) as $message) {
                $filePath = $fileName = $mime = null;
                $fileSize = null;
                if (!empty($message['attachment_data'])) {
                    $mime = (string) ($message['attachment_mime'] ?? '');
                    $fileContents = base64_decode((string) $message['attachment_data'], true);
                    if (!isset($extensions[$mime]) || $fileContents === false || strlen($fileContents) > 12 * 1024 * 1024) {
                        throw new InvalidArgumentException('Lampiran di dalam cadangan tidak valid.');
                    }
                    $restoredBytes += strlen($fileContents);
                    if ($restoredBytes > 25 * 1024 * 1024) {
                        throw new InvalidArgumentException('Ukuran total lampiran cadangan melebihi 25 MB.');
                    }
                    $filePath = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
                    if (file_put_contents(UPLOAD_ROOT . '/' . $filePath, $fileContents, LOCK_EX) === false) {
                        throw new RuntimeException('Lampiran cadangan gagal disimpan.');
                    }
                    $createdFiles[] = $filePath;
                    $fileName = mb_substr(basename((string) ($message['attachment_name'] ?? 'lampiran')), 0, 255);
                    $fileSize = strlen($fileContents);
                }
                $createdAt = $message['created_at'] ?? date('Y-m-d H:i:s');
                if (!is_string($createdAt) || strtotime($createdAt) === false) {
                    $createdAt = date('Y-m-d H:i:s');
                }
                $insertMessage->execute([$newChatId, ($message['sender'] ?? '') === 'contact' ? 'contact' : 'me', mb_substr((string) ($message['body'] ?? ''), 0, 5000), $filePath, $fileName, $mime, $fileSize, date('Y-m-d H:i:s', strtotime($createdAt))]);
            }
        }
        $db->commit();
        foreach ($oldPaths as $oldPath) {
            $path = UPLOAD_ROOT . '/' . basename($oldPath);
            if (is_file($path)) {
                unlink($path);
            }
        }
    } catch (Throwable $error) {
        $db->rollBack();
        foreach ($createdFiles as $createdFile) {
            $path = UPLOAD_ROOT . '/' . basename($createdFile);
            if (is_file($path)) {
                unlink($path);
            }
        }
        jsonResponse(['error' => 'Pemulihan gagal: ' . $error->getMessage()], 422);
    }
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Endpoint tidak ditemukan.'], 404);