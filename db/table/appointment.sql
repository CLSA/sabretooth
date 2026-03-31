CREATE TABLE appointment (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  interview_id INT(10) UNSIGNED NOT NULL,
  user_id INT(10) UNSIGNED NULL DEFAULT NULL,
  phone_id INT(10) UNSIGNED NULL DEFAULT NULL,
  assignment_id INT(10) UNSIGNED NULL DEFAULT NULL,
  override TINYINT(1) NOT NULL DEFAULT 0,
  outcome ENUM('reached', 'not reached', 'cancelled') NULL DEFAULT NULL,
  start_vacancy_id INT(10) UNSIGNED NULL DEFAULT NULL COMMENT 'Do not edit, determined by trigger.',
  end_vacancy_id INT(10) UNSIGNED NULL DEFAULT NULL COMMENT 'Do not edit, determined by trigger.',
  PRIMARY KEY (id),
  INDEX fk_assignment_id (assignment_id ASC),
  INDEX fk_phone_id (phone_id ASC),
  INDEX fk_interview_id (interview_id ASC),
  INDEX fk_user_id (user_id ASC),
  INDEX dk_outcome (outcome ASC),
  INDEX fk_start_vacancy_id (start_vacancy_id ASC),
  INDEX fk_end_vacancy_id (end_vacancy_id ASC),
  CONSTRAINT fk_appointment_assignment_id
    FOREIGN KEY (assignment_id)
    REFERENCES sabretooth.assignment (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_end_vacancy_id
    FOREIGN KEY (end_vacancy_id)
    REFERENCES sabretooth.vacancy (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES sabretooth.interview (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_phone_id
    FOREIGN KEY (phone_id)
    REFERENCES cenozo.phone (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_start_vacancy_id
    FOREIGN KEY (start_vacancy_id)
    REFERENCES sabretooth.vacancy (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
