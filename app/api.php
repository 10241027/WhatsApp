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

function conversationForUser(int $conversationId, int $userId): array
{
    $query = database()->prepare(
        'SELECT c.*, CASE WHEN c.participant_one_id = ? THEN c.participant_two_id ELSE c.participant_one_id END AS contact_user_id
         FROM conversations c
         WHERE c.id = ? AND (c.participant_one_id = ? OR c.participant_two_id = ?)'
    );
    $query->execute([$userId, $conversationId, $userId, $userId]);
    $conversation = $query->fetch();
    if (!$conversation) {
        jsonResponse(['error' => 'Percakapan tidak ditemukan.'], 404);
    }
    return $conversation;
}

function conversationFullForUser(int $conversationId, int $userId): array
{
    $query = database()->prepare(
        'SELECT c.id, c.updated_at, cp.archived, cp.display_name,
                CASE WHEN c.participant_one_id = ? THEN u2.id ELSE u1.id END AS contact_user_id,
                CASE WHEN c.participant_one_id = ? THEN u2.name ELSE u1.name END AS contact_name,
                CASE WHEN c.participant_one_id = ? THEN u2.phone ELSE u1.phone END AS contact_phone,
                (SELECT body FROM conversation_messages WHERE conversation_id = c.id ORDER BY created_at DESC, id DESC LIMIT 1) AS last_message,
                (SELECT COUNT(*) FROM conversation_messages WHERE conversation_id = c.id AND attachment_path IS NOT NULL) AS file_count
         FROM conversations c
         JOIN users u1 ON u1.id = c.participant_one_id
         JOIN users u2 ON u2.id = c.participant_two_id
         LEFT JOIN conversation_preferences cp ON cp.conversation_id = c.id AND cp.user_id = ?
         WHERE c.id = ? AND (c.participant_one_id = ? OR c.participant_two_id = ?)
           AND COALESCE(cp.archived, 0) = 0 AND cp.deleted_at IS NULL'
    );
    $query->execute([$userId, $userId, $userId, $userId, $conversationId, $userId, $userId]);
    $conversation = $query->fetch();
    if (!$conversation) {
        jsonResponse(['error' => 'Percakapan tidak ditemukan.'], 404);
    }
    return $conversation;
}

function conversationPayload(array $conversation, int $userId): array
{
    $contactId = (int) $conversation['contact_user_id'];
    $colors = ['#a77458', '#667b91', '#8d745f', '#728b70', '#9c6e78'];
    $name = $conversation['display_name'] ?: $conversation['contact_name'];
    return [
        'id' => (int) $conversation['id'],
        'contact_user_id' => $contactId,
        'contact_name' => $name,
        'contact_phone' => $conversation['contact_phone'],
        'avatar_color' => $colors[$contactId % count($colors)],
        'archived' => (int) $conversation['archived'],
        'updated_at' => $conversation['updated_at'],
        'last_message' => $conversation['last_message'],
        'file_count' => (int) $conversation['file_count'],
    ];
}

function messagePayload(array $message, int $userId): array
{
    $message['chat_id'] = (int) $message['conversation_id'];
    $message['sender'] = (int) $message['sender_user_id'] === $userId ? 'me' : 'contact';
    $message['is_read'] = (int) $message['is_read'];
    if (!empty($message['attachment_path'])) {
        $message['attachment_url'] = 'index.php?api=file&id=' . (int) $message['id'];
    }
    unset($message['attachment_path'], $message['sender_user_id']);
    return $message;
}

