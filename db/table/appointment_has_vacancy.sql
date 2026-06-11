CREATE TABLE appointment_has_vacancy (
  appointment_id int(10) unsigned NOT NULL,
  vacancy_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (appointment_id,vacancy_id),
  KEY fk_vacancy_id (vacancy_id),
  KEY fk_appointment_id (appointment_id),
  CONSTRAINT fk_appointment_has_vacancy_appointment_id
    FOREIGN KEY (appointment_id)
    REFERENCES appointment (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_has_vacancy_vacancy_id
    FOREIGN KEY (vacancy_id)
    REFERENCES vacancy (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;