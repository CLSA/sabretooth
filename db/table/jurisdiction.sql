CREATE TABLE jurisdiction (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  site_id INT(10) UNSIGNED NOT NULL,
  postcode VARCHAR(7) NOT NULL,
  longitude FLOAT NOT NULL,
  latitude FLOAT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_postcode (postcode ASC),
  INDEX fk_site_id (site_id ASC),
  INDEX dk_postcode (postcode ASC),
  CONSTRAINT fk_jurisdiction_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
