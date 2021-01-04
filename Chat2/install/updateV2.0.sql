CREATE TABLE IF NOT EXISTS `chat_channels` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `url` VARCHAR(255) NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  `users_id` INT NOT NULL,
  `status` CHAR(1) NULL DEFAULT 'a',
  PRIMARY KEY (`id`))
ENGINE = InnoDB;