function attachmentData(): array
{
    $file = $_FILES['attachment'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null, null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 12 * 1024 * 1024) {
        jsonResponse(['error' => 'Ukuran file maksimal 12 MB.'], 422);
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
    $extensions = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
        'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/ogg' => 'ogv', 'video/quicktime' => 'mov',
        'application/pdf' => 'pdf', 'text/plain' => 'txt', 'application/zip' => 'zip',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];
    if (!isset($extensions[$mime])) {
        jsonResponse(['error' => 'Jenis file ini belum didukung.'], 422);
    }
    $pathName = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_ROOT . '/' . $pathName)) {
        jsonResponse(['error' => 'File gagal disimpan.'], 500);
    }
    return [$pathName, mb_substr(basename((string) $file['name']), 0, 255), $mime, (int) $file['size']];
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
    $account = $query->fetch();
    if (!$account || !password_verify((string) ($data['password'] ?? ''), $account['password_hash'])) {
        jsonResponse(['error' => 'Email atau kata sandi tidak sesuai.'], 422);
    }
    if (!(int) $account['is_verified']) {
        $_SESSION['pending_user_id'] = (int) $account['id'];
        jsonResponse(['requires_verification' => true, 'email' => $email]);
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $account['id'];
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
    $db->prepare('UPDATE users SET verification_code_hash = ?, verification_expires = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?')->execute([password_hash($code, PASSWORD_DEFAULT), $userId]);
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
    $db->prepare('UPDATE users SET is_verified = 1, verification_code_hash = NULL, verification_expires = NULL WHERE id = ?')->execute([$userId]);
    session_regenerate_id(true);
    unset($_SESSION['pending_user_id']);
    $_SESSION['user_id'] = $userId;
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
    $query = $db->prepare(
        'SELECT m.attachment_path, m.attachment_name, m.attachment_mime FROM conversation_messages m
         JOIN conversations c ON c.id = m.conversation_id
         WHERE m.id = ? AND (c.participant_one_id = ? OR c.participant_two_id = ?)'
    );
    $query->execute([(int) ($_GET['id'] ?? 0), $user['id'], $user['id']]);
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
    $archived = (int) filter_var($_GET['archived'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $query = $db->prepare(
        'SELECT c.id, c.updated_at, cp.archived, cp.display_name,
                CASE WHEN c.participant_one_id = ? THEN u2.id ELSE u1.id END AS contact_user_id,
                CASE WHEN c.participant_one_id = ? THEN u2.name ELSE u1.name END AS contact_name,
                CASE WHEN c.participant_one_id = ? THEN u2.phone ELSE u1.phone END AS contact_phone,
                (SELECT body FROM conversation_messages WHERE conversation_id = c.id ORDER BY created_at DESC, id DESC LIMIT 1) AS last_message,
                (SELECT COUNT(*) FROM conversation_messages WHERE conversation_id = c.id AND attachment_path IS NOT NULL) AS file_count
         FROM conversations c
         JOIN users u1 ON u1.id = c.participant_one_id
         JOIN users u2 ON u2.id = c.participant_two_id
         LEFT JOIN conversation_preferences cp ON cp.conversation_id = c.id AND cp.user_id = ?
         WHERE (c.participant_one_id = ? OR c.participant_two_id = ?)
           AND COALESCE(cp.archived, 0) = ? AND cp.deleted_at IS NULL
         ORDER BY c.updated_at DESC, c.id DESC'
    );
    $query->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $archived]);
    $chats = array_map(static fn (array $chat): array => conversationPayload($chat, (int) $user['id']), $query->fetchAll());
    jsonResponse(['chats' => $chats]);
}

if ($action === 'chats' && $method === 'POST') {
    requireCsrf();
    $data = requestData();
    $identity = strtolower(trim((string) ($data['email'] ?? $data['phone'] ?? '')));
    if ($identity === '') {
        jsonResponse(['error' => 'Masukkan email pengguna yang ingin dihubungi.'], 422);
    }
    $contactQuery = $db->prepare('SELECT id FROM users WHERE LOWER(email) = ? AND is_verified = 1 LIMIT 1');
    $contactQuery->execute([$identity]);
    $contact = $contactQuery->fetch();
    if (!$contact) {
        jsonResponse(['error' => 'Pengguna tidak ditemukan atau belum memverifikasi akun.'], 404);
    }
    $contactId = (int) $contact['id'];
    if ($contactId === (int) $user['id']) {
        jsonResponse(['error' => 'Anda tidak dapat membuat percakapan dengan akun sendiri.'], 422);
    }
    $first = min((int) $user['id'], $contactId);
    $second = max((int) $user['id'], $contactId);
    $db->prepare('INSERT INTO conversations (participant_one_id, participant_two_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)')->execute([$first, $second]);
    $conversationId = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO conversation_preferences (conversation_id, user_id, archived, deleted_at) VALUES (?, ?, 0, NULL) ON DUPLICATE KEY UPDATE archived = 0, deleted_at = NULL')->execute([$conversationId, $user['id']]);
    jsonResponse(['chat_id' => $conversationId], 201);
}

