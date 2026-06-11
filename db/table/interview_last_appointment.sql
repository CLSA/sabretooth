CREATE TABLE interview_last_appointment (
  interview_id int(10) unsigned NOT NULL,
  appointment_id int(10) unsigned DEFAULT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (interview_id),
  KEY fk_appointment_id (appointment_id),
  CONSTRAINT fk_interview_last_appointment_appointment_id
    FOREIGN KEY (appointment_id)
    REFERENCES appointment (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_last_appointment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES interview (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;