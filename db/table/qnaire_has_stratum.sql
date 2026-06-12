CREATE TABLE qnaire_has_stratum (
  qnaire_id int(10) unsigned NOT NULL,
  stratum_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (qnaire_id,stratum_id),
  KEY fk_stratum_id (stratum_id),
  KEY fk_qnaire_id (qnaire_id),
  CONSTRAINT fk_qnaure_has_stratum_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES qnaire (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_qnaure_has_stratum_stratum_id
    FOREIGN KEY (stratum_id)
    REFERENCES cenozo.stratum (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
