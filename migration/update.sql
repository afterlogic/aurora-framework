DROP TABLE IF EXISTS `eav_objects`;
DROP TABLE IF EXISTS `eav_properties`;

-- ----------------------------------------------------------------------------
-- Table eav_entities
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_entities` (
  `id` BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,  
  `entity_type` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `module_name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_bool
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_bool` (
  `id` BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,  
  `id_entity` BIGINT(64) UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` TINYINT(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC),
  INDEX `idx_value` (`value` ASC),
  INDEX `fk_id_entity_idx` (`id_entity` ASC),
  CONSTRAINT `fk_eav_attributes_bool_id_entity`
    FOREIGN KEY (`id_entity`)
    REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_datetime
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_datetime` (
  `id` BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,  
  `id_entity` BIGINT(64) UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC),
  INDEX `idx_value` (`value` ASC),
  INDEX `fk_id_entity_idx` (`id_entity` ASC),
  CONSTRAINT `fk_eav_attributes_datetime_id_entity`
    FOREIGN KEY (`id_entity`)
    REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_int
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_int` (
  `id` BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,  
  `id_entity` BIGINT(64) UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC),
  INDEX `idx_value` (`value` ASC),
  INDEX `fk_id_entity_idx` (`id_entity` ASC),
  CONSTRAINT `fk_eav_attributes_int_id_entity`
    FOREIGN KEY (`id_entity`)
    REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_string
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_string` (
  `id` BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,  
  `id_entity` BIGINT(64) UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC),
  INDEX `idx_value` (`value` ASC),
  INDEX `fk_id_entity_idx` (`id_entity` ASC),
  CONSTRAINT `fk_eav_attributes_string_id_entity`
    FOREIGN KEY (`id_entity`)
    REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_text
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_text` (
  `id` BIGINT(64) UNSIGNED NOT NULL AUTO_INCREMENT,  
  `id_entity` BIGINT(64) UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` TEXT CHARACTER SET 'utf8' NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC),
  INDEX `fk_id_entity_idx` (`id_entity` ASC),
  CONSTRAINT `fk_eav_attributes_text_id_entity`
    FOREIGN KEY (`id_entity`)
    REFERENCES `eav_entities` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;
