CREATE TABLE vacancy (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  site_id int(10) unsigned NOT NULL,
  datetime datetime NOT NULL,
  operators int(11) NOT NULL DEFAULT 1,
  appointments int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_site_id_datetime (site_id,datetime),
  KEY dk_datetime (datetime),
  KEY fk_site_id (site_id),
  CONSTRAINT fk_vacancy_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;