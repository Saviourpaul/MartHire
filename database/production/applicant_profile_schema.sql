-- MySQL / MariaDB SQL for:
--   2026_11_03_110000_create_applicant_profile_sections
--   2026_11_03_110001_add_preferred_workplace_to_candidate_job_preferences
--   2026_11_03_120000_add_disability_details_to_candidate_self_identifications
--
-- Select the production database before running this script. The statements are
-- safe to rerun for a fully applied schema. They do not record migration rows;
-- run `php artisan migrate --force` after deployment so Laravel records them.

SELECT DATABASE() AS selected_database;

CREATE TABLE IF NOT EXISTS `applicant_profiles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `professional_headline` VARCHAR(160) NULL,
    `about` TEXT NULL,
    `resume_path` VARCHAR(255) NULL,
    `resume_original_name` VARCHAR(255) NULL,
    `resume_mime_type` VARCHAR(120) NULL,
    `resume_size` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `applicant_profiles_user_id_unique` (`user_id`),
    CONSTRAINT `applicant_profiles_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `candidate_profile_employments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `applicant_profile_id` BIGINT UNSIGNED NOT NULL,
    `company` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `location` VARCHAR(255) NULL,
    `employment_type` VARCHAR(50) NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `is_current` TINYINT(1) NOT NULL DEFAULT 0,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `candidate_employments_profile_start_date_index` (`applicant_profile_id`, `start_date`),
    CONSTRAINT `candidate_profile_employments_applicant_profile_id_foreign`
        FOREIGN KEY (`applicant_profile_id`) REFERENCES `applicant_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `candidate_profile_educations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `applicant_profile_id` BIGINT UNSIGNED NOT NULL,
    `institution` VARCHAR(255) NOT NULL,
    `qualification` VARCHAR(255) NOT NULL,
    `field_of_study` VARCHAR(255) NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `candidate_educations_profile_end_date_index` (`applicant_profile_id`, `end_date`),
    CONSTRAINT `candidate_profile_educations_applicant_profile_id_foreign`
        FOREIGN KEY (`applicant_profile_id`) REFERENCES `applicant_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `candidate_profile_skills` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `applicant_profile_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(80) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `candidate_profile_skills_applicant_profile_id_name_unique` (`applicant_profile_id`, `name`),
    CONSTRAINT `candidate_profile_skills_applicant_profile_id_foreign`
        FOREIGN KEY (`applicant_profile_id`) REFERENCES `applicant_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `candidate_profile_links` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `applicant_profile_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `url` TEXT NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `candidate_profile_links_applicant_profile_id_platform_unique` (`applicant_profile_id`, `platform`),
    CONSTRAINT `candidate_profile_links_applicant_profile_id_foreign`
        FOREIGN KEY (`applicant_profile_id`) REFERENCES `applicant_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `candidate_self_identifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `applicant_profile_id` BIGINT UNSIGNED NOT NULL,
    `gender_identity` TEXT NULL,
    `disability_status` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `candidate_self_identifications_applicant_profile_id_unique` (`applicant_profile_id`),
    CONSTRAINT `candidate_self_identifications_applicant_profile_id_foreign`
        FOREIGN KEY (`applicant_profile_id`) REFERENCES `applicant_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Add the preference column only when it is absent.
SET @preferred_workplace_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'candidate_job_preferences'
      AND `COLUMN_NAME` = 'preferred_workplace'
);
SET @preferred_workplace_sql := IF(
    @preferred_workplace_exists = 0,
    'ALTER TABLE `candidate_job_preferences` ADD COLUMN `preferred_workplace` VARCHAR(24) NULL AFTER `preferred_job_type`',
    'SELECT ''preferred_workplace already exists; skipped'' AS migration_status'
);
PREPARE applicant_profile_migration_statement FROM @preferred_workplace_sql;
EXECUTE applicant_profile_migration_statement;
DEALLOCATE PREPARE applicant_profile_migration_statement;

-- The migration skips this change when its parent table does not exist.
SET @self_identification_table_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`TABLES`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'candidate_self_identifications'
);
SET @disability_details_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'candidate_self_identifications'
      AND `COLUMN_NAME` = 'disability_details'
);
SET @disability_details_sql := IF(
    @self_identification_table_exists = 1 AND @disability_details_exists = 0,
    'ALTER TABLE `candidate_self_identifications` ADD COLUMN `disability_details` TEXT NULL AFTER `disability_status`',
    'SELECT ''disability_details already exists or parent table is absent; skipped'' AS migration_status'
);
PREPARE applicant_profile_migration_statement FROM @disability_details_sql;
EXECUTE applicant_profile_migration_statement;
DEALLOCATE PREPARE applicant_profile_migration_statement;
