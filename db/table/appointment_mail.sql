CREATE TABLE appointment_mail (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  site_id int(10) unsigned NOT NULL,
  language_id int(10) unsigned NOT NULL,
  from_name varchar(255) DEFAULT NULL,
  from_address varchar(127) NOT NULL,
  cc_address varchar(255) DEFAULT NULL,
  bcc_address varchar(255) DEFAULT NULL,
  delay_offset int(10) unsigned DEFAULT NULL,
  delay_unit enum('days','immediately') NOT NULL DEFAULT 'days',
  subject varchar(255) NOT NULL,
  body mediumtext NOT NULL,
  PRIMARY KEY (id),
  KEY fk_site_id (site_id),
  KEY fk_language_id (language_id),
  CONSTRAINT fk_appointment_mail_language_id
    FOREIGN KEY (language_id)
    REFERENCES cenozo.language (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_mail_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
