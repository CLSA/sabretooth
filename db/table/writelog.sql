CREATE TABLE writelog (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL,
  site_id int(10) unsigned NOT NULL,
  role_id int(10) unsigned NOT NULL,
  method enum('DELETE','PATCH','POST','PUT') DEFAULT NULL,
  path varchar(512) DEFAULT NULL,
  elapsed float DEFAULT NULL,
  status int(11) DEFAULT NULL,
  datetime datetime NOT NULL,
  PRIMARY KEY (id),
  KEY fk_user_id (user_id),
  KEY fk_site_id (site_id),
  KEY fk_role_id (role_id),
  KEY dk_datetime (datetime),
  CONSTRAINT fk_writelog_role_id
    FOREIGN KEY (role_id)
    REFERENCES cenozo.role (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_writelog_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_writelog_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;