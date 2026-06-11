CREATE TABLE qnaire_has_site (
  qnaire_id int(10) unsigned NOT NULL,
  site_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (qnaire_id,site_id),
  KEY fk_site_id (site_id),
  KEY fk_qnaire_id (qnaire_id),
  CONSTRAINT fk_qnaire_has_site_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES qnaire (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_qnaire_has_site_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;