CREATE TABLE assignment (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  user_id INT(10) UNSIGNED NOT NULL,
  role_id INT(10) UNSIGNED NOT NULL,
  site_id INT(10) UNSIGNED NOT NULL COMMENT 'The site from which the user was assigned.',
  interview_id INT(10) UNSIGNED NOT NULL,
  queue_id INT(10) UNSIGNED NOT NULL COMMENT 'The queue that the assignment came from.',
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX fk_interview_id (interview_id ASC),
  INDEX fk_queue_id (queue_id ASC),
  INDEX dk_start_datetime (start_datetime ASC),
  INDEX dk_end_datetime (end_datetime ASC),
  INDEX fk_user_id (user_id ASC),
  INDEX fk_site_id (site_id ASC),
  INDEX fk_role_id (role_id ASC),
  CONSTRAINT fk_assignment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES sabretooth.interview (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_assignment_queue_id
    FOREIGN KEY (queue_id)
    REFERENCES sabretooth.queue (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_assignment_role_id
    FOREIGN KEY (role_id)
    REFERENCES cenozo.role (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_assignment_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_assignment_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
