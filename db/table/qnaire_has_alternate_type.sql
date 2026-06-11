CREATE TABLE qnaire_has_alternate_type (
  qnaire_id int(10) unsigned NOT NULL,
  alternate_type_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (qnaire_id,alternate_type_id),
  KEY fk_alternate_type_id (alternate_type_id),
  KEY fk_qnaire_id (qnaire_id),
  CONSTRAINT fk_qnaire_has_alternate_type_alternate_type_id
    FOREIGN KEY (alternate_type_id)
    REFERENCES cenozo.alternate_type (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_qnaire_has_alternate_type_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES qnaire (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;