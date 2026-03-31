CREATE TABLE appointment_has_vacancy (
  appointment_id INT(10) UNSIGNED NOT NULL,
  vacancy_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (appointment_id, vacancy_id),
  INDEX fk_vacancy_id (vacancy_id ASC),
  INDEX fk_appointment_id (appointment_id ASC),
  CONSTRAINT fk_appointment_has_vacancy_appointment_id
    FOREIGN KEY (appointment_id)
    REFERENCES sabretooth.appointment (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_has_vacancy_vacancy_id
    FOREIGN KEY (vacancy_id)
    REFERENCES sabretooth.vacancy (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
