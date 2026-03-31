CREATE TABLE appointment_has_mail (
  appointment_id INT(10) UNSIGNED NOT NULL,
  mail_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (appointment_id, mail_id),
  UNIQUE INDEX uq_appointment_id_mail_id (appointment_id ASC, mail_id ASC),
  INDEX fk_mail_id (mail_id ASC),
  INDEX fk_appointment_id (appointment_id ASC),
  CONSTRAINT fk_appointment_has_mail_appointment_id
    FOREIGN KEY (appointment_id)
    REFERENCES sabretooth.appointment (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_has_mail_mail_id
    FOREIGN KEY (mail_id)
    REFERENCES cenozo.mail (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
