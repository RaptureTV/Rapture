-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';


-- -----------------------------------------------------
-- Table `chat_messages`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `message` LONGBLOB NOT NULL,
  `reply_chats_id` INT NULL,
  `from_users_id` INT NOT NULL,
  `to_users_id` INT NULL,
  `room_users_id` INT NULL,
  `created` DATETIME NULL,
  `file` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_chats_chats_idx` (`reply_chats_id` ASC),
  INDEX `fk_chats_users1_idx` (`from_users_id` ASC),
  INDEX `fk_chats_users2_idx` (`room_users_id` ASC),
  INDEX `fk_chats_users3_idx` (`to_users_id` ASC),
  CONSTRAINT `fk_chats_chats`
    FOREIGN KEY (`reply_chats_id`)
    REFERENCES `chat_messages` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `chat_online_status`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_online_status` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `status` CHAR(1) NOT NULL DEFAULT 'o' COMMENT 'o = online\na = away\ni = invisible\nf = offline\n',
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  `users_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_chat_online_status_users1_idx` (`users_id` ASC),
  INDEX `chat_online_status_users_modified` (`modified` ASC)
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `chat_ban`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_ban` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `users_id` INT NOT NULL,
  `banned_users_id` INT NOT NULL,
  `created` DATETIME NULL COMMENT '	',
  PRIMARY KEY (`id`),
  INDEX `fk_chat_ban_users1_idx` (`users_id` ASC),
  INDEX `fk_chat_ban_users2_idx` (`banned_users_id` ASC)
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `chat_message_log`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_message_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `chat_messages_id` INT NOT NULL,
  `users_id` INT NOT NULL,
  `status` CHAR(1) NOT NULL DEFAULT 'a',
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_chat_message_log_chat_messages1_idx` (`chat_messages_id` ASC),
  INDEX `fk_chat_message_log_users1_idx` (`users_id` ASC),
  CONSTRAINT `fk_chat_message_log_chat_messages1`
    FOREIGN KEY (`chat_messages_id`)
    REFERENCES `chat_messages` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB;


CREATE TABLE IF NOT EXISTS `chat_users` (
  `id` INT NOT NULL,
  `user` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL,
  `name` VARCHAR(255) NULL,
  `identification` VARCHAR(255) NULL,
  `photoURL` VARCHAR(255) NULL,
  `channelName` VARCHAR(255) NULL,
  donationLink VARCHAR(255) NULL,
  `status` CHAR(1) NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

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


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
