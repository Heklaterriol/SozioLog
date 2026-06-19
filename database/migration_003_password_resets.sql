-- ============================================================
--  Migration: Passwort-Reset
--  Datei: database/migration_003_password_resets.sql
--  Einspielen: mysql -u user -p logbuch < database/migration_003_password_resets.sql
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
--  password_resets
--  Ein Reset-Link pro Zeile. Es wird nur der SHA-256-Hash des
--  Tokens gespeichert (nicht der Token selbst) — so kann mit
--  einem DB-Leak kein gültiger Link nachgebaut werden.
--  Der Klartext-Token landet ausschließlich in der E-Mail.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `member_id`   INT UNSIGNED  NOT NULL,
    `token_hash`  CHAR(64)      NOT NULL  COMMENT 'SHA-256 Hex-Hash des Reset-Tokens',
    `expires_at`  DATETIME      NOT NULL,
    `used_at`     DATETIME               DEFAULT NULL  COMMENT 'NULL = noch nicht eingelöst',
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `requested_ip` VARCHAR(45)           DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pwreset_token` (`token_hash`),
    KEY `idx_pwreset_member`  (`member_id`),
    KEY `idx_pwreset_expires` (`expires_at`),
    CONSTRAINT `fk_pwreset_member`
        FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens für "Passwort vergessen"-Links';

SET foreign_key_checks = 1;
