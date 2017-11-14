
CREATE TABLE IF NOT EXISTS `%PREFIX%eav_entities` (
  `id`          BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`        CHAR(36) 					 NOT NULL,
  `parent_uuid`        CHAR(36) 					 NOT NULL,
  `entity_type` VARCHAR(255)                 DEFAULT NULL,
  `module_name` VARCHAR(255)                 DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%eav_attributes_bool` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     TINYINT(1)                   DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `idx_value` (`value`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `%PREFIX%fk_eav_attributes_bool_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `%PREFIX%eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%eav_attributes_datetime` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     DATETIME                     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `idx_value` (`value`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `%PREFIX%fk_eav_attributes_datetime_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `%PREFIX%eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%eav_attributes_int` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     INT(11)                      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `idx_value` (`value`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `%PREFIX%fk_eav_attributes_int_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `%PREFIX%eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%eav_attributes_string` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     VARCHAR(255)                 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `idx_value` (`value`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `%PREFIX%fk_eav_attributes_string_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `%PREFIX%eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `%PREFIX%eav_attributes_text` (
  `id`        BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entity` BIGINT(64) UNSIGNED          DEFAULT NULL,
  `name`      VARCHAR(255)                 DEFAULT NULL,
  `value`     TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique` (`id_entity`, `name`),
  KEY `fk_id_entity_idx` (`id_entity`),
  CONSTRAINT `%PREFIX%fk_eav_attributes_text_id_entity` FOREIGN KEY (`id_entity`) REFERENCES `%PREFIX%eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8;
