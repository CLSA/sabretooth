CREATE TABLE vacancy (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  site_id INT(10) UNSIGNED NOT NULL,
  datetime DATETIME NOT NULL,
  operators INT(11) NOT NULL DEFAULT 1,
  appointments INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_site_id_datetime (site_id ASC, datetime ASC),
  INDEX dk_datetime (datetime ASC),
  INDEX fk_site_id (site_id ASC),
  CONSTRAINT fk_vacancy_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
