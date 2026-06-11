CREATE TABLE role_has_service (
  role_id int(10) unsigned NOT NULL,
  service_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (role_id,service_id),
  KEY fk_role_id (role_id),
  KEY fk_service_id (service_id),
  CONSTRAINT fk_role_has_service_role_id
    FOREIGN KEY (role_id)
    REFERENCES cenozo.role (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_role_has_service_service_id
    FOREIGN KEY (service_id)
    REFERENCES service (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;