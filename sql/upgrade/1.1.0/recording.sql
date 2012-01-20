CREATE  TABLE IF NOT EXISTS `recording` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `interview_id` INT UNSIGNED NOT NULL ,
  `assignment_id` INT UNSIGNED NULL DEFAULT NULL ,
  `rank` INT UNSIGNED NOT NULL ,
  `processed` TINYINT(1)  NOT NULL DEFAULT false ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_interview_id` (`interview_id` ASC) ,
  UNIQUE INDEX `uq_interview_rank` (`interview_id` ASC, `rank` ASC) ,
  INDEX `fk_assignment_id1` (`assignment_id` ASC) ,
  CONSTRAINT `fk_recording_interview`
    FOREIGN KEY (`interview_id` )
    REFERENCES `interview` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_id1`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
