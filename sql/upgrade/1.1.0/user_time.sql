-- add in the new user_time table
CREATE  TABLE IF NOT EXISTS `user_time` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  `date` DATE NOT NULL ,
  `time` FLOAT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  UNIQUE INDEX `uq_user_site_role_date` (`user_id` ASC, `role_id` ASC, `site_id` ASC, `date` ASC) ,
  INDEX `dk_date` (`date` ASC) ,
  CONSTRAINT `fk_operator_time_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_operator_time_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_operator_time_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
