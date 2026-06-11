CREATE TABLE appointment_has_mail (
  appointment_id int(10) unsigned NOT NULL,
  mail_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (appointment_id,mail_id),
  UNIQUE KEY uq_appointment_id_mail_id (appointment_id,mail_id),
  KEY fk_mail_id (mail_id),
  KEY fk_appointment_id (appointment_id),
  CONSTRAINT fk_appointment_has_mail_appointment_id
    FOREIGN KEY (appointment_id)
    REFERENCES appointment (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_has_mail_mail_id
    FOREIGN KEY (mail_id)
    REFERENCES cenozo.mail (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;