CREATE TABLE qnaire_has_collection (
  qnaire_id INT(10) UNSIGNED NOT NULL,
  collection_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (qnaire_id, collection_id),
  INDEX fk_collection_id (collection_id ASC),
  INDEX fk_qnaire_id (qnaire_id ASC),
  CONSTRAINT fk_qnaire_has_collection_collection_id
    FOREIGN KEY (collection_id)
    REFERENCES cenozo.collection (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_qnaire_has_collection_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES sabretooth.qnaire (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
