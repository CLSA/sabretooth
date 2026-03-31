CREATE TABLE qnaire_has_site (
  qnaire_id INT(10) UNSIGNED NOT NULL,
  site_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (qnaire_id, site_id),
  INDEX fk_site_id (site_id ASC),
  INDEX fk_qnaire_id (qnaire_id ASC),
  CONSTRAINT fk_qnaire_has_site_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES sabretooth.qnaire (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_qnaire_has_site_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
