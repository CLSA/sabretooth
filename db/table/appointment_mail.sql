CREATE TABLE appointment_mail (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  site_id INT(10) UNSIGNED NOT NULL,
  language_id INT(10) UNSIGNED NOT NULL,
  from_name VARCHAR(255) NULL DEFAULT NULL,
  from_address VARCHAR(127) NOT NULL,
  cc_address VARCHAR(255) NULL DEFAULT NULL,
  bcc_address VARCHAR(255) NULL DEFAULT NULL,
  delay_offset INT(10) UNSIGNED NULL DEFAULT NULL,
  delay_unit ENUM('days', 'immediately') NOT NULL DEFAULT 'days',
  subject VARCHAR(255) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_site_id (site_id ASC),
  INDEX fk_language_id (language_id ASC),
  CONSTRAINT fk_appointment_mail_language_id
    FOREIGN KEY (language_id)
    REFERENCES cenozo.language (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_mail_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
