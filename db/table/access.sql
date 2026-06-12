CREATE TABLE access (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  user_id int(10) unsigned NOT NULL,
  role_id int(10) unsigned NOT NULL,
  site_id int(10) unsigned NOT NULL,
  datetime datetime DEFAULT NULL,
  microtime double DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_id_role_id_site_id (user_id,role_id,site_id),
  KEY fk_user_id (user_id),
  KEY fk_role_id (role_id),
  KEY fk_site_id (site_id),
  KEY datetime_microtime (datetime,microtime),
  CONSTRAINT fk_access_role_id
    FOREIGN KEY (role_id)
    REFERENCES cenozo.role (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_access_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_access_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
