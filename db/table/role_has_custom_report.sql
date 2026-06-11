CREATE TABLE role_has_custom_report (
  role_id int(10) unsigned NOT NULL,
  custom_report_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (role_id,custom_report_id),
  KEY fk_custom_report_id (custom_report_id),
  KEY fk_role_id (role_id),
  CONSTRAINT fk_role_has_custom_report_custom_report_id
    FOREIGN KEY (custom_report_id)
    REFERENCES custom_report (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_role_has_custom_report_role_id
    FOREIGN KEY (role_id)
    REFERENCES cenozo.role (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;