CREATE TABLE assignment_last_phone_call (
  assignment_id INT(10) UNSIGNED NOT NULL,
  phone_call_id INT(10) UNSIGNED NULL DEFAULT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (assignment_id),
  INDEX fk_phone_call_id (phone_call_id ASC),
  CONSTRAINT fk_assignment_last_phone_call_assignment_id
    FOREIGN KEY (assignment_id)
    REFERENCES sabretooth.assignment (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_assignment_last_phone_call_phone_call_id
    FOREIGN KEY (phone_call_id)
    REFERENCES sabretooth.phone_call (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
