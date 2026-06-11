CREATE TABLE qnaire_has_collection (
  qnaire_id int(10) unsigned NOT NULL,
  collection_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (qnaire_id,collection_id),
  KEY fk_collection_id (collection_id),
  KEY fk_qnaire_id (qnaire_id),
  CONSTRAINT fk_qnaire_has_collection_collection_id
    FOREIGN KEY (collection_id)
    REFERENCES cenozo.collection (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_qnaire_has_collection_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES qnaire (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;