CREATE TABLE interview_last_assignment (
  interview_id INT(10) UNSIGNED NOT NULL,
  assignment_id INT(10) UNSIGNED NULL DEFAULT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (interview_id),
  INDEX fk_assignment_id (assignment_id ASC),
  CONSTRAINT fk_interview_last_assignment_assignment_id
    FOREIGN KEY (assignment_id)
    REFERENCES sabretooth.assignment (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_last_assignment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES sabretooth.interview (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
