CREATE TABLE appointment (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  interview_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned DEFAULT NULL,
  phone_id int(10) unsigned DEFAULT NULL,
  assignment_id int(10) unsigned DEFAULT NULL,
  override tinyint(1) NOT NULL DEFAULT 0,
  outcome enum('reached','not reached','cancelled') DEFAULT NULL,
  start_vacancy_id int(10) unsigned DEFAULT NULL COMMENT 'Do not edit, determined by trigger.',
  end_vacancy_id int(10) unsigned DEFAULT NULL COMMENT 'Do not edit, determined by trigger.',
  PRIMARY KEY (id),
  KEY fk_assignment_id (assignment_id),
  KEY fk_phone_id (phone_id),
  KEY fk_interview_id (interview_id),
  KEY fk_user_id (user_id),
  KEY dk_outcome (outcome),
  KEY fk_start_vacancy_id (start_vacancy_id),
  KEY fk_end_vacancy_id (end_vacancy_id),
  CONSTRAINT fk_appointment_assignment_id
    FOREIGN KEY (assignment_id)
    REFERENCES assignment (id)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_end_vacancy_id
    FOREIGN KEY (end_vacancy_id)
    REFERENCES vacancy (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES interview (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_phone_id
    FOREIGN KEY (phone_id)
    REFERENCES cenozo.phone (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_start_vacancy_id
    FOREIGN KEY (start_vacancy_id)
    REFERENCES vacancy (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
