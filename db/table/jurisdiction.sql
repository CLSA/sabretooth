CREATE TABLE jurisdiction (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  site_id int(10) unsigned NOT NULL,
  postcode varchar(7) NOT NULL,
  longitude float NOT NULL,
  latitude float NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_postcode (postcode),
  KEY fk_site_id (site_id),
  KEY dk_postcode (postcode),
  CONSTRAINT fk_jurisdiction_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
