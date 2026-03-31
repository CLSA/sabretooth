CREATE TABLE phone_call (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  assignment_id INT(10) UNSIGNED NOT NULL,
  phone_id INT(10) UNSIGNED NOT NULL,
  start_datetime DATETIME NOT NULL COMMENT 'The time the call started.',
  end_datetime DATETIME NULL DEFAULT NULL COMMENT 'The time the call endede.',
  status ENUM('contacted', 'busy', 'no answer', 'machine message', 'machine no message', 'fax', 'disconnected', 'wrong number', 'not reached', 'hang up', 'soft refusal') NULL DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX fk_assignment_id (assignment_id ASC),
  INDEX dk_status (status ASC),
  INDEX fk_phone_id (phone_id ASC),
  INDEX dk_start_datetime (start_datetime ASC),
  INDEX dk_end_datetime (end_datetime ASC),
  CONSTRAINT fk_phone_call_assignment_id
    FOREIGN KEY (assignment_id)
    REFERENCES sabretooth.assignment (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_phone_call_phone_id
    FOREIGN KEY (phone_id)
    REFERENCES cenozo.phone (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
