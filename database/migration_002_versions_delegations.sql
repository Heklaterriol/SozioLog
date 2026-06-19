-- ============================================================
--  Migration: Versionshistorie + Delegations-Erweiterungen
--  Datei: database/migration_002_versions_delegations.sql
--  Einspielen: mysql -u user -p logbuch < database/migration_002_versions_delegations.sql
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
--  agreement_versions
--  Snapshot des Vereinbarungs-Inhalts vor jeder Änderung.
--  Wird automatisch via AgreementModel::update() befüllt.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agreement_versions` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `agreement_id` INT UNSIGNED  NOT NULL,
    `version`      SMALLINT      NOT NULL DEFAULT 1   COMMENT 'Laufende Versionsnummer',
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
--  delegations: Spalte 'notes' + 'status' ergänzen
--  (falls Tabelle schon existiert: ALTER; sonst beim ersten
--   schema.sql-Import bereits enthalten wenn man diese Datei
--   als Basis nimmt)
-- ------------------------------------------------------------
ALTER TABLE `delegations`
    ADD COLUMN IF NOT EXISTS `notes`      TEXT    DEFAULT NULL
        COMMENT 'Freitext-Beschreibung der Delegation'
        AFTER `description`,
    ADD COLUMN IF NOT EXISTS `status`     ENUM('active','ended') NOT NULL DEFAULT 'active'
        AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `started_at` DATE    DEFAULT NULL
        AFTER `status`,
    ADD COLUMN IF NOT EXISTS `ended_at`   DATE    DEFAULT NULL
        AFTER `started_at`;

-- Index auf status für Dashboard-Abfragen
ALTER TABLE `delegations`
    ADD INDEX IF NOT EXISTS `idx_del_status` (`status`);

SET foreign_key_checks = 1;
