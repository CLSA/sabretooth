CREATE TABLE queue_has_participant (
  queue_id INT(10) UNSIGNED NOT NULL,
  participant_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  site_id INT(10) UNSIGNED NULL DEFAULT NULL,
  qnaire_id INT(10) UNSIGNED NULL DEFAULT NULL,
  start_qnaire_date DATE NULL DEFAULT NULL,
  PRIMARY KEY (queue_id, participant_id),
  INDEX fk_participant_id (participant_id ASC),
  INDEX fk_queue_id (queue_id ASC),
  INDEX fk_qnaire_id (qnaire_id ASC),
  INDEX fk_site_id (site_id ASC),
  CONSTRAINT fk_queue_has_participant_participant_id
    FOREIGN KEY (participant_id)
    REFERENCES cenozo.participant (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_queue_has_participant_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES sabretooth.qnaire (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_queue_has_participant_queue_id
    FOREIGN KEY (queue_id)
    REFERENCES sabretooth.queue (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_queue_has_participant_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
