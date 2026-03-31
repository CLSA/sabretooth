CREATE TABLE qnaire_has_hold_type (
  qnaire_id INT(10) UNSIGNED NOT NULL,
  hold_type_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (qnaire_id, hold_type_id),
  INDEX fk_hold_type_id (hold_type_id ASC),
  INDEX fk_qnaire_id (qnaire_id ASC),
  CONSTRAINT fk_qnaire_has_hold_type_hold_type_id
    FOREIGN KEY (hold_type_id)
    REFERENCES cenozo.hold_type (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_qnaire_has_hold_type_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES sabretooth.qnaire (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
