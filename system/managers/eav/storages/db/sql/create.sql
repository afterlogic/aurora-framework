/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;

CREATE TABLE IF NOT EXISTS `eav_attributes_bool` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     TINYINT(1)                   DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `idx_value` (`value`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `fk_eav_attributes_bool_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 38
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `eav_attributes_datetime` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     DATETIME                     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `idx_value` (`value`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `fk_eav_attributes_datetime_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 5
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `eav_attributes_int` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     INT(11)                      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `idx_value` (`value`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `fk_eav_attributes_int_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 34
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `eav_attributes_string` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     VARCHAR(255)                 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `idx_value` (`value`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `fk_eav_attributes_string_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 66
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `eav_attributes_text` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `fk_eav_attributes_text_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 2
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `eav_entities` (
  `id`          BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(255)                 DEFAULT NULL,
  `module_name` VARCHAR(255)                 DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 9
  DEFAULT CHARSET = utf8;

/*!40101 SET SQL_MODE = IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS = IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;