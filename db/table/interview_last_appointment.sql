CREATE TABLE interview_last_appointment (
  interview_id INT(10) UNSIGNED NOT NULL,
  appointment_id INT(10) UNSIGNED NULL DEFAULT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (interview_id),
  INDEX fk_appointment_id (appointment_id ASC),
  CONSTRAINT fk_interview_last_appointment_appointment_id
    FOREIGN KEY (appointment_id)
    REFERENCES sabretooth.appointment (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_last_appointment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES sabretooth.interview (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