if ($action === 'chats' && $method === 'PATCH') {
    requireCsrf();
    $data = requestData();
    $conversation = conversationForUser((int) ($data['id'] ?? 0), (int) $user['id']);
    if (array_key_exists('archived', $data)) {
        $db->prepare('INSERT INTO conversation_preferences (conversation_id, user_id, archived) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE archived = VALUES(archived), deleted_at = NULL')->execute([$conversation['id'], $user['id'], (int) (bool) $data['archived']]);
    } else {
        $name = trim((string) ($data['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            jsonResponse(['error' => 'Nama kontak harus terdiri dari 2 hingga 120 karakter.'], 422);
        }
        $db->prepare('INSERT INTO conversation_preferences (conversation_id, user_id, display_name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), deleted_at = NULL')->execute([$conversation['id'], $user['id'], $name]);
    }
    jsonResponse(['ok' => true]);
}

if ($action === 'chats' && $method === 'DELETE') {
    requireCsrf();
    $data = requestData();
    $conversation = conversationForUser((int) ($data['id'] ?? 0), (int) $user['id']);
    $db->prepare('INSERT INTO conversation_preferences (conversation_id, user_id, deleted_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE deleted_at = NOW()')->execute([$conversation['id'], $user['id']]);
    jsonResponse(['ok' => true]);
}

if ($action === 'messages' && $method === 'GET') {
    $conversation = conversationFullForUser((int) ($_GET['chat_id'] ?? 0), (int) $user['id']);
    $query = $db->prepare('SELECT * FROM conversation_messages WHERE conversation_id = ? ORDER BY created_at, id');
    $query->execute([$conversation['id']]);
    $db->prepare('UPDATE conversation_messages SET is_read = 1 WHERE conversation_id = ? AND sender_user_id <> ?')->execute([$conversation['id'], $user['id']]);
    jsonResponse(['chat' => conversationPayload($conversation, (int) $user['id']), 'messages' => array_map(static fn (array $message): array => messagePayload($message, (int) $user['id']), $query->fetchAll())]);
}

if ($action === 'messages.poll' && $method === 'GET') {
    $conversation = conversationForUser((int) ($_GET['chat_id'] ?? 0), (int) $user['id']);
    $lastId = max(0, (int) ($_GET['last_id'] ?? 0));
    $query = $db->prepare('SELECT * FROM conversation_messages WHERE conversation_id = ? AND id > ? ORDER BY created_at, id');
    $query->execute([$conversation['id'], $lastId]);
    $messages = $query->fetchAll();
    $db->prepare('UPDATE conversation_messages SET is_read = 1 WHERE conversation_id = ? AND sender_user_id <> ?')->execute([$conversation['id'], $user['id']]);
    jsonResponse(['messages' => array_map(static fn (array $message): array => messagePayload($message, (int) $user['id']), $messages)]);
}

if ($action === 'messages' && $method === 'POST') {
    requireCsrf();
    $conversation = conversationForUser((int) ($_POST['chat_id'] ?? 0), (int) $user['id']);
    $body = trim((string) ($_POST['body'] ?? ''));
    if (mb_strlen($body) > 5000) {
        jsonResponse(['error' => 'Pesan maksimal terdiri dari 5000 karakter.'], 422);
    }
    [$pathName, $fileName, $mime, $fileSize] = attachmentData();
    if ($body === '' && $pathName === null) {
        jsonResponse(['error' => 'Pesan atau file perlu diisi.'], 422);
    }
    $insert = $db->prepare('INSERT INTO conversation_messages (conversation_id, sender_user_id, body, attachment_path, attachment_name, attachment_mime, attachment_size) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([$conversation['id'], $user['id'], $body, $pathName, $fileName, $mime, $fileSize]);
    $messageId = (int) $db->lastInsertId();
    $db->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = ?')->execute([$conversation['id']]);
    $db->prepare('UPDATE conversation_preferences SET deleted_at = NULL WHERE conversation_id = ? AND user_id = ?')->execute([$conversation['id'], $user['id']]);
    $query = $db->prepare('SELECT * FROM conversation_messages WHERE id = ?');
    $query->execute([$messageId]);
    jsonResponse(['message' => messagePayload($query->fetch(), (int) $user['id'])], 201);
}

if ($action === 'messages' && $method === 'PATCH') {
    requireCsrf();
    $data = requestData();
    $body = trim((string) ($data['body'] ?? ''));
    if ($body === '' || mb_strlen($body) > 5000) {
        jsonResponse(['error' => 'Pesan tidak dapat diperbarui.'], 422);
    }
    $query = $db->prepare('SELECT m.id FROM conversation_messages m JOIN conversations c ON c.id = m.conversation_id WHERE m.id = ? AND m.sender_user_id = ? AND (c.participant_one_id = ? OR c.participant_two_id = ?)');
    $query->execute([(int) ($data['id'] ?? 0), $user['id'], $user['id'], $user['id']]);
    $message = $query->fetch();
    if (!$message) {
        jsonResponse(['error' => 'Pesan tidak ditemukan.'], 404);
    }
    $db->prepare('UPDATE conversation_messages SET body = ?, is_edited = 1 WHERE id = ?')->execute([$body, $message['id']]);
    jsonResponse(['ok' => true]);
}

if ($action === 'messages' && $method === 'DELETE') {
    requireCsrf();
    $data = requestData();
    $query = $db->prepare('SELECT m.id, m.attachment_path FROM conversation_messages m JOIN conversations c ON c.id = m.conversation_id WHERE m.id = ? AND m.sender_user_id = ? AND (c.participant_one_id = ? OR c.participant_two_id = ?)');
    $query->execute([(int) ($data['id'] ?? 0), $user['id'], $user['id'], $user['id']]);
    $message = $query->fetch();
    if (!$message) {
        jsonResponse(['error' => 'Pesan tidak ditemukan.'], 404);
    }
    $db->prepare('DELETE FROM conversation_messages WHERE id = ?')->execute([$message['id']]);
    if ($message['attachment_path']) {
        $path = UPLOAD_ROOT . '/' . basename($message['attachment_path']);
        if (is_file($path)) {
            unlink($path);
        }
    }
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Endpoint tidak ditemukan.'], 404);
