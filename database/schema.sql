-- ============================================================
--  Soziokratisches Logbuch — Datenbankschema
--  Zeichensatz: utf8mb4 / utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
--  1. members  (Personen / Benutzer)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(120)    NOT NULL,
    `email`         VARCHAR(180)    NOT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,
    `is_admin`      TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_members_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  1b. password_resets  (Tokens für "Passwort vergessen")
--  Es wird nur der SHA-256-Hash des Tokens gespeichert, nicht
--  der Token selbst — der Klartext steht nur in der E-Mail.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `member_id`    INT UNSIGNED  NOT NULL,
    `token_hash`   CHAR(64)      NOT NULL  COMMENT 'SHA-256 Hex-Hash des Reset-Tokens',
    `expires_at`   DATETIME      NOT NULL,
    `used_at`      DATETIME               DEFAULT NULL  COMMENT 'NULL = noch nicht eingelöst',
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `requested_ip` VARCHAR(45)            DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pwreset_token` (`token_hash`),
    KEY `idx_pwreset_member`  (`member_id`),
    KEY `idx_pwreset_expires` (`expires_at`),
    CONSTRAINT `fk_pwreset_member`
        FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `circles` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `parent_id`   INT UNSIGNED             DEFAULT NULL,
    `name`        VARCHAR(180)    NOT NULL,
    `driver`      TEXT                     DEFAULT NULL  COMMENT 'S3: Treiber / Organisationstreiber',
    `domain`      TEXT                     DEFAULT NULL  COMMENT 'Domäne der Autorität',
    `purpose`     TEXT                     DEFAULT NULL,
    `status`      ENUM('active','archived') NOT NULL DEFAULT 'active',
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_circles_parent` (`parent_id`),
    CONSTRAINT `fk_circles_parent`
        FOREIGN KEY (`parent_id`) REFERENCES `circles` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  3. roles  (Rollen innerhalb eines Kreises)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `circle_id`        INT UNSIGNED  NOT NULL,
    `name`             VARCHAR(180)  NOT NULL,
    `domain`           TEXT                   DEFAULT NULL,
    `purpose`          TEXT                   DEFAULT NULL,
    `accountabilities` JSON                   DEFAULT NULL  COMMENT 'Array of accountability strings',
    `role_type`        ENUM(
                           'general',
                           'facilitator',
                           'secretary',
                           'rep_link',
                           'delegate_link',
                           'elected'
                       ) NOT NULL DEFAULT 'general',
    `is_elected`       TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_roles_circle` (`circle_id`),
    CONSTRAINT `fk_roles_circle`
        FOREIGN KEY (`circle_id`) REFERENCES `circles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  4. role_assignments  (Wer hat wann welche Rolle — N:M)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_assignments` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `role_id`       INT UNSIGNED  NOT NULL,
    `member_id`     INT UNSIGNED  NOT NULL,
    `start_date`    DATE          NOT NULL,
    `end_date`      DATE                   DEFAULT NULL  COMMENT 'NULL = aktuell aktiv',
    `elected_until` DATE                   DEFAULT NULL  COMMENT 'Befristung bei gewählten Rollen',
    `notes`         TEXT                   DEFAULT NULL,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ra_role`   (`role_id`),
    KEY `idx_ra_member` (`member_id`),
    CONSTRAINT `fk_ra_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ra_member`
        FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  5. meetings  (Protokoll-Kopf)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meetings` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `circle_id`      INT UNSIGNED  NOT NULL,
    `meeting_type`   ENUM(
                         'governance',
                         'operational',
                         'election',
                         'retrospective',
                         'other'
                     ) NOT NULL DEFAULT 'governance',
    `held_at`        DATETIME      NOT NULL,
    `location`       VARCHAR(255)           DEFAULT NULL,
    `facilitator_id` INT UNSIGNED           DEFAULT NULL,
    `secretary_id`   INT UNSIGNED           DEFAULT NULL,
    `attendees`      JSON                   DEFAULT NULL  COMMENT 'Array von member_ids',
    `notes`          TEXT                   DEFAULT NULL  COMMENT 'Freitext-Notizen / Check-in',
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_meetings_circle` (`circle_id`),
    KEY `idx_meetings_date`   (`held_at`),
    CONSTRAINT `fk_meetings_circle`
        FOREIGN KEY (`circle_id`) REFERENCES `circles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_meetings_facilitator`
        FOREIGN KEY (`facilitator_id`) REFERENCES `members` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_meetings_secretary`
        FOREIGN KEY (`secretary_id`) REFERENCES `members` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  6. agenda_items  (Agenda-Punkte eines Meetings)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agenda_items` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `meeting_id` INT UNSIGNED  NOT NULL,
    `tension_id` INT UNSIGNED           DEFAULT NULL,
    `title`      VARCHAR(255)  NOT NULL,
    `item_type`  ENUM(
                     'tension',
                     'agreement',
                     'election',
                     'checkin',
                     'other'
                 ) NOT NULL DEFAULT 'tension',
    `outcome`    TEXT                   DEFAULT NULL,
    `sort_order` SMALLINT      NOT NULL DEFAULT 0,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ai_meeting` (`meeting_id`),
    KEY `idx_ai_tension` (`tension_id`),
    CONSTRAINT `fk_ai_meeting`
        FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    -- fk_ai_tension wird nach tensions-Tabelle gesetzt (s.u.)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  7. tensions  (Spannungen / Treiber-Backlog)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tensions` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `circle_id`   INT UNSIGNED  NOT NULL,
    `raised_by`   INT UNSIGNED           DEFAULT NULL,
    `meeting_id`  INT UNSIGNED           DEFAULT NULL  COMMENT 'Meeting, in dem eingebracht',
    `title`       VARCHAR(255)           DEFAULT NULL,
    `description` TEXT                   DEFAULT NULL,
    `status`      ENUM(
                      'open',
                      'in_progress',
                      'resolved',
                      'dropped'
                  ) NOT NULL DEFAULT 'open',
    `resolved_by` INT UNSIGNED           DEFAULT NULL  COMMENT 'FK → agreements.id',
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tensions_circle`  (`circle_id`),
    KEY `idx_tensions_raised`  (`raised_by`),
    KEY `idx_tensions_meeting` (`meeting_id`),
    KEY `idx_tensions_status`  (`status`),
    CONSTRAINT `fk_tensions_circle`
        FOREIGN KEY (`circle_id`) REFERENCES `circles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tensions_raised`
        FOREIGN KEY (`raised_by`) REFERENCES `members` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_tensions_meeting`
        FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nachträgliche FK-Constraints (zirkuläre Abhängigkeiten)
