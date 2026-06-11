CREATE TABLE qnaire_has_hold_type (
  qnaire_id int(10) unsigned NOT NULL,
  hold_type_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (qnaire_id,hold_type_id),
  KEY fk_hold_type_id (hold_type_id),
  KEY fk_qnaire_id (qnaire_id),
  CONSTRAINT fk_qnaire_has_hold_type_hold_type_id
    FOREIGN KEY (hold_type_id)
    REFERENCES cenozo.hold_type (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_qnaire_has_hold_type_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES qnaire (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;