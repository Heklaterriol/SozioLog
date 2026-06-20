-- ============================================================
--  Migration: Berechtigungsstufen & direkte Kreiszugehörigkeit
--  Datei: database/migration_004_permissions_circle_membership.sql
--  Einspielen: mysql -u user -p logbuch < database/migration_004_permissions_circle_membership.sql
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
--  1. members.permission_level
--  Drei Stufen, ersetzen das bisherige reine is_admin-Flag:
--    'admin'    — alles (= bisheriges is_admin = 1)
--    'member'   — überall lesen, im eigenen Kreis verwalten
--    'readonly' — überall nur lesen
--
--  is_admin bleibt als Spalte bestehen (für Abwärtskompatibilität
--  von evtl. eigenen Skripten) und wird ab sofort aus
--  permission_level abgeleitet/synchron gehalten.
-- ------------------------------------------------------------
ALTER TABLE `members`
    ADD COLUMN `permission_level`
        ENUM('admin','member','readonly') NOT NULL DEFAULT 'member'
        AFTER `is_admin`;

-- Bestehende Admins übernehmen, alle anderen werden 'member'
-- (sicherer Default: weiterhin Schreibrechte wie zuvor, nur jetzt
-- auf den eigenen Kreis begrenzt statt global).
UPDATE `members` SET `permission_level` = 'admin' WHERE `is_admin` = 1;
UPDATE `members` SET `permission_level` = 'member' WHERE `is_admin` = 0;

-- ------------------------------------------------------------
--  2. circle_memberships
--  Direkte Kreiszugehörigkeit, unabhängig von Rollen.
--  Bestimmt u.a. den "eigenen Kreis" für die Stufe 'member'.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `circle_memberships` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id`   INT UNSIGNED NOT NULL,
    `circle_id`   INT UNSIGNED NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_circlemembership` (`member_id`, `circle_id`),
    KEY `idx_cm_member` (`member_id`),
    KEY `idx_cm_circle` (`circle_id`),
    CONSTRAINT `fk_cm_member`
        FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cm_circle`
        FOREIGN KEY (`circle_id`) REFERENCES `circles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Direkte Kreiszugehörigkeit (unabhängig von Rollen)';

SET foreign_key_checks = 1;
