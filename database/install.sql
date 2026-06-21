-- ============================================================
--  Soziokratisches Logbuch — Vollständiges Installationsschema
--  Erstinstallation: mysql -u user -p logbuch < database/install.sql
--
--  Login erfolgt ausschließlich per Nextcloud-SSO (OAuth2),
--  siehe config['nextcloud'].
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
--  1. members
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(120)    NOT NULL,
    `email`            VARCHAR(180)    NOT NULL,
    `password_hash`    VARCHAR(255)    NOT NULL,
    `is_admin`         TINYINT(1)      NOT NULL DEFAULT 0,
    `permission_level` ENUM('admin','member','readonly')
                                       NOT NULL DEFAULT 'member'
        COMMENT 'admin = alles | member = lesen überall + verwalten im eigenen Kreis | readonly = nur lesen',
    `nextcloud_user_id` VARCHAR(180)            DEFAULT NULL
        COMMENT 'Nextcloud-Benutzer-ID (SSO-Verknüpfung)',
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_members_email` (`email`),
    UNIQUE KEY `uq_members_nextcloud` (`nextcloud_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  2. circles  (selbstreferenzierend)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `circles` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `parent_id`   INT UNSIGNED             DEFAULT NULL,
    `name`        VARCHAR(180)    NOT NULL,
    `driver`      TEXT                     DEFAULT NULL  COMMENT 'Treiber / Organisationstreiber',
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
--  3. circle_memberships
--  Direkte Kreiszugehörigkeit (unabhängig von Rollen).
--  Definiert den „eigenen Kreis" für Berechtigungsstufe 'member'.
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

-- ------------------------------------------------------------
--  4. roles
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
--  5. role_assignments  (N:M mit Zeitraum)
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
--  6. meetings
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
--  7. agenda_items
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
    -- fk_ai_tension folgt nach tensions (zirkuläre Abhängigkeit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  8. tensions
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

-- FK agenda_items → tensions (zirkuläre Abhängigkeit, daher nachträglich)
ALTER TABLE `agenda_items`
    ADD CONSTRAINT `fk_ai_tension`
        FOREIGN KEY (`tension_id`) REFERENCES `tensions` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ------------------------------------------------------------
--  9. agreements
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

-- FK tensions → agreements (zirkuläre Abhängigkeit, daher nachträglich)
ALTER TABLE `tensions`
    ADD CONSTRAINT `fk_tensions_resolved`
        FOREIGN KEY (`resolved_by`) REFERENCES `agreements` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ------------------------------------------------------------
--  10. agreement_versions
--  Snapshot vor jeder Änderung — automatisch via AgreementModel.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agreement_versions` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `agreement_id` INT UNSIGNED  NOT NULL,
    `version`      SMALLINT      NOT NULL DEFAULT 1  COMMENT 'Laufende Versionsnummer',
    `title`        VARCHAR(255)  NOT NULL,
    `driver`       TEXT                   DEFAULT NULL,
    `body`         TEXT                   DEFAULT NULL,
    `agreed_at`    DATE          NOT NULL,
    `review_date`  DATE                   DEFAULT NULL,
    `status`       ENUM(
                       'active',
                       'expired',
                       'review_due',
                       'draft'
                   ) NOT NULL DEFAULT 'active',
    `changed_by`   INT UNSIGNED           DEFAULT NULL  COMMENT 'FK → members.id',
    `change_note`  VARCHAR(255)           DEFAULT NULL  COMMENT 'Optionaler Änderungsgrund',
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_agv_version` (`agreement_id`, `version`),
    KEY `idx_agv_agreement` (`agreement_id`),
    KEY `idx_agv_changed_by` (`changed_by`),
    CONSTRAINT `fk_agv_agreement`
        FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_agv_changed_by`
        FOREIGN KEY (`changed_by`) REFERENCES `members` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Vollständige Versionshistorie jeder Vereinbarung';

-- ------------------------------------------------------------
--  11. delegations
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `delegations` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `from_circle`    INT UNSIGNED  NOT NULL,
    `to_circle`      INT UNSIGNED  NOT NULL,
    `description`    TEXT                   DEFAULT NULL,
    `notes`          TEXT                   DEFAULT NULL  COMMENT 'Freitext-Beschreibung der Delegation',
    `status`         ENUM('active','ended') NOT NULL DEFAULT 'active',
    `started_at`     DATE                   DEFAULT NULL,
    `ended_at`       DATE                   DEFAULT NULL,
    `rep_link_role`  INT UNSIGNED           DEFAULT NULL  COMMENT 'Repräsentanten-Link-Rolle',
    `del_link_role`  INT UNSIGNED           DEFAULT NULL  COMMENT 'Delegierten-Link-Rolle',
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_del_from`   (`from_circle`),
    KEY `idx_del_to`     (`to_circle`),
    KEY `idx_del_status` (`status`),
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
--  Seed: Platzhalter-Admin
--  Wird vom Installer (public/install.php) automatisch entfernt
--  und durch den echten, per Nextcloud verknüpften Admin ersetzt.
--  Bei manueller Installation: members-Zeile löschen und stattdessen
--  ein Mitglied per erstem Nextcloud-Login anlegen lassen, danach
--  permission_level manuell auf 'admin' setzen.
-- ------------------------------------------------------------
INSERT INTO `members` (`name`, `email`, `password_hash`, `is_admin`, `permission_level`)
VALUES (
    'Administrator',
    'admin@example.org',
    '$2y$12$placeholder_change_this_hash_immediately',
    1,
    'admin'
);
