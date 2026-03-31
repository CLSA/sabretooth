CREATE TABLE qnaire (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  rank INT(11) NOT NULL,
  script_id INT(10) UNSIGNED NOT NULL,
  allow_missing_consent TINYINT(1) NOT NULL DEFAULT 1,
  web_version TINYINT(1) NOT NULL DEFAULT 0,
  delay_offset INT(11) NOT NULL DEFAULT 0,
  delay_unit ENUM('day', 'week', 'month') NOT NULL DEFAULT 'week',
  PRIMARY KEY (id),
  UNIQUE INDEX uq_rank (rank ASC),
  INDEX fk_script_id (script_id ASC),
  CONSTRAINT fk_qnaire_script_id
    FOREIGN KEY (script_id)
    REFERENCES cenozo.script (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
