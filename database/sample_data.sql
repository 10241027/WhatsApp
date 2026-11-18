-- Jalankan schema.sql terlebih dahulu.
-- Semua akun demo di bawah menggunakan kata sandi: password123

INSERT INTO users (name, email, phone, password_hash, is_verified)
VALUES
    ('Administrator Demo', 'admin@test.com', '081234567890', '$2y$12$VEms1ZuvfzUchHLk4zg2GueeEA09kguQtWsw2425C/jD8WU4v/Y4O', 1),
    ('Nadia Putri', 'nadia@test.com', '081280417721', '$2y$12$VEms1ZuvfzUchHLk4zg2GueeEA09kguQtWsw2425C/jD8WU4v/Y4O', 1),
    ('Raka Mahendra', 'raka@test.com', '081365021840', '$2y$12$VEms1ZuvfzUchHLk4zg2GueeEA09kguQtWsw2425C/jD8WU4v/Y4O', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    phone = VALUES(phone),
    password_hash = VALUES(password_hash),
    is_verified = 1,
    verification_code_hash = NULL,
    verification_expires = NULL;

-- Ambil ID akun berdasarkan email agar skrip tetap dapat dijalankan ulang.
SET @admin_id := (SELECT id FROM users WHERE email = 'admin@test.com');
SET @nadia_id := (SELECT id FROM users WHERE email = 'nadia@test.com');
SET @raka_id := (SELECT id FROM users WHERE email = 'raka@test.com');

-- Percakapan Administrator Demo dan Nadia Putri.
SET @first_id := LEAST(@admin_id, @nadia_id);
SET @second_id := GREATEST(@admin_id, @nadia_id);
INSERT INTO conversations (participant_one_id, participant_two_id)
VALUES (@first_id, @second_id)
ON DUPLICATE KEY UPDATE updated_at = updated_at;
SET @admin_nadia_conversation := (
    SELECT id FROM conversations
    WHERE participant_one_id = @first_id AND participant_two_id = @second_id
);

INSERT INTO conversation_messages (conversation_id, sender_user_id, body, created_at)
SELECT @admin_nadia_conversation, @nadia_id, 'Halo! Ini pesan dari akun Nadia, bukan data kontak dummy.', DATE_SUB(NOW(), INTERVAL 10 MINUTE)
WHERE NOT EXISTS (
    SELECT 1 FROM conversation_messages
    WHERE conversation_id = @admin_nadia_conversation
);
INSERT INTO conversation_messages (conversation_id, sender_user_id, body, created_at)
SELECT @admin_nadia_conversation, @admin_id, 'Halo Nadia, pesannya sudah masuk ke akun saya.', DATE_SUB(NOW(), INTERVAL 8 MINUTE)
WHERE NOT EXISTS (
    SELECT 1 FROM conversation_messages
    WHERE conversation_id = @admin_nadia_conversation AND sender_user_id = @admin_id
);

-- Percakapan Administrator Demo dan Raka Mahendra.
SET @first_id := LEAST(@admin_id, @raka_id);
SET @second_id := GREATEST(@admin_id, @raka_id);
INSERT INTO conversations (participant_one_id, participant_two_id)
VALUES (@first_id, @second_id)
ON DUPLICATE KEY UPDATE updated_at = updated_at;
SET @admin_raka_conversation := (
    SELECT id FROM conversations
    WHERE participant_one_id = @first_id AND participant_two_id = @second_id
);

INSERT INTO conversation_messages (conversation_id, sender_user_id, body, created_at)
SELECT @admin_raka_conversation, @raka_id, 'Hai, silakan coba kirim pesan dari akun admin ke akun saya.', DATE_SUB(NOW(), INTERVAL 5 MINUTE)
WHERE NOT EXISTS (
    SELECT 1 FROM conversation_messages
    WHERE conversation_id = @admin_raka_conversation
);

UPDATE conversations c
SET updated_at = (
    SELECT MAX(m.created_at) FROM conversation_messages m WHERE m.conversation_id = c.id
)
WHERE c.id IN (@admin_nadia_conversation, @admin_raka_conversation);

-- Opsional: nama panggilan hanya untuk tampilan Administrator Demo.
INSERT INTO conversation_preferences (conversation_id, user_id, display_name)
VALUES
    (@admin_nadia_conversation, @admin_id, 'Nadia'),
    (@admin_raka_conversation, @admin_id, 'Raka')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);