ALTER TABLE `agenda_items`
    ADD CONSTRAINT `fk_ai_tension`
        FOREIGN KEY (`tension_id`) REFERENCES `tensions` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ------------------------------------------------------------
--  8. agreements  (Vereinbarungen / Beschlüsse)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agreements` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `circle_id`    INT UNSIGNED  NOT NULL,
    `meeting_id`   INT UNSIGNED           DEFAULT NULL  COMMENT 'Meeting, in dem beschlossen',
    `title`        VARCHAR(255)  NOT NULL,
    `driver`       TEXT                   DEFAULT NULL  COMMENT 'Zugrundeliegender Treiber',
    `body`         TEXT                   DEFAULT NULL  COMMENT 'Volltext der Vereinbarung',
    `agreed_at`    DATE          NOT NULL,
    `review_date`  DATE                   DEFAULT NULL,
    `status`       ENUM(
                       'active',
                       'expired',
                       'review_due',
                       'draft'
                   ) NOT NULL DEFAULT 'active',
    `created_by`   INT UNSIGNED           DEFAULT NULL,
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_agr_circle`  (`circle_id`),
    KEY `idx_agr_meeting` (`meeting_id`),
    KEY `idx_agr_review`  (`review_date`),
    KEY `idx_agr_status`  (`status`),
    CONSTRAINT `fk_agr_circle`
        FOREIGN KEY (`circle_id`) REFERENCES `circles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_agr_meeting`
        FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_agr_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `members` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK tensions → agreements (nachträglich)
ALTER TABLE `tensions`
    ADD CONSTRAINT `fk_tensions_resolved`
        FOREIGN KEY (`resolved_by`) REFERENCES `agreements` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ------------------------------------------------------------
--  9. delegations  (Delegationen zwischen Kreisen)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `delegations` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `from_circle`    INT UNSIGNED  NOT NULL,
    `to_circle`      INT UNSIGNED  NOT NULL,
    `description`    TEXT                   DEFAULT NULL,
    `rep_link_role`  INT UNSIGNED           DEFAULT NULL  COMMENT 'Repräsentanten-Link-Rolle',
    `del_link_role`  INT UNSIGNED           DEFAULT NULL  COMMENT 'Delegierten-Link-Rolle',
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_del_from` (`from_circle`),
    KEY `idx_del_to`   (`to_circle`),
    CONSTRAINT `fk_del_from`
        FOREIGN KEY (`from_circle`) REFERENCES `circles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_del_to`
        FOREIGN KEY (`to_circle`) REFERENCES `circles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_del_rep`
        FOREIGN KEY (`rep_link_role`) REFERENCES `roles` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_del_del`
        FOREIGN KEY (`del_link_role`) REFERENCES `roles` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ------------------------------------------------------------
--  Seed-Daten: erster Admin-Benutzer (Passwort ändern!)
--  password_hash = bcrypt('changeme')
-- ------------------------------------------------------------
INSERT INTO `members` (`name`, `email`, `password_hash`, `is_admin`)
VALUES ('Administrator', 'admin@example.org',
        '$2y$12$placeholder_change_this_hash_immediately', 1);
