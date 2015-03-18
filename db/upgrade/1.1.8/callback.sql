CREATE  TABLE IF NOT EXISTS `callback` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `phone_id` INT UNSIGNED NULL DEFAULT NULL ,
  `assignment_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'This callback\'s assignment.' ,
  `datetime` DATETIME NOT NULL ,
  `reached` TINYINT(1) NULL DEFAULT NULL COMMENT 'If the callback was met, whether the participant was reached.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  INDEX `dk_reached` (`reached` ASC) ,
  INDEX `fk_phone_id` (`phone_id` ASC) ,
  INDEX `dk_datetime` (`datetime` ASC) ,
  CONSTRAINT `fk_callback_participant_id`
    FOREIGN KEY (`participant_id` )
    REFERENCES `participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_callback_assignment_id`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_callback_phone_id`
    FOREIGN KEY (`phone_id` )
    REFERENCES `phone` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
