CREATE TABLE queue_has_participant (
  queue_id int(10) unsigned NOT NULL,
  participant_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  site_id int(10) unsigned DEFAULT NULL,
  qnaire_id int(10) unsigned DEFAULT NULL,
  start_qnaire_date date DEFAULT NULL,
  PRIMARY KEY (queue_id,participant_id),
  KEY fk_participant_id (participant_id),
  KEY fk_queue_id (queue_id),
  KEY fk_qnaire_id (qnaire_id),
  KEY fk_site_id (site_id),
  CONSTRAINT fk_queue_has_participant_participant_id
    FOREIGN KEY (participant_id)
    REFERENCES cenozo.participant (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_queue_has_participant_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES qnaire (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_queue_has_participant_queue_id
    FOREIGN KEY (queue_id)
    REFERENCES queue (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_queue_has_participant_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;