CREATE TABLE qnaire (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  rank int(11) NOT NULL,
  script_id int(10) unsigned NOT NULL,
  allow_missing_consent tinyint(1) NOT NULL DEFAULT 1,
  web_version tinyint(1) NOT NULL DEFAULT 0,
  delay_offset int(11) NOT NULL DEFAULT 0,
  delay_unit enum('day','week','month') NOT NULL DEFAULT 'week',
  PRIMARY KEY (id),
  UNIQUE KEY uq_rank (rank),
  KEY fk_script_id (script_id),
  CONSTRAINT fk_qnaire_script_id
    FOREIGN KEY (script_id)
    REFERENCES cenozo.script (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
