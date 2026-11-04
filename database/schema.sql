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
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chats` (
	`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`user_id` BIGINT UNSIGNED NOT NULL,
	`contact_name` VARCHAR(120) NOT NULL,
	`contact_phone` VARCHAR(30) NOT NULL DEFAULT '',
	`avatar_color` CHAR(7) NOT NULL DEFAULT '#5b7c73',
	`archived` TINYINT(1) NOT NULL DEFAULT 0,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX `chats_owner_updated` (`user_id`, `archived`, `updated_at`),
	CONSTRAINT `chats_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
	`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`chat_id` BIGINT UNSIGNED NOT NULL,
	`sender` ENUM('me', 'contact') NOT NULL DEFAULT 'me',
	`body` TEXT NOT NULL,
	`attachment_path` VARCHAR(255) NULL,
	`attachment_name` VARCHAR(255) NULL,
	`attachment_mime` VARCHAR(120) NULL,
	`attachment_size` BIGINT UNSIGNED NULL,
	`is_edited` TINYINT(1) NOT NULL DEFAULT 0,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX `messages_chat_created` (`chat_id`, `created_at`, `id`),
	CONSTRAINT `messages_chat_fk` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
