DROP TABLE IF EXISTS `eav_objects`;
DROP TABLE IF EXISTS `eav_properties`;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_bool
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_bool` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_entity` INT(11) NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` TINYINT(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC),
  INDEX `idx_value` (`value` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_datetime
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_datetime` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_entity` INT(11) NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC),
  INDEX `idx_value` (`value` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_int
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_int` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_entity` INT(11) NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC),
  INDEX `idx_value` (`value` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_string
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_string` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_entity` INT(11) NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC),
  INDEX `idx_value` (`value` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_attributes_text
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_attributes_text` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_entity` INT(11) NULL DEFAULT NULL,
  `name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `value` TEXT CHARACTER SET 'utf8' NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_unique` (`id_entity` ASC, `name` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- ----------------------------------------------------------------------------
-- Table eav_entities
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eav_entities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  `module_name` VARCHAR(255) CHARACTER SET 'utf8' NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;
