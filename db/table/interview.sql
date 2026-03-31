CREATE TABLE interview (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  qnaire_id INT(10) UNSIGNED NOT NULL,
  participant_id INT(10) UNSIGNED NOT NULL,
  site_id INT(10) UNSIGNED NULL DEFAULT NULL,
  method ENUM('phone', 'web') NOT NULL DEFAULT 'phone',
  current_page_rank INT(10) UNSIGNED NULL DEFAULT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NULL DEFAULT NULL,
  note MEDIUMTEXT NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_participant_id_qnaire_id (participant_id ASC, qnaire_id ASC),
  INDEX fk_participant_id (participant_id ASC),
  INDEX fk_qnaire_id (qnaire_id ASC),
  INDEX dk_start_datetime (start_datetime ASC),
  INDEX dk_end_datetime (end_datetime ASC),
  INDEX fk_site_id (site_id ASC),
  CONSTRAINT fk_interview_participant_id
    FOREIGN KEY (participant_id)
    REFERENCES cenozo.participant (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES sabretooth.qnaire (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interview_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
