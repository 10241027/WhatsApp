CREATE DATABASE IF NOT EXISTS `whatsapp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `whatsapp`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NOT NULL DEFAULT '',
    `password_hash` VARCHAR(255) NOT NULL,
    `verification_code_hash` VARCHAR(255) NULL,
    `verification_expires` DATETIME NULL,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Setiap percakapan langsung hanya memiliki satu baris untuk dua pengguna.
-- participant_one_id selalu lebih kecil daripada participant_two_id.
CREATE TABLE IF NOT EXISTS `conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `participant_one_id` BIGINT UNSIGNED NOT NULL,
    `participant_two_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `conversations_participants` (`participant_one_id`, `participant_two_id`),
    INDEX `conversations_participant_one` (`participant_one_id`),
    INDEX `conversations_participant_two` (`participant_two_id`),
    CONSTRAINT `conversations_first_user_fk` FOREIGN KEY (`participant_one_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `conversations_second_user_fk` FOREIGN KEY (`participant_two_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    -- CHECK constraint requires MySQL 8.0.16+ or MariaDB 10.2.1+; ignored on older versions
    CONSTRAINT `conversations_different_users` CHECK (`participant_one_id` < `participant_two_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preferensi bersifat pribadi: mengarsipkan atau menghapus chat tidak
-- memengaruhi kotak masuk pengguna lain.
CREATE TABLE IF NOT EXISTS `conversation_preferences` (
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `display_name` VARCHAR(120) NULL,
    `archived` TINYINT(1) NOT NULL DEFAULT 0,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`conversation_id`, `user_id`),
    INDEX `conversation_preferences_user_list` (`user_id`, `archived`, `deleted_at`),
    CONSTRAINT `conversation_preferences_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `conversation_preferences_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `conversation_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `sender_user_id` BIGINT UNSIGNED NOT NULL,
    `body` TEXT NOT NULL,
    `attachment_path` VARCHAR(255) NULL,
    `attachment_name` VARCHAR(255) NULL,
    `attachment_mime` VARCHAR(120) NULL,
    `attachment_size` BIGINT UNSIGNED NULL,
    `is_edited` TINYINT(1) NOT NULL DEFAULT 0,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `conversation_messages_timeline` (`conversation_id`, `created_at`, `id`),
    INDEX `conversation_messages_sender` (`sender_user_id`),
    CONSTRAINT `conversation_messages_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `conversation_messages_sender_fk` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
