CREATE TABLE interview (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  qnaire_id int(10) unsigned NOT NULL,
  participant_id int(10) unsigned NOT NULL,
  site_id int(10) unsigned DEFAULT NULL,
  method enum('phone','web') NOT NULL DEFAULT 'phone',
  current_page_rank int(10) unsigned DEFAULT NULL,
  start_datetime datetime NOT NULL,
  end_datetime datetime DEFAULT NULL,
  note mediumtext DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_participant_id_qnaire_id (participant_id,qnaire_id),
  KEY fk_participant_id (participant_id),
  KEY fk_qnaire_id (qnaire_id),
  KEY dk_start_datetime (start_datetime),
  KEY dk_end_datetime (end_datetime),
  KEY fk_site_id (site_id),
  CONSTRAINT fk_interview_participant_id
    FOREIGN KEY (participant_id)
    REFERENCES cenozo.participant (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES qnaire (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='aka: qnaire_has_participant';
